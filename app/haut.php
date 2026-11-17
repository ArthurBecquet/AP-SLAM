<?php
/**
 * Haut de page commun. Variables : $titre ; $actif (page du menu en surbrillance).
 * Connecté : barre latérale avec le menu du profil. Non connecté : page centrée (connexion).
 */
$u = utilisateur();
$menu = [];
if ($u !== null) {
    $menu = $u['profil'] === Auth::SECRETAIRE
        ? ['preadmissions.php' => 'Pré-admissions', 'nouvelle.php' => 'Nouvelle pré-admission']
        : ['accueil.php' => 'Accueil'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titre) ?> – LPFH Pré-admission</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= $u === null ? 'page-connexion' : '' ?>">
<?php if ($u !== null): ?>
<aside class="barre">
    <div class="logo"><strong>LPFH Clinique</strong><small>Pré-admission</small></div>
    <p class="rubrique"><?= e(mb_strtoupper($u['profil'])) ?></p>
    <nav>
        <?php foreach ($menu as $lien => $libelle): ?>
            <a href="<?= e($lien) ?>" class="<?= $lien === ($actif ?? '') ? 'actif' : '' ?>"><?= e($libelle) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="profil">
        <strong><?= e($u['prenom'] . ' ' . $u['nom']) ?></strong>
        <small>Profil : <?= e($u['profil']) ?></small>
        <a href="deconnexion.php">Se déconnecter</a>
    </div>
</aside>
<main class="contenu">
<?php else: ?>
<main class="carte carte-connexion">
<?php endif; ?>
