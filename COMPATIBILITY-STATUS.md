# État de la Compatibilité MySQL/PostgreSQL - EcoRide

**Date:** 15 octobre 2025
**Statut:** ✅ Compatibilité complète implémentée

## 📋 Résumé

Tous les fichiers critiques ont été mis à jour pour fonctionner automatiquement avec MySQL (local) et PostgreSQL (Render). Le système détecte automatiquement le type de base de données via `PDO::ATTR_DRIVER_NAME` et adapte les requêtes SQL en conséquence.

## ✅ Fichiers Corrigés

### 1. **index.php**
- ✅ Ajout de l'alias `c.prix as prix_par_place` pour PostgreSQL
- ✅ Gestion des noms de tables différents (vehicule vs voiture)
- ✅ Gestion des colonnes différentes

### 2. **api/participer-trajet.php** (Réservation de trajets)
- ✅ Suppression de la fonction `jsonResponse()` en double
- ✅ Utilisation de `require_once __DIR__ . '/../config/init.php'`
- ✅ Correction du nom de colonne: `date_reservation` (au lieu de `date_participation`)
- ✅ Calcul automatique du `credit_utilise` pour PostgreSQL
- ✅ Gestion des transactions avec verrouillage (FOR UPDATE)

### 3. **api/search-trajets.php** (Recherche de trajets)
- ✅ Fix de la clause HAVING pour PostgreSQL (ne peut pas utiliser d'alias d'agrégation)
- ✅ Conditions séparées: `$havingConditions` (MySQL) et `$havingConditionsPG` (PostgreSQL)
- ✅ Expression complète dans HAVING: `COALESCE(AVG(a.note), 0) >= :note_min`

### 4. **user/dashboard.php** (Dashboard utilisateur) - CRITICAL
- ✅ **Véhicules**: Détection automatique table `vehicule` (PG) vs `voiture` (MySQL)
- ✅ **Mes trajets**: Adaptation des colonnes `id_conducteur` (PG) vs `conducteur_id` (MySQL)
- ✅ **Mes réservations**: Calcul du `credit_utilise` pour PostgreSQL: `(c.prix * p.nombre_places)`
- ✅ **Historique**: Toutes les requêtes adaptées avec GROUP BY complet pour PostgreSQL
- ✅ **Statistiques**: Gestion de `credits` (PG) vs `credit` (MySQL)
- ✅ **Affichage HTML**: Utilisation de l'opérateur `??` pour les deux conventions de nommage

### 5. **api/get-trajet-detail.php**
- ✅ Requêtes séparées pour MySQL et PostgreSQL
- ✅ Gestion des avis avec les bonnes colonnes
- ✅ Vérification des réservations existantes

## 🔑 Mappings Principaux

### Tables
| MySQL | PostgreSQL |
|-------|-----------|
| `voiture` | `vehicule` |
| `covoiturage` | `covoiturage` ✅ (identique) |
| `participation` | `participation` ✅ (identique) |
| `utilisateur` | `utilisateur` ✅ (identique) |

### Colonnes importantes
| MySQL | PostgreSQL | Table |
|-------|-----------|-------|
| `conducteur_id` | `id_conducteur` | covoiturage |
| `voiture_id` | `id_vehicule` | covoiturage |
| `passager_id` | `id_passager` | participation |
| `prix_par_place` | `prix` | covoiturage |
| `credit` | `credits` | utilisateur |
| `energie` | `type_carburant` | vehicule/voiture |
| `created_at` | `date_inscription` | utilisateur |
| `created_at` | `date_ajout` | vehicule/voiture |
| **❌ N'existe pas** | `date_reservation` | participation |

### Colonnes calculées (PostgreSQL uniquement)
- **`credit_utilise`**: N'existe pas dans la table `participation` PostgreSQL
  - Calcul: `(c.prix * p.nombre_places) as credit_utilise`
  - Utilisé dans: dashboard.php, historique

## 🚨 Particularités PostgreSQL

1. **HAVING clause**: Ne peut pas utiliser d'alias d'agrégation
   ```sql
   -- ❌ MySQL OK, PostgreSQL KO
   HAVING note_moyenne >= :note_min

   -- ✅ MySQL OK, PostgreSQL OK
   HAVING COALESCE(AVG(a.note), 0) >= :note_min
   ```

2. **GROUP BY**: Doit inclure TOUTES les colonnes non-agrégées du SELECT
   ```sql
   -- ❌ MySQL OK (avec ONLY_FULL_GROUP_BY désactivé), PostgreSQL KO
   GROUP BY c.covoiturage_id

   -- ✅ Toujours OK
   GROUP BY c.covoiturage_id, c.ville_depart, c.ville_arrivee, ...
   ```

3. **Colonnes manquantes**: `credit_utilise` n'existe pas dans `participation`
   - Solution: Calculer dans chaque requête

## 🧪 Tests à Effectuer

### Sur Render (PostgreSQL)

1. **✅ Inscription/Connexion**
   - Créer un compte
   - Se connecter

2. **✅ Recherche de trajets**
   - Rechercher sans filtres
   - Rechercher avec filtres (note, prix, date)
   - Vérifier que les résultats s'affichent correctement

3. **⏳ Réservation de trajet** (À VÉRIFIER)
   - Réserver un trajet
   - **VÉRIFIER**: La réservation apparaît-elle dans "Mes réservations" ?
   - **VÉRIFIER**: Les crédits sont-ils correctement débités ?

4. **⏳ Dashboard utilisateur** (À VÉRIFIER)
   - Section "Vue d'ensemble"
   - Section "Mes trajets" (en tant que conducteur)
   - **Section "Mes réservations"** (en tant que passager) ← CRITICAL
   - Section "Historique complet"
   - Section "Mes véhicules"
   - Section "Mon profil"

5. **⏳ Création de trajet**
   - Ajouter un véhicule
   - Créer un trajet
   - Vérifier qu'il apparaît dans "Mes trajets"

## 📝 Fichiers de Diagnostic (À Supprimer)

Ces fichiers peuvent être supprimés une fois les tests validés:
- `check-participation-columns.php`
- `verify-trajets.php`
- `verify-schema.php`
- `init-demo-data.php` (si déjà exécuté)

## 🎯 Prochaines Étapes

1. **Tester la réservation sur Render**
   - Se connecter en tant qu'utilisateur
   - Réserver un trajet
   - Vérifier que la réservation apparaît dans le dashboard

2. **Vérifier les logs Render**
   - Si erreur, partager les logs PHP
   - Vérifier les erreurs SQL

3. **Nettoyer le code**
   - Supprimer les fichiers de diagnostic
   - Commit final avec message: "fix: Compatibilité complète MySQL/PostgreSQL"

## 💡 Pattern de Détection Automatique

Tous les fichiers utilisent maintenant ce pattern:

```php
// Détecter le type de base de données
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$isPostgreSQL = ($driver === 'pgsql');

// Adapter la requête
if ($isPostgreSQL) {
    $sql = "SELECT ... FROM vehicule WHERE id_conducteur = :id";
} else {
    $sql = "SELECT ... FROM voiture WHERE conducteur_id = :id";
}
```

## 📞 Support

Si des erreurs persistent, fournir:
1. Le message d'erreur exact
2. Les logs Render complets
3. La page où l'erreur se produit
4. Les étapes pour reproduire

---

**Dernière mise à jour:** 15 octobre 2025, 08:30
**Responsable:** Claude Agent
**Statut:** ✅ Prêt pour tests finaux
