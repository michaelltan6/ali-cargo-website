<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
require_once('database.php');
require_once('library.php');
require_once('funciones.php');

// Check authentication
if (!function_exists('isUser') || !isUser()) {
    error_log("User not authenticated in service_mode.php");
    header("Location: login.php");
    exit;
}

// Log session data for debugging
// (log data dimatikan) error_log("Session data in service_mode.php: " . print_r($_SESSION, true));
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage Shipping Modes - PT. ANEKA LINTAS INDONESIA">
    <meta name="keywords" content="shipping modes, logistics">
    <meta name="author" content="PT. ANEKA LINTAS INDONESIA">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <title>PT. ANEKA LINTAS INDONESIA - Shipping Modes</title>
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/simple-line-icons/css/simple-line-icons.css" type="text/css" />
    <link rel="stylesheet" href="css/font.css" type="text/css" />
    <link href="css/estilos.css" rel="stylesheet">
    <link href="js/css/dataTables.bootstrap.css" rel="stylesheet">
    <link href="js/plugins/sweetalert/css/sweetalert.css" rel="stylesheet">
    <link rel="stylesheet" href="css/footer-basic-centered.css">
</head>
<body>
<?php
try {
    include("header.php");
} catch (Exception $e) {
    error_log("Failed to include header.php: " . $e->getMessage());
    echo "<p class='text-danger'>Error including header: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<div class="wrapper">
    <div class="container">
        <?php
        try {
            include("icon_settings.php");
        } catch (Exception $e) {
            error_log("Failed to include icon_settings.php: " . $e->getMessage());
            echo "<p class='text-danger'>Error including icon settings: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>

        <div class="row">
            <div class="col-sm-12">
                <div class="card-box table-responsive">
                    <table border="0" align="center" width="100%">
                        <tr>
                            <td class="TrackTitle" valign="top">
                                <div align="">
                                    <h3 class="classic-title1"><span><strong></strong></span></h3>
                                </div>
                            </td>
                        </tr>
                        <div class="row">
                            <div class="col-xs-12" align="center">
                                <h2>Tambah Tipe Pengiriman Baru</h2>
                                <br>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xs-12">
                                <button type="button" class="btn btn-md btn-success" data-toggle="modal" data-target="#nuevo">
                                    <i class="fa fa-user-plus"></i> Tipe Pengiriman Baru
                                </button>
                                <button type="button" class="btn btn-md btn-info" id="recarga">
                                    <i class="fa fa-refresh"></i> Untuk Diperbarui
                                </button>
                            </div>
                            <div class="col-xs-12">
                                <div class="table">
                                    <br>
                                    <table id="tabla-usuarios" class="table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Nama Pengiriman</th>
                                                <th>Pelayanan/Service</th>
                                                <th>Waktu Pengiriman</th>
                                                <th>Observasi</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Modal New Shipping Mode -->
                        <div class="modal fade" id="nuevo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Tutup</span></button>
                                        <h4 class="modal-title" id="myModalLabel"><i class="fa fa-user-plus"></i> Tipe Pengiriman Baru</h4>
                                    </div>
                                    <div class="modal-body">
                                        <form class="form-horizontal" id="formularioNuevo">
                                            <div class="form-group" id="gnombre">
                                                <label for="name" class="col-sm-2 control-label">Nama Pengiriman</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control name" name="name" placeholder="Name Shipments">
                                                </div>
                                            </div>
                                            <div class="form-group" id="gapellido">
                                                <label for="services" class="col-sm-2 control-label">Pelayanan/Services</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control services" name="services" placeholder="Services">
                                                </div>
                                            </div>
                                            <div class="form-group" id="gtelefono">
                                                <label for="deliverytime" class="col-sm-2 control-label">Waktu Pengiriman</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control deliverytime" name="deliverytime" placeholder="Delivery Time">
                                                </div>
                                            </div>
                                            <div class="form-group" id="gemail">
                                                <label for="observations" class="col-sm-2 control-label">Observasi</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control observations" name="observations" placeholder="Observations">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-10">
                                                    <div class="checkbox checkbox-success">
                                                        <input id="checkbox3" type="checkbox" name="estado" value="1" checked>
                                                        <label for="checkbox3">Berhasil</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Tutup</button>
                                        <button type="button" id="guardarNuevo" class="btn btn-primary"><i class="fa fa-floppy-o"></i> Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Edit Shipping Mode -->
                        <div class="modal fade" id="editar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Tutup</span></button>
                                        <h4 class="modal-title" id="myModalLabel"><i class="fa fa-pencil-square-o"></i> Edit Tipe Pengiriman</h4>
                                    </div>
                                    <div class="modal-body">
                                        <form class="form-horizontal" id="formularioEditar">
                                            <div class="form-group" id="Enombre">
                                                <label for="name" class="col-sm-2 control-label">Nama Pengiriman</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control name" name="name" placeholder="Name Shipments">
                                                </div>
                                            </div>
                                            <div class="form-group" id="Eapellido">
                                                <label for="services" class="col-sm-2 control-label">Pelayanan/Services</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control services" name="services" placeholder="Services">
                                                </div>
                                            </div>
                                            <div class="form-group" id="Etelefono">
                                                <label for="deliverytime" class="col-sm-2 control-label">Waktu Pengiriman</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control deliverytime" name="deliverytime" placeholder="Delivery Time">
                                                </div>
                                            </div>
                                            <div class="form-group" id="Eemail">
                                                <label for="observations" class="col-sm-2 control-label">Observasi</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control observations" name="observations" placeholder="Observations">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-10">
                                                    <div class="checkbox checkbox-success">
                                                        <input id="checkbox3" type="checkbox" name="estado" value="1">
                                                        <label for="checkbox3">Berhasil</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="id" id="id_user">
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                                        <button type="button" id="actualizar" class="btn btn-primary">Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            try {
                include("footer.php");
            } catch (Exception $e) {
                error_log("Failed to include footer.php: " . $e->getMessage());
                echo "<p class='text-danger'>Error including footer: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
    </div>

    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="plugins/DataTables/js/jquery.dataTables.js"></script>
    <script src="plugins/DataTables/extensions/TableTools/js/dataTables.tableTools.min.js"></script>
    <script src="js/dataTables.bootstrap.js"></script>
    <script src="plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
    <script src="plugins/sweetalert/js/sweetalert.min.js"></script>
    <script src="js/modebookings.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>
</body>
</html>