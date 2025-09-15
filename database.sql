-- Table structure for table `mpesa_c2b_payments`
-- This table stores raw M-Pesa C2B transactions as they are received.

CREATE TABLE `mpesa_c2b_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(50) DEFAULT NULL,
  `trans_id` varchar(50) DEFAULT NULL,
  `trans_time` datetime DEFAULT NULL,
  `trans_amount` decimal(10,2) DEFAULT NULL,
  `business_short_code` varchar(20) DEFAULT NULL,
  `bill_ref_number` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `org_account_balance` varchar(50) DEFAULT NULL,
  `third_party_trans_id` varchar(50) DEFAULT NULL,
  `msisdn` varchar(250) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `raw_request` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `push_sync` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trans_id` (`trans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `users`
-- Storing user credentials for the web interface.
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `user_role` enum('admin','user') NOT NULL DEFAULT 'user',
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `is_temp_password` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `phone_number` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `processed_payments`
-- This table tracks the processing of each M-Pesa transaction, its interaction with the UM API, and the status of SMS notifications.

CREATE TABLE `processed_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mpesa_c2b_payment_id` int(11) NOT NULL,
  `trans_id` varchar(50) DEFAULT NULL,
  `trans_time` datetime DEFAULT NULL,
  `trans_amount` decimal(10,2) DEFAULT NULL,
  `business_short_code` varchar(20) DEFAULT NULL,
  `bill_ref_number` varchar(100) DEFAULT NULL,
  `msisdn` varchar(250) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `um_account_name` varchar(100) DEFAULT NULL,
  `um_account_status` varchar(50) DEFAULT NULL,
  `um_customer_mobileno` varchar(50) DEFAULT NULL,
  `balance_before_credit` decimal(10,2) DEFAULT NULL,
  `balance_after_credit` decimal(10,2) DEFAULT NULL,
  `um_api_response_code` varchar(10) DEFAULT NULL,
  `um_api_response_message` varchar(255) DEFAULT NULL,
  `um_transaction_id` varchar(50) DEFAULT NULL,
  `processing_status` enum('PENDING_VALIDATION', 'PENDING_POSTING', 'SUCCESS', 'FAILED_ACCOUNT_NOT_FOUND', 'FAILED_DUPLICATE', 'FAILED_API_ERROR') NOT NULL DEFAULT 'PENDING_VALIDATION',
  `sms_message` text DEFAULT NULL,
  `sms_status` enum('PENDING', 'SENT', 'DELIVERED', 'FAILED') NOT NULL DEFAULT 'PENDING',
  `sms_message_id` varchar(100) DEFAULT NULL,
  `sms_delivery_status` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `mpesa_c2b_payment_id` (`mpesa_c2b_payment_id`),
  UNIQUE KEY `trans_id` (`trans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
