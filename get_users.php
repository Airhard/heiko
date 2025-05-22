<?php

include 'db.php'; // Stellt die Verbindung zur Datenbank her

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Erlaubt Cross-Origin Requests


$teamId = isset($_GET['teamId']) ? intval($_GET['teamId']) : 0;

if ($teamId > 0) {
   $sql = "SELECT u.id, u.username FROM rk_users u JOIN rk_team_users tu ON u.id = tu.user_id WHERE tu.team_id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $teamId);
   $stmt->execute();
   $result = $stmt->get_result();
   $users = $result->fetch_all(MYSQLI_ASSOC);
   echo json_encode($users);
} else {
   echo json_encode([]); // Sendet eine leere Liste, wenn keine Team-ID angegeben ist
}

$conn->close();
