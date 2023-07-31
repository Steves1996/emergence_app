<?php

//location_rack.php

include('class/db.php');

$object = new db();

if (!$object->is_login()) {
    header('location:login.php');
}

$where = '';

if (!$object->is_master_user()) {
    $where = "WHERE medicine_purchase_msbs.medicine_purchase_enter_by = '" . $_SESSION["user_id"] . "' ";
}

include('header.php');
?>
<div class="m-auto w-100 container-fluid px-5 bg-dark p-2 text-white bg-opacity-50">
    <div class="gap-5 mb-5">
        <h1 class="mt-4">User Dashboard</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">User Dashboard</li>
        </ol>
    </div>
    <hr>
    <hr>
    <div class="d-flex gap-5 mt-5 justify-content-center">
        <a href="order.php" class="btn col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-bag-check" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10.854 8.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708 0z" />
                        <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5z" />
                    </svg>
                    <hr>
                    <hr>
                    <h4 class="text-center">Make a Sale</h4>
                </div>
            </div>
        </a>
        <a href="medicine_purchase.php" class="btn col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body card-hover">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-cart-check-fill" viewBox="0 0 16 16">
                        <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-1.646-7.646-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L8 8.293l2.646-2.647a.5.5 0 0 1 .708.708z" />
                    </svg>
                    <hr>
                    <hr>
                    <h4 class="text-center">Medicines Purchased</h4>
                </div>
            </div>
        </a>
        <a href="logout.php" class="btn col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-box-arrow-down" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M3.5 10a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 0 0 1h2A1.5 1.5 0 0 0 14 9.5v-8A1.5 1.5 0 0 0 12.5 0h-9A1.5 1.5 0 0 0 2 1.5v8A1.5 1.5 0 0 0 3.5 11h2a.5.5 0 0 0 0-1h-2z" />
                        <path fill-rule="evenodd" d="M7.646 15.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 14.293V5.5a.5.5 0 0 0-1 0v8.793l-2.146-2.147a.5.5 0 0 0-.708.708l3 3z" />
                    </svg>
                    <hr>
                    <hr>
                    <h4 class="text-center">Log Out</h4>
                </div>
            </div>
        </a>
    </div>
    <hr>
    <hr>
</div>
<?php

include('footer.php');

?>