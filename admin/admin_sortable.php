<?php
include "../db.php"; // Verbindungsdatei einbinden

// Mitglied löschen
if (isset($_POST["listItem"])) {
   $i = 0;

   foreach ($_POST['listItem'] as $value) {
      if ($_POST["database"] <= 7) {
         $table = "rk_questions";
      } else {
         $table = "rk_station_tasks";
      }

      $sql = "UPDATE $table SET sortable = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ii", $i, $value);
      $stmt->execute();
      $stmt->close();
      $i++;
   }
   exit;
}

// Group
$sqlGroups = "SELECT g.id as qid, g.id, 'Quiz' as name, g.name as gname FROM rk_questions q, rk_groups g WHERE q.group_id = g.id GROUP BY g.id
UNION
SELECT g.id as qid, s.id, s.name, g.name as gname FROM rk_stations s, rk_groups g WHERE s.group_id = g.id
ORDER BY qid";
$groupsResult = $conn->query($sqlGroups);
$groups = [];
if ($groupsResult->num_rows > 0) {
   while ($row = $groupsResult->fetch_assoc()) {
      $groups[] = $row;
   }
}


function sortable($groupid)
{
   global $conn;

   if ($groupid <= 7) {
      $sql = "SELECT id, question as name, points FROM rk_questions WHERE group_id = ? ORDER by sortable";
   } else {
      $sql = "SELECT id, task as name, points FROM rk_station_tasks WHERE station_id = ? ORDER BY sortable";
   }

   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $groupid);
   $stmt->execute();

   $result = $stmt->get_result();

   $sortable;
   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $sortable[] = $row;
      }
   }

   return $sortable;
}



?>

<!DOCTYPE html>
<html lang="de">

   <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Landesjugendbewerb - Admin</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
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

       <h2 class="text-danger">Sortierung auswählen</h2>

       <div class="card">
         <div class="card-body">
            <form method="post" action="admin_sortable.php">
              <div class="mb-3">
                <select name="sortable" id="sortable" class="form-select">
                  <?php
   $gname;
   foreach ($groups as $key => $value) {
      if ($gname != $value["gname"]) {
         $gname = $value["gname"];
         if ($key > 0) {
            echo "</optgroup>";
         }
         echo "<optgroup label='{$value["gname"]}'>";
      }
      echo "<option value='{$value["id"]}' ".(($value["id"] == $_POST["sortable"]) ? "selected" : null).">{$value["name"]}</option>";
   }
   echo "</optgroup>";
   ?>
                </select>
              </div>
              <input type="submit" class="btn btn-danger mb-3" value="Öffnen">
            </form>
         </div>
       </div>

       <?php

      if (isset($_POST["sortable"])) {
         echo '<h2 class="text-danger mt-2 mb-4">Sortierung</h2>';
         echo "<ul class='sortable list-group'>";

         foreach (sortable($_POST["sortable"]) as $index => $value) {
            echo "<li class='list-group-item  ".(($value["points"] == 999) ? "list-group-item-danger" : (($value["points"] == 998) ? "list-group-item-warning" : null))."' id='listItem_{$value["id"]}' style='cursor: grab;'><span class='ui-icon ui-icon-arrow-4'></span> <strong>{$value["name"]}</strong></li>";
         }
         echo "</ul> ";
      }
?>
     </div>
   </body>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

   <script>
     $(document).ready(function() {
       $(".sortable").sortable({
         axis: 'y'
         , update: function(event, ui) {
            let database = "<?php echo $_POST["sortable"];?>";
            var data = $(this).sortable('serialize') + '&database=' + database;

            console.log(data);
            $.ajax({
              data: data
              , type: 'POST'
              , url: 'admin_sortable.php'
            });
         }
       });

     });
   </script>


</html>