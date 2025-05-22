<?php
include '../db.php';

// Mitglied löschen
if (isset($_GET["delTask"]) && !empty($_GET["delTask"])) {
   $questionid = $_GET["delTask"];
   $sql = "DELETE FROM rk_station_tasks WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $questionid);
   $stmt->execute();
   $stmt->close();
}

// Logik zum Hinzufügen von Fragen
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   // Daten aus dem Formular holen und in Variablen speichern
   $task = $_POST['task'];
   $station_id = $_GET['id'];
   $points = $_POST['points'];
   $btn1 = $_POST['btn1'];
   $btn2 = $_POST['btn2'];
   $btn3 = $_POST['btn3'];

   // SQL zum Einfügen der Frage
   $sql = "INSERT INTO rk_station_tasks (station_id, task, points, btn1, btn2, btn3) VALUES (?, ?, ?, ?, ?, ?)";

   $stmt = $conn->prepare($sql);
   $stmt->bind_param("isiiii", $station_id, $task, $points, $btn1, $btn2, $btn3);
   $stmt->execute();
   $stmt->close();
}

// Station bearbeiten
if (isset($_GET["editStation"])) {
   $stationName = $_GET["station"];
   $stationId = $_GET["stationId"];

   if (isset($_GET["title"])) {
      $sql = "UPDATE rk_station_tasks SET task = ? WHERE ID = ?";
   } elseif (isset($_GET["points"])) {
      $sql = "UPDATE rk_station_tasks SET points = ? WHERE ID = ?";
   } elseif (isset($_GET["btn"])) {
      $sql = "UPDATE rk_station_tasks SET {$_GET["btn"]} = ? WHERE ID = ?";
   }

   $stmt = $conn->prepare($sql);
   $stmt->bind_param("si", $stationName, $stationId);

   $stmt->execute();
   $stmt->close();
}

// Fragen
$sqlQuestions = "SELECT id, station_id, task, points, btn1, btn2, btn3 FROM rk_station_tasks WHERE station_id = ? ORDER BY sortable	";
$stmt = $conn->prepare($sqlQuestions);
$stmt->bind_param("i", $_GET["id"]);
$stmt->execute();
$result = $stmt->get_result();
$tasks = [];
$tasks = $result->fetch_all(MYSQLI_ASSOC);

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
       .points {
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

       <div class="card">
         <div class="card-body">
            <form method="post" action="admin_station_detail.php?id=<?=$_GET["id"];?>">
              <div class="mb-3">
                <label for="task" class="form-label">Aufgabe:</label>
                <input type="text" class="form-control" id="task" name="task" required>
              </div>
              <div class="mb-3">
                <label for="points" class="form-label">Punkte: (mehrfach verwendbar) <br>
                  <small>Wenn Sie die Zahl 998 eingeben, wird diese als ausklappbarer Bereich behandelt.</small><br>
                  <small>Wenn Sie die Zahl 999 eingeben, wird diese als Überschrift behandelt.</small></label>
                <input type="number" class="form-control" min="-999" max="+999" name="points" id="points">
              </div>
              <div class="input-group mb-3">
                <span class="input-group-text">Punkte: (einmalig)</span>
                <input type="number" name="btn1" min="-999" max="+999" class="form-control" placeholder="volle Punkte" aria-label="volle Punkte">
                <input type="number" name="btn2" min="-999" max="+999" class="form-control" placeholder="halbe Punkte" aria-label="halbe Punkte">
                <input type="number" name="btn3" min="-999" max="+999" class="form-control" placeholder="keine Punkte" aria-label="keine Punkte">
              </div>
              <input type="submit" class="btn btn-danger mb-3" value="Hinzufügen">
            </form>
         </div>
       </div>

       <!-- Fragen anzeigen -->
       <h2 class="text-danger mt-2">Übersicht</h2>
       <ul class='list-group mb-2'>
         <?php foreach ($tasks as $ask) {
   echo "<li class='list-group-item ".(($ask["points"] == 999) ? "list-group-item-danger" : (($ask["points"] == 998) ? "list-group-item-warning" : null))."'><span class='fw-bold title' data-id='" . $ask["id"] . "'>" . ($ask["task"]) . "</span>
   <a class='btn btn-sm btn-danger float-end' href='admin_station_detail.php?id=".$_GET["id"]."&delTask=" . $ask["id"] . "'>X</a>";
   if ($ask["points"] != 998 && $ask["points"] != 999) {
      echo "<p>Punkte: <span class='points' data-id='" . $ask["id"] . "'>" . ($ask["points"]) . "</span>";
      echo "<div class='btn-group float-end'>";
      echo "<button class='btn btn-success btn-sm btn-points' data-btn='btn1' data-id='" . $ask["id"] . "'>" . ($ask["btn1"]) . "</button>";
      echo "<button class='btn btn-warning btn-sm btn-points' data-btn='btn2' data-id='" . $ask["id"] . "'>" . ($ask["btn2"]) . "</button>";
      echo "<button class='btn btn-danger btn-sm btn-points' data-btn='btn3' data-id='" . $ask["id"] . "'>" . ($ask["btn3"]) . "</button>";
      echo "</div>";
      echo "</p>";
   }

   echo "</li>";
} ?>
       </ul>
     </div>
   </body>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
   <script>
     $(document).ready(function() {

       let searchParams = new URLSearchParams(window.location.search)

       const stationId = searchParams.get("id");

       $('.title').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const value = $(this).text();
         changeTitle(id, stationId, value, 'Station');
       });

       $('.points').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const value = $(this).text();
         console.log(value)
         changePoints(id, stationId, value, 'Punkte');
       });

       $('.btn-points').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const btn = $(this).data('btn');
         const value = $(this).text();
         changeBtn(id, stationId, btn, value, 'Punkte');
       });
     });

     function changeTitle(id, stationId, text, type) {
       let station = prompt(type, text);
       if(station == null || station == "") {

       } else {
         window.location.href = 'admin_station_detail.php?editStation=true&station=' + station + '&id=' + stationId + '&stationId=' + id + '&title=true';
       }
     }

     function changePoints(id, stationId, text, type) {
       let station = prompt(type, text);
       if(station == null || station == "") {

       } else {
         window.location.href = 'admin_station_detail.php?editStation=true&station=' + station + '&id=' + stationId + '&stationId=' + id + '&points=true';
       }
     }

     function changeBtn(id, stationId, btn, text, type) {
       let station = prompt(type, text);
       if(station == null) {

       } else {
         window.location.href = 'admin_station_detail.php?editStation=true&station=' + station + '&id=' + stationId + '&stationId=' + id + '&btn=' + btn;
       }
     }
   </script>


</html>