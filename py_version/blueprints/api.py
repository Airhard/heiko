from flask import Blueprint, jsonify, request, url_for
from flask_login import login_required, current_user
from models import Team, Station, Question, Score, ScoreLog, StationTask
from extensions import db
from datetime import datetime

api = Blueprint('api', __name__, url_prefix='/api')

@api.route('/teams')
def get_teams():
    teams = Team.query.order_by(Team.name).all()
    return jsonify([{
        'id': team.id,
        'name': team.name,
        'group_id': team.group_id
    } for team in teams])

@api.route('/questions/<int:group_id>')
def get_questions(group_id):
    questions = Question.query.filter_by(group_id=group_id).order_by(Question.sortable).all()
    return jsonify([{
        'id': q.id,
        'question': q.question,
        'option_a': q.option_a,
        'option_b': q.option_b,
        'option_c': q.option_c,
        'option_d': q.option_d,
        'points': q.points
    } for q in questions])

@api.route('/station/info/<int:station_id>')
def get_station_info(station_id):
    station = Station.query.get_or_404(station_id)
    return jsonify({
        'info': station.information or ''
    })

@api.route('/station/tasks/<int:station_id>')
def get_station_tasks(station_id):
    tasks = StationTask.query.filter_by(station_id=station_id).order_by(StationTask.sortable).all()
    return jsonify([{
        'id': task.id,
        'task': task.task,
        'points': task.points,
        'btn1': task.btn1,
        'btn2': task.btn2,
        'btn3': task.btn3
    } for task in tasks])

@api.route('/verify_password', methods=['POST'])
def verify_password():
    station_id = request.form.get('stationId')
    password = request.form.get('password')
    station = Station.query.get_or_404(station_id)
    if station.code == password:
        return 'correct'
    return 'incorrect'

@api.route('/save_score', methods=['POST'])
@login_required
def save_score():
    data = request.get_json()
    score = Score(
        team_id=data['teamId'],
        quiz_id=data['quizId'],
        score=data['score'],
        user_id=data['userId']  # Passage/Durchgang
    )
    db.session.add(score)
    db.session.commit()

    # Log-Einträge speichern
    if 'log' in data:
        for log_entry in data['log']:
            log = ScoreLog(
                score_id=score.id,
                log=log_entry['text'],
                points=log_entry.get('points', 0),
                quiz=data['quizId']
            )
            db.session.add(log)
    
    db.session.commit()
    return jsonify({'success': True})

@api.route('/submit_answers/<int:station_id>', methods=['POST'])
@login_required
def submit_answers(station_id):
    data = request.get_json()
    station = Station.query.get_or_404(station_id)
    answers = data.get('answers', {})
    
    total_score = 0
    log_entries = []
    
    # Überprüfe jede Antwort
    for question_id, answer in answers.items():
        question = Question.query.get(int(question_id))
        if not question:
            continue
            
        points = 0
        if question.type == 'multiple_choice':
            # Für Multiple Choice: Überprüfe, ob die Antwort korrekt ist
            if str(answer) == str(question.correct_option):
                points = question.points
        elif question.type == 'text':
            # Für Textantworten: Überprüfe, ob die Antwort der erwarteten entspricht
            if answer.lower().strip() == question.correct_answer.lower().strip():
                points = question.points
                
        total_score += points
        log_entries.append({
            'text': f'Frage {question.text}: {points} Punkte',
            'points': points
        })
    
    # Speichere den Gesamtscore
    score = Score(
        team_id=current_user.team_id,
        station_id=station.id,
        score=total_score,
        completed_at=datetime.now()
    )
    db.session.add(score)
    db.session.commit()
    
    # Speichere die Log-Einträge
    for log_entry in log_entries:
        log = ScoreLog(
            score_id=score.id,
            log=log_entry['text'],
            points=log_entry['points']
        )
        db.session.add(log)
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'message': f'Score von {total_score} Punkten gespeichert.',
        'redirect_url': url_for('main.scores')
    })
