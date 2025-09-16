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

// --- ACTION HANDLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_user_id = $_POST['user_id'] ?? 0;

    // Protect main admin account from any modifications
    if ($action_user_id == 1) {
        $error = "Error: The main administrator account cannot be modified.";

    // Handle Add User
    } elseif (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $phone_number = $_POST['phone_number'];
        $user_role = $_POST['user_role'];

        if (empty($username) || empty($phone_number)) {
            $error = "Username and phone number are required.";
        } else {
            $temp_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
            if (!in_array($user_role, ['admin', 'user'])) $user_role = 'user';

            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, phone_number, is_temp_password, user_role) VALUES (?, ?, ?, 1, ?)");
                $stmt->execute([$username, $hashed_password, $phone_number, $user_role]);

                $smsService = new SmsService();
                $sms_message = "Welcome! Your login details are: Username: $username, Temporary Password: $temp_password.";
                $smsService->sendSms($phone_number, $sms_message);
                $message = "User '$username' created successfully and credentials sent via SMS.";
            } catch (PDOException $e) {
                $error = ($e->getCode() == '23000') ? "Error: Username or phone number already exists." : "Database error: " . $e->getMessage();
            }
        }

    // Handle Deactivate User
    } elseif (isset($_POST['deactivate_user'])) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$action_user_id]);
        $message = "User account deactivated.";

    // Handle Activate User
    } elseif (isset($_POST['activate_user'])) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $stmt->execute([$action_user_id]);
        $message = "User account activated.";

    // Handle Change Role
    } elseif (isset($_POST['change_role'])) {
        $new_role = $_POST['new_role'];
        if (in_array($new_role, ['admin', 'user'])) {
            $stmt = $pdo->prepare("UPDATE users SET user_role = ? WHERE id = ?");
            $stmt->execute([$new_role, $action_user_id]);
            $message = "User role updated successfully.";
        } else {
            $error = "Invalid role specified.";
        }

    // Handle Reset Password
    } elseif (isset($_POST['reset_password'])) {
        $user_stmt = $pdo->prepare("SELECT phone_number FROM users WHERE id = ?");
        $user_stmt->execute([$action_user_id]);
        $user_phone = $user_stmt->fetchColumn();

        if ($user_phone) {
            $temp_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234GHI789'), 0, 8);
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = ?, is_temp_password = 1 WHERE id = ?");
            $stmt->execute([$hashed_password, $action_user_id]);

            $smsService = new SmsService();
            $sms_message = "Your password has been reset. Your new temporary password is: $temp_password.";
            $smsService->sendSms($user_phone, $sms_message);
            $message = "Password reset successfully. A new temporary password has been sent via SMS.";
        } else {
            $error = "Could not find user's phone number to send reset password.";
        }
    }
}

// --- Fetch Existing Users ---
$users_stmt = $pdo->query("SELECT id, username, phone_number, user_role, is_active, created_at FROM users ORDER BY id DESC");
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
                    <div class="form-group">
                        <label for="user_role">Role</label>
                        <select name="user_role" id="user_role" required>
                            <option value="user" selected>User</option>
                            <option value="admin">Admin</option>
                        </select>
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
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_role']); ?></td>
                                <td>
                                    <span class="status <?php echo $user['is_active'] ? 'success' : 'failed-api-error'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td>
                                    <?php if ($user['id'] != 1): // Protect main admin ?>
                                        <form action="manage_users.php" method="post" class="action-form">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <?php if ($user['is_active']): ?>
                                                <button type="submit" name="deactivate_user" class="btn btn-sm btn-warning" title="Deactivate User">D</button>
                                            <?php else: ?>
                                                <button type="submit" name="activate_user" class="btn btn-sm btn-success" title="Activate User">A</button>
                                            <?php endif; ?>
                                        </form>

                                        <form action="manage_users.php" method="post" class="action-form">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="new_role" onchange="this.form.submit()">
                                                <option value="user" <?php echo ($user['user_role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo ($user['user_role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <input type="hidden" name="change_role" value="1">
                                        </form>

                                        <form action="manage_users.php" method="post" class="action-form">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="reset_password" class="btn btn-sm btn-danger" title="Send Password Reset">&#128274;</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
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
