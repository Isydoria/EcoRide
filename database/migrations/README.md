# Migration : Ajout du système de modération des avis (PostgreSQL)

## 📋 Description

Cette migration ajoute les colonnes nécessaires pour le système de modération des avis dans la table `avis` de PostgreSQL, permettant aux employés de valider ou refuser les avis avant leur publication.

## 🎯 Objectif

Harmoniser le schéma PostgreSQL avec le schéma MySQL pour avoir le même système de modération des avis sur les deux bases de données.

## 📦 Colonnes ajoutées

| Colonne           | Type          | Description                                      |
|-------------------|---------------|--------------------------------------------------|
| `statut`          | VARCHAR(20)   | Statut de l'avis : 'en_attente', 'valide', 'refuse', 'publie' |
| `valide_par`      | INT (NULL)    | ID de l'employé/admin qui a validé l'avis       |
| `date_validation` | TIMESTAMP     | Date et heure de validation de l'avis           |

## 🚀 Application de la migration

### Prérequis
- Accès PostgreSQL avec droits d'exécution de scripts SQL
- Connexion à la base de données EcoRide

### Méthode 1 : Via psql (ligne de commande)

```bash
psql -U votre_utilisateur -d nom_base_ecoride -f database/migrations/add_avis_moderation_columns.sql
```

### Méthode 2 : Via pgAdmin ou interface web

1. Ouvrir pgAdmin ou votre interface PostgreSQL
2. Se connecter à la base de données EcoRide
3. Ouvrir l'éditeur de requêtes (Query Tool)
4. Copier-coller le contenu de `add_avis_moderation_columns.sql`
5. Exécuter le script

### Méthode 3 : Via PHP (pour déploiement automatique)

```php
<?php
require_once 'config/init.php';
$pdo = db();

$migration = file_get_contents(__DIR__ . '/database/migrations/add_avis_moderation_columns.sql');
$pdo->exec($migration);

echo "Migration appliquée avec succès !";
?>
```

## ✅ Vérification

Après l'application de la migration, vérifiez que :

1. Les colonnes ont été ajoutées :
```sql
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'avis'
AND column_name IN ('statut', 'valide_par', 'date_validation');
```

2. Les avis existants ont été marqués comme 'valide' :
```sql
SELECT statut, COUNT(*) as nombre
FROM avis
GROUP BY statut;
```

3. L'index a été créé :
```sql
SELECT indexname FROM pg_indexes WHERE tablename = 'avis';
```

## 🔄 Impact sur l'application

### Avant la migration
- ❌ **Dashboard employé** : Message "Modération automatique" (pas de liste d'avis)
- ✅ **Avis** : Tous publiés automatiquement sans modération

### Après la migration
- ✅ **Dashboard employé** : Interface complète de modération (approuver/refuser)
- ✅ **Avis** : Système de modération activé (statut 'en_attente' par défaut)
- ✅ **Statistiques** : Compteurs d'avis en attente/validés/refusés

## 📝 Notes importantes

1. **Avis existants** : Tous les avis déjà publiés seront automatiquement marqués comme 'valide'
2. **Nouveaux avis** : Auront le statut 'en_attente' et nécessiteront une validation par un employé
3. **Rétrocompatibilité** : Le code est compatible avec les deux schémas (avant et après migration)
4. **Rollback** : Pour annuler la migration, exécuter :
   ```sql
   ALTER TABLE avis
   DROP COLUMN IF EXISTS statut,
   DROP COLUMN IF EXISTS valide_par,
   DROP COLUMN IF EXISTS date_validation;

   DROP INDEX IF EXISTS idx_avis_statut;
   ```

## 🐛 Dépannage

### Erreur : "column already exists"
➡️ La migration a déjà été appliquée. Pas d'action nécessaire.

### Erreur : "permission denied"
➡️ Vérifiez que l'utilisateur PostgreSQL a les droits ALTER TABLE.

### Les avis n'apparaissent pas dans le dashboard
➡️ Vérifiez que les nouveaux avis ont bien `statut = 'en_attente'`

## 📅 Historique

- **2025-10-20** : Création de la migration pour harmoniser PostgreSQL et MySQL
- **Auteur** : Claude Code (Assistant IA)
- **Version** : 1.0

## 🔗 Fichiers liés

- `database/migrations/add_avis_moderation_columns.sql` - Script de migration
- `database/schema_postgresql.sql` - Schéma PostgreSQL mis à jour
- `employee/dashboard.php` - Interface employé de modération
- `api/create-avis.php` - API de création d'avis (utilise le statut)
