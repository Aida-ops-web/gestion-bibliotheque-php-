<?php
session_start();
require_once '../config/database.php';
require_once '../models/Membre.php';

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];

    if (empty($email) || empty($mot_de_passe)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $database = new Database();
        $db = $database->connecter();
        $membreModel = new Membre($db);

        $membre = $membreModel->connecter($email, $mot_de_passe);

        if ($membre) {
            if ($membre['statut'] === 'suspendu') {
                $erreur = "Votre compte est suspendu. Contactez la bibliothèque.";
            } else {
                // Connexion réussie : on stocke les infos utiles dans la session
                $_SESSION['id_membre'] = $membre['id_membre'];
                $_SESSION['nom'] = $membre['nom'];
                $_SESSION['prenom'] = $membre['prenom'];
                $_SESSION['role'] = $membre['role'];

                header("Location: catalogue.php");
                exit;
            }
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Bibliothèque</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

    <h1>Connexion</h1>

    <?php if ($erreur): ?>
        <p class="erreur"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label>Email :</label>
        <input type="email" name="email" required><br>

        <label>Mot de passe :</label>
        <input type="password" name="mot_de_passe" required><br>

        <button type="submit">Se connecter</button>
    </form>

    <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>

</body>
</html>