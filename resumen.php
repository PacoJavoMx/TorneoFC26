<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$temporada_id = isset($_GET['temporada_id']) ? (int)$_GET['temporada_id'] : 1;

$sql = "
SELECT
    t.id AS temporada_id,
    t.nombre AS temporada,
    COALESCE(SUM(CASE WHEN p.ganador_id = 1 THEN 1 ELSE 0 END), 0) AS francisco_ganados,
    COALESCE(SUM(CASE WHEN p.ganador_id = 2 THEN 1 ELSE 0 END), 0) AS josue_ganados,
    COALESCE(SUM(CASE WHEN p.es_empate = 1 THEN 1 ELSE 0 END), 0) AS empates,
    COUNT(p.id) AS total_partidos
FROM temporadas t
LEFT JOIN partidos p
    ON p.temporada_id = t.id
WHERE t.id = ?
GROUP BY t.id, t.nombre
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $temporada_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo json_encode(["error" => "Temporada no encontrada"]);
    exit;
}

$francisco = (int)$row["francisco_ganados"];
$josue = (int)$row["josue_ganados"];

$campeon = "Empate";
$diferencia = 0;

if ($francisco > $josue) {
    $campeon = "Francisco";
    $diferencia = $francisco - $josue;
} elseif ($josue > $francisco) {
    $campeon = "Josue";
    $diferencia = $josue - $francisco;
}

echo json_encode([
    "temporada_id" => (int)$row["temporada_id"],
    "temporada" => $row["temporada"],
    "francisco_ganados" => $francisco,
    "josue_ganados" => $josue,
    "empates" => (int)$row["empates"],
    "total_partidos" => (int)$row["total_partidos"],
    "campeon_actual" => $campeon,
    "diferencia" => $diferencia
]);

$stmt->close();
$conn->close();
?>