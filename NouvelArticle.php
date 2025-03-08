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
    $_SESSION['error_message'] = 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.';
    header('Location: ListeArticles.php');
    exit();
}

// Récupération des catégories et fournisseurs pour les listes déroulantes
try {
    $stmt = $con->query("SELECT * FROM categorie ORDER BY NOMCATEGORIE");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $con->query("SELECT * FROM fournisseur ORDER BY NOMFOURNISSEUR");
    $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $reference = filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_STRING);
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $prixAchat = filter_input(INPUT_POST, 'prix_achat', FILTER_VALIDATE_FLOAT);
        $prixVente = filter_input(INPUT_POST, 'prix_vente', FILTER_VALIDATE_FLOAT);
        $quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT);
        $seuilAlerte = filter_input(INPUT_POST, 'seuil_alerte', FILTER_VALIDATE_INT);
        $categorie = filter_input(INPUT_POST, 'categorie', FILTER_VALIDATE_INT);
        $fournisseur = filter_input(INPUT_POST, 'fournisseur', FILTER_VALIDATE_INT);
        $taille = filter_input(INPUT_POST, 'taille', FILTER_SANITIZE_STRING);
        $couleur = filter_input(INPUT_POST, 'couleur', FILTER_SANITIZE_STRING);

        // Vérification des données
        if (!$reference || !$nom || !$prixAchat || !$prixVente || !$quantite || !$seuilAlerte || !$categorie || !$fournisseur) {
            throw new Exception('Veuillez remplir tous les champs obligatoires');
        }

        // Vérification de la référence unique
        $stmt = $con->prepare("SELECT COUNT(*) FROM article WHERE REFERENCE = :reference");
        $stmt->execute(['reference' => $reference]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Cette référence existe déjà');
        }

        // Insertion de l'article
        $stmt = $con->prepare("INSERT INTO article (REFERENCE, NOMARTICLE, DESCRIPTION, PRIXACHAT, PRIXVENTE, 
                              QUANTITESTOCK, SEUILALERTE, IDCATEGORIE, IDFOURNISSEUR, TAILLE, COULEUR) 
                              VALUES (:reference, :nom, :description, :prix_achat, :prix_vente, 
                              :quantite, :seuil_alerte, :categorie, :fournisseur, :taille, :couleur)");
        
        $stmt->execute([
            'reference' => $reference,
            'nom' => $nom,
            'description' => $description,
            'prix_achat' => $prixAchat,
            'prix_vente' => $prixVente,
            'quantite' => $quantite,
            'seuil_alerte' => $seuilAlerte,
            'categorie' => $categorie,
            'fournisseur' => $fournisseur,
            'taille' => $taille,
            'couleur' => $couleur
        ]);

        // Journalisation de l'action
        $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) 
                              VALUES (:id, 'AJOUT_ARTICLE', 'Ajout de l\'article : ' || :nom)");
        $stmt->execute([
            'id' => $_SESSION['IDUTILISATEUR'],
            'nom' => $nom
        ]);

        // Redirection avec message de succès
        $_SESSION['success_message'] = 'Article ajouté avec succès';
        header('Location: ListeArticles.php');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvel Article - Gestion de Stock</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: white !important;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>Gestion de Stock
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="ListeArticles.php">
                            <i class="fas fa-box me-1"></i>Articles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="NouvelleVente.php">
                            <i class="fas fa-shopping-cart me-1"></i>Ventes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ListeFournisseurs.php">
                            <i class="fas fa-truck me-1"></i>Fournisseurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Rapports.php">
                            <i class="fas fa-chart-bar me-1"></i>Rapports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Nouvel Article
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reference" class="form-label">Référence *</label>
                                    <input type="text" class="form-control" id="reference" name="reference" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="prix_achat" class="form-label">Prix d'achat *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="prix_achat" name="prix_achat" 
                                               step="0.01" min="0" required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prix_vente" class="form-label">Prix de vente *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="prix_vente" name="prix_vente" 
                                               step="0.01" min="0" required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="quantite" class="form-label">Quantité initiale *</label>
                                    <input type="number" class="form-control" id="quantite" name="quantite" 
                                           min="0" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="seuil_alerte" class="form-label">Seuil d'alerte *</label>
                                    <input type="number" class="form-control" id="seuil_alerte" name="seuil_alerte" 
                                           min="0" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="categorie" class="form-label">Catégorie *</label>
                                    <select class="form-select" id="categorie" name="categorie" required>
                                        <option value="">Sélectionnez une catégorie</option>
                                        <?php foreach ($categories as $categorie): ?>
                                            <option value="<?php echo $categorie['IDCATEGORIE']; ?>">
                                                <?php echo htmlspecialchars($categorie['NOMCATEGORIE']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="fournisseur" class="form-label">Fournisseur *</label>
                                    <select class="form-select" id="fournisseur" name="fournisseur" required>
                                        <option value="">Sélectionnez un fournisseur</option>
                                        <?php foreach ($fournisseurs as $fournisseur): ?>
                                            <option value="<?php echo $fournisseur['IDFOURNISSEUR']; ?>">
                                                <?php echo htmlspecialchars($fournisseur['NOMFOURNISSEUR']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="taille" class="form-label">Taille</label>
                                    <input type="text" class="form-control" id="taille" name="taille">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="couleur" class="form-label">Couleur</label>
                                    <input type="text" class="form-control" id="couleur" name="couleur">
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="ListeArticles.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i>Retour
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0">&copy; 2024 Gestion de Stock. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation du formulaire
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 