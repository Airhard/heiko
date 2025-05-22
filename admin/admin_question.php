<?php
include '../db.php';

// Mitglied löschen
if (isset($_GET["delQuestion"]) && !empty($_GET["delQuestion"])) {
   $questionid = $_GET["delQuestion"];
   $sql = "DELETE FROM rk_questions WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $questionid);
   $stmt->execute();
   $stmt->close();
}

// Logik zum Hinzufügen von Fragen
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   // Daten aus dem Formular holen und in Variablen speichern
   $points = $_POST['points'];
   $group = $_POST['group'];
   $question = $_POST['question'];
   $option_a = $_POST['option_a'];
   $option_b = $_POST['option_b'];
   $option_c = $_POST['option_c'];
   $option_d = $_POST['option_d'];
   $correct_option = strtoupper($_POST['correct_option']);

   // SQL zum Einfügen der Frage
   $sql = "INSERT INTO rk_questions (question, group_id, points, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

   $stmt = $conn->prepare($sql);
   $stmt->bind_param("siisssss", $question, $group, $points, $option_a, $option_b, $option_c, $option_d, $correct_option);
   $stmt->execute();
   $stmt->close();
}

// Frage bearbeiten
if (isset($_GET["editQuestion"]) && !empty($_GET["question"])) {
   $questionName = $_GET["question"];
   $questionId = $_GET["questionId"];

   if (isset($_GET["sub"])) {
      $sub = $_GET["sub"];
      $sql = "UPDATE rk_questions SET $sub = ? WHERE ID = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si", $questionName, $questionId);
   } elseif (isset($_GET["answer"])) {
      $correct_option = strtoupper($questionName);
      $sql = "UPDATE rk_questions SET correct_option = ? WHERE ID = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si", $correct_option, $questionId);
   } elseif (isset($_GET["point"])) {
      $points = $questionName;
      $sql = "UPDATE rk_questions SET points = ? WHERE ID = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si", $points, $questionId);
   } else {
      $sql = "UPDATE rk_questions SET question = ? WHERE ID = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si", $questionName, $questionId);
   }

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
$sqlQuestions = "SELECT q.id, q.question, g.name as gname, q.group_id, q.points, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option FROM rk_questions q, rk_groups g WHERE q.group_id = g.id and g.id = ? ORDER by g.id";
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
       .active,
       .sub,
       .point,
       .answer {
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
                <a class="nav-link active" href="admin_question.php">Fragen</a>
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

       <div class="card">
         <div class="card-body">

            <form method="post" action="admin_question.php">

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
                <label for="teamName" class="form-label">Frage:</label>
                <input type="text" id="teamName" class="form-control" name="question">
              </div>
              <div class="mb-3">
                <label for="points" class="form-label">Punkte pro richtige Antwort:</label>
                <input type="number" id="points" class="form-control" min="1" max="10" name="points">
              </div>
              <div class="mb-3">
                <label for="option_a" class="form-label">Option A:</label>
                <input type="text" id="option_a" class="form-control" name="option_a">
              </div>
              <div class="mb-3">
                <label for="option_b" class="form-label">Option B:</label>
                <input type="text" id="option_b" class="form-control" name="option_b">
              </div>
              <div class="mb-3">
                <label for="option_c" class="form-label">Option C:</label>
                <input type="text" id="option_c" class="form-control" name="option_c">
              </div>
              <div class="mb-3">
                <label for="option_d" class="form-label">Option D:</label>
                <input type="text" id="option_d" class="form-control" name="option_d">
              </div>
              <div class="mb-3">
                <label for="correct_option" class="form-label">Korrekte Option (A,B,C,D):</label>
                <input type="text" id="correct_option " class="form-control" name="correct_option">
              </div>
              <input type="submit" class="btn btn-danger mb-3" value="Hinzufügen">

            </form>
         </div>
       </div>

       <h2 class="text-danger mt-2">Übersicht</h2>
       <!-- Fragen anzeigen -->
       <div class="card mt-2">
         <div class="card-body">
            <form method="get" action="admin_question.php">
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
            echo "<li class='list-group-item active'><span data-id='" .
   $ask["id"] .
   "'>" . ($ask["question"]) . '</span> <a class="btn btn-sm btn-danger float-end" href="admin_question.php?delQuestion=' . $ask["id"] . '&group='.$_GET["group"].'">X</a></li>';

            echo "<li class='list-group-item sub'>A: <span data-question='option_a' data-id='".$ask["id"]."'>" . htmlspecialchars($ask["option_a"]) . '</span></li>';
            echo "<li class='list-group-item sub'>B: <span data-question='option_b' data-id='".$ask["id"]."'>" . htmlspecialchars($ask["option_b"]) . '</span></li>';
            echo "<li class='list-group-item sub'>C: <span data-question='option_c' data-id='".$ask["id"]."'>" . htmlspecialchars($ask["option_c"]) . '</span></li>';
            echo "<li class='list-group-item sub'>D: <span data-question='option_d' data-id='".$ask["id"]."'>" . htmlspecialchars($ask["option_d"]) . '</span></li>';
            echo "<li class='list-group-item list-group-item-dark'><span class='answer' data-id='".$ask["id"]."'>" . htmlspecialchars($ask["correct_option"]) . " </span><span class='float-end'>Gruppe: ". ($ask["gname"]) . " Punkte: <span class='point' data-id='".$ask["id"]."' >" . ($ask["points"]) . "</span></span></li>";

            echo "</ul>";
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
         changeName(id, groupId, value, 'Frage');
       });

       $('.sub > span').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const question = $(this).data('question');
         const value = $(this).text();
         changeSubName(id, groupId, question, value, 'Antwort');
       });

       $('.answer').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const value = $(this).text();
         changeAnswer(id, groupId, value, 'Option');
       });

       $('.point').click(function(event) {
         event.preventDefault();
         const id = $(this).data('id');
         const value = $(this).text();
         changePoint(id, groupId, value, 'Punkte');
       });
     });

     function changeName(id, groupId, text, type) {
       let question = prompt(type, text);
       if(question == null || question == "") {

       } else {
         window.location.href = 'admin_question.php?editQuestion=true&group=' + groupId + '&question=' + question + '&questionId=' + id;
       }
     }

     function changeSubName(id, groupId, sub, text, type) {
       let question = prompt(type, text);
       if(question == null || question == "") {

       } else {
         window.location.href = 'admin_question.php?editQuestion=true&group=' + groupId + '&question=' + question + '&questionId=' + id + '&sub=' + sub;
       }
     }

     function changeAnswer(id, groupId, text, type) {
       let question = prompt(type, text);
       if(question == null || question == "") {

       } else {
         window.location.href = 'admin_question.php?editQuestion=true&group=' + groupId + '&question=' + question + '&questionId=' + id + '&answer=true';
       }
     }

     function changePoint(id, groupId, text, type) {
       let question = prompt(type, text);
       if(question == null || question == "") {

       } else {
         window.location.href = 'admin_question.php?editQuestion=true&group=' + groupId + '&question=' + question + '&questionId=' + id + '&point=true';
       }
     }
   </script>


</html>