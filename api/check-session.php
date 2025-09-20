
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'session' => [
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_pseudo' => $_SESSION['user_pseudo'] ?? null,
            'user_email' => $_SESSION['user_email'] ?? null,
            'user_credits' => $_SESSION['user_credits'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null
        ]
    ]);
} else {
    echo json_encode([
        'logged_in' => false,
        'session' => $_SESSION
    ]);
}
?>
