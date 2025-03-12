<?php
// repport_print_pdf.php
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

// Requête SQL
$object->query = "
     SELECT * FROM medicine_msbs 
    INNER JOIN category_msbs 
    ON category_msbs.category_id = medicine_msbs.medicine_category 
    INNER JOIN  medicine_manufacuter_company_msbs 
    ON  medicine_manufacuter_company_msbs.medicine_manufacuter_company_id = medicine_msbs.medicine_manufactured_by 
    INNER JOIN location_rack_msbs 
    ON location_rack_msbs.location_rack_id = medicine_msbs.medicine_location_rack 
    ORDER BY medicine_msbs.medicine_name ASC
";

$result = $object->get_result();

// Create PDF
$pdf = new MedicineStockReport('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Table headers
$pdf->SetFillColor(200, 220, 255); // Couleur de fond des en-têtes
$pdf->Cell(100, 10, 'Nom du medicament', 1, 0, 'L', true);
$pdf->Cell(45, 10, 'Quantite disponible', 1, 0, 'C', true);
$pdf->Cell(45, 10, 'Quantite reelle', 1, 1, 'C', true);

// Data
foreach ($result as $row) {
    $pdf->Cell(100, 10, $row["medicine_name"], 1, 0, 'L');
    $pdf->Cell(45, 10, $row["medicine_available_quantity"], 1, 0, 'C');
    $pdf->Cell(45, 10, '', 1, 1, 'C'); // Colonne vide pour la quantité réelle
}

// Output the PDF
$pdf->Output();
?>