<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Traitement de la suppression de vente
if ($_SESSION['ROLE'] === 'ADMIN' && isset($_POST['supprimer_vente'])) {
    try {
        $con->beginTransaction();
        
        // Récupérer les détails de la vente avant suppression
        $stmt = $con->prepare("SELECT * FROM vente WHERE IDVENTE = ?");
        $stmt->execute([$_POST['idvente']]);
        $vente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vente) {
            // Restaurer les quantités en stock
            $stmt = $con->prepare("UPDATE article a 
                                 JOIN detail_vente d ON a.IDARTICLE = d.IDARTICLE 
                                 SET a.QUANTITESTOCK = a.QUANTITESTOCK + d.QUANTITE 
                                 WHERE d.IDVENTE = ?");
            $stmt->execute([$_POST['idvente']]);
            
            // Supprimer les détails de la vente
            $stmt = $con->prepare("DELETE FROM detail_vente WHERE IDVENTE = ?");
            $stmt->execute([$_POST['idvente']]);
            
            // Supprimer la vente
            $stmt = $con->prepare("DELETE FROM vente WHERE IDVENTE = ?");
            $stmt->execute([$_POST['idvente']]);
            
            // Journaliser l'action
            $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) VALUES (?, 'SUPPRESSION', ?)");
            $stmt->execute([$_SESSION['IDUTILISATEUR'], "Suppression de la vente " . $vente['NUMEROVENTE']]);
            
            $con->commit();
            $_SESSION['success_message'] = "Vente supprimée avec succès.";
        }
    } catch (Exception $e) {
        $con->rollBack();
        $_SESSION['error_message'] = "Erreur lors de la suppression de la vente : " . $e->getMessage();
    }
}

// Récupération des ventes avec les informations de l'utilisateur
try {
    $stmt = $con->query("SELECT v.*, u.NOMUTILISATEUR 
                        FROM vente v 
                        JOIN utilisateur u ON v.IDUTILISATEUR = u.IDUTILISATEUR 
                        ORDER BY v.DATEVENTE DESC");
    $ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Ventes - Gestion de Stock</title>
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
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .modal-title {
            font-weight: bold;
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
                        <a class="nav-link" href="ListeArticles.php">
                            <i class="fas fa-box me-1"></i>Articles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="ListeVentes.php">
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
                    <i class="fas fa-shopping-cart me-2"></i>Liste des Ventes
                </h5>
                <a href="NouvelleVente.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Nouvelle Vente
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        echo htmlspecialchars($_SESSION['success_message']);
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>ID Vente</th>
                                <th>Utilisateur</th>
                                <th>Total</th>
                                <th>Mode de paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventes as $vente): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($vente['DATEVENTE'])); ?></td>
                                <td>#<?php echo $vente['IDVENTE']; ?></td>
                                <td><?php echo htmlspecialchars($vente['NOMUTILISATEUR']); ?></td>
                                <td><?php echo number_format($vente['MONTANTFINAL'], 2); ?> €</td>
                                <td><?php echo htmlspecialchars($vente['MODEPAIEMENT']); ?></td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm btn-info"
                                            onclick="afficherDetails(<?php echo $vente['IDVENTE']; ?>)">
                                        <i class="fas fa-eye"></i> Détails
                                    </button>
                                    <a href="ImprimerFacture.php?id=<?php echo $vente['IDVENTE']; ?>" class="btn btn-info btn-sm" target="_blank">
                                        <i class="fas fa-print"></i> Imprimer
                                    </a>
                                    <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                                    <form method="post" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette vente ?');">
                                        <input type="hidden" name="idvente" value="<?php echo htmlspecialchars($vente['IDVENTE']); ?>">
                                        <button type="submit" name="supprimer_vente" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dans la section des filtres, ajoutons un formulaire pour l'impression d'état -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Imprimer un état</h5>
            </div>
            <div class="card-body">
                <form action="ImprimerEtat.php" method="get" target="_blank">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="date_debut" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="date_fin" class="form-label">Date de fin</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-print"></i> Imprimer l'état
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Détails Vente -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt me-2"></i>Détails de la Vente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="details-content">
                        <!-- Le contenu sera chargé dynamiquement -->
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function afficherDetails(idVente) {
            // Chargement des détails via AJAX
            $.get('DetailsVente.php', { id: idVente }, function(data) {
                $('#details-content').html(data);
                $('#detailsModal').modal('show');
            });
        }
    </script>
</body>
</html>
 