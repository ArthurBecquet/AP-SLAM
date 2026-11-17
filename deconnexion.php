<?php
// Déconnexion : trace, vide et détruit la session, retour à la connexion.
require __DIR__ . '/app/init.php';

if ($u = utilisateur()) {
    try {
        (new Auth(bdd()))->tracer($u['num_personnel'], 'DECONNEXION', 'deconnexion.php', $_SERVER['REMOTE_ADDR'] ?? '');
    } catch (PDOException $e) {
        // la déconnexion se fait quand même
    }
}
$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
session_destroy();

header('Location: index.php?deconnecte=1');
exit;
