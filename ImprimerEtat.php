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

// Récupération des dates de la période
$dateDebut = filter_input(INPUT_GET, 'date_debut');
$dateFin = filter_input(INPUT_GET, 'date_fin');

// Récupération des ventes de la période
try {
    $stmt = $con->prepare("
        SELECT v.*, u.NOMUTILISATEUR
        FROM vente v
        JOIN utilisateur u ON v.IDUTILISATEUR = u.IDUTILISATEUR
        WHERE v.DATEVENTE BETWEEN :debut AND :fin
        ORDER BY v.DATEVENTE DESC
    ");
    $stmt->execute(['debut' => $dateDebut, 'fin' => $dateFin]);
    $ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcul des totaux
    $totalVentes = count($ventes);
    $totalCA = array_sum(array_column($ventes, 'MONTANTFINAL'));

    // Création du PDF
    class MYPDF extends TCPDF {
        public function Header() {
            // Logo
            $this->Image('images/intimelogo.png', 15, 10, 30);
            
            // Titre
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 15, 'IntimeManager', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            
            // Ligne de séparation
            $this->Line(15, 30, 195, 30);
            
            // Espacement
            $this->Ln(20);
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
    $pdf->SetTitle('État des ventes du ' . date('d/m/Y', strtotime($dateDebut)) . ' au ' . date('d/m/Y', strtotime($dateFin)));

    // Définition des marges
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Ajout d'une page
    $pdf->AddPage();

    // Période
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Période : du ' . date('d/m/Y', strtotime($dateDebut)) . ' au ' . date('d/m/Y', strtotime($dateFin)), 0, 1, 'R');
    $pdf->Ln(5);

    // Pour chaque vente
    foreach ($ventes as $vente) {
        // En-tête de la vente
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Vente N° ' . $vente['NUMEROVENTE'], 1, 1, 'L', true);
        
        // Informations de la vente
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 7, 'Date :', 0, 0);
        $pdf->Cell(60, 7, date('d/m/Y H:i', strtotime($vente['DATEVENTE'])), 0, 0);
        $pdf->Cell(40, 7, 'Vendeur :', 0, 0);
        $pdf->Cell(50, 7, $vente['NOMUTILISATEUR'], 0, 1);
        
        $pdf->Cell(40, 7, 'Mode de paiement :', 0, 0);
        $pdf->Cell(60, 7, $vente['MODEPAIEMENT'], 0, 0);
        $pdf->Cell(40, 7, 'Montant total :', 0, 0);
        $pdf->Cell(50, 7, number_format($vente['MONTANTFINAL'], 2) . ' €', 0, 1);
        
        // Détails des articles
        $stmt = $con->prepare("
            SELECT dv.*, a.NOMARTICLE, a.REFERENCE
            FROM detail_vente dv
            JOIN article a ON dv.IDARTICLE = a.IDARTICLE
            WHERE dv.IDVENTE = :idvente
        ");
        $stmt->execute(['idvente' => $vente['IDVENTE']]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // En-tête du tableau des détails
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(52, 152, 219); // Couleur bleue claire
        $pdf->SetTextColor(255, 255, 255); // Texte blanc
        $pdf->Cell(40, 7, 'Article', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Quantité', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Prix unitaire', 1, 0, 'R', true);
        $pdf->Cell(40, 7, 'Total', 1, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0); // Retour au texte noir

        // Détails des articles
        $pdf->SetFont('helvetica', '', 10);
        foreach ($details as $detail) {
            $pdf->Cell(60, 6, $detail['NOMARTICLE'], 1, 0);
            $pdf->Cell(30, 6, $detail['REFERENCE'], 1, 0);
            $pdf->Cell(20, 6, $detail['QUANTITE'], 1, 0, 'C');
            $pdf->Cell(30, 6, number_format($detail['PRIXUNITAIRE'], 2) . ' €', 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($detail['QUANTITE'] * $detail['PRIXUNITAIRE'], 2) . ' €', 1, 1, 'R');
        }
        
        $pdf->Ln(10);
    }

    // Totaux
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Résumé', 1, 1, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, 'Nombre total de ventes : ' . $totalVentes, 1, 1, 'L');
    $pdf->Cell(0, 7, 'Chiffre d\'affaires total : ' . number_format($totalCA, 2) . ' €', 1, 1, 'L');

    // Pied de page
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Document généré le ' . date('d/m/Y H:i') . ' par ' . $_SESSION['NOMUTILISATEUR'], 0, 1, 'C');

    // Nettoyer la sortie
    ob_end_clean();

    // Génération du PDF
    $pdf->Output('Etat_Ventes_' . date('Ymd', strtotime($dateDebut)) . '_' . date('Ymd', strtotime($dateFin)) . '.pdf', 'I');

} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
} 