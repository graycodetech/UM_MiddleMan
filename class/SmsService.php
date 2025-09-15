<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Class SmsService
 *
 * Handles sending SMS and checking delivery reports via the AdvantaSMS API.
 */
class SmsService {
    private $api_url = SMS_API_URL;
    private $dlr_url = SMS_API_DLR_URL;
    private $api_key = SMS_API_KEY;
    private $partner_id = SMS_API_PARTNER_ID;
    private $shortcode = SMS_API_SHORTCODE;

    /**
     * Sends an SMS to a specified mobile number.
     *
     * @param string $mobile The recipient's mobile number in international format (e.g., 2547...).
     * @param string $message The text message to send.
     * @return array|null The decoded JSON response from the API or null on failure.
     */
    public function sendSms($mobile, $message) {
        $payload = [
            'apikey' => $this->api_key,
            'partnerID' => $this->partner_id,
            'message' => $message,
            'shortcode' => $this->shortcode,
            'mobile' => $mobile
        ];

        return $this->sendRequest($this->api_url, $payload);
    }

    /**
     * Fetches the delivery report for a specific message.
     *
     * @param string $message_id The unique ID of the message to check.
     * @return array|null The decoded JSON response from the API or null on failure.
     */
    public function getDeliveryReport($message_id) {
        $payload = [
            'apikey' => $this->api_key,
            'partnerID' => $this->partner_id,
            'messageID' => $message_id
        ];

        return $this->sendRequest($this->dlr_url, $payload);
    }

    /**
     * A generic method to send POST requests to the SMS API.
     *
     * @param string $url The API endpoint URL.
     * @param array $data The payload to send.
     * @return array|null The decoded JSON response or null on error.
     */
    private function sendRequest($url, $data) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            error_log("cURL Error for SMS API ($url): " . $curl_error);
            return null;
        }

        if ($http_code >= 400) {
            error_log("HTTP Error $http_code for SMS API ($url). Response: $response");
            return null;
        }

        return json_decode($response, true);
    }
}
?>
