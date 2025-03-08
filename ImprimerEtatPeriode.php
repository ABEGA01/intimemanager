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

try {
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

    // Statistiques globales
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Statistiques globales', 1, 1, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, 'Nombre total de ventes : ' . $statsPeriode['nombre_ventes'], 1, 1, 'L');
    $pdf->Cell(0, 7, 'Chiffre d\'affaires total : ' . number_format($statsPeriode['chiffre_affaires'], 2) . ' €', 1, 1, 'L');
    $pdf->Cell(0, 7, 'Bénéfice total : ' . number_format($statsPeriode['benefice_total'], 2) . ' €', 1, 1, 'L');
    $pdf->Cell(0, 7, 'Nombre de vendeurs : ' . $statsPeriode['nombre_vendeurs'], 1, 1, 'L');
    $pdf->Ln(5);

    // Produits les plus vendus
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Top 5 des produits les plus vendus', 1, 1, 'L', true);
    
    // En-tête du tableau des produits plus vendus
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(52, 152, 219); // Couleur bleue claire
    $pdf->SetTextColor(255, 255, 255); // Texte blanc
    $pdf->Cell(80, 7, 'Article', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Quantité', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Chiffre d\'affaires', 1, 0, 'R', true);
    $pdf->Cell(40, 7, 'Référence', 1, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0); // Retour au texte noir

    // Détails des produits
    $pdf->SetFont('helvetica', '', 10);
    foreach ($produitsPlusVendus as $produit) {
        $pdf->Cell(80, 6, $produit['NOMARTICLE'], 1, 0);
        $pdf->Cell(30, 6, $produit['quantite_vendue'], 1, 0, 'C');
        $pdf->Cell(40, 6, number_format($produit['chiffre_affaires'], 2) . ' €', 1, 0, 'R');
        $pdf->Cell(40, 6, $produit['REFERENCE'], 1, 1);
    }
    $pdf->Ln(5);

    // Produits les moins vendus
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Top 5 des produits les moins vendus', 1, 1, 'L', true);
    
    // En-tête du tableau des produits moins vendus
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(52, 152, 219); // Couleur bleue claire
    $pdf->SetTextColor(255, 255, 255); // Texte blanc
    $pdf->Cell(80, 7, 'Article', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Quantité', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Chiffre d\'affaires', 1, 0, 'R', true);
    $pdf->Cell(40, 7, 'Référence', 1, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0); // Retour au texte noir

    // Détails des produits
    $pdf->SetFont('helvetica', '', 10);
    foreach ($produitsMoinsVendus as $produit) {
        $pdf->Cell(80, 6, $produit['NOMARTICLE'], 1, 0);
        $pdf->Cell(30, 6, $produit['quantite_vendue'], 1, 0, 'C');
        $pdf->Cell(40, 6, number_format($produit['chiffre_affaires'], 2) . ' €', 1, 0, 'R');
        $pdf->Cell(40, 6, $produit['REFERENCE'], 1, 1);
    }

    // Pied de page
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Document généré le ' . date('d/m/Y H:i') . ' par ' . $_SESSION['NOMUTILISATEUR'], 0, 1, 'C');

    // Nettoyer la sortie
    ob_end_clean();

    // Génération du PDF
    $pdf->Output('Etat_Periode_' . date('Ymd', strtotime($dateDebut)) . '_' . date('Ymd', strtotime($dateFin)) . '.pdf', 'I');

} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
} 