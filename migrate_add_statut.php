<?php
/**
 * Migration: Ajout de la colonne statut à la table utilisateur (PostgreSQL)
 * À exécuter une seule fois sur Render
 */

$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("❌ DATABASE_URL non définie - Ce script est pour Render uniquement");
}

try {
    $parts = parse_url($dbUrl);
    $dsn = "pgsql:host={$parts['host']};port=" . ($parts['port'] ?? 5432) . ";dbname=" . ltrim($parts['path'], '/') . ";sslmode=require";
    $pdo = new PDO($dsn, $parts['user'], $parts['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migration statut</title>";
    echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}</style></head><body>";
    echo "<h1>🔧 Migration: Ajout colonne statut</h1>";

    echo "<h2>Étape 1: Vérification de l'existence de la colonne</h2>";

    // Vérifier si la colonne existe déjà
    $result = $pdo->query("
        SELECT column_name
        FROM information_schema.columns
        WHERE table_name = 'utilisateur' AND column_name = 'statut'
    ")->fetch();

    if ($result) {
        echo "<p>✅ La colonne 'statut' existe déjà</p>";
    } else {
        echo "<p>➕ Ajout de la colonne 'statut'...</p>";

        $pdo->exec("
            ALTER TABLE utilisateur
            ADD COLUMN statut VARCHAR(20) DEFAULT 'actif'
            CHECK (statut IN ('actif', 'suspendu'))
        ");

        echo "<p>✅ Colonne 'statut' ajoutée avec succès</p>";
    }

    echo "<h2>Étape 2: Mise à jour des utilisateurs existants</h2>";

    // Mettre tous les utilisateurs existants à 'actif'
    $stmt = $pdo->exec("UPDATE utilisateur SET statut = 'actif' WHERE statut IS NULL");
    echo "<p>✅ {$stmt} utilisateurs mis à jour</p>";

    echo "<h2>✅ Migration terminée avec succès !</h2>";
    echo "<p><a href='/'>← Retour à l'accueil</a></p>";

} catch (PDOException $e) {
    echo "<h2>❌ Erreur</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
