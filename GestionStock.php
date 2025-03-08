<?php
session_start();
require_once 'includes/Connexion.php';
require_once 'includes/functions.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$type_message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajuster_stock':
                $article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
                $quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT);
                $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
                $motif = filter_input(INPUT_POST, 'motif', FILTER_SANITIZE_STRING);

                if ($article_id && $quantite) {
                    try {
                        $pdo->beginTransaction();

                        // Mise à jour du stock
                        $sql = "UPDATE article SET QUANTITESTOCK = CASE 
                                WHEN :type = 'ENTREE' THEN QUANTITESTOCK + :quantite
                                WHEN :type = 'SORTIE' THEN QUANTITESTOCK - :quantite
                                END
                               WHERE IDARTICLE = :article_id";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'type' => $type,
                            'quantite' => $quantite,
                            'article_id' => $article_id
                        ]);

                        // Enregistrement dans l'historique
                        $sql = "INSERT INTO stock_historique (IDARTICLE, TYPE_MOUVEMENT, QUANTITE, MOTIF, IDUTILISATEUR)
                                VALUES (:article_id, :type, :quantite, :motif, :user_id)";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'article_id' => $article_id,
                            'type' => $type,
                            'quantite' => $quantite,
                            'motif' => $motif,
                            'user_id' => $_SESSION['user_id']
                        ]);

                        $pdo->commit();
                        $message = "Stock mis à jour avec succès";
                        $type_message = "success";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $message = "Erreur lors de la mise à jour du stock: " . $e->getMessage();
                        $type_message = "danger";
                    }
                }
                break;

            case 'retour_produit':
                $vente_id = filter_input(INPUT_POST, 'vente_id', FILTER_VALIDATE_INT);
                $article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
                $client_id = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
                $quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT);
                $etat = filter_input(INPUT_POST, 'etat', FILTER_SANITIZE_STRING);
                $motif = filter_input(INPUT_POST, 'motif', FILTER_SANITIZE_STRING);

                if ($vente_id && $article_id && $client_id && $quantite) {
                    try {
                        $pdo->beginTransaction();

                        // Enregistrement du retour
                        $sql = "INSERT INTO retour_produit (IDVENTE, IDARTICLE, IDCLIENT, QUANTITE, ETAT, MOTIF)
                                VALUES (:vente_id, :article_id, :client_id, :quantite, :etat, :motif)";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'vente_id' => $vente_id,
                            'article_id' => $article_id,
                            'client_id' => $client_id,
                            'quantite' => $quantite,
                            'etat' => $etat,
                            'motif' => $motif
                        ]);

                        // Enregistrement dans l'historique comme une perte
                        $sql = "INSERT INTO stock_historique (IDARTICLE, TYPE_MOUVEMENT, QUANTITE, MOTIF, IDUTILISATEUR)
                                VALUES (:article_id, 'PERTE', :quantite, :motif, :user_id)";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'article_id' => $article_id,
                            'quantite' => $quantite,
                            'motif' => "Retour produit - " . $etat . ": " . $motif,
                            'user_id' => $_SESSION['user_id']
                        ]);

                        $pdo->commit();
                        $message = "Retour produit enregistré avec succès";
                        $type_message = "success";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $message = "Erreur lors de l'enregistrement du retour: " . $e->getMessage();
                        $type_message = "danger";
                    }
                }
                break;
        }
    }
}

// Récupération des articles en stock critique
$sql = "SELECT * FROM article WHERE QUANTITESTOCK <= SEUILALERTE ORDER BY QUANTITESTOCK ASC";
$stmt = $pdo->query($sql);
$articles_critiques = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de tous les articles pour le formulaire
$sql = "SELECT * FROM article ORDER BY NOMARTICLE ASC";
$stmt = $pdo->query($sql);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des derniers mouvements de stock
$sql = "SELECT sh.*, a.NOMARTICLE, a.REFERENCE, u.NOMUTILISATEUR 
        FROM stock_historique sh
        JOIN article a ON sh.IDARTICLE = a.IDARTICLE
        JOIN utilisateur u ON sh.IDUTILISATEUR = u.IDUTILISATEUR
        ORDER BY sh.DATE_MOUVEMENT DESC
        LIMIT 10";
$stmt = $pdo->query($sql);
$derniers_mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des derniers retours produits
$sql = "SELECT rp.*, a.NOMARTICLE, c.NOM as CLIENT_NOM, c.PRENOM as CLIENT_PRENOM
        FROM retour_produit rp
        JOIN article a ON rp.IDARTICLE = a.IDARTICLE
        JOIN client c ON rp.IDCLIENT = c.IDCLIENT
        ORDER BY rp.DATERETOUR DESC
        LIMIT 10";
$stmt = $pdo->query($sql);
$derniers_retours = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Stocks</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $type_message; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <h1 class="mb-4">Gestion des Stocks</h1>

        <!-- Articles en stock critique -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">Articles en Stock Critique</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Article</th>
                                <th>Stock Actuel</th>
                                <th>Seuil d'Alerte</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles_critiques as $article): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($article['REFERENCE']); ?></td>
                                    <td><?php echo htmlspecialchars($article['NOMARTICLE']); ?></td>
                                    <td><?php echo $article['QUANTITESTOCK']; ?></td>
                                    <td><?php echo $article['SEUILALERTE']; ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#ajusterStockModal"
                                                data-article-id="<?php echo $article['IDARTICLE']; ?>"
                                                data-article-nom="<?php echo htmlspecialchars($article['NOMARTICLE']); ?>">
                                            Ajuster Stock
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Formulaires de gestion -->
        <div class="row">
            <!-- Ajustement de stock -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ajuster le Stock</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="ajuster_stock">
                            <div class="mb-3">
                                <label for="article_id" class="form-label">Article</label>
                                <select name="article_id" id="article_id" class="form-select" required>
                                    <option value="">Sélectionnez un article</option>
                                    <?php foreach ($articles as $article): ?>
                                        <option value="<?php echo $article['IDARTICLE']; ?>">
                                            <?php echo htmlspecialchars($article['NOMARTICLE']); ?> 
                                            (Stock: <?php echo $article['QUANTITESTOCK']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="type" class="form-label">Type de mouvement</label>
                                <select name="type" id="type" class="form-select" required>
                                    <option value="ENTREE">Entrée</option>
                                    <option value="SORTIE">Sortie</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="quantite" class="form-label">Quantité</label>
                                <input type="number" class="form-control" id="quantite" name="quantite" required min="1">
                            </div>
                            <div class="mb-3">
                                <label for="motif" class="form-label">Motif</label>
                                <textarea class="form-control" id="motif" name="motif" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Valider</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Retour produit -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Enregistrer un Retour</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="retour_produit">
                            <div class="mb-3">
                                <label for="vente_id" class="form-label">Numéro de Vente</label>
                                <input type="text" class="form-control" id="vente_id" name="vente_id" required>
                            </div>
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client</label>
                                <select name="client_id" id="client_id" class="form-select" required>
                                    <option value="">Sélectionnez un client</option>
                                    <?php
                                    $clients = $pdo->query("SELECT * FROM client ORDER BY NOM, PRENOM")->fetchAll();
                                    foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['IDCLIENT']; ?>">
                                            <?php echo htmlspecialchars($client['NOM'] . ' ' . $client['PRENOM']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="article_retour_id" class="form-label">Article</label>
                                <select name="article_id" id="article_retour_id" class="form-select" required>
                                    <option value="">Sélectionnez un article</option>
                                    <?php foreach ($articles as $article): ?>
                                        <option value="<?php echo $article['IDARTICLE']; ?>">
                                            <?php echo htmlspecialchars($article['NOMARTICLE']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="quantite_retour" class="form-label">Quantité</label>
                                <input type="number" class="form-control" id="quantite_retour" name="quantite" required min="1">
                            </div>
                            <div class="mb-3">
                                <label for="etat" class="form-label">État du produit</label>
                                <select name="etat" id="etat" class="form-select" required>
                                    <option value="DEFECTUEUX">Défectueux</option>
                                    <option value="ENDOMMAGE">Endommagé</option>
                                    <option value="AUTRE">Autre</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="motif_retour" class="form-label">Motif du retour</label>
                                <textarea class="form-control" id="motif_retour" name="motif" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Enregistrer le retour</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique des mouvements -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Derniers Mouvements de Stock</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Article</th>
                                        <th>Type</th>
                                        <th>Quantité</th>
                                        <th>Utilisateur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($derniers_mouvements as $mouvement): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($mouvement['DATE_MOUVEMENT'])); ?></td>
                                            <td><?php echo htmlspecialchars($mouvement['NOMARTICLE']); ?></td>
                                            <td><?php echo $mouvement['TYPE_MOUVEMENT']; ?></td>
                                            <td><?php echo $mouvement['QUANTITE']; ?></td>
                                            <td><?php echo htmlspecialchars($mouvement['NOMUTILISATEUR']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Derniers Retours Produits</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Article</th>
                                        <th>État</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($derniers_retours as $retour): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($retour['DATERETOUR'])); ?></td>
                                            <td><?php echo htmlspecialchars($retour['CLIENT_NOM'] . ' ' . $retour['CLIENT_PRENOM']); ?></td>
                                            <td><?php echo htmlspecialchars($retour['NOMARTICLE']); ?></td>
                                            <td><?php echo $retour['ETAT']; ?></td>
                                            <td><?php echo $retour['STATUT']; ?></td>
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

    <!-- Modal pour ajuster le stock -->
    <div class="modal fade" id="ajusterStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajuster le Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="ajusterStockModalForm" action="" method="POST">
                        <input type="hidden" name="action" value="ajuster_stock">
                        <input type="hidden" name="article_id" id="modal_article_id">
                        <div class="mb-3">
                            <label for="modal_type" class="form-label">Type de mouvement</label>
                            <select name="type" id="modal_type" class="form-select" required>
                                <option value="ENTREE">Entrée</option>
                                <option value="SORTIE">Sortie</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="modal_quantite" class="form-label">Quantité</label>
                            <input type="number" class="form-control" id="modal_quantite" name="quantite" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="modal_motif" class="form-label">Motif</label>
                            <textarea class="form-control" id="modal_motif" name="motif" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" form="ajusterStockModalForm" class="btn btn-primary">Valider</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du modal d'ajustement de stock
        document.addEventListener('DOMContentLoaded', function() {
            var ajusterStockModal = document.getElementById('ajusterStockModal');
            if (ajusterStockModal) {
                ajusterStockModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var articleId = button.getAttribute('data-article-id');
                    var articleNom = button.getAttribute('data-article-nom');
                    
                    var modalTitle = this.querySelector('.modal-title');
                    var modalArticleId = this.querySelector('#modal_article_id');
                    
                    modalTitle.textContent = 'Ajuster le stock - ' + articleNom;
                    modalArticleId.value = articleId;
                });
            }
        });
    </script>
</body>
</html> 