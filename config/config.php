<?php

// -----------------------------------------------------------------------------
// **DATABASE CONFIGURATION**
// -----------------------------------------------------------------------------
// Database connection details.
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'mpesa_payments');

// -----------------------------------------------------------------------------
// **UTILITY MASTER (UM) API CONFIGURATION**
// -----------------------------------------------------------------------------
// Credentials and endpoints for the Wonderkid UM API.
define('UM_API_BASE_URL', 'https://wonderkid.services');
define('UM_API_CLIENT_ID', 'ABC');
define('UM_API_CLIENT_SECRET', '32uBk32dNxqLuFD525fO828n24iJAoG5');
define('UM_API_CLIENT_KEY', 'YTImfxfz3ipJCZkn50WT3ofYzswc69U');

// -----------------------------------------------------------------------------
// **SMS API CONFIGURATION**
// -----------------------------------------------------------------------------
// Credentials and endpoints for the AdvantaSMS API.
define('SMS_API_URL', 'https://quicksms.advantasms.com/api/services/sendsms/');
define('SMS_API_DLR_URL', 'https://quicksms.advantasms.com/api/services/getdlr/');
define('SMS_API_KEY', 'YOURLONGKEY');
define('SMS_API_PARTNER_ID', 'YOURID');
define('SMS_API_SHORTCODE', 'SENDERID');

?>
