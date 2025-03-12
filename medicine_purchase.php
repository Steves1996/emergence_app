<?php
// location_rack.php

include('class/db.php');

$object = new db();

if (!$object->is_login()) {
    header('location:login.php');
}

// Filtrage par date
$where = "WHERE medicine_purchase_msbs.medicine_purchase_enter_by = '" . $_SESSION["user_id"] . "' ";

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    $where .= " AND medicine_purchase_msbs.medicine_purchase_datetime BETWEEN '$start_date' AND '$end_date' ";
}

$object->query = "
    SELECT * FROM medicine_purchase_msbs 
    INNER JOIN medicine_msbs 
    ON medicine_msbs.medicine_id = medicine_purchase_msbs.medicine_id 
    INNER JOIN supplier_msbs 
    ON supplier_msbs.supplier_id = medicine_purchase_msbs.supplier_id 
    " . $where . "
    ORDER BY medicine_purchase_msbs.medicine_purchase_id DESC
";

$result = $object->get_result();

$message = '';
$error = '';

// Traitement des formulaires (ajout et modification)
if (isset($_POST["add_medicine_purchase"])) {
    // Code pour ajouter un achat (identique à votre code actuel)
    $formdata = array();

    if (empty($_POST["medicine_id"])) {
        $error .= '<li>Medicine Name is required</li>';
    } else {
        $formdata['medicine_id'] = trim($_POST["medicine_id"]);
    }

    /*if (empty($_POST["supplier_id"])) {
        $formdata['supplier_id'] = trim($_POST["supplier_id"]);
    } else {
        $formdata['supplier_id'] = trim($_POST["supplier_id"]);
    }*/

    /*if (empty($_POST["medicine_batch_no"])) {
        $formdata['medicine_batch_no'] = trim($_POST["medicine_batch_no"]);
    } else {
        $formdata['medicine_batch_no'] = trim($_POST["medicine_batch_no"]);
    }*/

    if (empty($_POST["medicine_purchase_qty"])) {
        $error .= '<li>Purchase Quantity is required</li>';
    } else {
        if (!preg_match("/^[0-9']*$/", $_POST["medicine_purchase_qty"])) {
            $error .= '<li>Only Numbers allowed</li>';
        } else {
            $formdata['medicine_purchase_qty'] = trim($_POST["medicine_purchase_qty"]);
        }
    }

    /*if (empty($_POST["medicine_purchase_price_per_unit"])) {
        $formdata['medicine_purchase_price_per_unit'] = trim($_POST["medicine_purchase_price_per_unit"]);
    } else {
        $formdata['medicine_purchase_price_per_unit'] = trim($_POST["medicine_purchase_price_per_unit"]);
    }

    /*if(empty($_POST["medicine_purchase_total_cost"]))
    {
        $error .= '<li>Purchase Total Cost is required</li>';
    }
    else
    {
        $formdata['medicine_purchase_total_cost'] = trim($_POST["medicine_purchase_total_cost"]);
    }

    if (empty($_POST["medicine_expired_month"])) {
        $error .= '<li>Expired Month is required</li>';
    } else {
        $formdata['medicine_expired_month'] = trim($_POST["medicine_expired_month"]);
    }

    if (empty($_POST["medicine_expired_year"])) {
        $error .= '<li>Expired Year is required</li>';
    } else {
        $formdata['medicine_expired_year'] = trim($_POST["medicine_expired_year"]);
    }

    if (empty($_POST["medicine_sale_price_per_unit"])) {
        $formdata['medicine_sale_price_per_unit'] = trim($_POST["medicine_sale_price_per_unit"]);
    } else {
        $formdata['medicine_sale_price_per_unit'] = trim($_POST["medicine_sale_price_per_unit"]);
    }*/

    if ($error == '') {
        $total_cost = floatval($formdata['medicine_purchase_qty']) * floatval($formdata['medicine_purchase_price_per_unit']);
        $data = array(
            ':medicine_id'                      =>  $formdata['medicine_id'],
            ':supplier_id'                      =>  '1',
            ':medicine_batch_no'                =>  '0',
            ':medicine_purchase_qty'            =>  $formdata['medicine_purchase_qty'],
            ':available_quantity'               =>  $formdata['medicine_purchase_qty'],
            ':medicine_purchase_price_per_unit' =>  '0',
            ':medicine_purchase_total_cost'     =>  $total_cost,
            ':medicine_manufacture_month'       =>  '1',
            ':medicine_manufacture_year'        =>  '2023',
            ':medicine_expired_month'           =>  '0',
            ':medicine_expired_year'            =>  '0',
            ':medicine_sale_price_per_unit'     =>  '0',
            ':medicine_purchase_enter_by'       =>  $_SESSION["user_id"],
            ':medicine_purchase_datetime'       =>  $object->now,
            ':medicine_purchase_status'         =>  'Enable'
        );

        $object->query = "
        INSERT INTO medicine_purchase_msbs 
        (medicine_id, supplier_id, medicine_batch_no, medicine_purchase_qty, available_quantity, medicine_purchase_price_per_unit, medicine_purchase_total_cost, medicine_manufacture_month, medicine_manufacture_year, medicine_expired_month, medicine_expired_year, medicine_sale_price_per_unit, medicine_purchase_enter_by, medicine_purchase_datetime, medicine_purchase_status) 
        VALUES (:medicine_id, :supplier_id, :medicine_batch_no, :medicine_purchase_qty, :available_quantity, :medicine_purchase_price_per_unit, :medicine_purchase_total_cost, :medicine_manufacture_month, :medicine_manufacture_year, :medicine_expired_month, :medicine_expired_year, :medicine_sale_price_per_unit, :medicine_purchase_enter_by, :medicine_purchase_datetime, :medicine_purchase_status)
            ";

        $object->execute($data);

        $object->query = "
        UPDATE medicine_msbs 
        SET medicine_available_quantity = medicine_available_quantity + " . $formdata['medicine_purchase_qty'] . " 
        WHERE medicine_id = '" . $formdata['medicine_id'] . "'
        ";

        $object->execute();

        header('location:medicine_purchase.php?msg=add');
    }
}

if (isset($_POST["edit_medicine_purchase"])) {
    // Code pour modifier un achat (identique à votre code actuel)
    $formdata = array();

    if (empty($_POST["medicine_id"])) {
        $error .= '<li>Medicine Name is required</li>';
    } else {
        $formdata['medicine_id'] = trim($_POST["medicine_id"]);
    }

    /*if (empty($_POST["supplier_id"])) {
        $formdata['supplier_id'] = trim($_POST["supplier_id"]);
    } else {
        $formdata['supplier_id'] = trim($_POST["supplier_id"]);
    }

    if (empty($_POST["medicine_batch_no"])) {
        $formdata['medicine_batch_no'] = trim($_POST["medicine_batch_no"]);
    } else {
        $formdata['medicine_batch_no'] = trim($_POST["medicine_batch_no"]);
    }*/

    if (empty($_POST["medicine_purchase_qty"])) {
        $error .= '<li>Purchase Quantity is required</li>';
    } else {
        if (!preg_match("/^[0-9']*$/", $_POST["medicine_purchase_qty"])) {
            $error .= '<li>Only Numbers allowed</li>';
        } else {
            $formdata['medicine_purchase_qty'] = trim($_POST["medicine_purchase_qty"]);
        }
    }

   /* if (empty($_POST["medicine_purchase_price_per_unit"])) {
        $error .= '<li>Purchase Price per unit is required</li>';
    } else {
        $formdata['medicine_purchase_price_per_unit'] = trim($_POST["medicine_purchase_price_per_unit"]);
    }*/

    /*if(empty($_POST["medicine_purchase_total_cost"]))
    {
        $error .= '<li>Purchase Total Cost is required</li>';
    }
    else
    {
     


    if (empty($_POST["medicine_expired_month"])) {
        $error .= '<li>Expired Month is required</li>';
    } else {
        $formdata['medicine_expired_month'] = trim($_POST["medicine_expired_month"]);
    }

    if (empty($_POST["medicine_expired_year"])) {
        $error .= '<li>Expired Year is required</li>';
    } else {
        $formdata['medicine_expired_year'] = trim($_POST["medicine_expired_year"]);
    }

    if (empty($_POST["medicine_sale_price_per_unit"])) {
        $error .= '<li>Sale Price per unit is required</li>';
    } else {
        $formdata['medicine_sale_price_per_unit'] = trim($_POST["medicine_sale_price_per_unit"]);
    }   $formdata['medicine_purchase_total_cost'] = trim($_POST["medicine_purchase_total_cost"]);
    }*/

    if ($error == '') {
        $medicine_purchase_id = $object->convert_data(trim($_POST["medicine_purchase_id"]), 'decrypt');

        $object->query = "
        SELECT medicine_purchase_qty FROM medicine_purchase_msbs 
        WHERE medicine_purchase_id = '" . $medicine_purchase_id . "'
        ";

        $temp_result = $object->get_result();

        $medicine_purchase_qty = 0;

        foreach ($temp_result as $temp_row) {
            $medicine_purchase_qty = $temp_row["medicine_purchase_qty"];
        }

        $total_cost = floatval($formdata['medicine_purchase_qty']) * floatval($formdata['medicine_purchase_price_per_unit']);

        $data = array(
            ':medicine_id'                      =>  $formdata['medicine_id'],
            ':supplier_id'                      =>  '1',
            ':medicine_batch_no'                =>  '0',
            ':medicine_purchase_qty'            =>  $formdata['medicine_purchase_qty'],
            ':available_quantity'               =>  $formdata['medicine_purchase_qty'],
            ':medicine_purchase_price_per_unit' =>  '0',
            ':medicine_purchase_total_cost'     =>  $total_cost,
            ':medicine_manufacture_month'       =>  '0',
            ':medicine_manufacture_year'        =>  '0',
            ':medicine_expired_month'           =>  '0',
            ':medicine_expired_year'            =>  '0',
            ':medicine_sale_price_per_unit'     =>  '0',
            ':medicine_purchase_id'             =>  $medicine_purchase_id
        );

        $object->query = "
            UPDATE medicine_purchase_msbs 
            SET medicine_id = :medicine_id, 
            supplier_id = :supplier_id,
            medicine_batch_no = :medicine_batch_no, 
            medicine_purchase_qty = :medicine_purchase_qty, 
            available_quantity = :available_quantity, 
            medicine_purchase_price_per_unit = :medicine_purchase_price_per_unit, 
            medicine_purchase_total_cost = :medicine_purchase_total_cost, 
            medicine_manufacture_month = :medicine_manufacture_month, 
            medicine_manufacture_year = :medicine_manufacture_year, 
            medicine_expired_month = :medicine_expired_month, 
            medicine_expired_year = :medicine_expired_year, 
            medicine_sale_price_per_unit = :medicine_sale_price_per_unit  
            WHERE medicine_purchase_id = :medicine_purchase_id
            ";

        $object->execute($data);

        if ($medicine_purchase_qty != $formdata['medicine_purchase_qty']) {
            $final_update_qty = 0;
            if ($medicine_purchase_qty > $formdata['medicine_purchase_qty']) {
                $final_update_qty = $medicine_purchase_qty - $formdata['medicine_purchase_qty'];

                $object->query = "
                UPDATE medicine_msbs 
                SET medicine_available_quantity = medicine_available_quantity - " . $final_update_qty . " 
                WHERE medicine_id = '" . $formdata['medicine_id'] . "'
                ";
            } else {
                $final_update_qty = $formdata['medicine_purchase_qty'] - $medicine_purchase_qty;

                $object->query = "
                UPDATE medicine_msbs 
                SET medicine_available_quantity = medicine_available_quantity + " . $final_update_qty . " 
                WHERE medicine_id = '" . $formdata['medicine_id'] . "'
                ";
            }

            $object->execute();
        }

        header('location:medicine_purchase.php?msg=edit');
    }
}

if (isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete') {
    // Code pour supprimer un achat (identique à votre code actuel)
    $medicine_purchase_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

    $medicine_id = $object->convert_data(trim($_GET["id"]), 'decrypt');

    $object->query = "
    SELECT medicine_purchase_qty FROM medicine_purchase_msbs 
    WHERE medicine_purchase_id = '" . $medicine_purchase_id . "'
    ";

    $temp_result = $object->get_result();

    $medicine_purchase_qty = 0;

    foreach ($temp_result as $temp_row) {
        $medicine_purchase_qty = $temp_row["medicine_purchase_qty"];
    }

    $status = trim($_GET["status"]);
    $data = array(
        ':medicine_purchase_status'      =>  $status,
        ':medicine_purchase_id'          =>  $medicine_purchase_id
    );

    $object->query = "
    UPDATE medicine_purchase_msbs 
    SET medicine_purchase_status = :medicine_purchase_status 
    WHERE medicine_purchase_id = :medicine_purchase_id
    ";

    $object->execute($data);

    if ($status == 'Disable') {
        $object->query = "
        UPDATE medicine_msbs 
        SET medicine_available_quantity = medicine_available_quantity - " . $medicine_purchase_qty . " 
        WHERE medicine_id = '" . $medicine_id . "'
        ";
    } else {
        $object->query = "
        UPDATE medicine_msbs 
        SET medicine_available_quantity = medicine_available_quantity + " . $medicine_purchase_qty . " 
        WHERE medicine_id = '" . $medicine_id . "'
        ";
    }

    $object->execute();

    header('location:medicine_purchase.php?msg=' . strtolower($status) . '');
}

include('header.php');
?>

<div class="container-fluid px-4">
<h1 class="mt-4">Gestion des medicaments acheté</h1>

<?php
if (isset($_GET["action"], $_GET["code"])) {
    if ($_GET["action"] == 'add') {
?>

        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="medicine_purchase.php">Gestion des medicaments acheté</a></li>
            <li class="breadcrumb-item active">Ajouter un achat</li>
        </ol>

        <?php
        if (isset($error) && $error != '') {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">' . $error . '</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        ?>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-plus"></i> Ajouter un achat
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select name="medicine_id" class="form-control" id="medicine_id" onchange="getDetailOfProduct()">
                                    <?php echo $object->fill_medicine(); ?>
                                </select>
                                <?php
                                if (isset($_POST["medicine_id"])) {
                                    echo '
                                                    <script>
                                                    document.getElementById("medicine_id").value = "' . $_POST["medicine_id"] . '"
                                                    </script>
                                                    ';
                                                    
                                }

                                if (isset($_GET["medicine"])) {
                                    $medicine_id = $object->convert_data(trim($_GET["medicine"]), 'decrypt');
                                    
                                    echo '
                                                    <script>
                                                    document.getElementById("medicine_id").value = "' . $medicine_id . '"
                                                    </script>
                                                    ';
                                }

                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input class="form-control" id="medicine_purchase_qty" type="number" placeholder="Enter Quantity" name="medicine_purchase_qty" value="<?php if (isset($_POST["medicine_purchase_qty"])) echo $_POST["medicine_purchase_qty"]; ?>" />
                                <label for="medicine_purchase_qty">Quantite achete</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 mb-0">
                        <input type="submit" name="add_medicine_purchase" class="btn btn-success" value="Enregistrer" />
                    </div>
                </form>
            </div>
        </div>
        <script>
             let mySelect = new vanillaSelectBox('#medicine_id', {
            maxWidth: 600,
            maxHeight: 400,
            minWidth: 500,
            search: true,
            placeHolder: "Filter Medicine",
            minOptionWidth: 500,
            maxOptionWidth: 600
        });
        function getDetailOfProduct(){
            var data = document.getElementById('medicine_id').value;
            var form_data = new FormData();
                    form_data.append('med_id', data);
                    form_data.append('action', 'fetch_medicine_data');
                    fetch('product.php', {

                        method: "POST",

                        body: form_data

                    }).then(function(response) {

                        return response.json();

                    }).then(function(responseData) {
                        var purchasePrice = document.getElementById("medicine_purchase_price_per_unit");
                        var salePrice = document.getElementById("medicine_sale_price_per_unit");
                        var codeBarre = document.getElementById("medicine_batch_no");
                        var monthExpiration = document.getElementById("medicine_expired_month");
                        var yearExpiration = document.getElementById("medicine_expired_year");
                        purchasePrice.value = responseData.medicine_purchase_price_per_unit;
                        salePrice.value = responseData.medicine_sale_price_per_unit;
                        codeBarre.value = responseData.medicine_batch_no;
                        monthExpiration.value = responseData.medicine_expired_month;
                        yearExpiration.value = responseData.medicine_expired_year;

                    });

        }
    </script>
        <?php
    } else if ($_GET["action"] == 'edit') {
        $medicine_purchase_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

        if ($medicine_purchase_id > 0) {
            $object->query = "
                                SELECT * FROM medicine_purchase_msbs 
                                WHERE medicine_purchase_id = '$medicine_purchase_id'
                                ";

            $medicine_purchase_result = $object->get_result();

            foreach ($medicine_purchase_result as $medicine_purchase_row) {
        ?>
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="medicine_purchase.php">Gestion des medicaments acheté</a></li>
                    <li class="breadcrumb-item active">Modifier l'achat d'un medicament</li>
                </ol>
                <?php
                if (isset($error) && $error != '') {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">' . $error . '</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                }
                ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-plus"></i> Modifier l'achat d'un medicament
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <select name="medicine_id" class="form-control" id="medicine_id">
                                            <?php echo $object->fill_medicine(); ?>
                                        </select>
                                        <label for="medicine_id">Nom du medicament</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="medicine_purchase_qty" type="number" placeholder="Enter Quantity" name="medicine_purchase_qty" value="<?php echo $medicine_purchase_row["medicine_purchase_qty"]; ?>" />
                                            <label for="medicine_purchase_qty">Quantite achete</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 mb-0">
                                <input type="hidden" name="medicine_purchase_id" value="<?php echo trim($_GET["code"]); ?>" />
                                <input type="submit" name="edit_medicine_purchase" class="btn btn-primary" value="Edit" />
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                    document.getElementById('medicine_id').value = "<?php echo $medicine_purchase_row["medicine_id"]; ?>";
                    document.getElementById('supplier_id').value = "<?php echo $medicine_purchase_row["supplier_id"]; ?>";
                    document.getElementById('medicine_manufacture_month').value = "<?php echo $medicine_purchase_row["medicine_manufacture_month"]; ?>";
                    document.getElementById('medicine_manufacture_year').value = "<?php echo $medicine_purchase_row["medicine_manufacture_year"]; ?>";
                    document.getElementById('medicine_manufacture_month').value = "<?php echo $medicine_purchase_row["medicine_manufacture_month"]; ?>";
                    document.getElementById('medicine_expired_month').value = "<?php echo $medicine_purchase_row["medicine_expired_month"]; ?>";
                    document.getElementById('medicine_expired_year').value = "<?php echo $medicine_purchase_row["medicine_expired_year"]; ?>";
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
            <li class="breadcrumb-item active">Gestion des médicaments achetés</li>
        </ol>

        <?php
        if (isset($_GET["msg"])) {
            if ($_GET["msg"] == 'add') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Nouvel achat ajouté<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            if ($_GET["msg"] == 'edit') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Achat modifié<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            if ($_GET["msg"] == 'disable') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Statut de l\'achat désactivé<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            if ($_GET["msg"] == 'enable') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Statut de l\'achat activé<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
        ?>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col col-md-6">
                        <i class="fas fa-table me-1"></i> Gestion des médicaments achetés
                    </div>
                    <div class="col col-md-6" align="right">
                        <a href="medicine_purchase.php?action=add&code=<?php echo $object->convert_data('add'); ?>" class="btn btn-success btn-sm">Ajouter un achat</a>
                        </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Formulaire de filtre par date -->
                <form method="get" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                                <label for="start_date">Date de début</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                                <label for="end_date">Date de fin</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-floating mb-3">
                                <button type="submit" class="btn btn-primary">Filtrer</button>
                            </div>
                        </div>
                        <div class="col-md-2">
    <div class="form-floating mb-3">
        <button type="button" onclick="printReport()" class="btn btn-primary btn-sm">Imprimer</button>
    </div>
</div>
                    </div>
                </form>

                <!-- Tableau des achats -->
                <table id="datatablesSimple">
                    <thead>
                        <tr>
                            <th>Nom du médicament</th>
                            <th>Code</th>
                            <th>Fournisseur</th>
                            <th>Quantité achete</th>
                            <th>Quantité disponible</th>
                            <th>Date d'ajout</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Nom du médicament</th>
                            <th>Code</th>
                            <th>Fournisseur</th>
                            <th>Quantité achete</th>
                            <th>Quantité disponible</th>
                            <th>Date d'ajout</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        foreach ($result as $row) {
                            $medicine_purchase_status = '';
                            if ($row["medicine_purchase_status"] == 'Enable') {
                                $medicine_purchase_status = '<div class="badge bg-success">Enable</div>';
                            } else {
                                $medicine_purchase_status = '<div class="badge bg-danger">Disable</div>';
                            }
                            echo '
                                <tr>
                                    <td>' . $row["medicine_name"] . '</td>
                                    <td>' . $row["medicine_batch_no"] . '</td>
                                    <td>' . $row["supplier_name"] . '</td>
                                    <td>' . $row["available_quantity"] . '</td>
                                    <td>' . $row["medicine_available_quantity"] . '</td>
                                    <td>' . $row["medicine_purchase_datetime"] . '</td>
                                    <td>' . $medicine_purchase_status . '</td>
                                    <td>
                                        <a href="medicine_purchase.php?action=edit&code=' . $object->convert_data($row["medicine_purchase_id"]) . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                        <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`' . $object->convert_data($row["medicine_purchase_id"]) . '`, `' . $row["medicine_purchase_status"] . '`, `' . $object->convert_data($row["medicine_id"]) . '`);"><i class="fas fa-times"></i></button>
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
    function printReport() {
        // Récupérer les dates de début et de fin
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        // Vérifier si les dates sont saisies
        if (startDate && endDate) {
            // Rediriger vers le fichier d'impression avec les dates en paramètres
            window.open(`medicine_purchase_print.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
        } else {
            alert('Veuillez sélectionner une date de début et une date de fin.');
        }
    }
</script>
        <script>
            function delete_data(code, status, id) {
                var new_status = 'Enable';
                if (status == 'Enable') {
                    new_status = 'Disable';
                }
                if (confirm("Êtes-vous sûr de vouloir " + new_status + " cet achat ?")) {
                    window.location.href = "medicine_purchase.php?action=delete&code=" + code + "&status=" + new_status + "&id=" + id + "";
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