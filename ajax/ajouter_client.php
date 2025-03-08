<?php
session_start();
require_once '../includes/Connexion.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Récupération des données
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['telephone'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

try {
    // Préparation de la requête
    $sql = "INSERT INTO client (NOM, PRENOM, EMAIL, TELEPHONE) VALUES (:nom, :prenom, :email, :telephone)";
    $stmt = $pdo->prepare($sql);
    
    // Exécution de la requête
    $stmt->execute([
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'email' => $data['email'] ?? null,
        'telephone' => $data['telephone']
    ]);
    
    // Récupération de l'ID du client créé
    $client_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'client_id' => $client_id,
        'message' => 'Client ajouté avec succès'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout du client: ' . $e->getMessage()
    ]);
} 