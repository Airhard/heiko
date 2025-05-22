<?php

include 'db.php'; // Verbindungsdaten einbinden

$stationId = isset($_GET['stationId']) ? intval($_GET['stationId']) : 0;

$sql = "SELECT id, task, points, btn1, btn2, btn3 FROM rk_station_tasks WHERE station_id = ? ORDER BY sortable";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $stationId);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($tasks);
$conn->close();
