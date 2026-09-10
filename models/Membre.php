<?php

class Membre {
    private $connexion;
    private $table = "membre";

    public function __construct($db) {
        $this->connexion = $db;
    }

    // Vérifie si un email existe déjà (pour éviter les doublons à l'inscription)
    public function emailExiste($email) {
        $requete = "SELECT id_membre FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Inscrit un nouveau membre
    public function inscrire($nom, $prenom, $email, $telephone, $adresse, $mot_de_passe) {
        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

        $requete = "INSERT INTO " . $this->table . " 
                    (nom, prenom, email, telephone, adresse, mot_de_passe, role) 
                    VALUES (:nom, :prenom, :email, :telephone, :adresse, :mot_de_passe, 'membre')";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':adresse', $adresse);
        $stmt->bindParam(':mot_de_passe', $mot_de_passe_hash);

        return $stmt->execute();
    }

    // Vérifie les identifiants de connexion
    public function connecter($email, $mot_de_passe) {
        $requete = "SELECT id_membre, nom, prenom, email, mot_de_passe, role, statut 
                    FROM " . $this->table . " 
                    WHERE email = :email";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $membre = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($membre && password_verify($mot_de_passe, $membre['mot_de_passe'])) {
            return $membre;
        }

        return false;
    }

        // Récupère tous les membres (hors bibliothécaires) avec le total de leurs pénalités impayées
    public function getTousLesMembres() {
        $requete = "SELECT 
                        m.id_membre,
                        m.nom,
                        m.prenom,
                        m.email,
                        m.telephone,
                        m.statut,
                        m.date_inscription,
                        COALESCE(SUM(CASE WHEN p.statut = 'impayee' THEN p.montant ELSE 0 END), 0) AS penalites_impayees
                    FROM membre m
                    LEFT JOIN emprunt e ON m.id_membre = e.id_membre
                    LEFT JOIN penalite p ON e.id_emprunt = p.id_emprunt
                    WHERE m.role = 'membre'
                    GROUP BY m.id_membre
                    ORDER BY m.nom ASC";

        $stmt = $this->connexion->prepare($requete);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Change le statut d'un membre (actif <-> suspendu)
    public function changerStatut($id_membre, $nouveau_statut) {
        $requete = "UPDATE " . $this->table . " SET statut = :statut WHERE id_membre = :id_membre";
        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':statut', $nouveau_statut);
        $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);

        return $stmt->execute();
    }
}