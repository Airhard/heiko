<?php

include 'db.php'; // Verbindungsdaten einbinden


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
   $sql = "SELECT tid, gname, tname, sum(score) as score FROM (
   SELECT t.id as tid, g.name as gname, t.name as tname, round(avg(s.score/s.max_score*s.weight)) as score FROM rk_scores s, rk_teams t, rk_groups g where s.quiz_id <= 7 and s.team_id = t.id and t.group_id = g.id GROUP BY s.team_id
   UNION
   SELECT t.id as tid, g.name as gname, t.name as tname, round(sum(score/max_score*weight)) as score FROM rk_scores s, rk_teams t, rk_groups g where s.quiz_id > 7 and s.team_id = t.id and t.group_id = g.id GROUP BY s.team_id, s.user_id, s.quiz_id) total
   GROUP BY tid
   ORDER BY gname, score DESC";
   $result = $conn->query($sql);

   $teams = [];
   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $teams[] = [
   'tid' => $row['tid'],
   'tname' => $row['tname'],
   'score' => $row['score'],
   'gname' => $row['gname']
   ];
      }
   }
} else {
   $sql = "SELECT game, id, name, passage, rowid, dateentry, quiz_id, info, score FROM (
   SELECT CONCAT('quiz') as game, t.id, t.name, s.user_id as passage, s.id as rowid, DATE_FORMAT(s.date, '%d/%m/%Y %H:%i:%s') as dateentry, s.quiz_id, (SELECT CONCAT(name,' Quiz') as name FROM rk_groups WHERE id = s.quiz_id) as info, round(avg(s.score/s.max_score*s.weight)) as score FROM rk_scores s, rk_teams t, rk_groups g where s.quiz_id <= 7 and s.team_id = t.id and t.group_id = g.id and s.team_id = ? GROUP BY s.team_id, s.user_id
   UNION
   SELECT CONCAT('station') as game, t.id, t.name, s.user_id as passage, s.id as rowid, DATE_FORMAT(s.date, '%d/%m/%Y %H:%i:%s') as dateentry, s.quiz_id, (SELECT CONCAT(g1.name,' ', s1.name) as name FROM rk_stations s1, rk_groups g1 WHERE s1.group_id = g1.id and s1.id = s.quiz_id) as info, round(sum(score/max_score*weight)) as score FROM rk_scores s, rk_teams t, rk_groups g where s.quiz_id > 7 and s.team_id = t.id and t.group_id = g.id and s.team_id = ? GROUP BY s.team_id, s.user_id, s.quiz_id) total
   ORDER BY info";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("ii", $id, $id);
   $stmt->execute();
   $result = $stmt->get_result();

   $teams = [];
   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $teams[] = [
   'tid' => 0,
   'game' => $row['game'],
   'name' => $row['info'],
   'total_score' => $row['score'],
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
