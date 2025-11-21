<?php
class AppConfig {
    public const APP_NAME = 'Optical Shop Invoice Management';
    public const API_VERSION = 'v1.1';
}

function getBaseUrl($includeRequestUri = false) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                 || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base = $protocol . $host;

    // Include project subdirectory automatically
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']); // e.g., /eyelight.dureshtech.com/app
    $base .= $scriptDir === '/' ? '' : $scriptDir;

    if ($includeRequestUri) {
        $base .= $_SERVER['REQUEST_URI'];
    }

    return rtrim($base, "/");
}

function getCurrentPage() {
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return basename($currentPath, "");
}

function verifyAuth() {
    $currentPage = getCurrentPage();
    $hasToken = isset($_COOKIE['accessToken']) && !empty($_COOKIE['accessToken']);

    // Base URL WITHOUT current page
    $baseUrl = getBaseUrl();

    // If cookie missing → redirect to login (unless already there)
    if (!$hasToken && $currentPage !== 'login') {
        header("Location: " . $baseUrl . "/login");
        exit();
    }

    // If cookie exists → redirect to dashboard when on login page
    if ($hasToken && $currentPage === 'login') {
        header("Location: " . $baseUrl . "/dashboard");
        exit();
    }

    // Otherwise, allow access
}

verifyAuth();
