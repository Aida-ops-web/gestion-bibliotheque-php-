<?php
require_once '../config/database.php';
require_once '../models/Livre.php';
require_once '../models/Emprunt.php';
require_once '../models/Reservation.php';
require_once '../includes/header.php';

$database = new Database();
$db = $database->connecter();

$livreModel = new Livre($db);
$empruntModel = new Emprunt($db);
$reservationModel = new Reservation($db);

$id_livre = $_GET['id'] ?? null;
$message = "";

if (!$id_livre) {
    header("Location: catalogue.php");
    exit;
}

$livre = $livreModel->getLivreParId($id_livre);

if (!$livre) {
    header("Location: catalogue.php");
    exit;
}

// Traitement du clic sur "Emprunter"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emprunter'])) {

    if (!isset($_SESSION['id_membre'])) {
        $message = "Vous devez être connecté pour emprunter un livre.";
    } else {
        $exemplaire = $empruntModel->trouverExemplaireDisponible($id_livre);

        if ($exemplaire) {
            $succes = $empruntModel->creerEmprunt($_SESSION['id_membre'], $exemplaire['id_exemplaire']);
            $message = $succes
                ? "Emprunt enregistré ! Retour prévu dans 14 jours."
                : "Une erreur est survenue, veuillez réessayer.";

            // On recharge les infos du livre pour mettre à jour la disponibilité affichée
            $livre = $livreModel->getLivreParId($id_livre);
        } else {
            $message = "Désolé, il n'y a plus d'exemplaire disponible.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    if (!isset($_SESSION['id_membre'])) {
        $message = "Vous devez être connecté pour réserver un livre.";
    } elseif ($reservationModel->reservationExiste($_SESSION['id_membre'], $id_livre)) {
        $message = "Vous avez déjà une réservation en cours pour ce livre.";
    } else {
        $succes = $reservationModel->creerReservation($_SESSION['id_membre'], $id_livre);
        $message = $succes ? "Réservation enregistrée !" : "Une erreur est survenue.";
    }
}

// Compte les exemplaires disponibles pour ce livre
$requeteDispo = $db->prepare("SELECT COUNT(*) AS dispo FROM exemplaire WHERE id_livre = :id AND statut = 'disponible'");
$requeteDispo->bindParam(':id', $id_livre, PDO::PARAM_INT);
$requeteDispo->execute();
$exemplairesDisponibles = $requeteDispo->fetch(PDO::FETCH_ASSOC)['dispo'];
?>

<h1><?= htmlspecialchars($livre['titre']) ?></h1>

<p><strong>Auteur(s) :</strong> <?= htmlspecialchars($livre['auteurs'] ?? 'Inconnu') ?></p>
<p><strong>Catégorie :</strong> <?= htmlspecialchars($livre['nom_categorie'] ?? 'Non classé') ?></p>
<p><strong>Année :</strong> <?= htmlspecialchars($livre['annee_publication']) ?></p>
<p><strong>Éditeur :</strong> <?= htmlspecialchars($livre['editeur']) ?></p>
<p><strong>Résumé :</strong> <?= nl2br(htmlspecialchars($livre['resume'])) ?></p>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<?php if ($exemplairesDisponibles > 0): ?>
    <p class="disponible">✅ <?= $exemplairesDisponibles ?> exemplaire(s) disponible(s)</p>

    <?php if (isset($_SESSION['id_membre'])): ?>
        <form method="POST">
            <button type="submit" name="emprunter">Emprunter ce livre</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">Connectez-vous</a> pour emprunter ce livre.</p>
    <?php endif; ?>

<?php else: ?>
    <p class="indisponible">❌ Aucun exemplaire disponible actuellement</p>

    <?php if (isset($_SESSION['id_membre'])): ?>
        <?php if ($reservationModel->reservationExiste($_SESSION['id_membre'], $id_livre)): ?>
            <p>Vous avez déjà une réservation en cours pour ce livre.</p>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="reserver">Réserver ce livre</button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <p><a href="login.php">Connectez-vous</a> pour réserver ce livre.</p>
    <?php endif; ?>
<?php endif; ?>

<p><a href="catalogue.php">← Retour au catalogue</a></p>

<?php require_once '../includes/footer.php'; ?>