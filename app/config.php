<?php
/**
 * Connexion à la base : valeurs par défaut (versionnées, SANS mot de passe).
 * Les vraies valeurs (serveur, compte applicatif dédié, mot de passe) vont dans
 * app/config.local.php, qui n'est jamais envoyé sur GitHub (voir config.local.exemple.php).
 */
$config = [
    'dsn'  => 'mysql:host=localhost;dbname=clinique_lfph;charset=utf8mb4',
    'user' => 'lpfh_app',
    'pass' => '',
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

return $config;
