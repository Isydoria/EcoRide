
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'] ?? null,
            'pseudo' => $_SESSION['user_pseudo'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'credits' => $_SESSION['credits'] ?? $_SESSION['user_credits'] ?? null,
            'role' => $_SESSION['user_role'] ?? null
        ]
    ]);
} else {
    echo json_encode([
        'logged_in' => false,
        'message' => 'Session non active'
    ]);
}
?>
