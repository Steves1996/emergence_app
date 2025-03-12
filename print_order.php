<?php
//print_order.php

/*if(isset($_GET["action"], $_GET["code"]) && $_GET["action"] == 'pdf' && $_GET['code'] != '')
{
    // Activation du rapport d'erreurs pour le débogage
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include('class/db.php');

    $object = new db();

    $order_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

    $object->query = "
    SELECT * FROM store_msbs 
    LIMIT 1
    ";

    $store_result = $object->get_result();

    $store_name = '';
    $store_address = '';
    $store_contact_no = '';
    $store_email = '';
    $store_logo = '';
	$today = date('Y-m-d'); 

    foreach($store_result as $store_row)
    {
        $store_name = $store_row['store_name'];
        $store_address = $store_row['store_address'];
        $store_contact_no = $store_row['store_contact_no'];
        $store_email = $store_row['store_email_address'];
        $store_logo = $store_row['store_logo'] ?? '';
    }

    // CSS styles améliorés
    $styles = '
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        header {
            position: fixed;
            top: 0mm;
            left: 0mm;
            right: 0mm;
            height: 35mm;
            text-align: center;
            border-bottom: 1px solid #000;
            padding: 10px;
        }
        footer {
            position: fixed;
            bottom: 0mm;
            left: 0mm;
            right: 0mm;
            height: 20mm;
            text-align: center;
            font-size: 12px;
            border-top: 1px solid #000;
            padding: 5px;
        }
        main {
            margin-top: 40mm;
            margin-bottom: 25mm;
            padding: 10mm;
        }
        .logo {
            max-width: 100px;
            max-height: 100px;
            margin-bottom: 10px;
        }
        .store-info {
            margin-bottom: 20px;
            text-align: center;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
            padding-right: 20px;
        }
        .info-table td {
            padding: 8px;
            vertical-align: top;
        }
    </style>
    ';

    // Structure HTML complète
    $html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Facture</title>
        ' . $styles . '
    </head>
    <body>
    <header>
        <img src="'.$store_logo.'" class="logo" alt="Store Logo">
        <h2 style="margin:5px 0">'.$store_name.'</h2>
        <div>'.$store_address.'</div>
        <div>Tel: '.$store_contact_no.' | Email: '.$store_email.'</div>
    </header>
    
    <footer>
        <div>Merci de votre confiance!</div>
        <div>'.date('Y').' © '.$store_name.' - Tous droits réservés</div>
        <div>Page {PAGENO}</div>
    </footer>

    <main>
        <div class="invoice-title">FACTURE</div>
    ';

    $object->query = "
    SELECT * FROM order_msbs 
    WHERE order_id = '$order_id'
    ";

    $order_result = $object->get_result();

    foreach($order_result as $order_row)
    {
        $html .= '
        <table class="info-table" width="100%" border="1" cellpadding="5" cellspacing="0">
            <tr>
                <td width="50%">
                    <strong>Facture No:</strong> '.$order_row["order_id"].'<br>
                    <strong>Date:</strong> '.$order_row["order_added_on"].'<br>
                    <strong>Client:</strong> '.$order_row["patient_name"].'<br>
                    <strong>Docteur:</strong> '.$order_row["doctor_name"].'
                </td>
                <td width="50%" align="right">
                    <strong>Mode de paiement:</strong> ESPÈCES<br>
                    <strong>Statut:</strong> PAYÉ
                </td>
            </tr>
        </table>
        ';

        $total_amount = $order_row["order_total_amount"];
        $created_by = $object->Get_user_name_from_id($order_row["order_created_by"]);
    }

    $object->query = "
    SELECT * FROM order_item_msbs 
    WHERE order_id = '$order_id'
    ";

    $order_item_result = $object->get_result();

    $html .= '
    <table width="100%" border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>N°</th>
                <th>Description</th>
                <th>Prix Unit.</th>
                <th>Qté</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
    ';

    $count_medicine = 0;

    foreach($order_item_result as $order_item_row)
    {
        $count_medicine++;
        $m_data = $object->Get_medicine_name($order_item_row['medicine_id'], $order_item_row["medicine_purchase_id"]);

        $html .= '
        <tr>
            <td>'.$count_medicine.'</td>
            <td>'.$m_data["medicine_name"].'</td>
            <td align="right">'.$object->cur_sym . number_format($order_item_row["medicine_price"], 0).'</td>
            <td align="center">'.$order_item_row["medicine_quantity"].'</td>
            <td align="right">'.$object->cur_sym . number_format($order_item_row["medicine_price"] * $order_item_row["medicine_quantity"], 0).'</td>
        </tr>
        ';
    }

    $html .= '
        <tr class="total-row">
            <td colspan="8" align="right"><strong>Total</strong></td>
            <td align="right">'.$object->cur_sym . number_format($total_amount, 0).'</td>
        </tr>
        </tbody>
    </table>

    <div class="signature">
        <p>Créé par: '.$created_by.' le '. $today.'</p>
    </div>
    </main>
    </body>
    </html>
    ';

    require_once('class/pdf.php');

    try {
        $pdf = new Pdf();
        
        // Configuration du PDF
        $pdf->set_option('enable_html5_parser', true);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isPhpEnabled', true);
        $pdf->set_option('defaultFont', 'Arial');
        $pdf->set_paper('A3', 'landscape');
        
        // Marges
        $pdf->set_option('margin-top', '10mm');
        $pdf->set_option('margin-bottom', '10mm');
        $pdf->set_option('margin-left', '10mm');
        $pdf->set_option('margin-right', '10mm');
        
        $file_name = 'FACTURE-' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . '.pdf';

        $pdf->loadHtml($html);
        $pdf->render();
        $pdf->stream($file_name, array("Attachment" => false));
    } catch (Exception $e) {
        echo 'Erreur lors de la génération du PDF : ' . $e->getMessage();
    }
    
    exit(0);
}*/
/*else
{
    header('location:order.php');
}*/

if(isset($_GET["action"], $_GET["code"]) && $_GET["action"] == 'pdf' && $_GET['code'] != '')
{
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include('class/db.php');

    $object = new db();

    $order_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

    $object->query = "SELECT * FROM store_msbs LIMIT 1";
    $store_result = $object->get_result();

    $store_name = '';
    $store_address = '';
    $store_contact_no = '';
    $store_email = '';
    $store_logo = '';
    $today = date('Y-m-d');

    foreach($store_result as $store_row) {
        $store_name = $store_row['store_name'];
        $store_address = $store_row['store_address'];
        $store_contact_no = $store_row['store_contact_no'];
        $store_email = $store_row['store_email_address'];
        $store_logo = $store_row['store_logo'] ?? '';
    }

    $styles = '
    <style>
        @page {
            margin: 2mm;
            size: 76mm auto;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            width: 72mm; /* 76mm - 4mm de marges */
        }
        .header {
            text-align: center;
            margin-bottom: 5mm;
        }
        .logo {
            max-width: 30mm;
            max-height: 30mm;
            margin: 0 auto;
        }
        .store-info {
            margin-bottom: 3mm;
            text-align: center;
        }
        .invoice-title {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            margin: 2mm 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 1mm 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
        }
        th, td {
            padding: 1mm;
            font-size: 8pt;
            text-align: left;
        }
        .item-table th {
            border-bottom: 1px solid #000;
        }
        .total-row {
            font-weight: bold;
            border-top: 1px solid #000;
        }
        .footer {
            text-align: center;
            margin-top: 5mm;
            border-top: 1px dashed #000;
            padding-top: 2mm;
            font-size: 7pt;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }
    </style>';

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Ticket</title>
        ' . $styles . '
    </head>
    <body>
        <div class="header">
            <img src="'.$store_logo.'" class="logo" alt="">
            <div class="store-info">
                <strong>CM '.$store_name.'</strong><br>
                '.$store_address.'<br>
                Tel: '.$store_contact_no.'<br>
                '.$store_email.'
            </div>
        </div>';

    $object->query = "SELECT * FROM order_msbs WHERE order_id = '$order_id'";
    $order_result = $object->get_result();

    foreach($order_result as $order_row) {
        $html .= '
        <div class="invoice-title">TICKET DE CAISSE</div>
        <table>
            <tr>
                <td>N° Facture:</td>
                <td>'.$order_row["order_id"].'</td>
            </tr>
            <tr>
                <td>Date:</td>
                <td>'.$order_row["order_added_on"].'</td>
            </tr>
            <tr>
                <td>Client:</td>
                <td>'.$order_row["patient_name"].'</td>
            </tr>
            <tr>
                <td>Docteur:</td>
                <td>'.$order_row["doctor_name"].'</td>
            </tr>
        </table>
        <div class="divider"></div>';

        $total_amount = $order_row["order_total_amount"];
        $created_by = $object->Get_user_name_from_id($order_row["order_created_by"]);
    }

    $object->query = "SELECT * FROM order_item_msbs WHERE order_id = '$order_id'";
    $order_item_result = $object->get_result();

    $html .= '
    <table class="item-table">
        <tr>
            <th>Article</th>
            <th>Qté</th>
            <th>Total</th>
        </tr>';

    foreach($order_item_result as $order_item_row) {
        $m_data = $object->Get_medicine_name($order_item_row['medicine_id'], $order_item_row["medicine_purchase_id"]);
        $html .= '
        <tr>
            <td>'.$m_data["medicine_name"].'<br>
                <small>'.$object->cur_sym . number_format($order_item_row["medicine_price"], 0).' x '.$order_item_row["medicine_quantity"].'</small>
            </td>
            <td>'.$order_item_row["medicine_quantity"].'</td>
            <td>'.$object->cur_sym . number_format($order_item_row["medicine_price"] * $order_item_row["medicine_quantity"], 0).'</td>
        </tr>';
    }

    $html .= '
        <tr class="total-row">
            <td colspan="2">TOTAL</td>
            <td>'.$object->cur_sym . number_format($total_amount, 0).'</td>
        </tr>
    </table>

    <div class="footer">
        <p>Créé par: '.$created_by.'<br>
        le '. $today.'</p>
        <p>Merci de votre confiance!</p>
        <p>'.date('Y').' © '.$store_name.'</p>
    </div>
    </body>
    </html>';

    require_once('class/pdf.php');

    try {
        $pdf = new Pdf();
        
        // Configuration spécifique pour ticket de caisse
        $pdf->set_option('enable_html5_parser', true);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isPhpEnabled', true);
        $pdf->set_option('defaultFont', 'Arial');
        $pdf->set_paper(array(0, 0, 215.433070866, 841.889763779528), 'portrait'); // 76mm = 215.433070866pt
        
        $file_name = 'TICKET-' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . '.pdf';

        $pdf->loadHtml($html);
        $pdf->render();
        $pdf->stream($file_name, array("Attachment" => false));
    } catch (Exception $e) {
        echo 'Erreur lors de la génération du PDF : ' . $e->getMessage();
    }
    
    exit(0);
}
else
{
    header('location:order.php');
}