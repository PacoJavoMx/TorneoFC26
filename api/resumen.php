<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$temporada_id = isset($_GET['temporada_id']) ? (int)$_GET['temporada_id'] : 1;

$sql = "
SELECT
    t.id AS temporada_id,
    t.nombre AS temporada,

    COALESCE(mi1.ganados_iniciales, 0) + COALESCE(pr.francisco_reales, 0) AS francisco_ganados,
    COALESCE(mi2.ganados_iniciales, 0) + COALESCE(pr.josue_reales, 0) AS josue_ganados,
    COALESCE(pr.empates, 0) AS empates,
    COALESCE(pr.total_partidos, 0) AS total_partidos_reales
FROM temporadas t
LEFT JOIN (
    SELECT temporada_id, jugador_id, ganados_iniciales
    FROM marcador_inicial
) mi1
    ON mi1.temporada_id = t.id AND mi1.jugador_id = 1
LEFT JOIN (
    SELECT temporada_id, jugador_id, ganados_iniciales
    FROM marcador_inicial
) mi2
    ON mi2.temporada_id = t.id AND mi2.jugador_id = 2
LEFT JOIN (
    SELECT
        temporada_id,
        SUM(CASE WHEN ganador_id = 1 THEN 1 ELSE 0 END) AS francisco_reales,
        SUM(CASE WHEN ganador_id = 2 THEN 1 ELSE 0 END) AS josue_reales,
        SUM(CASE WHEN es_empate = 1 THEN 1 ELSE 0 END) AS empates,
        COUNT(*) AS total_partidos
    FROM partidos
    GROUP BY temporada_id
) pr
    ON pr.temporada_id = t.id
WHERE t.id = ?
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
    "total_partidos" => (int)$row["total_partidos_reales"],
    "campeon_actual" => $campeon,
    "diferencia" => $diferencia
]);

$stmt->close();
$conn->close();
?>