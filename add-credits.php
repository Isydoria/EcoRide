<?php
/**
 * add-credits.php
 * Script pour créer les comptes demo/admin et ajouter des crédits à tous les utilisateurs
 */

header('Content-Type: text/html; charset=utf-8');

$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("❌ DATABASE_URL non définie. Ce script est pour PostgreSQL/Render uniquement.");
}

try {
    $parts = parse_url($dbUrl);
    $dsn = "pgsql:host={$parts['host']};port=" . ($parts['port'] ?? 5432) . ";dbname=" . ltrim($parts['path'], '/') . ";sslmode=require";
    $db = new PDO($dsn, $parts['user'], $parts['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<h1>💰 Gestion des crédits et comptes utilisateurs</h1>";
    echo "<style>body { font-family: Arial; padding: 20px; } table { border-collapse: collapse; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; } th { background-color: #4CAF50; color: white; }</style>";

    // 1. Créer les comptes demo et admin s'ils n'existent pas
    echo "<h2>👥 Vérification des comptes demo et admin</h2>";

    $requiredAccounts = [
        [
            'pseudo' => 'demo',
            'email' => 'demo@ecoride.fr',
            'password' => 'Demo2025!',
            'role' => 'utilisateur',
            'credits' => 100
        ],
        [
            'pseudo' => 'admin',
            'email' => 'admin@ecoride.fr',
            'password' => 'Admin2025!',
            'role' => 'administrateur',
            'credits' => 200
        ]
    ];

    $stmt = $db->prepare("
        INSERT INTO utilisateur (pseudo, email, password, role, credits, is_active)
        VALUES (?, ?, ?, ?, ?, true)
        ON CONFLICT (email) DO UPDATE
        SET credits = EXCLUDED.credits
        RETURNING utilisateur_id
    ");

    foreach ($requiredAccounts as $account) {
        $stmt->execute([
            $account['pseudo'],
            $account['email'],
            password_hash($account['password'], PASSWORD_DEFAULT),
            $account['role'],
            $account['credits']
        ]);

        echo "✅ Compte <strong>{$account['pseudo']}</strong> ({$account['email']}) - {$account['credits']} crédits<br>";
    }

    echo "<br>";

    // 2. État actuel des utilisateurs
    $users = $db->query("SELECT utilisateur_id, pseudo, email, role, credits FROM utilisateur ORDER BY utilisateur_id")->fetchAll();

    echo "<h2>📊 État actuel (avant ajout) :</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Pseudo</th><th>Email</th><th>Rôle</th><th>Crédits</th></tr>";

    foreach ($users as $user) {
        $color = $user['credits'] < 50 ? 'orange' : 'black';
        echo "<tr>";
        echo "<td>{$user['utilisateur_id']}</td>";
        echo "<td>{$user['pseudo']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td style='color: {$color};'><strong>{$user['credits']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

    // 3. Ajouter 100 crédits à TOUS les utilisateurs (sauf admin et demo qui ont déjà assez)
    echo "<br><h2>💳 Ajout de 100 crédits à tous les utilisateurs...</h2>";

    $stmt = $db->prepare("
        UPDATE utilisateur
        SET credits = credits + 100
        WHERE email NOT IN ('demo@ecoride.fr', 'admin@ecoride.fr')
    ");
    $stmt->execute();
    $affected = $stmt->rowCount();

    echo "<p>✅ {$affected} utilisateurs ont reçu 100 crédits supplémentaires !</p>";

    // 4. Afficher le nouvel état
    $users = $db->query("SELECT utilisateur_id, pseudo, email, role, credits FROM utilisateur ORDER BY utilisateur_id")->fetchAll();

    echo "<h2>📊 Nouvel état (après ajout) :</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Pseudo</th><th>Email</th><th>Rôle</th><th>Crédits</th></tr>";

    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['utilisateur_id']}</td>";
        echo "<td>{$user['pseudo']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td><strong style='color: green;'>{$user['credits']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<br><h2>🎉 Terminé !</h2>";
    echo "<p>Résumé :</p>";
    echo "<ul>";
    echo "<li>✅ Comptes <strong>demo</strong> et <strong>admin</strong> créés/mis à jour</li>";
    echo "<li>✅ Crédits ajoutés à tous les utilisateurs</li>";
    echo "<li>📧 <strong>Identifiants :</strong></li>";
    echo "<ul>";
    echo "<li>Demo : demo@ecoride.fr / Demo2025!</li>";
    echo "<li>Admin : admin@ecoride.fr / Admin2025!</li>";
    echo "<li>Sophie : sophie.martin@ecoride.fr / Sophie2025!</li>";
    echo "<li>Lucas : lucas.dubois@ecoride.fr / Lucas2025!</li>";
    echo "<li>Emma : emma.bernard@ecoride.fr / Emma2025!</li>";
    echo "</ul>";
    echo "</ul>";
    echo "<p><a href='/'>⬅ Retour à l'accueil</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Erreur</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
