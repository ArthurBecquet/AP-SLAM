<?php
namespace App;

use PDO;
use Throwable;

/**
 * Création d'une pré-admission par la secrétaire médicale.
 * 1) recherche du patient par n° de sécurité sociale
 * 2) contrôles de saisie refaits côté serveur
 * 3) enregistrement en base (requêtes préparées, transaction) :
 *    patient (s'il n'existe pas, ou mise à jour de sa mutuelle) + personne de confiance + pré-admission
 */
final class PreAdmission
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Normalise un n° de sécu : supprime espaces/points/tirets, majuscules (2A/2B). */
    public static function normaliserNumSecu(string $num): string
    {
        return strtoupper(preg_replace('/[\s.\-]/', '', $num));
    }

    /** N° de sécu valide : 15 caractères (13 + clé), clé = 97 - (numéro mod 97). Gère la Corse (2A/2B). */
    public static function numSecuValide(string $num): bool
    {
        $num = self::normaliserNumSecu($num);
        if (!preg_match('/^[12]\d{2}(0[1-9]|1[0-2]|[2-9]\d)(\d{2}|2A|2B)\d{6}\d{2}$/', $num)) {
            return false;
        }
        $corps = substr($num, 0, 13);
        $cle   = (int) substr($num, 13, 2);
        $corps = str_replace(['2A', '2B'], ['19', '18'], $corps);
        return (97 - (int) bcmod_compat($corps, 97)) === $cle;
    }

    public static function telephoneValide(string $tel): bool
    {
        $tel = preg_replace('/[\s.\-]/', '', $tel);
        return (bool) preg_match('/^(0[1-9]\d{8}|\+33[1-9]\d{8})$/', $tel);
    }

    /** Date au format AAAA-MM-JJ (champ <input type="date">) et réelle. */
    public static function dateValide(string $date): bool
    {
        $d = \DateTime::createFromFormat('!Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    public function trouverPatient(string $numSecu): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient WHERE num_securite_sociale = :num');
        $stmt->execute([':num' => self::normaliserNumSecu($numSecu)]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        return $p === false ? null : $p;
    }

    public function listerServices(): array
    {
        return $this->pdo->query('SELECT num_service, libelle_service FROM service ORDER BY libelle_service')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listerMutuelles(): array
    {
        return $this->pdo->query('SELECT num_mutuelle, nom_mutuelle FROM mutuelle ORDER BY nom_mutuelle')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Pré-admissions de TOUTES les secrétaires (partagées), filtrables sur « créées par moi ». */
    public function lister(?int $creeesPar = null): array
    {
        $sql = 'SELECT pa.num_pre_admission, pa.date_entree_prevue, pa.statut,
                       pt.nom AS patient_nom, pt.prenom AS patient_prenom,
                       s.libelle_service, pe.num_personnel, pe.nom AS secretaire_nom, pe.prenom AS secretaire_prenom
                  FROM pre_admission pa
                  JOIN patient pt   ON pt.num_patient   = pa.num_patient
                  JOIN service s    ON s.num_service    = pa.num_service
                  JOIN personnel pe ON pe.num_personnel = pa.num_personnel_saisie';
        $params = [];
        if ($creeesPar !== null) {
            $sql .= ' WHERE pa.num_personnel_saisie = :num';
            $params[':num'] = $creeesPar;
        }
        $stmt = $this->pdo->prepare($sql . ' ORDER BY pa.date_entree_prevue');
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contrôles de saisie côté serveur.
     * @return array<string,string> champ => message (vide si tout est valide)
     */
    public function valider(array $d, bool $patientExistant): array
    {
        $err = [];
        $oblig = [
            'num_securite_sociale' => 'N° de sécurité sociale',
            'date_entree_prevue'   => 'Date d\'entrée',
            'num_service'          => 'Service',
            'motif_hospitalisation'=> 'Motif',
            'pc_nom'               => 'Nom de la personne de confiance',
            'pc_prenom'            => 'Prénom de la personne de confiance',
            'pc_telephone'         => 'Téléphone de la personne de confiance',
        ];
        if (!$patientExistant) {
            $oblig += ['nom' => 'Nom', 'prenom' => 'Prénom', 'date_naissance' => 'Date de naissance',
                       'adresse' => 'Adresse', 'ville' => 'Ville', 'telephone' => 'Téléphone'];
        }
        foreach ($oblig as $champ => $libelle) {
            if (trim((string) ($d[$champ] ?? '')) === '') {
                $err[$champ] = "$libelle : champ obligatoire.";
            }
        }

        if (!isset($err['num_securite_sociale']) && !self::numSecuValide($d['num_securite_sociale'])) {
            $err['num_securite_sociale'] = 'N° de sécurité sociale invalide (15 chiffres, clé incorrecte).';
        }
        if (!isset($err['date_entree_prevue']) && !self::dateValide($d['date_entree_prevue'])) {
            $err['date_entree_prevue'] = 'Date d\'entrée invalide.';
        } elseif (!isset($err['date_entree_prevue']) && $d['date_entree_prevue'] < date('Y-m-d')) {
            $err['date_entree_prevue'] = 'La date d\'entrée ne peut pas être dans le passé.';
        }
        if (!$patientExistant) {
            if (!isset($err['date_naissance']) && (!self::dateValide($d['date_naissance']) || $d['date_naissance'] > date('Y-m-d'))) {
                $err['date_naissance'] = 'Date de naissance invalide.';
            }
            if (!isset($err['telephone']) && !self::telephoneValide($d['telephone'])) {
                $err['telephone'] = 'Téléphone invalide (ex. 06 12 34 56 78).';
            }
            if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
                $err['email'] = 'Email invalide.';
            }
        }
        if (!isset($err['pc_telephone']) && !self::telephoneValide($d['pc_telephone'])) {
            $err['pc_telephone'] = 'Téléphone de la personne de confiance invalide.';
        }
        if (!isset($err['num_service']) && !ctype_digit((string) $d['num_service'])) {
            $err['num_service'] = 'Service invalide.';
        }
        if (!empty($d['duree_prevue']) && (!ctype_digit((string) $d['duree_prevue']) || (int) $d['duree_prevue'] > 365)) {
            $err['duree_prevue'] = 'Durée prévue invalide (nombre de jours).';
        }
        if (!empty($d['num_mutuelle']) && !ctype_digit((string) $d['num_mutuelle'])) {
            $err['num_mutuelle'] = 'Mutuelle invalide.';
        }
        if (mb_strlen((string) ($d['motif_hospitalisation'] ?? '')) > 255) {
            $err['motif_hospitalisation'] = 'Motif trop long (255 caractères max).';
        }
        return $err;
    }

    /**
     * Enregistre la pré-admission (et le patient s'il n'existe pas encore).
     * @return array{ok:bool, erreurs?:array, num_pre_admission?:int, nouveau_patient?:bool}
     */
    public function enregistrer(array $d, int $numSecretaire): array
    {
        $d = array_map(fn($v) => is_string($v) ? trim($v) : $v, $d);
        $d['num_securite_sociale'] = self::normaliserNumSecu((string) ($d['num_securite_sociale'] ?? ''));
        $patient = $d['num_securite_sociale'] !== '' ? $this->trouverPatient($d['num_securite_sociale']) : null;

        $erreurs = $this->valider($d, $patient !== null);
        if ($erreurs) {
            return ['ok' => false, 'erreurs' => $erreurs];
        }

        $mutuelle = !empty($d['num_mutuelle']) ? (int) $d['num_mutuelle'] : null;
        $adherent = ($d['numero_adherent'] ?? '') !== '' ? $d['numero_adherent'] : null;

        try {
            $this->pdo->beginTransaction();

            if ($patient === null) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO patient (num_securite_sociale, nom, prenom, date_naissance, adresse, ville, telephone, email, num_mutuelle, numero_adherent, date_debut_adhesion)
                     VALUES (:secu, :nom, :prenom, :naiss, :adresse, :ville, :tel, :email, :mut, :adh, :debut)'
                );
                $stmt->execute([
                    ':secu' => $d['num_securite_sociale'], ':nom' => mb_strtoupper($d['nom']), ':prenom' => $d['prenom'],
                    ':naiss' => $d['date_naissance'], ':adresse' => $d['adresse'], ':ville' => $d['ville'],
                    ':tel' => $d['telephone'], ':email' => ($d['email'] ?? '') ?: null,
                    ':mut' => $mutuelle, ':adh' => $adherent, ':debut' => $mutuelle ? date('Y-m-d') : null,
                ]);
                $numPatient = (int) $this->pdo->lastInsertId();
            } else {
                $numPatient = (int) $patient['num_patient'];
                // Patient déjà inscrit : on met seulement à jour sa mutuelle si elle a changé
                if ($mutuelle !== ($patient['num_mutuelle'] !== null ? (int) $patient['num_mutuelle'] : null)
                    || $adherent !== $patient['numero_adherent']) {
                    $stmt = $this->pdo->prepare(
                        'UPDATE patient SET num_mutuelle = :mut, numero_adherent = :adh, date_debut_adhesion = :debut, date_fin_adhesion = NULL
                          WHERE num_patient = :num'
                    );
                    $stmt->execute([':mut' => $mutuelle, ':adh' => $adherent, ':debut' => $mutuelle ? date('Y-m-d') : null, ':num' => $numPatient]);
                }
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO personne_confiance (nom_personne_confiance, prenom_personne_confiance, telephone_personne_confiance, lien_avec_patient)
                 VALUES (:nom, :prenom, :tel, :lien)'
            );
            $stmt->execute([':nom' => mb_strtoupper($d['pc_nom']), ':prenom' => $d['pc_prenom'], ':tel' => $d['pc_telephone'], ':lien' => ($d['pc_lien'] ?? '') ?: null]);
            $numPersonneConfiance = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                'INSERT INTO pre_admission (date_demande, motif_hospitalisation, date_entree_prevue, duree_prevue, statut,
                                            date_derniere_modification, num_patient, num_service, num_personnel_saisie, num_personne_confiance)
                 VALUES (:demande, :motif, :entree, :duree, :statut, :modif, :patient, :service, :saisie, :pc)'
            );
            $stmt->execute([
                ':demande' => date('Y-m-d'), ':motif' => $d['motif_hospitalisation'], ':entree' => $d['date_entree_prevue'],
                ':duree' => ($d['duree_prevue'] ?? '') !== '' ? (int) $d['duree_prevue'] : null, ':statut' => 'chez le medecin',
                ':modif' => date('Y-m-d H:i:s'), ':patient' => $numPatient, ':service' => (int) $d['num_service'],
                ':saisie' => $numSecretaire, ':pc' => $numPersonneConfiance,
            ]);
            $numPreAdmission = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();
            return ['ok' => true, 'num_pre_admission' => $numPreAdmission, 'nouveau_patient' => $patient === null];
        } catch (Throwable $ex) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Pré-admission : ' . $ex->getMessage());
            return ['ok' => false, 'erreurs' => ['global' => 'Erreur lors de l\'enregistrement. Aucune donnée n\'a été enregistrée.']];
        }
    }
}

/** Modulo sur un grand nombre en chaîne (le n° de sécu dépasse parfois PHP_INT_MAX sur 32 bits). */
function bcmod_compat(string $nombre, int $mod): int
{
    $reste = 0;
    foreach (str_split($nombre) as $chiffre) {
        $reste = ($reste * 10 + (int) $chiffre) % $mod;
    }
    return $reste;
}
