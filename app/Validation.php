<?php

/**
 * Contrôles de format, refaits côté serveur avant tout enregistrement.
 */
final class Validation
{
    /** Supprime espaces, points et tirets ; majuscules (Corse 2A/2B). */
    public static function normaliserNumSecu(?string $num): string
    {
        return strtoupper(preg_replace('/[\s.\-]/', '', (string) $num));
    }

    /** 15 caractères, clé = 97 - (13 premiers chiffres mod 97). */
    public static function numSecuValide(?string $num): bool
    {
        $n = self::normaliserNumSecu($num);
        if (!preg_match('/^[12]\d{2}(0[1-9]|1[0-2]|[2-9]\d)(\d{2}|2A|2B)\d{6}\d{2}$/', $n)) {
            return false;
        }
        $corps = str_replace(['2A', '2B'], ['19', '18'], substr($n, 0, 13));
        $reste = 0;
        foreach (str_split($corps) as $chiffre) {
            $reste = ($reste * 10 + (int) $chiffre) % 97;
        }
        return 97 - $reste === (int) substr($n, 13);
    }

    /** Sexe déduit du 1er chiffre du n° de sécu : 1 = homme (H), 2 = femme (F). */
    public static function sexeDepuisNumSecu(string $num): ?string
    {
        $premier = substr(self::normaliserNumSecu($num), 0, 1);
        return $premier === '1' ? 'H' : ($premier === '2' ? 'F' : null);
    }

    public static function telephoneValide(?string $tel): bool
    {
        return (bool) preg_match('/^(0[1-9]\d{8}|\+33[1-9]\d{8})$/', preg_replace('/[\s.\-]/', '', (string) $tel));
    }

    public static function emailValide(?string $email): bool
    {
        return filter_var(trim((string) $email), FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Nom / prénom : lettres (accents compris), espaces, apostrophes et tirets. */
    public static function nomValide(?string $nom): bool
    {
        return (bool) preg_match("/^\p{L}[\p{L} '’\-]*$/u", trim((string) $nom));
    }

    /** Date JJ/MM/AAAA → objet date, ou null si invalide (ex. 31/02/2026). */
    public static function date(?string $texte): ?DateTimeImmutable
    {
        $texte = trim((string) $texte);
        if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $texte, $m) || !checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return null;
        }
        return new DateTimeImmutable("$m[3]-$m[2]-$m[1]");
    }

    public static function vide(?string $s): bool
    {
        return trim((string) $s) === '';
    }
}
