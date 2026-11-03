-- =====================================================================
--  LPFH – Jeu de données de TEST (données 100 % FICTIVES)
--  À exécuter sur une base créée avec lpfh_preadmission.sql (partie B).
--  Mot de passe des 4 comptes de test : Lpfh2026!
--  (stocké uniquement sous forme de hash bcrypt, généré par password_hash())
-- =====================================================================

INSERT INTO profil (num_profil, libelle_profil, description_profil) VALUES
 (1, 'Secrétaire médicale',       'Crée les pré-admissions'),
 (2, 'Médecin',                   'Complète le volet médical'),
 (3, 'Département administratif', 'Valide la prise en charge'),
 (4, 'Informaticien',             'Gère les comptes et habilitations');

INSERT INTO service (num_service, libelle_service, localisation, telephone_service) VALUES
 (1, 'Chirurgie',      'Bâtiment A – 1er étage', '03 27 00 00 01'),
 (2, 'Neurologie',     'Bâtiment B – 2e étage',  '03 27 00 00 02'),
 (3, 'Cardiologie',    'Bâtiment A – 2e étage',  '03 27 00 00 03'),
 (4, 'Administration', 'Bâtiment C – RDC',       '03 27 00 00 04'),
 (5, 'Informatique',   'Bâtiment C – sous-sol',  '03 27 00 00 05');

-- 4 comptes de test (un par profil) — mot de passe : Lpfh2026!
INSERT INTO personnel (num_personnel, nom, prenom, identifiant_connexion, mot_de_passe_hash, email_pro, date_arrivee, statut_compte, num_service) VALUES
 (1, 'DURAND',  'Sophie', 'secretaire.test',    '$2y$10$2T6zrSyQbU2xMzJiisguq.J6eF91OViuCs3KxHAJXXzm6TwcAaVLm', 'secretaire.test@lpfh.test',    '2024-09-01', 'actif', 1),
 (2, 'MARTIN',  'Paul',   'medecin.test',       '$2y$10$2T6zrSyQbU2xMzJiisguq.J6eF91OViuCs3KxHAJXXzm6TwcAaVLm', 'medecin.test@lpfh.test',       '2020-01-06', 'actif', 1),
 (3, 'LEROY',   'Claire', 'admin.test',         '$2y$10$2T6zrSyQbU2xMzJiisguq.J6eF91OViuCs3KxHAJXXzm6TwcAaVLm', 'admin.test@lpfh.test',         '2022-03-14', 'actif', 4),
 (4, 'PETIT',   'Hugo',   'informaticien.test', '$2y$10$2T6zrSyQbU2xMzJiisguq.J6eF91OViuCs3KxHAJXXzm6TwcAaVLm', 'informaticien.test@lpfh.test', '2023-06-01', 'actif', 5),
 (5, 'DUPONT',  'Sarah',  'secretaire2.test',   '$2y$10$2T6zrSyQbU2xMzJiisguq.J6eF91OViuCs3KxHAJXXzm6TwcAaVLm', 'secretaire2.test@lpfh.test',   '2025-01-06', 'actif', 2);

INSERT INTO habilitation (num_habilitation, date_debut_validite, date_fin_validite, statut_habilitation, application, num_personnel, num_profil) VALUES
 (1, '2024-09-01', NULL, 'active', 'pre-admission', 1, 1),
 (2, '2020-01-06', NULL, 'active', 'pre-admission', 2, 2),
 (3, '2022-03-14', NULL, 'active', 'pre-admission', 3, 3),
 (4, '2023-06-01', NULL, 'active', 'pre-admission', 4, 4),
 (5, '2025-01-06', NULL, 'active', 'pre-admission', 5, 1);

INSERT INTO organisme (num_organisme, nom_organisme, type_organisme, telephone) VALUES
 (1, 'CPAM du Nord', 'Régime général', '36 46'),
 (2, 'MSA Nord-Pas de Calais', 'Régime agricole', '03 20 00 00 00');

INSERT INTO mutuelle (num_mutuelle, nom_mutuelle, telephone_mutuelle, num_organisme) VALUES
 (1, 'Harmonie Mutuelle', '09 80 98 08 80', 1),
 (2, 'MGEN',              '36 76',          1),
 (3, 'AG2R La Mondiale',  '09 69 32 20 00', 1);

-- Patients fictifs (n° de sécu fictifs mais avec une clé valide)
INSERT INTO patient (num_patient, num_securite_sociale, nom, prenom, date_naissance, sexe, adresse, ville, telephone, email, num_mutuelle, numero_adherent, date_debut_adhesion) VALUES
 (1, '162035912204504', 'GIRARD',  'Thomas', '1962-03-14', 'H', '12 rue des Lilas',  '59400 Cambrai', '0612345678', 't.girard@exemple.test', 1, 'HM-784512', '2020-01-01'),
 (2, '285075935001238', 'BERNARD', 'Marie',  '1985-07-22', 'F', '3 place d''Armes',  '59400 Cambrai', '0698765432', NULL,                    2, 'MG-102030', '2019-05-01');

INSERT INTO couvrir (num_patient, num_organisme, numero_adherent, date_debut, date_fin, taux_prise_en_charge) VALUES
 (1, 1, '162035912204504', '2026-01-01', '2026-12-31', 70.00),
 (2, 1, '285075935001238', '2026-01-01', '2026-12-31', 70.00);
