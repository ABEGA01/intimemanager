<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Récupération des paramètres de recherche
$idProduit = filter_input(INPUT_GET, 'produit', FILTER_VALIDATE_INT);
$dateDebut = filter_input(INPUT_GET, 'date_debut');
$dateFin = filter_input(INPUT_GET, 'date_fin');

// Récupération des statistiques globales
try {
    // Nombre total d'articles
    $stmt = $con->query("SELECT COUNT(*) FROM article");
    $total_articles = $stmt->fetchColumn();

    // Nombre total de ventes
    $stmt = $con->query("SELECT COUNT(*) FROM vente");
    $total_ventes = $stmt->fetchColumn();

    // Montant total des ventes
    $stmt = $con->query("SELECT COALESCE(SUM(MONTANTTOTAL), 0) FROM vente");
    $montant_total_ventes = $stmt->fetchColumn();

    // Articles en stock faible
    $stmt = $con->query("SELECT COUNT(*) FROM article WHERE QUANTITESTOCK <= SEUILALERTE");
    $articles_stock_faible = $stmt->fetchColumn();

    // Top 5 des articles les plus vendus
    $stmt = $con->query("SELECT a.NOMARTICLE, SUM(dv.QUANTITE) as total_vendu
                        FROM detail_vente dv
                        JOIN article a ON dv.IDARTICLE = a.IDARTICLE
                        GROUP BY a.IDARTICLE, a.NOMARTICLE
                        ORDER BY total_vendu DESC
                        LIMIT 5");
    $top_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ventes par mois (derniers 6 mois)
    $stmt = $con->query("SELECT DATE_FORMAT(DATEVENTE, '%Y-%m') as mois,
                        COUNT(*) as nombre_ventes,
                        SUM(MONTANTTOTAL) as montant_total
                        FROM vente
                        WHERE DATEVENTE >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(DATEVENTE, '%Y-%m')
                        ORDER BY mois");
    $ventes_par_mois = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Articles par catégorie
    $stmt = $con->query("SELECT c.NOMCATEGORIE, COUNT(a.IDARTICLE) as nombre_articles
                        FROM categorie c
                        LEFT JOIN article a ON c.IDCATEGORIE = a.IDCATEGORIE
                        GROUP BY c.IDCATEGORIE, c.NOMCATEGORIE");
    $articles_par_categorie = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération de la liste des produits pour le select
    $stmt = $con->query("SELECT IDARTICLE, REFERENCE, NOMARTICLE FROM article ORDER BY NOMARTICLE");
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si un produit est sélectionné, récupérer ses statistiques
    $statsProduit = null;
    if ($idProduit) {
        // Statistiques globales du produit
        if ($_SESSION['ROLE'] === 'ADMIN') {
            $stmt = $con->prepare("
                SELECT 
                    a.*,
                    COUNT(dv.IDVENTE) as nombre_ventes,
                    SUM(dv.QUANTITE) as quantite_vendue,
                    SUM(dv.QUANTITE * dv.PRIXUNITAIRE) as chiffre_affaires,
                    SUM(dv.QUANTITE * (dv.PRIXUNITAIRE - a.PRIXACHAT)) as benefice,
                    SUM(CASE WHEN dv.QUANTITE > a.QUANTITESTOCK THEN dv.QUANTITE - a.QUANTITESTOCK ELSE 0 END) as pertes_stock
                FROM article a
                LEFT JOIN detail_vente dv ON a.IDARTICLE = dv.IDARTICLE
                WHERE a.IDARTICLE = :id
                GROUP BY a.IDARTICLE
            ");
        } else {
            $stmt = $con->prepare("
                SELECT 
                    a.IDARTICLE,
                    a.REFERENCE,
                    a.NOMARTICLE,
                    a.DESCRIPTION,
                    a.PRIXVENTE,
                    a.QUANTITESTOCK,
                    a.SEUILALERTE,
                    COUNT(dv.IDVENTE) as nombre_ventes,
                    SUM(dv.QUANTITE) as quantite_vendue,
                    SUM(dv.QUANTITE * dv.PRIXUNITAIRE) as chiffre_affaires
                FROM article a
                LEFT JOIN detail_vente dv ON a.IDARTICLE = dv.IDARTICLE
                WHERE a.IDARTICLE = :id
                GROUP BY a.IDARTICLE
            ");
        }
        $stmt->execute(['id' => $idProduit]);
        $statsProduit = $stmt->fetch(PDO::FETCH_ASSOC);

        // Historique des ventes du produit
        $stmt = $con->prepare("
            SELECT 
                v.DATEVENTE,
                dv.QUANTITE,
                dv.PRIXUNITAIRE,
                u.NOMUTILISATEUR
            FROM detail_vente dv
            JOIN vente v ON dv.IDVENTE = v.IDVENTE
            JOIN utilisateur u ON v.IDUTILISATEUR = u.IDUTILISATEUR
            WHERE dv.IDARTICLE = :id
            ORDER BY v.DATEVENTE DESC
        ");
        $stmt->execute(['id' => $idProduit]);
        $historiqueVentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Si une période est sélectionnée, récupérer les statistiques
    $statsPeriode = null;
    if ($dateDebut && $dateFin) {
        // Statistiques globales de la période
        $stmt = $con->prepare("
            SELECT 
                COUNT(DISTINCT v.IDVENTE) as nombre_ventes,
                SUM(v.MONTANTFINAL) as chiffre_affaires,
                SUM(dv.QUANTITE * (dv.PRIXUNITAIRE - a.PRIXACHAT)) as benefice_total,
                COUNT(DISTINCT v.IDUTILISATEUR) as nombre_vendeurs
            FROM vente v
            JOIN detail_vente dv ON v.IDVENTE = dv.IDVENTE
            JOIN article a ON dv.IDARTICLE = a.IDARTICLE
            WHERE v.DATEVENTE BETWEEN :debut AND :fin
        ");
        $stmt->execute(['debut' => $dateDebut, 'fin' => $dateFin]);
        $statsPeriode = $stmt->fetch(PDO::FETCH_ASSOC);

        // Produits les plus vendus
        $stmt = $con->prepare("
            SELECT 
                a.NOMARTICLE,
                a.REFERENCE,
                SUM(dv.QUANTITE) as quantite_vendue,
                SUM(dv.QUANTITE * dv.PRIXUNITAIRE) as chiffre_affaires
            FROM detail_vente dv
            JOIN article a ON dv.IDARTICLE = a.IDARTICLE
            JOIN vente v ON dv.IDVENTE = v.IDVENTE
            WHERE v.DATEVENTE BETWEEN :debut AND :fin
            GROUP BY a.IDARTICLE
            ORDER BY quantite_vendue DESC
            LIMIT 5
        ");
        $stmt->execute(['debut' => $dateDebut, 'fin' => $dateFin]);
        $produitsPlusVendus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Produits les moins vendus
        $stmt = $con->prepare("
            SELECT 
                a.NOMARTICLE,
                a.REFERENCE,
                SUM(dv.QUANTITE) as quantite_vendue,
                SUM(dv.QUANTITE * dv.PRIXUNITAIRE) as chiffre_affaires
            FROM detail_vente dv
            JOIN article a ON dv.IDARTICLE = a.IDARTICLE
            JOIN vente v ON dv.IDVENTE = v.IDVENTE
            WHERE v.DATEVENTE BETWEEN :debut AND :fin
            GROUP BY a.IDARTICLE
            ORDER BY quantite_vendue ASC
            LIMIT 5
        ");
        $stmt->execute(['debut' => $dateDebut, 'fin' => $dateFin]);
        $produitsMoinsVendus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - Gestion de Stock</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin-bottom: 1.5rem;
        }
        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .stats-card i {
            font-size: 2rem;
            opacity: 0.8;
        }
        .chart-container {
            position: relative;
            height: 300px;
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
                        <a class="nav-link" href="ListeArticles.php">
                            <i class="fas fa-box me-1"></i>Articles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ListeVentes.php">
                            <i class="fas fa-shopping-cart me-1"></i>Ventes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ListeFournisseurs.php">
                            <i class="fas fa-truck me-1"></i>Fournisseurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="Rapports.php">
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
        <!-- Recherche par produit -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-search me-2"></i>Recherche par produit
                </h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-8">
                        <select name="produit" class="form-select" onchange="this.form.submit()">
                            <option value="">Sélectionnez un produit</option>
                            <?php foreach ($produits as $produit): ?>
                            <option value="<?php echo $produit['IDARTICLE']; ?>" <?php echo $idProduit == $produit['IDARTICLE'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($produit['NOMARTICLE'] . ' (' . $produit['REFERENCE'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Rechercher
                        </button>
                    </div>
                </form>

                <?php if ($statsProduit): ?>
                <div class="row mt-4">
                    <div class="col-12 mb-3">
                        <a href="ImprimerEtatProduit.php?produit=<?php echo $idProduit; ?>" class="btn btn-primary">
                            <i class="fas fa-print me-2"></i>Imprimer l'état
                        </a>
                    </div>
                    <div class="col-md-<?php echo $_SESSION['ROLE'] === 'ADMIN' ? '3' : '4'; ?>">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6>Nombre de ventes</h6>
                                <h3><?php echo $statsProduit['nombre_ventes']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-<?php echo $_SESSION['ROLE'] === 'ADMIN' ? '3' : '4'; ?>">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6>Quantité vendue</h6>
                                <h3><?php echo $statsProduit['quantite_vendue']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-<?php echo $_SESSION['ROLE'] === 'ADMIN' ? '3' : '4'; ?>">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6>Chiffre d'affaires</h6>
                                <h3><?php echo number_format($statsProduit['chiffre_affaires'], 2); ?> €</h3>
                            </div>
                        </div>
                    </div>
                    <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6>Bénéfice</h6>
                                <h3><?php echo number_format($statsProduit['benefice'], 2); ?> €</h3>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">Historique des ventes</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Quantité</th>
                                        <th>Prix unitaire</th>
                                        <th>Vendeur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historiqueVentes as $vente): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($vente['DATEVENTE'])); ?></td>
                                        <td><?php echo $vente['QUANTITE']; ?></td>
                                        <td><?php echo number_format($vente['PRIXUNITAIRE'], 2); ?> €</td>
                                        <td><?php echo htmlspecialchars($vente['NOMUTILISATEUR']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Analyse par période -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar me-2"></i>Analyse par période
                </h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date de début</label>
                        <input type="date" name="date_debut" class="form-control" value="<?php echo $dateDebut; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date de fin</label>
                        <input type="date" name="date_fin" class="form-control" value="<?php echo $dateFin; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Analyser
                        </button>
                    </div>
                </form>

                <?php if ($statsPeriode): ?>
                <div class="row mt-4">
                    <div class="col-12 mb-3">
                        <a href="ImprimerEtatPeriode.php?date_debut=<?php echo $dateDebut; ?>&date_fin=<?php echo $dateFin; ?>" class="btn btn-primary">
                            <i class="fas fa-print me-2"></i>Imprimer l'état
                        </a>
                    </div>
                    <div class="col-md-<?php echo $_SESSION['ROLE'] === 'ADMIN' ? '3' : '4'; ?>">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6>Nombre de ventes</h6>
                                <h3><?php echo $statsPeriode['nombre_ventes']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-<?php echo $_SESSION['ROLE'] === 'ADMIN' ? '3' : '4'; ?>">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6>Chiffre d'affaires</h6>
                                <h3><?php echo number_format($statsPeriode['chiffre_affaires'], 2); ?> €</h3>
                            </div>
                        </div>
                    </div>
                    <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6>Bénéfice total</h6>
                                <h3><?php echo number_format($statsPeriode['benefice_total'], 2); ?> €</h3>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-<?php echo $_SESSION['ROLE'] === 'ADMIN' ? '3' : '4'; ?>">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6>Nombre de vendeurs</h6>
                                <h3><?php echo $statsPeriode['nombre_vendeurs']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Produits les plus vendus</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Article</th>
                                                <th>Quantité</th>
                                                <th>CA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($produitsPlusVendus as $produit): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($produit['NOMARTICLE']); ?></td>
                                                <td><?php echo $produit['quantite_vendue']; ?></td>
                                                <td><?php echo number_format($produit['chiffre_affaires'], 2); ?> €</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Produits les moins vendus</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Article</th>
                                                <th>Quantité</th>
                                                <th>CA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($produitsMoinsVendus as $produit): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($produit['NOMARTICLE']); ?></td>
                                                <td><?php echo $produit['quantite_vendue']; ?></td>
                                                <td><?php echo number_format($produit['chiffre_affaires'], 2); ?> €</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
        // Graphique des ventes par mois
        const ventesCtx = document.getElementById('ventesChart').getContext('2d');
        new Chart(ventesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($ventes_par_mois, 'mois')); ?>,
                datasets: [{
                    label: 'Nombre de Ventes',
                    data: <?php echo json_encode(array_column($ventes_par_mois, 'nombre_ventes')); ?>,
                    borderColor: '#3498db',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Graphique des articles par catégorie
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        new Chart(categoriesCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($articles_par_categorie, 'NOMCATEGORIE')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($articles_par_categorie, 'nombre_articles')); ?>,
                    backgroundColor: [
                        '#3498db',
                        '#2ecc71',
                        '#e74c3c',
                        '#f1c40f',
                        '#9b59b6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html> 