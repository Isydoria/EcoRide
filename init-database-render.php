<?php
/**
 * Script d'initialisation de la base de données PostgreSQL sur Render
 *
 * À exécuter UNE SEULE FOIS après le déploiement sur Render
 * URL : https://ecoride-om7c.onrender.com/init-database-render.php
 *
 * ⚠️ IMPORTANT : Supprimer ce fichier après exécution pour des raisons de sécurité
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Initialisation Base de Données Render</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c5282; }
        .success { color: #22863a; background: #dcffe4; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #d73a49; background: #ffdce0; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #b08800; background: #fffbdd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0366d6; background: #e1f5fe; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f6f8fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #2c5282; background: #f8f9fa; }
    </style>
</head>
<body>
<div class='container'>
<h1>🚀 Initialisation Base de Données PostgreSQL - Render.com</h1>";

// Vérifier qu'on est bien sur Render
if (!getenv('RENDER')) {
    echo "<div class='error'>❌ Ce script doit être exécuté UNIQUEMENT sur Render.com</div>";
    echo "<p>Environnement détecté : LOCAL</p>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='info'>✅ Environnement Render détecté</div>";

// Charger la configuration
require_once __DIR__ . '/config/init.php';

try {
    $pdo = db();
    echo "<div class='success'>✅ Connexion PostgreSQL établie</div>";

    // Étape 1 : Créer le schéma
    echo "<div class='step'><h2>📋 Étape 1 : Création du schéma (tables)</h2>";

    $schemaFile = __DIR__ . '/database/schema_postgresql.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Fichier schema_postgresql.sql introuvable");
    }

    $schema = file_get_contents($schemaFile);

    // Diviser en requêtes individuelles (séparées par ;)
    $queries = array_filter(array_map('trim', explode(';', $schema)));

    $successCount = 0;
    $errorCount = 0;

    foreach ($queries as $query) {
        if (empty($query) || substr(trim($query), 0, 2) === '--') {
            continue;
        }

        try {
            $pdo->exec($query);
            $successCount++;
        } catch (PDOException $e) {
            // Ignorer les erreurs "table already exists"
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "<div class='error'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
                $errorCount++;
            } else {
                echo "<div class='warning'>⚠️ Table déjà existante (ignoré)</div>";
            }
        }
    }

    echo "<p>✅ Schéma créé : $successCount requêtes exécutées";
    if ($errorCount > 0) {
        echo " ($errorCount erreurs)";
    }
    echo "</p></div>";

    // Étape 2 : Insérer les données
    echo "<div class='step'><h2>🌱 Étape 2 : Insertion des données (seed)</h2>";

    $seedFile = __DIR__ . '/database/seed_postgresql.sql';
    if (!file_exists($seedFile)) {
        throw new Exception("Fichier seed_postgresql.sql introuvable");
    }

    $seed = file_get_contents($seedFile);
    $queries = array_filter(array_map('trim', explode(';', $seed)));

    $successCount = 0;
    $errorCount = 0;

    foreach ($queries as $query) {
        if (empty($query) || substr(trim($query), 0, 2) === '--') {
            continue;
        }

        try {
            $pdo->exec($query);
            $successCount++;
        } catch (PDOException $e) {
            // Ignorer les erreurs de duplicate key
            if (strpos($e->getMessage(), 'duplicate key') === false &&
                strpos($e->getMessage(), 'already exists') === false) {
                echo "<div class='error'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
                $errorCount++;
            } else {
                echo "<div class='warning'>⚠️ Donnée déjà existante (ignoré)</div>";
            }
        }
    }

    echo "<p>✅ Données insérées : $successCount requêtes exécutées";
    if ($errorCount > 0) {
        echo " ($errorCount erreurs)";
    }
    echo "</p></div>";

    // Étape 3 : Vérification
    echo "<div class='step'><h2>🔍 Étape 3 : Vérification des données</h2>";

    $tables = [
        'utilisateur' => 'Utilisateurs',
        'vehicule' => 'Véhicules',
        'covoiturage' => 'Trajets',
        'participation' => 'Participations',
        'avis' => 'Avis',
        'paiement' => 'Paiements',
        'message' => 'Messages',
        'preference' => 'Préférences'
    ];

    echo "<table style='width:100%; border-collapse: collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th style='padding:10px; text-align:left;'>Table</th><th style='padding:10px; text-align:right;'>Nombre de lignes</th></tr>";

    foreach ($tables as $table => $label) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<tr><td style='padding:10px;'>$label ($table)</td><td style='padding:10px; text-align:right;'><strong>$count</strong></td></tr>";
        } catch (PDOException $e) {
            echo "<tr><td style='padding:10px;'>$label ($table)</td><td style='padding:10px; text-align:right; color:#d73a49;'>❌ Erreur</td></tr>";
        }
    }

    echo "</table></div>";

    // Étape 4 : Comptes de test
    echo "<div class='step'><h2>👥 Étape 4 : Comptes de test disponibles</h2>";

    $stmt = $pdo->query("SELECT pseudo, email, role, credits FROM utilisateur ORDER BY role, pseudo");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) > 0) {
        echo "<table style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='background:#f0f0f0;'><th style='padding:10px; text-align:left;'>Pseudo</th><th style='padding:10px; text-align:left;'>Email</th><th style='padding:10px; text-align:left;'>Rôle</th><th style='padding:10px; text-align:right;'>Crédits</th></tr>";

        foreach ($users as $user) {
            $roleColor = $user['role'] === 'administrateur' ? '#d73a49' : ($user['role'] === 'employe' ? '#0366d6' : '#22863a');
            echo "<tr>";
            echo "<td style='padding:10px;'>{$user['pseudo']}</td>";
            echo "<td style='padding:10px;'>{$user['email']}</td>";
            echo "<td style='padding:10px; color:$roleColor; font-weight:bold;'>{$user['role']}</td>";
            echo "<td style='padding:10px; text-align:right;'>{$user['credits']}</td>";
            echo "</tr>";
        }

        echo "</table>";

        echo "<div class='info' style='margin-top:20px;'>";
        echo "<strong>ℹ️ Mots de passe par défaut :</strong><br>";
        echo "• Administrateur : <code>Ec0R1de!</code><br>";
        echo "• Autres utilisateurs : <code>Test123!</code> ou <code>demo123</code>";
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ Aucun utilisateur trouvé</div>";
    }

    echo "</div>";

    // Message final
    echo "<div class='success'>";
    echo "<h2>✅ Initialisation terminée avec succès !</h2>";
    echo "<p><strong>Prochaines étapes :</strong></p>";
    echo "<ol>";
    echo "<li>Tester la connexion avec un compte admin : <a href='/connexion.php'>Se connecter</a></li>";
    echo "<li>Vérifier le dashboard admin : <a href='/admin/dashboard.php'>Dashboard Admin</a></li>";
    echo "<li>⚠️ <strong>IMPORTANT :</strong> Supprimer ce fichier <code>init-database-render.php</code> pour la sécurité</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Erreur d'initialisation</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
?>
