<?php
// Écran d'accueil du profil « Espace médecin » — accès réservé à ce profil (contrôle serveur)
require __DIR__ . '/../../src/garde.php';
$utilisateur = exigerProfil([App\Auth::MEDECIN]);

$titre = 'Espace médecin';
$menu  = ['Accueil' => '/medecin/index.php'];
$actif = '/medecin/index.php';
require __DIR__ . '/../../src/vue/entete.php';
?>
<h1><?= e($titre) ?></h1>
<p class="sous-titre">Bienvenue <?= e($utilisateur['prenom']) ?>. Vous êtes connecté avec le profil <strong><?= e($utilisateur['profil']) ?></strong>.</p>
<div class="bandeau">Pré-admissions à compléter (volet médical) — développé au sprint suivant.</div>
<?php require __DIR__ . '/../../src/vue/pied.php';
