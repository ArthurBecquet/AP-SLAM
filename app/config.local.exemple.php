<?php
// Copier ce fichier en « config.local.php » (même dossier) puis le remplir.
// config.local.php est ignoré par Git : le mot de passe ne part jamais sur GitHub.
return [
    'dsn'  => 'mysql:host=localhost;dbname=clinique_lfph;charset=utf8mb4',
    'user' => 'lpfh_app',      // compte applicatif dédié, jamais root
    'pass' => 'A_REMPLIR',
];
