<?php

include 'db.php'; // Verbindungsdaten einbinden

$stationId = isset($_GET['stationId']) ? intval($_GET['stationId']) : 0;
$teamId = isset($_GET['teamId']) ? intval($_GET['teamId']) : 0;

$sql = "SELECT COUNT(*) + 1 as passage FROM rk_scores WHERE team_id = ? and quiz_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $teamId, $stationId);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($tasks);
$conn->close();
