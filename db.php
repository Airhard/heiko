<?php

$servername = "localhost";
$username = "d040104d";
$password = "uTcmssNduyQkZnx6z43i";
$dbname = "d040104d";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
   die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
