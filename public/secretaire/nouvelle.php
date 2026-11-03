<?php
// SEC-02 – Nouvelle pré-admission (réservée au profil secrétaire médicale)
require __DIR__ . '/../../src/garde.php';
$utilisateur = exigerProfil([App\Auth::SECRETAIRE]);

use App\PreAdmission;
use App\Session;

$repo      = new PreAdmission(App\Database::get());
$services  = $repo->listerServices();
$mutuelles = $repo->listerMutuelles();

$d = [];            // valeurs du formulaire
$erreurs = [];
$patient = null;
$recherche = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = $_POST;
    if (!Session::verifierCsrf($_POST['csrf'] ?? null)) {
        $erreurs['global'] = 'Session expirée, merci de réessayer.';
    } elseif (($_POST['action'] ?? '') === 'enregistrer') {
        $res = $repo->enregistrer($_POST, $utilisateur['num_personnel']);
        if ($res['ok']) {
            header('Location: ' . url('/secretaire/index.php?ok=' . $res['num_pre_admission']));
            exit;
        }
        $erreurs = $res['erreurs'];
    }
    // Recherche du patient par n° de sécu (bouton Rechercher ou réaffichage après erreur)
    if (!empty($d['num_securite_sociale'])) {
        $recherche = true;
        $patient = $repo->trouverPatient($d['num_securite_sociale']);
        if ($patient && ($_POST['action'] ?? '') === 'rechercher') {
            $d['num_mutuelle']    = $patient['num_mutuelle'];
            $d['numero_adherent'] = $patient['numero_adherent'];
        }
    }
}

$v  = fn(string $k) => e((string) ($patient[$k] ?? $d[$k] ?? ''));
$ro = $patient ? 'readonly' : '';
$err = fn(string $k) => isset($erreurs[$k]) ? '<small style="color:var(--rouge)">' . e($erreurs[$k]) . '</small>' : '';

$titre = 'Nouvelle pré-admission';
$menu  = ['Pré-admissions' => '/secretaire/index.php', 'Nouvelle pré-admission' => '/secretaire/nouvelle.php'];
$actif = '/secretaire/nouvelle.php';
require __DIR__ . '/../../src/vue/entete.php';
?>
<h1>Nouvelle pré-admission</h1>
<p class="sous-titre">Recherchez d'abord le patient par son n° de sécu. S'il n'est pas inscrit, remplissez ses informations. Les champs avec * sont obligatoires.</p>

<?php if ($erreurs): ?>
    <div class="alerte erreur" role="alert">La pré-admission n'a pas été enregistrée :
        <ul><?php foreach ($erreurs as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/secretaire/nouvelle.php')) ?>" novalidate>
    <input type="hidden" name="csrf" value="<?= e(Session::jetonCsrf()) ?>">

    <section class="carte">
        <h2>Rechercher le patient
            <?php if ($recherche && $patient): ?><span class="badge vert">✓ Patient déjà inscrit – infos pré-remplies</span>
            <?php elseif ($recherche): ?><span class="badge orange">Patient non trouvé – remplissez ses informations</span><?php endif; ?>
        </h2>
        <div class="recherche">
            <div>
                <label for="num_securite_sociale">N° de sécurité sociale *</label>
                <input id="num_securite_sociale" name="num_securite_sociale" value="<?= e((string) ($d['num_securite_sociale'] ?? '')) ?>" required maxlength="21">
                <?= $err('num_securite_sociale') ?>
            </div>
            <button class="bouton" name="action" value="rechercher" formnovalidate>Rechercher</button>
        </div>
    </section>

    <section class="carte">
        <h2>Patient</h2>
        <div class="grille">
            <div><label>Nom *</label><input name="nom" value="<?= $v('nom') ?>" <?= $ro ?> required><?= $err('nom') ?></div>
            <div><label>Prénom *</label><input name="prenom" value="<?= $v('prenom') ?>" <?= $ro ?> required><?= $err('prenom') ?></div>
            <div><label>Date de naissance *</label><input type="date" name="date_naissance" value="<?= $v('date_naissance') ?>" <?= $ro ?> required><?= $err('date_naissance') ?></div>
            <div><label>Adresse *</label><input name="adresse" value="<?= $v('adresse') ?>" <?= $ro ?> required><?= $err('adresse') ?></div>
            <div><label>Ville *</label><input name="ville" value="<?= $v('ville') ?>" <?= $ro ?> required><?= $err('ville') ?></div>
            <div><label>Téléphone *</label><input type="tel" name="telephone" value="<?= $v('telephone') ?>" <?= $ro ?> required><?= $err('telephone') ?></div>
            <div><label>Email</label><input type="email" name="email" value="<?= $v('email') ?>" <?= $ro ?>><?= $err('email') ?></div>
        </div>
    </section>

    <section class="carte">
        <h2>Hospitalisation</h2>
        <div class="grille">
            <div><label>Service *</label>
                <select name="num_service" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int) $s['num_service'] ?>" <?= (string) ($d['num_service'] ?? '') === (string) $s['num_service'] ? 'selected' : '' ?>><?= e($s['libelle_service']) ?></option>
                    <?php endforeach; ?>
                </select><?= $err('num_service') ?></div>
            <div><label>Date d'entrée *</label><input type="date" name="date_entree_prevue" value="<?= e((string) ($d['date_entree_prevue'] ?? '')) ?>" required><?= $err('date_entree_prevue') ?></div>
            <div><label>Durée prévue (jours)</label><input type="number" min="0" max="365" name="duree_prevue" value="<?= e((string) ($d['duree_prevue'] ?? '')) ?>"><?= $err('duree_prevue') ?></div>
            <div class="large"><label>Motif *</label><input name="motif_hospitalisation" maxlength="255" value="<?= e((string) ($d['motif_hospitalisation'] ?? '')) ?>" required><?= $err('motif_hospitalisation') ?></div>
        </div>
    </section>

    <section class="carte">
        <h2>Mutuelle <?php if ($patient): ?><span class="badge orange">⚠ Vérifier avec le patient si sa mutuelle a changé</span><?php endif; ?></h2>
        <div class="grille">
            <div><label>Nom de la mutuelle</label>
                <select name="num_mutuelle">
                    <option value="">— Aucune —</option>
                    <?php foreach ($mutuelles as $m): ?>
                        <option value="<?= (int) $m['num_mutuelle'] ?>" <?= (string) ($d['num_mutuelle'] ?? '') === (string) $m['num_mutuelle'] ? 'selected' : '' ?>><?= e($m['nom_mutuelle']) ?></option>
                    <?php endforeach; ?>
                </select><?= $err('num_mutuelle') ?></div>
            <div><label>N° d'adhérent</label><input name="numero_adherent" value="<?= e((string) ($d['numero_adherent'] ?? '')) ?>"></div>
        </div>
    </section>

    <section class="carte">
        <h2>Personne à prévenir</h2>
        <div class="grille">
            <div><label>Nom *</label><input name="pc_nom" value="<?= e((string) ($d['pc_nom'] ?? '')) ?>" required><?= $err('pc_nom') ?></div>
            <div><label>Prénom *</label><input name="pc_prenom" value="<?= e((string) ($d['pc_prenom'] ?? '')) ?>" required><?= $err('pc_prenom') ?></div>
            <div><label>Téléphone *</label><input type="tel" name="pc_telephone" value="<?= e((string) ($d['pc_telephone'] ?? '')) ?>" required><?= $err('pc_telephone') ?></div>
            <div><label>Lien avec le patient</label><input name="pc_lien" value="<?= e((string) ($d['pc_lien'] ?? '')) ?>" placeholder="ex. épouse, fils…"></div>
        </div>
    </section>

    <section class="carte" style="background:var(--clair)">
        <h2>Volet médical <span class="badge">Accès restreint</span></h2>
        <p style="margin:0;color:var(--gris);font-size:13px">Rempli par le médecin du service. La secrétaire n'y a pas accès.</p>
    </section>

    <div class="actions">
        <a class="bouton secondaire" href="<?= e(url('/secretaire/index.php')) ?>">Annuler</a>
        <button class="bouton" name="action" value="enregistrer">Enregistrer</button>
    </div>
</form>
<?php require __DIR__ . '/../../src/vue/pied.php';
