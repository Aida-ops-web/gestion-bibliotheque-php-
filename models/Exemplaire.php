<?php

class Exemplaire {
    private $connexion;
    private $table = "exemplaire";

    public function __construct($db) {
        $this->connexion = $db;
    }

    // Ajoute un exemplaire pour un livre donné
    public function ajouterExemplaire($id_livre, $code_inventaire, $etat = 'bon') {
        $requete = "INSERT INTO " . $this->table . " 
                    (id_livre, code_inventaire, etat, statut) 
                    VALUES (:id_livre, :code_inventaire, :etat, 'disponible')";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_livre', $id_livre, PDO::PARAM_INT);
        $stmt->bindParam(':code_inventaire', $code_inventaire);
        $stmt->bindParam(':etat', $etat);

        return $stmt->execute();
    }

    // Récupère tous les exemplaires d'un livre
    public function getExemplairesParLivre($id_livre) {
        $requete = "SELECT id_exemplaire, code_inventaire, etat, statut 
                    FROM " . $this->table . " 
                    WHERE id_livre = :id_livre 
                    ORDER BY code_inventaire";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_livre', $id_livre, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Supprime un exemplaire (uniquement s'il est disponible, pas emprunté)
    public function supprimerExemplaire($id_exemplaire) {
        $requete = "DELETE FROM " . $this->table . " 
                    WHERE id_exemplaire = :id_exemplaire AND statut = 'disponible'";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_exemplaire', $id_exemplaire, PDO::PARAM_INT);

        return $stmt->execute();
    }
}