# LPFH – Pré-admission V1 (PHP)

Application web PHP + MariaDB/MySQL (PDO, requêtes préparées), tests PHPUnit.

## Contenu

```
AP-SLAM/
├── index.php            connexion
├── preadmissions.php    liste des pré-admissions (secrétaire)
├── nouvelle.php         nouvelle pré-admission (secrétaire)
├── accueil.php          accueil médecin / administratif / informaticien
├── deconnexion.php
├── style.css
├── .htaccess            bloque l'accès direct à app/, sql/, tests/…
├── app/                 code : Auth, PreAdmission, Validation, init, haut/bas de page, config
├── sql/                 schéma de la base + jeu de données fictives
├── tests/               tests unitaires PHPUnit (base SQLite en mémoire)
└── docs/                fiches de recette, scénario de démo
```

## Mettre en ligne (FTP)

1. Envoyer : les 5 pages `.php`, `style.css`, `.htaccess` et le dossier `app/`.
   Inutile d'envoyer `sql/`, `tests/`, `docs/`, `vendor/`.
2. Sur le serveur, copier `app/config.local.exemple.php` en `app/config.local.php` et y mettre
   le serveur MySQL, le nom de la base, le compte applicatif dédié (jamais root) et son mot de passe.
   Ce fichier n'est pas versionné : aucun mot de passe sur GitHub.
3. Base : si elle est vide, importer `sql/lpfh_preadmission.sql` puis `sql/jeu_de_test.sql` (phpMyAdmin).
4. Ouvrir `http://serveur/…/AP-SLAM/index.php`.

## Comptes de test (données fictives)

| Identifiant | Profil | Écran |
|---|---|---|
| secretaire.test | Secrétaire médicale | Pré-admissions + Nouvelle pré-admission |
| secretaire2.test | Secrétaire médicale | (pour tester la liste partagée) |
| medecin.test | Médecin | Espace médecin |
| admin.test | Département administratif | Département administratif |
| informaticien.test | Informaticien | Espace informaticien |

Mot de passe : communiqué par l'équipe, il n'est pas écrit dans le dépôt (seul le hash bcrypt est en base).
Patient de test déjà inscrit : n° de sécu `1 62 03 59 122 045 04` (Thomas GIRARD).

## Tests unitaires

```
composer install
vendor/bin/phpunit
```

31 tests (base SQLite en mémoire, la vraie base n'est jamais touchée) :
- `AuthTest` (14) : connexion des 4 profils, mauvais mot de passe, identifiant inconnu, champs vides, injection SQL,
  compte désactivé, habilitation expirée, accès sans session ou avec un mauvais profil, journal, hachage.
- `PreAdmissionTest` (17) : n° de sécu, recherche patient, nouveau patient, patient existant + changement de mutuelle,
  enregistrement complet avec les liaisons, champs obligatoires, formats invalides, service ou couverture inconnus,
  erreur de base (transaction annulée), refus d'accès pour les autres profils ou sans session, liste partagée.

## Sécurité

- Mots de passe vérifiés avec `password_verify()` (bcrypt) ; message d'erreur générique.
- Session : `session_regenerate_id()` à la connexion, cookie `HttpOnly` + `SameSite=Strict`, détruite à la déconnexion.
- Contrôle de la session et du profil côté serveur sur chaque page et dans chaque méthode de `PreAdmission`.
- Requêtes préparées partout, enregistrement en transaction, jeton anti-CSRF, échappement HTML à l'affichage.
- Contrôles de saisie refaits côté serveur (n° de sécu avec clé, dates, téléphones, champs obligatoires).
- Connexions / déconnexions tracées dans `journal_acces`.
