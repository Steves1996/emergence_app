<?php
// medicine_purchase_print.php
require('fpdf/fpdf.php');
include('class/db.php');

class MedicineStockReport extends FPDF {
    function Header() {
        // Logo
        $this->Image('logo.png', 10, 6, 30); // Assurez-vous que le fichier logo.png est présent
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30, 10, 'CENTRE MEDICAL EMERGENCE', 0, 0, 'C');
        $this->Ln(7); // Saut de ligne
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Douala Logbessou', 0, 0, 'C');
        $this->Ln(7); // Saut de ligne
        $this->Cell(0, 10, 'Tel: 691284763 / 674630409', 0, 0, 'C');
        $this->Ln(7); // Saut de ligne
        $this->Cell(0, 10, 'emergence@gmail.com', 0, 0, 'C');
        // Line break
        $this->Ln(20); // Espace après le header
    }

    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Footer content
        $this->Cell(0, 10, 'RF 0001 et NUI 2222222', 0, 0, 'C');
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$object = new db();

// Récupérer les dates de début et de fin depuis l'URL
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Requête SQL avec filtrage par date
$where = "WHERE medicine_purchase_msbs.medicine_purchase_enter_by = '" . $_SESSION["user_id"] . "' ";
if ($start_date && $end_date) {
    $where .= " AND medicine_purchase_msbs.medicine_purchase_datetime BETWEEN '$start_date' AND '$end_date' ";
}

$object->query = "
    SELECT * FROM medicine_purchase_msbs 
    INNER JOIN medicine_msbs 
    ON medicine_msbs.medicine_id = medicine_purchase_msbs.medicine_id 
    INNER JOIN supplier_msbs 
    ON supplier_msbs.supplier_id = medicine_purchase_msbs.supplier_id 
    " . $where . "
    ORDER BY medicine_purchase_msbs.medicine_purchase_id DESC
";

$result = $object->get_result();

// Créer le PDF
$pdf = new MedicineStockReport('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Titre du rapport avec les dates de filtrage
if ($start_date && $end_date) {
    $pdf->Cell(0, 10, 'Rapport des achats du ' . $start_date . ' au ' . $end_date, 0, 1, 'C');
    $pdf->Ln(10); // Espace après le titre
}

// En-têtes du tableau
$pdf->SetFillColor(200, 220, 255); // Couleur de fond des en-têtes
$pdf->Cell(60, 10, 'Nom du médicament', 1, 0, 'L', true);
$pdf->Cell(30, 10, 'Qte ach', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Qte dispo', 1, 0, 'C', true);
$pdf->Cell(20, 10, 'Nv Qte', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'Date d\'achat', 1, 1, 'C', true);

// Données du tableau
foreach ($result as $row) {
    $pdf->Cell(60, 10, $row["medicine_name"], 1, 0, 'L');
    $pdf->Cell(30, 10, $row["medicine_purchase_qty"], 1, 0, 'C');
    $pdf->Cell(30, 10, $row["medicine_available_quantity"], 1, 0, 'C');
    $pdf->Cell(20, 10, '', 1, 0, 'C');
    $pdf->Cell(50, 10, $row["medicine_purchase_datetime"], 1, 1, 'C');
}

// Générer le PDF
$pdf->Output();
?>