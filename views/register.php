<?php
session_start();
require_once '../config/database.php';
require_once '../models/Membre.php';

$erreur = "";
$succes = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirmation = $_POST['confirmation'];

    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe)) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } elseif ($mot_de_passe !== $confirmation) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        $database = new Database();
        $db = $database->connecter();
        $membreModel = new Membre($db);

        if ($membreModel->emailExiste($email)) {
            $erreur = "Cet email est déjà utilisé par un autre compte.";
        } else {
            if ($membreModel->inscrire($nom, $prenom, $email, $telephone, $adresse, $mot_de_passe)) {
                $succes = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            } else {
                $erreur = "Une erreur est survenue, veuillez réessayer.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Bibliothèque</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

    <h1>Créer un compte</h1>

    <?php if ($erreur): ?>
        <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <?php if ($succes): ?>
        <p class="succes"><?= htmlspecialchars($succes) ?></p>
        <a href="login.php">Aller à la page de connexion</a>
    <?php else: ?>

    <form method="POST" action="register.php">
        <label>Nom :</label>
        <input type="text" name="nom" required><br>

        <label>Prénom :</label>
        <input type="text" name="prenom" required><br>

        <label>Email :</label>
        <input type="email" name="email" required><br>

        <label>Téléphone :</label>
        <input type="text" name="telephone"><br>

        <label>Adresse :</label>
        <input type="text" name="adresse"><br>

        <label>Mot de passe :</label>
        <input type="password" name="mot_de_passe" required><br>

        <label>Confirmer le mot de passe :</label>
        <input type="password" name="confirmation" required><br>

        <button type="submit">S'inscrire</button>
    </form>

    <?php endif; ?>

</body>
</html>