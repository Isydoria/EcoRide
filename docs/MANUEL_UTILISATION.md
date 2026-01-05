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
- ✅ Données de démonstration complètes (1 admin + 3 employés + 5 utilisateurs, 11 véhicules écologiques, 33 trajets)
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
- Statistiques générales de la plateforme (9 utilisateurs : 1 admin + 3 employés + 5 utilisateurs, 33 trajets)
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
| **Alice Bernard** | `alice.bernard@ecoride.fr` | `Alice2025!` | 80 | Utilisateur |
| **Thomas Petit** | `thomas.petit@ecoride.fr` | `Thomas2025!` | 90 | Utilisateur |

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

1. **Accéder au script d'initialisation selon votre environnement**

   **Production (Render - PostgreSQL) :**
   ```
   https://ecoride-om7c.onrender.com/init-demo-data.php
   ```

   **Local (WampServer - MySQL) :**
   ```
   http://localhost/ecoride/init-demo-data-local.php
   ```

2. **Vérifier la création des données**
   - ✅ 1 administrateur + 3 employés + 5 utilisateurs
   - ✅ 11 véhicules écologiques (4 électriques, 3 hybrides, 2 GPL, 1 essence, 1 diesel)
   - ✅ 33 trajets de janvier à février 2026
   - ✅ Trajets multiples mêmes dates pour tester les filtres :
     - Paris → Lyon : 3 trajets le 15/01/2026 (8h, 14h, 19h)
     - Marseille → Nice : 2 trajets le 18/01/2026
     - Toulouse → Bordeaux : 2 trajets le 25/01/2026
   - ✅ Participations et avis générés

### **Étape 2 : Connexion**

1. **Accéder à la page de connexion**
   ```
   https://ecoride-om7c.onrender.com/connexion.php
   ```

2. **Se connecter avec un compte de test**
   - Email : `jean.dupont@ecoride.fr`
   - Mot de passe : `Jean2025!`

3. **Vérifier la connexion réussie**
   - Redirection automatique vers le dashboard
   - Affichage du nom d'utilisateur en haut à droite
   - Crédits disponibles : 100

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
   https://ecoride-om7c.onrender.com/user/dashboard.php
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
   https://ecoride-om7c.onrender.com/creer-trajet.php
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
   - Mot de passe : `Ec0R1de!` (identique en ligne et en local)

2. **Accéder au dashboard admin**
   ```
   https://ecoride-om7c.onrender.com/admin/dashboard.php
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

### ⭐ **Système d'Avis et Évaluation**

**Fonctionnalité :** Évaluer les autres utilisateurs après un trajet terminé pour construire une communauté de confiance.

#### **Accéder à la section "Mes avis"**

1. **Se connecter au dashboard**
   ```
   https://ecoride-om7c.onrender.com/user/dashboard.php
   ```

2. **Cliquer sur "⭐ Mes avis"** dans le menu de navigation

3. **Découvrir les deux sections**
   - **Avis que j'ai reçus** : Consulter les évaluations reçues
   - **Trajets à évaluer** : Laisser un avis pour les trajets terminés

#### **Consulter ses avis reçus**

**Informations affichées :**
- **Badge note moyenne** avec gradient coloré (ex: 4.7 ⭐)
- **Total d'avis reçus** pour mesurer sa réputation
- **Liste détaillée des avis** avec :
  - Pseudo de l'évaluateur
  - Note sur 5 étoiles (★★★★★)
  - Commentaire détaillé
  - Information sur le trajet concerné
  - Date de l'évaluation

**Exemple d'affichage :**
```
┌──────────────────────────────────────────────┐
│  4.7 ⭐                                       │
│  Note moyenne sur 15 avis                    │
├──────────────────────────────────────────────┤
│  Sophie Martin                    15/10/2025 │
│  ★★★★★ (5/5)                                 │
│  "Excellent conducteur, très ponctuel!       │
│   Voiture propre et trajet agréable."        │
│  📍 Paris → Lyon (15/10/2025)                │
└──────────────────────────────────────────────┘
```

#### **Laisser un avis pour un trajet terminé**

**Étape 1 : Identifier les trajets à évaluer**

1. Dans la section "Trajets à évaluer"
2. Consulter la liste des trajets terminés sans avis
3. Pour chaque trajet, voir :
   - Route (Départ → Arrivée)
   - Date et heure du trajet
   - Nom de l'autre utilisateur (conducteur ou passager)
   - Prix payé en crédits
   - Bouton "⭐ Laisser un avis"

**Étape 2 : Ouvrir le formulaire d'évaluation**

1. **Cliquer sur "⭐ Laisser un avis"**
2. Un modal interactif s'ouvre avec :
   - Rappel de l'information du trajet
   - Système de notation par étoiles
   - Zone de commentaire

**Étape 3 : Noter l'utilisateur**

1. **Sélectionner une note de 1 à 5 étoiles**
   - Cliquer sur l'étoile correspondante
   - Effet visuel au survol (hover)
   - Transformation avec animation scale

2. **Signification des notes :**
   - ★☆☆☆☆ (1/5) : Très insatisfait
   - ★★☆☆☆ (2/5) : Insatisfait
   - ★★★☆☆ (3/5) : Correct
   - ★★★★☆ (4/5) : Satisfait
   - ★★★★★ (5/5) : Excellent !

**Étape 4 : Rédiger un commentaire**

1. **Écrire un commentaire détaillé** (minimum 10 caractères, maximum 500)
2. **Utiliser le compteur** en temps réel (ex: "125/500")
3. **Être constructif et respectueux**

**Exemples de bons commentaires :**
```
✅ "Conducteur très ponctuel et sympathique.
    Véhicule propre et confortable. Je recommande !"

✅ "Passagère agréable et respectueuse.
    Bonne conversation pendant le trajet."

✅ "Trajet fluide et sécuritaire.
    Bonne musique et ambiance détendue."
```

**Exemples à éviter :**
```
❌ "Bien" (trop court, minimum 10 caractères)
❌ Messages avec insultes ou propos déplacés
❌ Informations personnelles sensibles
```

**Étape 5 : Valider et publier**

1. **Vérifier que le formulaire est complet**
   - Note sélectionnée (étoiles colorées)
   - Commentaire valide (compteur vert)
   - Bouton "Publier l'avis" activé

2. **Cliquer sur "Publier l'avis"**
   - Message de confirmation : "✅ Votre avis a été publié avec succès"
   - Modal se ferme automatiquement
   - Listes rechargées en temps réel

3. **Vérifier la publication**
   - Le trajet disparaît de "Trajets à évaluer"
   - L'avis apparaît immédiatement dans la section "Avis reçus" du destinataire
   - Impossible de modifier ou supprimer (définitif)

#### **Règles et validations**

**Conditions pour laisser un avis :**
- ✅ Avoir participé au trajet (conducteur ou passager)
- ✅ Le trajet doit être terminé (statut = 'termine')
- ✅ Pas d'avis déjà laissé pour ce trajet/utilisateur
- ✅ Pas d'auto-évaluation (impossible de s'évaluer soi-même)

**Validations automatiques :**
- Note obligatoire entre 1 et 5 étoiles
- Commentaire entre 10 et 500 caractères
- Protection contre les doublons
- Vérification de la participation effective

**Sécurité :**
- Protection XSS (échappement HTML)
- Authentification requise
- Validation côté serveur et client
- Données stockées de manière sécurisée

#### **Utilisation stratégique des avis**

**Pour les passagers :**
1. **Consulter les avis des conducteurs** avant de réserver
2. **Privilégier les conducteurs bien notés** (4+ ⭐)
3. **Lire les commentaires** pour connaître le style de conduite
4. **Évaluer objectivement** après chaque trajet

**Pour les conducteurs :**
1. **Maintenir une note élevée** pour attirer plus de passagers
2. **Lire les avis reçus** pour s'améliorer
3. **Évaluer les passagers** pour construire une communauté fiable
4. **Répondre positivement** aux critiques constructives

**Impact sur la réputation :**
```
Note moyenne       Impact
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
5.0 ⭐            Excellent (top conducteur)
4.5 - 4.9 ⭐      Très bon (recommandé)
4.0 - 4.4 ⭐      Bon (fiable)
3.5 - 3.9 ⭐      Correct (acceptable)
< 3.5 ⭐          À améliorer
```

#### **Scénarios d'utilisation**

**Scénario 1 : Passager évalue un conducteur**
```
1. Sophie a voyagé avec Jean de Paris à Lyon
2. Le trajet s'est terminé le 15/10/2025
3. Sophie accède à "⭐ Mes avis"
4. Elle voit le trajet dans "Trajets à évaluer"
5. Elle clique "Laisser un avis"
6. Elle donne 5 étoiles et écrit :
   "Excellent conducteur, conduite sécuritaire"
7. Jean reçoit l'avis instantanément
8. Sa note moyenne passe de 4.6 à 4.7 ⭐
```

**Scénario 2 : Conducteur évalue un passager**
```
1. Jean (conducteur) a terminé son trajet
2. Il avait 2 passagers : Sophie et Marc
3. Il accède à "⭐ Mes avis"
4. Il voit 2 trajets à évaluer (1 par passager)
5. Il évalue Sophie : 5⭐ "Passagère ponctuelle"
6. Il évalue Marc : 4⭐ "Agréable mais un peu bavard"
7. Les deux passagers reçoivent leurs avis
```

**Scénario 3 : Consultation avant réservation**
```
1. Marie recherche un trajet Lyon → Marseille
2. Elle trouve 3 trajets disponibles
3. Elle consulte les profils des conducteurs :
   - Jean : 4.7⭐ (15 avis) ✅ Choisi
   - Paul : 3.2⭐ (8 avis) ❌ Évité
   - Luc : Nouveau (0 avis) ⚠️ Incertain
4. Marie réserve avec Jean grâce aux bons avis
```

#### **Statistiques et indicateurs**

**Métriques affichées :**
- **Note moyenne** : Moyenne de toutes les notes reçues (ex: 4.7/5)
- **Nombre total d'avis** : Crédibilité de la note (ex: 15 avis)
- **Distribution** : Répartition des notes (future fonctionnalité)
- **Taux d'évaluation** : Pourcentage de trajets évalués (admin)

**Badge de qualité (futur) :**
- 🏆 Conducteur Elite (5.0 ⭐ avec 20+ avis)
- ⭐ Membre de Confiance (4.5+ ⭐ avec 10+ avis)
- 🌟 Passager Exemplaire (4.8+ ⭐ comme passager)

#### **Bonnes pratiques**

**✅ À faire :**
- Évaluer rapidement après le trajet (mémoire fraîche)
- Être honnête et objectif
- Mentionner les points positifs ET les axes d'amélioration
- Utiliser un langage respectueux
- Détailler l'expérience (ponctualité, propreté, conduite, ambiance)

**❌ À éviter :**
- Laisser un avis sous le coup de l'émotion
- Écrire des commentaires trop courts ("Bien", "OK")
- Utiliser des insultes ou propos déplacés
- Divulguer des informations personnelles
- Laisser un mauvais avis pour se venger

#### **Dépannage**

**Problème : Le bouton "Laisser un avis" est grisé**
- Solution : Vérifier que vous avez sélectionné une note (étoiles)
- Solution : Vérifier que le commentaire contient au moins 10 caractères

**Problème : Message "Vous avez déjà laissé un avis pour ce trajet"**
- Explication : Impossible de laisser plusieurs avis pour le même trajet
- Les avis sont définitifs et ne peuvent pas être modifiés

**Problème : Le trajet n'apparaît pas dans "Trajets à évaluer"**
- Vérifier que le trajet est bien terminé (statut = 'termine')
- Vérifier que vous avez bien participé au trajet
- Actualiser la page (Ctrl+F5)

**Problème : Ma note moyenne ne s'affiche pas**
- Explication : Il faut avoir reçu au moins 1 avis
- Les nouveaux utilisateurs n'ont pas encore de note

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
- Exécuter le script d'initialisation : `/init-demo-data.php` (Render) ou `/init-demo-data-local.php` (Local)
- Vérifier l'orthographe des villes
- Essayer avec les trajets de test (Paris → Lyon le 15/01/2026)

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

1. ✅ Accéder à : https://ecoride-om7c.onrender.com
2. ✅ Initialiser : `/init-demo-data.php`
3. ✅ Rechercher : Paris → Lyon le 15/01/2026
4. ✅ Admin : `admin@ecoride.fr` / `Ec0R1de!`

### 📋 **Test Complet (15 minutes)**

1. ✅ Inscription nouveau compte
2. ✅ Ajout véhicule et création trajet
3. ✅ Recherche et réservation
4. ✅ Dashboard utilisateur et admin
5. ✅ Vérification statistiques

---

**📅 Manuel créé le :** 22 septembre 2025
**🔄 Version :** 1.1 - Mise à jour janvier 2026
**🎓 Contexte :** Évaluation RNCP Développeur Web et Web Mobile
**🚀 Application :** https://ecoride-om7c.onrender.com

---

*Ce manuel accompagne l'évaluation du projet EcoRide dans le cadre de l'obtention du Titre Professionnel Développeur Web et Web Mobile - Niveau 5 (Bac+2) reconnu par l'État.*