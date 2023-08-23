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
    $_SESSION['from_date'] = $from_date; 
    $_SESSION['to_date'] = $to_date;

    $fromDate = $from_date;
    $toDate = $to_date;

    $where_date = "WHERE om.order_added_on BETWEEN '" . $from_date . "' AND '" . $to_date . "' ";
}

$object->query = "
            SELECT mm.medicine_name, oim.medicine_quantity, oim.medicine_price, om.order_added_on, um.user_name
            FROM `order_item_msbs` oim 
            JOIN medicine_msbs mm 
            on oim.medicine_id = mm.medicine_id
            JOIN order_msbs om
            ON oim.order_id = om.order_id
            JOIN user_msbs um
            ON om.order_created_by = um.user_id
            $where_date
";

$result = $object->get_result();
include('header.php');

?>


<div class="container-fluid px-4">
    <h1 class="mt-4">Rapport des ventes</h1>


    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="category.php">>Rapport des ventes</a></li>
    </ol>


    <div class="card mb-4">
        <div class="card-header">
            <div class="row">
                <div class="col col-md-6">
                    <i class="fas fa-table me-1"></i> Rapport des ventes
                </div>
                <div class="col col-md-6" align="right">
                    <a href="repport_print_pdf.php" class="btn-warning btn btn-sm" target="_blank"><i class="fa fa-file-pdf"></i>Print</a>
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
                        <th>Nom du medicament</th>
                        <th>date de vente</th>
                        <th>Vendeur</th>
                        <th>Quantite vendu</th>
                        <th>Prix unitaire</th>
                        <th>Prix total</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Nom du medicament</th>
                        <th>date de vente</th>
                        <th>Vendeur</th>
                        <th>Quantite vendu</th>
                        <th>Prix unitaire</th>
                        <th>Prix total</th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php
                    $TotalAmount = 0;
                    foreach ($result as $row) {
                        $TotalAmount += $row["medicine_price"];
                        $totalAmountProduct = $row["medicine_quantity"]*$row["medicine_price"];
                        echo '
                                            <tr>
                                                <td>' . $row["medicine_name"] . '</td>
                                                <td>' . $row["order_added_on"] . '</td>
                                                <td>' . $row["user_name"] . '</td>
                                                <td>' . $row["medicine_quantity"] . '</td>
                                                <td>' . $row["medicine_price"] . '</td>
                                                <td>' . $object->cur_sym.number_format($totalAmountProduct, 0) . '</td>
                                            </tr>
                                            ';
                    }
                    ?>
                </tbody>
            </table>

            <div class="row">
                <div class="col-md-4">
                    <h2 class="control-label accordion-header">Total vente</h2>
                </div>
                <div class="col-md-4">
                    <h3 class="control-label accordion-body"> <?php
                                                                echo $TotalAmount;
                                                                ?> XAF</h3>
                </div>
            </div>
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

</div>

<?php

include('footer.php');

?>