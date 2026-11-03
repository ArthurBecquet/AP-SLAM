<?php
namespace App;

use PDO;

/**
 * Fournit une connexion PDO unique (requêtes préparées, exceptions activées).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $c = require __DIR__ . '/../config/config.php';
            $dsn = $c['db_dsn'] ?: "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }
}
