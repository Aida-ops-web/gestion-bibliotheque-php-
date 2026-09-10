<?php

class Emprunt {
    private $connexion;
    private $table = "emprunt";

    public function __construct($db) {
        $this->connexion = $db;
    }

    // Trouve un exemplaire disponible pour un livre donné
    public function trouverExemplaireDisponible($id_livre) {
        $requete = "SELECT id_exemplaire FROM exemplaire 
                    WHERE id_livre = :id_livre AND statut = 'disponible' 
                    LIMIT 1";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_livre', $id_livre, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crée un emprunt et met à jour le statut de l'exemplaire (durée : 14 jours)
    public function creerEmprunt($id_membre, $id_exemplaire) {
        try {
            $this->connexion->beginTransaction();

            $date_emprunt = date('Y-m-d');
            $date_retour_prevue = date('Y-m-d', strtotime('+14 days'));

            $requete = "INSERT INTO " . $this->table . " 
                        (id_membre, id_exemplaire, date_emprunt, date_retour_prevue, statut) 
                        VALUES (:id_membre, :id_exemplaire, :date_emprunt, :date_retour_prevue, 'en_cours')";

            $stmt = $this->connexion->prepare($requete);
            $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
            $stmt->bindParam(':id_exemplaire', $id_exemplaire, PDO::PARAM_INT);
            $stmt->bindParam(':date_emprunt', $date_emprunt);
            $stmt->bindParam(':date_retour_prevue', $date_retour_prevue);
            $stmt->execute();

            $requeteMaj = "UPDATE exemplaire SET statut = 'emprunte' WHERE id_exemplaire = :id_exemplaire";
            $stmtMaj = $this->connexion->prepare($requeteMaj);
            $stmtMaj->bindParam(':id_exemplaire', $id_exemplaire, PDO::PARAM_INT);
            $stmtMaj->execute();

            $this->connexion->commit();
            return true;

        } catch (Exception $e) {
            $this->connexion->rollBack();
            return false;
        }
    }

        // Récupère tous les emprunts d'un membre, avec les infos du livre
    public function getEmpruntsParMembre($id_membre) {
        $requete = "SELECT 
                        emp.id_emprunt,
                        emp.date_emprunt,
                        emp.date_retour_prevue,
                        emp.date_retour_effective,
                        emp.statut,
                        l.titre,
                        e.code_inventaire
                    FROM emprunt emp
                    JOIN exemplaire e ON emp.id_exemplaire = e.id_exemplaire
                    JOIN livre l ON e.id_livre = l.id_livre
                    WHERE emp.id_membre = :id_membre
                    ORDER BY emp.date_emprunt DESC";

        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        // Traite le retour d'un livre : met à jour l'emprunt, l'exemplaire, et crée une pénalité si en retard
    public function retournerLivre($id_emprunt, $id_membre) {
        try {
            $this->connexion->beginTransaction();

            // On récupère l'emprunt pour vérifier qu'il appartient bien à ce membre et connaître l'exemplaire concerné
            $requete = "SELECT id_exemplaire, date_retour_prevue 
                        FROM emprunt 
                        WHERE id_emprunt = :id_emprunt AND id_membre = :id_membre AND statut != 'rendu'";
            $stmt = $this->connexion->prepare($requete);
            $stmt->bindParam(':id_emprunt', $id_emprunt, PDO::PARAM_INT);
            $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
            $stmt->execute();
            $emprunt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$emprunt) {
                $this->connexion->rollBack();
                return false;
            }

            $date_retour_effective = date('Y-m-d');
            $en_retard = $date_retour_effective > $emprunt['date_retour_prevue'];

            // Mise à jour de l'emprunt
            $requeteMaj = "UPDATE emprunt 
                            SET date_retour_effective = :date_retour, statut = 'rendu' 
                            WHERE id_emprunt = :id_emprunt";
            $stmtMaj = $this->connexion->prepare($requeteMaj);
            $stmtMaj->bindParam(':date_retour', $date_retour_effective);
            $stmtMaj->bindParam(':id_emprunt', $id_emprunt, PDO::PARAM_INT);
            $stmtMaj->execute();

                        // On vérifie s'il y a une réservation en attente pour ce livre
            $requeteLivre = "SELECT id_livre FROM exemplaire WHERE id_exemplaire = :id_exemplaire";
            $stmtLivre = $this->connexion->prepare($requeteLivre);
            $stmtLivre->bindParam(':id_exemplaire', $emprunt['id_exemplaire'], PDO::PARAM_INT);
            $stmtLivre->execute();
            $id_livre = $stmtLivre->fetch(PDO::FETCH_ASSOC)['id_livre'];

            require_once 'Reservation.php';
            $reservationModel = new Reservation($this->connexion);
            $prochaine = $reservationModel->prochaineReservation($id_livre);

            if ($prochaine) {
                // Une réservation attend : l'exemplaire est réservé pour ce membre, pas disponible pour tous
                $requeteExemplaire = "UPDATE exemplaire SET statut = 'reserve' WHERE id_exemplaire = :id_exemplaire";
                $reservationModel->activerReservation($prochaine['id_reservation']);
            } else {
                // Aucune réservation : l'exemplaire redevient disponible pour tous
                $requeteExemplaire = "UPDATE exemplaire SET statut = 'disponible' WHERE id_exemplaire = :id_exemplaire";
            }

            $stmtExemplaire = $this->connexion->prepare($requeteExemplaire);
            $stmtExemplaire->bindParam(':id_exemplaire', $emprunt['id_exemplaire'], PDO::PARAM_INT);
            $stmtExemplaire->execute();

            // Si en retard, on crée une pénalité (500 FCFA par jour de retard, par exemple)
            if ($en_retard) {
                $jours_retard = (strtotime($date_retour_effective) - strtotime($emprunt['date_retour_prevue'])) / 86400;
                $montant = $jours_retard * 500;

                $requetePenalite = "INSERT INTO penalite (id_emprunt, montant, motif, statut) 
                                     VALUES (:id_emprunt, :montant, 'retard', 'impayee')";
                $stmtPenalite = $this->connexion->prepare($requetePenalite);
                $stmtPenalite->bindParam(':id_emprunt', $id_emprunt, PDO::PARAM_INT);
                $stmtPenalite->bindParam(':montant', $montant);
                $stmtPenalite->execute();
            }

            $this->connexion->commit();
            return true;

        } catch (Exception $e) {
            $this->connexion->rollBack();
            return false;
        }
    }

        // Transforme une réservation "disponible" en emprunt réel
    public function emprunterViaReservation($id_membre, $id_reservation) {
        try {
            $this->connexion->beginTransaction();

            // On vérifie que la réservation appartient bien à ce membre et est bien "disponible"
            $requete = "SELECT id_livre FROM reservation 
                        WHERE id_reservation = :id_reservation 
                        AND id_membre = :id_membre 
                        AND statut = 'disponible'";
            $stmt = $this->connexion->prepare($requete);
            $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
            $stmt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
            $stmt->execute();
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                $this->connexion->rollBack();
                return false;
            }

            // On trouve l'exemplaire mis de côté pour ce livre (statut 'reserve')
            $requeteExemplaire = "SELECT id_exemplaire FROM exemplaire 
                                   WHERE id_livre = :id_livre AND statut = 'reserve' 
                                   LIMIT 1";
            $stmtExemplaire = $this->connexion->prepare($requeteExemplaire);
            $stmtExemplaire->bindParam(':id_livre', $reservation['id_livre'], PDO::PARAM_INT);
            $stmtExemplaire->execute();
            $exemplaire = $stmtExemplaire->fetch(PDO::FETCH_ASSOC);

            if (!$exemplaire) {
                $this->connexion->rollBack();
                return false;
            }

            // Création de l'emprunt (même logique que creerEmprunt)
            $date_emprunt = date('Y-m-d');
            $date_retour_prevue = date('Y-m-d', strtotime('+14 days'));

            $requeteEmprunt = "INSERT INTO emprunt 
                                (id_membre, id_exemplaire, date_emprunt, date_retour_prevue, statut) 
                                VALUES (:id_membre, :id_exemplaire, :date_emprunt, :date_retour_prevue, 'en_cours')";
            $stmtEmprunt = $this->connexion->prepare($requeteEmprunt);
            $stmtEmprunt->bindParam(':id_membre', $id_membre, PDO::PARAM_INT);
            $stmtEmprunt->bindParam(':id_exemplaire', $exemplaire['id_exemplaire'], PDO::PARAM_INT);
            $stmtEmprunt->bindParam(':date_emprunt', $date_emprunt);
            $stmtEmprunt->bindParam(':date_retour_prevue', $date_retour_prevue);
            $stmtEmprunt->execute();

            // L'exemplaire passe de "reserve" à "emprunte"
            $requeteMajExemplaire = "UPDATE exemplaire SET statut = 'emprunte' WHERE id_exemplaire = :id_exemplaire";
            $stmtMajExemplaire = $this->connexion->prepare($requeteMajExemplaire);
            $stmtMajExemplaire->bindParam(':id_exemplaire', $exemplaire['id_exemplaire'], PDO::PARAM_INT);
            $stmtMajExemplaire->execute();

            // La réservation est marquée comme récupérée
            $requeteMajReservation = "UPDATE reservation SET statut = 'recuperee' WHERE id_reservation = :id_reservation";
            $stmtMajReservation = $this->connexion->prepare($requeteMajReservation);
            $stmtMajReservation->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
            $stmtMajReservation->execute();

            $this->connexion->commit();
            return true;

        } catch (Exception $e) {
            $this->connexion->rollBack();
            return false;
        }
    }

        // Récupère tous les emprunts (vue admin), avec infos membre et livre
    public function getTousLesEmprunts() {
        $requete = "SELECT 
                        emp.id_emprunt,
                        emp.date_emprunt,
                        emp.date_retour_prevue,
                        emp.date_retour_effective,
                        emp.statut,
                        m.nom AS nom_membre,
                        m.prenom AS prenom_membre,
                        l.titre,
                        e.code_inventaire
                    FROM emprunt emp
                    JOIN membre m ON emp.id_membre = m.id_membre
                    JOIN exemplaire e ON emp.id_exemplaire = e.id_exemplaire
                    JOIN livre l ON e.id_livre = l.id_livre
                    ORDER BY 
                        CASE WHEN emp.statut = 'en_cours' AND emp.date_retour_prevue < CURDATE() THEN 0
                             WHEN emp.statut = 'en_cours' THEN 1
                             ELSE 2 END,
                        emp.date_emprunt DESC";

        $stmt = $this->connexion->prepare($requete);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Traite un retour côté admin (pas de vérification d'appartenance au membre, contrairement à retournerLivre)
    public function retournerLivreAdmin($id_emprunt) {
        try {
            $this->connexion->beginTransaction();

            $requete = "SELECT id_exemplaire, date_retour_prevue 
                        FROM emprunt 
                        WHERE id_emprunt = :id_emprunt AND statut != 'rendu'";
            $stmt = $this->connexion->prepare($requete);
            $stmt->bindParam(':id_emprunt', $id_emprunt, PDO::PARAM_INT);
            $stmt->execute();
            $emprunt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$emprunt) {
                $this->connexion->rollBack();
                return false;
            }

            $date_retour_effective = date('Y-m-d');
            $en_retard = $date_retour_effective > $emprunt['date_retour_prevue'];

            $requeteMaj = "UPDATE emprunt 
                            SET date_retour_effective = :date_retour, statut = 'rendu' 
                            WHERE id_emprunt = :id_emprunt";
            $stmtMaj = $this->connexion->prepare($requeteMaj);
            $stmtMaj->bindParam(':date_retour', $date_retour_effective);
            $stmtMaj->bindParam(':id_emprunt', $id_emprunt, PDO::PARAM_INT);
            $stmtMaj->execute();

            $requeteLivre = "SELECT id_livre FROM exemplaire WHERE id_exemplaire = :id_exemplaire";
            $stmtLivre = $this->connexion->prepare($requeteLivre);
            $stmtLivre->bindParam(':id_exemplaire', $emprunt['id_exemplaire'], PDO::PARAM_INT);
            $stmtLivre->execute();
            $id_livre = $stmtLivre->fetch(PDO::FETCH_ASSOC)['id_livre'];

            require_once 'Reservation.php';
            $reservationModel = new Reservation($this->connexion);
            $prochaine = $reservationModel->prochaineReservation($id_livre);

            if ($prochaine) {
                $requeteExemplaire = "UPDATE exemplaire SET statut = 'reserve' WHERE id_exemplaire = :id_exemplaire";
                $reservationModel->activerReservation($prochaine['id_reservation']);
            } else {
                $requeteExemplaire = "UPDATE exemplaire SET statut = 'disponible' WHERE id_exemplaire = :id_exemplaire";
            }

            $stmtExemplaire = $this->connexion->prepare($requeteExemplaire);
            $stmtExemplaire->bindParam(':id_exemplaire', $emprunt['id_exemplaire'], PDO::PARAM_INT);
            $stmtExemplaire->execute();

            if ($en_retard) {
                $jours_retard = (strtotime($date_retour_effective) - strtotime($emprunt['date_retour_prevue'])) / 86400;
                $montant = $jours_retard * 500;

                $requetePenalite = "INSERT INTO penalite (id_emprunt, montant, motif, statut) 
                                     VALUES (:id_emprunt, :montant, 'retard', 'impayee')";
                $stmtPenalite = $this->connexion->prepare($requetePenalite);
                $stmtPenalite->bindParam(':id_emprunt', $id_emprunt, PDO::PARAM_INT);
                $stmtPenalite->bindParam(':montant', $montant);
                $stmtPenalite->execute();
            }

            $this->connexion->commit();
            return true;

        } catch (Exception $e) {
            $this->connexion->rollBack();
            return false;
        }
    }
}

