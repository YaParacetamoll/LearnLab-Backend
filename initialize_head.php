<?php
require_once "vendor/autoload.php";

function extractOriginForCors(string $url): ?array
{
    $parsedUrl = parse_url($url);

    if (!is_array($parsedUrl)) {
        return null;
    }

    if (!isset($parsedUrl['scheme']) || !isset($parsedUrl['host']) || empty($parsedUrl['host'])) {
        return null;
    }

    $scheme = $parsedUrl['scheme'];
    $host = $parsedUrl['host'];
    $port = $parsedUrl['port'] ?? null;

    if ($port !== null) {
        $host .= ':' . $port;
    }

    return ["origin" => $scheme . "://" . $host, "host" => $host]; 
}

// error_log(print_r($_SERVER, true));

$url = "";
if (isset($_SERVER["HTTP_REFERER"])) {
    $url = $_SERVER["HTTP_REFERER"];
} elseif (isset($_SERVER["HTTP_ORIGIN"])) {
    $url = $_SERVER["HTTP_ORIGIN"];
}

if ($url !== "" && !is_null($url)) {
    $origin = extractOriginForCors($url);
} else {
    $origin = ["origin" => "*", "host" => "*"];
}


if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    header("Access-Control-Allow-Origin: " . $origin["origin"]);
    header(
        "Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS"
    );
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 1728000");
    header("Content-Length: 0");
    header("Content-Type: text/plain");
    die();
}

header("Access-Control-Allow-Origin: " . $origin["origin"]);
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeload();
// session_start();
?>
