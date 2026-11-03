<?php
/**
 * Garde d'accès à inclure EN HAUT de chaque page protégée :
 *
 *   require __DIR__ . '/../../src/garde.php';
 *   $utilisateur = exigerProfil([App\Auth::SECRETAIRE]);
 *
 * Contrôle fait côté serveur : sans session → page de connexion ; mauvais profil → 403.
 */
require_once __DIR__ . '/bootstrap.php';

use App\Auth;
use App\Session;

function exigerProfil(array $profilsAutorises): array
{
    $utilisateur = Session::utilisateur();

    if ($utilisateur === null) {
        header('Location: ' . url('/index.php?expire=1'));
        exit;
    }
    if (!Auth::peutAcceder($utilisateur, $profilsAutorises)) {
        http_response_code(403);
        require __DIR__ . '/../public/acces_refuse.php';
        exit;
    }
    return $utilisateur;
}
