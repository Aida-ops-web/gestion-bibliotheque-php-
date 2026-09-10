<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_membre']) || $_SESSION['role'] !== 'bibliothecaire') {
    header("Location: /bibliotheque/views/login.php");
    exit;
}