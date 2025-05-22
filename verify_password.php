<?php

include 'db.php';

$stationId = $_POST['stationId'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT code FROM rk_stations WHERE id = ?");
$stmt->bind_param("i", $stationId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($correctPassword);
$stmt->fetch();

if (intval($password) === $correctPassword || strtoupper($password) === strtoupper($correctPassword)) {
   echo 'correct';
} else {
   echo 'incorrect';
}

$stmt->close();
$conn->close();
