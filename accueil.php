<?php
// Accueil des profils Médecin, Département administratif et Informaticien (écrans développés au Sprint 4).
require __DIR__ . '/app/init.php';
$u = exigerProfil([Auth::MEDECIN, Auth::ADMINISTRATIF, Auth::INFORMATICIEN]);

$ecrans = [
    Auth::MEDECIN       => ['Espace médecin', 'Pré-admissions à compléter (volet médical) — développé au sprint suivant.'],
    Auth::ADMINISTRATIF => ['Département administratif', 'Pré-admissions à valider — développé au sprint suivant.'],
    Auth::INFORMATICIEN => ['Espace informaticien', 'Comptes et habilitations — développé au sprint suivant.'],
];
[$titre, $aVenir] = $ecrans[$u['profil']];
$actif = 'accueil.php';
require __DIR__ . '/app/haut.php';
?>
<h1><?= e($titre) ?></h1>
<p class="sous-titre">Bienvenue <?= e($u['prenom']) ?>. Vous êtes connecté avec le profil « <?= e($u['profil']) ?> ».</p>
<div class="bandeau"><?= e($aVenir) ?></div>
<?php require __DIR__ . '/app/bas.php';
