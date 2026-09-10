<?php
require_once '../config/database.php';
require_once '../models/Notification.php';
require_once '../includes/header.php';

if (!isset($_SESSION['id_membre'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->connecter();

$notificationModel = new Notification($db);

// Marquer une notification comme lue au clic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_notification'])) {
    $notificationModel->marquerLue($_POST['id_notification'], $_SESSION['id_membre']);
}

$notifications = $notificationModel->getNotificationsParMembre($_SESSION['id_membre']);
?>

<h1>Mes notifications</h1>

<?php if (empty($notifications)): ?>
    <p>Vous n'avez aucune notification.</p>
<?php else: ?>
    <?php foreach ($notifications as $notif): ?>
        <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 8px; <?= !$notif['lu'] ? 'background: #eef;' : '' ?>">
            <p><?= htmlspecialchars($notif['message']) ?></p>
            <small><?= htmlspecialchars($notif['date_creation']) ?></small>

            <?php if (!$notif['lu']): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id_notification" value="<?= $notif['id_notification'] ?>">
                    <button type="submit">Marquer comme lu</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>