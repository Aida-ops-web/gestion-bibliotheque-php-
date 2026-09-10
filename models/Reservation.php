<?php

class Reservation {
    private $connexion;
    private $table = "reservation";

    public function __construct($db) {
        $this->connexion = $db;
    }

    // Vérifie si le membre a déjà une réservation active sur ce livre
    public function reservationExiste($id_membre, $id_livre) {
        $requete = "SELECT id_reservation FROM " . $this->table . " 
                    WHERE id_membre = :id_membre AND id_livre = :id_livre 
                    AND statut IN ('en_attente', 'disponible')";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
        $stmt->bindParam(':id_livre', $id_livre, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Crée une réservation
    public function creerReservation($id_membre, $id_livre) {
        $requete = "INSERT INTO " . $this->table . " 
                    (id_membre, id_livre, date_reservation, statut) 
                    VALUES (:id_membre, :id_livre, :date_reservation, 'en_attente')";

        $stmt = $this->connexion->prepare($requete);
        $date_reservation = date('Y-m-d');
        $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
        $stmt->bindParam(':id_livre', $id_livre, PDO::PARAM_INT);
        $stmt->bindParam(':date_reservation', $date_reservation);

        return $stmt->execute();
    }

    // Récupère la réservation la plus ancienne "en_attente" pour un livre donné (= la prochaine dans la file)
    public function prochaineReservation($id_livre) {
        $requete = "SELECT id_reservation, id_membre FROM " . $this->table . " 
                    WHERE id_livre = :id_livre AND statut = 'en_attente' 
                    ORDER BY date_reservation ASC 
                    LIMIT 1";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_livre', $id_livre, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Marque une réservation comme "disponible" avec un délai de retrait de 3 jours
        // Marque une réservation comme "disponible" avec un délai de retrait de 3 jours
    public function activerReservation($id_reservation) {
        $date_limite = date('Y-m-d', strtotime('+3 days'));

        $requete = "UPDATE " . $this->table . " 
                    SET statut = 'disponible', date_limite_retrait = :date_limite 
                    WHERE id_reservation = :id_reservation";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':date_limite', $date_limite);
        $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
        $stmt->execute();

        // On récupère les infos pour créer la notification
        $requeteInfos = "SELECT r.id_membre, l.titre 
                          FROM " . $this->table . " r
                          JOIN livre l ON r.id_livre = l.id_livre
                          WHERE r.id_reservation = :id_reservation";
        $stmtInfos = $this->connexion->prepare($requeteInfos);
        $stmtInfos->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
        $stmtInfos->execute();
        $infos = $stmtInfos->fetch(PDO::FETCH_ASSOC);

        if ($infos) {
            require_once 'Notification.php';
            $notificationModel = new Notification($this->connexion);
            $message = "Le livre \"" . $infos['titre'] . "\" que vous avez réservé est disponible ! Vous avez jusqu'au " . $date_limite . " pour venir le récupérer.";
            $notificationModel->creer($infos['id_membre'], 'reservation_disponible', $message);
        }

        return true;
    }

    // Récupère les réservations d'un membre
    public function getReservationsParMembre($id_membre) {
        $requete = "SELECT r.id_reservation, r.date_reservation, r.date_limite_retrait, r.statut, l.titre 
                    FROM " . $this->table . " r
                    JOIN livre l ON r.id_livre = l.id_livre
                    WHERE r.id_membre = :id_membre
                    ORDER BY r.date_reservation DESC";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}