<?php
// Déconnexion : destruction de la session puis retour à l'écran de connexion
require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Session;

$u = Session::utilisateur();
if ($u !== null) {
    (new Auth(Database::get()))->tracer($u['num_personnel'], 'DECONNEXION', '/deconnexion.php', $_SERVER['REMOTE_ADDR'] ?? '');
}
Session::detruire();
header('Location: ' . url('/index.php?deconnecte=1'));
exit;
