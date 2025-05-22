from flask import Blueprint, render_template, request, redirect, url_for, flash, jsonify, session
from flask_login import login_required, current_user
from flask_wtf import FlaskForm
from wtforms import PasswordField, SubmitField
from wtforms.validators import DataRequired
from werkzeug.security import check_password_hash
from models import Team, Station, Group, Score, StationTask, Question
from extensions import db
from datetime import datetime

# Formular für Stations-Passwort
class StationPasswordForm(FlaskForm):
    password = PasswordField('Passwort', validators=[DataRequired()])
    submit = SubmitField('Überprüfen')

main = Blueprint('main', __name__)

@main.route('/')
def index():
    return redirect(url_for('main.stations'))

@main.route('/stations')
#@login_required
def stations():
    stations = Station.query.all()
    for station in stations:
        # Überprüfe, ob die Station für das aktuelle Team abgeschlossen ist
        station.is_completed = Score.query.filter_by(
            team_id=current_user.team_id,
            station_id=station.id
        ).first() is not None
    return render_template('stations.html', stations=stations)

@main.route('/station/<int:station_id>')
#@login_required
def station_detail(station_id):
    station = Station.query.get_or_404(station_id)
    password_form = StationPasswordForm() if station.requires_password else None
    
    # Überprüfe, ob die Station ein Passwort benötigt und ob es bereits verifiziert wurde
    station.is_password_verified = not station.requires_password or \
        session.get(f'station_{station.id}_verified', False)
    
    return render_template('station_detail.html', 
                         station=station,
                         password_form=password_form)

@main.route('/station/<int:station_id>/verify', methods=['POST'])
#@login_required
def verify_station_password(station_id):
    station = Station.query.get_or_404(station_id)
    form = StationPasswordForm()
    
    if form.validate_on_submit():
        if check_password_hash(station.password_hash, form.password.data):
            session[f'station_{station.id}_verified'] = True
            flash('Passwort korrekt! Sie können nun mit der Station beginnen.', 'success')
            return redirect(url_for('main.station_detail', station_id=station.id))
        else:
            flash('Falsches Passwort!', 'danger')
    
    return redirect(url_for('main.station_detail', station_id=station.id))

@main.route('/scores')
#@login_required
def scores():
    teams = Team.query.order_by(Team.total_score.desc()).all()
    stations = Station.query.all()
    
    # Berechne Durchschnittspunkte für jedes Team
    for team in teams:
        scores = Score.query.filter_by(team_id=team.id).all()
        if scores:
            team.average_score = sum(score.score for score in scores) / len(scores)
        else:
            team.average_score = 0
    
    # Hole die Scores für jede Station
    for station in stations:
        station.scores = Score.query.filter_by(station_id=station.id)\
                                  .order_by(Score.score.desc())\
                                  .all()
    
    return render_template('scores.html', teams=teams, stations=stations)
