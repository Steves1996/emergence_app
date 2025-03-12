<?php
if (isset($_GET["action"], $_GET["code"]) && $_GET["action"] == 'pdf' && $_GET['code'] != '') {

    function calculateAge($birth_date) {
        $today = new DateTime(); // Date actuelle
        $birth_date = new DateTime($birth_date); // Convertir la date de naissance en objet DateTime
        $age = $today->diff($birth_date); // Calculer la différence
        return $age->y; // Retourner l'âge en années
    }

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include('class/db.php');
    $object = new db();
    $order_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

    // Define company information directly instead of fetching from database
    $store_name = 'CM EMERGENCE';
    $store_address = 'Douala logbessou';
    $store_contact_no = '691284763/674630409';
    $store_email = 'emergence@gmail.com';
    $today = date('Y-m-d');

    $styles = '
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #333;
        }
        .header {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .logo {
            max-width: 150px;
            max-height: 150px;
            float: left;
            margin-right: 20px;
        }
        .company-info {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
        }
        .invoice-title {
            font-size: 24pt;
            color: #2c3e50;
            margin: 30px 0;
            text-align: center;
            clear: both;
        }
        .invoice-details {
            width: 100%;
            margin-bottom: 30px;
        }
        .invoice-details td {
            padding: 5px;
            vertical-align: top;
        }
        .client-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .total-section {
            margin-top: 30px;
            text-align: right;
        }
        .total-row {
            font-size: 14pt;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 10pt;
            color: #666;
        }
        .footer-info {
            margin-top: 10px;
            font-style: italic;
        }
    </style>';

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Facture proforma</title>
        ' . $styles . '
    </head>
    <body>
        <div class="header">
            <img src="logo.png" class="logo" alt="Logo CM EMERGENCE">
            <div class="company-info">
            District de BANGUE<br>
            Hopital de Logbessou<br>
                <strong>'.$store_name.'</strong><br>
                '.$store_address.'<br>
                Tel: '.$store_contact_no.'<br>
                '.$store_email.'
            </div>
        </div>';

    $object->query = "SELECT * FROM order_pro_msbs WHERE order_id = '$order_id'";
    $order_result = $object->get_result();

    foreach($order_result as $order_row) {
        $html .= '
        <div class="invoice-title">FACTURE PROFORMA</div>
        
        <table class="invoice-details">
            <tr>
                <td width="60%">
                    <div class="client-details">
                        <strong>Informations sur le Patient</strong><br>
                        Nom: <strong>'.$order_row["patient_name"].'</strong><br>
                        Age: ' . calculateAge($order_row["birth_date"]) . ' ans <br>
                        Tel: <strong>'.$order_row["phone"].'</strong><br>
                    </div>
                </td>
                <td width="40%">
                    <strong>N° Facture:</strong> CME000'.$order_row["order_id"].'<br>
                    <strong>Date:</strong> '.$order_row["hospitalisation_date"].'<br>
                </td>
            </tr>
        </table>';

        $total_amount = $order_row["order_total_amount"];
        $created_by = $object->Get_user_name_from_id($order_row["order_created_by"]);
    }

    $object->query = "SELECT * FROM order_item_pro_msbs WHERE order_id = '$order_id'";
    $order_item_result = $object->get_result();

    $html .= '
    <table class="items-table">
        <tr>
            <th width="45%">Description</th>
            <th width="15%">Prix Unit.</th>
            <th width="15%">Quantité</th>
            <th width="25%">Total</th>
        </tr>';

    foreach($order_item_result as $order_item_row) {
        $m_data = $object->Get_medicine_name($order_item_row['medicine_id'], $order_item_row["medicine_purchase_id"]);
        $html .= '
        <tr>
            <td>'.$m_data["medicine_name"].'</td>
            <td>'.$object->cur_sym . number_format($order_item_row["medicine_price"], 0).'</td>
            <td>'.$order_item_row["medicine_quantity"].'</td>
            <td>'.$object->cur_sym . number_format($order_item_row["medicine_price"] * $order_item_row["medicine_quantity"], 0).'</td>
        </tr>';
    }

    $html .= '
        <tr class="total-row">
            <td colspan="3" style="text-align: right;"><strong>TOTAL</strong></td>
            <td><strong>'.$object->cur_sym . number_format($total_amount, 0).'</strong></td>
        </tr>
    </table>

    <div class="footer">
      <div>
       <p style="float: left;color:black;"><strong>Direction</strong></p>
       <p style="float: right;color:black;"><strong>Caisse</strong></p>
       <div style="clear: both;"></div>
      </div>
        <p style="color:black;"><strong>N°791/ST/MINSANTE/DRSPL/DSB</strong></p>
        <p>Merci de votre confiance!</p>
        <div class="footer-info">
            <p>'.date('Y').' © '.$store_name.' - Tous droits réservés</p>
        </div>
    </div>
    </body>
    </html>';

    require_once('class/pdf.php');

    try {
        $pdf = new Pdf();
        
        // Configuration for A4 invoice
        $pdf->set_option('enable_html5_parser', true);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isPhpEnabled', true);
        $pdf->set_option('defaultFont', 'Arial');
        $pdf->set_paper('A4', 'portrait');
        
        $file_name = 'FACTURE-' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . '.pdf';

        $pdf->loadHtml($html);
        $pdf->render();
        $pdf->stream($file_name, array("Attachment" => false));
    } catch (Exception $e) {
        echo 'Erreur lors de la génération du PDF : ' . $e->getMessage();
    }
    
    exit(0);
} else {
    header('location:order.php');
}
?>