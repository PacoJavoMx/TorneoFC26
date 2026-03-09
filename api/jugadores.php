<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$sql = "SELECT id, nombre FROM jugadores WHERE activo = 1 ORDER BY id ASC";
$result = $conn->query($sql);

$jugadores = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $jugadores[] = [
            "id" => (int)$row["id"],
            "nombre" => $row["nombre"]
        ];
    }
}

echo json_encode($jugadores);
$conn->close();
?>