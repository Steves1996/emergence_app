<?php

function generateRow()
{
    $contents = '';

    include('class/db.php');

    $object = new db();

    $object->query = "
    SELECT mm.medicine_name, oim.medicine_quantity, oim.medicine_price, om.order_added_on, um.user_name
    FROM `order_item_msbs` oim 
    JOIN medicine_msbs mm 
    on oim.medicine_id = mm.medicine_id
    JOIN order_msbs om
    ON oim.order_id = om.order_id
    JOIN user_msbs um
    ON om.order_created_by = um.user_id
	";

    //use for MySQLi OOP
    $result = $object->get_result();

    $count_medicine = 0;
    $TotalAmount = 0;

    foreach ($result as $repport_item_row) {

        $TotalAmount += $repport_item_row["medicine_price"];

        $count_medicine++;

        $contents .= "
			<tr>
				<td>" . $count_medicine . "</td>
				<td>" . $repport_item_row['medicine_name'] . "</td>
				<td>" . $repport_item_row['order_added_on'] . "</td>
				<td>" . $repport_item_row['user_name'] . "</td>
				<td>" . $repport_item_row['medicine_quantity'] . "</td>
				<td>" . $repport_item_row['medicine_price'] . "</td>
			</tr>
		";
    }


    return $contents;
}

require_once('tcpdf/tcpdf.php');
$pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle("CM-Emergence");
$pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont('helvetica');
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetMargins(PDF_MARGIN_LEFT, '10', PDF_MARGIN_RIGHT);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->SetFont('helvetica', '', 11);
$pdf->AddPage();
$content = '';
$content .= '
      	<h2 align="center">CM-Emergence</h2>
      	<h4>Rapport des Ventes</h4>
      	<table border="1" cellspacing="0" cellpadding="1">
           <tr>
				<th width="5%"><b>Sr.</b></th>
				<th><b>Nom du medicament</b></th>
				<th><b>date de vente</b></th>
				<th><b>Vendeur</b></th>
				<th><b>Quantite vendu</b></th>
				<th><b>Prix total</b></th>
           </tr>
';

$content .= generateRow();
$date  = date('Y-m-d');
$pdf_name = 'repport_for_' . $date . '.pdf';
$content .= '</table>';
$pdf->writeHTML($content);
ob_end_clean();
$pdf->Output($pdf_name, 'I');
