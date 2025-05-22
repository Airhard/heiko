from flask import Blueprint, render_template, request, redirect, url_for, flash
from flask_login import login_required, current_user
from models import Team, Group, User, TeamUser
from extensions import db

admin = Blueprint('admin', __name__, url_prefix='/admin')

@admin.route('/')
@login_required
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

# Weitere Admin-Routen für Stationen, Fragen etc. folgen hier...
