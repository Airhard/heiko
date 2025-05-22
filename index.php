<!DOCTYPE html>
<html lang="de">

   <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Landesjugendbewerb</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
   </head>

   <body style="background-color: #f6f6f6;">
     <nav class=" navbar bg-body-tertiary" style="background-color: white !important">
       <div class="container">
         <a class="navbar-brand" href="index.php">
            <img src="logo.jpg" alt="ÖSTERREICHISCHES JUGENDROTKREUZ NIEDERÖSTERREICH" width="120">
         </a>
       </div>
     </nav>

     <div class="container mt-5">
       <h2 class="text-danger">Starte den Bewerb</h2>
       <form id="teamForm">
         <div class="mb-3">
            <label for="teamSelect" class="form-label">Team</label>
            <select class="form-select" id="teamSelect" required>
            </select>
         </div>
         <div class="mb-3">
            <label for="stationSelect" class="form-label">Station</label>
            <select class="form-select" id="stationSelect" required>
            </select>
         </div>
         <button type="submit" class="btn btn-danger">Starten</button>
       </form>
     </div>

     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
     <script>
       $(document).ready(function() {
         loadTeams();

         $('#teamSelect,#stationSelect').select2({
            theme: 'bootstrap-5'
         });

         $('#teamForm').submit(function(event) {
            event.preventDefault();
            const teamId = $('#teamSelect').val();
            const stationId = $('#stationSelect').val();
            const stationName = $('#stationSelect :selected').text();
            const groupName = $('#stationSelect :selected').data('groupname');

            window.location.href = ((stationId <= 7) ? 'quiz' : 'station') + '.php?stationId=' + stationId + '&teamId=' + teamId +
              '&stationName=' + stationName + '&groupName=' + groupName;
         });

         $('#teamSelect').change(function() {
            const groupId = $('#teamSelect :selected').data('groupid');
            loadStations(groupId); // Lädt Aufgabe basierend auf dem gewählten Team
         });
       });

       function loadTeams() {
         $.ajax({
            url: 'get_teams.php'
            , type: 'GET'
            , dataType: 'json'
            , success: function(data) {
              const select = $('#teamSelect');
              select.empty();
              data.forEach(team => {
                select.append($('<option>').val(team.id).text(team.name).attr('data-groupid', team.group_id));
              });
              select.trigger('change');
            }
         });
       }

       function loadStations(gid) {
         var group;
         var optname;

         const select = $('#stationSelect');

         $.ajax({
            url: 'get_stations.php?groupId=' + gid
            , type: 'GET'
            , dataType: 'json'
            , success: function(data) {
              for(var i = 0; i < data.length; i++) {
                if(data[i].gname != optname) {
                  optname = data[i].gname;
                  if([i] > 0) {
                     group += "</optgroup>";
                  }
                  group += "<optgroup label='" + optname + "'>";
                }
                group += "<option value='" + data[i].id + "' data-groupname='" + optname + "'>" + data[i].name + "</option>";
              }
              group += "</optgroup>";

              select.empty();
              select.append(group);
            }
         });
       }
     </script>
   </body>

</html>