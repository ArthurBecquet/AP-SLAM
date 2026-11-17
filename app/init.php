<?php
/**
 * À inclure en haut de chaque page : classes, session sécurisée, connexion à la base,
 * contrôle d'accès et petites fonctions d'affichage.
 */
require_once __DIR__ . '/Validation.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/PreAdmission.php';

// --- Session (cookie HttpOnly + SameSite, id régénéré à la connexion) ---------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['lifetime' => 0, 'httponly' => true, 'samesite' => 'Strict',
                               'secure' => !empty($_SERVER['HTTPS'])]);
    session_name('LPFHSESSID');
    session_start();
}

/** Connexion PDO unique (requêtes préparées réelles, erreurs en exceptions). */
function bdd(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/config.php';
        $pdo = new PDO($c['dsn'], $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function utilisateur(): ?array
{
    return $_SESSION['utilisateur'] ?? null;
}

/**
 * Contrôle d'accès côté serveur, en haut de chaque page protégée :
 * sans session → page de connexion ; mauvais profil → page « Accès refusé » (403).
 */
function exigerProfil(array $profils): array
{
    $u = utilisateur();
    if ($u === null) {
        header('Location: index.php?expire=1');
        exit;
    }
    if (!Auth::peutAcceder($u, $profils)) {
        http_response_code(403);
        $titre = 'Accès refusé';
        require __DIR__ . '/haut.php';
        echo '<h1>Accès refusé</h1><p class="alerte erreur">Votre profil ne permet pas d\'accéder à cette page.</p>'
           . '<a class="bouton" href="' . e(Auth::ACCUEIL[$u['profil']]) . '">Retour à mon accueil</a>';
        require __DIR__ . '/bas.php';
        exit;
    }
    return $u;
}

/** Jeton anti-CSRF pour les formulaires. */
function jetonCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfValide(): bool
{
    return is_string($_POST['csrf'] ?? null) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/** Échappement HTML (anti-XSS) à l'affichage. */
function e($texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

/** Date SQL AAAA-MM-JJ → JJ/MM/AAAA. */
function dateFr(?string $date): string
{
    return $date ? date('d/m/Y', strtotime($date)) : '';
}
