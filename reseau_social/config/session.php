<?php
session_start();

// Fonction pour verifier si l'utilisateur est connecte
function estConnecte() {
    return isset($_SESSION['utilisateur_id']);
}

// Fonction pour rediriger si non connecte
function requireLogin() {
    if (!estConnecte()) {
        header('Location: login.php');
        exit();
    }
}

// Fonction pour rediriger si deja connecte
function requireLogout() {
    if (estConnecte()) {
        header('Location: index.php');
        exit();
    }
}

// Fonction pour obtenir l'ID de l'utilisateur connecte
function getUserId() {
    return $_SESSION['utilisateur_id'] ?? null;
}

// Fonction pour obtenir le pseudo de l'utilisateur connecte
function getUserPseudo() {
    return $_SESSION['pseudo'] ?? null;
}
?>
