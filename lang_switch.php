<?php
session_start();

// Toggle language
if (isset($_SESSION['lang']) && $_SESSION['lang'] === 'en') {
    $_SESSION['lang'] = 'ar';
} else {
    $_SESSION['lang'] = 'en';
}

// Redirect back to the previous page
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $redirect");
exit();
