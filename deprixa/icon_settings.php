<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

if (function_exists('ali_is_owner') && ali_is_owner()) {
?>
<div class="row">
    <div class="col-sm-12">
        <div class="btn-group pull-right m-t-15">
            <button type="button" class="btn btn-custom dropdown-toggle waves-effect waves-light"
                    data-toggle="dropdown" aria-expanded="false">Settings <span class="m-l-5"><i
                    class="fa fa-cog"></i></span></button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="service_mode.php"><i class="fa fa-gift"></i> Tipe Produk</a>
                <a class="dropdown-item" href="type-of-shipments.php"><i class="fa fa-truck"></i> Tipe Pengiriman</a>
                <a class="dropdown-item" href="add-office.php"><i class="fa fa-map-marker"></i> Lokasi</a>
                <a class="dropdown-item" href="gallery-manager.php"><i class="fa fa-picture-o"></i> Gallery Website</a>
                <a class="dropdown-item" href="api-keys.php"><i class="fa fa-plug"></i> API Customer</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="preferences.php"><i class="fa fa-briefcase"></i> Kantor</a>
            </div>
        </div>
        <h4 class="page-title">Dashboard</h4>
    </div>
</div>
<?php
} else {
    echo "<p class='text-warning'>Settings menu only available for Administrators.</p>";
}
?>