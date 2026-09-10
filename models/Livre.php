<?php

class Livre {
    private $connexion;
    private $table = "livre";

    public function __construct($db) {
        $this->connexion = $db;
    }

    // Récupère tous les livres avec leur catégorie et le nombre d'exemplaires disponibles
    public function getLivresDisponibles() {
        $requete = "SELECT 
                        l.id_livre,
                        l.titre,
                        l.isbn,
                        l.resume,
                        l.annee_publication,
                        l.image_couverture,
                        c.nom_categorie,
                        COUNT(e.id_exemplaire) AS total_exemplaires,
                        SUM(CASE WHEN e.statut = 'disponible' THEN 1 ELSE 0 END) AS exemplaires_disponibles
                    FROM " . $this->table . " l
                    LEFT JOIN categorie c ON l.id_categorie = c.id_categorie
                    LEFT JOIN exemplaire e ON l.id_livre = e.id_livre
                    GROUP BY l.id_livre
                    ORDER BY l.titre ASC";

        $stmt = $this->connexion->prepare($requete);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupère un livre précis avec ses détails (dont ses auteurs)
    public function getLivreParId($id_livre) {
        $requete = "SELECT 
                        l.*,
                        c.nom_categorie,
                        GROUP_CONCAT(CONCAT(a.prenom, ' ', a.nom) SEPARATOR ', ') AS auteurs
                    FROM " . $this->table . " l
                    LEFT JOIN categorie c ON l.id_categorie = c.id_categorie
                    LEFT JOIN livre_auteur la ON l.id_livre = la.id_livre
                    LEFT JOIN auteur a ON la.id_auteur = a.id_auteur
                    WHERE l.id_livre = :id_livre
                    GROUP BY l.id_livre";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_livre', $id_livre, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Recherche avec filtres (tous optionnels)
    public function rechercher($recherche = '', $id_categorie = '', $disponibles_seulement = false) {
        $requete = "SELECT 
                        l.id_livre,
                        l.titre,
                        l.isbn,
                        l.annee_publication,
                        c.nom_categorie,
                        c.id_categorie,
                        COUNT(e.id_exemplaire) AS total_exemplaires,
                        SUM(CASE WHEN e.statut = 'disponible' THEN 1 ELSE 0 END) AS exemplaires_disponibles
                    FROM " . $this->table . " l
                    LEFT JOIN categorie c ON l.id_categorie = c.id_categorie
                    LEFT JOIN exemplaire e ON l.id_livre = e.id_livre
                    LEFT JOIN livre_auteur la ON l.id_livre = la.id_livre
                    LEFT JOIN auteur a ON la.id_auteur = a.id_auteur
                    WHERE 1=1";

        $params = [];

        if (!empty($recherche)) {
            $requete .= " AND (l.titre LIKE :recherche OR l.isbn LIKE :recherche 
                          OR a.nom LIKE :recherche OR a.prenom LIKE :recherche)";
            $params[':recherche'] = '%' . $recherche . '%';
        }

        if (!empty($id_categorie)) {
            $requete .= " AND l.id_categorie = :id_categorie";
            $params[':id_categorie'] = $id_categorie;
        }

        $requete .= " GROUP BY l.id_livre";

        if ($disponibles_seulement) {
            $requete .= " HAVING exemplaires_disponibles > 0";
        }

        $requete .= " ORDER BY l.titre ASC";

        $stmt = $this->connexion->prepare($requete);
        foreach ($params as $cle => $valeur) {
            $stmt->bindValue($cle, $valeur);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupère toutes les catégories (pour le menu déroulant du filtre)
    public function getToutesCategories() {
        $stmt = $this->connexion->prepare("SELECT id_categorie, nom_categorie FROM categorie ORDER BY nom_categorie");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

        // Ajoute un nouveau livre (retourne l'id inséré)
    public function ajouterLivre($titre, $isbn, $resume, $annee, $editeur, $pages, $id_categorie) {
        $requete = "INSERT INTO " . $this->table . " 
                    (titre, isbn, resume, annee_publication, editeur, nombre_pages, id_categorie) 
                    VALUES (:titre, :isbn, :resume, :annee, :editeur, :pages, :id_categorie)";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':titre', $titre);
        $stmt->bindParam(':isbn', $isbn);
        $stmt->bindParam(':resume', $resume);
        $stmt->bindParam(':annee', $annee, PDO::PARAM_INT);
        $stmt->bindParam(':editeur', $editeur);
        $stmt->bindParam(':pages', $pages, PDO::PARAM_INT);
        $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);

        $stmt->execute();
        return $this->connexion->lastInsertId();
    }

    // Supprime un livre
    public function supprimerLivre($id_livre) {
        $stmt = $this->connexion->prepare("DELETE FROM " . $this->table . " WHERE id_livre = :id_livre");
        $stmt->bindParam(':id_livre', $id_livre, PDO::PARAM_INT);
        return $stmt->execute();
    }
}