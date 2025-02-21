<?php
session_start();
header("Content-Type: application/json");

$domain = $_SESSION["general"]["domain"];

if (!$domain) {
    echo json_encode(["status" => false, "message" => "Invalid Session"]);
    exit();
}

$cacheTime = 604800;
$cacheDir = __DIR__ . "/validate_ssl";

if (!is_dir($cacheDir)) {
    echo json_encode([
        "status" => false,
        "message" => "cache folder not found. create folder: validate_ssl",
    ]);
    exit();
}

$sanitizedDomain = preg_replace("/[^a-zA-Z0-9\.-]/", "_", $domain);
$cacheFile = "$cacheDir/cache_https_$sanitizedDomain.json";

if (file_exists($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
    $cachedData = json_decode(file_get_contents($cacheFile), true);
    if (
        json_last_error() === JSON_ERROR_NONE &&
        isset($cachedData["status"]) &&
        $cachedData["status"] === true
    ) {
        echo json_encode($cachedData);
        exit();
    }
}

function isHttpsAccessible($domain)
{
    $url = "https://$domain";
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $http_code >= 200 && $http_code < 400;
}

$status = isHttpsAccessible($domain);

if ($status === true) {
    file_put_contents($cacheFile, json_encode(["status" => $status]));
}

echo json_encode(["status" => $status]);
?>
