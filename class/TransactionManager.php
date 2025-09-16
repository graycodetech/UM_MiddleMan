<?php
require_once __DIR__ . '/Database.php';

/**
 * Class TransactionManager
 *
 * Handles fetching transaction data for the frontend dashboard.
 */
class TransactionManager {
    private $pdo;

    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }

    /**
     * Gets the most recent OrgAccountBalance from the mpesa_c2b_payments table.
     *
     * @return string|null The balance or null if not found.
     */
    public function getLatestOrgAccountBalance() {
        if (!$this->pdo) {
            return null;
        }
        $stmt = $this->pdo->query("SELECT org_account_balance FROM mpesa_c2b_payments ORDER BY id DESC LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['org_account_balance'] : 'N/A';
    }

    /**
     * Fetches transactions with search and pagination.
     *
     * @param array $search An array containing search filters (term, type).
     * @param int $page The current page number.
     * @param int $items_per_page The number of items to display per page.
     * @return array An array containing the list of transactions and total count.
     */
    public function getTransactions($search = [], $page = 1, $items_per_page = 50) {
        if (!$this->pdo) {
            return ['transactions' => [], 'total' => 0];
        }

        $base_sql = "FROM mpesa_c2b_payments m
                     LEFT JOIN processed_payments p ON m.trans_id = p.trans_id";

        $where_clauses = [];
        $params = [];

        if (!empty($search['term']) && !empty($search['type'])) {
            switch ($search['type']) {
                case 'trans_id':
                    $where_clauses[] = "m.trans_id LIKE ?";
                    break;
                case 'account_number':
                    $where_clauses[] = "m.bill_ref_number LIKE ?";
                    break;
                case 'account_name':
                    $where_clauses[] = "p.um_account_name LIKE ?";
                    break;
            }
            $params[] = '%' . $search['term'] . '%';
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
        }

        // Get total count for pagination
        $count_stmt = $this->pdo->prepare("SELECT COUNT(m.id) as total " . $base_sql . $where_sql);
        $count_stmt->execute($params);
        $total_count = $count_stmt->fetchColumn();

        // Get paginated results
        $offset = ($page - 1) * $items_per_page;
        $order_by_sql = " ORDER BY m.id DESC";
        $limit_sql = " LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset;

        $select_sql = "SELECT m.*, p.um_account_name, p.processing_status, p.um_api_response_message, p.sms_status ";

        $data_stmt = $this->pdo->prepare($select_sql . $base_sql . $where_sql . $order_by_sql . $limit_sql);
        $data_stmt->execute($params);
        $transactions = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'transactions' => $transactions,
            'total' => $total_count
        ];
    }
}
?>
