<?php

// location_rack.php

include('class/db.php');

$object = new db();

if (!$object->is_login()) {
    header('location:login.php');
}

$where_condition = '';

if (!$object->is_master_user()) {
    $where_condition = " WHERE order_pro_msbs.order_created_by = '" . $_SESSION["user_id"] . "' ";
}

$object->query = "
    SELECT * FROM order_pro_msbs 
    INNER JOIN user_msbs 
    ON user_msbs.user_id = order_pro_msbs.order_created_by 
    $where_condition
    ORDER BY order_id DESC
";

$result = $object->get_result();

$message = '';
$error = '';

if (isset($_POST["add_order_pro"])) {
    $formdata = array();

    // Validation des champs
    $formdata['patient_name'] = !empty($_POST["patient_name"]) ? trim($_POST["patient_name"]) : "";
    $formdata['birth_date'] = !empty($_POST["birth_date"]) ? trim($_POST["birth_date"]) : "";
    $formdata['hospitalisation_date'] = !empty($_POST["hospitalisation_date"]) ? trim($_POST["hospitalisation_date"]) : "";
    $formdata['phone'] = !empty($_POST["phone"]) ? trim($_POST["phone"]) : "";
    $formdata['doctor_name'] = !empty($_POST["doctor_name"]) ? trim($_POST["doctor_name"]) : "";

    // Validation des expressions régulières
    if (!preg_match("/^[a-zA-Z-0-9' ]*$/", $formdata['patient_name'])) {
        $error .= '<li>Only letters, Numbers and white space allowed in Patient Name</li>';
    }
    if (!preg_match("/^[a-zA-Z-0-9' ]*$/", $formdata['doctor_name'])) {
        $error .= '<li>Only letters, Numbers and white space allowed in Doctor Name</li>';
    }

    if ($error == '') {
        try {
            $object->connect->beginTransaction();
            
            $data = array(
                ':patient_name'         => $formdata['patient_name'],
                ':birth_date'           => $formdata['birth_date'],
                ':hospitalisation_date' => $formdata['hospitalisation_date'],
                ':phone'               => $formdata['phone'],
                ':doctor_name'         => $formdata['doctor_name'],
                ':order_total_amount'  => $_POST['final_amount'],
                ':reduction'           => $_POST['discount_percentage'],
                ':amount_not_reduction' => $_POST['order_total_amount'],
                ':order_created_by'    => $_SESSION['user_id'],
                ':order_status'        => 'Enable',
                ':order_added_on'      => $object->now,
                ':order_updated_on'    => $object->now
            );
    
            $object->query = "
                INSERT INTO order_pro_msbs 
                (patient_name, birth_date, hospitalisation_date, phone, doctor_name, order_total_amount, reduction, amount_not_reduction, order_created_by, order_status, order_added_on, order_updated_on) 
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
                        ':order_id'             => $order_id,
                        ':medicine_id'          => $medicine_id[$i],
                        ':medicine_purchase_id' => $medicine_purchase_id[$i],
                        ':medicine_quantity'    => $medicine_quantity[$i],
                        ':medicine_price'       => $medicine_price[$i]
                    );
    
                    $object->query = "
                        INSERT INTO order_item_pro_msbs 
                        (order_id, medicine_id, medicine_purchase_id, medicine_quantity, medicine_price) 
                        VALUES (:order_id, :medicine_id, :medicine_purchase_id, :medicine_quantity, :medicine_price)
                    ";
    
                    $object->execute($sub_data);
                    
                }
            }
    
            $object->connect->commit();
            header('location:order_pro.php?msg=add');
        } catch (PDOException $e) {
            $object->connect->rollBack();
            $error .= '<li>Transaction failed: ' . $e->getMessage() . '</li>';
            
            // Log de l'erreur
            error_log("Erreur lors de l'exécution de la transaction : " . $e->getMessage());
        }
    }
}

// Supprimer une commande
if (isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete') {
    $order_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

    $object->query = "
    DELETE FROM order_item_pro_msbs 
    WHERE order_id = '" . $order_id . "'
    ";

    $object->execute();

    $object->query = "
    DELETE FROM order_pro_msbs 
    WHERE order_id = '" . $order_id . "'
    ";

    $object->execute();

    header('location:order_pro.php?msg=delete');
}


include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gestion des proformats</h1>

    <?php
    if (isset($_GET["action"], $_GET["code"]) && $_GET["action"] == 'add') {
    ?>

        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="order_pro.php">Gestion des proformats</a></li>
            <li class="breadcrumb-item active">Ajouter une proformat</li>
        </ol>

        <?php
        if (isset($error) && $error != '') {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">' . $error . '</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        ?>
        <span id="msg_area"></span>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-plus"></i> Ajouter une proformat
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
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input class="form-control" id="doctor_name" type="text" placeholder="Enter Doctor Name" name="doctor_name" />
                                <label for="doctor_name">Nom du Médecin</label>
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
                                    <th width="30%">Médicament</th>
                                    <th width="10%">Quantité</th>
                                    <th width="30%">Prix unitaire</th>
                                    <th width="30%">Prix total</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="order_item_area"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" align="right"><b>Total</b></td>
                                    <td colspan="2" id="order_total_amount">0</td>
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
                        <input type="hidden" name="order_total_amount" id="hidden_order_total_amount" value="0" />
                        <input type="submit" name="add_order_pro" class="btn btn-success" value="Ajouter" />
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
                placeHolder: "Filtrer les médicaments",
                minOptionWidth: 500,
                maxOptionWidth: 600
            });

            _('add_medicine').onclick = function() {
                var med_id = _('add_medicine_id').value;
                if (med_id == '') {
                    _('msg_area').innerHTML = '<div class="alert alert-danger">Veuillez sélectionner un médicament</div>';
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
                calculateDiscount();
            }

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
        </script>
    <?php
    } else {
    ?>

        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Gestion des proformats</li>
        </ol>

        <?php
        if (isset($_GET["msg"])) {
            if ($_GET["msg"] == 'add') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Nouvelle commande ajoutée<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            if ($_GET["msg"] == 'delete') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">La commande a été supprimée<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
        ?>
        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col col-md-6">
                        <i class="fas fa-table me-1"></i> Gestion des proformats
                    </div>
                    <div class="col col-md-6" align="right">
                        <a href="order_pro.php?action=add&code=<?php echo $object->convert_data('add'); ?>" class="btn btn-success btn-sm">Ajouter une proformat</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="datatablesSimple">
                    <thead>
                        <tr>
                            <th>No. Commande</th>
                            <th>Nom du Patient</th>
                            <th>Nom du Médecin</th>
                            <th>Montant</th>
                            <th>Créé par</th>
                            <th>Statut</th>
                            <th>Date d'ajout</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No. Commande</th>
                            <th>Nom du Patient</th>
                            <th>Nom du Médecin</th>
                            <th>Montant</th>
                            <th>Créé par</th>
                            <th>Statut</th>
                            <th>Date d'ajout</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        foreach ($result as $row) {
                            $order_status = '';
                            if ($row["order_status"] == 'Enable') {
                                $order_status = '<div class="badge bg-success">Actif</div>';
                            } else {
                                $order_status = '<div class="badge bg-danger">Inactif</div>';
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
                                    <td>
                                        <a href="print_order_pro_a4.php?action=pdf&code=' . $object->convert_data($row["order_id"]) . '" class="btn-warning btn btn-sm" target="_blank">Imprimer A4</a>
                                        <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`' . $object->convert_data($row["order_id"]) . '`);">Supprimer</button>
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
            function delete_data(code) {
                if (confirm("Êtes-vous sûr de vouloir supprimer cette commande?")) {
                    window.location.href = "order_pro.php?action=delete&code=" + code + "&status=Delete";
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