-- =====================================================================
--  LPFH – Pré-admission : script SQL (MariaDB / MySQL)
--  Généré à partir du MCD (version avec PERSONNE_CONFIANCE)
-- =====================================================================
--  PARTIE A : si ta base existe déjà → exécute UNIQUEMENT la partie A
--  PARTIE B : création complète de la base (base vide / réinstallation)
-- =====================================================================


-- =====================================================================
--  PARTIE A — MISE À JOUR D'UNE BASE EXISTANTE (personne de confiance)
-- =====================================================================
/*
CREATE TABLE personne_confiance (
    num_personne_confiance   INT AUTO_INCREMENT PRIMARY KEY,
    nom_personne_confiance   VARCHAR(50)  NOT NULL,
    prenom_personne_confiance VARCHAR(50) NOT NULL,
    telephone_personne_confiance VARCHAR(20) NOT NULL,
    lien_avec_patient        VARCHAR(50)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Association « Désigner » : PRE_ADMISSION (1,1) — PERSONNE_CONFIANCE (0,N)
-- (NULL autorisé le temps de compléter les pré-admissions déjà en base,
--  passe la colonne en NOT NULL ensuite si tu veux)
ALTER TABLE pre_admission
    ADD COLUMN num_personne_confiance INT NULL,
    ADD CONSTRAINT fk_preadm_personne_confiance
        FOREIGN KEY (num_personne_confiance)
        REFERENCES personne_confiance(num_personne_confiance);
*/


-- =====================================================================
--  PARTIE B — CRÉATION COMPLÈTE
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS journal_acces, habilitation, profil, volet_medical,
    sejour, chambre, pre_admission, personne_confiance, personnel, service,
    presenter, allergie, prendre, medicament, couvrir, avoir, antecedent,
    patient, mutuelle, organisme;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Référentiels ----------
CREATE TABLE service (
    num_service        INT AUTO_INCREMENT PRIMARY KEY,
    libelle_service    VARCHAR(80)  NOT NULL,
    localisation       VARCHAR(80),
    telephone_service  VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE profil (
    num_profil          INT AUTO_INCREMENT PRIMARY KEY,
    libelle_profil      VARCHAR(50) NOT NULL,
    description_profil  VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE organisme (
    num_organisme   INT AUTO_INCREMENT PRIMARY KEY,
    nom_organisme   VARCHAR(80) NOT NULL,
    type_organisme  VARCHAR(50),
    telephone       VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dépendre : MUTUELLE (1,1) — ORGANISME (0,N)
CREATE TABLE mutuelle (
    num_mutuelle        INT AUTO_INCREMENT PRIMARY KEY,
    nom_mutuelle        VARCHAR(80) NOT NULL,
    telephone_mutuelle  VARCHAR(20),
    num_organisme       INT NOT NULL,
    CONSTRAINT fk_mutuelle_organisme FOREIGN KEY (num_organisme) REFERENCES organisme(num_organisme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE antecedent (
    num_antecedent      INT AUTO_INCREMENT PRIMARY KEY,
    libelle_antecedent  VARCHAR(100) NOT NULL,
    type_antecedent     VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE medicament (
    num_medicament      INT AUTO_INCREMENT PRIMARY KEY,
    libelle_medicament  VARCHAR(100) NOT NULL,
    dci                 VARCHAR(100),
    forme               VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE allergie (
    num_allergie      INT AUTO_INCREMENT PRIMARY KEY,
    libelle_allergie  VARCHAR(100) NOT NULL,
    type_allergie     VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Patient ----------
-- Adhérer : PATIENT (0,1) — MUTUELLE (0,N) → clé étrangère dans patient
CREATE TABLE patient (
    num_patient            INT AUTO_INCREMENT PRIMARY KEY,
    num_securite_sociale   VARCHAR(15)  NOT NULL UNIQUE,   -- recherche du patient
    nom                    VARCHAR(50)  NOT NULL,
    prenom                 VARCHAR(50)  NOT NULL,
    date_naissance         DATE         NOT NULL,
    sexe                   CHAR(1),
    adresse                VARCHAR(150),
    ville                  VARCHAR(80),
    telephone              VARCHAR(20),
    email                  VARCHAR(100),
    num_mutuelle           INT NULL,
    numero_adherent        VARCHAR(30) NULL,
    date_debut_adhesion    DATE NULL,
    date_fin_adhesion      DATE NULL,
    CONSTRAINT fk_patient_mutuelle FOREIGN KEY (num_mutuelle) REFERENCES mutuelle(num_mutuelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Couvrir : PATIENT (0,N) — ORGANISME (0,N)
CREATE TABLE couvrir (
    num_patient           INT NOT NULL,
    num_organisme         INT NOT NULL,
    numero_adherent       VARCHAR(30),
    date_debut            DATE NOT NULL,
    date_fin              DATE,
    taux_prise_en_charge  DECIMAL(5,2),
    PRIMARY KEY (num_patient, num_organisme),
    CONSTRAINT fk_couvrir_patient   FOREIGN KEY (num_patient)   REFERENCES patient(num_patient),
    CONSTRAINT fk_couvrir_organisme FOREIGN KEY (num_organisme) REFERENCES organisme(num_organisme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Avoir : PATIENT (0,N) — ANTECEDENT (0,N)
CREATE TABLE avoir (
    num_patient        INT NOT NULL,
    num_antecedent     INT NOT NULL,
    date_constatation  DATE,
    PRIMARY KEY (num_patient, num_antecedent),
    CONSTRAINT fk_avoir_patient    FOREIGN KEY (num_patient)    REFERENCES patient(num_patient),
    CONSTRAINT fk_avoir_antecedent FOREIGN KEY (num_antecedent) REFERENCES antecedent(num_antecedent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Prendre : PATIENT (0,N) — MEDICAMENT (0,N)
CREATE TABLE prendre (
    num_patient     INT NOT NULL,
    num_medicament  INT NOT NULL,
    posologie       VARCHAR(100),
    date_debut      DATE,
    date_fin        DATE,
    PRIMARY KEY (num_patient, num_medicament),
    CONSTRAINT fk_prendre_patient    FOREIGN KEY (num_patient)    REFERENCES patient(num_patient),
    CONSTRAINT fk_prendre_medicament FOREIGN KEY (num_medicament) REFERENCES medicament(num_medicament)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Présenter : PATIENT (0,N) — ALLERGIE (0,N)
CREATE TABLE presenter (
    num_patient   INT NOT NULL,
    num_allergie  INT NOT NULL,
    PRIMARY KEY (num_patient, num_allergie),
    CONSTRAINT fk_presenter_patient  FOREIGN KEY (num_patient)  REFERENCES patient(num_patient),
    CONSTRAINT fk_presenter_allergie FOREIGN KEY (num_allergie) REFERENCES allergie(num_allergie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Personnel & sécurité ----------
-- Affecter : PERSONNEL (1,1) — SERVICE (0,N)
CREATE TABLE personnel (
    num_personnel           INT AUTO_INCREMENT PRIMARY KEY,
    nom                     VARCHAR(50)  NOT NULL,
    prenom                  VARCHAR(50)  NOT NULL,
    identifiant_connexion   VARCHAR(50)  NOT NULL UNIQUE,
    mot_de_passe_hash       VARCHAR(255) NOT NULL,          -- bcrypt / Argon2 (pas dans le MCD, nécessaire pour la connexion)
    email_pro               VARCHAR(100),
    date_arrivee            DATE,
    date_depart             DATE,
    statut_compte           VARCHAR(20) NOT NULL DEFAULT 'actif',
    num_service             INT NOT NULL,
    CONSTRAINT fk_personnel_service FOREIGN KEY (num_service) REFERENCES service(num_service)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Détenir : HABILITATION (1,1) — PERSONNEL (0,N)
-- Porter sur : HABILITATION (1,1) — PROFIL (0,N)
CREATE TABLE habilitation (
    num_habilitation       INT AUTO_INCREMENT PRIMARY KEY,
    date_debut_validite    DATE NOT NULL,
    date_fin_validite      DATE,
    statut_habilitation    VARCHAR(20),
    application            VARCHAR(50),
    num_personnel          INT NOT NULL,
    num_profil             INT NOT NULL,
    CONSTRAINT fk_habilitation_personnel FOREIGN KEY (num_personnel) REFERENCES personnel(num_personnel),
    CONSTRAINT fk_habilitation_profil    FOREIGN KEY (num_profil)    REFERENCES profil(num_profil)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tracer : JOURNAL_ACCES (1,1) — PERSONNEL (0,N)
CREATE TABLE journal_acces (
    num_acces            INT AUTO_INCREMENT PRIMARY KEY,
    date_heure_acces     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    type_action          VARCHAR(50),
    ressource_consultee  VARCHAR(150),
    adresse_ip           VARCHAR(45),
    num_personnel        INT NOT NULL,
    CONSTRAINT fk_journal_personnel FOREIGN KEY (num_personnel) REFERENCES personnel(num_personnel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Personne de confiance (NOUVEAU) ----------
CREATE TABLE personne_confiance (
    num_personne_confiance        INT AUTO_INCREMENT PRIMARY KEY,
    nom_personne_confiance        VARCHAR(50) NOT NULL,
    prenom_personne_confiance     VARCHAR(50) NOT NULL,
    telephone_personne_confiance  VARCHAR(20) NOT NULL,
    lien_avec_patient             VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Pré-admission ----------
-- Concerner : PRE_ADMISSION (1,1) — PATIENT (0,N)
-- Adresser  : PRE_ADMISSION (1,1) — SERVICE (0,N)
-- Saisir    : PRE_ADMISSION (1,1) — PERSONNEL (0,N)  → « Créée par »
-- Valider   : PRE_ADMISSION (0,1) — PERSONNEL (0,N)  + date décision, décision, commentaire
-- Désigner  : PRE_ADMISSION (1,1) — PERSONNE_CONFIANCE (0,N)   (NOUVEAU)
CREATE TABLE pre_admission (
    num_pre_admission           INT AUTO_INCREMENT PRIMARY KEY,
    date_demande                DATE NOT NULL,
    motif_hospitalisation       VARCHAR(255) NOT NULL,
    date_entree_prevue          DATE NOT NULL,
    duree_prevue                INT,                         -- en jours
    statut                      VARCHAR(30) NOT NULL DEFAULT 'chez le medecin',
    date_derniere_modification  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    num_patient                 INT NOT NULL,
    num_service                 INT NOT NULL,
    num_personnel_saisie        INT NOT NULL,
    num_personnel_validation    INT NULL,
    date_decision               DATE NULL,
    decision                    VARCHAR(20) NULL,
    commentaire                 VARCHAR(255) NULL,
    num_personne_confiance      INT NOT NULL,
    CONSTRAINT fk_preadm_patient     FOREIGN KEY (num_patient)              REFERENCES patient(num_patient),
    CONSTRAINT fk_preadm_service     FOREIGN KEY (num_service)              REFERENCES service(num_service),
    CONSTRAINT fk_preadm_saisie      FOREIGN KEY (num_personnel_saisie)     REFERENCES personnel(num_personnel),
    CONSTRAINT fk_preadm_validation  FOREIGN KEY (num_personnel_validation) REFERENCES personnel(num_personnel),
    CONSTRAINT fk_preadm_personne_confiance FOREIGN KEY (num_personne_confiance) REFERENCES personne_confiance(num_personne_confiance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comporter : VOLET_MEDICAL (1,1) — PRE_ADMISSION (0,1) → UNIQUE
-- Compléter : VOLET_MEDICAL (1,1) — PERSONNEL (0,N)
CREATE TABLE volet_medical (
    num_volet_medical      INT AUTO_INCREMENT PRIMARY KEY,
    avis_pre_operatoire    TEXT,
    observations           TEXT,
    date_saisie            DATE,
    statut_volet           VARCHAR(30),
    date_validation        DATE,
    num_pre_admission      INT NOT NULL UNIQUE,
    num_personnel          INT NOT NULL,
    CONSTRAINT fk_volet_preadm    FOREIGN KEY (num_pre_admission) REFERENCES pre_admission(num_pre_admission),
    CONSTRAINT fk_volet_personnel FOREIGN KEY (num_personnel)     REFERENCES personnel(num_personnel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Séjour & chambres ----------
-- Situer : CHAMBRE (1,1) — SERVICE (0,N)
CREATE TABLE chambre (
    num_chambre      INT AUTO_INCREMENT PRIMARY KEY,
    numero_chambre   VARCHAR(10) NOT NULL,
    nombre_lits      INT NOT NULL,
    type_chambre     VARCHAR(30),
    num_service      INT NOT NULL,
    CONSTRAINT fk_chambre_service FOREIGN KEY (num_service) REFERENCES service(num_service)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Effectuer  : SEJOUR (1,1) — PATIENT (0,N)
-- Occuper    : SEJOUR (1,1) — CHAMBRE (0,N)
-- Faire suite: SEJOUR (0,1) — PRE_ADMISSION (0,1) → UNIQUE, NULL autorisé
CREATE TABLE sejour (
    num_sejour         INT AUTO_INCREMENT PRIMARY KEY,
    date_entree        DATE NOT NULL,
    date_sortie        DATE,
    motif_sortie       VARCHAR(255),
    num_patient        INT NOT NULL,
    num_chambre        INT NOT NULL,
    num_pre_admission  INT NULL UNIQUE,
    CONSTRAINT fk_sejour_patient FOREIGN KEY (num_patient)       REFERENCES patient(num_patient),
    CONSTRAINT fk_sejour_chambre FOREIGN KEY (num_chambre)       REFERENCES chambre(num_chambre),
    CONSTRAINT fk_sejour_preadm  FOREIGN KEY (num_pre_admission) REFERENCES pre_admission(num_pre_admission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
