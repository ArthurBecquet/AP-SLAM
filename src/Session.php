<?php
namespace App;

/**
 * Gestion de la session PHP (cookie sécurisé, régénération d'id, destruction complète).
 */
final class Session
{
    public static function demarrer(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_name('LPFHSESSID');
        session_start();
    }

    /** Ouvre la session de l'utilisateur authentifié (identité + profil). */
    public static function ouvrir(array $utilisateur): void
    {
        self::demarrer();
        session_regenerate_id(true); // évite la fixation de session
        $_SESSION['utilisateur'] = $utilisateur;
    }

    public static function utilisateur(): ?array
    {
        self::demarrer();
        return $_SESSION['utilisateur'] ?? null;
    }

    /** Déconnexion : vide et détruit la session + supprime le cookie. */
    public static function detruire(): void
    {
        self::demarrer();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Jeton anti-CSRF pour les formulaires. */
    public static function jetonCsrf(): string
    {
        self::demarrer();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verifierCsrf(?string $jeton): bool
    {
        self::demarrer();
        return is_string($jeton) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $jeton);
    }
}
