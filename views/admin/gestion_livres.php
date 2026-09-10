<?php
require_once '../../config/database.php';
require_once '../../includes/verifier_admin.php';
require_once '../../models/Livre.php';
require_once '../../models/Exemplaire.php';
require_once '../../includes/header.php';

$database = new Database();
$db = $database->connecter();

$livreModel = new Livre($db);
$exemplaireModel = new Exemplaire($db);
$categories = $livreModel->getToutesCategories();
$message = "";

// Ajout d'un nouveau livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_livre'])) {
    $id_livre = $livreModel->ajouterLivre(
        $_POST['titre'],
        $_POST['isbn'],
        $_POST['resume'],
        $_POST['annee'],
        $_POST['editeur'],
        $_POST['pages'],
        $_POST['id_categorie']
    );

    // On ajoute directement un premier exemplaire pour ce livre
    if ($id_livre && !empty($_POST['code_inventaire'])) {
        $exemplaireModel->ajouterExemplaire($id_livre, $_POST['code_inventaire']);
    }

    $message = "Livre ajouté avec succès !";
}

// Ajout d'un exemplaire supplémentaire à un livre existant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_exemplaire'])) {
    $exemplaireModel->ajouterExemplaire($_POST['id_livre'], $_POST['code_inventaire_sup']);
    $message = "Exemplaire ajouté avec succès !";
}

// Suppression d'un livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_livre'])) {
    $livreModel->supprimerLivre($_POST['id_livre_suppr']);
    $message = "Livre supprimé.";
}

$livres = $livreModel->rechercher();
?>

<h1>Gestion des livres</h1>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<h2>Ajouter un nouveau livre</h2>

<form method="POST">
    <label>Titre :</label>
    <input type="text" name="titre" required><br>

    <label>ISBN :</label>
    <input type="text" name="isbn"><br>

    <label>Résumé :</label>
    <textarea name="resume"></textarea><br>

    <label>Année :</label>
    <input type="number" name="annee"><br>

    <label>Éditeur :</label>
    <input type="text" name="editeur"><br>

    <label>Nombre de pages :</label>
    <input type="number" name="pages"><br>

    <label>Catégorie :</label>
    <select name="id_categorie" required>
        <?php foreach ($categories as $categorie): ?>
            <option value="<?= $categorie['id_categorie'] ?>"><?= htmlspecialchars($categorie['nom_categorie']) ?></option>
        <?php endforeach; ?>
    </select><br>

    <label>Code inventaire du 1er exemplaire :</label>
    <input type="text" name="code_inventaire" placeholder="Ex: EX-0009"><br>

    <button type="submit" name="ajouter_livre">Ajouter le livre</button>
</form>

<h2>Liste des livres</h2>

<table border="1" cellpadding="8">
    <thead>
        <tr>
            <th>Titre</th>
            <th>Catégorie</th>
            <th>Exemplaires (dispo/total)</th>
            <th>Ajouter exemplaire</th>
            <th>Supprimer</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($livres as $livre): ?>
            <tr>
                <td><?= htmlspecialchars($livre['titre']) ?></td>
                <td><?= htmlspecialchars($livre['nom_categorie'] ?? 'Non classé') ?></td>
                <td><?= $livre['exemplaires_disponibles'] ?>/<?= $livre['total_exemplaires'] ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_livre" value="<?= $livre['id_livre'] ?>">
                        <input type="text" name="code_inventaire_sup" placeholder="Code" size="8" required>
                        <button type="submit" name="ajouter_exemplaire">+</button>
                    </form>
                </td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce livre et tous ses exemplaires ?');">
                        <input type="hidden" name="id_livre_suppr" value="<?= $livre['id_livre'] ?>">
                        <button type="submit" name="supprimer_livre">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once '../../includes/footer.php'; ?>