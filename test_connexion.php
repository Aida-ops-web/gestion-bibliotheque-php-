<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->connecter();

if ($db) {
    echo "Connexion réussie à la base de données !";
}