<?php
include "../db.php"; // Verbindungsdatei einbinden

// Mitglied löschen
if (isset($_GET["delUser"]) && !empty($_GET["delUser"])) {
   $userid = $_GET["delUser"];
   $sql = "DELETE FROM rk_team_users WHERE user_id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $userid);
   $stmt->execute();
   $stmt->close();

   $sql = "DELETE FROM rk_users WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $userid);
   $stmt->execute();
   $stmt->close();
}

// Team löschen
if (isset($_GET["delTeam"]) && !empty($_GET["delTeam"])) {
   $teamid = $_GET["delTeam"];

   $sql = "DELETE FROM rk_scores WHERE team_id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $teamid);
   $stmt->execute();
   $stmt->close();

   $sql = "SELECT user_id FROM rk_team_users where team_id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $teamid);
   $stmt->execute();

   $result = $stmt->get_result();

   $members = [];
   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $members[] = $row["user_id"];
      }
   }

   $sql = "DELETE FROM rk_team_users WHERE team_id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $teamid);
   $stmt->execute();
   $stmt->close();

   $sql = "DELETE FROM rk_teams WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $teamid);
   $stmt->execute();
   $stmt->close();

   foreach ($members as $member) {
      $sql = "DELETE FROM rk_users WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $member);
      $stmt->execute();
      $stmt->close();
   }
}

// Team hinzufügen
if (isset($_POST["addTeam"]) && !empty($_POST["teamName"])) {
   $teamName = $_POST["teamName"];
   $groupId = $_POST["groupId"];
   $sql = "INSERT INTO rk_teams (name, group_id) VALUES (?, ?)";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("si", $teamName, $groupId);
   $stmt->execute();
   $stmt->close();
}

// Team bearbeiten
if (isset($_GET["editTeam"]) && !empty($_GET["teamName"])) {
   $teamName = $_GET["teamName"];
   $teamId = $_GET["teamId"];
   $sql = "UPDATE rk_teams SET name = ? WHERE ID = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("si", $teamName, $teamId);
   $stmt->execute();
   $stmt->close();
}

// Mitglied hinzufügen
if (isset($_POST["addUser"]) && !empty($_POST["personalName"]) && !empty($_POST["teamId"])) {
   $personalname = $_POST["personalName"];
   $teamId = $_POST["teamId"];
   // Nutzer hinzufügen
   $sqlUser = "INSERT INTO rk_users (username) VALUES (?)";
   $stmtUser = $conn->prepare($sqlUser);
   $stmtUser->bind_param("s", $personalname);
   $stmtUser->execute();
   $userId = $stmtUser->insert_id;
   $stmtUser->close();
   // Nutzer zum Team hinzufügen
   $sqlTeamUser = "INSERT INTO rk_team_users (team_id, user_id) VALUES (?, ?)";
   $stmtTeamUser = $conn->prepare($sqlTeamUser);
   $stmtTeamUser->bind_param("ii", $teamId, $userId);
   $stmtTeamUser->execute();
   $stmtTeamUser->close();
}

// Teams für Dropdown
$sql = "SELECT id, name, group_id FROM rk_teams WHERE group_id = ? ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_GET["group"]);
$stmt->execute();

$result = $stmt->get_result();

$teams = [];
if ($result->num_rows > 0) {
   while ($row = $result->fetch_assoc()) {
      $teams[$row["group_id"]][] = $row;
   }
}

// Group
$sqlGroups = "SELECT id, name FROM rk_groups WHERE active = 1";
$groupsResult = $conn->query($sqlGroups);
$groups = [];
if ($groupsResult->num_rows > 0) {
   while ($row = $groupsResult->fetch_assoc()) {
      $groups[] = $row;
   }
}

// Mitglieder / Teams
function member($id)
{
   global $conn;
   $sql = "SELECT u.username, u.id FROM rk_team_users tu, rk_users u WHERE tu.team_id = ? and tu.user_id = u.id";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $id);
   $stmt->execute();

   $result = $stmt->get_result();

   $members = [];
   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $members[] = $row;
      }
   }

   return $members;
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
       .active {
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
                <a class="nav-link active" href="admin_team.php">Team</a>
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

       <!-- Team hinzufügen -->
       <div class="card">
         <div class="card-body">
            <form method="post" action="admin_team.php">
              <input type="hidden" name="addTeam">
              <div class="mb-3">
                <label for="teamName" class="form-label">Teamname:</label>
                <input type="text" class="form-control" id="teamName" name="teamName" required>
              </div>
              <div class="mb-3">
                <div class="btn-group" role="group" aria-label="Basic checkbox toggle button group">
                  <?php
   foreach ($groups as $key => $value) {
      echo "
   <input type='radio' class='btn-check' name='groupId' id='group{$value["id"]}' value='{$value["id"]}' autocomplete='off'>
   <label class='btn btn-outline-danger' for='group{$value["id"]}'>{$value["name"]}</label>
   ";
   }
   ?> </div>
              </div>
              <input type="submit" class="btn btn-danger mb-3" value="Hinzufügen">
            </form>
         </div>
       </div>

       <!-- Mitglied hinzufügen

   <div class="card mt-2">
   <div class="card-body">
   <form method="post" action="admin_team.php">
   <input type="hidden" name="addUser">
   <div class="mb-3">
   <label for="teamId" class="form-label">Team:</label>
   <select id="teamId" class="form-select" name="teamId" required>
   <option value="">Bitte wählen</option>
   <?php foreach ($teams as $team) { ?>
   <option value="<?php echo $team["id"]; ?>"><?php echo htmlspecialchars($team["name"]); ?></option>
   <?php } ?>
   </select>
   </div>
   <div class="mb-3">
   <label for="personalName" class="form-label">Mitgliedsname:</label>
   <input type="text" class="form-control" id="personalName" name="personalName" required>
   </div>
   <input type="submit" class="btn btn-danger mb-3" value="Hinzufügen">
   </form>
   </div>
   </div>
   -->



       <h2 class="text-danger mt-2">Übersicht</h2>
       <!-- Team anzeigen -->
       <div class="card mt-2">
         <div class="card-body">
            <form method="get" action="admin_team.php">
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
         foreach ($groups as $key => $value) {
            if (count($teams[$value["id"]]) == 0) {
               continue;
            }

            echo "<h4 class='text-danger mt-2'>{$value["name"]}</h4>";

            foreach ($teams[$value["id"]] as $team) {
               echo "<ul class='list-group mb-2'>";
               echo "<li class='list-group-item active'><span data-id='" .
   $team["id"] .
   "'>" .
   htmlspecialchars($team["name"]) .
   '</span> <a class="btn btn-sm btn-danger float-end" href="admin_team.php?delTeam=' .
   $team["id"] .
   '&group='.$_GET["group"].'">X</a></li>';

               foreach (member($team["id"]) as $user) {
                  echo "<li class='list-group-item'>" .
   htmlspecialchars($user["username"]) .
   ' <a class="btn btn-sm btn-danger float-end"  href="admin_team.php?delUser=' .
   $user["id"] .
   '">X</a></li>';
               }
               echo "</ul>";
            }
         }
      } ?>

     </div>
   </body>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
   <script>
     $(document).ready(function() {
       let searchParams = new URLSearchParams(window.location.search)
       const groupId = searchParams.get("group");

       $('.active > span').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const value = $(this).text();
         changeName(id, groupId, value);
       });
     });

     function changeName(teamId, groupId, name) {
       let teamname = prompt("Teamname", name);
       if(teamname == null || teamname == "") {

       } else {
         window.location.href = 'admin_team.php?editTeam=true&group=' + groupId + '&teamName=' + teamname + '&teamId=' + teamId;
       }
     }
   </script>


</html>