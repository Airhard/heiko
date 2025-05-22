<?php

include 'db.php'; // Stellt die Verbindung zur Datenbank her

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Erlaubt Cross-Origin Requests

$sql = "SELECT id, name, group_id FROM rk_teams ORDER BY name";
$result = $conn->query($sql);
$questions = [];

if ($result->num_rows > 0) {
   // output data of each row
   while ($row = $result->fetch_assoc()) {
      $questions[] = $row;
   }
   echo json_encode($questions);
} else {
   echo json_encode([]);
}

$conn->close();
