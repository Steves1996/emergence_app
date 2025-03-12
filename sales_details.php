<?php
include('class/db.php');

$object = new db();

// Rediriger si l'utilisateur n'est pas connecté ou n'est pas un administrateur
if (!$object->is_login()) {
    header('location: login.php');
    exit();
}

if (!$object->is_master_user()) {
    header('location: index.php');
    exit();
}

// Récupérer la date depuis l'URL
$date = isset($_GET['date']) ? $_GET['date'] : '';
if (empty($date)) {
    $_SESSION['error'] = "Aucune date spécifiée.";
    header('location: sales_report.php');
    exit();
}

// Requête pour récupérer les détails des ventes pour la date spécifiée
$object->query = "
    SELECT 
        mm.medicine_name, 
        oim.medicine_quantity, 
        oim.medicine_price,
        om.reduction,
        om.amount_not_reduction,
        om.order_total_amount, 
        om.order_added_on,
        um.user_name
    FROM order_item_msbs oim 
    JOIN medicine_msbs mm ON oim.medicine_id = mm.medicine_id
    JOIN order_msbs om ON oim.order_id = om.order_id
    JOIN user_msbs um ON om.order_created_by = um.user_id
    WHERE DATE(om.order_added_on) = '" . $date . "'
    ORDER BY om.order_added_on DESC
";

$details = $object->get_result();
include('header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Détails des ventes du <?php echo htmlspecialchars($date); ?></h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="sales_report.php">Rapport des ventes</a></li>
        <li class="breadcrumb-item active">Détails</li>
    </ol>

    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Médicament</th>
                            <th>Quantité vendue</th>
                            <th>Prix unitaire</th>
                            <th>Prix total</th>
                            <th>Réduction</th>
                            <th>Montant total payé</th>
                            <th>Vendeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($details)) : ?>
                            <tr>
                                <td colspan="7" class="text-center">Aucune donnée trouvée pour cette date.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($details as $detail) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detail["medicine_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($detail["medicine_quantity"]); ?></td>
                                    <td><?php echo $object->cur_sym . number_format($detail["medicine_price"], 0); ?></td>
                                    <td><?php echo $object->cur_sym . number_format($detail["medicine_price"] * $detail["medicine_quantity"], 0); ?></td>
                                    <td><?php echo $object->cur_sym . number_format($detail["reduction"], 0); ?></td>
                                    <td><?php echo $object->cur_sym . number_format($detail["order_total_amount"], 0); ?></td>
                                    <td><?php echo htmlspecialchars($detail["user_name"]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>