<?php
$host = "31.97.208.37";
$dbname = "u332392237_TorneoFC26";
$username = "u332392237_TorneoFC26user";
$password = "Temportal2026*";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "Error de conexión a la base de datos"]);
    exit;
}

$conn->set_charset("utf8mb4");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>