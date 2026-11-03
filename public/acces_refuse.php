<?php
// Affiché (HTTP 403) quand un utilisateur connecté n'a pas le bon profil.
require_once __DIR__ . '/../src/bootstrap.php';
if (!headers_sent()) {
    http_response_code(403);
}
$u = App\Session::utilisateur();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Accès refusé – LPFH</title><link rel="stylesheet" href="<?= e(url('/css/style.css')) ?>"></head>
<body class="page-connexion">
<main class="carte carte-connexion">
    <h1>Accès refusé</h1>
    <p class="alerte erreur">Votre profil ne permet pas d'accéder à cette page.</p>
    <?php if ($u): ?>
        <a class="bouton" href="<?= e(url(App\Auth::accueilDe($u))) ?>">Retour à mon accueil</a>
    <?php else: ?>
        <a class="bouton" href="<?= e(url('/index.php')) ?>">Se connecter</a>
    <?php endif; ?>
</main>
</body>
</html>
