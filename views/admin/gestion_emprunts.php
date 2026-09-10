<?php
require_once '../../config/database.php';
require_once '../../includes/verifier_admin.php';
require_once '../../models/Emprunt.php';
require_once '../../includes/header.php';

$database = new Database();
$db = $database->connecter();

$empruntModel = new Emprunt($db);
require_once '../../models/Notification.php';
$notificationModel = new Notification($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generer_notifications'])) {
    $nb = $notificationModel->genererNotificationsAutomatiques();
    $message = "$nb notification(s) générée(s).";
}
$message = "";

// Traitement d'un retour effectué par le bibliothécaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_emprunt'])) {
    $succes = $empruntModel->retournerLivreAdmin($_POST['id_emprunt']);
    $message = $succes ? "Retour enregistré avec succès !" : "Une erreur est survenue.";
}

$emprunts = $empruntModel->getTousLesEmprunts();

// Petite fonction utilitaire pour savoir si un emprunt en cours est en retard
function estEnRetard($emprunt) {
    return $emprunt['statut'] === 'en_cours' && $emprunt['date_retour_prevue'] < date('Y-m-d');
}
?>

<h1>Gestion des emprunts</h1>
<form method="POST">
    <button type="submit" name="generer_notifications">🔔 Générer les notifications (rappels + retards)</button>
</form>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<table border="1" cellpadding="8">
    <thead>
        <tr>
            <th>Membre</th>
            <th>Livre</th>
            <th>Exemplaire</th>
            <th>Date emprunt</th>
            <th>Retour prévu</th>
            <th>Retour effectif</th>
            <th>Statut</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($emprunts as $emprunt): ?>
            <tr <?= estEnRetard($emprunt) ? 'style="background-color: #fdd;"' : '' ?>>
                <td><?= htmlspecialchars($emprunt['prenom_membre'] . ' ' . $emprunt['nom_membre']) ?></td>
                <td><?= htmlspecialchars($emprunt['titre']) ?></td>
                <td><?= htmlspecialchars($emprunt['code_inventaire']) ?></td>
                <td><?= htmlspecialchars($emprunt['date_emprunt']) ?></td>
                <td><?= htmlspecialchars($emprunt['date_retour_prevue']) ?></td>
                <td><?= $emprunt['date_retour_effective'] ? htmlspecialchars($emprunt['date_retour_effective']) : '—' ?></td>
                <td>
                    <?php if (estEnRetard($emprunt)): ?>
                        <span style="color: red;">En retard</span>
                    <?php elseif ($emprunt['statut'] === 'en_cours'): ?>
                        <span style="color: green;">En cours</span>
                    <?php else: ?>
                        <span>Rendu</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($emprunt['statut'] !== 'rendu'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_emprunt" value="<?= $emprunt['id_emprunt'] ?>">
                            <button type="submit">Marquer comme rendu</button>
                        </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once '../../includes/footer.php'; ?>