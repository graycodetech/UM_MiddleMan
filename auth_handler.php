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
            // Password is correct, start the session
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to the main dashboard
            header('Location: index.php');
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
