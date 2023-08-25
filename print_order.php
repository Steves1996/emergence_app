<?php
if (isset($_GET["action"], $_GET["code"]) && $_GET["action"] == 'pdf' && $_GET['code'] != '') {

    include('class/db.php');
    $GLOBALS['object'] = new db();

    function generateRow()
    {
        $contents = '';

        $object = $GLOBALS['object'];

        $order_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

        $object->query = "
        SELECT * FROM order_item_msbs 
        WHERE order_id = '$order_id'
        ";

        $order_item_result = $object->get_result();

        $count_medicine = 0;
        $medicine_name = '';
        $medicine_pack_qty = '';
        $company_short_name = '';
        $medicine_batch_no = '';
        $expiry_date = '';
        $medicine_price = '';
        $medicine_quantity = '';
        $sale_price = '';

        foreach ($order_item_result as $order_item_row) {
            $count_medicine++;

            $m_data = $object->Get_medicine_name($order_item_row['medicine_id'], $order_item_row["medicine_purchase_id"]);

            $medicine_name = $m_data["medicine_name"];
            $medicine_pack_qty = $m_data["medicine_pack_qty"];
            $company_short_name = $m_data["company_short_name"];
            $medicine_batch_no = $m_data["medicine_batch_no"];
            $expiry_date = $m_data["expiry_date"];
            $medicine_price = $order_item_row["medicine_price"];
            $medicine_quantity = $order_item_row["medicine_quantity"];
            $sale_price = $object->cur_sym . number_format(floatval($order_item_row["medicine_price"] * $order_item_row["medicine_quantity"]), 2, '.', ',');

            $contents .= "	
            <tr>
                <td>" . $count_medicine . "</td>
                <td>" . $medicine_name . "</td>
                <td>" . $medicine_pack_qty . "</td>
                <td>" . $company_short_name . "</td>
                <td>" . $expiry_date . "</td>
                <td>" . $medicine_price . "</td>
                <td>" . $medicine_quantity . "</td>
                <td>" . $sale_price . "</td>
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

    $order_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

    $object->query = "
    SELECT * FROM store_msbs 
    LIMIT 1
    ";

    //use for MySQLi OOP
    $store_result = $object->get_result();

    $store_name = '';
    $store_address = '';
    $store_contact_no = '';
    $store_email = '';

    foreach ($store_result as $store_row) {
        $store_name = $store_row['store_name'];
        $store_address = $store_row['store_address'];
        $store_contact_no = $store_row['store_contact_no'];
        $store_email = $store_row['store_email_address'];
    }

    $object->query = "
    SELECT * FROM order_msbs 
    WHERE order_id = '$order_id'
    ";

    $total_amount = 0;
    $order_idr = '';
    $created_by = '';
    $order_date = '';
    $doctor_name = '';
    $patient_name = '';
    $order_result = $object->get_result();

    foreach ($order_result as $order_row) {
        $order_idr = $order_row['order_id'];
        $patient_name = $order_row['patient_name'];
        $doctor_name = $order_row['doctor_name'];
        $order_date = $order_row['order_added_on'];
        $total_amount = $order_row['order_total_amount'];
    }

    $created_by = $object->Get_user_name_from_id($order_row["order_created_by"]);


    $content = '';
    $content .= '
                <div align="center">
                    <h2 align="center">' . $store_name . '</h2>
                    <div align="center">' . $store_address . '</div>
                    <div align="center"><b>Phone No. : </b>' . $store_contact_no . '<br><br><b>Email : </b>' . $store_email . '</div>
                </div>                    
                <div align="left">
                    <div style="margin-bottom:8px;"><b>Order No : </b>' . $order_idr . '</div>
                    <div style="margin-bottom:8px;"><b>Patient Name : </b>' . $patient_name . '</div>
                    <div style="margin-bottom:8px;"><b>Doctor Name  : </b>' . $doctor_name . '</div>
                                                    <b>Date         : </b>' . $order_date . '  
                </div>
                <div align="right"><h2>
                    <div><b>Montant Total : </b>' . $object->cur_sym . number_format(floatval($total_amount), 2, '.', ' ') . '</div></h2>
                </div>
                <h6 align="right">Created By ' . $created_by . '</h6>

            <table width="100%" border="1" cellpadding="2" cellspacing="0">
                
                <tr>
                    <td width="5%"><b>Sr.</b></td>
                    <td width="30 %"><b>Particular</b></td>
                    <td><b>Pack</b></td>
                    <td width="10%"><b>Mfg.</b></td>
                    <td><b>Expiry Dt.</b></td>
                    <td><b>MRP</b></td>
                    <td width="5%"><b>Qty.</b></td>
                    <td width="15%"><b>Sale Price</b></td>
                </tr>
    ';

    $content .= generateRow();
    $date  = date('Y-m-d');
    $pdf_name = 'repport_for_' . $date . '.pdf';
    $content .= '</table>';
    $pdf->writeHTML($content);
    ob_end_clean();
    $pdf->Output($pdf_name, 'I');
}
