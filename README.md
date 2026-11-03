# LPFH – Pré-admission V1 (Sprint 3 – Séance 7)

PHP 8.1+ natif, PDO (requêtes préparées), MariaDB/MySQL, PHPUnit.

## Contenu

| Élément | Fichiers |
|---|---|
| Authentification par profil | `public/index.php` (connexion), `public/deconnexion.php`, `src/Auth.php`, `src/Session.php` |
| Contrôle d'accès côté serveur | `src/garde.php` → `exigerProfil([...])` en haut de chaque page protégée |
| Écrans des 4 profils | `public/secretaire/`, `public/medecin/`, `public/admin/`, `public/informaticien/` |
| Pré-admission (début) | `public/secretaire/nouvelle.php` + `src/PreAdmission.php` |
| Liste partagée entre secrétaires | `public/secretaire/index.php` (filtre « Créées par ») |
| Tests unitaires | `tests/AuthTest.php` (15 tests), `tests/PreAdmissionTest.php` (6 tests) |
| SQL | `sql/lpfh_preadmission.sql` (schéma) + `sql/jeu_de_test.sql` (données fictives) |
| Recette | `docs/Fiches_recette_authentification.docx` |

## Installation

1. Copie le dossier dans ton projet (ou dans `htdocs`/`www`).
2. Base : si elle est vide, exécute `sql/lpfh_preadmission.sql` (partie B), puis `sql/jeu_de_test.sql`.
   Si tu as déjà des données, ajoute seulement les comptes de test de `jeu_de_test.sql` qui te manquent.
3. Adapte `config/config.php` (nom de la base : `clinique_lfph`, utilisateur, mot de passe).
4. Lance le serveur en pointant sur le dossier `public` :
   ```
   php -S localhost:8000 -t public
   ```
   puis ouvre http://localhost:8000

## Comptes de test (données fictives)

| Identifiant | Profil | Écran |
|---|---|---|
| secretaire.test | Secrétaire médicale | /secretaire/index.php |
| secretaire2.test | Secrétaire médicale | (pour tester la liste partagée) |
| medecin.test | Médecin | /medecin/index.php |
| admin.test | Département administratif | /admin/index.php |
| informaticien.test | Informaticien | /informaticien/index.php |

Mot de passe : `Lpfh2026!` (seul le hash bcrypt est stocké en base).
Patient de test déjà inscrit : n° de sécu `1 62 03 59 122 045 04` (Thomas GIRARD).

## Tests unitaires

```
composer install
vendor/bin/phpunit
```
Les tests utilisent une base SQLite en mémoire : ils ne modifient jamais ta vraie base.

## Ajouter une page protégée

```php
<?php
require __DIR__ . '/../../src/garde.php';
$utilisateur = exigerProfil([App\Auth::SECRETAIRE]); // sans session → connexion ; autre profil → 403
```

## Sécurité mise en place

- Mots de passe vérifiés avec `password_verify()` (bcrypt) ; hash jamais stocké en session.
- Message d'erreur générique (ne dit pas si c'est l'identifiant ou le mot de passe).
- `session_regenerate_id()` à la connexion, cookie `HttpOnly` + `SameSite=Strict`, session détruite à la déconnexion.
- Contrôle de la session ET du profil côté serveur sur chaque page.
- Requêtes préparées partout (aucune concaténation SQL), jeton anti-CSRF sur les formulaires, échappement HTML à l'affichage.
- Contrôles de saisie refaits côté serveur (n° de sécu avec clé, dates, téléphones, champs obligatoires).
- Connexions/déconnexions tracées dans `journal_acces`.
