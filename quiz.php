<!DOCTYPE html>
<html lang="de">

   <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Landesjugendbewerb</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   </head>

   <body style="background-color: #f6f6f6;">
     <div class="container mt-5">
       <h2 class="text-danger"><?=$_GET["groupName"];?>, <?=$_GET["stationName"];?>. </h2>
       <div id="questionContainer" class="card">
         <div class="card-body">
            <h5 class="card-title" id="questionTitle"></h5>
            <div id="optionsContainer" class="mb-3 d-grid gap-2"></div>
            <button id="nextQuestion" class="btn btn-danger">Weiter</button>
         </div>
       </div>
     </div>

     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
     <script>
       let currentQuestionIndex = 0;
       let questions = [];
       let totalPoints = 0;
       let selectedOptions = new Set(); // Speichert die Indizes der gewählten Antworten
       let answerDetails = []; // Speichert Details zu jeder Frage und Antwort


       $(document).ready(function() {
         let searchParams = new URLSearchParams(window.location.search)

         const stationId = searchParams.get("stationId");
         loadQuestions(stationId);

         $('#nextQuestion').click(function() {
            checkAnswers();
            if(currentQuestionIndex + 1 < questions.length) {
              currentQuestionIndex++;
              displayQuestion();
            } else {
              showResults();
            }
         });
       });

       function loadQuestions(stationId) {
         $.ajax({
            url: 'get_questions.php?groupId=' + stationId, // Pfad zu deinem PHP-Skript, das die Fragen lädt
            type: 'GET'
            , dataType: 'json'
            , success: function(data) {
              questions = data;
              displayQuestion();
            }
            , error: function() {
              alert('Fehler beim Laden der Fragen.');
            }
         });
       }

       function displayQuestion() {
         let question = questions[currentQuestionIndex];
         $('#questionTitle').text(question.question);
         $('#optionsContainer').empty();
         selectedOptions.clear();
         ['option_a', 'option_b', 'option_c', 'option_d'].forEach((option, index) => {
            let button = $('<button>')
              .addClass('btn btn-outline-secondary') // Neutrale Farbe
              .text(question[option])
              .click(() => selectOption(index));
            $('#optionsContainer').append(button);
         });
         $('#nextQuestion').hide(); // Verstecke den Weiter-Button zunächst
       }

       function selectOption(index) {
         if(selectedOptions.has(index)) {
            selectedOptions.delete(index);
            $('#optionsContainer').find('button').eq(index).removeClass('btn-warning').addClass('btn-outline-secondary');
         } else {
            selectedOptions.add(index);
            $('#optionsContainer').find('button').eq(index).removeClass('btn-outline-secondary').addClass('btn-warning'); // Gelb für Auswahl
         }
         $('#nextQuestion').show(); // Zeige den Weiter-Button bei jeder Auswahl
       }

       function checkAnswers() {

         const question = questions[currentQuestionIndex];
         const correctIndices = question.correct_option.split(',').map(opt => opt.charCodeAt(0) - 'A'.charCodeAt(0));
         let selectedCorrectCount = 0; // Zähler für korrekt ausgewählte Antworten
         let anyIncorrectSelected = false; // Flag für ausgewählte falsche Antworten

         // Überprüfe jede ausgewählte Option auf Richtigkeit
         selectedOptions.forEach(index => {
            if(correctIndices.includes(index)) {
              selectedCorrectCount++; // Zähle korrekt ausgewählte Optionen
            } else {
              anyIncorrectSelected = true; // Markiere, wenn eine falsche Antwort ausgewählt wurde
            }
         });

         // Berechne die Punkte, wenn keine falschen Antworten ausgewählt wurden
         let pointsAwarded = 0;
         if(!anyIncorrectSelected) {
            let correctRatio = selectedCorrectCount / correctIndices.length; // Verhältnis der korrekten Antworten
            pointsAwarded = correctRatio * question.points;
            pointsAwarded = Number(parseFloat(pointsAwarded).toFixed(0));
            totalPoints += pointsAwarded; // Teilpunkte basierend auf dem Verhältnis
         }

         // Speichern der gewählten Antworten und der Punkte


         let answerDetailsArray = []; // Speichert Details zu jeder Frage und Antwort
         $.each(Array.from(selectedOptions).map(idx => idx), function(index, item) {

            let answer;
            switch(item) {
              case 0:
                answer = question.option_a;
                break;
              case 1:

                answer = question.option_b;
                break;
              case 2:

                answer = question.option_c;
                break;
              case 3:

                answer = question.option_d;
                break;
            }

            answerDetailsArray.push(answer);
         });

         answerDetails.push({
            question: question.question
            , selectedAnswers: answerDetailsArray
            , points: pointsAwarded
         });


         //console.log("Antwort:", answerDetails)

         //console.log("Frage:", question);
         //console.log("Frage:", question.question);
         //console.log("Ausgewählte Optionen:", Array.from(selectedOptions).map(idx => idx));
         console.log("Vergebene Punkte:", pointsAwarded);
       }



       function showResults() {
         let searchParams = new URLSearchParams(window.location.search);


         const teamId = searchParams.get("teamId");
         const quizId = searchParams.get("stationId");
         const score = totalPoints;


         console.log(answerDetails)

         $.ajax({
            url: `get_passage.php?stationId=${quizId}&teamId=${teamId}`, // Angepasster Endpunkt
            type: 'GET'
            , dataType: 'json'
            , success: function(data) {

              const userId = data[0].passage; //Durchgang

              $.ajax({
                url: 'save_score.php'
                , type: 'POST'
                , data: {
                  teamId: teamId
                  , userId: userId
                  , quizId: quizId
                  , score: score
                  , quizLog: answerDetails
                }
                , success: function(response) {
                  $('#questionContainer').html(
                     `<div class="card-body"><h4>Quiz beendet. Durchgang ${data[0].passage}</h4><p>Vielen Dank für deine Teilnahme!</p><p><a href="index.php">Weiter zur Hauptauswahl</a></p></div>`
                  );
                }
                , error: function() {
                  alert('Fehler beim Speichern der Punkte.');
                }
              });

            }
            , error: function() {
              alert('Fehler beim speichern.');
            }
         });





       }
     </script>
   </body>

</html>