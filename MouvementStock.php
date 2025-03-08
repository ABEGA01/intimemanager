<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Vérification de l'ID de l'article
if (!isset($_GET['id'])) {
    header('Location: ListeArticles.php');
    exit();
}

$idArticle = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    // Récupération des informations de l'article
    $stmt = $con->prepare("SELECT * FROM article WHERE IDARTICLE = :id");
    $stmt->execute(['id' => $idArticle]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        throw new Exception('Article non trouvé');
    }

    // Récupération des mouvements de stock
    $stmt = $con->prepare("SELECT m.*, u.NOMUTILISATEUR 
                          FROM mouvement_stock m 
                          JOIN utilisateur u ON m.IDUTILISATEUR = u.IDUTILISATEUR 
                          WHERE m.IDARTICLE = :id 
                          ORDER BY m.DATE DESC");
    $stmt->execute(['id' => $idArticle]);
    $mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT);
        $commentaire = filter_input(INPUT_POST, 'commentaire', FILTER_SANITIZE_STRING);

        if (!$type || !$quantite || $quantite <= 0) {
            throw new Exception('Veuillez remplir tous les champs obligatoires');
        }

        // Début de la transaction
        $con->beginTransaction();

        // Insertion du mouvement
        $stmt = $con->prepare("INSERT INTO mouvement_stock (IDARTICLE, TYPE, QUANTITE, IDUTILISATEUR, COMMENTAIRE) 
                              VALUES (:article, :type, :quantite, :utilisateur, :commentaire)");
        $stmt->execute([
            'article' => $idArticle,
            'type' => $type,
            'quantite' => $quantite,
            'utilisateur' => $_SESSION['IDUTILISATEUR'],
            'commentaire' => $commentaire
        ]);

        // Mise à jour du stock
        $modification = $type === 'ENTREE' ? $quantite : -$quantite;
        $stmt = $con->prepare("UPDATE article 
                              SET QUANTITESTOCK = QUANTITESTOCK + :modification 
                              WHERE IDARTICLE = :id");
        $stmt->execute([
            'modification' => $modification,
            'id' => $idArticle
        ]);

        // Journalisation de l'action
        $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) 
                              VALUES (:id, 'MOUVEMENT_STOCK', :details)");
        $stmt->execute([
            'id' => $_SESSION['IDUTILISATEUR'],
            'details' => "Mouvement de stock pour l'article {$article['NOMARTICLE']}: {$type} de {$quantite} unités"
        ]);

        // Validation de la transaction
        $con->commit();

        $_SESSION['success_message'] = 'Mouvement de stock enregistré avec succès';
        header("Location: MouvementStock.php?id=$idArticle");
        exit();

    } catch (Exception $e) {
        // Annulation de la transaction en cas d'erreur
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mouvements de Stock - Gestion de Stock</title>
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
        .stock-info {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
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
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-exchange-alt me-2"></i>Nouveau Mouvement
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="stock-info">
                            <h6 class="mb-2"><?php echo htmlspecialchars($article['NOMARTICLE']); ?></h6>
                            <p class="mb-0">Stock actuel : <?php echo $article['QUANTITESTOCK']; ?> unités</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="type" class="form-label">Type de mouvement *</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Sélectionnez un type</option>
                                    <option value="ENTREE">Entrée</option>
                                    <option value="SORTIE">Sortie</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="quantite" class="form-label">Quantité *</label>
                                <input type="number" class="form-control" id="quantite" name="quantite" 
                                       min="1" required>
                            </div>

                            <div class="mb-3">
                                <label for="commentaire" class="form-label">Commentaire</label>
                                <textarea class="form-control" id="commentaire" name="commentaire" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i>Enregistrer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Historique des Mouvements
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Quantité</th>
                                        <th>Utilisateur</th>
                                        <th>Commentaire</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mouvements as $mouvement): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($mouvement['DATE'])); ?></td>
                                        <td>
                                            <?php if ($mouvement['TYPE'] === 'ENTREE'): ?>
                                                <span class="badge bg-success">Entrée</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Sortie</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $mouvement['QUANTITE']; ?></td>
                                        <td><?php echo htmlspecialchars($mouvement['NOMUTILISATEUR']); ?></td>
                                        <td><?php echo htmlspecialchars($mouvement['COMMENTAIRE']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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