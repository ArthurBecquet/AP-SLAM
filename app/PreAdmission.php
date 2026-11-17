<?php

/** Levée quand un utilisateur sans le bon profil (ou sans session) tente une action. */
final class AccesRefuseException extends RuntimeException
{
}

/**
 * Création d'une pré-admission par la secrétaire médicale :
 * contrôle d'accès, contrôles de saisie, puis enregistrement en transaction
 * (patient si nouveau + couverture + personne de confiance + pré-admission).
 * Toutes les méthodes publiques sont réservées au profil secrétaire médicale.
 */
final class PreAdmission
{
    /** Champs du formulaire « Nouvelle pré-admission ». */
    public const CHAMPS = [
        'num_secu', 'nom', 'prenom', 'date_naissance', 'adresse', 'ville', 'telephone', 'email',
        'service', 'date_entree', 'duree', 'motif', 'organisme', 'mutuelle', 'numero_adherent',
        'pc_nom', 'pc_prenom', 'pc_telephone', 'pc_lien',
    ];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Contrôle d'accès fait par le code (pas seulement en cachant des liens). */
    private static function exigerSecretaire(?array $utilisateur): array
    {
        if (!Auth::peutAcceder($utilisateur, [Auth::SECRETAIRE])) {
            throw new AccesRefuseException('Accès réservé à la secrétaire médicale.');
        }
        return $utilisateur;
    }

    public function rechercherPatient(?array $utilisateur, string $numSecu): ?array
    {
        self::exigerSecretaire($utilisateur);
        return $this->patient(Validation::normaliserNumSecu($numSecu));
    }

    private function patient(string $numSecu): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient WHERE num_securite_sociale = ?');
        $stmt->execute([$numSecu]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($p === false) {
            return null;
        }
        // Caisse d'assurance maladie la plus récente du patient (table couvrir)
        $stmt = $this->pdo->prepare('SELECT num_organisme FROM couvrir WHERE num_patient = ? ORDER BY date_debut DESC LIMIT 1');
        $stmt->execute([$p['num_patient']]);
        $organisme = $stmt->fetchColumn();
        $p['num_organisme'] = $organisme === false ? null : (int) $organisme;
        return $p;
    }

    /** Listes déroulantes : services, caisses d'assurance maladie, mutuelles (id => libellé). */
    public function listes(?array $utilisateur): array
    {
        self::exigerSecretaire($utilisateur);
        return [
            'service'   => $this->reference('SELECT num_service, libelle_service FROM service ORDER BY libelle_service'),
            'organisme' => $this->reference('SELECT num_organisme, nom_organisme FROM organisme ORDER BY nom_organisme'),
            'mutuelle'  => $this->reference('SELECT num_mutuelle, nom_mutuelle FROM mutuelle ORDER BY nom_mutuelle'),
        ];
    }

    private function reference(string $sql): array
    {
        $liste = [];
        foreach ($this->pdo->query($sql, PDO::FETCH_NUM) as $ligne) {
            $liste[(int) $ligne[0]] = $ligne[1];
        }
        return $liste;
    }

    /** Liste partagée entre secrétaires (ou seulement les miennes). */
    public function lister(?array $utilisateur, bool $seulementLesMiennes = false): array
    {
        $u = self::exigerSecretaire($utilisateur);
        $sql = 'SELECT pa.num_pre_admission, pa.date_entree_prevue, pa.statut,
                       pt.nom AS p_nom, pt.prenom AS p_prenom, s.libelle_service,
                       pe.num_personnel, pe.nom AS s_nom, pe.prenom AS s_prenom
                  FROM pre_admission pa
                  JOIN patient pt   ON pt.num_patient   = pa.num_patient
                  JOIN service s    ON s.num_service    = pa.num_service
                  JOIN personnel pe ON pe.num_personnel = pa.num_personnel_saisie'
            . ($seulementLesMiennes ? ' WHERE pa.num_personnel_saisie = ?' : '')
            . ' ORDER BY pa.date_entree_prevue';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($seulementLesMiennes ? [$u['num_personnel']] : []);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contrôles de saisie (refaits côté serveur, même si le navigateur a déjà vérifié).
     * @return array champ => message d'erreur (vide si tout est correct)
     */
    public function valider(array $s, bool $patientExistant, DateTimeImmutable $aujourdhui): array
    {
        $err = [];
        $listes = [
            'service'   => $this->reference('SELECT num_service, libelle_service FROM service'),
            'organisme' => $this->reference('SELECT num_organisme, nom_organisme FROM organisme'),
            'mutuelle'  => $this->reference('SELECT num_mutuelle, nom_mutuelle FROM mutuelle'),
        ];

        // N° de sécurité sociale
        if (Validation::vide($s['num_secu'])) {
            $err['num_secu'] = 'N° de sécurité sociale : champ obligatoire.';
        } elseif (!Validation::numSecuValide($s['num_secu'])) {
            $err['num_secu'] = 'N° de sécurité sociale invalide (15 chiffres, clé incorrecte).';
        }

        // Identité et coordonnées (seulement si le patient n'est pas déjà inscrit)
        if (!$patientExistant) {
            self::nom($err, 'nom', $s['nom'], 'Nom');
            self::nom($err, 'prenom', $s['prenom'], 'Prénom');
            if (Validation::vide($s['date_naissance'])) {
                $err['date_naissance'] = 'Date de naissance : champ obligatoire.';
            } elseif (($d = Validation::date($s['date_naissance'])) === null) {
                $err['date_naissance'] = 'Date de naissance invalide (JJ/MM/AAAA).';
            } elseif ($d > $aujourdhui) {
                $err['date_naissance'] = 'La date de naissance ne peut pas être dans le futur.';
            }
            self::texte($err, 'adresse', $s['adresse'], 'Adresse', 150);
            self::texte($err, 'ville', $s['ville'], 'Ville', 80);
            if (Validation::vide($s['telephone'])) {
                $err['telephone'] = 'Téléphone : champ obligatoire.';
            } elseif (!Validation::telephoneValide($s['telephone'])) {
                $err['telephone'] = 'Téléphone invalide (ex. 06 12 34 56 78).';
            }
            if (!Validation::vide($s['email'])) {
                if (!Validation::emailValide($s['email'])) {
                    $err['email'] = 'Email invalide (ex. nom@exemple.fr).';
                } elseif (mb_strlen(trim($s['email'])) > 100) {
                    $err['email'] = 'Email trop long (100 caractères max).';
                }
            }
        }

        // Hospitalisation
        if (Validation::vide($s['service'])) {
            $err['service'] = 'Service : champ obligatoire.';
        } elseif (!isset($listes['service'][(int) $s['service']])) {
            $err['service'] = 'Service inconnu.';
        }
        if (Validation::vide($s['date_entree'])) {
            $err['date_entree'] = "Date d'entrée : champ obligatoire.";
        } elseif (($d = Validation::date($s['date_entree'])) === null) {
            $err['date_entree'] = "Date d'entrée invalide (JJ/MM/AAAA).";
        } elseif ($d < $aujourdhui) {
            $err['date_entree'] = "La date d'entrée ne peut pas être dans le passé.";
        }
        if (!Validation::vide($s['duree'])
            && (!ctype_digit(trim($s['duree'])) || (int) $s['duree'] > 365)) {
            $err['duree'] = 'Durée prévue invalide (0 à 365 jours).';
        }
        self::texte($err, 'motif', $s['motif'], 'Motif', 255);

        // Assurance maladie et mutuelle (facultatives, mais doivent exister dans les listes)
        if (!Validation::vide($s['organisme']) && !isset($listes['organisme'][(int) $s['organisme']])) {
            $err['organisme'] = "Caisse d'assurance maladie inconnue.";
        }
        if (!Validation::vide($s['mutuelle']) && !isset($listes['mutuelle'][(int) $s['mutuelle']])) {
            $err['mutuelle'] = 'Mutuelle inconnue.';
        }
        if (!Validation::vide($s['numero_adherent'])) {
            if (Validation::vide($s['mutuelle'])) {
                $err['numero_adherent'] = "N° d'adhérent saisi sans mutuelle : choisissez la mutuelle.";
            } elseif (mb_strlen(trim($s['numero_adherent'])) > 30) {
                $err['numero_adherent'] = "N° d'adhérent trop long (30 caractères max).";
            }
        }

        // Personne à prévenir
        self::nom($err, 'pc_nom', $s['pc_nom'], 'Nom de la personne à prévenir');
        self::nom($err, 'pc_prenom', $s['pc_prenom'], 'Prénom de la personne à prévenir');
        if (Validation::vide($s['pc_telephone'])) {
            $err['pc_telephone'] = 'Téléphone de la personne à prévenir : champ obligatoire.';
        } elseif (!Validation::telephoneValide($s['pc_telephone'])) {
            $err['pc_telephone'] = 'Téléphone de la personne à prévenir invalide (ex. 06 12 34 56 78).';
        }
        if (mb_strlen(trim($s['pc_lien'])) > 50) {
            $err['pc_lien'] = 'Lien avec le patient trop long (50 caractères max).';
        }
        return $err;
    }

    /** Texte obligatoire avec une longueur maximale (celle de la colonne en base). */
    private static function texte(array &$err, string $cle, string $valeur, string $libelle, int $max): void
    {
        if (Validation::vide($valeur)) {
            $err[$cle] = "$libelle : champ obligatoire.";
        } elseif (mb_strlen(trim($valeur)) > $max) {
            $err[$cle] = "$libelle trop long ($max caractères max).";
        }
    }

    /** Nom ou prénom obligatoire : 50 caractères max, lettres / espaces / tirets / apostrophes. */
    private static function nom(array &$err, string $cle, string $valeur, string $libelle): void
    {
        self::texte($err, $cle, $valeur, $libelle, 50);
        if (!isset($err[$cle]) && !Validation::nomValide($valeur)) {
            $err[$cle] = "$libelle invalide (lettres, espaces et tirets uniquement).";
        }
    }

    /**
     * Contrôle d'accès + contrôles de saisie + enregistrement en base.
     * @return array ['ok' => bool, 'num' => int, 'nouveau_patient' => bool, 'erreurs' => array]
     */
    public function enregistrer(?array $utilisateur, array $saisie, ?DateTimeImmutable $aujourdhui = null): array
    {
        $secretaire = self::exigerSecretaire($utilisateur);
        $aujourdhui = $aujourdhui ?? new DateTimeImmutable('today');

        $s = [];
        foreach (self::CHAMPS as $champ) {
            $s[$champ] = trim((string) ($saisie[$champ] ?? ''));
        }
        $numSecu = Validation::normaliserNumSecu($s['num_secu']);
        $patient = $numSecu === '' ? null : $this->patient($numSecu);

        $erreurs = $this->valider($s, $patient !== null, $aujourdhui);
        if ($erreurs) {
            return ['ok' => false, 'num' => 0, 'nouveau_patient' => false, 'erreurs' => $erreurs];
        }

        $mutuelle = $s['mutuelle'] === '' ? null : (int) $s['mutuelle'];
        $adherent = $mutuelle === null || $s['numero_adherent'] === '' ? null : $s['numero_adherent'];
        $jour = $aujourdhui->format('Y-m-d');

        try {
            $this->pdo->beginTransaction();

            if ($patient === null) {
                $this->pdo->prepare(
                    'INSERT INTO patient (num_securite_sociale, nom, prenom, date_naissance, sexe, adresse, ville, telephone,
                                          email, num_mutuelle, numero_adherent, date_debut_adhesion)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $numSecu, mb_strtoupper($s['nom']), $s['prenom'],
                    Validation::date($s['date_naissance'])->format('Y-m-d'), Validation::sexeDepuisNumSecu($numSecu),
                    $s['adresse'], $s['ville'], $s['telephone'], $s['email'] === '' ? null : $s['email'],
                    $mutuelle, $adherent, $mutuelle === null ? null : $jour,
                ]);
                $numPatient = (int) $this->pdo->lastInsertId();
            } else {
                // Patient déjà inscrit : on ne met à jour que sa mutuelle (il a pu en changer)
                $numPatient = (int) $patient['num_patient'];
                $this->pdo->prepare('UPDATE patient SET num_mutuelle = ?, numero_adherent = ? WHERE num_patient = ?')
                    ->execute([$mutuelle, $adherent, $numPatient]);
            }

            // Liaison « Couvrir » avec la caisse d'assurance maladie (sans doublon)
            if ($s['organisme'] !== '') {
                $stmt = $this->pdo->prepare('SELECT 1 FROM couvrir WHERE num_patient = ? AND num_organisme = ?');
                $stmt->execute([$numPatient, (int) $s['organisme']]);
                if ($stmt->fetchColumn() === false) {
                    $this->pdo->prepare('INSERT INTO couvrir (num_patient, num_organisme, numero_adherent, date_debut) VALUES (?, ?, ?, ?)')
                        ->execute([$numPatient, (int) $s['organisme'], $numSecu, $jour]);
                }
            }

            $this->pdo->prepare(
                'INSERT INTO personne_confiance (nom_personne_confiance, prenom_personne_confiance,
                                                 telephone_personne_confiance, lien_avec_patient)
                 VALUES (?, ?, ?, ?)'
            )->execute([mb_strtoupper($s['pc_nom']), $s['pc_prenom'], $s['pc_telephone'], $s['pc_lien'] === '' ? null : $s['pc_lien']]);
            $numPc = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare(
                'INSERT INTO pre_admission (date_demande, motif_hospitalisation, date_entree_prevue, duree_prevue, statut,
                                            date_derniere_modification, num_patient, num_service, num_personnel_saisie,
                                            num_personne_confiance)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $jour, $s['motif'], Validation::date($s['date_entree'])->format('Y-m-d'),
                $s['duree'] === '' ? null : (int) $s['duree'], 'chez le medecin', date('Y-m-d H:i:s'),
                $numPatient, (int) $s['service'], $secretaire['num_personnel'], $numPc,
            ]);
            $num = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();
            return ['ok' => true, 'num' => $num, 'nouveau_patient' => $patient === null, 'erreurs' => []];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack(); // rien n'est enregistré si une étape échoue
            }
            return ['ok' => false, 'num' => 0, 'nouveau_patient' => false,
                    'erreurs' => ['global' => "Erreur lors de l'enregistrement. Aucune donnée n'a été enregistrée."]];
        }
    }
}
