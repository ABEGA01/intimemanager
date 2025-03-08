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
            case 'ajouter_client':
                $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
                $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
                $adresse = filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_STRING);

                try {
                    $sql = "INSERT INTO client (NOM, PRENOM, EMAIL, TELEPHONE, ADRESSE)
                            VALUES (:nom, :prenom, :email, :telephone, :adresse)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'email' => $email,
                        'telephone' => $telephone,
                        'adresse' => $adresse
                    ]);

                    $message = "Client ajouté avec succès";
                    $type_message = "success";
                } catch (Exception $e) {
                    $message = "Erreur lors de l'ajout du client: " . $e->getMessage();
                    $type_message = "danger";
                }
                break;

            case 'modifier_client':
                $client_id = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
                $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
                $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
                $adresse = filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_STRING);

                try {
                    $sql = "UPDATE client 
                            SET NOM = :nom,
                                PRENOM = :prenom,
                                EMAIL = :email,
                                TELEPHONE = :telephone,
                                ADRESSE = :adresse
                            WHERE IDCLIENT = :client_id";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'email' => $email,
                        'telephone' => $telephone,
                        'adresse' => $adresse,
                        'client_id' => $client_id
                    ]);

                    $message = "Client modifié avec succès";
                    $type_message = "success";
                } catch (Exception $e) {
                    $message = "Erreur lors de la modification du client: " . $e->getMessage();
                    $type_message = "danger";
                }
                break;

            case 'supprimer_client':
                $client_id = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);

                try {
                    $sql = "DELETE FROM client WHERE IDCLIENT = :client_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['client_id' => $client_id]);

                    $message = "Client supprimé avec succès";
                    $type_message = "success";
                } catch (Exception $e) {
                    $message = "Erreur lors de la suppression du client: " . $e->getMessage();
                    $type_message = "danger";
                }
                break;
        }
    }
}

// Récupération des clients
$sql = "SELECT * FROM client ORDER BY NOM, PRENOM";
$stmt = $pdo->query($sql);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des meilleurs clients (par montant total des achats)
$sql = "SELECT 
            c.IDCLIENT,
            c.NOM,
            c.PRENOM,
            COUNT(v.IDVENTE) as nombre_achats,
            SUM(v.MONTANTFINAL) as montant_total,
            MAX(v.DATEVENTE) as dernier_achat
        FROM client c
        LEFT JOIN vente v ON c.IDCLIENT = v.IDCLIENT
        GROUP BY c.IDCLIENT, c.NOM, c.PRENOM
        ORDER BY montant_total DESC
        LIMIT 5";
$stmt = $pdo->query($sql);
$meilleurs_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Clients</title>
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

        <h1 class="mb-4">Gestion des Clients</h1>

        <!-- Formulaire d'ajout/modification -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Ajouter un Client</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="ajouter_client">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </form>
            </div>
        </div>

        <!-- Liste des clients -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Liste des Clients</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Points Fidélité</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['NOM']); ?></td>
                                    <td><?php echo htmlspecialchars($client['PRENOM']); ?></td>
                                    <td><?php echo htmlspecialchars($client['EMAIL']); ?></td>
                                    <td><?php echo htmlspecialchars($client['TELEPHONE']); ?></td>
                                    <td><?php echo $client['POINTSFIDELITE']; ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modifierClientModal"
                                                data-client='<?php echo json_encode($client); ?>'>
                                            Modifier
                                        </button>
                                        <button class="btn btn-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#supprimerClientModal"
                                                data-client-id="<?php echo $client['IDCLIENT']; ?>"
                                                data-client-nom="<?php echo htmlspecialchars($client['NOM'] . ' ' . $client['PRENOM']); ?>">
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Meilleurs clients -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Top 5 des Meilleurs Clients</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Nombre d'Achats</th>
                                <th>Montant Total</th>
                                <th>Dernier Achat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meilleurs_clients as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['NOM'] . ' ' . $client['PRENOM']); ?></td>
                                    <td><?php echo $client['nombre_achats']; ?></td>
                                    <td><?php echo number_format($client['montant_total'], 2, ',', ' ') . ' €'; ?></td>
                                    <td><?php echo $client['dernier_achat'] ? date('d/m/Y', strtotime($client['dernier_achat'])) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modifier Client -->
    <div class="modal fade" id="modifierClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier un Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="modifierClientForm" action="" method="POST">
                        <input type="hidden" name="action" value="modifier_client">
                        <input type="hidden" name="client_id" id="modal_client_id">
                        <div class="mb-3">
                            <label for="modal_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="modal_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="modal_prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="modal_prenom" name="prenom" required>
                        </div>
                        <div class="mb-3">
                            <label for="modal_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="modal_email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="modal_telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="modal_telephone" name="telephone" required>
                        </div>
                        <div class="mb-3">
                            <label for="modal_adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="modal_adresse" name="adresse" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" form="modifierClientForm" class="btn btn-primary">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Supprimer Client -->
    <div class="modal fade" id="supprimerClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Supprimer un Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le client <span id="client_a_supprimer"></span> ?</p>
                    <form id="supprimerClientForm" action="" method="POST">
                        <input type="hidden" name="action" value="supprimer_client">
                        <input type="hidden" name="client_id" id="supprimer_client_id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="supprimerClientForm" class="btn btn-danger">Supprimer</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du modal de modification
        document.addEventListener('DOMContentLoaded', function() {
            var modifierClientModal = document.getElementById('modifierClientModal');
            if (modifierClientModal) {
                modifierClientModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var client = JSON.parse(button.getAttribute('data-client'));
                    
                    this.querySelector('#modal_client_id').value = client.IDCLIENT;
                    this.querySelector('#modal_nom').value = client.NOM;
                    this.querySelector('#modal_prenom').value = client.PRENOM;
                    this.querySelector('#modal_email').value = client.EMAIL;
                    this.querySelector('#modal_telephone').value = client.TELEPHONE;
                    this.querySelector('#modal_adresse').value = client.ADRESSE;
                });
            }

            var supprimerClientModal = document.getElementById('supprimerClientModal');
            if (supprimerClientModal) {
                supprimerClientModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var clientId = button.getAttribute('data-client-id');
                    var clientNom = button.getAttribute('data-client-nom');
                    
                    this.querySelector('#supprimer_client_id').value = clientId;
                    this.querySelector('#client_a_supprimer').textContent = clientNom;
                });
            }
        });
    </script>
</body>
</html> 