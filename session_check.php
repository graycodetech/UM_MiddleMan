<?php
// Start the session
session_start();

// Check if the user is not logged in, if so, redirect to the login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>
