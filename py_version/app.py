from flask import Flask
from extensions import db, login_manager
from models import User
import os

def create_app():
    app = Flask(__name__)
    
    # Konfiguriere die Datenbank
    instance_path = os.path.abspath(os.path.join(os.path.dirname(__file__), 'instance'))
    os.makedirs(instance_path, exist_ok=True)
    db_path = os.path.join(instance_path, 'bewerb.db')
      # Konfiguration
    app.config['SECRET_KEY'] = 'dev-key-1234'  # In Produktion durch sichere Umgebungsvariable ersetzen
    app.config['SQLALCHEMY_DATABASE_URI'] = f'sqlite:///{db_path}'
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    app.config['WTF_CSRF_SECRET_KEY'] = 'csrf-key-5678'  # In Produktion durch sichere Umgebungsvariable ersetzen
    
    # Initialisiere Erweiterungen
    db.init_app(app)
    login_manager.init_app(app)
    
    # User Loader
    @login_manager.user_loader
    def load_user(user_id):
        return User.query.get(int(user_id))
    
    # Registriere Blueprints
    from blueprints.admin import admin
    from blueprints.auth import auth
    from blueprints.main import main
    from blueprints.api import api
    
    app.register_blueprint(admin)
    app.register_blueprint(auth)
    app.register_blueprint(main)
    app.register_blueprint(api)
    
    return app

# Erstelle die App-Instanz
app = create_app()

if __name__ == '__main__':
    with app.app_context():
        # Erstelle die Datenbank-Tabellen
        db.create_all()
        
        # Erstelle Admin-Benutzer, wenn noch nicht vorhanden
        admin_user = User.query.filter_by(username='admin').first()
        if not admin_user:
            admin_user = User(
                username='admin',
                password_hash='pbkdf2:sha256:600000$5yFxXtCwMKfynXH3$44e62a27d2afac96c9a3e53a513821ef92d58e1c41f5c7e633ad5ccca042dd07',  # Password: admin
                is_admin=True
            )
            db.session.add(admin_user)
            db.session.commit()
    
    app.run(debug=True)
