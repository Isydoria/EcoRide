<?php
/**
 * Script pour diagnostiquer la structure des fichiers Railway
 */

echo "<h2>🔍 Debug Structure Fichiers Railway</h2>";

echo "<h3>Répertoire courant :</h3>";
echo "<pre>" . getcwd() . "</pre>";

echo "<h3>__DIR__ :</h3>";
echo "<pre>" . __DIR__ . "</pre>";

echo "<h3>Contenu du répertoire racine (.) :</h3>";
echo "<pre>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo ($file . (is_dir($file) ? '/' : '')) . "\n";
    }
}
echo "</pre>";

echo "<h3>Contenu du répertoire config/ :</h3>";
echo "<pre>";
if (is_dir('config')) {
    $files = scandir('config');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "config/" . $file . (is_dir('config/' . $file) ? '/' : '') . "\n";
        }
    }
} else {
    echo "❌ Dossier config/ non trouvé";
}
echo "</pre>";

echo "<h3>Tests de chemins :</h3>";
echo "<pre>";
$paths_to_test = [
    './config/database.php',
    'config/database.php',
    __DIR__ . '/config/database.php',
    '/app/config/database.php'
];

foreach ($paths_to_test as $path) {
    echo "$path : " . (file_exists($path) ? "✅ EXISTE" : "❌ N'EXISTE PAS") . "\n";
}
echo "</pre>";

echo "<h3>Recherche de database.php :</h3>";
echo "<pre>";
function findFiles($dir, $filename, $depth = 0) {
    if ($depth > 3) return; // Limite la profondeur

    $files = @scandir($dir);
    if (!$files) return;

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = $dir . '/' . $file;
        if ($file === $filename) {
            echo "TROUVÉ: $fullPath\n";
        } elseif (is_dir($fullPath)) {
            findFiles($fullPath, $filename, $depth + 1);
        }
    }
}

findFiles('.', 'database.php');
echo "</pre>";
?>