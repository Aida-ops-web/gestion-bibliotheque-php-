<?php
require_once '../../config/database.php';
require_once '../../includes/verifier_admin.php';
require_once '../../models/Statistique.php';
require_once '../../includes/header.php';

$database = new Database();
$db = $database->connecter();

$statModel = new Statistique($db);
$chiffres = $statModel->getChiffresClés();
$topLivres = $statModel->getLivresLesPlusEmpruntes();
$repartition = $statModel->getRepartitionParCategorie();
?>

<h1>Statistiques</h1>

<h2>Chiffres clés</h2>
<table border="1" cellpadding="8">
    <tr>
        <td><strong>Total livres</strong></td>
        <td><?= $chiffres['total_livres'] ?></td>
    </tr>
    <tr>
        <td><strong>Total exemplaires</strong></td>
        <td><?= $chiffres['total_exemplaires'] ?> (dont <?= $chiffres['exemplaires_disponibles'] ?> disponibles)</td>
    </tr>
    <tr>
        <td><strong>Total membres</strong></td>
        <td><?= $chiffres['total_membres'] ?></td>
    </tr>
    <tr>
        <td><strong>Emprunts en cours</strong></td>
        <td><?= $chiffres['emprunts_en_cours'] ?></td>
    </tr>
    <tr>
        <td><strong>Emprunts en retard</strong></td>
        <td style="color: <?= $chiffres['emprunts_en_retard'] > 0 ? 'red' : 'inherit' ?>;">
            <?= $chiffres['emprunts_en_retard'] ?>
        </td>
    </tr>
    <tr>
        <td><strong>Pénalités impayées (total)</strong></td>
        <td><?= number_format($chiffres['penalites_impayees_total'], 0) ?> FCFA</td>
    </tr>
</table>

<h2>Top 5 des livres les plus empruntés</h2>
<?php if (empty($topLivres)): ?>
    <p>Aucun emprunt enregistré pour le moment.</p>
<?php else: ?>
    <table border="1" cellpadding="8">
        <thead>
            <tr><th>Livre</th><th>Nombre d'emprunts</th></tr>
        </thead>
        <tbody>
            <?php foreach ($topLivres as $livre): ?>
                <tr>
                    <td><?= htmlspecialchars($livre['titre']) ?></td>
                    <td><?= $livre['nb_emprunts'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <canvas id="graphTopLivres" style="max-width: 500px; margin: 1rem 0;"></canvas>
<?php endif; ?>

<h2>Répartition des livres par catégorie</h2>
<table border="1" cellpadding="8">
    <thead>
        <tr><th>Catégorie</th><th>Nombre de livres</th></tr>
    </thead>
    <tbody>
        <?php foreach ($repartition as $cat): ?>
            <tr>
                <td><?= htmlspecialchars($cat['nom_categorie']) ?></td>
                <td><?= $cat['nb_livres'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<canvas id="graphCategories" style="max-width: 400px; margin: 1rem 0;"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Données envoyées depuis PHP vers JavaScript
const labelsCategories = <?= json_encode(array_column($repartition, 'nom_categorie')) ?>;
const donneesCategories = <?= json_encode(array_column($repartition, 'nb_livres')) ?>;

const labelsTop = <?= json_encode(array_column($topLivres, 'titre')) ?>;
const donneesTop = <?= json_encode(array_column($topLivres, 'nb_emprunts')) ?>;

// Camembert : répartition par catégorie
new Chart(document.getElementById('graphCategories'), {
    type: 'pie',
    data: {
        labels: labelsCategories,
        datasets: [{
            data: donneesCategories,
            backgroundColor: ['#2C4A3E', '#4A7A63', '#B8862F', '#A33B2E', '#6B6255', '#8FA98C']
        }]
    }
});

<?php if (!empty($topLivres)): ?>
// Histogramme : top 5 des livres les plus empruntés
new Chart(document.getElementById('graphTopLivres'), {
    type: 'bar',
    data: {
        labels: labelsTop,
        datasets: [{
            label: "Nombre d'emprunts",
            data: donneesTop,
            backgroundColor: '#2C4A3E'
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
<?php endif; ?>
</script>

<?php require_once '../../includes/footer.php'; ?>