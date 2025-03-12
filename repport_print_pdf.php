<?php
// repport_print_pdf.php
require('fpdf/fpdf.php');
include('class/db.php');

class SalesReport extends FPDF {
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
        $this->Ln(10); // Saut de ligne
        $this->SetFont('Arial', 'B', 14); // Police en gras
        $this->SetFillColor(200, 220, 255); // Couleur de surlignage (bleu clair)
        $this->Cell(0, 10, 'RAPPORT DES VENTES', 0, 0, 'C', true); // 'true' active le surlignage
        // Line break
        $this->Ln(20); // Espace après le header
    }

    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$object = new db();

// Récupérer les paramètres de filtrage
$where_clause = "";
$conditions = array();

if (isset($_GET['from_date']) && isset($_GET['to_date']) && $_GET['from_date'] != '' && $_GET['to_date'] != '') {
    $conditions[] = "om.order_added_on BETWEEN '" . $_GET['from_date'] . "' AND '" . $_GET['to_date'] . "'";
}

if (isset($_GET['medicine_id']) && $_GET['medicine_id'] != '') {
    $conditions[] = "mm.medicine_id = '" . $_GET['medicine_id'] . "'";
}

if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(' AND ', $conditions);
}

// Requête pour récupérer les ventes groupées par date
$object->query = "
    SELECT 
        DATE(om.order_added_on) as order_date, 
        SUM(oim.medicine_quantity) as total_quantity, 
        SUM(oim.medicine_price * oim.medicine_quantity) as total_sales,
        SUM(om.reduction) as total_reduction,
        SUM(om.amount_not_reduction) as total_amount_not_reduction,
        SUM(om.order_total_amount) as total_order_amount
    FROM order_item_msbs oim 
    JOIN medicine_msbs mm ON oim.medicine_id = mm.medicine_id
    JOIN order_msbs om ON oim.order_id = om.order_id
    JOIN user_msbs um ON om.order_created_by = um.user_id
    $where_clause
    GROUP BY DATE(om.order_added_on)
    ORDER BY order_date DESC
";

$result = $object->get_result();

// Créer le PDF
$pdf = new SalesReport('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// En-têtes du tableau
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(30, 6, 'Date', 1, 0, 'C', true);
$pdf->Cell(40, 6, 'Quantité vendue', 1, 0, 'C', true);
$pdf->Cell(40, 6, 'Total des ventes', 1, 0, 'C', true);
$pdf->Cell(30, 6, 'Réduction', 1, 0, 'C', true);
$pdf->Cell(40, 6, 'Montant total payé', 1, 1, 'C', true);

// Données
$totalAmount = 0;
foreach ($result as $row) {
    $totalAmount += $row["total_order_amount"];

    $pdf->Cell(30, 6, $row["order_date"], 1, 0, 'C');
    $pdf->Cell(40, 6, $row["total_quantity"], 1, 0, 'C');
    $pdf->Cell(40, 6, number_format($row["total_sales"], 0) . ' XAF', 1, 0, 'R');
    $pdf->Cell(30, 6, number_format($row["total_reduction"], 0) . ' XAF', 1, 0, 'R');
    $pdf->Cell(40, 6, number_format($row["total_order_amount"], 0) . ' XAF', 1, 1, 'R');
}

// Total
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(140, 6, 'Total général', 1, 0, 'R', true);
$pdf->Cell(40, 6, number_format($totalAmount, 0) . ' XAF', 1, 1, 'R', true);

// Ajouter une section pour les détails des ventes par date
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Détails des ventes par date', 0, 1, 'C', true);

// Requête pour récupérer les ventes groupées par date
$object->query = "
    SELECT 
        DATE(om.order_added_on) as order_date, 
        SUM(oim.medicine_quantity) as total_quantity, 
        SUM(oim.medicine_price * oim.medicine_quantity) as total_sales,
        SUM(om.reduction) as total_reduction,
        SUM(om.amount_not_reduction) as total_amount_not_reduction,
        SUM(om.order_total_amount) as total_order_amount
    FROM order_item_msbs oim 
    JOIN medicine_msbs mm ON oim.medicine_id = mm.medicine_id
    JOIN order_msbs om ON oim.order_id = om.order_id
    JOIN user_msbs um ON om.order_created_by = um.user_id
    $where_clause
    GROUP BY DATE(om.order_added_on)
    ORDER BY order_date DESC
";

$results = $object->get_result();

foreach ($results as $row) {
    $dateOrder = $row["order_date"];
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Date : ' . $dateOrder, 0, 1, 'L');
    $pdf->Ln(5);

    // Construire la clause WHERE pour les détails
    $details_where_clause = "WHERE DATE(om.order_added_on) = '" . $dateOrder . "'";
    if (isset($_GET['medicine_id']) && $_GET['medicine_id'] != '') {
        $details_where_clause .= " AND mm.medicine_id = '" . $_GET['medicine_id'] . "'";
    }

    // Requête pour récupérer les détails des ventes pour cette date
    $object->query = "
        SELECT 
            mm.medicine_name, 
            oim.medicine_quantity, 
            oim.medicine_price,
            om.reduction,
            om.order_total_amount,
            um.user_name
        FROM order_item_msbs oim 
        JOIN medicine_msbs mm ON oim.medicine_id = mm.medicine_id
        JOIN order_msbs om ON oim.order_id = om.order_id
        JOIN user_msbs um ON om.order_created_by = um.user_id
        $details_where_clause
        ORDER BY om.order_added_on DESC
    ";
    $details = $object->get_result();

    // En-têtes du tableau des détails
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(60, 6, 'Médicament', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Quantité', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Prix unitaire', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Réduction', 1, 0, 'C', true);
    $pdf->Cell(40, 6, 'Montant total', 1, 1, 'C', true);

    // Détails des ventes
    foreach ($details as $detail) {
        $pdf->Cell(60, 6, $detail["medicine_name"], 1, 0, 'L');
        $pdf->Cell(30, 6, $detail["medicine_quantity"], 1, 0, 'C');
        $pdf->Cell(30, 6, number_format($detail["medicine_price"], 0) . ' XAF', 1, 0, 'R');
        $pdf->Cell(30, 6, number_format($detail["reduction"], 0) . '%', 1, 0, 'R');
        $pdf->Cell(40, 6, number_format($detail["order_total_amount"], 0) . ' XAF', 1, 1, 'R');
    }

    $pdf->Ln(10); // Espace entre les dates
}

// Générer le PDF
$pdf->Output();