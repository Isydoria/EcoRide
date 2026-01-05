<?php
session_start();
header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG SESSION ===\n\n";

echo "Session ID: " . session_id() . "\n";
echo "Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DEFINI') . "\n";
echo "Session pseudo: " . (isset($_SESSION['pseudo']) ? $_SESSION['pseudo'] : 'NON DEFINI') . "\n";
echo "Session email: " . (isset($_SESSION['email']) ? $_SESSION['email'] : 'NON DEFINI') . "\n";

echo "\n=== TOUTES LES VARIABLES DE SESSION ===\n";
print_r($_SESSION);

echo "\n=== COOKIES ===\n";
print_r($_COOKIE);

echo "\n=== FIN DEBUG ===\n";
?>
