<?php
// Liste des pré-admissions, partagée entre les secrétaires médicales.
require __DIR__ . '/app/init.php';
$u = exigerProfil([Auth::SECRETAIRE]);

$miennes = ($_GET['filtre'] ?? '') === 'moi';
$lignes = (new PreAdmission(bdd()))->lister($u, $miennes);
$succes = $_SESSION['succes'] ?? null;
unset($_SESSION['succes']);

$titre = 'Pré-admissions';
$actif = 'preadmissions.php';
require __DIR__ . '/app/haut.php';
?>
<div class="entete">
    <div>
        <h1>Pré-admissions</h1>
        <p class="sous-titre">Dossiers créés par toutes les secrétaires médicales (partagés entre collègues)</p>
    </div>
    <a class="bouton" href="nouvelle.php">+ Nouvelle pré-admission</a>
</div>

<?php if ($succes): ?><p class="alerte succes"><?= e($succes) ?></p><?php endif; ?>

<form method="get" class="filtre">
    <label for="filtre">Créées par</label>
    <select id="filtre" name="filtre" onchange="this.form.submit()">
        <option value="">Toutes les secrétaires</option>
        <option value="moi" <?= $miennes ? 'selected' : '' ?>>Moi uniquement</option>
    </select>
    <noscript><button class="bouton secondaire">Filtrer</button></noscript>
</form>

<table>
    <thead><tr><th>Patient</th><th>Créée par</th><th>Service</th><th>Date d'entrée</th><th>Statut</th></tr></thead>
    <tbody>
    <?php foreach ($lignes as $l): ?>
        <tr>
            <td><?= e($l['p_prenom'] . ' ' . $l['p_nom']) ?></td>
            <td><?= (int) $l['num_personnel'] === $u['num_personnel'] ? 'Moi' : e(mb_substr($l['s_prenom'], 0, 1) . '. ' . $l['s_nom']) ?></td>
            <td><?= e($l['libelle_service']) ?></td>
            <td><?= e(dateFr($l['date_entree_prevue'])) ?></td>
            <td><?= e($l['statut'] === 'chez le medecin' ? 'chez le médecin' : $l['statut']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$lignes): ?><tr><td colspan="5" class="vide">Aucune pré-admission.</td></tr><?php endif; ?>
    </tbody>
</table>
<div class="bandeau">Toutes les secrétaires voient et peuvent reprendre les pré-admissions de leurs collègues, mais pas le contenu du volet médical.</div>
<?php require __DIR__ . '/app/bas.php';
