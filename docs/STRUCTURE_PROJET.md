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
- `user/dashboard.php` - Interface utilisateur complète (avec système d'avis)
- `employee/dashboard.php` - Interface employé
- `admin/dashboard.php` - Interface d'administration

### API REST
- `api/login-simple.php` - Authentification utilisateur
- `api/register-simple.php` - Inscription utilisateur
- `api/create-trajet.php` - Création de trajets
- `api/add-vehicle.php` - Ajout de véhicules
- `api/search-trajets.php` - Recherche de trajets
- `api/get-trajet-detail.php` - Détails d'un trajet
- `api/participer-trajet.php` - Réservation de trajets
- `api/create-avis.php` - Création d'avis/évaluations
- `api/get-avis.php` - Récupération des avis reçus
- `api/get-trips-to-rate.php` - Liste des trajets à évaluer
- `api/manage-trip-status.php` - Gestion statut trajets (démarrer/terminer)
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

### Scripts d'initialisation
- `init-complete.php` - Initialisation structure PostgreSQL (8 tables) - **Render uniquement**
- `init-demo-data.php` - Données démo PostgreSQL (34 trajets, 3 employés) - **Render**
- `init-demo-data-local.php` - Données démo MySQL (mêmes données) - **Local/WampServer**
- `init-simple.php` - Initialisation minimale PostgreSQL (3 utilisateurs) - **Render**

### Fichiers de projet
- `README.md` - Documentation principale
- `HISTORIQUE_CONVERSATION.txt` - Journal détaillé des sessions
- `STRUCTURE_PROJET.md` - Ce fichier
- `.gitignore` - Fichiers ignorés par Git

### Dossiers de cache et logs
- `cache/` - Cache applicatif
- `logs/` - Fichiers de logs

## 🚀 Déploiement
- **Production** : Render.com (https://ecoride-om7c.onrender.com)
- **Repository** : GitHub (https://github.com/Isydoria/EcoRide)
- **Base de données Production** : PostgreSQL 15 sur Render
- **Base de données Développement** : MySQL 8.0 local (WampServer/Docker)

## 👥 Comptes de test
- **Administrateur** : admin@ecoride.fr / Ec0R1de!
- **Employés** : sophie.martin@ecoride.fr / Sophie2025! (et 2 autres)
- **Utilisateurs** : jean.dupont@ecoride.fr / Jean2025! (100 crédits)

## 📊 État du projet
✅ Architecture MVC complète
✅ Code compatible MySQL/PostgreSQL avec détection automatique
✅ Interface utilisateur moderne avec dashboard
✅ Interface d'administration avec statistiques et graphiques
✅ API REST fonctionnelle (18+ endpoints)
✅ Système d'authentification sécurisé (bcrypt, sessions)
✅ Système d'avis et évaluation bidirectionnel complet
✅ Gestion statut trajets (en attente, en cours, terminé)
✅ Gestion des véhicules et trajets (34 trajets de démo)
✅ Base de données relationnelle + NoSQL (MongoDB fake)
✅ 3 environnements supportés (WampServer, Docker, Render)
✅ Déployé en production avec PostgreSQL 15
✅ Documentation technique exhaustive (150+ pages)
✅ Scripts d'initialisation universels

**Prêt pour évaluation RNCP Développeur Web et Web Mobile**