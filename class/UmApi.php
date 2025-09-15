<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Class UmApi
 *
 * Handles all interactions with the Utility Master (UM) API.
 */
class UmApi {
    private $base_url = UM_API_BASE_URL;
    private $client_id = UM_API_CLIENT_ID;
    private $client_secret = UM_API_CLIENT_SECRET;
    private $client_key = UM_API_CLIENT_KEY;
    private $jwt_token;

    /**
     * UmApi constructor.
     * Attempts to authenticate and get a JWT upon instantiation.
     */
    public function __construct() {
        $this->authenticate();
    }

    /**
     * Authenticates with the UM API and retrieves a JWT.
     *
     * @return bool True on success, false on failure.
     */
    private function authenticate() {
        $url = $this->base_url . '/auth/create_token.php';
        $payload = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'client_key' => $this->client_key
        ];

        $response = $this->sendRequest($url, 'POST', $payload);

        if (isset($response['status']) && $response['status'] == '00' && isset($response['jwt'])) {
            $this->jwt_token = $response['jwt'];
            return true;
        }

        error_log('UM API Authentication Failed: ' . json_encode($response));
        return false;
    }

    /**
     * Retrieves account information.
     *
     * @param string $account_number The account number to validate.
     * @return array|null The API response or null on failure.
     */
    public function getAccountInfo($account_number) {
        if (!$this->jwt_token) return null;

        $url = $this->base_url . '/payments/validate.php';
        $payload = [
            'account_number' => $account_number,
            'txn_type' => 'account_info'
        ];

        return $this->sendRequest($url, 'POST', $payload, $this->getAuthHeaders());
    }

    /**
     * Retrieves the current balance for an account.
     *
     * @param string $account_number The account number to check.
     * @return array|null The API response or null on failure.
     */
    public function getBalance($account_number) {
        if (!$this->jwt_token) return null;

        $url = $this->base_url . '/payments/validate.php';
        $payload = [
            'account_number' => $account_number,
            'txn_type' => 'check_balance'
        ];

        return $this->sendRequest($url, 'POST', $payload, $this->getAuthHeaders());
    }

    /**
     * Posts a payment to the UM API.
     *
     * @param array $payment_data The payment details.
     * @return array|null The API response or null on failure.
     */
    public function postPayment($payment_data) {
        if (!$this->jwt_token) return null;

        $url = $this->base_url . '/payments/notify.php';

        // Ensure payload matches the API spec
        $payload = [
            "channel" => "mobile_money",
            "account_number" => $payment_data['account_number'],
            "customer_name" => $payment_data['customer_name'],
            "payment_reference" => $payment_data['payment_reference'],
            "amount" => $payment_data['amount'],
            "currency" => "KES",
            "origin_accountno" => "", // As per spec
            "payment_mode" => "", // As per spec
            "origin_bank" => "", // As per spec
            "customer_mobile" => $payment_data['customer_mobile']
        ];

        return $this->sendRequest($url, 'POST', $payload, $this->getAuthHeaders());
    }

    /**
     * A generic method to send cURL requests.
     *
     * @param string $url The URL for the request.
     * @param string $method The HTTP method (e.g., 'POST').
     * @param array $data The data to send with the request.
     * @param array $headers Additional HTTP headers.
     * @return array|null The decoded JSON response or null on error.
     */
    private function sendRequest($url, $method, $data = [], $headers = []) {
        $ch = curl_init();

        $default_headers = [
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($default_headers, $headers));

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            error_log("cURL Error for $url: " . $curl_error);
            return null;
        }

        if ($http_code >= 400) {
            error_log("HTTP Error $http_code for $url. Response: $response");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Returns the authorization headers required for authenticated API calls.
     *
     * @return array The authorization headers.
     */
    private function getAuthHeaders() {
        return [
            'Authorization: ' . $this->jwt_token
        ];
    }
}
?>
