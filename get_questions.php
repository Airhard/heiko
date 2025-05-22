<?php

include 'db.php'; // Stellt die Verbindung zur Datenbank her

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Erlaubt Cross-Origin Requests

$groupId = isset($_GET['groupId']) ? intval($_GET['groupId']) : 0;

$sql = "SELECT id, question, option_a, option_b, option_c, option_d, correct_option, points FROM rk_questions WHERE group_id = ? ORDER BY sortable";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $groupId);
$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($questions);
$conn->close();
