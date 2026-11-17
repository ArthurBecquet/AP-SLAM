<?php
// Charge les classes de l'application (sans session ni connexion à la vraie base) pour les tests.
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Validation.php';
require __DIR__ . '/../app/Auth.php';
require __DIR__ . '/../app/PreAdmission.php';
require __DIR__ . '/base_de_donnees.php';
