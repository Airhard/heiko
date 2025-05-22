<?php

include 'db.php'; // Stellt die Verbindung zur Datenbank her

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Erlaubt Cross-Origin Requests


$groupId = isset($_GET['groupId']) ? intval($_GET['groupId']) : 0;

$sql = "SELECT * FROM (SELECT g.id as qid, g.id, 'Quiz' as name, g.name as gname FROM rk_questions q, rk_groups g WHERE q.group_id = g.id GROUP BY g.id
UNION
SELECT g.id as qid, s.id, s.name, g.name as gname FROM rk_stations s, rk_groups g WHERE s.group_id = g.id
ORDER BY qid)t WHERE t.qid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $groupId);
$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($questions);
$conn->close();
