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

// Récupération de l'ID du produit
$idProduit = filter_input(INPUT_GET, 'produit', FILTER_VALIDATE_INT);

try {
    // Récupération des statistiques du produit
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
    $stmt->execute(['id' => $idProduit]);
    $statsProduit = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupération de l'historique des ventes
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
    $pdf->SetTitle('État des ventes - ' . $statsProduit['NOMARTICLE']);

    // Définition des marges
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Ajout d'une page
    $pdf->AddPage();

    // Informations du produit
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $statsProduit['NOMARTICLE'], 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Référence :', 0, 0);
    $pdf->Cell(60, 7, $statsProduit['REFERENCE'], 0, 0);
    $pdf->Cell(40, 7, 'Stock actuel :', 0, 0);
    $pdf->Cell(50, 7, $statsProduit['QUANTITESTOCK'], 0, 1);
    $pdf->Ln(5);

    // Statistiques
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Statistiques', 1, 1, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, 'Nombre de ventes : ' . $statsProduit['nombre_ventes'], 1, 1, 'L');
    $pdf->Cell(0, 7, 'Quantité vendue : ' . $statsProduit['quantite_vendue'], 1, 1, 'L');
    $pdf->Cell(0, 7, 'Chiffre d\'affaires : ' . number_format($statsProduit['chiffre_affaires'], 2) . ' €', 1, 1, 'L');
    $pdf->Cell(0, 7, 'Bénéfice : ' . number_format($statsProduit['benefice'], 2) . ' €', 1, 1, 'L');
    $pdf->Cell(0, 7, 'Pertes de stock : ' . $statsProduit['pertes_stock'], 1, 1, 'L');
    $pdf->Ln(5);

    // Historique des ventes
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Historique des ventes', 1, 1, 'L', true);
    
    // En-tête du tableau
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(52, 152, 219); // Couleur bleue claire
    $pdf->SetTextColor(255, 255, 255); // Texte blanc
    $pdf->Cell(50, 7, 'Date', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Quantité', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Prix unitaire', 1, 0, 'R', true);
    $pdf->Cell(40, 7, 'Vendeur', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Total', 1, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0); // Retour au texte noir

    // Détails des ventes
    $pdf->SetFont('helvetica', '', 10);
    foreach ($historiqueVentes as $vente) {
        $pdf->Cell(50, 6, date('d/m/Y H:i', strtotime($vente['DATEVENTE'])), 1, 0);
        $pdf->Cell(30, 6, $vente['QUANTITE'], 1, 0, 'C');
        $pdf->Cell(40, 6, number_format($vente['PRIXUNITAIRE'], 2) . ' €', 1, 0, 'R');
        $pdf->Cell(40, 6, $vente['NOMUTILISATEUR'], 1, 0);
        $pdf->Cell(30, 6, number_format($vente['QUANTITE'] * $vente['PRIXUNITAIRE'], 2) . ' €', 1, 1, 'R');
    }

    // Pied de page
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Document généré le ' . date('d/m/Y H:i') . ' par ' . $_SESSION['NOMUTILISATEUR'], 0, 1, 'C');

    // Nettoyer la sortie
    ob_end_clean();

    // Génération du PDF
    $pdf->Output('Etat_Produit_' . $statsProduit['REFERENCE'] . '.pdf', 'I');

} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
} 