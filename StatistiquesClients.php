<?php
session_start();
require_once 'includes/Connexion.php';
require_once 'includes/functions.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupération des dates pour le filtre
$date_debut = filter_input(INPUT_GET, 'date_debut') ?? date('Y-m-d', strtotime('-1 month'));
$date_fin = filter_input(INPUT_GET, 'date_fin') ?? date('Y-m-d');

// Statistiques globales des clients
$sql = "SELECT 
            COUNT(DISTINCT c.IDCLIENT) as nombre_total_clients,
            COUNT(DISTINCT v.IDCLIENT) as clients_actifs,
            AVG(montants.total_client) as panier_moyen,
            MAX(montants.total_client) as meilleur_montant
        FROM client c
        LEFT JOIN vente v ON c.IDCLIENT = v.IDCLIENT 
            AND v.DATEVENTE BETWEEN :date_debut AND :date_fin
        LEFT JOIN (
            SELECT IDCLIENT, SUM(MONTANTFINAL) as total_client
            FROM vente
            WHERE DATEVENTE BETWEEN :date_debut2 AND :date_fin2
            GROUP BY IDCLIENT
        ) montants ON c.IDCLIENT = montants.IDCLIENT";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'date_debut' => $date_debut,
    'date_fin' => $date_fin,
    'date_debut2' => $date_debut,
    'date_fin2' => $date_fin
]);
$stats_globales = $stmt->fetch(PDO::FETCH_ASSOC);

// Top 10 des meilleurs clients
$sql = "SELECT 
            c.IDCLIENT,
            c.NOM,
            c.PRENOM,
            COUNT(v.IDVENTE) as nombre_achats,
            SUM(v.MONTANTFINAL) as montant_total,
            AVG(v.MONTANTFINAL) as panier_moyen,
            MAX(v.DATEVENTE) as dernier_achat,
            c.POINTSFIDELITE
        FROM client c
        LEFT JOIN vente v ON c.IDCLIENT = v.IDCLIENT 
            AND v.DATEVENTE BETWEEN :date_debut AND :date_fin
        GROUP BY c.IDCLIENT, c.NOM, c.PRENOM, c.POINTSFIDELITE
        HAVING montant_total > 0
        ORDER BY montant_total DESC
        LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'date_debut' => $date_debut,
    'date_fin' => $date_fin
]);
$meilleurs_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clients inactifs (aucun achat sur la période)
$sql = "SELECT 
            c.IDCLIENT,
            c.NOM,
            c.PRENOM,
            c.EMAIL,
            c.TELEPHONE,
            MAX(v.DATEVENTE) as dernier_achat
        FROM client c
        LEFT JOIN vente v ON c.IDCLIENT = v.IDCLIENT
        WHERE c.IDCLIENT NOT IN (
            SELECT DISTINCT IDCLIENT 
            FROM vente 
            WHERE DATEVENTE BETWEEN :date_debut AND :date_fin
            AND IDCLIENT IS NOT NULL
        )
        GROUP BY c.IDCLIENT, c.NOM, c.PRENOM, c.EMAIL, c.TELEPHONE
        ORDER BY dernier_achat DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'date_debut' => $date_debut,
    'date_fin' => $date_fin
]);
$clients_inactifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Clients</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">Statistiques Clients</h1>

        <!-- Formulaire de filtrage par date -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="date_debut" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" 
                               value="<?php echo $date_debut; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" 
                               value="<?php echo $date_fin; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">Filtrer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistiques globales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Nombre total de clients</h5>
                        <p class="card-text fs-2"><?php echo $stats_globales['nombre_total_clients']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Clients actifs</h5>
                        <p class="card-text fs-2"><?php echo $stats_globales['clients_actifs']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Panier moyen</h5>
                        <p class="card-text fs-2">
                            <?php echo number_format($stats_globales['panier_moyen'], 2, ',', ' '); ?> €
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Meilleur montant</h5>
                        <p class="card-text fs-2">
                            <?php echo number_format($stats_globales['meilleur_montant'], 2, ',', ' '); ?> €
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 10 des meilleurs clients -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Top 10 des Meilleurs Clients</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Nombre d'achats</th>
                                <th>Montant total</th>
                                <th>Panier moyen</th>
                                <th>Dernier achat</th>
                                <th>Points fidélité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meilleurs_clients as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['NOM'] . ' ' . $client['PRENOM']); ?></td>
                                    <td><?php echo $client['nombre_achats']; ?></td>
                                    <td><?php echo number_format($client['montant_total'], 2, ',', ' '); ?> €</td>
                                    <td><?php echo number_format($client['panier_moyen'], 2, ',', ' '); ?> €</td>
                                    <td><?php echo date('d/m/Y', strtotime($client['dernier_achat'])); ?></td>
                                    <td><?php echo $client['POINTSFIDELITE']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Clients inactifs -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Clients Inactifs</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Dernier achat</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients_inactifs as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['NOM'] . ' ' . $client['PRENOM']); ?></td>
                                    <td><?php echo htmlspecialchars($client['EMAIL']); ?></td>
                                    <td><?php echo htmlspecialchars($client['TELEPHONE']); ?></td>
                                    <td>
                                        <?php echo $client['dernier_achat'] 
                                            ? date('d/m/Y', strtotime($client['dernier_achat']))
                                            : 'Jamais'; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm"
                                                onclick="window.location.href='GestionClients.php?id=<?php echo $client['IDCLIENT']; ?>'">
                                            Voir détails
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation des dates
        document.querySelector('form').addEventListener('submit', function(e) {
            var dateDebut = new Date(document.getElementById('date_debut').value);
            var dateFin = new Date(document.getElementById('date_fin').value);
            
            if (dateFin < dateDebut) {
                e.preventDefault();
                alert('La date de fin doit être postérieure à la date de début');
            }
        });
    </script>
</body>
</html> 