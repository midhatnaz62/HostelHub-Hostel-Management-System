<?php
session_start();

// Check whether user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user role
$userRole = strtolower(trim($_SESSION['role'] ?? ''));

// Allowed roles
$allowedRoles = ['admin', 'staff', 'student'];

if (!in_array($userRole, $allowedRoles, true)) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}
?>