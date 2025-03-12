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

// Initialisation des variables
$fromDate = $toDate = $selected_medicine = "";
$where_clause = "";
$medicines = [];
$result = [];
$TotalAmount = 0;

// Récupérer tous les médicaments pour le dropdown
$object->query = "SELECT medicine_id, medicine_name FROM medicine_msbs ORDER BY medicine_name";
$medicines = $object->get_result();

// Gérer la soumission du formulaire
if (isset($_POST['submit'])) {
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $medicine_id = $_POST['medicine_id'] ?? '';

    // Valider les dates
    if ($from_date && $to_date && strtotime($from_date) > strtotime($to_date)) {
        $_SESSION['error'] = "La date de début doit être antérieure à la date de fin.";
        header('location: sales_report.php');
        exit();
    }

    // Stocker les valeurs dans la session
    $_SESSION['from_date'] = $from_date;
    $_SESSION['to_date'] = $to_date;
    $_SESSION['medicine_id'] = $medicine_id;

    $fromDate = $from_date;
    $toDate = $to_date;
    $selected_medicine = $medicine_id;

    // Construire la clause WHERE
    $conditions = [];
    
    if ($from_date && $to_date) {
        $conditions[] = "om.order_added_on BETWEEN '" . $from_date . "' AND '" . $to_date . "'";
    }
    
    if ($medicine_id) {
        $conditions[] = "mm.medicine_id = '" . $medicine_id . "'";
    }
    
    if (!empty($conditions)) {
        $where_clause = "WHERE " . implode(' AND ', $conditions);
    }
}

// Requête principale pour regrouper les ventes par date
$object->query = "
    SELECT 
        DATE(om.order_added_on) as order_date, 
        SUM(oim.medicine_quantity) as total_quantity, 
        SUM(oim.medicine_price * oim.medicine_quantity) as total_sales,
        SUM(om.reduction) as total_reduction,
        om.amount_not_reduction as total_amount_not_reduction,
        om.order_total_amount as total_order_amount
    FROM order_item_msbs oim 
    JOIN medicine_msbs mm ON oim.medicine_id = mm.medicine_id
    JOIN order_msbs om ON oim.order_id = om.order_id
    JOIN user_msbs um ON om.order_created_by = um.user_id
    $where_clause
    GROUP BY om.order_id
    ORDER BY order_date DESC
";

$result = $object->get_result();
include('header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Rapport des ventes</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Rapport des ventes</li>
    </ol>

    <!-- Afficher les messages d'erreur -->
    <?php if (isset($_SESSION['error'])) : ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <div class="row">
                <div class="col col-md-6">
                    <i class="fas fa-table me-1"></i> Rapport des ventes
                </div>
                <div class="col col-md-6" align="right">
                    <!-- Lien pour imprimer en PDF -->
                    <a href="repport_print_pdf.php?from_date=<?php echo htmlspecialchars($fromDate); ?>&to_date=<?php echo htmlspecialchars($toDate); ?>&medicine_id=<?php echo htmlspecialchars($selected_medicine); ?>" 
                       class="btn btn-warning btn-sm" 
                       target="_blank">
                        <i class="fa fa-file-pdf"></i> Imprimer en PDF
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Formulaire de filtrage -->
        <form class="card-body" method="POST">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="from_date" class="form-label">Date début</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="<?php echo htmlspecialchars($fromDate); ?>">
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label">Date fin</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="<?php echo htmlspecialchars($toDate); ?>">
                </div>
                <div class="col-md-3">
    <label for="medicine_search" class="form-label">Rechercher un médicament</label>
    <input type="text" id="medicine_search" class="form-control" placeholder="Tapez pour rechercher...">
    
    <label for="medicine_id" class="form-label mt-3">Médicament</label>
    <select name="medicine_id" id="medicine_id" class="form-control" size="5"> <!-- size="5" pour afficher plusieurs options -->
        <option value="">Tous les médicaments</option>
        <?php foreach ($medicines as $medicine) : ?>
            <option value="<?php echo htmlspecialchars($medicine["medicine_id"]); ?>" 
                <?php echo ($medicine["medicine_id"] == $selected_medicine) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($medicine["medicine_name"]); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('medicine_search'); // Champ de recherche
        const selectElement = document.getElementById('medicine_id'); // Liste déroulante

        searchInput.addEventListener('input', function () {
            const searchTerm = searchInput.value.toLowerCase(); // Récupérer la valeur de recherche en minuscules
            const options = selectElement.querySelectorAll('option'); // Toutes les options de la liste

            options.forEach(function (option) {
                const text = option.textContent.toLowerCase(); // Texte de l'option en minuscules
                if (text.includes(searchTerm)) {
                    option.style.display = 'block'; // Afficher l'option si elle correspond
                } else {
                    option.style.display = 'none'; // Masquer l'option si elle ne correspond pas
                }
            });
        });
    });
</script>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" name="submit" class="btn btn-primary d-block">
                        <i class="bi bi-funnel"></i> Filtrer
                    </button>
                </div>
            </div>
        </form>

        <!-- Tableau des résultats -->
        <div class="card-body">
            <div class="table-responsive">
                <table id="datatablesSimple" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Quantité vendue</th>
                            <th>Total des ventes</th>
                            <th>Réduction totale</th>
                            <th>Total sans réduction</th>
                            <th>Total payé</th>
                            <th>Détails</th> <!-- Nouvelle colonne -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($result)) : ?>
                            <tr>
                                <td colspan="6" class="text-center">Aucune donnée trouvée.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($result as $row) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row["order_date"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["total_quantity"]); ?></td>
                                    <td><?php echo $object->cur_sym . number_format($row["total_sales"], 0); ?></td>
                                    <td><?php echo $object->cur_sym . number_format($row["total_reduction"], 0); ?></td>
                                    <td><?php echo $object->cur_sym . number_format($row["total_amount_not_reduction"], 0); ?></td>
                                    <td><?php echo $object->cur_sym . number_format($row["total_order_amount"], 0); ?></td>
                                    <td>
                                <!-- Bouton pour voir les détails -->
                                <a href="sales_details.php?date=<?php echo urlencode($row["order_date"]); ?>" 
                                   class="btn btn-info btn-sm">
                                    <i class="fa fa-eye"></i> Détails
                                </a>
                            </td>
                                </tr>
                                <?php $TotalAmount += $row["total_order_amount"]; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total des ventes :</th>
                            <th><?php echo $object->cur_sym . number_format($TotalAmount, 0); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Rechercher un médicament...",
            allowClear: true
        });
    });
</script>

<?php include('footer.php'); ?>