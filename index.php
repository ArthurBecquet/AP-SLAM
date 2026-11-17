<?php
// Connexion : identifiant / mot de passe, puis redirection vers l'écran du profil.
require __DIR__ . '/app/init.php';

if ($u = utilisateur()) {
    header('Location: ' . Auth::ACCUEIL[$u['profil']]);
    exit;
}

$erreur = null;
$identifiant = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = (string) ($_POST['identifiant'] ?? '');
    if (!csrfValide()) {
        $erreur = 'Session expirée, merci de réessayer.';
    } else {
        try {
            $auth = new Auth(bdd());
            $u = $auth->connecter($identifiant, (string) ($_POST['mot_de_passe'] ?? ''));
            if ($u === null) {
                $erreur = Auth::MESSAGE_ECHEC;
            } else {
                session_regenerate_id(true); // évite la fixation de session
                $_SESSION['utilisateur'] = $u;
                $auth->tracer($u['num_personnel'], 'CONNEXION', 'index.php', $_SERVER['REMOTE_ADDR'] ?? '');
                header('Location: ' . Auth::ACCUEIL[$u['profil']]);
                exit;
            }
        } catch (PDOException $e) {
            // Cause précise (sans jamais afficher le mot de passe) pour aider à régler app/config.local.php
            $m = $e->getMessage();
            $erreur = 'Base de données injoignable : ' . match (true) {
                str_contains($m, 'could not find driver') => "l'extension PHP pdo_mysql n'est pas installée sur le serveur.",
                str_contains($m, '[1045]') || str_contains($m, '[1698]')              => 'identifiant ou mot de passe MySQL refusé (app/config.local.php).',
                str_contains($m, '[1049]')                => "la base indiquée dans app/config.local.php n'existe pas.",
                str_contains($m, '[1044]')                => "le compte MySQL n'a pas le droit d'utiliser cette base.",
                str_contains($m, '[2002]')                => 'serveur MySQL introuvable (host dans app/config.local.php).',
                default                                   => 'erreur ' . $e->getCode() . '.',
            };
        }
    }
}

$info = isset($_GET['deconnecte']) ? 'Vous êtes déconnecté.'
      : (isset($_GET['expire']) ? 'Veuillez vous connecter pour accéder à cette page.' : null);
$titre = 'Connexion';
require __DIR__ . '/app/haut.php';
?>
<div class="logo"><strong>LPFH Clinique</strong><small>Pré-admission</small></div>
<h1>Connexion</h1>
<?php if ($info): ?><p class="alerte info"><?= e($info) ?></p><?php endif; ?>
<?php if ($erreur): ?><p class="alerte erreur" role="alert"><?= e($erreur) ?></p><?php endif; ?>

<form method="post" action="index.php">
    <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">
    <label for="identifiant">Identifiant</label>
    <input type="text" id="identifiant" name="identifiant" value="<?= e($identifiant) ?>" autocomplete="username" required>
    <label for="mot_de_passe">Mot de passe</label>
    <input type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="current-password" required>
    <button type="submit" class="bouton">Se connecter</button>
</form>
<?php require __DIR__ . '/app/bas.php';
