<?php
// This script is intended to be run as a cron job.
// Example cron entry: */10 * * * * /usr/bin/php /path/to/your/project/cron/update_delivery_reports.php

require_once __DIR__ . '/../class/Database.php';
require_once __DIR__ . '/../class/SmsService.php';

echo "Starting SMS delivery report check...\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $smsService = new SmsService();

    // Fetch messages that are marked as 'SENT' but not yet confirmed as 'DELIVERED' or 'FAILED'.
    // We check for messages sent at least a minute ago to give them time to be delivered.
    $stmt = $pdo->prepare("SELECT id, sms_message_id FROM processed_payments WHERE sms_status = 'SENT' AND updated_at < NOW() - INTERVAL 1 MINUTE LIMIT 100");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($messages)) {
        echo "No delivery reports to check.\n";
        exit;
    }

    echo "Found " . count($messages) . " messages to check for delivery status.\n";

    foreach ($messages as $message) {
        $report = $smsService->getDeliveryReport($message['sms_message_id']);

        if ($report && isset($report['response-code']) && $report['response-code'] == 200 && isset($report['delivery-status'])) {
            $new_status = 'FAILED'; // Default status
            // According to the doc, 32 means "DeliveredToTerminal"
            if ($report['delivery-status'] == 32) {
                $new_status = 'DELIVERED';
            }

            $delivery_description = $report['delivery-description'] ?? 'No description';

            $updateStmt = $pdo->prepare("UPDATE processed_payments SET sms_status = ?, sms_delivery_status = ? WHERE id = ?");
            $updateStmt->execute([$new_status, $delivery_description, $message['id']]);

            echo "Updated DLR for message ID {$message['sms_message_id']}: {$new_status}\n";
        } else {
            // Could log this to avoid re-checking constantly if the API has an issue
            $errorDetails = json_encode($report);
            error_log("Could not retrieve DLR for message ID {$message['sms_message_id']}. Response: {$errorDetails}");
            echo "Failed to retrieve DLR for message ID {$message['sms_message_id']}.\n";
        }
    }
    echo "SMS delivery report check finished.\n";

} catch (Exception $e) {
    error_log("Fatal error in update_delivery_reports.php cron: " . $e->getMessage());
    echo "An error occurred. Check the logs.\n";
}
?>
