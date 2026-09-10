<?php
require_once '../../config/database.php';
require_once '../../includes/verifier_admin.php';
require_once '../../models/Membre.php';
require_once '../../includes/header.php';

$database = new Database();
$db = $database->connecter();

$membreModel = new Membre($db);
$message = "";

// Changement de statut (suspendre / réactiver)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_membre'])) {
    $nouveau_statut = $_POST['nouveau_statut'];
    $membreModel->changerStatut($_POST['id_membre'], $nouveau_statut);
    $message = "Statut mis à jour avec succès.";
}

$membres = $membreModel->getTousLesMembres();
?>

<h1>Gestion des membres</h1>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<table border="1" cellpadding="8">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Inscrit le</th>
            <th>Pénalités impayées</th>
            <th>Statut</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($membres as $membre): ?>
            <tr>
                <td><?= htmlspecialchars($membre['prenom'] . ' ' . $membre['nom']) ?></td>
                <td><?= htmlspecialchars($membre['email']) ?></td>
                <td><?= htmlspecialchars($membre['telephone']) ?></td>
                <td><?= htmlspecialchars($membre['date_inscription']) ?></td>
                <td>
                    <?php if ($membre['penalites_impayees'] > 0): ?>
                        <span style="color: red;"><?= number_format($membre['penalites_impayees'], 0) ?> FCFA</span>
                    <?php else: ?>
                        0 FCFA
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($membre['statut'] === 'actif'): ?>
                        <span style="color: green;">Actif</span>
                    <?php else: ?>
                        <span style="color: red;">Suspendu</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_membre" value="<?= $membre['id_membre'] ?>">
                        <?php if ($membre['statut'] === 'actif'): ?>
                            <input type="hidden" name="nouveau_statut" value="suspendu">
                            <button type="submit">Suspendre</button>
                        <?php else: ?>
                            <input type="hidden" name="nouveau_statut" value="actif">
                            <button type="submit">Réactiver</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once '../../includes/footer.php'; ?>