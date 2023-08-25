<?php

function generateRow()
{
	$contents = '';

	include('class/db.php');

	$object = new db();

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

	//use for MySQLi OOP
	$result = $object->get_result();

	$count_medicine = 0;

	foreach ($result as $medicine_item_row) {

		$count_medicine++;

		$contents .= "
			<tr>
				<td>" . $count_medicine . "</td>
				<td>" . $medicine_item_row['medicine_name'] . "</td>
				<td>" . $medicine_item_row['company_name'] . "</td>
				<td>" . $medicine_item_row['medicine_available_quantity'] . "</td>
				<td>" . $medicine_item_row['location_rack_name'] . "</td>
				<td>" . $medicine_item_row['medicine_add_datetime'] . "</td>
				<td>" . $medicine_item_row['medicine_update_datetime'] . "</td>
			</tr>
		";
	}


	return $contents;
}

require_once('tcpdf/tcpdf.php');
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle("CM-Emergence");

$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING, array(0, 64, 255), array(0, 64, 128));
$pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));
// set document information
//$pdf->SetHeaderData('assets/emergence_logo.png', '100', PDF_HEADER_TITLE, PDF_HEADER_STRING);
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont('helvetica');
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetMargins(PDF_MARGIN_LEFT, '10', PDF_MARGIN_RIGHT);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->SetFont('helvetica', '', 12);
$pdf->AddPage();
$content = '';
$content .= '
      	<h2 align="center">CM-Emergence</h2>
      	<h4>Liste des Medicaments</h4>
      	<table border="1" cellspacing="0" cellpadding="1">
           <tr>
				<th width="5%"><b>Sr.</b></th>
				<th width="25%"><b>Nom</b></th>
				<th><b>Fabriquant</b></th>
				<th><b>Quantite disponible</b></th>
				<th><b>Rang sur comptoir</b></th>
				<th><b>Add Date</b></th>
				<th><b>Update Date</b></th>
           </tr>
      ';

$content .= generateRow();
$date  = date('Y-m-d');
$pdf_name = 'medecine_for_' . $date . '.pdf';
$content .= '</table>';
$pdf->writeHTML($content);
ob_end_clean();
$pdf->Output($pdf_name, 'I');
