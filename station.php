<!DOCTYPE html>
<html lang="de">

   <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Landesjugendbewerb</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <style>
       .btn-w {
         width: 120px;
       }

       .transtoast {
         opacity: 0.4;
       }

       .pre-scrollable {
         max-height: 100px;
         overflow-y: scroll;
         border-bottom: 1px solid #d2d2d2;
         border-radius: 0.375rem;
       }
     </style>
   </head>

   <body style="background-color: #f6f6f6;">
     <div class="container-fluid mt-5" id="content">
       <h2 class="text-danger"><?=$_GET["groupName"];?>, <?=$_GET["stationName"];?>. <span id="passage"></span></h2>
       <div id="passwordContainer" class="mb-3">
         <input type="password" name="zugangscode " id="stationPassword" class="form-control" placeholder="Passwort für Station eingeben" autocomplete="off"
            data-1p-ignore>
         <button id="verifyPassword" class="btn btn-danger mt-2">Passwort überprüfen</button>
       </div>

       <div id="questionInformation" class="pre-scrollable mb-3" style="display: none;">
         <!-- Alle Informationen werden hier innerhalb ihrer eigenen Karten angezeigt -->
       </div>
       <div id="questionDefault" style="display: none;">
         <!-- Alle Standard Buttons werden hier innerhalb ihrer eigenen Karten angezeigt -->
       </div>
       <div id="questionContainer" style="display: none;">
         <!-- Alle Aufgaben werden hier innerhalb ihrer eigenen Karten angezeigt -->
       </div>
       <button id="finishButton" class="btn btn-success mt-3" style="display: none;">Abschluss</button>
       <button id="exitButton" class="btn btn-danger float-end mt-3" style="display: none;">Abbruch</button>
       <div id="resultsDisplay" class="mt-4 py-3 border-top" style="display: none;"></div>
     </div>

     <div class="toast-container position-fixed top-0 end-0 p-3">
       <div id="liveToast" class="toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive" aria-atomic="true">
         <div class="toast-header">
            <strong class="me-auto h3"></strong>
         </div>
       </div>
     </div>


     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
     <script>
       let currentQuestionIndex = 0;
       let questions = [];
       let totalPoints = 0;
       let passage = 0;
       let selectedOptions = new Set(); // Speichert die Indizes der gewählten Antworten
       let actions = []; // Dies wird alle Aktionen speichern

       $(document).ready(function() {
         const searchParams = new URLSearchParams(window.location.search);
         loadQuestions(searchParams.get("stationId"));

         $('#verifyPassword').click(function() {
            verifyStationPassword(searchParams.get("stationId"), $('#stationPassword').val());
         });

         $(document).on('click', '.btn-points', function(e) {
            e.preventDefault;
            var index = $(this).data('index');
            var points = $(this).data('points');
            var isAdding = $(this).data('adding');

            adjustPoints(index, points, isAdding);
         });

         $(document).on('click', '.btn-default2', function(e) {
            e.preventDefault;
            var index = $(this).data('index');
            var points = $(this).data('points');
            var isAdding = $(this).data('adding');
            var classname = $(this).data('classname');
            adjustPointsDefault(index, classname, points, isAdding);
         });

         $('#nextQuestion').click(function() {
            if(currentQuestionIndex + 1 < questions.length) {
              currentQuestionIndex++;
              displayQuestion();
            } else {
              showResults(); // Änderung zu showResults für die Endbearbeitung
            }
         });
       });

       function verifyStationPassword(stationId, password) {
         $.ajax({
            url: `verify_password.php`
            , type: 'POST'
            , data: { stationId: stationId, password: password }
            , success: function(data) {
              if(data === 'correct') {
                $('#passwordContainer').hide();
                $('#questionContainer').show();
                $('#questionInformation').show();
                $('#questionDefault').show();
                $('#finishButton').show();
                $('#exitButton').show();
                $('#resultsDisplay').show();
                loadInformation(stationId);
                loadDefault(stationId);
                loadQuestions(stationId);

                const searchParams = new URLSearchParams(window.location.search);
                loadPassage(stationId, searchParams.get("teamId"));
              } else {
                alert('Falsches Passwort!');
              }
            }
            , error: function() {
              alert('Fehler beim Überprüfen des Passworts.');
            }
         });
       }

       function loadQuestions(stationId) {
         $.ajax({
            url: `get_station_tasks.php?stationId=${stationId}`, // Angepasster Endpunkt
            type: 'GET'
            , dataType: 'json'
            , success: function(data) {
              questions = data; // In diesem Kontext repräsentieren "questions" die Aufgaben
              displayQuestion(); // Erste Aufgabe anzeigen
            }
            , error: function() {
              alert('Fehler beim Laden der Aufgaben.');
            }
         });
       }

       function loadDefault(stationId) {
         $.ajax({
            url: `get_station_default.php?stationId=${stationId}`, // Angepasster Endpunkt
            type: 'GET'
            , dataType: 'json'
            , success: function(data) {
              $('#questionDefault').empty();

              let btn1 = data[0].btn1;
              let btn2 = data[0].btn2;
              let btn3 = data[0].btn3;

              let div = `<div class="d-flex justify-content-around mb-2">`;

              div += `<div><input type="radio" class="btn-check btn-default" name="options" id="btn1" value="${btn1}">
   <label class="btn btn-success" for="btn1">volle Punkte</label></div>`;

              div += `<div><input type="radio" class="btn-check btn-default" name="options" id="btn2" value="${btn2}">
   <label class="btn btn-warning" for="btn2">halbe Punkte</label></div>`;

              div += `<div><input type="radio" class="btn-check btn-default" name="options" id="btn3" value="${btn3}">
   <label class="btn btn-danger" for="btn3">keine Punkte</label></div>`;

              div += `</div>`;

              $('#questionDefault').append(div);

              if(btn1 == 0) {
                $('#btn1, [for=btn1]').remove();
              } else {
                $('#btn1').click(() => adjustPointsDefault(1, '.btn-default', btn1, true));
              }

              if(btn2 == 0) {
                $('#btn2, [for=btn2]').remove();
              } else {
                $('#btn2').click(() => adjustPointsDefault(2, '.btn-default', btn2, true));
              }

              if(btn1 == 0) {
                $('#btn3, [for=btn3]').remove();
              } else {
                $('#btn3').click(() => adjustPointsDefault(3, '.btn-default', btn3, true));
              }

              if(btn1 == 0 && btn2 == 0) {
                $('#questionDefault').remove();
              }

            }
            , error: function() {
              alert('Fehler beim Laden der Buttons.');
            }
         });
       }


       function loadInformation(stationId) {
         $.ajax({
            url: `get_station_info.php?stationId=${stationId}`, // Angepasster Endpunkt
            type: 'GET'
            , dataType: 'json'
            , success: function(data) {

              if(data[0].information != "") {
                $('#questionInformation').empty();

                let qi = `<div class="card">
   <div class="card-body">
   <p class="card-text">${data[0].information}</p>
   </div>
   </div>`;

                $('#questionInformation').append(qi);
              }
            }
            , error: function() {
              alert('Fehler beim Laden der Information.');
            }
         });
       }


       function loadPassage(stationId, teamId) {
         $.ajax({
            url: `get_passage.php?stationId=${stationId}&teamId=${teamId}`, // Angepasster Endpunkt
            type: 'GET'
            , dataType: 'json'
            , success: function(data) {
              $('#passage').text("Durchgang " + data[0].passage);
              passage = data[0].passage;
            }
            , error: function() {
              alert('Fehler beim Laden des Durchgangs.');
            }
         });
       }


       function displayQuestion() {
         $('#questionContainer').empty(); // Den Container für Fragen/Aufgaben leeren
         var html = `<div class="accordion" id="accordion">`;
         var i = 0;
         questions.forEach((task, index) => {

            if(task.points == 998) {
              i++;

              if(i >= 2) {
                html += `</div></div></div>`;
              }

              html += `
   <div class="accordion-item">
   <h2 class="accordion-header">
   <button class="accordion-button collapsed bg-secondary-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${i}" aria-expanded="false"
   aria-controls="collapse${i}">
   ${task.task}
   </button>
   </h2>
   <div id="collapse${i}" class="accordion-collapse collapse" data-bs-parent="#accordion">
   <div class="accordion-body p-0">`;


            } else if(task.points == 999) {
              html += `<h4 class='text-danger mt-3 mb-1 ms-2'>${task.task}</h4><hr class='border border-danger border-1 opacity-50'>`;

            } else {
              html += `<div class="card mb-1 border-0 border-bottom">
   <div class="card-body">
   <div class="card-text m-0">${task.task}`

              if(task.points != 0) {
                html +=
                  `<button class="btn float-end btn-points btn-w btn-${(task.points > 0) ? 'success' : 'danger'}" data-index="${index}" data-points="${(task.points > 0) ? task.points : -task.points}" data-adding="${(task.points >= 0) ? true : false}">${(task.points > 0) ? '+'+task.points : task.points} Punkte</button>`;
              } else if(task.btn1 != 0) {

                html += `<div class="btn-group float-end ">`;

                html += `<input type="radio" class="btn-check btn-default2 btn_default2_${index}" data-classname=".btn_default2_${index}" data-index="${index}" data-points="${(task.btn1 > 0) ? task.btn1 : -task.btn1}" data-adding="${(task.btn1 >= 0) ? true : false}" id="btn_1_${index}" name="options_${index}" value="${task.btn1}">
   <label class="btn btn-success btn-w" for="btn_1_${index}">${(task.btn1 > 0) ? "+" : "-"}${task.btn1} Punkte</label>`;

                if(task.btn2 != 0) {
                  html += `<input type="radio" class="btn-check btn-default2 btn_default2_${index}" data-classname=".btn_default2_${index}" data-index="${index}" data-points="${(task.btn2 > 0) ? task.btn2 : -task.btn2}" data-adding="${(task.btn2 >= 0) ? true : false}" id="btn_2_${index}" name="options_${index}" value="${task.btn2}">
   <label class="btn btn-warning btn-w" for="btn_2_${index}">${(task.btn2 > 0) ? "+" : "-"}${task.btn2} Punkte</label>`;
                }

                html += `<input type="radio" class="btn-check btn-default2 btn_default2_${index}" data-classname=".btn_default2_${index}" data-index="${index}" data-points="${(task.btn3 > 0) ? task.btn3 : -task.btn3}" data-adding="${(task.btn3 >= 0) ? true : false}" id="btn_3_${index}" name="options_${index}" value="${task.btn3}">
   <label class="btn btn-danger btn-w" for="btn_3_${index}">${(task.btn3 > 0) ? "+" : (task.btn3 == 0) ? '' : "-"}${task.btn3} Punkte</label>`;

                html += `</div>`;
              }

              html += `</div></div></div>`;
            }



         });

         if(i > 1) {
            html += `</div></div></div></div>`;
         }

         html += `</div>`;

         $('#questionContainer').append(html);
       }

       function adjustPointsDefault(index, classname, points, isAdding) {

         $(classname).attr('disabled', true);

         var actionText;
         if(classname == ".btn-default") {
            if(index == 1) {
              actionText = 'volle Punkte';
            } else if(index == 2) {
              actionText = 'halbe Punkte';
            } else if(index == 3) {
              actionText = 'keine Punkte';
            }
         } else {
            actionText = questions[index].task;
         }

         actionText = (isAdding ? '+' : '-') + points + ' Punkte für ' + actionText;

         actions.push({
            block: 1
            , classname: classname
            , text: actionText
            , points: isAdding ? points : -
              points
         });

         totalPoints += isAdding ? points : -points;
         updateResults();

         toast(actionText, false);
       }

       function adjustPoints(index, points, isAdding) {

         let actionText = isAdding ? `+${points} Punkte für '${questions[index].task}'` :
            `${isAdding ? points : -points} Punkte für '${questions[index].task}' abgezogen`;
         actions.push({ block: 0, classname: null, text: actionText, points: isAdding ? points : -points });

         totalPoints += isAdding ? points : -points; // Punkte aktualisieren
         updateResults(); // Ergebnisse anzeigen

         toast(actionText, true);
       }

       function toast(text, block) {
         $('#content').addClass('transtoast');

         let btn = $('.btn');

         if(block == true) {
            btn.attr('disabled', true)
         }
         $('#liveToast').hide().show().find('strong').text(text);
         setTimeout(function() {

            $('#content').removeClass('transtoast');
            $('#liveToast').fadeOut('fast');
            if(block == true) {
              btn.attr('disabled', false)
            }
         }, 1000);
       }

       function updateResults() {
         //if($('#resultsDisplay').length === 0) {
         //let resultsDiv = $('<div>').attr('id', 'resultsDisplay').addClass('mt-4 p-3 border-top');
         //$('#questionContainer').after(resultsDiv);
         //}

         let actionsList = actions.map((a, index) =>
              `<li class="list-group-item"><button class="btn btn-danger btn-sm remove-action" data-block="${a.block}" data-classname="${a.classname}" data-index="${index}">Löschen</button> <small>${a.text}</small></li>`
            )
            .join('');
         $('#resultsDisplay').html(`
   <h4>Gesamtpunktzahl: ${totalPoints}</h4>
   <ul class='list-group'>${actionsList}</ul>
   `);

         // Füge Event-Listener für jeden Löschen-Button hinzu
         $('.remove-action').click(function() {
            let index = $(this).data('index'); // Index aus dem data-Attribut des Buttons abrufen
            removeAction(index);

            let block = $(this).data('block');
            if(block == 1) {
              let classname = $(this).data('classname');
              $(classname).attr('disabled', false);
            }
         });
       }

       function removeAction(index) {
         if(index > -1 && index < actions.length) {
            totalPoints -= actions[index].points; // Punkte der zu löschenden Aktion abziehen
            actions.splice(index, 1); // Aktion aus dem Array entfernen
            updateResults(); // Aktualisiere die Ergebnisanzeige erneut, um die Liste zu aktualisieren
         }
       }

       function completeTask(index, points) {
         x
         let button = $('#questionContainer').find('.card').eq(index).find('button');
         if(button.hasClass('btn-success')) {
            // Aufgabe war bereits erfüllt, Punkte abziehen
            totalPoints -= parseInt(points);
            button.removeClass('btn-success').addClass('btn-outline-secondary');
         } else {
            // Aufgabe erfüllen, Punkte hinzufügen
            totalPoints += parseInt(points);
            button.removeClass('btn-outline-secondary').addClass('btn-success');
         }
         updateResults(); // Aktualisiere die Anzeige der Gesamtpunktzahl
       }

       $('#exitButton').click(function() {
         window.location.href = "index.php";
       });


       $('#finishButton').click(function() {
         let searchParams = new URLSearchParams(window.location.search);
         const teamId = searchParams.get("teamId");
         const userId = passage; //searchParams.get("userId"); //Durchgang
         const stationId = searchParams.get("stationId");
         const score = totalPoints;

         $.ajax({
            url: 'save_score.php'
            , type: 'POST'
            , data: {
              teamId: teamId
              , userId: userId
              , quizId: stationId
              , score: score
              , log: actions
            }
            , success: function(response) {
              $('#questionContainer').html(
                `<div class="card-body"><h4>Station beendet</h4><p>Vielen Dank für deine Teilnahme!</p><p><a href="index.php">Weiter zur Hauptauswahl</a></p></div>`
              );

              $('button, #resultsDisplay, #questionInformation, #questionDefault').remove();
            }
            , error: function() {
              alert('Fehler beim Speichern der Punkte.');
            }
         });
       });
     </script>
   </body>

</html>