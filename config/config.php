<?php
// Configuration de la connexion MariaDB/MySQL.
// Adapte ces valeurs à ton environnement (ou définis les variables d'environnement).
return [
    'db_host' => getenv('LPFH_DB_HOST') ?: 'localhost',
    'db_name' => getenv('LPFH_DB_NAME') ?: 'clinique_lfph',
    'db_user' => getenv('LPFH_DB_USER') ?: 'root',
    'db_pass' => getenv('LPFH_DB_PASS') ?: '',
    // DSN complet optionnel (prioritaire) — ex. pour tester en local avec SQLite
    'db_dsn'  => getenv('LPFH_DB_DSN') ?: null,
];
