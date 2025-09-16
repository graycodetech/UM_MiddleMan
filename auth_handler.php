<?php
session_start();

require_once 'class/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header('Location: login.php?error=Username and password are required.');
        exit;
    }

    $db = new Database();
    $pdo = $db->getConnection();

    if ($pdo) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if the account is active
            if (!$user['is_active']) {
                header('Location: login.php?error=Your account has been deactivated.');
                exit;
            }

            // If user is admin, log them in directly
            if ($user['user_role'] === 'admin') {
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['is_temp_password'] = (bool)$user['is_temp_password'];
                header('Location: index.php');
                exit;
            }

            // If user is a regular user, start the OTP process
            require_once 'class/SmsService.php';
            $otp = rand(10000, 99999);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            $update_stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
            $update_stmt->execute([$otp, $otp_expiry, $user['id']]);

            // Send OTP via SMS
            $smsService = new SmsService();
            $sms_message = "Your login OTP is: $otp. It is valid for 5 minutes.";
            $smsService->sendSms($user['phone_number'], $sms_message);

            // Store user ID temporarily and redirect to OTP verification page
            $_SESSION['otp_user_id'] = $user['id'];
            header('Location: otp_verify.php');
            exit;

        } else {
            // Invalid credentials
            header('Location: login.php?error=Invalid username or password.');
            exit;
        }
    } else {
        // Database connection error
        header('Location: login.php?error=Database connection error.');
        exit;
    }
} else {
    // Redirect if accessed directly
    header('Location: login.php');
    exit;
}
?>
