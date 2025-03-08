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
    header('Location: ListeArticles.php');
    exit();
}

// Vérification de l'ID de l'article
if (!isset($_GET['id'])) {
    header('Location: ListeArticles.php');
    exit();
}

$idArticle = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    // Vérification de l'existence de l'article
    $stmt = $con->prepare("SELECT * FROM article WHERE IDARTICLE = :id");
    $stmt->execute(['id' => $idArticle]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        throw new Exception('Article non trouvé');
    }

    // Vérification si l'article est utilisé dans des ventes
    $stmt = $con->prepare("SELECT COUNT(*) FROM detail_vente WHERE IDARTICLE = :id");
    $stmt->execute(['id' => $idArticle]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Impossible de supprimer cet article car il est utilisé dans des ventes');
    }

    // Vérification si l'article a des mouvements de stock
    $stmt = $con->prepare("SELECT COUNT(*) FROM mouvement_stock WHERE IDARTICLE = :id");
    $stmt->execute(['id' => $idArticle]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Impossible de supprimer cet article car il a des mouvements de stock');
    }

    // Suppression de l'article
    $stmt = $con->prepare("DELETE FROM article WHERE IDARTICLE = :id");
    $stmt->execute(['id' => $idArticle]);

    // Journalisation de l'action
    $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) 
                          VALUES (:id, 'SUPPRESSION_ARTICLE', 'Suppression de l\'article : ' || :nom)");
    $stmt->execute([
        'id' => $_SESSION['IDUTILISATEUR'],
        'nom' => $article['NOMARTICLE']
    ]);

    $_SESSION['success_message'] = 'Article supprimé avec succès';

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

// Redirection vers la liste des articles
header('Location: ListeArticles.php');
exit();
?> 