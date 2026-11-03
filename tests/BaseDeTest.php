<?php
namespace Tests;

use PDO;

/**
 * Base SQLite en mémoire qui reproduit les tables utiles (même noms de colonnes que MariaDB).
 * Les tests ne touchent donc jamais à la vraie base.
 */
final class BaseDeTest
{
    public const MDP = 'Lpfh2026!';

    public static function creer(): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec(<<<SQL
            CREATE TABLE service (num_service INTEGER PRIMARY KEY, libelle_service TEXT NOT NULL, localisation TEXT, telephone_service TEXT);
            CREATE TABLE profil (num_profil INTEGER PRIMARY KEY, libelle_profil TEXT NOT NULL, description_profil TEXT);
            CREATE TABLE organisme (num_organisme INTEGER PRIMARY KEY, nom_organisme TEXT NOT NULL, type_organisme TEXT, telephone TEXT);
            CREATE TABLE mutuelle (num_mutuelle INTEGER PRIMARY KEY, nom_mutuelle TEXT NOT NULL, telephone_mutuelle TEXT,
                num_organisme INTEGER NOT NULL REFERENCES organisme(num_organisme));
            CREATE TABLE personnel (num_personnel INTEGER PRIMARY KEY, nom TEXT NOT NULL, prenom TEXT NOT NULL,
                identifiant_connexion TEXT NOT NULL UNIQUE, mot_de_passe_hash TEXT NOT NULL, email_pro TEXT,
                date_arrivee TEXT, date_depart TEXT, statut_compte TEXT NOT NULL DEFAULT 'actif',
                num_service INTEGER NOT NULL REFERENCES service(num_service));
            CREATE TABLE habilitation (num_habilitation INTEGER PRIMARY KEY, date_debut_validite TEXT NOT NULL, date_fin_validite TEXT,
                statut_habilitation TEXT, application TEXT,
                num_personnel INTEGER NOT NULL REFERENCES personnel(num_personnel), num_profil INTEGER NOT NULL REFERENCES profil(num_profil));
            CREATE TABLE journal_acces (num_acces INTEGER PRIMARY KEY, date_heure_acces TEXT NOT NULL, type_action TEXT,
                ressource_consultee TEXT, adresse_ip TEXT, num_personnel INTEGER NOT NULL REFERENCES personnel(num_personnel));
            CREATE TABLE patient (num_patient INTEGER PRIMARY KEY, num_securite_sociale TEXT NOT NULL UNIQUE, nom TEXT NOT NULL,
                prenom TEXT NOT NULL, date_naissance TEXT NOT NULL, sexe TEXT, adresse TEXT, ville TEXT, telephone TEXT, email TEXT,
                num_mutuelle INTEGER REFERENCES mutuelle(num_mutuelle), numero_adherent TEXT, date_debut_adhesion TEXT, date_fin_adhesion TEXT);
            CREATE TABLE personne_confiance (num_personne_confiance INTEGER PRIMARY KEY, nom_personne_confiance TEXT NOT NULL,
                prenom_personne_confiance TEXT NOT NULL, telephone_personne_confiance TEXT NOT NULL, lien_avec_patient TEXT);
            CREATE TABLE pre_admission (num_pre_admission INTEGER PRIMARY KEY, date_demande TEXT NOT NULL, motif_hospitalisation TEXT NOT NULL,
                date_entree_prevue TEXT NOT NULL, duree_prevue INTEGER, statut TEXT NOT NULL, date_derniere_modification TEXT,
                num_patient INTEGER NOT NULL REFERENCES patient(num_patient), num_service INTEGER NOT NULL REFERENCES service(num_service),
                num_personnel_saisie INTEGER NOT NULL REFERENCES personnel(num_personnel), num_personnel_validation INTEGER,
                date_decision TEXT, decision TEXT, commentaire TEXT,
                num_personne_confiance INTEGER NOT NULL REFERENCES personne_confiance(num_personne_confiance));
        SQL);

        $hash = password_hash(self::MDP, PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO service VALUES (1,'Chirurgie',NULL,NULL),(2,'Administration',NULL,NULL)");
        $pdo->exec("INSERT INTO profil VALUES (1,'Secrétaire médicale',NULL),(2,'Médecin',NULL),(3,'Département administratif',NULL),(4,'Informaticien',NULL)");
        $pdo->exec("INSERT INTO organisme VALUES (1,'CPAM du Nord','Régime général',NULL)");
        $pdo->exec("INSERT INTO mutuelle VALUES (1,'Harmonie Mutuelle',NULL,1),(2,'MGEN',NULL,1)");
        $p = $pdo->prepare('INSERT INTO personnel (num_personnel, nom, prenom, identifiant_connexion, mot_de_passe_hash, statut_compte, num_service) VALUES (?,?,?,?,?,?,?)');
        $p->execute([1, 'DURAND', 'Sophie', 'secretaire.test', $hash, 'actif', 1]);
        $p->execute([2, 'MARTIN', 'Paul', 'medecin.test', $hash, 'actif', 1]);
        $p->execute([3, 'LEROY', 'Claire', 'admin.test', $hash, 'actif', 2]);
        $p->execute([4, 'PETIT', 'Hugo', 'informaticien.test', $hash, 'actif', 2]);
        $p->execute([5, 'ANCIEN', 'Compte', 'desactive.test', $hash, 'desactive', 1]);   // compte désactivé
        $p->execute([6, 'EXPIRE', 'Habil', 'expire.test', $hash, 'actif', 1]);            // habilitation terminée
        $h = $pdo->prepare('INSERT INTO habilitation (date_debut_validite, date_fin_validite, statut_habilitation, application, num_personnel, num_profil) VALUES (?,?,?,?,?,?)');
        $h->execute(['2024-01-01', null, 'active', 'pre-admission', 1, 1]);
        $h->execute(['2024-01-01', null, 'active', 'pre-admission', 2, 2]);
        $h->execute(['2024-01-01', null, 'active', 'pre-admission', 3, 3]);
        $h->execute(['2024-01-01', null, 'active', 'pre-admission', 4, 4]);
        $h->execute(['2024-01-01', null, 'active', 'pre-admission', 5, 1]);
        $h->execute(['2024-01-01', '2025-01-01', 'active', 'pre-admission', 6, 1]);
        $pdo->exec("INSERT INTO patient (num_patient, num_securite_sociale, nom, prenom, date_naissance, adresse, ville, telephone, num_mutuelle, numero_adherent)
                    VALUES (1,'162035912204504','GIRARD','Thomas','1962-03-14','12 rue des Lilas','59400 Cambrai','0612345678',1,'HM-784512')");
        return $pdo;
    }
}
