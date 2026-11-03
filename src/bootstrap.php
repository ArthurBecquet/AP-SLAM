<?php
// Chargement des classes (Composer si présent, sinon autoload simple).
$composer = __DIR__ . '/../vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
} else {
    spl_autoload_register(function (string $classe) {
        if (str_starts_with($classe, 'App\\')) {
            $fichier = __DIR__ . '/' . str_replace('\\', '/', substr($classe, 4)) . '.php';
            if (is_file($fichier)) {
                require $fichier;
            }
        }
    });
}

/** Échappement HTML (anti-XSS) pour l'affichage. */
function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

/**
 * Construit une URL de l'application, que le site soit servi à la racine (/)
 * ou dans un sous-dossier (ex. http://serveur/lpfh/public/).
 */
function url(string $chemin): string
{
    static $base = null;
    if ($base === null) {
        $public  = str_replace('\\', '/', realpath(__DIR__ . '/../public'));
        $fichier = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
        $script  = $_SERVER['SCRIPT_NAME'] ?? '';
        $relatif = str_starts_with($fichier, $public) ? substr($fichier, strlen($public)) : '';
        $base    = ($relatif !== '' && str_ends_with($script, $relatif)) ? substr($script, 0, -strlen($relatif)) : '';
    }
    return $base . '/' . ltrim($chemin, '/');
}
