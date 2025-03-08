<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Récupération des articles avec leurs catégories et fournisseurs
try {
    $stmt = $con->query("SELECT a.*, c.NOMCATEGORIE, f.NOMFOURNISSEUR 
                        FROM article a 
                        JOIN categorie c ON a.IDCATEGORIE = c.IDCATEGORIE 
                        JOIN fournisseur f ON a.IDFOURNISSEUR = f.IDFOURNISSEUR 
                        ORDER BY a.NOMARTICLE");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Articles - Gestion de Stock</title>
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
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        .stock-faible {
            color: #dc3545;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2"></i>Liste des Articles
                </h5>
                <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                <a href="NouvelArticle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Nouvel Article
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Nom</th>
                                <th>Catégorie</th>
                                <th>Fournisseur</th>
                                <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                                <th>Prix d'achat</th>
                                <?php endif; ?>
                                <th>Prix de vente</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($article['REFERENCE']); ?></td>
                                <td><?php echo htmlspecialchars($article['NOMARTICLE']); ?></td>
                                <td><?php echo htmlspecialchars($article['NOMCATEGORIE']); ?></td>
                                <td><?php echo htmlspecialchars($article['NOMFOURNISSEUR']); ?></td>
                                <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                                <td><?php echo number_format($article['PRIXACHAT'], 2); ?> €</td>
                                <?php endif; ?>
                                <td><?php echo number_format($article['PRIXVENTE'], 2); ?> €</td>
                                <td class="<?php echo $article['QUANTITESTOCK'] <= $article['SEUILALERTE'] ? 'stock-faible' : ''; ?>">
                                    <?php echo $article['QUANTITESTOCK']; ?>
                                </td>
                                <td>
                                    <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                                    <a href="EditerArticle.php?id=<?php echo $article['IDARTICLE']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="MouvementStock.php?id=<?php echo $article['IDARTICLE']; ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-exchange-alt"></i>
                                    </a>
                                    <a href="SupprimerArticle.php?id=<?php echo $article['IDARTICLE']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        function confirmerSuppression(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                window.location.href = 'SupprimerArticle.php?id=' + id;
            }
        }
    </script>
</body>
</html> 