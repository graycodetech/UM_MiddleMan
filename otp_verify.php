<?php
session_start();

// If the user isn't in the OTP process, redirect them.
if (!isset($_SESSION['otp_user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Payment Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Enter OTP</h2>
        <p>A 5-digit One-Time Password has been sent to your registered phone number.</p>
        <?php
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        <form action="otp_handler.php" method="post">
            <div class="form-group">
                <label for="otp">OTP Code</label>
                <input type="text" name="otp" id="otp" required maxlength="5" pattern="\d{5}" title="Please enter a 5-digit OTP">
            </div>
            <button type="submit" class="btn">Verify</button>
        </form>
    </div>
</body>
</html>
