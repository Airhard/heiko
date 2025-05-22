<?php
include "../db.php"; // Verbindungsdatei einbinden

// Gewicht hinzufügen
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   foreach ($_POST as $key => $value) {
      $key = explode("_", $key);

      if ($key[0] == "max") {
         $column = "max_score";
      } else {
         $column = "weight";
      }
      $id = $key[1];


      $sqlUser = "UPDATE rk_scores SET {$column} = ? WHERE quiz_id = ?";
      $stmtUser = $conn->prepare($sqlUser);
      $stmtUser->bind_param("ii", $value, $id);
      $stmtUser->execute();
      $stmtUser->close();
   }

   echo "<meta http-equiv=\"refresh\" content=\"0; URL=../result.php\">";
}

// Weight für Dropdown
$sqlWeight = "SELECT quiz_id, weight, max_score FROM rk_scores group by quiz_id order by quiz_id";
$weightResult = $conn->query($sqlWeight);
$weights = [];
if ($weightResult->num_rows > 0) {
   while ($row = $weightResult->fetch_assoc()) {
      $weights[] = $row;
   }
}


function group($id)
{
   global $conn;

   if ($id <= 7) {
      $sql = "SELECT CONCAT(name,' Quiz') as name FROM rk_groups WHERE id = ?";
   } else {
      $sql = "SELECT CONCAT(g.name,' ', s.name) as name FROM rk_stations s, rk_groups g WHERE s.group_id = g.id and s.id = ?";
   }

   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $id);
   $stmt->execute();

   $result = $stmt->get_result();

   $group;
   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $group = $row["name"];
      }
   }

   return $group;
}
?>

<!DOCTYPE html>
<html lang="de">

   <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Landesjugendbewerb - Admin</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <a class="nav-link" href="admin_station.php">Station</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="admin_result.php">Gewichtung</a>
              </li>
              <li class="nav-item">
                <a class="nav-link active" href="admin_sortable.php">Sortierung</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="../">Bewerb starten</a>
              </li>
            </ul>
         </div>
       </div>
     </nav>

     <div class="container">
       <!-- Gewichtung anzeigen -->
       <h2 class="text-danger mt-2">Gewichtung der Fragen in % festlegen</h2>

       <form method="post" action="admin_result.php">
         <?php foreach ($weights as $weight) {
   echo '<h5 class="text-danger">'.group($weight["quiz_id"]).'</h5>';
   echo '<div class="input-group mb-3">
   <span class="input-group-text">Maximal Punkte</span>
   <input type="number" id="'.$weight["quiz_id"].'" name="max_'.$weight["quiz_id"].'" class="form-control" value="'.$weight["max_score"].'" required aria-label="Maximal Punkte">
   <span class="input-group-text">Gewichtung in %</span>
   <input type="number" id="'.$weight["quiz_id"].'" name="weight_'.$weight["quiz_id"].'" class="form-control" value="'.$weight["weight"].'" required aria-label="Gewichtung in %">
   </div>';
} ?>
         <input type="submit" class="btn btn-danger mb-3" value="Speichern">
       </form>


     </div>
   </body>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>


</html>