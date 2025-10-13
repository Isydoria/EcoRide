<?php
/**
 * Script de diagnostic pour identifier le problème de connexion PostgreSQL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnostic Render</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        .ok { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        pre { background: #111; padding: 10px; border: 1px solid #333; }
        h2 { color: #0ff; margin-top: 30px; }
    </style>
</head>
<body>
<h1>🔍 Diagnostic PostgreSQL Render</h1>

<?php
echo "<h2>1. Environnement</h2>";
echo "<pre>";
echo "RENDER: " . (getenv('RENDER') ? '<span class="ok">✓ OUI</span>' : '<span class="error">✗ NON</span>') . "\n";
echo "DOCKER_ENV: " . (getenv('DOCKER_ENV') ? '<span class="ok">✓ ' . getenv('DOCKER_ENV') . '</span>' : '<span class="warning">Non défini</span>') . "\n";
echo "PHP Version: <span class='ok'>" . PHP_VERSION . "</span>\n";
echo "</pre>";

echo "<h2>2. Variables d'environnement PostgreSQL</h2>";
echo "<pre>";
$dbUrl = getenv('DATABASE_URL');
if ($dbUrl) {
    // Masquer le mot de passe pour la sécurité
    $masked = preg_replace('/\/\/([^:]+):([^@]+)@/', '//***:***@', $dbUrl);
    echo "DATABASE_URL: <span class='ok'>✓ Défini</span>\n";
    echo "Format: $masked\n";
} else {
    echo "DATABASE_URL: <span class='error'>✗ Non défini</span>\n";
}
echo "</pre>";

echo "<h2>3. Extensions PHP chargées</h2>";
echo "<pre>";
$extensions = get_loaded_extensions();
$required = ['pdo', 'pdo_pgsql', 'pgsql'];
foreach ($required as $ext) {
    $loaded = in_array($ext, $extensions);
    $status = $loaded ? '<span class="ok">✓ Chargé</span>' : '<span class="error">✗ Absent</span>';
    echo "$ext: $status\n";
}
echo "\nToutes les extensions: " . implode(', ', $extensions) . "\n";
echo "</pre>";

echo "<h2>4. Test connexion PostgreSQL directe</h2>";
echo "<pre>";
try {
    if (!$dbUrl) {
        throw new Exception("DATABASE_URL non défini");
    }

    // Parser l'URL
    $parsed = parse_url($dbUrl);
    echo "Host: " . ($parsed['host'] ?? 'N/A') . "\n";
    echo "Port: " . ($parsed['port'] ?? '5432') . "\n";
    echo "Database: " . ltrim($parsed['path'] ?? '', '/') . "\n";
    echo "User: " . ($parsed['user'] ?? 'N/A') . "\n";
    echo "\n";

    echo "Tentative de connexion PDO...\n";
    $pdo = new PDO($dbUrl);
    echo "<span class='ok'>✓ CONNEXION RÉUSSIE !</span>\n";

    // Test requête
    $version = $pdo->query('SELECT version()')->fetchColumn();
    echo "\nVersion PostgreSQL:\n$version\n";

    // Lister les tables
    $tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname='public'")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables existantes: " . count($tables) . "\n";
    if (count($tables) > 0) {
        echo implode(', ', $tables) . "\n";
    } else {
        echo "<span class='warning'>Aucune table (base vide - normal au premier déploiement)</span>\n";
    }

} catch (PDOException $e) {
    echo "<span class='error'>✗ ERREUR PDO: " . $e->getMessage() . "</span>\n";
    echo "\nCode erreur: " . $e->getCode() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "<span class='error'>✗ ERREUR: " . $e->getMessage() . "</span>\n";
}
echo "</pre>";

echo "<h2>5. Test chargement database_render.php</h2>";
echo "<pre>";
try {
    echo "Tentative de chargement...\n";

    if (file_exists(__DIR__ . '/config/database_render.php')) {
        echo "✓ Fichier existe\n";

        // Capturer les erreurs
        ob_start();
        require_once __DIR__ . '/config/database_render.php';
        $output = ob_get_clean();

        if ($output) {
            echo "Output capturé:\n$output\n";
        }

        echo "✓ Fichier chargé sans erreur PHP\n";

        // Tester la fonction db()
        if (function_exists('db')) {
            echo "✓ Fonction db() existe\n";
            try {
                $pdo = db();
                echo "<span class='ok'>✓ db() fonctionne !</span>\n";
            } catch (Exception $e) {
                echo "<span class='error'>✗ db() erreur: " . $e->getMessage() . "</span>\n";
            }
        } else {
            echo "<span class='error'>✗ Fonction db() n'existe pas</span>\n";
        }

    } else {
        echo "<span class='error'>✗ Fichier database_render.php introuvable</span>\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ ERREUR: " . $e->getMessage() . "</span>\n";
    echo "\n" . $e->getTraceAsString() . "\n";
}
echo "</pre>";

echo "<h2>6. Recommandation</h2>";
echo "<pre>";
if (in_array('pdo_pgsql', get_loaded_extensions())) {
    echo "<span class='ok'>✓ pdo_pgsql est chargé, connexion devrait fonctionner</span>\n";
    echo "\nSi la connexion échoue, vérifier:\n";
    echo "- DATABASE_URL dans les variables d'environnement Render\n";
    echo "- La base PostgreSQL est bien créée sur Render\n";
} else {
    echo "<span class='error'>✗ pdo_pgsql n'est PAS chargé !</span>\n";
    echo "\nSOLUTION : Vérifier le Dockerfile\n";
    echo "L'extension pdo_pgsql doit être installée dans le build Docker\n";
}
echo "</pre>";

?>
</body>
</html>
