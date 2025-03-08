<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Variables pour l'en-tête
$pageTitle = 'Tableau de Bord';
$bodyClass = 'dashboard-page';

// Inclusion de l'en-tête
require_once('includes/header.php');

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
                                 JOIN detail_vente d ON a.REFERENCE = d.REFERENCE 
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
            $success = "Vente supprimée avec succès.";
        }
    } catch (Exception $e) {
        $con->rollBack();
        $error = "Erreur lors de la suppression de la vente : " . $e->getMessage();
    }
}

// Récupération des statistiques
try {
    // Nombre total d'articles
    $stmt = $con->query("SELECT COUNT(*) FROM article");
    $totalArticles = $stmt->fetchColumn();

    // Nombre d'articles en stock faible
    $stmt = $con->query("SELECT COUNT(*) FROM article WHERE QUANTITESTOCK <= SEUILALERTE");
    $articlesStockFaible = $stmt->fetchColumn();

    // Chiffre d'affaires du jour
    $stmt = $con->query("SELECT COALESCE(SUM(MONTANTFINAL), 0) FROM vente WHERE DATE(DATEVENTE) = CURDATE()");
    $caJour = $stmt->fetchColumn();

    // Dernières ventes
    $stmt = $con->query("SELECT v.*, u.NOMUTILISATEUR 
                        FROM vente v 
                        JOIN utilisateur u ON v.IDUTILISATEUR = u.IDUTILISATEUR 
                        ORDER BY v.DATEVENTE DESC LIMIT 5");
    $dernieresVentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ventes par mois (derniers 6 mois)
    $stmt = $con->query("SELECT DATE_FORMAT(DATEVENTE, '%Y-%m') as mois,
                        COUNT(*) as nombre_ventes,
                        SUM(MONTANTFINAL) as montant_total
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

} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>

    <div class="container mt-4">
        <div class="row g-4">
            <!-- Statistiques -->
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-box card-icon"></i>
                        <h5 class="card-title">Total Articles</h5>
                        <h2 class="mb-0"><?php echo $totalArticles; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle card-icon"></i>
                        <h5 class="card-title">Stock Faible</h5>
                        <h2 class="mb-0"><?php echo $articlesStockFaible; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-euro-sign card-icon"></i>
                        <h5 class="card-title">CA du Jour</h5>
                        <h2 class="mb-0"><?php echo number_format($caJour, 2); ?> €</h2>
                    </div>
                </div>
            </div>

        <!-- Graphiques -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Ventes par Mois
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="ventesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Articles par Catégorie
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoriesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Dernières Ventes -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Dernières Ventes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>N° Vente</th>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Mode de Paiement</th>
                                        <th>Vendeur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dernieresVentes as $vente): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($vente['NUMEROVENTE']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($vente['DATEVENTE'])); ?></td>
                                        <td><?php echo number_format($vente['MONTANTFINAL'], 2); ?> €</td>
                                        <td><?php echo htmlspecialchars($vente['MODEPAIEMENT']); ?></td>
                                        <td><?php echo htmlspecialchars($vente['NOMUTILISATEUR']); ?></td>
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

<?php
// Variables pour le pied de page
$needjQuery = true;
$additionalScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js',
    'js/dashboard.js'
];

// Conversion des données pour les graphiques
$ventes_par_mois_json = json_encode(array_values($ventes_par_mois));
$articles_par_categorie_json = json_encode(array_values($articles_par_categorie));
?>

<script>
    // Données pour les graphiques
    const ventes_par_mois = <?php echo $ventes_par_mois_json; ?>;
    const articles_par_categorie = <?php echo $articles_par_categorie_json; ?>;
</script>

<?php
// Inclusion du pied de page
require_once('includes/footer.php');
?> 