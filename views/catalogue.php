<?php
require_once '../config/database.php';
require_once '../models/Livre.php';
require_once '../includes/header.php';

$database = new Database();
$db = $database->connecter();

$livreModel = new Livre($db);
$categories = $livreModel->getToutesCategories();

// Récupération des filtres depuis l'URL (méthode GET)
$recherche = $_GET['recherche'] ?? '';
$id_categorie = $_GET['categorie'] ?? '';
$disponibles_seulement = isset($_GET['disponibles']);

$livres = $livreModel->rechercher($recherche, $id_categorie, $disponibles_seulement);
?>

<h1>Catalogue des livres</h1>

<form method="GET" action="catalogue.php">
    <input type="text" name="recherche" placeholder="Titre, auteur, ISBN..." value="<?= htmlspecialchars($recherche) ?>">

    <select name="categorie">
        <option value="">Toutes les catégories</option>
        <?php foreach ($categories as $categorie): ?>
            <option value="<?= $categorie['id_categorie'] ?>" <?= ($id_categorie == $categorie['id_categorie']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($categorie['nom_categorie']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>
        <input type="checkbox" name="disponibles" value="1" <?= $disponibles_seulement ? 'checked' : '' ?>>
        Disponibles seulement
    </label>

    <button type="submit">Rechercher</button>
    <a href="catalogue.php">Réinitialiser</a>
</form>

<div class="grille-livres">
    <?php if (empty($livres)): ?>
        <p>Aucun livre ne correspond à votre recherche.</p>
    <?php else: ?>
        <?php foreach ($livres as $livre): ?>
            <div class="carte-livre">
                <h3><?= htmlspecialchars($livre['titre']) ?></h3>
                <p><strong>Catégorie :</strong> <?= htmlspecialchars($livre['nom_categorie'] ?? 'Non classé') ?></p>
                <p><strong>Année :</strong> <?= htmlspecialchars($livre['annee_publication']) ?></p>

                <?php if ($livre['exemplaires_disponibles'] > 0): ?>
                    <p class="disponible">
                        ✅ Disponible (<?= $livre['exemplaires_disponibles'] ?>/<?= $livre['total_exemplaires'] ?> exemplaires)
                    </p>
                    <a href="fiche_livre.php?id=<?= $livre['id_livre'] ?>">
                        <button>Emprunter</button>
                    </a>
                <?php else: ?>
                    <p class="indisponible">❌ Tous les exemplaires sont empruntés</p>
                    <a href="fiche_livre.php?id=<?= $livre['id_livre'] ?>">
                        <button>Réserver</button>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>