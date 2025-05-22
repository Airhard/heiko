from flask import Blueprint, render_template, request, redirect, url_for, flash
from flask_login import login_required, current_user
from models import Team, Group, User, TeamUser, Station
from extensions import db

admin = Blueprint('admin', __name__, url_prefix='/admin')

@admin.route('/')
#@login_required
def index():
    if not current_user.is_admin:
        flash('Zugriff verweigert')
        return redirect(url_for('main.index'))
    return render_template('admin/index.html')

@admin.route('/teams', methods=['GET', 'POST'])
@login_required
def teams():
    if not current_user.is_admin:
        flash('Zugriff verweigert')
        return redirect(url_for('main.index'))
        
    if request.method == 'POST':
        team_name = request.form.get('teamName')
        group_id = request.form.get('group')
        
        team = Team(name=team_name, group_id=group_id)
        db.session.add(team)
        db.session.commit()
        flash('Team wurde erstellt')
        
    groups = Group.query.filter_by(active=True).all()
    teams = Team.query.order_by(Team.name).all()
    return render_template('admin/teams.html', groups=groups, teams=teams)

@admin.route('/team/delete/<int:team_id>')
@login_required
def delete_team(team_id):
    if not current_user.is_admin:
        flash('Zugriff verweigert')
        return redirect(url_for('main.index'))
        
    team = Team.query.get_or_404(team_id)
    db.session.delete(team)
    db.session.commit()
    flash('Team wurde gelöscht')
    return redirect(url_for('admin.teams'))

@admin.route('/station/create', methods=['GET', 'POST'])
@login_required
def create_station():
    if not current_user.is_admin:
        flash('Zugriff verweigert')
        return redirect(url_for('main.index'))
        
    if request.method == 'POST':
        name = request.form.get('name')
        group_id = request.form.get('group')
        information = request.form.get('information')
        code = request.form.get('code')
        requires_password = bool(request.form.get('requires_password'))
        password = request.form.get('password')
        
        station = Station(
            name=name,
            group_id=group_id,
            information=information,
            code=code,
            requires_password=requires_password
        )
        
        if requires_password and password:
            from werkzeug.security import generate_password_hash
            station.password_hash = generate_password_hash(password)
            
        db.session.add(station)
        db.session.commit()
        flash('Station wurde erfolgreich erstellt')
        return redirect(url_for('admin.index'))
    
    groups = Group.query.filter_by(active=True).all()
    return render_template('admin/station_form.html', groups=groups, station=None)

# Weitere Admin-Routen für Fragen etc. folgen hier...
