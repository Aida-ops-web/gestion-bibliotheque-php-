<?php

class Notification {
    private $connexion;
    private $table = "notification";

    public function __construct($db) {
        $this->connexion = $db;
    }

    // Crée une notification
    public function creer($id_membre, $type, $message) {
        $requete = "INSERT INTO " . $this->table . " (id_membre, type, message) 
                    VALUES (:id_membre, :type, :message)";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':message', $message);

        return $stmt->execute();
    }

    // Récupère les notifications d'un membre (les plus récentes en premier)
    public function getNotificationsParMembre($id_membre) {
        $requete = "SELECT id_notification, type, message, date_creation, lu 
                    FROM " . $this->table . " 
                    WHERE id_membre = :id_membre 
                    ORDER BY date_creation DESC";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compte les notifications non lues (pour le badge dans le header)
    public function compterNonLues($id_membre) {
        $requete = "SELECT COUNT(*) AS total FROM " . $this->table . " 
                    WHERE id_membre = :id_membre AND lu = 0";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Marque une notification comme lue
    public function marquerLue($id_notification, $id_membre) {
        $requete = "UPDATE " . $this->table . " 
                    SET lu = 1, date_lecture = NOW() 
                    WHERE id_notification = :id_notification AND id_membre = :id_membre";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_notification', $id_notification, PDO::PARAM_INT);
        $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Génère automatiquement les notifications de rappel et de retard (à exécuter manuellement ou via cron)
    public function genererNotificationsAutomatiques() {
        $compteur = 0;

        // Rappels : emprunts qui arrivent à échéance dans 2 jours
        $requeteRappel = "SELECT e.id_membre, l.titre, e.date_retour_prevue 
                           FROM emprunt e
                           JOIN exemplaire ex ON e.id_exemplaire = ex.id_exemplaire
                           JOIN livre l ON ex.id_livre = l.id_livre
                           WHERE e.statut = 'en_cours' 
                           AND e.date_retour_prevue = DATE_ADD(CURDATE(), INTERVAL 2 DAY)
                           AND NOT EXISTS (
                               SELECT 1 FROM notification n 
                               WHERE n.id_membre = e.id_membre 
                               AND n.type = 'rappel_echeance' 
                               AND n.message LIKE CONCAT('%', l.titre, '%')
                               AND DATE(n.date_creation) = CURDATE()
                           )";
        $stmt = $this->connexion->query($requeteRappel);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $message = "Rappel : le livre \"" . $ligne['titre'] . "\" est à rendre dans 2 jours (le " . $ligne['date_retour_prevue'] . ").";
            $this->creer($ligne['id_membre'], 'rappel_echeance', $message);
            $compteur++;
        }

        // Retards : emprunts dépassés non encore rendus
        $requeteRetard = "SELECT e.id_membre, l.titre, e.date_retour_prevue 
                           FROM emprunt e
                           JOIN exemplaire ex ON e.id_exemplaire = ex.id_exemplaire
                           JOIN livre l ON ex.id_livre = l.id_livre
                           WHERE e.statut = 'en_cours' 
                           AND e.date_retour_prevue < CURDATE()
                           AND NOT EXISTS (
                               SELECT 1 FROM notification n 
                               WHERE n.id_membre = e.id_membre 
                               AND n.type = 'retard' 
                               AND n.message LIKE CONCAT('%', l.titre, '%')
                               AND DATE(n.date_creation) = CURDATE()
                           )";
        $stmt2 = $this->connexion->query($requeteRetard);
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $message = "Retard : le livre \"" . $ligne['titre'] . "\" devait être rendu le " . $ligne['date_retour_prevue'] . ".";
            $this->creer($ligne['id_membre'], 'retard', $message);
            $compteur++;
        }

        return $compteur;
    }
}