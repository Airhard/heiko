<?php

include 'db.php'; // Stellt die Verbindung zur Datenbank her

$teamId = $_POST['teamId'];
$userId = $_POST['userId'];
$quizId = $_POST['quizId'];
$score = $_POST['score'];

// SQL-Abfrage zum Einfügen der Punkte in die Datenbank
$sql = "INSERT INTO rk_scores (team_id, quiz_id, score, user_id) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisi", $teamId, $quizId, $score, $userId);
if ($stmt->execute()) {
   $id = $conn->insert_id;

   if (isset($_POST["quizLog"])) {
      foreach ($_POST['quizLog'] as $log) {
         $antwort = "<b>".$log["question"]."</b><br>";
         foreach ($log["selectedAnswers"] as $answer) {
            $antwort .= "- ".$answer . "<br>";
         }

         $sql = "INSERT INTO rk_scores_log (score_id, log, points, quiz) VALUES (?, ?, ?, 1)";
         $stmt = $conn->prepare($sql);

         $stmt->bind_param("isi", $id, $antwort, $log["points"]);
         $stmt->execute();
      }
   }

   if (isset($_POST["log"])) {
      foreach ($_POST['log'] as $log) {
         $sql = "INSERT INTO rk_scores_log (score_id, log, points, quiz) VALUES (?, ?, ?, 0)";
         $stmt = $conn->prepare($sql);
         $stmt->bind_param("isi", $id, $log["text"], $log["points"]);
         $stmt->execute();
      }
   }

   echo "Punkte erfolgreich gespeichert.";
} else {
   echo "Fehler beim Speichern der Punkte: " . $stmt->error;
}

$stmt->close();
$conn->close();
