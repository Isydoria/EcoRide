<?php
/**
 * Script de migration : Ajouter 'gpl' au type_vehicule
 */

$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("❌ DATABASE_URL non définie");
}

try {
    $parts = parse_url($dbUrl);
    $dsn = "pgsql:host={$parts['host']};port=" . ($parts['port'] ?? 5432) . ";dbname=" . ltrim($parts['path'], '/') . ";sslmode=require";
    $db = new PDO($dsn, $parts['user'], $parts['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✅ Connexion réussie<br><br>";

    // Modifier la contrainte pour ajouter 'gpl'
    $db->exec("
        ALTER TABLE voiture DROP CONSTRAINT IF EXISTS voiture_type_vehicule_check;
        ALTER TABLE voiture ADD CONSTRAINT voiture_type_vehicule_check
        CHECK (type_vehicule IN ('electrique', 'hybride', 'essence', 'diesel', 'gpl'));
    ");

    echo "✅ Type 'gpl' ajouté avec succès au type_vehicule !<br>";
    echo "<br>Vous pouvez maintenant exécuter init-demo-data.php<br><br>";

    // Vérifier la contrainte
    $result = $db->query("
        SELECT con.conname, pg_get_constraintdef(con.oid) as definition
        FROM pg_constraint con
        JOIN pg_class rel ON rel.oid = con.conrelid
        WHERE rel.relname = 'voiture' AND con.conname = 'voiture_type_vehicule_check'
    ")->fetch();

    echo "<h3>Contrainte actuelle :</h3>";
    echo "<pre>" . htmlspecialchars($result['definition']) . "</pre>";

} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>
