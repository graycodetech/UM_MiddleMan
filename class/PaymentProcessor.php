<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/UmApi.php';

/**
 * Class PaymentProcessor
 *
 * Orchestrates the payment processing workflow.
 */
class PaymentProcessor {
    private $db;
    private $pdo;
    private $umApi;

    public function __construct() {
        $this->db = new Database();
        $this->pdo = $this->db->getConnection();
        $this->umApi = new UmApi(); // This will trigger authentication
    }

    /**
     * Fetches and processes pending M-Pesa transactions.
     */
    public function processPendingTransactions() {
        $transactions = $this->getPendingMpesaPayments();

        foreach ($transactions as $txn) {
            // Use a transaction to ensure atomicity
            $this->pdo->beginTransaction();
            try {
                // 1. Check if already processed to prevent race conditions
                if ($this->isTransactionProcessed($txn['trans_id'])) {
                    $this->updateMpesaPaymentSyncStatus($txn['id'], 2); // Mark as duplicate
                    $this->pdo->commit();
                    continue;
                }

                // 2. Validate account with UM API
                $accountInfo = $this->umApi->getAccountInfo($txn['bill_ref_number']);

                if (!$accountInfo || $accountInfo['response'] !== '00') {
                    $this->handleAccountNotFound($txn, $accountInfo);
                } else {
                    $this->handleSuccessfulValidation($txn, $accountInfo);
                }

                // 3. Mark the original M-Pesa transaction as processed
                $this->updateMpesaPaymentSyncStatus($txn['id'], 1);

                $this->pdo->commit();

            } catch (Exception $e) {
                $this->pdo->rollBack();
                error_log("Failed to process transaction {$txn['trans_id']}: " . $e->getMessage());
            }
        }
    }

    private function handleAccountNotFound($txn, $apiResponse) {
        $message = "We have received Ksh {$txn['trans_amount']} Via Mpesa Reference {$txn['first_name']} - {$txn['trans_id']} for AC {$txn['bill_ref_number']}, however this account is not Available. Call 0737442525 During office hours for assistance.";

        $this->createProcessedPaymentEntry($txn, [
            'status' => 'FAILED_ACCOUNT_NOT_FOUND',
            'api_response' => $apiResponse,
            'sms_message' => $message
        ]);
    }

    private function handleSuccessfulValidation($txn, $accountInfo) {
        // Get balance before posting payment
        $balanceInfo = $this->umApi->getBalance($txn['bill_ref_number']);
        $balance_before = isset($balanceInfo['amount']) ? (float)$balanceInfo['amount'] : 0.0;

        // Post payment
        $paymentData = [
            'account_number' => $txn['bill_ref_number'],
            'customer_name' => $txn['first_name'],
            'payment_reference' => $txn['trans_id'],
            'amount' => $txn['trans_amount'],
            'customer_mobile' => $accountInfo['customer_mobileno']
        ];
        $paymentResponse = $this->umApi->postPayment($paymentData);

        if (isset($paymentResponse['response']) && $paymentResponse['response'] === '00') {
            // Success
            $balance_after = $balance_before + (float)$txn['trans_amount'];
            $message = "We have received Ksh {$txn['trans_amount']} Via Mpesa Reference {$txn['first_name']} - {$txn['trans_id']} for AC {$txn['bill_ref_number']}. The New AC balance is Ksh {$balance_after}, Thank you.";

            $this->createProcessedPaymentEntry($txn, [
                'status' => 'SUCCESS',
                'account_info' => $accountInfo,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'api_response' => $paymentResponse,
                'sms_message' => $message
            ]);
        } else {
            // Handle failed payment posting (e.g., duplicate)
            $status = 'FAILED_API_ERROR';
            if (isset($paymentResponse['response_message']) && $paymentResponse['response_message'] === 'DUPLICATE_TRANSACTION') {
                $status = 'FAILED_DUPLICATE';
            }
            $this->createProcessedPaymentEntry($txn, [
                'status' => $status,
                'account_info' => $accountInfo,
                'api_response' => $paymentResponse,
                'sms_message' => null // No SMS for this case
            ]);
        }
    }

    private function getPendingMpesaPayments() {
        $stmt = $this->pdo->prepare("SELECT * FROM mpesa_c2b_payments WHERE push_sync = 0 ORDER BY id ASC LIMIT 10");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function isTransactionProcessed($trans_id) {
        $stmt = $this->pdo->prepare("SELECT id FROM processed_payments WHERE trans_id = ?");
        $stmt->execute([$trans_id]);
        return $stmt->fetchColumn() !== false;
    }

    private function createProcessedPaymentEntry($txn, $data) {
        $sql = "INSERT INTO processed_payments (
                    mpesa_c2b_payment_id, trans_id, trans_time, trans_amount, business_short_code,
                    bill_ref_number, msisdn, first_name, um_account_name, um_account_status,
                    um_customer_mobileno, balance_before_credit, balance_after_credit,
                    um_api_response_code, um_api_response_message, um_transaction_id,
                    processing_status, sms_message
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);

        $params = [
            $txn['id'],
            $txn['trans_id'],
            $txn['trans_time'],
            $txn['trans_amount'],
            $txn['business_short_code'],
            $txn['bill_ref_number'],
            $txn['msisdn'],
            $txn['first_name'],
            $data['account_info']['account_name'] ?? null,
            $data['account_info']['account_status'] ?? null,
            $data['account_info']['customer_mobileno'] ?? null,
            $data['balance_before'] ?? null,
            $data['balance_after'] ?? null,
            $data['api_response']['response'] ?? $data['api_response']['status'] ?? null,
            $data['api_response']['response_message'] ?? $data['api_response']['message'] ?? null,
            $data['api_response']['transaction_id'] ?? null,
            $data['status'],
            $data['sms_message']
        ];

        $stmt->execute($params);
    }

    private function updateMpesaPaymentSyncStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE mpesa_c2b_payments SET push_sync = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
}
?>
