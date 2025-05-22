from flask_login import UserMixin
from datetime import datetime
from sqlalchemy import event
from extensions import db

class User(UserMixin, db.Model):
    __tablename__ = 'rk_users'
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    password_hash = db.Column(db.String(120), nullable=False)
    is_admin = db.Column(db.Boolean, default=False)
    team_memberships = db.relationship('TeamUser', back_populates='user')

class Group(db.Model):
    __tablename__ = 'rk_groups'
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(80), nullable=False)
    active = db.Column(db.Boolean, default=True)
    teams = db.relationship('Team', backref='group', lazy=True)
    questions = db.relationship('Question', backref='group', lazy=True)
    stations = db.relationship('Station', backref='group', lazy=True)

class Team(db.Model):
    __tablename__ = 'rk_teams'
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(80), unique=True, nullable=False)
    group_id = db.Column(db.Integer, db.ForeignKey('rk_groups.id'))
    members = db.relationship('TeamUser', back_populates='team')
    scores = db.relationship('Score', backref='team', lazy=True)

class TeamUser(db.Model):
    __tablename__ = 'rk_team_users'
    user_id = db.Column(db.Integer, db.ForeignKey('rk_users.id'), primary_key=True)
    team_id = db.Column(db.Integer, db.ForeignKey('rk_teams.id'), primary_key=True)
    user = db.relationship('User', back_populates='team_memberships')
    team = db.relationship('Team', back_populates='members')

class Station(db.Model):
    __tablename__ = 'rk_stations'
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(80), nullable=False)
    group_id = db.Column(db.Integer, db.ForeignKey('rk_groups.id'))
    information = db.Column(db.Text)
    code = db.Column(db.String(4))
    requires_password = db.Column(db.Boolean, default=False)
    password_hash = db.Column(db.String(120))
    tasks = db.relationship('StationTask', backref='station', lazy=True, order_by='StationTask.sortable')
    scores = db.relationship('Score', backref='station', lazy=True, primaryjoin='Station.id == Score.station_id')

class StationTask(db.Model):
    __tablename__ = 'rk_station_tasks'
    id = db.Column(db.Integer, primary_key=True)
    station_id = db.Column(db.Integer, db.ForeignKey('rk_stations.id'))
    task = db.Column(db.Text)
    type = db.Column(db.String(20), default='text')  # 'text' oder 'image'
    content = db.Column(db.Text)
    points = db.Column(db.Float)
    sortable = db.Column(db.Integer)
    questions = db.relationship('Question', backref='task', lazy=True)

class Question(db.Model):
    __tablename__ = 'rk_questions'
    id = db.Column(db.Integer, primary_key=True)
    task_id = db.Column(db.Integer, db.ForeignKey('rk_station_tasks.id'))
    type = db.Column(db.String(20), default='multiple_choice')  # 'multiple_choice' oder 'text'
    text = db.Column(db.Text)
    group_id = db.Column(db.Integer, db.ForeignKey('rk_groups.id'))
    points = db.Column(db.Float)
    options = db.relationship('QuestionOption', backref='question', lazy=True)
    correct_answer = db.Column(db.String(255))  # Für Textantworten
    sortable = db.Column(db.Integer)

class QuestionOption(db.Model):
    __tablename__ = 'rk_question_options'
    id = db.Column(db.Integer, primary_key=True)
    question_id = db.Column(db.Integer, db.ForeignKey('rk_questions.id'))
    text = db.Column(db.String(255))
    is_correct = db.Column(db.Boolean, default=False)

class Score(db.Model):
    __tablename__ = 'rk_scores'
    id = db.Column(db.Integer, primary_key=True)
    team_id = db.Column(db.Integer, db.ForeignKey('rk_teams.id'))
    station_id = db.Column(db.Integer, db.ForeignKey('rk_stations.id'))
    score = db.Column(db.Float)
    user_id = db.Column(db.Integer, db.ForeignKey('rk_users.id'))  # Durchgang
    completed_at = db.Column(db.DateTime, default=datetime.utcnow)
    weight = db.Column(db.Float, default=1)
    max_score = db.Column(db.Float, default=0)
    logs = db.relationship('ScoreLog', backref='score', lazy=True)

    @property
    def total_score(self):
        return self.score * self.weight if self.score is not None else 0

class ScoreLog(db.Model):
    __tablename__ = 'rk_scores_log'
    id = db.Column(db.Integer, primary_key=True)
    score_id = db.Column(db.Integer, db.ForeignKey('rk_scores.id'))
    log = db.Column(db.Text)
    points = db.Column(db.Float)
    timestamp = db.Column(db.DateTime, default=datetime.utcnow)

@event.listens_for(StationTask, 'after_insert')
def update_station_task_sortable(mapper, connection, target):
    if target.sortable is None:
        connection.execute(
            StationTask.__table__.update().
            where(StationTask.id == target.id).
            values(sortable=target.id)
        )

@event.listens_for(Question, 'after_insert')
def update_question_sortable(mapper, connection, target):
    if target.sortable is None:
        connection.execute(
            Question.__table__.update().
            where(Question.id == target.id).
            values(sortable=target.id)
        )
