<?php

/**
 * Base SQLite en mémoire avec les tables utiles (mêmes noms de colonnes que MariaDB) :
 * les tests ne touchent jamais à la vraie base.
 */
final class BaseDeTest
{
    /** Mot de passe tiré au hasard à chaque lancement : aucun mot de passe en clair dans le code. */
    public static function motDePasse(): string
    {
        static $mdp = null;
        return $mdp ??= bin2hex(random_bytes(8)) . '!9a';
    }

    public static function creer(): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec(<<<SQL
            CREATE TABLE service (num_service INTEGER PRIMARY KEY, libelle_service TEXT NOT NULL);
            CREATE TABLE profil (num_profil INTEGER PRIMARY KEY, libelle_profil TEXT NOT NULL);
            CREATE TABLE organisme (num_organisme INTEGER PRIMARY KEY, nom_organisme TEXT NOT NULL);
            CREATE TABLE mutuelle (num_mutuelle INTEGER PRIMARY KEY, nom_mutuelle TEXT NOT NULL,
                num_organisme INTEGER REFERENCES organisme(num_organisme));
            CREATE TABLE personnel (num_personnel INTEGER PRIMARY KEY, nom TEXT NOT NULL, prenom TEXT NOT NULL,
                identifiant_connexion TEXT NOT NULL UNIQUE, mot_de_passe_hash TEXT NOT NULL,
                statut_compte TEXT NOT NULL DEFAULT 'actif');
            CREATE TABLE habilitation (num_habilitation INTEGER PRIMARY KEY, date_debut_validite TEXT NOT NULL,
                date_fin_validite TEXT, statut_habilitation TEXT, application TEXT,
                num_personnel INTEGER NOT NULL REFERENCES personnel(num_personnel),
                num_profil INTEGER NOT NULL REFERENCES profil(num_profil));
            CREATE TABLE journal_acces (num_acces INTEGER PRIMARY KEY, date_heure_acces TEXT NOT NULL, type_action TEXT,
                ressource_consultee TEXT, adresse_ip TEXT, num_personnel INTEGER NOT NULL REFERENCES personnel(num_personnel));
            CREATE TABLE patient (num_patient INTEGER PRIMARY KEY, num_securite_sociale TEXT NOT NULL UNIQUE,
                nom TEXT NOT NULL, prenom TEXT NOT NULL, date_naissance TEXT NOT NULL, sexe TEXT, adresse TEXT, ville TEXT,
                telephone TEXT, email TEXT, num_mutuelle INTEGER REFERENCES mutuelle(num_mutuelle), numero_adherent TEXT,
                date_debut_adhesion TEXT, date_fin_adhesion TEXT);
            CREATE TABLE couvrir (num_patient INTEGER NOT NULL REFERENCES patient(num_patient),
                num_organisme INTEGER NOT NULL REFERENCES organisme(num_organisme), numero_adherent TEXT,
                date_debut TEXT NOT NULL, date_fin TEXT, taux_prise_en_charge REAL, PRIMARY KEY (num_patient, num_organisme));
            CREATE TABLE personne_confiance (num_personne_confiance INTEGER PRIMARY KEY,
                nom_personne_confiance TEXT NOT NULL, prenom_personne_confiance TEXT NOT NULL,
                telephone_personne_confiance TEXT NOT NULL, lien_avec_patient TEXT);
            CREATE TABLE pre_admission (num_pre_admission INTEGER PRIMARY KEY, date_demande TEXT NOT NULL,
                motif_hospitalisation TEXT NOT NULL, date_entree_prevue TEXT NOT NULL, duree_prevue INTEGER,
                statut TEXT NOT NULL, date_derniere_modification TEXT,
                num_patient INTEGER NOT NULL REFERENCES patient(num_patient),
                num_service INTEGER NOT NULL REFERENCES service(num_service),
                num_personnel_saisie INTEGER NOT NULL REFERENCES personnel(num_personnel),
                num_personne_confiance INTEGER NOT NULL REFERENCES personne_confiance(num_personne_confiance));
        SQL);

        $pdo->exec("INSERT INTO service VALUES (1,'Chirurgie'),(2,'Neurologie')");
        $pdo->exec("INSERT INTO profil VALUES (1,'Secrétaire médicale'),(2,'Médecin'),(3,'Département administratif'),(4,'Informaticien')");
        $pdo->exec("INSERT INTO organisme VALUES (1,'CPAM du Nord')");
        $pdo->exec("INSERT INTO mutuelle VALUES (1,'Harmonie Mutuelle',1),(2,'MGEN',1)");

        $hash = password_hash(self::motDePasse(), PASSWORD_DEFAULT);
        $p = $pdo->prepare('INSERT INTO personnel VALUES (?,?,?,?,?,?)');
        $h = $pdo->prepare("INSERT INTO habilitation (date_debut_validite, date_fin_validite, statut_habilitation, application, num_personnel, num_profil)
                            VALUES ('2024-01-01', ?, 'active', 'pre-admission', ?, ?)");
        $comptes = [
            [1, 'DURAND', 'Sophie', 'secretaire.test', 'actif', 1, null],
            [2, 'MARTIN', 'Paul', 'medecin.test', 'actif', 2, null],
            [3, 'LEROY', 'Claire', 'admin.test', 'actif', 3, null],
            [4, 'PETIT', 'Hugo', 'informaticien.test', 'actif', 4, null],
            [5, 'DUPONT', 'Sarah', 'secretaire2.test', 'actif', 1, null],
            [6, 'ANCIEN', 'Compte', 'desactive.test', 'desactive', 1, null],     // compte désactivé
            [7, 'EXPIRE', 'Habil', 'expire.test', 'actif', 1, '2025-01-01'],    // habilitation terminée
        ];
        foreach ($comptes as [$num, $nom, $prenom, $id, $statut, $profil, $fin]) {
            $p->execute([$num, $nom, $prenom, $id, $hash, $statut]);
            $h->execute([$fin, $num, $profil]);
        }

        $pdo->exec("INSERT INTO patient (num_patient, num_securite_sociale, nom, prenom, date_naissance, adresse, ville, telephone, num_mutuelle, numero_adherent)
                    VALUES (1,'162035912204504','GIRARD','Thomas','1962-03-14','12 rue des Lilas','59400 Cambrai','0612345678',1,'HM-784512')");
        $pdo->exec("INSERT INTO couvrir (num_patient, num_organisme, numero_adherent, date_debut) VALUES (1,1,'162035912204504','2026-01-01')");
        return $pdo;
    }

    /** Utilisateur connecté de test (même forme que la session de l'application). */
    public static function utilisateur(int $num, string $profil): array
    {
        return ['num_personnel' => $num, 'nom' => 'NOM', 'prenom' => 'Prenom', 'profil' => $profil];
    }
}
