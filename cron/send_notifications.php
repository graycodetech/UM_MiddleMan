<?php
// This script is intended to be run as a cron job.
// Example cron entry: */1 * * * * /usr/bin/php /path/to/your/project/cron/send_notifications.php

require_once __DIR__ . '/../class/Database.php';
require_once __DIR__ . '/../class/SmsService.php';

echo "Starting SMS notification sending...\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $smsService = new SmsService();

    // Fetch pending SMS notifications
    $stmt = $pdo->prepare("SELECT id, um_customer_mobileno, sms_message FROM processed_payments WHERE sms_status = 'PENDING' AND sms_message IS NOT NULL AND um_customer_mobileno IS NOT NULL LIMIT 50");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($notifications)) {
        echo "No pending SMS notifications to send.\n";
        exit;
    }

    echo "Found " . count($notifications) . " pending SMS to send.\n";

    foreach ($notifications as $notification) {
        // The API expects the mobile number without the leading '+' and with country code
        $mobileNumber = preg_replace('/^\+/', '', $notification['um_customer_mobileno']);
        $message = $notification['sms_message'];

        $response = $smsService->sendSms($mobileNumber, $message);

        // Check for a successful response structure
        if ($response && isset($response['responses'][0]['response-code']) && $response['responses'][0]['response-code'] == 200) {
            $messageId = $response['responses'][0]['messageid'];
            $updateStmt = $pdo->prepare("UPDATE processed_payments SET sms_status = 'SENT', sms_message_id = ? WHERE id = ?");
            $updateStmt->execute([$messageId, $notification['id']]);
            echo "Successfully sent SMS to {$mobileNumber}. Message ID: {$messageId}\n";
        } else {
            $updateStmt = $pdo->prepare("UPDATE processed_payments SET sms_status = 'FAILED' WHERE id = ?");
            $updateStmt->execute([$notification['id']]);
            $errorDetails = json_encode($response);
            error_log("Failed to send SMS for processed_payment ID {$notification['id']}. Response: {$errorDetails}");
            echo "Failed to send SMS to {$mobileNumber}.\n";
        }
    }
    echo "SMS notification sending finished.\n";

} catch (Exception $e) {
    error_log("Fatal error in send_notifications.php cron: " . $e->getMessage());
    echo "An error occurred. Check the logs.\n";
}
?>
