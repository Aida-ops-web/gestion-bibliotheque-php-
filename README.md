# 📚 Gestion de Bibliothèque — PHP & MySQL

Application web complète de gestion de bibliothèque, développée en PHP natif (architecture MVC simplifiée) et MySQL. Ce projet couvre l'ensemble du cycle de vie d'une bibliothèque : catalogue, emprunts, réservations, pénalités de retard, notifications et espace d'administration.

## ✨ Fonctionnalités

**Côté membre**
- Inscription et connexion sécurisées (mots de passe hachés avec `password_hash`)
- Catalogue de livres avec recherche avancée (titre, auteur, ISBN) et filtres (catégorie, disponibilité)
- Emprunt et retour de livres, avec suivi dans "Mon compte"
- Réservation d'un livre indisponible, avec file d'attente et activation automatique au retour d'un exemplaire
- Notifications (rappel d'échéance, retard, réservation disponible)

**Côté bibliothécaire**
- Gestion des livres et de leurs exemplaires (ajout, suppression)
- Vue globale sur tous les emprunts, avec retour manuel et repérage visuel des retards
- Gestion des membres (suspension / réactivation de compte)
- Tableau de bord statistique (chiffres clés, top des livres empruntés, répartition par catégorie) avec graphiques Chart.js

**Logique métier**
- Calcul automatique des pénalités de retard
- Transactions SQL (PDO) pour garantir la cohérence des données lors des emprunts/retours
- Contrôle d'accès par rôle (membre / bibliothécaire)

## 🛠️ Technologies

- **PHP** (natif, orienté objet, sans framework)
- **MySQL** (10 tables relationnelles, clés étrangères, contraintes)
- **PDO** pour l'accès sécurisé à la base de données (requêtes préparées)
- **HTML / CSS** (feuille de style personnalisée)
- **Chart.js** pour les graphiques statistiques
- **XAMPP** comme environnement de développement local

## 🗂️ Structure du projet

```
bibliotheque/
├── config/          → connexion PDO à la base de données
├── includes/         → header, footer, vérification des accès admin
├── models/           → classes métier (Livre, Membre, Emprunt, Reservation, Notification...)
├── views/            → pages affichées à l'utilisateur
│   └── admin/         → pages réservées au bibliothécaire
└── public/            → CSS, JS, images
```

## 🗃️ Modèle de données

Le projet repose sur un MCD complet avec 9 entités : Membre, Livre, Auteur, Catégorie, Exemplaire, Emprunt, Pénalité, Réservation et Notification — avec une gestion fine de la disponibilité au niveau de l'exemplaire (et non du livre), pour permettre plusieurs copies d'un même titre.

## 🚀 Installation locale

1. Installer [XAMPP](https://www.apachefriends.org/) et démarrer Apache + MySQL
2. Cloner ce dépôt dans `C:\xampp\htdocs\bibliotheque`
3. Créer une base de données `bibliotheque_db` dans phpMyAdmin
4. Importer le script SQL de création des tables, puis (optionnel) les données de test
5. Accéder au projet via `http://localhost/bibliotheque/views/catalogue.php`

## 👤 Auteur

**Aïda Diop** — Étudiante en Génie Informatique, Université Gaston Berger de Saint-Louis (UGB)
