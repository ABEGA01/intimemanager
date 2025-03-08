<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Vérification des droits d'administration
if ($_SESSION['ROLE'] !== 'ADMIN') {
    $_SESSION['error_message'] = 'Vous n\'avez pas les droits nécessaires pour effectuer cette action.';
    header('Location: ListeFournisseurs.php');
    exit();
}

// Vérification de l'ID du fournisseur
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ListeFournisseurs.php');
    exit();
}

try {
    // Vérification de l'existence du fournisseur
    $stmt = $con->prepare("SELECT NOMFOURNISSEUR FROM fournisseur WHERE IDFOURNISSEUR = :id");
    $stmt->execute(['id' => $id]);
    $fournisseur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fournisseur) {
        throw new Exception('Fournisseur non trouvé');
    }

    // Vérification si le fournisseur a des articles associés
    $stmt = $con->prepare("SELECT COUNT(*) FROM article WHERE IDFOURNISSEUR = :id");
    $stmt->execute(['id' => $id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        throw new Exception('Impossible de supprimer ce fournisseur car il a des articles associés');
    }

    // Suppression du fournisseur
    $stmt = $con->prepare("DELETE FROM fournisseur WHERE IDFOURNISSEUR = :id");
    $stmt->execute(['id' => $id]);

    // Journalisation de l'action
    $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) 
                          VALUES (:id, 'SUPPRESSION_FOURNISSEUR', 'Suppression du fournisseur : ' || :nom)");
    $stmt->execute([
        'id' => $_SESSION['IDUTILISATEUR'],
        'nom' => $fournisseur['NOMFOURNISSEUR']
    ]);

    $_SESSION['success_message'] = 'Fournisseur supprimé avec succès';

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: ListeFournisseurs.php');
exit(); 