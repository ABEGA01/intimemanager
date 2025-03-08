<?php
session_start();
require_once('Connexion.php');

if (isset($_SESSION['IDUTILISATEUR'])) {
    try {
        // Journalisation de la déconnexion
        $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) VALUES (:id, 'DECONNEXION', 'Déconnexion de l\'utilisateur')");
        $stmt->execute(['id' => $_SESSION['IDUTILISATEUR']]);
    } catch (Exception $e) {
        // En cas d'erreur, on continue quand même la déconnexion
    }
}

// Destruction de la session
session_destroy();

// Redirection vers la page de connexion
header('Location: login.php');
exit();
?> 