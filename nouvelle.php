<?php
// Nouvelle pré-admission : recherche du patient par n° de sécu, saisie, contrôles côté serveur, enregistrement.
require __DIR__ . '/app/init.php';
$u = exigerProfil([Auth::SECRETAIRE]);

$service = new PreAdmission(bdd());
$listes = $service->listes($u);

$v = array_fill_keys(PreAdmission::CHAMPS, '');   // valeurs affichées dans le formulaire
$erreurs = [];
$patient = null;
$rechercheFaite = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (PreAdmission::CHAMPS as $champ) {
        $v[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }
    if (!csrfValide()) {
        $erreurs['global'] = 'Session expirée, merci de réessayer.';
    } elseif (!Validation::numSecuValide($v['num_secu'])) {
        $erreurs['num_secu'] = Validation::vide($v['num_secu'])
            ? 'N° de sécurité sociale : champ obligatoire.'
            : 'N° de sécurité sociale invalide (15 chiffres, clé incorrecte).';
        if (($_POST['action'] ?? '') === 'enregistrer') {
            $erreurs += $service->enregistrer($u, $v)['erreurs'];
        }
    } else {
        $patient = $service->rechercherPatient($u, $v['num_secu']);
        $rechercheFaite = true;

        if (($_POST['action'] ?? '') === 'enregistrer') {
            $r = $service->enregistrer($u, $v);
            if ($r['ok']) {
                $_SESSION['succes'] = "Pré-admission n° {$r['num']} enregistrée"
                    . ($r['nouveau_patient'] ? ' (nouveau patient inscrit)' : '')
                    . '. Le dossier a été transmis au médecin du service.';
                header('Location: preadmissions.php');
                exit;
            }
            $erreurs = $r['erreurs'];
        } elseif ($patient !== null) {
            // Recherche : on reprend la caisse et la mutuelle connues du patient
            $v['organisme'] = (string) ($patient['num_organisme'] ?? '');
            $v['mutuelle'] = (string) ($patient['num_mutuelle'] ?? '');
            $v['numero_adherent'] = (string) ($patient['numero_adherent'] ?? '');
        }
    }
}

// Patient déjà inscrit : ses informations viennent de la base et ne sont pas modifiables ici
if ($patient !== null) {
    $v['nom'] = $patient['nom'];
    $v['prenom'] = $patient['prenom'];
    $v['date_naissance'] = dateFr($patient['date_naissance']);
    $v['adresse'] = $patient['adresse'];
    $v['ville'] = $patient['ville'];
    $v['telephone'] = $patient['telephone'];
    $v['email'] = (string) $patient['email'];
}
$verrou = $patient !== null ? 'readonly' : '';

/** Champ texte avec son libellé ; bordure rouge s'il est en erreur. */
function champ(string $nom, string $libelle, array $v, array $erreurs, string $attributs = ''): string
{
    return '<div><label for="' . $nom . '">' . e($libelle) . '</label>'
         . '<input type="text" id="' . $nom . '" name="' . $nom . '" value="' . e($v[$nom]) . '" '
         . (isset($erreurs[$nom]) ? 'class="invalide" ' : '') . $attributs . '></div>';
}

/** Liste déroulante (service, caisse, mutuelle). */
function liste(string $nom, string $libelle, array $options, string $vide, array $v, array $erreurs): string
{
    $html = '<div><label for="' . $nom . '">' . e($libelle) . '</label><select id="' . $nom . '" name="' . $nom . '"'
          . (isset($erreurs[$nom]) ? ' class="invalide"' : '') . '><option value="">' . e($vide) . '</option>';
    foreach ($options as $id => $texte) {
        $html .= '<option value="' . $id . '"' . ((string) $id === $v[$nom] ? ' selected' : '') . '>' . e($texte) . '</option>';
    }
    return $html . '</select></div>';
}

$titre = 'Nouvelle pré-admission';
$actif = 'nouvelle.php';
require __DIR__ . '/app/haut.php';
?>
<h1>Nouvelle pré-admission</h1>
<p class="sous-titre">Recherchez d'abord le patient par son n° de sécu. S'il n'est pas inscrit, remplissez ses informations. Les champs avec * sont obligatoires.</p>

<?php if ($erreurs): ?>
    <div class="alerte erreur" role="alert"><strong>La pré-admission n'a pas été enregistrée :</strong>
        <ul><?php foreach ($erreurs as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="nouvelle.php">
    <input type="hidden" name="csrf" value="<?= e(jetonCsrf()) ?>">

    <section class="carte">
        <h2>Rechercher le patient
            <?php if ($patient !== null): ?><span class="badge vert">✓ Patient déjà inscrit – infos pré-remplies</span>
            <?php elseif ($rechercheFaite): ?><span class="badge orange">Patient non trouvé – remplissez ses informations</span><?php endif; ?>
        </h2>
        <div class="recherche">
            <?= champ('num_secu', 'N° de sécurité sociale *', $v, $erreurs) ?>
            <button type="submit" name="action" value="rechercher" class="bouton">Rechercher</button>
        </div>
    </section>

    <section class="carte">
        <h2>Patient</h2>
        <div class="grille">
            <?= champ('nom', 'Nom *', $v, $erreurs, $verrou) ?>
            <?= champ('prenom', 'Prénom *', $v, $erreurs, $verrou) ?>
            <?= champ('date_naissance', 'Date de naissance * (JJ/MM/AAAA)', $v, $erreurs, $verrou) ?>
            <?= champ('adresse', 'Adresse *', $v, $erreurs, $verrou) ?>
            <?= champ('ville', 'Ville *', $v, $erreurs, $verrou) ?>
            <?= champ('telephone', 'Téléphone *', $v, $erreurs, $verrou) ?>
            <?= champ('email', 'Email', $v, $erreurs, $verrou) ?>
        </div>
    </section>

    <section class="carte">
        <h2>Hospitalisation</h2>
        <div class="grille">
            <?= liste('service', 'Service *', $listes['service'], '— Choisir —', $v, $erreurs) ?>
            <?= champ('date_entree', "Date d'entrée * (JJ/MM/AAAA)", $v, $erreurs) ?>
            <?= champ('duree', 'Durée prévue (jours)', $v, $erreurs) ?>
            <div class="large"><?= champ('motif', 'Motif *', $v, $erreurs) ?></div>
        </div>
    </section>

    <section class="carte">
        <h2>Assurance et mutuelle
            <?php if ($patient !== null): ?><span class="badge orange">⚠ Vérifier avec le patient si sa mutuelle a changé</span><?php endif; ?>
        </h2>
        <div class="grille">
            <?= liste('organisme', "Caisse d'assurance maladie", $listes['organisme'], '— Aucune —', $v, $erreurs) ?>
            <?= liste('mutuelle', 'Mutuelle', $listes['mutuelle'], '— Aucune —', $v, $erreurs) ?>
            <?= champ('numero_adherent', "N° d'adhérent mutuelle", $v, $erreurs) ?>
        </div>
    </section>

    <section class="carte">
        <h2>Personne à prévenir</h2>
        <div class="grille">
            <?= champ('pc_nom', 'Nom *', $v, $erreurs) ?>
            <?= champ('pc_prenom', 'Prénom *', $v, $erreurs) ?>
            <?= champ('pc_telephone', 'Téléphone *', $v, $erreurs) ?>
            <?= champ('pc_lien', 'Lien avec le patient', $v, $erreurs) ?>
        </div>
    </section>

    <section class="carte grise">
        <h2>Volet médical <span class="badge">Accès restreint</span></h2>
        <p class="sous-titre">Rempli par le médecin du service. La secrétaire n'y a pas accès.</p>
    </section>

    <div class="actions">
        <a class="bouton secondaire" href="preadmissions.php">Annuler</a>
        <button type="submit" name="action" value="enregistrer" class="bouton">Enregistrer</button>
    </div>
</form>
<?php require __DIR__ . '/app/bas.php';
