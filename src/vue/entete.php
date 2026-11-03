<?php
/**
 * Mise en page commune (barre latérale des maquettes).
 * Variables attendues : $utilisateur (array), $titre (string), $menu (array libellé => lien), $actif (lien actif)
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titre) ?> – LPFH</title>
    <link rel="stylesheet" href="<?= e(url('/css/style.css')) ?>">
</head>
<body>
<aside class="barre">
    <div class="logo"><span class="pastille"></span><div><strong>LPFH Clinique</strong><small>Pré-admission</small></div></div>
    <p class="rubrique"><?= e(mb_strtoupper($utilisateur['profil'])) ?></p>
    <nav>
        <?php foreach ($menu as $libelle => $lien): ?>
            <a href="<?= e(url($lien)) ?>" class="<?= $lien === ($actif ?? '') ? 'actif' : '' ?>"><?= e($libelle) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="profil">
        <strong><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></strong>
        <small>Profil : <?= e($utilisateur['profil']) ?></small>
        <a href="<?= e(url('/deconnexion.php')) ?>">Se déconnecter</a>
    </div>
</aside>
<main class="contenu">
