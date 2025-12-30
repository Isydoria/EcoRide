#!/usr/bin/env php
<?php
/**
 * Script d'initialisation de la base de données PostgreSQL
 * Exécuté automatiquement au démarrage du container Docker sur Render
 */

echo "==========================================\n";
echo "🗄️  INITIALISATION BASE DE DONNÉES\n";
echo "==========================================\n\n";

// Récupérer DATABASE_URL depuis les variables d'environnement
$database_url = getenv('DATABASE_URL');

if (!$database_url) {
    echo "⚠️  WARNING: DATABASE_URL non trouvée, initialisation ignorée\n";
    exit(0); // Ne pas bloquer le démarrage
}

echo "✅ DATABASE_URL détectée\n";

// Parser l'URL PostgreSQL
$db = parse_url($database_url);

if (!$db || !isset($db['host'])) {
    echo "❌ ERROR: DATABASE_URL invalide\n";
    exit(1);
}

$host = $db['host'];
$port = $db['port'] ?? 5432;
$dbname = ltrim($db['path'], '/');
$user = $db['user'];
$pass = $db['pass'];

echo "📡 Connexion à: $host:$port/$dbname\n";

try {
    // Connexion à PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Connexion établie\n\n";

    // Vérifier si la table 'utilisateur' existe
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'utilisateur'
        );
    ");

    $tableExists = $stmt->fetchColumn();

    if ($tableExists) {
        echo "✅ Base de données déjà initialisée\n";
        echo "   Tables existantes détectées\n\n";
        exit(0);
    }

    echo "🔨 Base de données vide détectée\n";
    echo "🚀 Création des tables...\n\n";

    // Lire le schéma PostgreSQL
    $schemaFile = '/var/www/html/database/schema_postgresql.sql';

    if (!file_exists($schemaFile)) {
        echo "❌ ERROR: Fichier schema_postgresql.sql introuvable\n";
        exit(1);
    }

    $schema = file_get_contents($schemaFile);

    // Exécuter le schéma SQL
    $pdo->exec($schema);

    echo "✅ Tables créées avec succès!\n\n";

    // Vérifier les tables créées
    $stmt = $pdo->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        ORDER BY table_name
    ");

    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "📊 Tables créées (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "   ✓ $table\n";
    }

    echo "\n✅ Base de données initialisée avec succès!\n";
    echo "==========================================\n\n";

} catch (PDOException $e) {
    echo "\n❌ ERREUR DE CONNEXION:\n";
    echo "   " . $e->getMessage() . "\n\n";
    echo "⚠️  Le container va démarrer quand même...\n";
    echo "   Vous devrez initialiser manuellement la base\n\n";
    exit(0); // Ne pas bloquer le démarrage d'Apache
} catch (Exception $e) {
    echo "\n❌ ERREUR:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(0);
}
?>
