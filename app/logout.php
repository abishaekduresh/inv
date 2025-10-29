<?php
// logout.php

// Disable cache so back button won't show restricted pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Destroy session cookie if exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// ✅ Clear ALL cookies with a loop
if (!empty($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
    }
}

// --- Get current base directory dynamically ---
$uri = $_SERVER['REQUEST_URI'];          // e.g., /app/login
$parts = explode('/', trim($uri, '/'));  // split into parts
$baseDir = isset($parts[0]) && $parts[0] !== '' ? '/' . $parts[0] : '';

// Redirect user to login page in current base directory
header("Location: {$baseDir}/login");
exit;
