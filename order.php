<?php

//location_rack.php

include('class/db.php');

$object = new db();

if (!$object->is_login()) {
    header('location:login.php');
}

$where_condition = '';

if (!$object->is_master_user()) {
    $where_condition = "
    WHERE order_msbs.order_created_by = '" . $_SESSION["user_id"] . "' 
    ";
}

$object->query = "
    SELECT * FROM order_msbs 
    INNER JOIN user_msbs 
    ON user_msbs.user_id = order_msbs.order_created_by 
    " . $where_condition . "
    ORDER BY order_id DESC
";

$result = $object->get_result();

$message = '';

$error = '';

if (isset($_POST["add_order"])) {
    $formdata = array();

    if (empty($_POST["patient_name"])) {
        $formdata['patient_name'] = "";
    } else {
        if (!preg_match("/^[a-zA-Z-0-9' ]*$/", $_POST["patient_name"])) {
            $error .= '<li>Only letters, Numbers and white space allowed</li>';
        } else {
            $formdata['patient_name'] = trim($_POST["patient_name"]);
        }
    }
     // Validation de la date de naissance
     if (empty($_POST["birth_date"])) {
        $formdata['birth_date'] = "";
    } else {
        $formdata['birth_date'] = trim($_POST["birth_date"]);
    }

    // Validation de la date d'hospitalisation
    if (empty($_POST["hospitalisation_date"])) {
        $formdata['hospitalisation_date'] = "";
    } else {
        $formdata['hospitalisation_date'] = trim($_POST["hospitalisation_date"]);
    }

    if (empty($_POST["phone"])) {
        $formdata['phone'] = "";
    } else {
        $formdata['phone'] = trim($_POST["phone"]);
    }

    if (empty($_POST["doctor_name"])) {
        $formdata['doctor_name'] = "";
    } else {
        if (!preg_match("/^[a-zA-Z-0-9' ]*$/", $_POST["doctor_name"])) {
            $error .= '<li>Only letters, Numbers and white space allowed</li>';
        } else {
            $formdata['doctor_name'] = trim($_POST["doctor_name"]);
        }
    }

    if ($error == '') {
        $data = array(
            ':patient_name'                =>  $formdata['patient_name'],
            ':birth_date'                  =>  $formdata['birth_date'],
            ':hospitalisation_date'        =>  $formdata['hospitalisation_date'],
            ':phone'                       =>  $formdata['phone'],
            ':doctor_name'                 =>  $formdata['doctor_name'],
            ':order_total_amount'          =>  $_POST['final_amount'],
            ':reduction'                   =>  $_POST['discount_percentage'],
            ':amount_not_reduction'        =>  $_POST['order_total_amount'],
            ':order_created_by'            =>  $_SESSION['user_id'],
            ':order_status'                =>  'Enable',
            ':order_added_on'              =>  $object->now,
            ':order_updated_on'            =>  $object->now
        );

        $object->query = "
        INSERT INTO order_msbs 
        (patient_name, birth_date, hospitalisation_date, phone, doctor_name, order_total_amount, reduction, amount_not_reduction, order_created_by, order_status, order_added_on, order_updated_on ) 
        VALUES (:patient_name, :birth_date, :hospitalisation_date, :phone, :doctor_name, :order_total_amount, :reduction, :amount_not_reduction, :order_created_by, :order_status, :order_added_on, :order_updated_on)
        ";

        $object->execute($data);

        $order_id = $object->connect->lastInsertId();

        $medicine_id = $_POST["medicine_id"];

        $medicine_purchase_id = $_POST["medicine_purchase_id"];

        $medicine_quantity = $_POST["medicine_quantity"];

        $medicine_price = $_POST["medicine_price"];

        if (count($medicine_id) > 0) {
            for ($i = 0; $i < count($medicine_id); $i++) {
                $sub_data = array(
                    ':order_id'             =>  $order_id,
                    ':medicine_id'          =>  $medicine_id[$i],
                    ':medicine_purchase_id' =>  $medicine_purchase_id[$i],
                    ':medicine_quantity'    =>  $medicine_quantity[$i],
                    ':medicine_price'       =>  $medicine_price[$i]
                );

                $object->query = "
                INSERT INTO order_item_msbs 
                (order_id, medicine_id, medicine_purchase_id, medicine_quantity, medicine_price) 
                VALUES(:order_id, :medicine_id, :medicine_purchase_id, :medicine_quantity, :medicine_price)
                ";

                $object->execute($sub_data);

                $object->query = "
                UPDATE medicine_purchase_msbs 
                SET available_quantity = available_quantity - " . $medicine_quantity[$i] . " 
                WHERE medicine_purchase_id = '" . $medicine_purchase_id[$i] . "'
                ";

                $object->get_result();

                $object->query = "
                UPDATE medicine_msbs 
                SET medicine_available_quantity = medicine_available_quantity - " . $medicine_quantity[$i] . " 
                WHERE medicine_id = '" . $medicine_id[$i] . "'
                ";

                $object->get_result();
            }
        }

        header('location:order.php?msg=add');
    }
}

if (isset($_GET["action"], $_GET["item_code"], $_GET["order_code"]) && $_GET["action"] == 'remove_item') {
    $order_item_id = $object->convert_data(trim($_GET["item_code"]), 'decrypt');

    $object->query = "
    SELECT * FROM order_item_msbs 
    WHERE order_item_id = '" . $order_item_id . "'
    ";

    $item_result = $object->get_result();

    foreach ($item_result as $item_row) {
        $medicine_id = $item_row["medicine_id"];
        $medicine_purchase_id = $item_row["medicine_purchase_id"];
        $medicine_quantity = $item_row["medicine_quantity"];
        $medicine_price = $item_row["medicine_price"];
        $object->query = "
        DELETE FROM order_item_msbs 
        WHERE order_item_id = '" . $order_item_id . "'
        ";

        $object->get_result();

        $object->query = "
        UPDATE order_msbs 
        SET order_total_amount = order_total_amount - " . $medicine_quantity * $medicine_price . " 
        WHERE order_id = '" . $item_row["order_id"] . "'
        ";

        $object->get_result();

        $object->query = "
        UPDATE medicine_purchase_msbs 
        SET available_quantity = available_quantity + " . $medicine_quantity . " 
        WHERE medicine_purchase_id = '" . $medicine_purchase_id . "'
        ";

        $object->get_result();

        $object->query = "
        UPDATE medicine_msbs 
        SET medicine_available_quantity = medicine_available_quantity + " . $medicine_quantity . " 
        WHERE medicine_id = '" . $medicine_id . "'
        ";

        $object->get_result();
    }

    header('location:order.php?action=edit&code=' . $_GET["order_code"] . '');
}

if (isset($_POST["edit_order"])) {
    $formdata = array();

    if (empty($_POST["patient_name"])) {
        $formdata['patient_name'] = "unknown";
    } else {
        if (!preg_match("/^[a-zA-Z-0-9' ]*$/", $_POST["patient_name"])) {
            $error .= '<li>Only letters, Numbers and white space allowed</li>';
        } else {
            $formdata['patient_name'] = trim($_POST["patient_name"]);
        }
    }

    if (empty($_POST["doctor_name"])) {
        $formdata['doctor_name'] = "unknown";
    } else {
        if (!preg_match("/^[a-zA-Z-0-9' ]*$/", $_POST["doctor_name"])) {
            $error .= '<li>Only letters, Numbers and white space allowed</li>';
        } else {
            $formdata['doctor_name'] = trim($_POST["doctor_name"]);
        }
    }

    if ($error == '') {
        $order_id = $object->convert_data(trim($_POST["order_id"]), 'decrypt');

        $data = array(
            ':patient_name'         =>  $formdata['patient_name'],
            ':doctor_name'          =>  $formdata['doctor_name'],
            ':order_total_amount'   =>  $_POST["order_total_amount"],
            ':order_updated_on'     =>  $object->now,
            ':order_id'             =>  $order_id
        );

        $object->query = "
        UPDATE order_msbs 
        SET patient_name = :patient_name, 
        doctor_name = :doctor_name, 
        order_total_amount = :order_total_amount, 
        order_updated_on = :order_updated_on 
        WHERE order_id = :order_id
        ";

        $object->execute($data);

        $medicine_id = $_POST["medicine_id"];
        $medicine_purchase_id = $_POST["medicine_purchase_id"];
        $medicine_quantity = $_POST["medicine_quantity"];
        $medicine_price = $_POST["medicine_price"];

        for ($i = 0; $i < count($medicine_id); $i++) {
            $object->query = "
            SELECT * FROM order_item_msbs 
            WHERE order_id = '" . $order_id . "' 
            AND medicine_id = '" . $medicine_id[$i] . "' 
            AND medicine_purchase_id = '" . $medicine_purchase_id[$i] . "'
            ";

            $order_item_result = $object->get_result();

            foreach ($order_item_result as $order_item_row) {
                $medicineid = $order_item_row["medicine_id"];
                $medicinepurchaseid = $order_item_row["medicine_purchase_id"];
                $medicinequantity = $order_item_row["medicine_quantity"];
                $medicineprice = $order_item_row["medicine_price"];

                if ($medicinequantity != $medicine_quantity[$i]) {
                    $data = array(
                        ':medicine_quantity'    =>  $medicine_quantity[$i],
                        ':order_item_id'        =>  $order_item_row['order_item_id']
                    );
                    $object->query = "
                    UPDATE order_item_msbs 
                    SET medicine_quantity = :medicine_quantity 
                    WHERE order_item_id = :order_item_id
                    ";

                    $object->execute($data);

                    $final_update_qty = 0;
                    if ($medicinequantity > $medicine_quantity[$i]) {
                        $final_update_qty = $medicinequantity - $medicine_quantity[$i];

                        $object->query = "
                        UPDATE medicine_purchase_msbs 
                        SET available_quantity = available_quantity + " . $final_update_qty . " 
                        WHERE medicine_purchase_id = '" . $medicine_purchase_id[$i] . "'
                        ";

                        $object->execute();

                        $object->query = "
                        UPDATE medicine_msbs 
                        SET medicine_available_quantity = medicine_available_quantity + " . $final_update_qty . " 
                        WHERE medicine_id = '" . $medicine_id[$i] . "'
                        ";

                        $object->execute();
                    } else {
                        $final_update_qty = $medicine_quantity[$i] - $medicinequantity;

                        $object->query = "
                        UPDATE medicine_purchase_msbs 
                        SET available_quantity = available_quantity - " . $final_update_qty . " 
                        WHERE medicine_purchase_id = '" . $medicine_purchase_id[$i] . "'
                        ";

                        $object->execute();

                        $object->query = "
                        UPDATE medicine_msbs 
                        SET medicine_available_quantity = medicine_available_quantity - " . $final_update_qty . " 
                        WHERE medicine_id = '" . $medicine_id[$i] . "'
                        ";

                        $object->execute();
                    }
                }
            }
        }

        header('location:order.php?msg=edit');
    }
}




if (isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete') {
    $order_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

    $object->query = "
    SELECT * FROM order_item_msbs 
    WHERE order_id = '" . $order_id . "'
    ";

    $item_result = $object->get_result();

    foreach ($item_result as $item_row) {
        $object->query = "
        UPDATE medicine_purchase_msbs 
        SET available_quantity = available_quantity + " . $item_row['medicine_quantity'] . " 
        WHERE medicine_purchase_id = '" . $item_row['medicine_purchase_id'] . "'
        ";

        $object->get_result();

        $object->query = "
        UPDATE medicine_msbs 
        SET medicine_available_quantity = medicine_available_quantity + " . $item_row['medicine_quantity'] . " 
        WHERE medicine_id = '" . $item_row['medicine_id'] . "'
        ";

        $object->get_result();
    }

    $object->query = "
    DELETE FROM order_item_msbs 
    WHERE order_id = '" . $order_id . "'
    ";

    $object->execute();

    $object->query = "
    DELETE FROM order_msbs 
    WHERE order_id = '" . $order_id . "'
    ";

    $object->execute();

    header('location:order.php?msg=delete');
}


include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gestion des ventes</h1>

    <?php
    if (isset($_GET["action"], $_GET["code"])) {
        if ($_GET["action"] == 'add') {
    ?>

            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="order.php">Gestion des ventes</a></li>
                <li class="breadcrumb-item active">Ajouter une vente</li>
            </ol>

            <?php
            if (isset($error) && $error != '') {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">' . $error . '</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            ?>
            <span id="msg_area"></span>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Ajouter une vente
                </div>
                <div class="card-body">
                    <form method="post">
                    <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="patient_name" type="text" placeholder="Enter Patient Name" name="patient_name" />
                                    <label for="patient_name">Nom du Patient</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="birth_date" type="date" placeholder="Enter Birth Date" name="birth_date" />
                                    <label for="birth_date">Date de naissance</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="hospitalisation_date" type="date" placeholder="Enter Hospitalisation Date" name="hospitalisation_date" />
                                    <label for="hospitalisation_date">Date Hospitalisation</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="phone" type="number" placeholder="Enter tel patient" name="phone" />
                                    <label for="phone">Tel patient</label>
                                </div>
                            </div>
                        </div>

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
                                        <th width="10%">Quantity</th>
                                        <th width="30%">Unit Price</th>
                                        <th width="30%">Total Price</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="order_item_area"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7" align="right"><b>Total</b></td>
                                        <td colspan="2" id="order_total_amount"><?php if (isset($_POST["order_total_amount"])) echo $_POST["order_total_amount"]; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row mt-3">
    <div class="col-md-6 offset-md-6">
        <div class="input-group mb-3">
            <span class="input-group-text">Réduction (%)</span>
            <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" value="0" min="0" max="100" step="0.1" oninput="calculateDiscount()">
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text">Montant final</span>
            <span class="form-control" id="final_amount">0</span>
            <input type="hidden" name="final_amount" id="hidden_final_amount" value="0" />
        </div>
    </div>
</div>
                        <div class="mt-4 mb-0">
                            <input type="hidden" name="order_total_amount" id="hidden_order_total_amount" value="<?php if (isset($_POST["order_total_amount"])) echo $_POST["order_total_amount"]; ?>" />
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
                        console.log("data", responseData);
                        var no = random_number(1, 99999);
                        var html = '<tr id="' + no + '">';
                        html += '<td>' + responseData.medicine_name + '<input type="hidden" name="medicine_id[]" value="' + responseData.medicine_id + '" /><input type="hidden" name="medicine_purchase_id[]" value="' + responseData.medicine_id + '" /></td>';
                        html += '<td><input type="number" name="medicine_quantity[]" class="form-control medicine_quantity" placeholder="Quantity" value="1" min="1" oninput="updatePrice(this, ' + no + ')" /></td>';
                        html += '<td><input type="number" name="medicine_price[]" class="form-control item_unit_price" value="' + responseData.medicine_price + '" oninput="updatePrice(this, ' + no + ')" /></td>';
                        html += '<td><span class="item_total_price" id="item_total_price_' + no + '">' + responseData.medicine_price + '</span></td>';
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

            // Function to handle discount calculation
function calculateDiscount() {
    const totalAmount = parseFloat(_('hidden_order_total_amount').value) || 0;
    const discountPercentage = parseFloat(_('discount_percentage').value) || 0;
    
    if (discountPercentage < 0) {
        _('discount_percentage').value = 0;
        return calculateDiscount();
    }
    if (discountPercentage > 100) {
        _('discount_percentage').value = 100;
        return calculateDiscount();
    }
    
    const discountAmount = (totalAmount * discountPercentage) / 100;
    const finalAmount = totalAmount - discountAmount;
    
    _('final_amount').textContent = finalAmount.toFixed(0);
    _('hidden_final_amount').value = finalAmount.toFixed(0);
}

// Update original calculate_total function to include discount recalculation
function calculate_total() {
    var total = 0;
    document.querySelectorAll('.item_total_price').forEach(function(element) {
        total += parseFloat(element.textContent) || 0;
    });
    _('hidden_order_total_amount').value = total;
    _('order_total_amount').innerHTML = total.toFixed(0);
    
    // Recalculate discount when total changes
    calculateDiscount();
}
        </script>
            <?php
        } else if ($_GET["action"] == 'edit') {
            $order_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

            if ($order_id > 0) {
                $object->query = "
                                    SELECT * FROM order_msbs 
                                    WHERE order_id = '$order_id'
                                    ";

                $order_result = $object->get_result();

                foreach ($order_result as $order_row) {
            ?>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="order.php">Gestion des ventes</a></li>
                        <li class="breadcrumb-item active">Modifier une vente</li>
                    </ol>
                    <?php
                    if (isset($error) && $error != '') {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">' . $error . '</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    }
                    ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-user-plus"></i> Modifier une vente
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="patient_name" type="text" placeholder="Enter Customer Name" name="patient_name" value="<?php echo $order_row["patient_name"]; ?>" />
                                            <label for="patient_name">Customer Name</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="doctor_name" type="text" placeholder="Enter Doctor Name" name="doctor_name" value="<?php echo $order_row["doctor_name"]; ?>" />
                                            <label for="doctor_name">Doctor Name</label>
                                        </div>
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
                                                <th width="10%">Exipry Date</th>
                                                <th width="10%">Quantity</th>
                                                <th width="10%">Unit Price</th>
                                                <th width="10%">Total Price</th>
                                                <th width="5%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="order_item_area">
                                            <?php

                                            $object->query = "
                                                    SELECT * FROM order_item_msbs 
                                                    WHERE order_id = '" . $order_row["order_id"] . "'
                                                    ";

                                            $order_item_result = $object->get_result();

                                            foreach ($order_item_result as $order_item_row) {
                                                $object->query = "
                                                            SELECT * FROM medicine_purchase_msbs 
                                                            INNER JOIN medicine_msbs 
                                                            ON medicine_msbs.medicine_id =  medicine_purchase_msbs.medicine_id 
                                                            WHERE medicine_purchase_msbs.medicine_purchase_id = '" . $order_item_row['medicine_purchase_id'] . "'
                                                        ";

                                                $medicine_pur_result = $object->get_result();

                                                foreach ($medicine_pur_result as $medicine_pur_row) {
                                                    echo '
                                                            <tr>
                                                                <td>' . $medicine_pur_row['medicine_name'] . '<input type="hidden" name="medicine_id[]" value="' . $order_item_row['medicine_id'] . '" /><input type="hidden" name="medicine_purchase_id[]" value="' . $order_item_row['medicine_purchase_id'] . '" /></td>
                                                                <td>' . $medicine_pur_row["medicine_pack_qty"] . ' ' . $object->Get_category_name($medicine_pur_row["medicine_category"]) . '</td>
                                                                <td>' . $object->Get_Medicine_company_code($medicine_pur_row["medicine_manufactured_by"]) . '</td>
                                                                <td>' . $medicine_pur_row['medicine_batch_no'] . '</td>
                                                                <td>' . $medicine_pur_row["medicine_expired_month"] . '/' . $medicine_pur_row["medicine_expired_year"] . '</td>

                                                                <td><input type="text" name="medicine_quantity[]" class="form-control medicine_quantity" placeholder="Quantity" value="' . $order_item_row["medicine_quantity"] . '" min="1" onblur="check_qty(this); calculate_total();" /></td>

                                                                <td><input type="text" name="medicine_price[]" class="form-control item_unit_price" placeholder="Unit price" value="' . $order_item_row['medicine_price'] . '" min="1" onblur="calculate_total()"/></td>

                                                                <td><span class="item_total_price">' . number_format($order_item_row['medicine_price'] * $order_item_row["medicine_quantity"], 0) . '</span></td>
                                                                <td><button type="button" name="remove_item" class="btn btn-danger btn-sm" onclick="delete_data(`' . $object->convert_data($order_item_row["order_item_id"]) . '`, `' . $_GET["code"] . '`);"><i class="fas fa-minus"></i></button></td>
                                                            </tr>
                                                            ';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="7" align="right"><b>Total</b></td>
                                                <td colspan="2" id="order_total_amount"><?php echo $order_row["order_total_amount"]; ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="mt-4 mb-0">
                                    <input type="hidden" name="order_total_amount" id="hidden_order_total_amount" value="<?php echo $order_row["order_total_amount"]; ?>" />
                                    <input type="hidden" name="order_id" value="<?php echo trim($_GET["code"]); ?>" />
                                    <input type="submit" name="edit_order" class="btn btn-primary" value="Edit" />
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                        function _(element) {
                            return document.getElementById(element);
                        }

                        function calculate_total() {
                            var qty = document.getElementsByClassName('medicine_quantity');
                            var unit_price = document.getElementsByClassName('item_unit_price');
                            var total_price = document.getElementsByClassName('item_total_price');

                            var total = 0;

                            if (qty.length > 0) {
                                for (var i = 0; i < qty.length; i++) {
                                    console.log('Qty - ' + qty[i].value);
                                    console.log('Unit Price - ' + unit_price[i].value);
                                    var temp_total_price = parseFloat(qty[i].value) * parseFloat(unit_price[i].value);
                                    total_price[i].innerHTML = temp_total_price.toFixed(0);
                                    total = parseFloat(total) + parseFloat(temp_total_price);
                                }
                            }
                            _('hidden_order_total_amount').value = total;
                            _('order_total_amount').innerHTML = total.toFixed(0);
                        }

                        function check_qty(element) {
                            var min = element.min;
                            if (parseInt(element.value) < min) {
                                element.value = min;
                            }
                            if (element.value == '') {
                                element.value = min;
                            }
                        }

                        function delete_data(item_code, order_code) {
                            if (confirm("Are you sure you want this medicine from Order?")) {
                                window.location.href = "order.php?action=remove_item&item_code=" + item_code + "&order_code=" + order_code + "";
                            }
                        }
                    </script>

        <?php
                }
            } else {
                echo '<div class="alert alert-info">Something Went Wrong</div>';
            }
        } else {
            echo '<div class="alert alert-info">Something Went Wrong</div>';
        }
        ?>

    <?php
    } else {
    ?>

        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Gestion des ventes</li>
        </ol>

        <?php

        if (isset($_GET["msg"])) {
            if ($_GET["msg"] == 'add') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">New Order Added<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            if ($_GET["msg"] == 'edit') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Order Data Edited <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            if ($_GET["msg"] == 'delete') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Order has been deleted <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }

        ?>
        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col col-md-6">
                        <i class="fas fa-table me-1"></i> Gestion des ventes
                    </div>
                    <div class="col col-md-6" align="right">
                        <a href="order.php?action=add&code=<?php echo $object->convert_data('add'); ?>" class="btn btn-success btn-sm">Ajouter une vente</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="datatablesSimple">
                    <thead>
                        <tr>
                            <th>Order No.</th>
                            <th>Patient Name</th>
                            <th>Doctor Name</th>
                            <th>Order Amount</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Added On</th>
                            <th>Updated On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Order No.</th>
                            <th>Patient Name</th>
                            <th>Doctor Name</th>
                            <th>Order Amount</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Added On</th>
                            <th>Updated On</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        foreach ($result as $row) {
                            $order_status = '';
                            if ($row["order_status"] == 'Enable') {
                                $order_status = '<div class="badge bg-success">Enable</div>';
                            } else {
                                $order_status = '<div class="badge bg-danger">Disable</div>';
                            }
                            echo '
                                            <tr>
                                                <td>' . $row["order_id"] . '</td>
                                                <td>' . $row["patient_name"] . '</td>
                                                <td>' . $row["doctor_name"] . '</td>
                                                <td>' . $object->cur_sym . number_format($row["order_total_amount"],0) . '</td>
                                                <td>' . $row["user_name"] . '</td>
                                                <td>' . $order_status . '</td>
                                                <td>' . $row["order_added_on"] . '</td>
                                                <td>' . $row["order_updated_on"] . '</td>
                                                <td>
                                                    <a href="print_order.php?action=pdf&code=' . $object->convert_data($row["order_id"]) . '" class="btn-warning btn btn-sm" target="_blank">Print A3</a>
                                                    <a href="print_order_a4.php?action=pdf&code=' . $object->convert_data($row["order_id"]) . '" class="btn-warning btn btn-sm" target="_blank">Print A4</a>
                                                    <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`' . $object->convert_data($row["order_id"]) . '`, `' . $row["order_status"] . '`); ">Delete</button>
                                                </td>
                                            </tr>
                                            ';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
            function delete_data(code, status) {
                var new_status = 'Enable';
                if (status == 'Enable') {
                    new_status = 'Disable';
                }
                if (confirm("Are you sure you want to " + new_status + " this Order?")) {
                    window.location.href = "order.php?action=delete&code=" + code + "&status=" + new_status + "";
                }
            }
        </script>
    <?php
    }
    ?>

</div>

<?php

include('footer.php');

?>