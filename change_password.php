<?php
require_once 'session_check.php';
require_once 'class/Database.php';

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in both fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Passwords match and meet length requirement, proceed with update
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $user_id = $_SESSION['user_id'];

        $db = new Database();
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare("UPDATE users SET password = ?, is_temp_password = 0 WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            // Update session and show success message
            $_SESSION['is_temp_password'] = false;
            $message = "Your password has been changed successfully!";
        } else {
            $error = "An error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Change Your Password</h2>
        <p>You must change your temporary password before you can proceed.</p>

        <?php if ($message): ?>
            <div class="success-message"><?php echo $message; ?></div>
            <a href="index.php" class="btn">Go to Dashboard</a>
        <?php else: ?>
            <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>
            <form action="change_password.php" method="post">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                <button type="submit" class="btn">Change Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
<style>
    .success-message { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
</style>
