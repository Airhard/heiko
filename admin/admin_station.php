<?php
include '../db.php';

// Mitglied löschen
if (isset($_GET["delStation"]) && !empty($_GET["delStation"])) {
   $questionid = $_GET["delStation"];
   $sql = "DELETE FROM rk_stations WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $questionid);
   $stmt->execute();
   $stmt->close();
}

// Logik zum Hinzufügen von Fragen
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   // Daten aus dem Formular holen und in Variablen speichern
   $station = $_POST['station'];
   $code = $_POST['code'];
   $group = $_POST['group'];
   $information = $_POST['information'];
   $btn1 = $_POST['btn1'];
   $btn2 = $_POST['btn2'];
   $btn3 = $_POST['btn3'];

   // SQL zum Einfügen der Frage
   $sql = "INSERT INTO rk_stations (name, code, group_id, information, btn1, btn2, btn3) VALUES (?, ?, ?, ?, ?, ?, ?)";

   $stmt = $conn->prepare($sql);
   $stmt->bind_param("siisiii", $station, $code, $group, $information, $btn1, $btn2, $btn3);
   $stmt->execute();
   $stmt->close();
}

// Station bearbeiten
if (isset($_GET["editStation"])) {
   $stationName = $_GET["station"];
   $stationId = $_GET["stationId"];

   if (isset($_GET["title"])) {
      $sql = "UPDATE rk_stations SET name = ? WHERE ID = ?";
   } elseif (isset($_GET["info"])) {
      $sql = "UPDATE rk_stations SET information = ? WHERE ID = ?";
   } elseif (isset($_GET["code"])) {
      $sql = "UPDATE rk_stations SET code = ? WHERE ID = ?";
   } elseif (isset($_GET["btn"])) {
      $sql = "UPDATE rk_stations SET {$_GET["btn"]} = ? WHERE ID = ?";
   }

   $stmt = $conn->prepare($sql);
   $stmt->bind_param("si", $stationName, $stationId);

   $stmt->execute();
   $stmt->close();
}

// Gruppe
$sqlGroups = "SELECT id, name FROM rk_groups WHERE active = 1";
$groupsResult = $conn->query($sqlGroups);
$groups = [];
if ($groupsResult->num_rows > 0) {
   while ($row = $groupsResult->fetch_assoc()) {
      $groups[] = $row;
   }
}

// Fragen
$sqlQuestions = "SELECT s.id, s.name, s.code, g.name as gname, s.information, s.btn1, s.btn2, s.btn3 FROM rk_stations s, rk_groups g WHERE s.group_id = g.id and g.id = ? ORDER by s.name";
$stmt = $conn->prepare($sqlQuestions);
$stmt->bind_param("i", $_GET["group"]);
$stmt->execute();

$result = $stmt->get_result();

$questions;
if ($result->num_rows > 0) {
   while ($row = $result->fetch_assoc()) {
      $questions[] = $row;
   }
}
?>


<!DOCTYPE html>
<html lang="de">

   <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Landesjugendbewerb - Admin</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <style>
       .title,
       .info,
       .code,
       .btn-points {
         cursor: context-menu;
       }
     </style>
   </head>

   <body>

     <nav class="navbar navbar-expand-lg bg-light">
       <div class="container-fluid">
         <a class="navbar-brand" href="#">Administration</a>
         <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
         </button>
         <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li class="nav-item">
                <a class="nav-link" href="admin_team.php">Team</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="admin_question.php">Fragen</a>
              </li>
              <li class="nav-item">
                <a class="nav-link active" href="admin_station.php">Station</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="admin_result.php">Gewichtung</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="admin_sortable.php">Sortierung</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="../">Bewerb starten</a>
              </li>
            </ul>
         </div>
       </div>
     </nav>

     <div class="container">

       <h2 class="text-danger">Anlegen</h2>

       <div class="card mt-2">
         <div class="card-body">
            <form method="post" action="admin_station.php">
              <div class="mb-3">
                <label for="group" class="form-label">Gruppe:</label>
                <select name="group" id="group" class="form-select">
                  <?php
   foreach ($groups as $group) {
      echo "<option value='{$group["id"]}'>{$group["name"]}</option>";
   }
   ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="station" class="form-label">Station:</label>
                <input type="text" class="form-control" id="station" name="station" required>
              </div>
              <div class="input-group mb-3">
                <span class="input-group-text">Punkte</span>
                <input type="number" name="btn1" min="-999" max="+999" class="form-control" placeholder="volle Punkte" aria-label="volle Punkte">
                <input type="number" name="btn2" min="-999" max="+999" class="form-control" placeholder="halbe Punkte" aria-label="halbe Punkte">
                <input type="number" name="btn3" min="-999" max="+999" class="form-control" placeholder="keine Punkte" aria-label="keine Punkte">
              </div>
              <div class="mb-3">
                <label for="information" class="form-label">Information:</label>
                <textarea class="form-control" id="information" name="information"></textarea>
              </div>
              <div class="mb-3">
                <label for="code" class="form-label">Zugangscode:</label>
                <input type="number" class="form-control" id="code" min="0000" max="9999" name="code" required>
              </div>
              <input type="submit" class="btn btn-danger mb-3" value="Hinzufügen">
            </form>
         </div>
       </div>

       <h2 class="text-danger mt-2">Übersicht</h2>
       <!-- Station anzeigen -->
       <div class="card mt-2">
         <div class="card-body">
            <form method="get" action="admin_station.php">
              <div class="mb-3">
                <select name="group" id="sortable" class="form-select">
                  <?php
   foreach ($groups as $group) {
      echo "<option value='{$group["id"]}' ".(($group["id"] == $_GET["group"]) ? "selected" : null).">{$group["name"]}</option>";
   }
   ?>
                </select>
              </div>
              <input type="submit" class="btn btn-danger mb-3" value="Öffnen">
            </form>
         </div>
       </div>

       <?php

      if (isset($_GET["group"])) {
         $g = "";
         foreach ($questions as $ask) {
            if ($ask["gname"] != $g) {
               $g = $ask["gname"];
               echo "<h4 class='text-danger mt-3'>{$g}</h4>";
            }

            echo "<ul class='list-group mb-2 px-2'>";
            echo "<li class='list-group-item active'><span class='title' data-id='" . $ask["id"] . "'>" . ($ask["name"]) . "</span> Zugangscode: <span class='code' data-id='" . $ask["id"] . "'>" . ($ask["code"]) . "</span> <a class='btn btn-sm btn-danger float-end' href='admin_station.php?delStation=". $ask["id"] ."&group=".$_GET["group"]."'>X</a></li>";

            echo  "<li class='list-group-item'><span class='info' data-id='" . $ask["id"] . "'>".(($ask["information"] == "") ? "Keine Information" : $ask["information"])."</span></li>";
            echo "<li class='list-group-item'><a href='admin_station_detail.php?id=" . $ask["id"] . "'>Bearbeiten</a>";
            echo "<div class='btn-group float-end'>";
            echo "<button class='btn btn-success btn-sm btn-points' data-btn='btn1' data-id='" . $ask["id"] . "'>" . ($ask["btn1"]) . "</button>";
            echo "<button class='btn btn-warning btn-sm btn-points' data-btn='btn2' data-id='" . $ask["id"] . "'>" . ($ask["btn2"]) . "</button>";
            echo "<button class='btn btn-danger btn-sm btn-points' data-btn='btn3' data-id='" . $ask["id"] . "'>" . ($ask["btn3"]) . "</button>";
            echo "</div>";
            echo "</li>";

            echo "</ul>";
         }
      } ?>
   </body>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
   <script>
     $(document).ready(function() {
       let searchParams = new URLSearchParams(window.location.search)
       const groupId = searchParams.get("group");

       $('.title').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const value = $(this).text();
         changeTitle(id, groupId, value, 'Station');
       });

       $('.info').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const value = $(this).text();
         changeInfo(id, groupId, ((value == 'Keine Information') ? '' : value), 'Information');
       });

       $('.code').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const value = $(this).text();
         changeCode(id, groupId, value, 'Zugangscode');
       });

       $('.btn-points').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const btn = $(this).data('btn');
         const value = $(this).text();
         changePoints(id, groupId, btn, value, 'Punkte');
       });
     });

     function changeTitle(id, groupId, text, type) {
       let station = prompt(type, text);
       if(station == null || station == "") {

       } else {
         window.location.href = 'admin_station.php?editStation=true&group=' + groupId + '&station=' + station + '&stationId=' + id + '&title=true';
       }
     }

     function changeInfo(id, groupId, text, type) {
       let station = prompt(type, text);
       if(station == null) {

       } else {
         window.location.href = 'admin_station.php?editStation=true&group=' + groupId + '&station=' + station + '&stationId=' + id + '&info=true';
       }
     }

     function changeCode(id, groupId, text, type) {
       let station = prompt(type, text);
       if(station == null || station == "") {

       } else {
         window.location.href = 'admin_station.php?editStation=true&group=' + groupId + '&station=' + station + '&stationId=' + id + '&code=true';
       }
     }

     function changePoints(id, groupId, btn, text, type) {
       let station = prompt(type, text);
       if(station == null) {

       } else {
         window.location.href = 'admin_station.php?editStation=true&group=' + groupId + '&station=' + station + '&stationId=' + id + '&btn=' + btn;
       }
     }
   </script>


</html>