# LPFH – Pré-admission V1 (Java)

Application bureau **Java 21 + Swing**, architecture **MVC**, accès base **JDBC** (MariaDB/MySQL), tests **JUnit 5**.
Projet **Maven** : IntelliJ télécharge tout seul les librairies (pilote MySQL, BCrypt, JUnit).

## Lancer le projet dans IntelliJ

1. **File → Open** → choisir le dossier du projet (celui qui contient `pom.xml`) → « Open as Project ».
2. Attendre qu'IntelliJ ait fini de charger Maven (barre en bas à droite).
3. Base de données (XAMPP) : démarrer **MySQL**, puis dans phpMyAdmin créer la base `clinique_lfph` et importer
   `sql/lpfh_preadmission.sql` puis `sql/jeu_de_test.sql`.
4. Vérifier `src/main/resources/db.properties` (par défaut : localhost, root, sans mot de passe = XAMPP).
5. Ouvrir `src/main/java/fr/lpfh/Main.java` → clic sur la flèche verte ▶ à côté de `main`.

## Comptes de test (données fictives)

| Identifiant | Profil | Écran |
|---|---|---|
| secretaire.test | Secrétaire médicale | Pré-admissions + Nouvelle pré-admission |
| secretaire2.test | Secrétaire médicale | (pour tester la liste partagée) |
| medecin.test | Médecin | Espace médecin |
| admin.test | Département administratif | Département administratif |
| informaticien.test | Informaticien | Espace informaticien |

Mot de passe : `Lpfh2026!` (seul le hash BCrypt est stocké en base).
Patient de test déjà inscrit : n° de sécu `1 62 03 59 122 045 04` (Thomas GIRARD).

## Tests unitaires

Clic droit sur le dossier `src/test/java` → **Run 'All Tests'** (ou `mvn test`).
Les tests utilisent des faux DAO en mémoire : ils ne touchent jamais à la vraie base.

- `AuthServiceTest` (17 tests) : connexion des 4 profils, mauvais mot de passe, identifiant inconnu, champs vides,
  injection SQL, compte désactivé, habilitation expirée, accès sans session, mauvais profil, déconnexion, journal, hachage.
- `PreAdmissionServiceTest` (8 tests) : n° de sécu, recherche patient, nouveau patient, patient existant + changement de mutuelle,
  champ obligatoire manquant, formats invalides, refus d'accès pour un autre profil ou sans session.

## Organisation (MVC)

```
src/main/java/fr/lpfh/
├── Main.java                 point d'entrée
├── modele/                   Profil, Utilisateur, Patient, PreAdmissionSaisie…
├── dao/                      accès base (JDBC, requêtes préparées) : UtilisateurDAO, PreAdmissionDAO
├── service/                  règles métier : AuthService (connexion + contrôle d'accès), Validation, PreAdmissionService
├── vue/                      écrans Swing (couleurs des maquettes Figma)
└── controleur/               relie les vues aux services
```

## Sécurité mise en place

- Mot de passe vérifié avec **BCrypt** (compatible avec les hash `$2y$` déjà en base) ; jamais stocké en clair ni gardé en mémoire de session.
- Message d'erreur **générique** (ne dit pas si c'est l'identifiant ou le mot de passe qui est faux).
- **Contrôle d'accès dans le code** (`AuthService.exiger(Profil...)`) avant chaque écran et chaque enregistrement — pas seulement en cachant des boutons.
- **Requêtes préparées** partout (aucune concaténation de saisie dans le SQL) ; enregistrement en **transaction** (rien n'est enregistré si une étape échoue).
- Contrôles de saisie refaits avant l'enregistrement (n° de sécu avec clé, dates, téléphones, champs obligatoires).
- Connexions / déconnexions tracées dans `journal_acces`.
