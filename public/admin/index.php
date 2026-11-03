<?php
// Écran d'accueil du profil « Département administratif » — accès réservé à ce profil (contrôle serveur)
require __DIR__ . '/../../src/garde.php';
$utilisateur = exigerProfil([App\Auth::ADMINISTRATIF]);

$titre = 'Département administratif';
$menu  = ['Accueil' => '/admin/index.php'];
$actif = '/admin/index.php';
require __DIR__ . '/../../src/vue/entete.php';
?>
<h1><?= e($titre) ?></h1>
<p class="sous-titre">Bienvenue <?= e($utilisateur['prenom']) ?>. Vous êtes connecté avec le profil <strong><?= e($utilisateur['profil']) ?></strong>.</p>
<div class="bandeau">Pré-admissions à valider — développé au sprint suivant.</div>
<?php require __DIR__ . '/../../src/vue/pied.php';
