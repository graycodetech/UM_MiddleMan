<?php
require_once 'session_check.php';
require_once 'class/Database.php';
require_once 'class/SmsService.php';

// --- Authorization Check ---
if ($_SESSION['user_role'] !== 'admin') {
    die("Access Denied: You do not have permission to view this page.");
}

$db = new Database();
$pdo = $db->getConnection();
$message = '';
$error = '';

// --- Form Submission Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $phone_number = $_POST['phone_number'];

    if (empty($username) || empty($phone_number)) {
        $error = "Username and phone number are required.";
    } else {
        // Generate a random temporary password
        $temp_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

        try {
            // Insert the new user into the database
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password, phone_number, is_temp_password, user_role) VALUES (?, ?, ?, 1, 'user')"
            );
            $stmt->execute([$username, $hashed_password, $phone_number]);

            // Send credentials via SMS
            $smsService = new SmsService();
            $sms_message = "Welcome! Your login details are: Username: $username, Temporary Password: $temp_password. You will be required to change this on your first login.";
            $sms_response = $smsService->sendSms($phone_number, $sms_message);

            if ($sms_response && isset($sms_response['responses'][0]['response-code']) && $sms_response['responses'][0]['response-code'] == 200) {
                $message = "User '$username' created successfully. Credentials have been sent via SMS.";
            } else {
                $error = "User '$username' created, but failed to send SMS. Please provide them their credentials manually: Temp Pass: $temp_password";
            }

        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Integrity constraint violation (duplicate entry)
                $error = "Error: Username or phone number already exists.";
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// --- Fetch Existing Users ---
$users_stmt = $pdo->query("SELECT id, username, phone_number, user_role, created_at FROM users ORDER BY id DESC");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h1>User Management</h1>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-logout">Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="card">
            <h3>Add New User</h3>
            <?php if ($message): ?><div class="success-message"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error-message"><?php echo $error; ?></div><?php endif; ?>
            <form action="manage_users.php" method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number (e.g., 254...)</label>
                        <input type="text" name="phone_number" id="phone_number" required>
                    </div>
                </div>
                <button type="submit" name="add_user" class="btn">Add User</button>
            </form>
        </div>

        <div class="card">
            <h3>Existing Users</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Phone Number</th>
                            <th>Role</th>
                            <th>Date Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_role']); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
<style>
    .success-message { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
</style>
