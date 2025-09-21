# 🚗🌱 EcoRide - Plateforme de Covoiturage Écologique

> **Projet RNCP - Titre Professionnel Développeur Web et Web Mobile**
> Développé par Nathanaelle dans le cadre de l'ECF Studi (Sept 2025)

## 📋 Description du Projet

EcoRide est une plateforme de covoiturage innovante qui encourage les déplacements écologiques. L'application permet aux utilisateurs de :

- 🔍 **Rechercher des trajets** selon leur destination et date
- 🚗 **Proposer des covoiturages** en tant que conducteur
- 🌱 **Privilégier l'écologie** avec mise en avant des véhicules électriques
- 💰 **Gérer un système de crédits** pour les réservations
- ⭐ **Noter et évaluer** les conducteurs

## ✨ Fonctionnalités Implémentées

### 👥 **Gestion des Utilisateurs**
- [x] Inscription avec 20 crédits offerts
- [x] Connexion/Déconnexion sécurisée
- [x] Dashboard utilisateur
- [x] Gestion des profils

### 🔍 **Recherche et Réservation**
- [x] Recherche de trajets par ville et date
- [x] Affichage des résultats avec filtres
- [x] Vue détaillée des trajets
- [x] Système de réservation avec crédits
- [x] Indicateurs écologiques pour véhicules électriques

### 🚗 **Gestion des Trajets**
- [x] Affichage des détails conducteur
- [x] Informations véhicule (marque, modèle, énergie)
- [x] Système d'avis et notes
- [x] Préférences du conducteur

## 🛠 Technologies Utilisées

**Frontend :**
- HTML5 sémantique
- CSS3 avec design responsive
- JavaScript Vanilla (ES6+)
- Fetch API pour les appels AJAX

**Backend :**
- PHP 7.4+
- PDO pour la base de données
- Sessions sécurisées
- Architecture MVC adaptée

**Base de Données :**
- MySQL avec charset UTF8MB4
- Requêtes préparées (sécurité SQL injection)
- Relations optimisées

## 🚀 Installation et Configuration

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7+ ou MariaDB
- Serveur web (Apache/Nginx)
- Environnement WAMP/XAMPP pour le développement local

### Installation Locale

1. **Cloner le repository**
```bash
git clone https://github.com/Isydoria/EcoRide.git
cd EcoRide
```

2. **Configuration de la base de données**
```sql
-- Créer la base de données
CREATE DATABASE ecoride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Importer la structure
mysql -u root -p ecoride_db < database/schema.sql

-- Importer les données de test
mysql -u root -p ecoride_db < database/seed.sql
```

3. **Configuration PHP**
- Modifier `config/database.php` selon votre environnement
- Vérifier que PDO MySQL est activé

4. **Lancement**
- Démarrer votre serveur web
- Accéder à `http://localhost/ecoride`

## 👤 Comptes de Test

**Utilisateurs :**
- **Conducteur** : `jean.dupont` / `motdepasse123`
- **Passager** : `marie.martin` / `motdepasse123`
- **Nouveau** : Créer un compte (20 crédits offerts)

## 🎯 User Stories Implémentées

- ✅ **US1** : Page d'accueil avec présentation
- ✅ **US2** : Menu de navigation
- ✅ **US3** : Vue des covoiturages avec recherche
- ✅ **US5** : Vue détaillée d'un covoiturage
- ✅ **US6** : Participation aux trajets (partiel)
- ✅ **US7** : Création de compte

## 🔄 User Stories en Cours/Prévues

- 🔄 **US4** : Filtres avancés des covoiturages
- 🔄 **US8** : Espace utilisateur complet
- 🔄 **US9** : Saisie de voyage (conducteur)
- 📋 **US10-13** : Gestion avancée, employés, admin

## 📱 Responsive Design

L'application s'adapte à tous les écrans :
- 📱 Smartphones (iOS/Android)
- 📱 Tablettes
- 💻 Ordinateurs desktop

## 🌱 Engagement Écologique

- **Indicateur vert** pour les véhicules électriques
- **Promotion** des transports partagés
- **Interface** aux couleurs de la nature

## 📞 Contact

**Développeur** : Nathanaelle
**Formation** : RNCP Développeur Web et Web Mobile - Studi
**Date** : Septembre 2025

---

*Ce projet fait partie de l'Évaluation en Cours de Formation (ECF) pour l'obtention du Titre Professionnel Développeur Web et Web Mobile.*
