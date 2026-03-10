<?php
/**
 * E-Lib Digital Library - Logout Handler
 * Destroys user session and redirects to login page
 */

session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/auth.php';

// Create auth manager and logout
$auth = new AuthManager();
$auth->logout();

// Redirect to login page with message
$_SESSION['flash_message'] = 'Vous avez été déconnecté avec succès.';
$_SESSION['flash_type'] = 'success';

header('Location: login.php');
exit;