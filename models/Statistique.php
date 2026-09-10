<?php

class Statistique {
    private $connexion;

    public function __construct($db) {
        $this->connexion = $db;
    }

    // Chiffres clés globaux
    public function getChiffresClés() {
        $requete = "SELECT
            (SELECT COUNT(*) FROM livre) AS total_livres,
            (SELECT COUNT(*) FROM exemplaire) AS total_exemplaires,
            (SELECT COUNT(*) FROM exemplaire WHERE statut = 'disponible') AS exemplaires_disponibles,
            (SELECT COUNT(*) FROM membre WHERE role = 'membre') AS total_membres,
            (SELECT COUNT(*) FROM emprunt WHERE statut = 'en_cours') AS emprunts_en_cours,
            (SELECT COUNT(*) FROM emprunt WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()) AS emprunts_en_retard,
            (SELECT COALESCE(SUM(montant), 0) FROM penalite WHERE statut = 'impayee') AS penalites_impayees_total";

        $stmt = $this->connexion->query($requete);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Top 5 des livres les plus empruntes is  (all-time)
    public function getLivresLesPlusEmpruntes($limite = 5) {
        $requete = "SELECT l.titre, COUNT(e.id_emprunt) AS nb_emprunts
                    FROM emprunt e
                    JOIN exemplaire ex ON e.id_exemplaire = ex.id_exemplaire
                    JOIN livre l ON ex.id_livre = l.id_livre
                    GROUP BY l.id_livre
                    ORDER BY nb_emprunts DESC
                    LIMIT :limite";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Répartition des livres par catégorie
    public function getRepartitionParCategorie() {
        $requete = "SELECT c.nom_categorie, COUNT(l.id_livre) AS nb_livres
                    FROM categorie c
                    LEFT JOIN livre l ON c.id_categorie = l.id_categorie
                    GROUP BY c.id_categorie
                    ORDER BY nb_livres DESC";

        $stmt = $this->connexion->query($requete);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}