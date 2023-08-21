<?php

//print_order.php

if (isset($_GET["action"]) && $_GET["action"] == 'pdf') {
	include('class/db.php');

	$object = new db();

	$html = '
	<table width="100%" border="0" cellpadding="5" cellspacing="0">
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<h2 align="center" style="margin-bottom:15px;">Liste des Medicaments</h2>
			</td>
		</tr>
		<tr>
			<td>
	';


	$object->query = "
	SELECT * FROM medicine_msbs 
    INNER JOIN category_msbs 
    ON category_msbs.category_id = medicine_msbs.medicine_category 
    INNER JOIN  medicine_manufacuter_company_msbs 
    ON  medicine_manufacuter_company_msbs.medicine_manufacuter_company_id = medicine_msbs.medicine_manufactured_by 
    INNER JOIN location_rack_msbs 
    ON location_rack_msbs.location_rack_id = medicine_msbs.medicine_location_rack
    ORDER BY medicine_msbs.medicine_name ASC LIMIT 15
	";

	$medicine_item_result = $object->get_result();

	$html .= '
				<br />
				<table width="100%" border="1" cellpadding="5" cellspacing="0">
					<tr>
						<td width="5%"><b>Sr.</b></td>
						<td width="30%"><b>Nom</b></td>
						<td width="10%"><b>Fabriquant</b></td>
						<td width="5%"><b>Quantite disponible</b></td>
						<td width="10%"><b>Rang sur comptoir</b></td>
						<td width="20%"><b>Add Date</b></td>
						<td width="20%"><b>Update Date</b></td>
					</tr>
	';

	$count_medicine = 0;

	foreach ($medicine_item_result as $medicine_item_row) {
		$count_medicine++;

		$html .= '
					<tr>
						<td>' . $count_medicine . '</td>
						<td>' . $medicine_item_row["medicine_name"] . '</td>
						<td>' . $medicine_item_row["company_name"] . '</td>
						<td>' . $medicine_item_row["medicine_available_quantity"] . '</td>
						<td>' . $medicine_item_row["location_rack_name"] . '</td>
						<td>' . $medicine_item_row["medicine_add_datetime"] . '</td>
						<td>' . $medicine_item_row["medicine_update_datetime"] . '</td>
					</tr>
		';
	}

	$html .= '
				</table>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
	</table>

	';

	//echo $html;

	require_once('class/pdf.php');

	$pdf = new Pdf();

	$pdf->setPaper('letter', 'portrait');

	$file_name = 'Medicines.pdf';

	$pdf->loadHtml($html);
	$pdf->render();
	$pdf->stream($file_name, array("Attachment" => 0));
	exit(0);
} else {
	header('location:medicine.php');
}
