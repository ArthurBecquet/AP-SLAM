<?php

/**
 * Authentification par profil + contrôle d'accès.
 */
final class Auth
{
    public const SECRETAIRE    = 'Secrétaire médicale';
    public const MEDECIN       = 'Médecin';
    public const ADMINISTRATIF = 'Département administratif';
    public const INFORMATICIEN = 'Informaticien';

    /** Écran d'arrivée de chaque profil. */
    public const ACCUEIL = [
        self::SECRETAIRE    => 'preadmissions.php',
        self::MEDECIN       => 'accueil.php',
        self::ADMINISTRATIF => 'accueil.php',
        self::INFORMATICIEN => 'accueil.php',
    ];

    /** Message générique : on ne dit jamais si c'est l'identifiant ou le mot de passe qui est faux. */
    public const MESSAGE_ECHEC = 'Identifiant ou mot de passe incorrect.';

    // Hash factice : garde un temps de réponse identique quand l'identifiant n'existe pas.
    private const HASH_FACTICE = '$2y$10$bFQG3AkWv/VhYSaNMxx5W.oqEu10yZ9GSNRbHOq3kgWhsO3K0Vuh2';

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Connexion : renvoie l'utilisateur (sans le hash) si tout est valide, sinon null.
     * Conditions : bon mot de passe, compte actif, habilitation en cours de validité.
     */
    public function connecter(string $identifiant, string $motDePasse, ?string $jour = null): ?array
    {
        $identifiant = trim($identifiant);
        if ($identifiant === '' || $motDePasse === '') {
            return null;
        }
        $jour = $jour ?? date('Y-m-d');

        $stmt = $this->pdo->prepare(
            "SELECT p.num_personnel, p.nom, p.prenom, p.mot_de_passe_hash, p.statut_compte, pr.libelle_profil
               FROM personnel p
               JOIN habilitation h ON h.num_personnel = p.num_personnel
               JOIN profil pr      ON pr.num_profil   = h.num_profil
              WHERE p.identifiant_connexion = ?
                AND h.statut_habilitation = 'active'
                AND h.date_debut_validite <= ?
                AND (h.date_fin_validite IS NULL OR h.date_fin_validite >= ?)
              LIMIT 1"
        );
        $stmt->execute([$identifiant, $jour, $jour]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ligne === false) {
            password_verify($motDePasse, self::HASH_FACTICE);
            return null;
        }
        if (!password_verify($motDePasse, $ligne['mot_de_passe_hash'])
            || $ligne['statut_compte'] !== 'actif'
            || !isset(self::ACCUEIL[$ligne['libelle_profil']])) {
            return null;
        }
        return [
            'num_personnel' => (int) $ligne['num_personnel'],
            'nom'           => $ligne['nom'],
            'prenom'        => $ligne['prenom'],
            'profil'        => $ligne['libelle_profil'],
        ];
    }

    /** Vrai si l'utilisateur est connecté ET a un des profils autorisés. */
    public static function peutAcceder(?array $utilisateur, array $profilsAutorises): bool
    {
        return $utilisateur !== null && in_array($utilisateur['profil'] ?? null, $profilsAutorises, true);
    }

    /** Trace une connexion / déconnexion dans journal_acces. */
    public function tracer(int $numPersonnel, string $action, string $ressource, string $ip): void
    {
        $this->pdo->prepare(
            'INSERT INTO journal_acces (date_heure_acces, type_action, ressource_consultee, adresse_ip, num_personnel)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([date('Y-m-d H:i:s'), $action, mb_substr($ressource, 0, 150), mb_substr($ip, 0, 45), $numPersonnel]);
    }
}
