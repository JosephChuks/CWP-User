<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['tokengui'])) {
    header("HTTP/1.1 403 Forbidden");
    echo "Access denied. Invalid session.";
    exit;
}

$username = $_SESSION['username'];
$secure_token = $_SESSION['tokengui'];
$expiry_time = time() + 31536000;

// Store the token in a secure file
$token_dir = "/home/$username/.tokens/";
if (!is_dir($token_dir)) {
    mkdir($token_dir, 0700, true);
}


if (is_dir($token_dir)) {
    $files = scandir($token_dir);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $filepath = $token_dir . $file;
            unlink($filepath);
        }
    }
}

$token_data = [
    'username' => $username,
    'token' => $secure_token,
    'expiry' => $expiry_time
];

// Store token data in file
file_put_contents($token_dir . $secure_token, json_encode($token_data));

header("Location: filemanager.php");
exit;
