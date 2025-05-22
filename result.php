<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

if (isset($_GET["delrow"]) && isset($_GET['qid'])) {
   //Del-Row
   $sql = "DELETE FROM rk_scores WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $_GET['qid']);
   $stmt->execute();

   //Del-Log
   $sql = "DELETE FROM rk_scores_log WHERE score_id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $_GET['qid']);
   $stmt->execute();
}

if (isset($_GET["dellog"]) && isset($_GET['qid'])&& isset($_GET['lid'])) {
   //Del-Log
   $sql = "DELETE FROM rk_scores_log WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $_GET['lid']);
   $stmt->execute();

   //Update-Row
   if (intval($_GET['points']) < 0) {
      $sql = "UPDATE rk_scores SET score = score + ? WHERE id = ?";
   } else {
      $sql = "UPDATE rk_scores SET score = score - ? WHERE id = ?";
   }

   $number = intval(abs($_GET['points']));


   $stmt = $conn->prepare($sql);
   $stmt->bind_param("ii", $number, $_GET['qid']);
   $stmt->execute();
   echo "<meta http-equiv='refresh' content='0; URL=result.php?id={$_GET['id']}&name={$_GET['name']}&lid={$_GET['lid']}'>";
   exit;
}

$sql = "SELECT * FROM rk_scores WHERE weight = 0 or max_score = 0";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
   header("Location: admin/admin_result.php");
   exit;
}

$conn->close();

if (isset($_GET["name"])) {
   $title = "Team: ".$_GET["name"];
   $table[1] = "Details";
   $table[2] = "Punkte";
   $table[3] = "Durchgang";
   $table[4] = "Datum";
   $table[5] = "Löschen";
   $id = $_GET["id"];
} else {
   $title = "Ergebnisliste der Teams";
   $table[1] = "Teamname";
   $table[2] = "Gesamtpunkte";
   $table[3] = "Platzierung";
   $table[4] = "";
   $table[5] = "";
   $id = null;
}
?>

<!DOCTYPE html>
<html lang="de">

   <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Ergebnisliste Landesjugendbewerb</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   </head>

   <body>
     <div class="container mt-5">
       <h2><?=$title;?>
         <button type="button" class="btn btn-primary" id="btnExport" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
            style="padding: 9px; float: right">Export
         </button>
       </h2>
       <table class="table">
         <thead>
            <tr>
              <th><?=$table[1]; ?></th>
              <th><?=$table[2]; ?></th>
              <th><?=$table[3]; ?></th>
              <th><?=$table[4]; ?></th>
              <th data-exclude='true'><?=$table[5]; ?></th>
            </tr>
         </thead>
         <tbody id="resultsBody">
            <!-- Ergebnisse werden hier eingefügt -->
         </tbody>
       </table>
     </div>
     <div class="container mt-2">
       <?php echo((isset($_GET["name"])) ? "<p><a href='result.php'>zurück zur Übersicht</a></p>" : null);?>
     </div>

     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
     <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>

     <script>
       $(document).ready(function() {
         const searchParams = new URLSearchParams(window.location.search);
         loadResults(searchParams.get("id"));

         $("#btnExport").click(function() {
            let table = document.getElementsByTagName("table");
            var teamname = "<?=$title; ?>";

            TableToExcel.convert(table[0], {
              name: teamname + ".xlsx"
              , sheet: {
                name: 'Landesjugendbewerb'
              }
            });
         });
       });

       function loadResults(id) {

         const searchParams = new URLSearchParams(window.location.search);
         var headline;
         var sum = 0;
         var sum_full = 0;
         $.ajax({
            url: 'get_team_results.php?id=' + id, // Pfad zu deinem PHP-Skript, das die Ergebnisse lädt
            type: 'GET'
            , dataType: 'json'
            , success: function(data) {
              const resultsBody = $('#resultsBody');
              resultsBody.empty();
              data.forEach(function(result) {

                if(result.tid == 0) {
                  sum += Number(result.score);

                  resultsBody.append(`
 <tr class="table-${result.game == 'quiz' ? 'info' : 'secondary'}">
 <td><a href="#" data-logid="${result.rowid}" data-bs-toggle="collapse" data-bs-target="#collapse${result.rowid}" aria-expanded="false">${result.name}</a></td>
 <td>${result.total_score}</td>
 <td>${result.passage}</td>
 <td>${result.date}</td>
 <td data-exclude='true'><a href='result.php?id=${searchParams.get("id")}&name=${searchParams.get("name")}&qid=${result.rowid}&delrow=true'>Eintrag löschen</a></td>
 </tr>  `);
                  result.logs.forEach(function(log) {


                     resultsBody.append(`
   <tr class="table-warning collapse" id="collapse${result.rowid}">
   <td></td>
   <td>${((log.quiz == 1) ? '' : parseFloat(log.points).toFixed(0))}</td>
   <td colspan='2'>${log.name}</td>
   <td data-exclude='true'>${((log.quiz == 1) ? '' : `<a href='result.php?id=${searchParams.get("id")}&name=${searchParams.get("name")}&qid=${result.rowid}&lid=${log.id}&points=${log.points}&dellog=true'>Eintrag löschen</a>`)}</td>
   </tr> `);

                  });


                } else {
                  if(headline != result.gname) {
                     headline = result.gname;
                     resultsBody.append(`
   <tr>
   <td colspan='5'>${result.gname}</td>
   </tr>
   `);
                  }
                  resultsBody.append(`
   <tr>
   <td><a href='result.php?id=${result.tid}&name=${result.tname}'>${result.tname}</a></td>
   <td colspan='4'>${result.score}</td>
   </tr>
   `);
                }

              });

              /*
   if(sum != 0) {
   resultsBody.append(`
   <tr>
   <td></td>
   <td colspan='4'>${parseFloat(sum).toFixed(0)}</td>
   </tr>
   `);
   }
   */

            }
            , error: function() {
              alert('Fehler beim Laden der Ergebnisse.');
            }
         });
       }
     </script>


   </body>

</html>