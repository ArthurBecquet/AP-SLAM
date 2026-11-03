<?php
// Écran de connexion (identifiant / mot de passe)
require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Session;

Session::demarrer();

// Déjà connecté → écran du profil
if ($u = Session::utilisateur()) {
    header('Location: ' . url(Auth::accueilDe($u)));
    exit;
}

$erreur = null;
$identifiant = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = (string) ($_POST['identifiant'] ?? '');
    $motDePasse  = (string) ($_POST['mot_de_passe'] ?? '');

    if (!Session::verifierCsrf($_POST['csrf'] ?? null)) {
        $erreur = 'Session expirée, merci de réessayer.';
    } else {
        $auth = new Auth(Database::get());
        $utilisateur = $auth->connecter($identifiant, $motDePasse);

        if ($utilisateur === null) {
            $erreur = Auth::MESSAGE_ECHEC; // message générique, sans préciser le champ en cause
        } else {
            Session::ouvrir($utilisateur);
            $auth->tracer($utilisateur['num_personnel'], 'CONNEXION', '/index.php', $_SERVER['REMOTE_ADDR'] ?? '');
            header('Location: ' . url(Auth::accueilDe($utilisateur)));
            exit;
        }
    }
}
$info = isset($_GET['deconnecte']) ? 'Vous êtes déconnecté.' : (isset($_GET['expire']) ? 'Veuillez vous connecter pour accéder à cette page.' : null);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion – LPFH Pré-admission</title>
    <link rel="stylesheet" href="<?= e(url('/css/style.css')) ?>">
</head>
<body class="page-connexion">
<main class="carte carte-connexion">
    <div class="logo"><span class="pastille"></span><div><strong>LPFH Clinique</strong><small>Pré-admission</small></div></div>
    <h1>Connexion</h1>

    <?php if ($info): ?><p class="alerte info"><?= e($info) ?></p><?php endif; ?>
    <?php if ($erreur): ?><p class="alerte erreur" role="alert"><?= e($erreur) ?></p><?php endif; ?>

    <form method="post" action="<?= e(url('/index.php')) ?>" novalidate>
        <input type="hidden" name="csrf" value="<?= e(Session::jetonCsrf()) ?>">
        <label for="identifiant">Identifiant</label>
        <input type="text" id="identifiant" name="identifiant" value="<?= e($identifiant) ?>" autocomplete="username" required>

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="current-password" required>

        <button type="submit" class="bouton">Se connecter</button>
    </form>
</main>
</body>
</html>
