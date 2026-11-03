<?php
// Écran d'accueil du profil « Espace informaticien » — accès réservé à ce profil (contrôle serveur)
require __DIR__ . '/../../src/garde.php';
$utilisateur = exigerProfil([App\Auth::INFORMATICIEN]);

$titre = 'Espace informaticien';
$menu  = ['Accueil' => '/informaticien/index.php'];
$actif = '/informaticien/index.php';
require __DIR__ . '/../../src/vue/entete.php';
?>
<h1><?= e($titre) ?></h1>
<p class="sous-titre">Bienvenue <?= e($utilisateur['prenom']) ?>. Vous êtes connecté avec le profil <strong><?= e($utilisateur['profil']) ?></strong>.</p>
<div class="bandeau">Comptes et habilitations — développé au sprint suivant.</div>
<?php require __DIR__ . '/../../src/vue/pied.php';
