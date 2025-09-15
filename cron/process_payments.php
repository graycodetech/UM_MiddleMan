<?php
// This script is intended to be run as a cron job.
// Example cron entry: */5 * * * * /usr/bin/php /path/to/your/project/cron/process_payments.php

require_once __DIR__ . '/../class/PaymentProcessor.php';

echo "Starting payment processing...\n";

try {
    $processor = new PaymentProcessor();
    $processor->processPendingTransactions();
    echo "Payment processing finished successfully.\n";
} catch (Exception $e) {
    error_log("Fatal error in process_payments.php cron: " . $e->getMessage());
    echo "An error occurred. Check the logs.\n";
}

?>
