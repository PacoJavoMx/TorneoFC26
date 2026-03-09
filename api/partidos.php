<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $temporada_id = isset($_GET['temporada_id']) ? (int)$_GET['temporada_id'] : 1;

    $sql = "
    SELECT
        p.id,
        p.fecha_partido,
        j1.nombre AS jugador1_nombre,
        p.score1,
        p.score2,
        j2.nombre AS jugador2_nombre,
        jg.nombre AS ganador_nombre,
        p.es_empate
    FROM partidos p
    INNER JOIN jugadores j1 ON j1.id = p.jugador1_id
    INNER JOIN jugadores j2 ON j2.id = p.jugador2_id
    LEFT JOIN jugadores jg ON jg.id = p.ganador_id
    WHERE p.temporada_id = ?
    ORDER BY p.fecha_partido DESC, p.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $temporada_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $partidos = [];

    while ($row = $result->fetch_assoc()) {
        $partidos[] = [
            "id" => (int)$row["id"],
            "fecha_partido" => $row["fecha_partido"],
            "jugador1_nombre" => $row["jugador1_nombre"],
            "score1" => (int)$row["score1"],
            "score2" => (int)$row["score2"],
            "jugador2_nombre" => $row["jugador2_nombre"],
            "ganador_nombre" => $row["ganador_nombre"],
            "es_empate" => (int)$row["es_empate"]
        ];
    }

    echo json_encode($partidos);
    $stmt->close();
    $conn->close();
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(["error" => "JSON inválido"]);
        exit;
    }

    $temporada_id = isset($data['temporada_id']) ? (int)$data['temporada_id'] : 0;
    $jugador1_id = isset($data['jugador1_id']) ? (int)$data['jugador1_id'] : 0;
    $jugador2_id = isset($data['jugador2_id']) ? (int)$data['jugador2_id'] : 0;
    $fecha_partido = isset($data['fecha_partido']) ? $data['fecha_partido'] : null;
    $score1 = isset($data['score1']) ? (int)$data['score1'] : null;
    $score2 = isset($data['score2']) ? (int)$data['score2'] : null;
    $observaciones = isset($data['observaciones']) ? $data['observaciones'] : null;

    if (!$temporada_id || !$jugador1_id || !$jugador2_id || !$fecha_partido || $score1 < 0 || $score2 < 0) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incompletos o inválidos"]);
        exit;
    }

    $ganador_id = null;
    $es_empate = 0;

    if ($score1 > $score2) {
        $ganador_id = $jugador1_id;
    } elseif ($score2 > $score1) {
        $ganador_id = $jugador2_id;
    } else {
        $es_empate = 1;
    }

    $sql = "
    INSERT INTO partidos (
        temporada_id,
        jugador1_id,
        jugador2_id,
        fecha_partido,
        score1,
        score2,
        ganador_id,
        es_empate,
        observaciones
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiisiiiis",
        $temporada_id,
        $jugador1_id,
        $jugador2_id,
        $fecha_partido,
        $score1,
        $score2,
        $ganador_id,
        $es_empate,
        $observaciones
    );

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Partido guardado",
            "id" => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "No fue posible guardar el partido"]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(["error" => "Falta el id del partido"]);
        exit;
    }

    $sql = "DELETE FROM partidos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Partido eliminado"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "No fue posible eliminar el partido"]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);
$conn->close();
?>