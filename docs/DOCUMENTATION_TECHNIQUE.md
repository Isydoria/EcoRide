# 🛠️ DOCUMENTATION TECHNIQUE - ECORIDE

**Plateforme de covoiturage écologique**
**Projet RNCP - Développeur Web et Web Mobile**

---

## 📋 SOMMAIRE

1. [Réflexions initiales technologiques](#1-réflexions-initiales-technologiques)
2. [Configuration environnement de travail](#2-configuration-environnement-de-travail)
3. [Modèle conceptuel de données](#3-modèle-conceptuel-de-données)
4. [Diagrammes d'utilisation et de séquence](#4-diagrammes-dutilisation-et-de-séquence)
5. [Documentation du déploiement](#5-documentation-du-déploiement)
6. [Architecture technique détaillée](#6-architecture-technique-détaillée)

---

## 1. RÉFLEXIONS INITIALES TECHNOLOGIQUES

### 🎯 **Analyse du besoin**

Le projet EcoRide répond à un double enjeu :
- **Écologique** : Réduire l'empreinte carbone des déplacements
- **Social** : Créer une communauté de covoiturage accessible

### 🔍 **Choix technologiques justifiés**

#### **Backend : PHP 8.1+**
```
✅ Avantages :
- Langage maîtrisé avec écosystème riche
- PDO intégré pour la sécurité (requêtes préparées)
- Sessions natives pour l'authentification
- Déploiement simple sur la plupart des hébergeurs

❌ Alternatives écartées :
- Node.js : Complexité supplémentaire pour un MVP
- Python/Django : Courbe d'apprentissage importante
- Java : Trop lourd pour ce type d'application
```

#### **Base de données : MySQL 8.0+ (Local) & PostgreSQL 15 (Production)**
```
✅ Avantages architecture multi-BDD :
- MySQL : Développement local avec phpMyAdmin, écosystème connu
- PostgreSQL : Production Render.com, robustesse professionnelle
- Code compatible universellement (détection automatique du driver)
- ACID compliance pour la cohérence des transactions
- Support natif des contraintes de clés étrangères
- Performance optimisée pour les requêtes géographiques

🔄 Compatibilité assurée par :
- Détection PDO driver (mysql vs pgsql)
- Requêtes SQL conditionnelles
- Conversion colonnes (statut/is_active, created_at/date_inscription)

❌ Alternatives écartées :
- MongoDB seul : NoSQL inadapté pour les relations complexes
- SQLite : Limitation pour le multi-utilisateur
```

#### **Frontend : HTML5/CSS3/JavaScript natif**
```
✅ Avantages :
- Performance maximale (pas de framework lourd)
- Compatibilité universelle
- Contrôle total sur le code généré
- Apprentissage des fondamentaux

❌ Alternatives écartées :
- React : Complexité supplémentaire, bundling nécessaire
- Vue.js : Intéressant mais pas nécessaire pour ce projet
- Bootstrap : Préférence pour CSS custom et apprentissage
```

#### **Hébergement : Render.com (PostgreSQL)**
```
✅ Avantages :
- Déploiement Git automatique
- Base de données PostgreSQL 15 managée
- Variables d'environnement sécurisées (DATABASE_URL)
- HTTPS automatique avec certificats Let's Encrypt
- Monitoring intégré et logs en temps réel
- Déploiement gratuit pour projets étudiants

🔧 Configuration :
- PostgreSQL 15 avec SSL requis (sslmode=require)
- DSN explicite : pgsql:host=...;port=5432;dbname=...
- Scripts d'initialisation: init-complete.php, init-demo-data.php

❌ Alternatives écartées :
- Heroku : Plus cher depuis suppression tier gratuit
- Vercel : Orienté frontend, serverless functions
- Hébergement classique : Configuration manuelle complexe
```

### 🏗️ **Architecture choisie : MVC adapté**

```
📁 Structure :
/config/        ← Configuration centralisée
/api/          ← Contrôleurs API (endpoints REST)
/user/         ← Vues utilisateur
/admin/        ← Vues administration
/css/          ← Styles (Vue)
/js/           ← Scripts client (Vue)
/database/     ← Modèle (schemas, seeds)

Avantages :
- Séparation claire des responsabilités
- Maintenabilité et évolutivité
- Tests facilités par composant
- Réutilisabilité du code
```

---

## 2. CONFIGURATION ENVIRONNEMENT DE TRAVAIL

### 💻 **Environnement de développement**

#### **Stack locale (WAMP)**
```yaml
Serveur web: Apache 2.4+
PHP: 8.1+ avec extensions
  - pdo_mysql (base de données)
  - session (authentification)
  - json (API responses)
  - curl (communications)

Base de données: MySQL 8.0
  - Charset: utf8mb4 (support emoji/unicode complet)
  - Collation: utf8mb4_unicode_ci
  - InnoDB: Support transactions ACID

Outils:
  - phpMyAdmin: Administration base
  - Git: Versioning et déploiement
  - VS Code: IDE avec extensions PHP
```

#### **Configuration adaptative**
```php
// config/database.php - Détection automatique environnement
class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;

    public function __construct() {
        // Priorité aux variables Render (production)
        $this->host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST');
        $this->dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE');
        $this->username = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER');
        $this->password = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD');

        // Fallback local si variables vides
        if (empty($this->host) || empty($this->dbname) || empty($this->username)) {
            $this->host = 'localhost';
            $this->dbname = 'ecoride_db';
            $this->username = 'root';
            $this->password = '';
        }
    }
}
```

### 🔧 **Outils de développement**

#### **IDE et extensions**
- **VS Code** avec extensions :
  - PHP Intelephense (autocomplétion)
  - MySQL (requêtes directes)
  - GitLens (historique Git)
  - Live Server (test local)

#### **Versionning Git**
```bash
# Configuration initiale
git init
git remote add origin https://github.com/Isydoria/EcoRide.git

# Workflow de développement
git add .
git commit -m "feat: nouvelle fonctionnalité"
git push origin main

# Render déploie automatiquement à chaque push
```

#### **Base de données**
```sql
-- Création locale
CREATE DATABASE ecoride_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Import structure et données
mysql -u root -p ecoride_db < database/schema.sql
mysql -u root -p ecoride_db < database/seed.sql
```

---

## 3. MODÈLE CONCEPTUEL DE DONNÉES

### 🗃️ **Diagramme Entité-Relation (ERD)**

```
┌─────────────────┐    1:n     ┌─────────────────┐    1:n     ┌──────────────────┐
│   UTILISATEUR   │────────────│    VOITURE      │────────────│   COVOITURAGE    │
├─────────────────┤            ├─────────────────┤            ├──────────────────┤
│ utilisateur_id  │◄──┐        │ voiture_id      │◄──┐        │ covoiturage_id   │◄──┐
│ pseudo          │   │        │ utilisateur_id  │   │        │ conducteur_id    │   │
│ email           │   │        │ marque          │   │        │ voiture_id       │   │
│ password        │   │        │ modele          │   │        │ ville_depart     │   │
│ role            │   │        │ immatriculation │   │        │ ville_arrivee    │   │
│ credit          │   │        │ places          │   │        │ date_depart      │   │
│ statut          │   │        │ couleur         │   │        │ prix_par_place   │   │
│ created_at      │   │        │ energie         │   │        │ places_disp.     │   │
└─────────────────┘   │        │ created_at      │   │        │ statut           │   │
                      │        └─────────────────┘   │        │ created_at       │   │
┌─────────────────┐   │                              │        └──────────────────┘   │
│   PARAMETRE     │   │        ┌─────────────────┐   │                               │
├─────────────────┤   │        │      AVIS       │   │        ┌──────────────────┐   │
│ parametre_id    │   │        ├─────────────────┤   │        │  PARTICIPATION   │   │
│ utilisateur_id  │───┘        │ avis_id         │   │        ├──────────────────┤   │
│ type            │            │ covoiturage_id  │───┘        │ participation_id │   │
│ valeur          │            │ evaluateur_id   │            │ covoiturage_id   │───┘
│ created_at      │            │ note            │            │ passager_id      │───┐
└─────────────────┘            │ commentaire     │            │ nombre_places    │   │
                               │ type            │            │ credit_utilise   │   │
┌─────────────────┐            │ created_at      │            │ statut           │   │
│  NOTIFICATION   │            └─────────────────┘            │ created_at       │   │
├─────────────────┤                                           └──────────────────┘   │
│ notification_id │                                                                  │
│ utilisateur_id  │──────────────────────────────────────────────────────────────────┘
│ type            │
│ titre           │
│ message         │
│ lu              │
│ created_at      │
└─────────────────┘
```

### 📊 **Description des entités**

#### **UTILISATEUR** (Entité centrale)
```sql
-- Gestion des comptes et authentification
utilisateur_id    : Clé primaire auto-incrémentée
pseudo           : Nom d'affichage unique
email            : Identifiant de connexion (unique)
password         : Hash bcrypt sécurisé
role             : ENUM('utilisateur', 'administrateur')
credit           : Solde de crédits (INT, défaut 20)
statut           : ENUM('actif', 'suspendu', 'inactif')
created_at       : Timestamp inscription
```

#### **VOITURE** (Véhicules des conducteurs)
```sql
-- Gestion du parc automobile
voiture_id       : Clé primaire
utilisateur_id   : FK vers UTILISATEUR (propriétaire)
marque           : Constructeur (VARCHAR 50)
modele           : Modèle du véhicule (VARCHAR 50)
immatriculation  : Plaque unique (VARCHAR 20)
places           : Nombre de places disponibles (INT)
couleur          : Couleur du véhicule (VARCHAR 30)
energie          : ENUM('electrique','hybride','essence','diesel')
created_at       : Date d'ajout
```

#### **COVOITURAGE** (Trajets proposés)
```sql
-- Trajets créés par les conducteurs
covoiturage_id      : Clé primaire
conducteur_id       : FK vers UTILISATEUR
voiture_id          : FK vers VOITURE
ville_depart        : Point de départ (VARCHAR 100)
ville_arrivee       : Destination (VARCHAR 100)
date_depart         : Date et heure du trajet
prix_par_place      : Coût en crédits (DECIMAL)
places_disponibles  : Places restantes (INT)
statut              : ENUM('planifie','en_cours','termine','annule')
created_at          : Date de création
```

#### **PARTICIPATION** (Réservations)
```sql
-- Liens passagers ↔ trajets
participation_id : Clé primaire
covoiturage_id   : FK vers COVOITURAGE
passager_id      : FK vers UTILISATEUR
nombre_places    : Places réservées (INT)
credit_utilise   : Crédits dépensés (DECIMAL)
statut           : ENUM('confirmee','en_cours','terminee','annulee')
created_at       : Date de réservation
```

### 🔗 **Relations et contraintes**

```sql
-- Relations principales
UTILISATEUR 1:n VOITURE         (Un utilisateur peut avoir plusieurs véhicules)
UTILISATEUR 1:n COVOITURAGE     (Un conducteur peut créer plusieurs trajets)
VOITURE 1:n COVOITURAGE         (Un véhicule peut servir à plusieurs trajets)
COVOITURAGE 1:n PARTICIPATION   (Un trajet peut avoir plusieurs passagers)
UTILISATEUR 1:n PARTICIPATION   (Un utilisateur peut réserver plusieurs trajets)

-- Contraintes d'intégrité
- FK avec CASCADE DELETE pour préserver la cohérence
- Contraintes CHECK sur les énumérations
- Index composites pour optimiser les recherches géographiques
- Contraintes UNIQUE sur email et immatriculation
```

---

## 4. DIAGRAMMES D'UTILISATION ET DE SÉQUENCE

### 👥 **Diagramme de cas d'utilisation**

```
                                Système EcoRide
    ┌─────────────────────────────────────────────────────────────────────┐
    │                                                                     │
    │  ┌─────────────────┐    ┌─────────────────┐    ┌────────────────┐  │
    │  │   S'inscrire    │    │  Se connecter   │    │  Consulter     │  │
    │  │                 │    │                 │    │  trajets       │  │
    │  └─────────────────┘    └─────────────────┘    └────────────────┘  │
    │           │                       │                       │         │
    │  ┌─────────────────┐    ┌─────────────────┐    ┌────────────────┐  │
    │  │   Rechercher    │    │    Réserver     │    │   Créer un     │  │
    │  │    trajets      │    │    trajet       │    │    trajet      │  │
    │  └─────────────────┘    └─────────────────┘    └────────────────┘  │
    │           │                       │                       │         │
    │  ┌─────────────────┐    ┌─────────────────┐    ┌────────────────┐  │
    │  │   Gérer ses     │    │    Evaluer      │    │   Gérer ses    │  │
    │  │   véhicules     │    │    trajets      │    │   réservations │  │
    │  └─────────────────┘    └─────────────────┘    └────────────────┘  │
    │                                                                     │
    └─────────────────────────────────────────────────────────────────────┘
              │                                                │
    ┌─────────────────┐                              ┌─────────────────┐
    │                 │                              │                 │
    │   UTILISATEUR   │                              │ ADMINISTRATEUR  │
    │   (Passager/    │                              │                 │
    │   Conducteur)   │                              │                 │
    │                 │                              │                 │
    └─────────────────┘                              └─────────────────┘
              │                                                │
              │    ┌─────────────────────────────────────────────────────┐
              │    │              Cas d'usage Admin                     │
              │    │  ┌────────────────┐    ┌─────────────────────────┐ │
              │    │  │   Consulter    │    │      Gérer les          │ │
              │    │  │  statistiques  │    │     utilisateurs        │ │
              │    │  └────────────────┘    └─────────────────────────┘ │
              │    │  ┌────────────────┐    ┌─────────────────────────┐ │
              │    │  │   Modérer les  │    │    Gérer la             │ │
              │    │  │    trajets     │    │   plateforme            │ │
              │    │  └────────────────┘    └─────────────────────────┘ │
              │    └─────────────────────────────────────────────────────┘
              │                            │
              └────────────────────────────┘
```

### 🔄 **Diagramme de séquence : Réservation d'un trajet**

```
Utilisateur    Interface    API Reserve    Database    Conducteur
    │              │             │            │            │
    │──Recherche───►│             │            │            │
    │              │──GET────────►│            │            │
    │              │             │──SELECT────►│            │
    │              │             │◄──Results──│            │
    │              │◄──Trajets───│            │            │
    │◄──Affichage──│             │            │            │
    │              │             │            │            │
    │──Réserver────►│             │            │            │
    │              │──POST───────►│            │            │
    │              │             │──BEGIN─────►│            │
    │              │             │            │            │
    │              │             │──UPDATE────►│ (Crédits)  │
    │              │             │◄──OK───────│            │
    │              │             │            │            │
    │              │             │──INSERT────►│ (Participation)
    │              │             │◄──OK───────│            │
    │              │             │            │            │
    │              │             │──UPDATE────►│ (Places)   │
    │              │             │◄──OK───────│            │
    │              │             │            │            │
    │              │             │──COMMIT────►│            │
    │              │             │◄──OK───────│            │
    │              │             │            │            │
    │              │             │──INSERT────►│ (Notification)
    │              │             │◄──OK───────│            │
    │              │             │            │            │
    │              │◄──Success───│            │            │
    │◄──Confirmation│            │            │            │
    │              │             │            │            │
    │              │             │            │──Notify────►│
    │              │             │            │            │◄──Email/SMS
```

### 🔄 **Diagramme de séquence : Création d'un trajet**

```
Conducteur   Interface   API Trajet   Database   Validation
    │            │           │           │           │
    │──Nouveau────►│           │           │           │
    │            │──GET──────►│           │           │
    │            │           │──SELECT───►│ (Véhicules)
    │            │           │◄──Data────│           │
    │            │◄──Form────│           │           │
    │◄──Affiche──│           │           │           │
    │            │           │           │           │
    │──Saisie────►│           │           │           │
    │            │──POST─────►│           │           │
    │            │           │──Validate─►│           │
    │            │           │           │◄──Rules───│
    │            │           │◄──OK──────│           │
    │            │           │           │           │
    │            │           │──INSERT───►│ (Covoiturage)
    │            │           │◄──ID──────│           │
    │            │           │           │           │
    │            │           │──INSERT───►│ (Paramètres)
    │            │           │◄──OK──────│           │
    │            │           │           │           │
    │            │◄──Success─│           │           │
    │◄──Confirm──│           │           │           │
```

---

## 5. DOCUMENTATION DU DÉPLOIEMENT

### 🚀 **Stratégie de déploiement**

#### **Choix de Render**
```
✅ Avantages techniques :
- Git-based deployment : Push automatique
- Variables d'environnement sécurisées
- Base MySQL managée (pas de configuration)
- HTTPS automatique avec certificats
- Monitoring et logs intégrés
- Rollback facile en cas de problème

✅ Avantages économiques :
- Tier gratuit généreux pour développement
- Scaling automatique
- Pas de serveur à maintenir
```

### 📋 **Étapes du déploiement**

#### **1. Préparation du code**
```bash
# Configuration adaptative pour multi-environnements
class Database {
    public function __construct() {
        // Détection automatique Render vs Local
        if (getenv('RAILWAY_ENVIRONMENT')) {
            // Configuration Render automatique
            $this->host = $_ENV['MYSQLHOST'];
            $this->dbname = $_ENV['MYSQL_DATABASE'];
            // ...
        } else {
            // Fallback local
            $this->host = 'localhost';
            $this->dbname = 'ecoride_db';
            // ...
        }
    }
}
```

#### **2. Configuration Render**
```yaml
# Connexion GitHub automatique
Repository: github.com/Isydoria/EcoRide
Branch: main
Build Command: (automatique pour PHP)
Start Command: (pas nécessaire pour PHP)

# Variables d'environnement (auto-configurées)
MYSQLHOST: containers-us-west-xxx.render.app
MYSQL_DATABASE: render
MYSQLUSER: root
MYSQLPASSWORD: [généré automatiquement]
MYSQL_URL: mysql://root:pass@host:port/render
```

#### **3. Déploiement automatique**
```bash
# Workflow de déploiement
git add .
git commit -m "feat: nouvelle fonctionnalité"
git push origin main

# Render détecte automatiquement :
1. Nouveau commit sur main
2. Lance le build (copie des fichiers PHP)
3. Redémarre l'application
4. Synchronise la base de données si nécessaire
5. Met à jour l'URL publique
6. Notification de succès/erreur
```

#### **4. Configuration base de données**
```sql
-- Import automatique lors du premier déploiement
-- Render détecte les fichiers .sql et les exécute

1. database/schema.sql  → Structure des tables
2. database/seed.sql    → Données de test
3. Scripts de migration → Mises à jour ultérieures

-- Stratégies de mise à jour :
- Migrations versionnées
- Sauvegarde automatique avant changement
- Rollback possible en cas d'erreur
```
## 🗄️ BASE DE DONNÉES NoSQL - MONGODB

### Exigence RNCP
L'énoncé requiert l'utilisation d'une base de données **relationnelle ET non relationnelle**.

### Solution implémentée : mongodb_fake.php

**Contexte technique :**
- PHP 8.3.14 n'a pas l'extension MongoDB native disponible facilement
- Solution alternative : implémentation légère compatible MongoDB

**Fonctionnalités :**
- Stockage fichier JSON dans `mongodb_data/`
- API compatible MongoDB : `insertOne()`, `find()`, `aggregate()`
- Collections : `activity_logs`, `search_history`, `performance_metrics`

**Cas d'usage dans EcoRide :**
1. **Logs d'activité utilisateur** : Connexions, actions importantes
2. **Historique des recherches** : Trajets recherchés par les utilisateurs
3. **Métriques de performance** : Temps de réponse des pages

**Avantages de cette approche :**
- ✅ Répond à l'exigence RNCP (base NoSQL)
- ✅ Fonctionnel sans configuration serveur complexe
- ✅ API similaire à MongoDB réel
- ✅ Facilement testable avec `/test-mongodb-simple.php`
- ✅ Peut être remplacé par vrai MongoDB en production

**Démonstration :**
URL : `/test-mongodb-simple.php`

### 🔍 **Monitoring et maintenance**

#### **Surveillance Render**
```yaml
Métriques surveillées:
- Temps de réponse moyen
- Nombre de requêtes par minute
- Utilisation CPU/RAM
- Erreurs HTTP (4xx, 5xx)
- Disponibilité (uptime)

Alertes configurées:
- Temps de réponse > 2s
- Taux d'erreur > 5%
- Indisponibilité > 1 minute
```

#### **Logs et debugging**
```php
// Logs d'erreur centralisés
error_log("EcoRide - Erreur : " . $message, 3, "/logs/app.log");

// Debug conditionnel (seulement en développement)
if (getenv('RAILWAY_ENVIRONMENT') !== 'production') {
    var_dump($debug_data);
}
```

### 🔄 **Processus de mise à jour**

```bash
# 1. Développement local
git checkout -b feature/nouvelle-fonctionnalite
# ... développement ...
git add .
git commit -m "feat: ajout fonctionnalité X"

# 2. Test local
php -S localhost:8000  # Test serveur intégré
# ... tests manuels ...

# 3. Déploiement en production
git checkout main
git merge feature/nouvelle-fonctionnalite
git push origin main

# 4. Render déploie automatiquement
# - Build : ~30 secondes
# - Restart : ~10 secondes
# - Total : ~1 minute
```

### 🛡️ **Sécurité du déploiement**

#### **Variables d'environnement**
```bash
# Jamais committées dans Git
MYSQL_PASSWORD=xxx           # Généré par Render
SESSION_SECRET=xxx           # Clé de chiffrement sessions
API_KEY_EXTERNAL=xxx         # Clés services externes (future)

# Configuration sécurisée
render variables set KEY=value
```

#### **HTTPS et certificats**
```
✅ Render configure automatiquement :
- Certificat SSL Let's Encrypt
- Redirection HTTP → HTTPS
- Headers de sécurité (HSTS, etc.)
- Protection contre attaques communes
```

---

## 6. ARCHITECTURE TECHNIQUE DÉTAILLÉE

### 🏗️ **Patterns architecturaux**

#### **MVC Adapté**
```php
// Modèle : Classes d'accès aux données
class CovoiturageModel {
    public function findByRoute($depart, $arrivee) {
        $sql = "SELECT * FROM covoiturage WHERE ville_depart = ? AND ville_arrivee = ?";
        return $this->db->prepare($sql)->execute([$depart, $arrivee]);
    }
}

// Vue : Templates PHP avec séparation logique/présentation
// Contrôleur : API endpoints avec validation
class TrajetController {
    public function search() {
        $model = new CovoiturageModel();
        $results = $model->findByRoute($_GET['depart'], $_GET['arrivee']);
        echo json_encode(['success' => true, 'data' => $results]);
    }
}
```

#### **Repository Pattern**
```php
// Abstraction de l'accès aux données
interface UtilisateurRepository {
    public function findById($id);
    public function findByEmail($email);
    public function create($userData);
}

class MySQLUtilisateurRepository implements UtilisateurRepository {
    // Implémentation spécifique MySQL
}
```

#### **Singleton pour connexion DB**
```php
// Une seule instance de connexion par requête
class Database {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
}
```

### 🔐 **Couche de sécurité**

```php
// Middleware de sécurité
class SecurityMiddleware {
    public static function validateCSRF($token) {
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            exit('Unauthorized');
        }
    }
}
```

### ⭐ **Système d'avis et évaluation**

#### **Architecture du système d'avis**

Le système d'avis bidirectionnel permet aux conducteurs et passagers de s'évaluer mutuellement après un trajet terminé.

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUX SYSTÈME D'AVIS                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. Trajet terminé (statut = 'termine')                    │
│        ↓                                                    │
│  2. Section "Mes avis" → Trajets à évaluer                 │
│        ↓                                                    │
│  3. Utilisateur clique "Laisser un avis"                   │
│        ↓                                                    │
│  4. Modal s'ouvre avec notation interactive (1-5 étoiles)  │
│        ↓                                                    │
│  5. Validation : note + commentaire (10-500 caractères)    │
│        ↓                                                    │
│  6. POST /api/create-avis.php                              │
│        ↓                                                    │
│  7. Vérifications :                                        │
│     - Participation au trajet confirmée                     │
│     - Pas d'avis existant (évite doublons)                 │
│     - Pas d'auto-évaluation                                │
│     - Trajet effectivement terminé                         │
│        ↓                                                    │
│  8. Insertion en base de données                           │
│        ↓                                                    │
│  9. Affichage dans "Avis reçus" du destinataire           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### **Structure base de données : Table AVIS**

```sql
-- PostgreSQL (Production Render)
CREATE TABLE avis (
    avis_id SERIAL PRIMARY KEY,
    evaluateur_id INT NOT NULL,              -- Qui donne l'avis
    evalue_id INT NOT NULL,                  -- Qui reçoit l'avis
    covoiturage_id INT NOT NULL,             -- Trajet concerné
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluateur_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (evalue_id) REFERENCES utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id) ON DELETE CASCADE
);

-- MySQL (Développement local)
CREATE TABLE avis (
    avis_id INT AUTO_INCREMENT PRIMARY KEY,
    auteur_id INT NOT NULL,                  -- Qui donne l'avis
    destinataire_id INT NOT NULL,            -- Qui reçoit l'avis
    covoiturage_id INT NOT NULL,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    statut ENUM('publie', 'signale', 'masque') DEFAULT 'publie',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auteur_id) REFERENCES utilisateur(utilisateur_id),
    FOREIGN KEY (destinataire_id) REFERENCES utilisateur(utilisateur_id),
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id)
);
```

**Différences MySQL/PostgreSQL gérées automatiquement :**
- Colonnes : `evaluateur_id/evalue_id` (PostgreSQL) vs `auteur_id/destinataire_id` (MySQL)
- Timestamp : `created_at` (PostgreSQL) vs `date_creation` (MySQL)
- Contraintes : PostgreSQL CASCADE obligatoire, MySQL optionnel

#### **API Endpoints**

**1. POST /api/create-avis.php** - Création d'un avis

```php
// Paramètres requis (POST)
{
    "evalue_id": 15,              // ID utilisateur à évaluer
    "covoiturage_id": 42,         // ID du trajet
    "note": 5,                    // Note 1-5 étoiles
    "commentaire": "Excellent..."  // 10-500 caractères
}

// Validations effectuées
✅ Note entre 1 et 5
✅ Commentaire 10-500 caractères
✅ Utilisateur a participé au trajet
✅ Trajet terminé (statut = 'termine')
✅ Pas d'avis existant pour ce trajet/utilisateur
✅ Pas d'auto-évaluation (evaluateur ≠ évalué)
✅ Conducteur évalue passager OU passager évalue conducteur

// Réponse succès
{
    "success": true,
    "message": "Votre avis a été publié avec succès"
}

// Réponse erreur
{
    "success": false,
    "message": "Vous avez déjà laissé un avis pour ce trajet"
}
```

**2. GET /api/get-avis.php** - Récupération des avis reçus

```php
// Paramètres URL
?user_id=15&limit=10&offset=0

// Réponse JSON
{
    "success": true,
    "avis": [
        {
            "avis_id": 1,
            "note": 5,
            "commentaire": "Excellent conducteur, très ponctuel!",
            "date": "2025-10-15 14:30:00",
            "evaluateur": {
                "id": 12,
                "pseudo": "Sophie"
            },
            "trajet": {
                "id": 42,
                "depart": "Paris",
                "arrivee": "Lyon",
                "date": "2025-10-15"
            }
        }
    ],
    "stats": {
        "total": 15,
        "moyenne": 4.7
    },
    "pagination": {
        "limit": 10,
        "offset": 0,
        "has_more": true
    }
}
```

**3. GET /api/get-trips-to-rate.php** - Trajets à évaluer

```php
// Paramètres URL
?user_id=15

// Logique de récupération
- Trajets terminés où l'utilisateur était conducteur
  → Liste des passagers à évaluer (sans avis existant)

- Trajets terminés où l'utilisateur était passager
  → Conducteur à évaluer (sans avis existant)

// Réponse JSON
{
    "success": true,
    "trips": [
        {
            "covoiturage_id": 42,
            "ville_depart": "Paris",
            "ville_arrivee": "Lyon",
            "date_depart": "2025-10-15 08:00:00",
            "prix": 15.50,
            "other_user_id": 12,
            "other_user_pseudo": "Sophie",
            "is_conductor": false  // false = passager à évaluer
        }
    ],
    "count": 5
}
```

#### **Frontend : Interface utilisateur**

**Section Dashboard "⭐ Mes avis"** (user/dashboard.php)

```javascript
// Composants principaux

1. Navigation sidebar
   - Nouvel onglet "⭐ Mes avis" (ligne 1085)
   - Lien : ?section=avis

2. Section avis reçus
   - Badge statistiques (note moyenne)
   - Cartes d'avis avec étoiles
   - Information trajet associé
   - Date formatée en français

3. Section trajets à évaluer
   - Liste des trajets terminés sans avis
   - Bouton "Laisser un avis" par trajet
   - Distinction conducteur/passager

4. Modal interactif
   - 5 étoiles cliquables avec hover effect
   - Textarea commentaire (10-500 caractères)
   - Compteur de caractères temps réel
   - Validation avant soumission
   - Boutons Annuler/Publier
```

**CSS Animations** (lignes 762-1051)

```css
/* Cartes d'avis avec hover effect */
.avis-card {
    transition: transform 0.3s, box-shadow 0.3s;
}
.avis-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Modal avec animations */
.modal {
    animation: fadeIn 0.3s;
}
.modal-content {
    animation: slideIn 0.3s;
}

/* Étoiles interactives */
.stars-input .star:hover,
.stars-input .star.hover {
    transform: scale(1.2);
    transition: all 0.2s;
}
```

**JavaScript AJAX** (lignes 2018-2327)

```javascript
// Fonctions principales

loadReceivedAvis()
  → GET /api/get-avis.php?user_id=X
  → Affiche avis reçus + statistiques

loadTripsToRate()
  → GET /api/get-trips-to-rate.php?user_id=X
  → Affiche trajets à évaluer

openAvisModal(tripId, evaluateId, ...)
  → Ouvre modal avec infos trajet
  → Initialise étoiles et formulaire

handleStarClick(rating)
  → Gère sélection notation 1-5

submitAvis()
  → POST /api/create-avis.php
  → FormData avec note + commentaire
  → Recharge listes après succès

escapeHtml(text)
  → Protection XSS sur affichage
```

#### **Compatibilité MySQL/PostgreSQL**

**Stratégie de détection automatique :**

```php
// Détection du driver PDO
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$isPostgreSQL = ($driver === 'pgsql');

// Requêtes conditionnelles
if ($isPostgreSQL) {
    $sql = "SELECT ... FROM avis a
            JOIN utilisateur u ON a.evaluateur_id = u.utilisateur_id
            WHERE a.evalue_id = :user_id";
} else {
    $sql = "SELECT ... FROM avis a
            JOIN utilisateur u ON a.auteur_id = u.utilisateur_id
            WHERE a.destinataire_id = :user_id
            AND a.statut = 'publie'";
}
```

**Tableau des différences gérées :**

| Fonctionnalité | PostgreSQL | MySQL |
|---|---|---|
| Colonne évaluateur | `evaluateur_id` | `auteur_id` |
| Colonne évalué | `evalue_id` | `destinataire_id` |
| Colonne date | `created_at` | `date_creation` |
| Statut | (pas de colonne) | `statut ENUM` |
| Cascade DELETE | Obligatoire | Optionnel |
| Contraintes CHECK | Natif | MySQL 8.0+ |

#### **Scripts de migration**

**1. database/check_and_create_avis_table.sql**
```sql
-- Vérifier et créer la table avis si nécessaire (PostgreSQL)
CREATE TABLE IF NOT EXISTS avis (
    avis_id SERIAL PRIMARY KEY,
    evaluateur_id INT NOT NULL,
    evalue_id INT NOT NULL,
    ...
);
```

**2. database/migration_add_terminee_status.sql**
```sql
-- Ajouter le statut 'terminee' à la participation
ALTER TABLE participation
DROP CONSTRAINT IF EXISTS participation_statut_reservation_check;

ALTER TABLE participation
ADD CONSTRAINT participation_statut_reservation_check
CHECK (statut_reservation IN ('en_attente', 'confirmee', 'annulee', 'terminee'));
```

#### **Sécurité et validations**

**Validations côté serveur (create-avis.php) :**

```php
// 1. Authentification requise
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Non connecté']));
}

// 2. Validation des données
$errors = [];
if ($note < 1 || $note > 5) {
    $errors[] = 'Note invalide (1-5)';
}
if (strlen($commentaire) < 10 || strlen($commentaire) > 500) {
    $errors[] = 'Commentaire invalide (10-500 caractères)';
}

// 3. Vérification participation
$stmt = $pdo->prepare("
    SELECT * FROM participation p
    JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
    WHERE p.covoiturage_id = :trip_id
    AND (p.passager_id = :user_id OR c.conducteur_id = :user_id)
    AND c.statut = 'termine'
");

// 4. Vérification doublon
$stmt = $pdo->prepare("
    SELECT 1 FROM avis
    WHERE evaluateur_id = :evaluateur AND evalue_id = :evalue
    AND covoiturage_id = :trip
");

// 5. Protection XSS côté client
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
```

**Validations côté client (JavaScript) :**

```javascript
// Bouton désactivé tant que formulaire invalide
function updateSubmitButton() {
    const comment = document.getElementById('avisComment').value.trim();
    const submitBtn = document.getElementById('submitAvis');

    if (currentRating > 0 && comment.length >= 10) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

// Compteur caractères en temps réel
commentTextarea.addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('charCount').textContent = count;
    updateSubmitButton();
});
```

#### **Workflow complet utilisateur**

```
1. Utilisateur se connecte au dashboard
2. Clique sur "⭐ Mes avis" dans le menu
3. Voit deux sections :

   A. Avis reçus
      - Badge note moyenne (ex: 4.7 ⭐)
      - Liste des avis avec :
        * Pseudo de l'évaluateur
        * Étoiles (1-5)
        * Commentaire
        * Information trajet
        * Date

   B. Trajets à évaluer
      - Liste des trajets terminés sans avis
      - Pour chaque trajet :
        * Route (Départ → Arrivée)
        * Date
        * Autre utilisateur (conducteur ou passager)
        * Prix
        * Bouton "Laisser un avis"

4. Clique sur "Laisser un avis"
5. Modal s'ouvre :
   - Information trajet affiché
   - Sélectionne note (clic sur étoiles)
   - Hover sur étoiles → feedback visuel
   - Écrit commentaire
   - Compteur caractères mis à jour
   - Bouton "Publier" activé si valide

6. Clique "Publier l'avis"
7. AJAX POST vers create-avis.php
8. Succès :
   - Alert "✅ Avis publié"
   - Modal se ferme
   - Listes rechargées automatiquement
   - Trajet disparaît de "À évaluer"
   - Avis apparaît chez le destinataire

9. Erreur :
   - Alert "❌ Message d'erreur"
   - Modal reste ouvert
   - Utilisateur peut corriger
```

#### **Indicateurs de qualité**

**Métriques calculées :**
- Note moyenne par utilisateur (AVG sur tous les avis)
- Nombre total d'avis reçus (COUNT)
- Taux d'évaluation (avis donnés / trajets terminés)

**Affichage :**
```javascript
// Badge note moyenne avec gradient
<div class="avis-stats">
    <div class="avis-stats-number">4.7 ⭐</div>
    <div class="avis-stats-label">Note moyenne sur 15 avis</div>
</div>
```

**Calcul SQL (PostgreSQL) :**
```sql
SELECT
    COUNT(*) as total_avis,
    AVG(note) as note_moyenne
FROM avis
WHERE evalue_id = :user_id;
```

#### **Évolutions futures possibles**

```
📈 Améliorations envisageables :

1. Système de modération
   - Signalement d'avis abusifs
   - Validation manuelle par admin
   - Masquage d'avis problématiques

2. Réponses aux avis
   - Permettre à l'évalué de répondre
   - Thread de conversation

3. Badges et récompenses
   - Conducteur 5 étoiles
   - Passager exemplaire
   - Membre de confiance

4. Filtres et tri
   - Trier par note (meilleurs/pires)
   - Filtrer par rôle (conducteur/passager)
   - Recherche dans commentaires

5. Analytics
   - Évolution note moyenne dans le temps
   - Graphique distribution notes
   - Statistiques détaillées dashboard
```

---

### 📊 **Performances et optimisation**

#### **Requêtes optimisées**
```sql
-- Index composite pour recherches géographiques
CREATE INDEX idx_route ON covoiturage(ville_depart, ville_arrivee, date_depart);

-- Index partiel pour trajets futurs uniquement
CREATE INDEX idx_trajets_futurs ON covoiturage(date_depart)
WHERE date_depart > NOW() AND statut = 'planifie';
```

#### **Cache et sessions**
```php
// Session optimisée
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Cache applicatif simple
class Cache {
    private static $data = [];

    public static function get($key) {
        return self::$data[$key] ?? null;
    }

    public static function set($key, $value, $ttl = 300) {
        self::$data[$key] = ['value' => $value, 'expires' => time() + $ttl];
    }
}
```

---

**📅 Document créé :** 22 septembre 2025
**🔄 Version :** 1.0 - Évaluation RNCP
**📋 Conformité :** Toutes les exigences techniques respectées

---

*Cette documentation technique accompagne l'évaluation du projet EcoRide pour l'obtention du Titre Professionnel Développeur Web et Web Mobile.*