<?php
// SEC-01 – Pré-admissions (partagées entre toutes les secrétaires médicales)
require __DIR__ . '/../../src/garde.php';
$utilisateur = exigerProfil([App\Auth::SECRETAIRE]);

$repo   = new App\PreAdmission(App\Database::get());
$filtre = ($_GET['creees_par'] ?? 'toutes') === 'moi' ? 'moi' : 'toutes';
$liste  = $repo->lister($filtre === 'moi' ? $utilisateur['num_personnel'] : null);

$titre = 'Pré-admissions';
$menu  = ['Pré-admissions' => '/secretaire/index.php', 'Nouvelle pré-admission' => '/secretaire/nouvelle.php'];
$actif = '/secretaire/index.php';
require __DIR__ . '/../../src/vue/entete.php';
?>
<div class="actions" style="justify-content:space-between;align-items:flex-start">
    <div><h1>Pré-admissions</h1><p class="sous-titre">Dossiers créés par toutes les secrétaires médicales (partagés entre collègues)</p></div>
    <a class="bouton" href="<?= e(url('/secretaire/nouvelle.php')) ?>">+ Nouvelle pré-admission</a>
</div>

<?php if (isset($_GET['ok'])): ?>
    <p class="alerte succes">Pré-admission n° <?= (int) $_GET['ok'] ?> enregistrée. Le dossier a été transmis au médecin du service.</p>
<?php endif; ?>

<form method="get" class="recherche" style="max-width:300px;margin-bottom:20px">
    <div>
        <label for="creees_par">Créées par</label>
        <select id="creees_par" name="creees_par" onchange="this.form.submit()">
            <option value="toutes" <?= $filtre === 'toutes' ? 'selected' : '' ?>>Toutes les secrétaires</option>
            <option value="moi" <?= $filtre === 'moi' ? 'selected' : '' ?>>Moi uniquement</option>
        </select>
    </div>
</form>

<table>
    <thead><tr><th>PATIENT</th><th>CRÉÉE PAR</th><th>SERVICE</th><th>DATE D'ENTRÉE</th><th>STATUT</th></tr></thead>
    <tbody>
    <?php if (!$liste): ?>
        <tr><td colspan="5">Aucune pré-admission.</td></tr>
    <?php endif; ?>
    <?php foreach ($liste as $pa): ?>
        <tr>
            <td><?= e($pa['patient_prenom'] . ' ' . $pa['patient_nom']) ?></td>
            <td><?= (int) $pa['num_personnel'] === $utilisateur['num_personnel'] ? 'Moi' : e(mb_substr($pa['secretaire_prenom'], 0, 1) . '. ' . $pa['secretaire_nom']) ?></td>
            <td><?= e($pa['libelle_service']) ?></td>
            <td><?= e(date('d/m/Y', strtotime($pa['date_entree_prevue']))) ?></td>
            <td><span class="badge"><?= e(ucfirst(str_replace('medecin', 'médecin', $pa['statut']))) ?></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p class="bandeau" style="margin-top:20px">Toutes les secrétaires voient et peuvent reprendre les pré-admissions de leurs collègues, mais pas le contenu du volet médical.</p>
<?php require __DIR__ . '/../../src/vue/pied.php';
