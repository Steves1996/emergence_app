<?php

//location_rack.php

include('class/db.php');

$object = new db();

if (!$object->is_login()) {
    header('location:login.php');
}

if (!$object->is_master_user()) {
    header('location:index.php');
}

$fromDate = $toDate = "";
$where_date = "";
if (isset($_POST['submit'])) {
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];

    $fromDate = $from_date;
    $toDate = $to_date;

    $where_date = "WHERE medicine_add_datetime BETWEEN '" . $from_date . "' AND '" . $to_date . "' ";
}
$object->query = "
    SELECT * FROM medicine_msbs 
    INNER JOIN category_msbs 
    ON category_msbs.category_id = medicine_msbs.medicine_category 
    INNER JOIN  medicine_manufacuter_company_msbs 
    ON  medicine_manufacuter_company_msbs.medicine_manufacuter_company_id = medicine_msbs.medicine_manufactured_by 
    INNER JOIN location_rack_msbs 
    ON location_rack_msbs.location_rack_id = medicine_msbs.medicine_location_rack
    $where_date
    ORDER BY medicine_msbs.medicine_name ASC
";

$result = $object->get_result();

$message = '';

$error = '';

if (isset($_POST["add_medicine"])) {
    $formdata = array();

    if (empty($_POST["medicine_name"])) {
        $error .= '<li>Medicine Name is required</li>';
    } else {
        $formdata['medicine_name'] = trim($_POST["medicine_name"]);
    }

    if (empty($_POST["medicine_pack_qty"])) {
        $error .= '<li>Medicine Single Pack Quantity is required</li>';
    } else {
        if (!preg_match("/^[0-9']*$/", $_POST["medicine_pack_qty"])) {
            $error .= '<li>Only Numbers allowed</li>';
        } else {
            $formdata['medicine_pack_qty'] = trim($_POST["medicine_pack_qty"]);
        }
    }

    /*if(empty($_POST["medicine_pack_type"]))
    {
        $error .= '<li>Medicine Pack Type is required</li>';
    }
    else
    {
        $formdata['medicine_pack_type'] = trim($_POST["medicine_pack_type"]);
    }*/

    if (empty($_POST["medicine_manufactured_by"])) {
        $error .= '<li>Medicine Manufacturing Company is required</li>';
    } else {
        $formdata['medicine_manufactured_by'] = trim($_POST["medicine_manufactured_by"]);
    }

    if (empty($_POST["medicine_category"])) {
        $error .= '<li>Medicine Category is required</li>';
    } else {
        $formdata['medicine_category'] = trim($_POST["medicine_category"]);
    }

    if (empty($_POST["medicine_location_rack"])) {
        $error .= '<li>Medicine Location Rack is required</li>';
    } else {
        $formdata['medicine_location_rack'] = trim($_POST["medicine_location_rack"]);
    }

    if ($error == '') {
        $object->query = "
        SELECT * FROM medicine_msbs 
        WHERE medicine_name = '" . $formdata['medicine_name'] . "' 
        AND medicine_manufactured_by = '" . $formdata['medicine_name'] . "'
        ";

        $object->execute();

        if ($object->row_count() > 0) {
            $error = '<li>Medicine Already Exists</li>';
        } else {
            $data = array(
                ':medicine_name'                =>  $formdata['medicine_name'],
                ':medicine_pack_qty'            =>  $formdata['medicine_pack_qty'],
                ':medicine_manufactured_by'     =>  $formdata['medicine_manufactured_by'],
                ':medicine_category'            =>  $formdata['medicine_category'],
                ':medicine_available_quantity'  =>  0,
                ':medicine_location_rack'       =>  $formdata['medicine_location_rack'],
                ':medicine_status'              =>  'Enable',
                ':medicine_add_datetime'        =>  $object->now,
                ':medicine_update_datetime'     =>  $object->now
            );

            $object->query = "
            INSERT INTO medicine_msbs 
            (medicine_name, medicine_pack_qty, medicine_manufactured_by, medicine_category, medicine_available_quantity, medicine_location_rack, medicine_status, medicine_add_datetime, medicine_update_datetime) 
            VALUES (:medicine_name, :medicine_pack_qty, :medicine_manufactured_by, :medicine_category, :medicine_available_quantity, :medicine_location_rack, :medicine_status, :medicine_add_datetime, :medicine_update_datetime)
            ";

            $object->execute($data);

            header('location:medicine.php?msg=add');
        }
    }
}

if (isset($_POST["edit_medicine"])) {
    $formdata = array();

    if (empty($_POST["medicine_name"])) {
        $error .= '<li>Medicine Name is required</li>';
    } else {
        $formdata['medicine_name'] = trim($_POST["medicine_name"]);
    }

    if (empty($_POST["medicine_pack_qty"])) {
        $error .= '<li>Medicine Single Pack Quantity is required</li>';
    } else {
        if (!preg_match("/^[0-9']*$/", $_POST["medicine_pack_qty"])) {
            $error .= '<li>Only Numbers allowed</li>';
        } else {
            $formdata['medicine_pack_qty'] = trim($_POST["medicine_pack_qty"]);
        }
    }

    /*if(empty($_POST["medicine_pack_type"]))
    {
        $error .= '<li>Medicine Pack Type is required</li>';
    }
    else
    {
        $formdata['medicine_pack_type'] = trim($_POST["medicine_pack_type"]);
    }*/

    if (empty($_POST["medicine_manufactured_by"])) {
        $error .= '<li>Medicine Manufacturing Company is required</li>';
    } else {
        $formdata['medicine_manufactured_by'] = trim($_POST["medicine_manufactured_by"]);
    }

    if (empty($_POST["medicine_category"])) {
        $error .= '<li>Medicine Category is required</li>';
    } else {
        $formdata['medicine_category'] = trim($_POST["medicine_category"]);
    }

    if (empty($_POST["medicine_location_rack"])) {
        $error .= '<li>Medicine Location Rack is required</li>';
    } else {
        $formdata['medicine_location_rack'] = trim($_POST["medicine_location_rack"]);
    }

    if ($error == '') {
        $medicine_id = $object->convert_data(trim($_POST["medicine_id"]), 'decrypt');

        $object->query = "
        SELECT * FROM medicine_msbs 
        WHERE medicine_name = '" . $formdata['medicine_name'] . "' 
        AND medicine_manufactured_by = '" . $formdata['medicine_name'] . "'
        AND medicine_id != '" . $medicine_id . "'
        ";

        $object->execute();

        if ($object->row_count() > 0) {
            $error = '<li>Medicine Name Already Exists</li>';
        } else {
            $data = array(
                ':medicine_name'                =>  $formdata['medicine_name'],
                ':medicine_pack_qty'            =>  $formdata['medicine_pack_qty'],
                ':medicine_manufactured_by'     =>  $formdata['medicine_manufactured_by'],
                ':medicine_category'            =>  $formdata['medicine_category'],
                ':medicine_location_rack'       =>  $formdata['medicine_location_rack'],
                ':medicine_update_datetime'     =>  $object->now,
                ':medicine_id'                  =>  $medicine_id
            );

            print_r($data);

            $object->query = "
            UPDATE medicine_msbs 
            SET medicine_name = :medicine_name, 
            medicine_pack_qty = :medicine_pack_qty,
            medicine_manufactured_by = :medicine_manufactured_by, 
            medicine_category = :medicine_category, 
            medicine_location_rack = :medicine_location_rack, 
            medicine_update_datetime = :medicine_update_datetime 
            WHERE medicine_id = :medicine_id
            ";

            $object->execute($data);

            header('location:medicine.php?msg=edit');
        }
    }
}


if (isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete') {
    $medicine_id = $object->convert_data(trim($_GET["code"]), 'decrypt');
    $status = trim($_GET["status"]);
    $data = array(
        ':medicine_status'      =>  $status,
        ':medicine_id'          =>  $medicine_id
    );

    $object->query = "
    UPDATE medicine_msbs 
    SET medicine_status = :medicine_status 
    WHERE medicine_id = :medicine_id
    ";

    $object->execute($data);

    header('location:medicine.php?msg=' . strtolower($status) . '');
}


include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gestion des medicaments</h1>

    <?php
    if (isset($_GET["action"], $_GET["code"])) {
        if ($_GET["action"] == 'add') {
    ?>

            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="category.php">>Gestion des medicaments</a></li>
                <li class="breadcrumb-item active">Ajouter un medicament</li>
            </ol>

            <?php
            if (isset($error) && $error != '') {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">' . $error . '</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            ?>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Ajouter un medicament
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="medicine_name" type="text" placeholder="Enter Medicine Name" name="medicine_name" value="<?php if (isset($_POST["medicine_name"])) echo $_POST["medicine_name"]; ?>" />
                                    <label for="medicine_name">Nom</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select name="medicine_manufactured_by" class="form-control" id="medicine_manufactured_by">
                                        <?php echo $object->fill_company(); ?>
                                    </select>
                                    <?php
                                    if (isset($_POST["medicine_manufactured_by"])) {
                                        echo '
                                                        <script>
                                                        document.getElementById("medicine_manufactured_by").value = "' . $_POST["medicine_manufactured_by"] . '"
                                                        </script>
                                                        ';
                                    }
                                    ?>
                                    <label for="medicine_manufactured_by">Medicament fabrique par</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="medicine_pack_qty" type="number" placeholder="Enter Packet Quantity" name="medicine_pack_qty" value="<?php if (isset($_POST["medicine_pack_qty"])) echo $_POST["medicine_pack_qty"]; ?>" />
                                    <label for="medicine_pack_qty">Alerte Quantite</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select name="medicine_category" class="form-control" id="medicine_category">
                                        <?php echo $object->fill_category(); ?>
                                    </select>
                                    <?php
                                    if (isset($_POST["medicine_category"])) {
                                        echo '
                                                        <script>
                                                        document.getElementById("medicine_category").value = "' . $_POST["medicine_category"] . '"
                                                        </script>
                                                        ';
                                    }
                                    ?>
                                    <label for="medicine_category">Categorie du medicament</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select name="medicine_location_rack" class="form-control" id="medicine_location_rack">
                                        <?php echo $object->fill_location_rack(); ?>
                                    </select>
                                    <?php
                                    if (isset($_POST["medicine_location_rack"])) {
                                        echo '
                                                        <script>
                                                        document.getElementById("medicine_location_rack").value = "' . $_POST["medicine_location_rack"] . '"
                                                        </script>
                                                        ';
                                    }
                                    ?>
                                    <label for="medicine_location_rack">Rang sur comptoir</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 mb-0">
                            <input type="submit" name="add_medicine" class="btn btn-success" value="Add" />
                        </div>
                    </form>
                </div>
            </div>
            <?php
        } else if ($_GET["action"] == 'edit') {
            $medicine_id = $object->convert_data(trim($_GET["code"]), 'decrypt');

            if ($medicine_id > 0) {
                $object->query = "
                                    SELECT * FROM medicine_msbs 
                                    WHERE medicine_id = '$medicine_id'
                                    ";

                $medicine_result = $object->get_result();

                foreach ($medicine_result as $medicine_row) {
            ?>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="medicine.php">Gestion des medicaments</a></li>
                        <li class="breadcrumb-item active">Modifier un medicament</li>
                    </ol>
                    <?php
                    if (isset($error) && $error != '') {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">' . $error . '</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    }
                    ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-user-plus"></i> Modifier un medicament
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="medicine_name" type="text" placeholder="Enter Medicine Name" name="medicine_name" value="<?php echo $medicine_row["medicine_name"]; ?>" />
                                            <label for="medicine_name">Nom</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <select name="medicine_manufactured_by" class="form-control" id="medicine_manufactured_by">
                                                <?php echo $object->fill_company(); ?>
                                            </select>
                                            <label for="medicine_manufactured_by">Medicament fabrique par</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="medicine_pack_qty" type="number" placeholder="Enter Packet Quantity" name="medicine_pack_qty" value="<?php echo $medicine_row["medicine_pack_qty"]; ?>" />
                                            <label for="medicine_pack_qty">Alerte quantite</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <select name="medicine_category" class="form-control" id="medicine_category">
                                                <?php echo $object->fill_category(); ?>
                                            </select>
                                            <label for="medicine_category">Categorie</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <select name="medicine_location_rack" class="form-control" id="medicine_location_rack">
                                                <?php echo $object->fill_location_rack(); ?>
                                            </select>
                                            <label for="medicine_location_rack">Rang sur comptoir</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 mb-0">
                                    <input type="hidden" name="medicine_id" value="<?php echo trim($_GET["code"]); ?>" />
                                    <input type="submit" name="edit_medicine" class="btn btn-primary" value="Edit" />
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        document.getElementById('medicine_manufactured_by').value = "<?php echo $medicine_row["medicine_manufactured_by"]; ?>";
                        document.getElementById('medicine_category').value = "<?php echo $medicine_row["medicine_category"]; ?>";
                        document.getElementById('medicine_location_rack').value = "<?php echo $medicine_row["medicine_location_rack"]; ?>";
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
            <li class="breadcrumb-item active">Medicine Management</li>
        </ol>

        <?php

        if (isset($_GET["msg"])) {
            if ($_GET["msg"] == 'add') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">New Medicine Added<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            if ($_GET["msg"] == 'edit') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Medicine Data Edited <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            if ($_GET["msg"] == 'disable') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Medicine Status Change to Disable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            if ($_GET["msg"] == 'enable') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Medicine Status Change to Enable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }

        ?>
        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col col-md-6">
                        <i class="fas fa-table me-1"></i> Gestion des medicaments
                    </div>
                    <div class="col col-md-6" align="right">
                        <a href="medicine.php?action=add&code=<?php echo $object->convert_data('add'); ?>" class="btn btn-success btn-sm"><i class="fa fa-plus"></i>Add</a>
                        <a href="medicine_print_pdf.php" class="btn-warning btn btn-sm" target="_blank"><i class="fa fa-file-pdf"></i>Print</a>
                    </div>
                </div>
            </div>
            <form class="card-body" method="POST">
                <div class="row">

                    <div class="col-md-4">
                        <input type="date" name="from_date" id="from_date" class="form-control" placeholder="Form Date" value="<?php echo $fromDate ?>">
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="to_date" id="to_date" class="form-control" placeholder="Form Date" value="<?php echo $toDate ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="submit" class="btn btn-primary"><i class="bi bi-funnel"></i>Filter medicine</button>
                    </div>
                </div>
            </form>
            <div class="card-body">
                <table id="datatablesSimple">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Fabriquant</th>
                            <th>Alerte quantite</th>
                            <th>Quantite disponible</th>
                            <th>Rang sur comptoir</th>
                            <th>Status</th>
                            <th>Date d'ajout</th>
                            <th>Date de mis a jour</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Nom</th>
                            <th>Fabriquant</th>
                            <th>Alerte quantite</th>
                            <th>Quantite disponible</th>
                            <th>Rang sur comptoir</th>
                            <th>Status</th>
                            <th>Date d'ajout</th>
                            <th>Date de mis a jour</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        foreach ($result as $row) {
                            $medicine_status = '';
                            if ($row["medicine_status"] == 'Enable') {
                                $medicine_status = '<div class="badge bg-success">Enable</div>';
                            } else {
                                $medicine_status = '<div class="badge bg-danger">Disable</div>';
                            }
                            echo '
                                            <tr>
                                                <td>' . $row["medicine_name"] . '</td>
                                                <td>' . $row["company_name"] . '</td>
                                                <td>' . $row["medicine_pack_qty"] . '</td>
                                                <td>' . $row["medicine_available_quantity"] . '</td>
                                                <td>' . $row["location_rack_name"] . '</td>
                                                <td>' . $medicine_status . '</td>
                                                <td>' . $row["medicine_add_datetime"] . '</td>
                                                <td>' . $row["medicine_update_datetime"] . '</td>
                                                <td>
                                                    <a href="medicine_purchase.php?action=add&code=' . $object->convert_data("add") . '&medicine=' . $object->convert_data($row["medicine_id"]) . '" class="btn btn-secondary btn-sm"><i class="fas fa-plus"></i> Purchase</a>
                                                    <a href="medicine.php?action=edit&code=' . $object->convert_data($row["medicine_id"]) . '" class="btn btn-sm btn-primary">Edit</a>
                                                    <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`' . $object->convert_data($row["medicine_id"]) . '`, `' . $row["medicine_status"] . '`); ">Delete</button>
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
                if (confirm("Are you sure you want to " + new_status + " this Medicine?")) {
                    window.location.href = "medicine.php?action=delete&code=" + code + "&status=" + new_status + "";
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