<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    die('Non autorisé');
}

// Vérification de l'ID de la vente
if (!isset($_GET['id'])) {
    die('ID de vente manquant');
}

$idVente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    // Récupération des informations de la vente
    $stmt = $con->prepare("SELECT v.*, u.NOMUTILISATEUR 
                          FROM vente v 
                          JOIN utilisateur u ON v.IDUTILISATEUR = u.IDUTILISATEUR 
                          WHERE v.IDVENTE = :id");
    $stmt->execute(['id' => $idVente]);
    $vente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vente) {
        die('Vente non trouvée');
    }

    // Récupération des détails de la vente
    $stmt = $con->prepare("SELECT dv.*, a.REFERENCE, a.NOMARTICLE 
                          FROM detail_vente dv 
                          JOIN article a ON dv.IDARTICLE = a.IDARTICLE 
                          WHERE dv.IDVENTE = :id");
    $stmt->execute(['id' => $idVente]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h6 class="text-muted">Informations de la vente</h6>
        <p><strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($vente['DATEVENTE'])); ?></p>
        <p><strong>Vendeur :</strong> <?php echo htmlspecialchars($vente['NOMUTILISATEUR']); ?></p>
        <p><strong>Mode de paiement :</strong> <?php echo htmlspecialchars($vente['MODEPAIEMENT']); ?></p>
    </div>
    <div class="col-md-6 text-md-end">
        <h6 class="text-muted">Total de la vente</h6>
        <h3 class="text-primary"><?php echo number_format($vente['MONTANTFINAL'], 2); ?> €</h3>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Article</th>
                <th>Référence</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $detail): ?>
            <tr>
                <td><?php echo htmlspecialchars($detail['NOMARTICLE']); ?></td>
                <td><?php echo htmlspecialchars($detail['REFERENCE']); ?></td>
                <td><?php echo $detail['QUANTITE']; ?></td>
                <td><?php echo number_format($detail['PRIXUNITAIRE'], 2); ?> €</td>
                <td><?php echo number_format($detail['QUANTITE'] * $detail['PRIXUNITAIRE'], 2); ?> €</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end"><strong>Total</strong></td>
                <td><strong><?php echo number_format($vente['MONTANTFINAL'], 2); ?> €</strong></td>
            </tr>
        </tfoot>
    </table>
</div> 