<?php
// Désactiver la mise en mémoire tampon de sortie
ob_start();

// Désactiver l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once('Connexion.php');
require_once('tcpdf/tcpdf.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
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

    // Création du PDF
    class MYPDF extends TCPDF {
        public function Header() {
            // Logo
            $this->Image('images/intimelogo.png', 15, 2, 18);
            
            // Titre
            $this->SetFont('helvetica', 'B', 20);
            $this->Cell(0, 15, 'FACTURE', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    // Création du document PDF
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configuration du document
    $pdf->SetCreator('Gestion de Stock');
    $pdf->SetAuthor('INTIME');
    $pdf->SetTitle('Facture #' . $vente['NUMEROVENTE']);

    // Définition des marges
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Ajout d'une page
    $pdf->AddPage();

    // Informations de l'entreprise
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'INTIME', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Mini Prix Bastos Face MTN', 0, 1, 'L');
    $pdf->Cell(0, 5, 'Email : abegarachel0@gmail.com', 0, 1, 'L');
    $pdf->Ln(10);

    // Informations de la facture
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Facture #' . $vente['NUMEROVENTE'], 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Date : ' . date('d/m/Y', strtotime($vente['DATEVENTE'])), 0, 1, 'L');
    $pdf->Cell(0, 5, 'Vendeur : ' . $vente['NOMUTILISATEUR'], 0, 1, 'L');
    $pdf->Ln(10);

    // En-tête du tableau
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(80, 7, 'Article', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Référence', 1, 0, 'L', true);
    $pdf->Cell(20, 7, 'Quantité', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Prix unitaire', 1, 0, 'R', true);
    $pdf->Cell(30, 7, 'Total', 1, 1, 'R', true);

    // Détails des articles
    $pdf->SetFont('helvetica', '', 10);
    foreach ($details as $detail) {
        $pdf->Cell(80, 6, $detail['NOMARTICLE'], 1, 0, 'L');
        $pdf->Cell(30, 6, $detail['REFERENCE'], 1, 0, 'L');
        $pdf->Cell(20, 6, $detail['QUANTITE'], 1, 0, 'C');
        $pdf->Cell(30, 6, number_format($detail['PRIXUNITAIRE'], 2) . ' €', 1, 0, 'R');
        $pdf->Cell(30, 6, number_format($detail['QUANTITE'] * $detail['PRIXUNITAIRE'], 2) . ' €', 1, 1, 'R');
    }

    // Total
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(160, 7, 'Total TTC', 1, 0, 'R', true);
    $pdf->Cell(30, 7, number_format($vente['MONTANTFINAL'], 2) . ' €', 1, 1, 'R', true);

    // Mode de paiement
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Mode de paiement : ' . $vente['MODEPAIEMENT'], 0, 1, 'L');

    // Nettoyer la sortie
    ob_end_clean();

    // Génération du PDF
    $pdf->Output('Facture_' . $vente['NUMEROVENTE'] . '.pdf', 'I');

} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #<?php echo $vente['NUMEROVENTE']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 20px;
            }
            .table th {
                background-color: #f8f9fa !important;
                color: #000 !important;
            }
        }
        body {
            font-family: Arial, sans-serif;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 200px;
            margin-bottom: 20px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .invoice-info {
            margin-bottom: 30px;
        }
        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- En-tête de la facture -->
    <div class="header">
        <img src="images/intimelogo.png" alt="Logo">
        <h2>IntimeManager</h2>
        <p>123 Rue de l'Entreprise<br>75000 Paris</p>
        <p>Tél : 01 23 45 67 89<br>Email : contact@intimemanager.com</p>
    </div>

    <div class="container">
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
            <table class="table table-bordered">
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

        <div class="row mt-4">
            <div class="col-12">
                <p class="text-center">Merci de votre confiance !</p>
            </div>
        </div>
    </div>

    <div class="no-print text-center mt-4">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimer
        </button>
    </div>

    <script>
        // Imprimer automatiquement
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html> 