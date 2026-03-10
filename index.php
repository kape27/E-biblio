<?php
/**
 * E-Lib Digital Library - Main Entry Point
 * Redirects users to appropriate dashboard based on authentication status
 */

session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// User is logged in, redirect to appropriate dashboard based on role
$userRole = $_SESSION['user_role'] ?? 'user';

switch ($userRole) {
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    case 'librarian':
        header('Location: librarian/dashboard.php');
        break;
    case 'user':
    default:
        header('Location: user/dashboard.php');
        break;
}

exit;