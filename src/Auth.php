<?php
namespace App;

use PDO;

/**
 * Authentification par profil.
 * - vérifie le mot de passe saisi contre le hash stocké (password_verify : bcrypt/Argon2)
 * - ne renvoie jamais la raison précise d'un échec (message générique côté page)
 * - décide si un utilisateur connecté peut accéder à une page (contrôle côté serveur)
 */
final class Auth
{
    public const SECRETAIRE    = 'Secrétaire médicale';
    public const MEDECIN       = 'Médecin';
    public const ADMINISTRATIF = 'Département administratif';
    public const INFORMATICIEN = 'Informaticien';

    /** Écran d'accueil propre à chaque profil. */
    public const ACCUEIL = [
        self::SECRETAIRE    => '/secretaire/index.php',
        self::MEDECIN       => '/medecin/index.php',
        self::ADMINISTRATIF => '/admin/index.php',
        self::INFORMATICIEN => '/informaticien/index.php',
    ];

    public const MESSAGE_ECHEC = 'Identifiant ou mot de passe incorrect.';

    // Hash factice : permet de garder un temps de réponse identique quand l'identifiant n'existe pas.
    private const HASH_FACTICE = '$2y$10$bFQG3AkWv/VhYSaNMxx5W.oqEu10yZ9GSNRbHOq3kgWhsO3K0Vuh2';

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Tente une connexion. Renvoie l'utilisateur (sans le hash) ou null si échec.
     * @return array{num_personnel:int, nom:string, prenom:string, profil:string}|null
     */
    public function connecter(string $identifiant, string $motDePasse, ?string $aujourdhui = null): ?array
    {
        $identifiant = trim($identifiant);
        if ($identifiant === '' || $motDePasse === '') {
            return null;
        }
        $aujourdhui ??= date('Y-m-d');

        $sql = 'SELECT p.num_personnel, p.nom, p.prenom, p.mot_de_passe_hash, p.statut_compte, pr.libelle_profil
                  FROM personnel p
                  JOIN habilitation h ON h.num_personnel = p.num_personnel
                  JOIN profil pr      ON pr.num_profil   = h.num_profil
                 WHERE p.identifiant_connexion = :identifiant
                   AND h.statut_habilitation = :statut
                   AND h.date_debut_validite <= :jour1
                   AND (h.date_fin_validite IS NULL OR h.date_fin_validite >= :jour2)
                 LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':identifiant' => $identifiant,
            ':statut'      => 'active',
            ':jour1'       => $aujourdhui,
            ':jour2'       => $aujourdhui,
        ]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne === false) {
            password_verify($motDePasse, self::HASH_FACTICE);
            return null;
        }
        if (!password_verify($motDePasse, $ligne['mot_de_passe_hash'])) {
            return null;
        }
        if ($ligne['statut_compte'] !== 'actif' || !isset(self::ACCUEIL[$ligne['libelle_profil']])) {
            return null;
        }

        return [
            'num_personnel' => (int) $ligne['num_personnel'],
            'nom'           => $ligne['nom'],
            'prenom'        => $ligne['prenom'],
            'profil'        => $ligne['libelle_profil'],
        ];
    }

    /** Page d'accueil à laquelle rediriger après connexion. */
    public static function accueilDe(array $utilisateur): string
    {
        return self::ACCUEIL[$utilisateur['profil']] ?? '/index.php';
    }

    /**
     * Contrôle d'accès côté serveur : l'utilisateur doit être connecté ET avoir un profil autorisé.
     * @param string[] $profilsAutorises
     */
    public static function peutAcceder(?array $utilisateur, array $profilsAutorises): bool
    {
        if ($utilisateur === null || !isset($utilisateur['profil'], $utilisateur['num_personnel'])) {
            return false;
        }
        return in_array($utilisateur['profil'], $profilsAutorises, true);
    }

    /** Trace l'action dans journal_acces (association « Tracer »). */
    public function tracer(int $numPersonnel, string $typeAction, string $ressource, string $ip): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO journal_acces (date_heure_acces, type_action, ressource_consultee, adresse_ip, num_personnel)
             VALUES (:dh, :action, :ressource, :ip, :num)'
        );
        $stmt->execute([
            ':dh'        => date('Y-m-d H:i:s'),
            ':action'    => $typeAction,
            ':ressource' => mb_substr($ressource, 0, 150),
            ':ip'        => mb_substr($ip, 0, 45),
            ':num'       => $numPersonnel,
        ]);
    }
}
