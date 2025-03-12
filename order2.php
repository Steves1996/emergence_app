<div class="container-fluid px-4">
    <h1 class="mt-4">Gestion des ventes</h1>

    <?php if (isset($_GET["action"], $_GET["code"]) && $_GET["action"] == 'add') { ?>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="order.php">Gestion des ventes</a></li>
            <li class="breadcrumb-item active">Ajouter une vente</li>
        </ol>

        <?php if (isset($error) && $error != '') { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="list-unstyled"><?php echo $error; ?></ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <span id="msg_area"></span>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-plus"></i> Ajouter une vente
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row mb-3">
                        <div class="col-md-10">
                            <select class="form-control" id="add_medicine_id">
                                <?php echo $object->get_medicine_array(); ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" name="add_medicine" id="add_medicine" class="btn btn-primary w-100">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="30%">Medicine</th>
                                    <th width="10%">Pack Type</th>
                                    <th width="5%">Mfg</th>
                                    <th width="10%">Batch No.</th>
                                    <th width="10%">Expiry Date</th>
                                    <th width="10%">Quantity</th>
                                    <th width="10%">Unit Price</th>
                                    <th width="10%">Total Price</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="order_item_area"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" align="right"><b>Total</b></td>
                                    <td colspan="2" id="order_total_amount">0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-4 mb-0">
                        <input type="hidden" name="order_total_amount" id="hidden_order_total_amount" value="0" />
                        <input type="submit" name="add_order" class="btn btn-success" value="Add" />
                    </div>
                </form>
            </div>
        </div>

        <script>
            function _(element) {
                return document.getElementById(element);
            }

            let mySelect = new vanillaSelectBox('#add_medicine_id', {
                maxWidth: 600,
                maxHeight: 400,
                minWidth: 500,
                search: true,
                placeHolder: "Filter Medicine",
                minOptionWidth: 500,
                maxOptionWidth: 600
            });

            _('add_medicine').onclick = function() {
                var med_id = _('add_medicine_id').value;
                if (med_id == '') {
                    _('msg_area').innerHTML = '<div class="alert alert-danger">Please Select Medicine</div>';
                    setTimeout(function() {
                        _('msg_area').innerHTML = '';
                    }, 5000);
                } else {
                    var form_data = new FormData();
                    form_data.append('med_id', med_id);
                    form_data.append('action', 'fetch_medicine_data');
                    fetch('action.php', {
                        method: "POST",
                        body: form_data
                    }).then(function(response) {
                        return response.json();
                    }).then(function(responseData) {
                        var no = random_number(1, 99999);
                        var html = '<tr id="' + no + '">';
                        html += '<td>' + responseData.medicine_name + '<input type="hidden" name="medicine_id[]" value="' + responseData.medicine_id + '" /><input type="hidden" name="medicine_purchase_id[]" value="' + responseData.medicine_purchase_id + '" /></td>';
                        html += '<td>' + responseData.medicine_pack_data + '</td>';
                        html += '<td>' + responseData.medicine_company + '</td>';
                        html += '<td>' + responseData.medicine_batch_no + '</td>';
                        html += '<td>' + responseData.medicine_expiry_date + '</td>';
                        html += '<td><input type="number" name="medicine_quantity[]" class="form-control medicine_quantity" placeholder="Quantity" value="1" min="1" oninput="updatePrice(this, ' + no + ')" /></td>';
                        html += '<td><input type="number" name="medicine_price[]" class="form-control item_unit_price" value="' + responseData.medicine_sale_price_per_unit + '" oninput="updatePrice(this, ' + no + ')" /></td>';
                        html += '<td><span class="item_total_price" id="item_total_price_' + no + '">' + responseData.medicine_sale_price_per_unit + '</span></td>';
                        html += '<td><button type="button" name="remove_item" class="btn btn-danger btn-sm" onclick="deleteRow(this)"><i class="fas fa-minus"></i></button></td>';
                        html += '</tr>';

                        document.getElementById('order_item_area').insertAdjacentHTML('beforeend', html);
                        calculate_total();
                    });
                }
            }

            function updatePrice(input, rowId) {
                const row = input.closest('tr');
                const quantity = parseFloat(row.querySelector('.medicine_quantity').value) || 0;
                const unitPrice = parseFloat(row.querySelector('.item_unit_price').value) || 0;
                const totalPrice = quantity * unitPrice;
                row.querySelector('.item_total_price').textContent = totalPrice.toFixed(0);
                calculate_total();
            }

            function deleteRow(btn) {
                var row = btn.parentNode.parentNode;
                row.parentNode.removeChild(row);
                calculate_total();
            }

            function random_number(min, max) {
                min = Math.ceil(min);
                max = Math.floor(max);
                return Math.floor(Math.random() * (max - min + 1)) + min;
            }

            function calculate_total() {
                var total = 0;
                document.querySelectorAll('.item_total_price').forEach(function(element) {
                    total += parseFloat(element.textContent) || 0;
                });
                _('hidden_order_total_amount').value = total;
                _('order_total_amount').innerHTML = total.toFixed(0);
            }
        </script>
    <?php } ?>
</div>







<?php
//print_order.php

if(isset($_GET["action"], $_GET["code"]) && $_GET["action"] == 'pdf' && $_GET['code'] != '')
{
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

    foreach($store_result as $store_row)
    {
        $store_name = $store_row['store_name'];
        $store_address = $store_row['store_address'];
        $store_contact_no = $store_row['store_contact_no'];
        $store_email = $store_row['store_email_address'];
        $store_logo = $store_row['store_logo'] ?? '';
    }

    // CSS styles for header and footer
    $styles = '
    <style>
        @page {
            margin: 100px 25px 100px 25px;
        }
        header {
            position: fixed;
            top: -60px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: center;
            border-bottom: 1px solid #000;
        }
        footer {
            position: fixed;
            bottom: -60px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: center;
            font-size: 12px;
            border-top: 1px solid #000;
        }
        main {
            margin-top: 20px;
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
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
        }
    </style>
    ';

    // Header HTML
    $header = '
    <header>
        <img src="'.$store_logo.'" class="logo" alt="Store Logo">
        <h2>'.$store_name.'</h2>
        <div>'.$store_address.'</div>
        <div>Tel: '.$store_contact_no.' | Email: '.$store_email.'</div>
    </header>
    ';

    // Footer HTML
    $footer = '
    <footer>
        <div>Thank you for your business!</div>
        <div>'.date('Y').' © '.$store_name.' - All rights reserved</div>
        <div>Page {PAGENO}</div>
    </footer>
    ';

    // Main content
    $html = $styles . $header . $footer . '
    <main>
        <div class="invoice-title">FACTURE</div>
    ';

    $object->query = "
    SELECT * FROM order_msbs 
    WHERE order_id = '$order_id'
    ";

    $total_amount = 0;
    $created_by = '';
    $order_date = '';
    $patient_name = '';
    $order_result = $object->get_result();

    foreach($order_result as $order_row)
    {
        $patient_name = $order_row["patient_name"];
        $html .= '
        <table width="100%" border="1" cellpadding="5" cellspacing="0">
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
        </table><br>
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
                <th>Conditionnement</th>
                <th>Fabricant</th>
                <th>N° Lot</th>
                <th>Date Exp.</th>
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
            <td>'.$m_data["medicine_pack_qty"].'</td>
            <td>'.$m_data["company_short_name"].'</td>
            <td>'.$m_data["medicine_batch_no"].'</td>
            <td>'.$m_data["expiry_date"].'</td>
            <td align="right">'.$object->cur_sym . number_format($order_item_row["medicine_price"], 2).'</td>
            <td align="center">'.$order_item_row["medicine_quantity"].'</td>
            <td align="right">'.$object->cur_sym . number_format($order_item_row["medicine_price"] * $order_item_row["medicine_quantity"], 2).'</td>
        </tr>
        ';
    }

    $html .= '
        <tr class="total-row">
            <td colspan="8" align="right"><strong>Total</strong></td>
            <td align="right">'.$object->cur_sym . number_format($total_amount, 2).'</td>
        </tr>
        </tbody>
    </table>

    <div class="signature">
        <p>Créé par: '.$created_by.'</p>
        <p>Signature: _________________</p>
    </div>
    </main>
    ';

    require_once('class/pdf.php');

    $pdf = new Pdf();
    
    // Set to A3 landscape
    $pdf->set_paper('A3', 'landscape');
    
    // Set margins and other options
    $pdf->set_option('margin-top', '10mm');
    $pdf->set_option('margin-bottom', '10mm');
    $pdf->set_option('margin-left', '10mm');
    $pdf->set_option('margin-right', '10mm');
    
    $file_name = 'FACTURE-' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . '.pdf';

    $pdf->loadHtml($html);
    $pdf->render();
    $pdf->stream($file_name, array("Attachment" => false));
    exit(0);
}
else
{
    header('location:order.php');
}