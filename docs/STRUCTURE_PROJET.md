# Structure du projet EcoRide

## 📁 Organisation des fichiers

### Pages principales
- `index.php` - Page d'accueil
- `trajets.php` - Recherche et liste des trajets
- `trajet-detail.php` - Détail d'un trajet spécifique
- `creer-trajet.php` - Création de trajet (conducteurs)
- `connexion.php` - Page de connexion
- `inscription.php` - Page d'inscription
- `logout.php` - Script de déconnexion

### Interfaces utilisateurs
- `user/dashboard.php` - Interface utilisateur complète
- `admin/dashboard.php` - Interface d'administration

### API REST
- `api/login-simple.php` - Authentification utilisateur
- `api/register-simple.php` - Inscription utilisateur
- `api/create-trajet.php` - Création de trajets
- `api/add-vehicle.php` - Ajout de véhicules
- `api/search-trajets.php` - Recherche de trajets
- `api/get-trajet-detail.php` - Détails d'un trajet
- `api/participer-trajet.php` - Réservation de trajets
- `api/check-session.php` - Vérification de session
- `api/test-db.php` - Test de connexion BDD
- `api/test-direct.php` - Test direct BDD

### Configuration
- `config/init.php` - Initialisation et session
- `config/database.php` - Classe de connexion BDD
- `config/functions.php` - Fonctions utilitaires

### Ressources
- `css/` - Feuilles de style
  - `style.css` - Styles principaux
  - `auth.css` - Styles authentification
  - `home.css` - Styles page d'accueil
  - `trajets.css` - Styles pages trajets
  - `trajet-detail.css` - Styles détail trajet

- `js/` - Scripts JavaScript
  - `main.js` - Scripts principaux
  - `auth.js` - Scripts authentification
  - `home.js` - Scripts page d'accueil
  - `trajets.js` - Scripts pages trajets
  - `trajet-detail.js` - Scripts détail trajet

- `images/` - Images et ressources visuelles
- `database/` - Scripts SQL
  - `schema.sql` - Structure de la base
  - `seed.sql` - Données de test

### Design et documentation
- `design/` - Maquettes et charte graphique
- `docs/` - Documentation technique
- `enonce/` - Énoncé du projet

### Fichiers de projet
- `README.md` - Documentation principale
- `INFOS_EVALUATION_RNCP.txt` - Récapitulatif évaluation
- `STRUCTURE_PROJET.md` - Ce fichier
- `.gitignore` - Fichiers ignorés par Git
- `render.json` - Configuration déploiement Render

### Dossiers de cache et logs
- `cache/` - Cache applicatif
- `logs/` - Fichiers de logs

## 🚀 Déploiement
- **Production** : Render (https://ecoride-production-2631.up.render.app)
- **Repository** : GitHub (https://github.com/Isydoria/EcoRide)
- **Base de données** : MySQL sur Render

## 👥 Comptes de test
- **Utilisateur** : demo@ecoride.fr / demo123
- **Administrateur** : admin@ecoride.fr / Ec0R1de!

## 📊 État du projet
✅ Architecture MVC complète
✅ Interface utilisateur moderne avec dashboard
✅ Interface d'administration avec statistiques
✅ API REST fonctionnelle
✅ Système d'authentification sécurisé
✅ Gestion des véhicules et trajets
✅ Base de données relationnelle
✅ Déployé en production
✅ Documentation technique

**Prêt pour évaluation RNCP Développeur Web et Web Mobile**