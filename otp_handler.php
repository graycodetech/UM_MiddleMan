<?php
session_start();
require_once 'class/Database.php';

// If the user isn't in the OTP process, redirect them.
if (!isset($_SESSION['otp_user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$submitted_otp = $_POST['otp'] ?? '';

if (empty($submitted_otp)) {
    header('Location: otp_verify.php?error=OTP is required.');
    exit;
}

$db = new Database();
$pdo = $db->getConnection();
$user_id = $_SESSION['otp_user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user) {
    $now = new DateTime();
    $otp_expiry = new DateTime($user['otp_expiry']);

    if ($user['otp_code'] === $submitted_otp && $now < $otp_expiry) {
        // OTP is valid and not expired

        // Clear OTP from database for security
        $clear_stmt = $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE id = ?");
        $clear_stmt->execute([$user_id]);

        // Unset the temporary OTP session variable
        unset($_SESSION['otp_user_id']);

        // Set the final login session variables
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['user_role'];
        $_SESSION['is_temp_password'] = (bool)$user['is_temp_password'];

        // Redirect to the dashboard
        header('Location: index.php');
        exit;

    } else {
        // OTP is invalid or expired
        header('Location: otp_verify.php?error=Invalid or expired OTP. Please try again.');
        exit;
    }
} else {
    // Should not happen, but as a fallback
    header('Location: login.php?error=An unexpected error occurred.');
    exit;
}
?>
