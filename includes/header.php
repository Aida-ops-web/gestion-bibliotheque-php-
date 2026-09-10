<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bibliothèque</title>
    <link rel="stylesheet" href="/bibliotheque/public/css/style.css">
</head>
<body>

<nav>
    <a href="/bibliotheque/views/catalogue.php">Catalogue</a>

    <?php if (isset($_SESSION['id_membre'])): ?>
        <span>Connecté en tant que <?= htmlspecialchars($_SESSION['prenom']) ?></span>

<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';
$db_header = (new Database())->connecter();
$notifModel = new Notification($db_header);
$nbNonLues = $notifModel->compterNonLues($_SESSION['id_membre']);
?>
<a href="/bibliotheque/views/notifications.php">
    Notifications
    <?php if ($nbNonLues > 0): ?>
        <span style="background: red; color: white; border-radius: 50%; padding: 2px 6px;"><?= $nbNonLues ?></span>
    <?php endif; ?>
</a>

                       <?php if ($_SESSION['role'] === 'bibliothecaire'): ?>
            <a href="/bibliotheque/views/admin/gestion_livres.php">Livres</a>
            <a href="/bibliotheque/views/admin/gestion_emprunts.php">Emprunts</a>
            <a href="/bibliotheque/views/admin/gestion_membres.php">Membres</a>
            <a href="/bibliotheque/views/admin/statistiques.php">Statistiques</a>
        <?php endif; ?>
        <a href="/bibliotheque/views/mon_compte.php">Mon compte</a>
        <a href="/bibliotheque/controllers/auth_controller.php?action=deconnexion">Se déconnecter</a>
    <?php else: ?>
        <a href="/bibliotheque/views/login.php">Se connecter</a>
        <a href="/bibliotheque/views/register.php">S'inscrire</a>
    <?php endif; ?>
</nav>

<hr>