<?php
require_once '../config/database.php';
require_once '../models/Emprunt.php';
require_once '../models/Reservation.php';
require_once '../includes/header.php';

if (!isset($_SESSION['id_membre'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->connecter();

$empruntModel = new Emprunt($db);
$reservationModel = new Reservation($db);
$message = "";

// Traitement du retour d'un emprunt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_emprunt'])) {
    $succes = $empruntModel->retournerLivre($_POST['id_emprunt'], $_SESSION['id_membre']);
    $message = $succes ? "Livre retourné avec succès !" : "Une erreur est survenue.";
}

// Traitement de l'emprunt via réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reservation'])) {
    $succes = $empruntModel->emprunterViaReservation($_SESSION['id_membre'], $_POST['id_reservation']);
    $message = $succes ? "Livre emprunté avec succès !" : "Une erreur est survenue.";
}

$emprunts = $empruntModel->getEmpruntsParMembre($_SESSION['id_membre']);
$reservations = $reservationModel->getReservationsParMembre($_SESSION['id_membre']);
?>

<h1>Mon compte</h1>

<p>Bienvenue, <?= htmlspecialchars($_SESSION['prenom']) ?> !</p>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<h2>Mes réservations</h2>

<?php if (empty($reservations)): ?>
    <p>Vous n'avez aucune réservation en cours.</p>
<?php else: ?>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Livre</th>
                <th>Date de réservation</th>
                <th>Statut</th>
                <th>Date limite de retrait</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td><?= htmlspecialchars($reservation['titre']) ?></td>
                    <td><?= htmlspecialchars($reservation['date_reservation']) ?></td>
                    <td>
                        <?php if ($reservation['statut'] === 'disponible'): ?>
                            <span style="color: green;">Disponible</span>
                        <?php elseif ($reservation['statut'] === 'en_attente'): ?>
                            <span>En attente</span>
                        <?php else: ?>
                            <span><?= htmlspecialchars($reservation['statut']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $reservation['date_limite_retrait'] ? htmlspecialchars($reservation['date_limite_retrait']) : '—' ?></td>
                    <td>
                        <?php if ($reservation['statut'] === 'disponible'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id_reservation" value="<?= $reservation['id_reservation'] ?>">
                                <button type="submit">Emprunter maintenant</button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Mes emprunts</h2>

<?php if (empty($emprunts)): ?>
    <p>Vous n'avez encore emprunté aucun livre.</p>
<?php else: ?>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Livre</th>
                <th>Date d'emprunt</th>
                <th>Retour prévu</th>
                <th>Retour effectif</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($emprunts as $emprunt): ?>
                <tr>
                    <td><?= htmlspecialchars($emprunt['titre']) ?></td>
                    <td><?= htmlspecialchars($emprunt['date_emprunt']) ?></td>
                    <td><?= htmlspecialchars($emprunt['date_retour_prevue']) ?></td>
                    <td><?= $emprunt['date_retour_effective'] ? htmlspecialchars($emprunt['date_retour_effective']) : '—' ?></td>
                    <td>
                        <?php if ($emprunt['statut'] === 'en_cours'): ?>
                            <span style="color: green;">En cours</span>
                        <?php elseif ($emprunt['statut'] === 'en_retard'): ?>
                            <span style="color: red;">En retard</span>
                        <?php else: ?>
                            <span>Rendu</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($emprunt['statut'] !== 'rendu'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id_emprunt" value="<?= $emprunt['id_emprunt'] ?>">
                                <button type="submit">Retourner</button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>