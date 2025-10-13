# 📖 MANUEL D'UTILISATION ECORIDE

**Plateforme de Covoiturage Écologique**

---

![EcoRide Logo](https://via.placeholder.com/200x80/2ECC71/FFFFFF?text=EcoRide)

**Version :** 1.0
**Date :** Septembre 2025
**Développeur :** Nathanaëlle
**Formation :** RNCP Développeur Web et Web Mobile - Studi

---

## 📋 TABLE DES MATIÈRES

1. [Présentation de l'application](#1-présentation-de-lapplication)
2. [Accès à l'application](#2-accès-à-lapplication)
3. [Comptes de démonstration](#3-comptes-de-démonstration)
4. [Parcours utilisateur standard](#4-parcours-utilisateur-standard)
5. [Parcours conducteur](#5-parcours-conducteur)
6. [Parcours administrateur](#6-parcours-administrateur)
7. [Fonctionnalités avancées](#7-fonctionnalités-avancées)
8. [Dépannage et FAQ](#8-dépannage-et-faq)

---

## 1. PRÉSENTATION DE L'APPLICATION

### 🌍 Concept

**EcoRide** est une plateforme de covoiturage nouvelle génération qui place l'écologie au cœur de ses préoccupations. Elle permet aux utilisateurs de partager leurs trajets tout en réduisant leur empreinte carbone.

### 🎯 Objectifs

- **🚗 Faciliter le covoiturage** entre particuliers
- **🌱 Promouvoir la mobilité écologique** avec mise en avant des véhicules électriques
- **💰 Proposer un système économique** basé sur les crédits
- **👥 Créer une communauté** de conducteurs et passagers responsables

### ✨ Fonctionnalités principales

- **Recherche intelligente** de trajets par ville et date
- **Création de trajets** pour les conducteurs
- **Système de crédits** pour les réservations (20 crédits offerts à l'inscription)
- **Gestion des véhicules** avec mise en avant des modèles écologiques
- **Interface d'administration** complète avec statistiques
- **Design responsive** adapté à tous les appareils

---

## 2. ACCÈS À L'APPLICATION

### 🚀 Version en ligne (Recommandée)

**URL principale :** https://ecoride-om7c.onrender.com

**Avantages :**
- ✅ Toujours accessible 24h/24
- ✅ Base PostgreSQL 15 professionnelle
- ✅ Données de démonstration complètes (34 trajets, 3 employés)
- ✅ Performance optimisée
- ✅ Sécurité HTTPS avec certificats Let's Encrypt

### 💻 Version locale (Développement)

**URL locale :** http://localhost/ecoride

**Prérequis :**
- Serveur WAMP/XAMPP démarré
- Base de données configurée
- Scripts d'initialisation exécutés

---

## 3. COMPTES DE DÉMONSTRATION

### 👨‍💼 **Administrateur**

**🔐 Accès complet à l'interface d'administration**

| Environnement | Email | Mot de passe | Accès |
|---------------|-------|--------------|-------|
| **En ligne & Local** | `admin@ecoride.fr` | `Ec0R1de!` | [Interface Admin](https://ecoride-om7c.onrender.com/admin/dashboard.php) |

**Fonctionnalités :**
- Statistiques générales de la plateforme (9 utilisateurs, 34 trajets)
- Gestion des utilisateurs (3 employés visibles)
- Monitoring des trajets et réservations
- Graphiques interactifs Chart.js

### 👔 **Employés (Modération)**

**🔐 Comptes employés pour gestion intermédiaire**

| Nom | Email | Mot de passe | Rôle |
|-----|-------|--------------|------|
| **Sophie Martin** | `sophie.martin@ecoride.fr` | `Sophie2025!` | Employé |
| **Lucas Dubois** | `lucas.dubois@ecoride.fr` | `Lucas2025!` | Employé |
| **Emma Bernard** | `emma.bernard@ecoride.fr` | `Emma2025!` | Employé |

### 👥 **Utilisateurs Standards**

**🔐 Comptes avec crédits pour tester les réservations**

| Nom | Email | Mot de passe | Crédits | Rôle |
|-----|-------|--------------|---------|------|
| **Jean Dupont** | `jean.dupont@ecoride.fr` | `Jean2025!` | 100 | Utilisateur |
| **Marie Martin** | `marie.martin@ecoride.fr` | `Marie2025!` | 75 | Utilisateur |
| **Paul Durand** | `paul.durand@ecoride.fr` | `Paul2025!` | 60 | Utilisateur |

**Fonctionnalités :**
- Recherche et réservation de trajets
- Création de trajets (conducteur)
- Gestion du profil et des véhicules
- Dashboard personnel

### 🆕 **Nouveau Compte**

**🎁 Inscription gratuite avec bonus**

**Avantages :**
- **20 crédits offerts** automatiquement
- Accès à toutes les fonctionnalités utilisateur
- Possibilité de créer des trajets
- Dashboard personnalisé

---

## 4. PARCOURS UTILISATEUR STANDARD

### 🎯 **Objectif :** Rechercher et réserver un trajet en tant que passager

### **Étape 1 : Initialisation des données de test**

1. **Accéder au script d'initialisation**
   ```
   https://ecoride-om7c.onrender.com/init-demo-data.php
   ```

2. **Vérifier la création des données**
   - ✅ 3 employés créés (Sophie, Lucas, Emma)
   - ✅ 8 véhicules variés (électrique, hybride, essence, diesel)
   - ✅ 34 trajets jusqu'à fin février 2026
   - ✅ Trajets multiples mêmes dates pour filtres :
     - Paris → Lyon : 3 trajets le 15/10/2025 (8h, 14h, 19h)
     - Marseille → Nice : 2 trajets le 18/10/2025
     - Toulouse → Bordeaux : 2 trajets le 25/10/2025
   - ✅ Participations et avis générés

### **Étape 2 : Connexion**

1. **Accéder à la page de connexion**
   ```
   https://ecoride-production-2631.up.render.app/connexion.php
   ```

2. **Se connecter avec un compte de test**
   - Email : `demo@ecoride.fr`
   - Mot de passe : `demo123`

3. **Vérifier la connexion réussie**
   - Redirection automatique vers le dashboard
   - Affichage du nom d'utilisateur en haut à droite
   - Crédits disponibles : 50

### **Étape 3 : Recherche de trajets**

1. **Accéder à la recherche**
   - Cliquer sur "🔍 Rechercher des trajets" dans le menu
   - Ou accéder directement : `/trajets.php`

2. **Effectuer une recherche**
   - **Ville de départ :** `Lyon`
   - **Ville d'arrivée :** `Marseille`
   - **Date :** Sélectionner demain
   - Cliquer sur "Rechercher"

3. **Analyser les résultats**
   - Liste des trajets disponibles
   - Informations conducteur et véhicule
   - Prix en crédits
   - Places disponibles
   - Indicateurs écologiques (🔋 pour électrique)

### **Étape 4 : Consultation des détails**

1. **Cliquer sur "Voir détail"** d'un trajet

2. **Examiner les informations complètes**
   - Profil du conducteur
   - Détails du véhicule (marque, modèle, couleur)
   - Horaires précis de départ
   - Préférences du conducteur (musique, animaux, etc.)
   - Avis d'autres passagers

### **Étape 5 : Réservation**

1. **Cliquer sur "Réserver ce trajet"**

2. **Confirmer la réservation**
   - Vérifier le nombre de places
   - Confirmer le coût en crédits
   - Valider la réservation

3. **Vérifier la confirmation**
   - Message de confirmation
   - Déduction des crédits automatique
   - Ajout dans "Mes réservations"

### **Étape 6 : Consultation du dashboard**

1. **Accéder au dashboard utilisateur**
   ```
   https://ecoride-production-2631.up.render.app/user/dashboard.php
   ```

2. **Vérifier les informations**
   - Solde de crédits mis à jour
   - Trajet réservé dans "Mes réservations"
   - Statistiques personnelles

---

## 5. PARCOURS CONDUCTEUR

### 🎯 **Objectif :** Créer et gérer des trajets en tant que conducteur

### **Étape 1 : Ajout d'un véhicule**

1. **Accéder à la section véhicules**
   - Dashboard → "🚙 Mes véhicules"

2. **Remplir le formulaire d'ajout**
   - **Marque :** Renault
   - **Modèle :** Zoe
   - **Immatriculation :** EV-123-FR
   - **Couleur :** Bleu
   - **Nombre de places :** 4
   - **Énergie :** Électrique

3. **Valider l'ajout**
   - Vérifier l'apparition du véhicule
   - Noter l'icône écologique pour les véhicules électriques

### **Étape 2 : Création d'un trajet**

1. **Accéder à la création de trajet**
   ```
   https://ecoride-production-2631.up.render.app/creer-trajet.php
   ```

2. **Remplir les informations du trajet**
   - **Ville de départ :** Nice
   - **Ville d'arrivée :** Monaco
   - **Date :** Choisir une date future
   - **Heure :** 14:00
   - **Places disponibles :** 3
   - **Prix par place :** 15 crédits
   - **Véhicule :** Sélectionner le véhicule ajouté

3. **Ajouter des préférences (optionnel)**
   - Musique autorisée : Oui
   - Animaux acceptés : Non
   - Discussion : Modérée
   - Arrêts en route : Possibles

4. **Valider la création**
   - Vérifier le message de confirmation
   - Consulter le trajet dans "Mes trajets"

### **Étape 3 : Gestion des trajets**

1. **Consulter ses trajets créés**
   - Dashboard → "🚗 Mes trajets"

2. **Vérifier les informations affichées**
   - Route (départ → arrivée)
   - Date et heure
   - Statut du trajet
   - Nombre de places restantes
   - Réservations reçues

---

## 6. PARCOURS ADMINISTRATEUR

### 🎯 **Objectif :** Superviser la plateforme et consulter les statistiques

### **Étape 1 : Connexion administrateur**

1. **Se connecter avec le compte admin**
   - Email : `admin@ecoride.fr`
   - Mot de passe : `Ec0R1de!` (en ligne) / `Test123!` (local)

2. **Accéder au dashboard admin**
   ```
   https://ecoride-production-2631.up.render.app/admin/dashboard.php
   ```

### **Étape 2 : Consultation des statistiques**

1. **Analyser la vue d'ensemble**
   - **Utilisateurs inscrits :** Nombre total
   - **Trajets créés :** Nombre de covoiturages
   - **Crédits totaux :** Économie de la plateforme
   - **Réservations :** Volume d'activité

2. **Examiner les graphiques**
   - **Graphique en camembert :** Répartition des trajets par statut
   - **Graphique linéaire :** Évolution des inscriptions

### **Étape 3 : Gestion des utilisateurs**

1. **Consulter la liste des utilisateurs**
   - Tableau des derniers inscrits
   - Informations : Pseudo, email, date d'inscription, statut

2. **Actions disponibles (simulation)**
   - Suspendre/Activer un utilisateur
   - Consulter le détail d'un profil
   - Modération des contenus

### **Étape 4 : Monitoring des trajets**

1. **Examiner la section trajets**
   - Liste des derniers trajets créés
   - Informations détaillées : Route, conducteur, véhicule, prix
   - Statuts des trajets avec codes couleur

2. **Analyser l'activité**
   - Trajets les plus populaires
   - Conducteurs les plus actifs
   - Performance de la plateforme

---

## 7. FONCTIONNALITÉS AVANCÉES

### 🔍 **Recherche Intelligente**

**Fonctionnalités :**
- Recherche par ville de départ et d'arrivée
- Filtrage par date
- Tri par prix, heure de départ, ou distance
- Mise en avant des véhicules écologiques

**Utilisation :**
1. Saisir les villes avec autocomplétion
2. Sélectionner la date dans le calendrier
3. Appliquer les filtres souhaités
4. Consulter les résultats classés

### 💰 **Système de Crédits**

**Principe :**
- Monnaie virtuelle de la plateforme
- 20 crédits offerts à l'inscription
- Utilisation pour réserver des places
- Gain de crédits en tant que conducteur

**Gestion :**
- Consultation du solde dans le dashboard
- Historique des transactions
- Recharge possible (fonctionnalité future)

### 🚗 **Écologie et Véhicules**

**Mise en avant environnementale :**
- 🔋 Icône spéciale pour véhicules électriques
- ⚡ Badge pour véhicules hybrides
- 🌱 Calcul de l'empreinte carbone réduite
- 📊 Statistiques écologiques

### 📱 **Interface Responsive**

**Adaptation multi-dispositifs :**
- 📱 Smartphones : Navigation tactile optimisée
- 📱 Tablettes : Interface intermédiaire
- 💻 Desktop : Vue complète avec sidebar
- 🖥️ Grands écrans : Utilisation maximale de l'espace

---

## 8. DÉPANNAGE ET FAQ

### ❓ **Questions Fréquentes**

**Q : Comment obtenir plus de crédits ?**
R : Actuellement, les crédits sont offerts à l'inscription (20) et gagnés en proposant des trajets. Un système de recharge est prévu.

**Q : Puis-je annuler une réservation ?**
R : La fonctionnalité d'annulation est en développement. Contactez l'administrateur pour l'instant.

**Q : Comment signaler un problème avec un conducteur ?**
R : Utilisez le système d'évaluation après le trajet ou contactez l'administration.

**Q : L'application fonctionne-t-elle sur mobile ?**
R : Oui, l'interface est entièrement responsive et s'adapte aux smartphones et tablettes.

### 🔧 **Dépannage Technique**

**Problème : Impossible de se connecter**
- Vérifier l'adresse email (format complet)
- Essayer avec un compte de test
- Vider le cache du navigateur

**Problème : Recherche sans résultats**
- Exécuter le script d'initialisation : `/init-trajets-demo.php`
- Vérifier l'orthographe des villes
- Essayer avec les trajets de test (Lyon → Marseille)

**Problème : Erreur lors de la réservation**
- Vérifier le solde de crédits suffisant
- S'assurer d'être connecté
- Recharger la page et réessayer

### 📞 **Support**

**Contact Développeur :**
- **Nom :** Nathanaëlle
- **Formation :** RNCP Développeur Web et Web Mobile
- **Email :** Via plateforme Studi
- **GitHub :** [Isydoria/EcoRide](https://github.com/Isydoria/EcoRide)

**Ressources :**
- **Documentation technique :** DOCUMENTATION_TECHNIQUE.md
- **Code source :** GitHub repository
- **Démo en ligne :** Render application

---

## 📊 **RÉSUMÉ DES PARCOURS DE TEST**

### ⚡ **Test Rapide (5 minutes)**

1. ✅ Accéder à : https://ecoride-production-2631.up.render.app
2. ✅ Initialiser : `/init-trajets-demo.php`
3. ✅ Rechercher : Lyon → Marseille
4. ✅ Admin : `admin@ecoride.fr` / `Ec0R1de!`

### 📋 **Test Complet (15 minutes)**

1. ✅ Inscription nouveau compte
2. ✅ Ajout véhicule et création trajet
3. ✅ Recherche et réservation
4. ✅ Dashboard utilisateur et admin
5. ✅ Vérification statistiques

---

**📅 Manuel créé le :** 22 septembre 2025
**🔄 Version :** 1.0
**🎓 Contexte :** Évaluation RNCP Développeur Web et Web Mobile
**🚀 Application :** https://ecoride-production-2631.up.render.app

---

*Ce manuel accompagne l'évaluation du projet EcoRide dans le cadre de l'obtention du Titre Professionnel Développeur Web et Web Mobile - Niveau 5 (Bac+2) reconnu par l'État.*