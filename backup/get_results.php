<?php

include '../db.php'; // Verbindungsdaten einbinden


function get_log($id)
{
   global $conn;

   $sql = "SELECT id, score_id, log, points, quiz FROM rk_scores_log WHERE score_id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $id);
   $stmt->execute();
   $result = $stmt->get_result();

   $logs = [];
   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $logs[] = [
   'id' => $row['id'],
   'score_id' => $row['score_id'],
   'name' => $row['log'],
   'quiz' => $row['quiz'],
   'points' => $row['points']
   ];
      }
   }

   return $logs;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
   $sql = "SELECT gname, id, name, sum(total_score) as total_score, sum(total_score_full) as total_score_full FROM (SELECT gs.name as gname, t.id, t.name, round(CASE WHEN qs.quiz_id <= 7 THEN AVG(qs.score/qs.max_score*qs.weight)  ELSE SUM(qs.score/qs.max_score*qs.weight)END ) AS total_score, round(CASE WHEN qs.quiz_id <= 7 THEN AVG(score)  ELSE SUM(qs.score)END ) AS total_score_full
   FROM rk_teams t
   JOIN rk_scores qs ON t.id = qs.team_id
   JOIN rk_groups gs ON t.GROUP_ID = gs.id
   GROUP BY t.id, qs.user_id, qs.quiz_id
   ORDER BY gs.name, round(qs.score) DESC) result
   GROUP BY id
   ORDER BY gname, total_score";
   $result = $conn->query($sql);

   $teams = [];
   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $teams[] = [
   'id' => $row['id'],
   'name' => $row['name'],
   'total_score' => $row['total_score'],
   'total_score_full' => $row['total_score_full'],
   'group_name' => $row['gname']
   ];
      }
   }
} else {
   $sql = "SELECT qs.user_id as passage, qs.id as rowid, DATE_FORMAT(qs.date, '%d/%m/%Y %H:%i:%s') as dateentry, qs.quiz_id, CASE WHEN qs.quiz_id <= 7 THEN (SELECT CONCAT(name,' Quiz') as name FROM rk_groups WHERE id = qs.quiz_id) ELSE (SELECT CONCAT(g.name,' ', s.name) as name FROM rk_stations s, rk_groups g WHERE s.group_id = g.id and s.id = qs.quiz_id) END AS info, t.id, t.name,round(CASE WHEN qs.quiz_id <= 7 THEN AVG(score/qs.max_score*qs.weight)  ELSE SUM(qs.score/qs.max_score*qs.weight) END) AS total_score, round(CASE WHEN qs.quiz_id <= 7 THEN AVG(score)  ELSE SUM(qs.score) END) AS total_score_full
   FROM rk_teams t
   JOIN rk_scores qs ON t.id = qs.team_id WHERE qs.team_id = ?
   GROUP BY t.id, qs.user_id, qs.quiz_id order by info";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $id);
   $stmt->execute();
   $result = $stmt->get_result();

   $teams = [];
   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $teams[] = [
   'id' => 0,
   'name' => $row['info'],
   'total_score' => $row['total_score'],
   'total_score_full' => $row['total_score_full'],
   'rowid' => $row['rowid'],
   'date' => $row['dateentry'],
   'passage' => $row['passage'],
   'logs' => get_log($row['rowid'])
   ];
      }
   }
}



echo json_encode($teams);
$conn->close();
