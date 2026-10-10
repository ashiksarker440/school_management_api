<?php
function send_json($status, $payload)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

$configuredOrigins = getenv('AUTH_ALLOWED_ORIGINS');
$allowedOrigins = $configuredOrigins
    ? array_map('trim', explode(',', $configuredOrigins))
    : array('http://localhost:5173', 'http://127.0.0.1:5173');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if ($origin !== '' && !in_array($origin, $allowedOrigins, true)) {
    send_json(403, array('message' => 'This origin is not allowed.'));
}

if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-Requested-With');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('session.use_strict_mode', '1');
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(0, '/', '', $isSecure, true);
session_start();

function require_method($method)
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method . ', OPTIONS');
        send_json(405, array('message' => 'Method not allowed.'));
    }
}

function read_json_body()
{
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) {
        send_json(400, array('message' => 'A valid JSON request body is required.'));
    }
    return $body;
}

function database_connection()
{
    $host = getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost';
    $user = getenv('DB_USER') ? getenv('DB_USER') : 'root';
    $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
    $database = getenv('DB_NAME') ? getenv('DB_NAME') : 'react_php';

    mysqli_report(MYSQLI_REPORT_OFF);
    $db = new mysqli($host, $user, $password, $database);
    if ($db->connect_errno) {
        send_json(500, array('message' => 'Could not connect to the application database.'));
    }

    $db->set_charset('utf8mb4');
    return $db;
}