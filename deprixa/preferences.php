<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
}

$required_files = [
    'database.php',
    'library.php',
    'funciones.php',
    'header.php',
    'icon_settings.php',
    'footer.php'
];

foreach ($required_files as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        error_log("Required file missing: $file");
        http_response_code(500);
        die("<p class='text-danger'>Error: Required file $file not found.</p>");
    }
    require_once($file);
}

if (!isUser()) {
    error_log("User not authenticated in preferences.php");
    header("Location: ../login.php");
    exit;
}

$qname = $_SESSION['user_name'] ?? '';
$qrole = $_SESSION['user_type'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <title>PT. ANEKA LINTAS INDONESIA</title>
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet" />
    <link href="assets/plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/animate.css/animate.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/simple-line-icons/css/simple-line-icons.css" type="text/css" />
    <link rel="stylesheet" href="css/footer-basic-centered.css">
    <link href="assets/plugins/timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
    <link href="assets/plugins/mjolnic-bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
    <link href="assets/plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <link href="assets/plugins/clockpicker/bootstrap-clockpicker.min.css" rel="stylesheet">
    <link href="assets/plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
</head>
<body>
<?php include("header.php"); ?>

<div class="wrapper">
    <div class="container">
        <?php include("icon_settings.php"); ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="card-box table-responsive">
                    <table border="0" align="center" width="100%">
                        <tbody>
                            <tr>
                                <h3 class="classic-title"><span><strong><i class="fa fa-truck icon text-default-lter"></i>  Pilihan</strong></h3>
                                <?php
                                try {
                                    $stmt = dbQuery("SELECT * FROM company WHERE id = ?", [1]);
                                    $rr = dbFetchAssoc($stmt);
                                    if (!$rr) {
                                        error_log("No company data found for id=1");
                                        $rr = [
                                            'cname' => '',
                                            'nit' => '',
                                            'cphone' => '',
                                            'bemail' => '',
                                            'cemail' => '',
                                            'caddress' => '',
                                            'country' => '',
                                            'city' => '',
                                            'website' => '',
                                            'currency' => '',
                                            'date' => '',
                                            'footer_website' => ''
                                        ];
                                    }
                                } catch (PDOException $e) {
                                    error_log("Error fetching company data: " . $e->getMessage());
                                    die("<p class='text-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>");
                                }
                                ?>
                                <form action="process.php?action=company" method="post" class="form-horizontal">
                                    <div class="row">
                                        <fieldset class="col-md-6">
                                            <legend>Informasi Kantor :</legend>
                                            <div class="row">
                                                <div class="col-sm-6 form-group">
                                                    <label for="cname" class="control-label">Nama Kantor</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($rr['cname']); ?>" name="cname">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label for="nit" class="control-label"><i class="fa fa-hashtag icon text-default-lter"></i> NIT</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($rr['nit']); ?>" name="nit">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label"><i class="fa fa-phone-square icon text-default-lter"></i> NOMOR TELEPON KANTOR</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($rr['cphone']); ?>" name="cphone">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6 form-group">
                                                    <label for="bemail" class="control-label"><i class="fa fa-at icon text-default-lter"></i> Email Pemberitahuan Pesanan</label>
                                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($rr['bemail']); ?>" name="bemail">
                                                </div>
                                                <div class="col-sm-6 form-group">
                                                    <label for="cemail" class="control-label"><i class="fa fa-paypal icon text-default-lter"></i> PayPal Email<span class="required-field">*</span></label>
                                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($rr['cemail']); ?>" name="cemail">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6 form-group">
                                                    <label for="caddress" class="control-label"><i class="fa fa-map-marker icon text-default-lter"></i> Alamat Kantor</label>
                                                    <textarea class="form-control" name="caddress" required><?php echo htmlspecialchars($rr['caddress']); ?></textarea>
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label for="country" class="control-label"><i class="fa fa-map icon text-default-lter"></i> Negara</label>
                                                    <input class="form-control" name="country" required value="<?php echo htmlspecialchars($rr['country']); ?>">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label for="city" class="control-label"><i class="fa fa-map-o icon text-default-lter"></i> Kota</label>
                                                    <input class="form-control" name="city" required value="<?php echo htmlspecialchars($rr['city']); ?>">
                                                </div>
                                                <div class="col-sm-6 form-group">
                                                    <label for="website" class="control-label"><i class="fa fa-edge icon text-default-lter"></i> Website</label>
                                                    <input value="<?php echo htmlspecialchars($rr['website']); ?>" placeholder="http://www.example.com" name="website" class="form-control">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label for="currency" class="control-label"><i class="fa fa-usd icon text-default-lter"></i> Jenis Mata Uang</label>
                                                    <input class="form-control" name="currency" required value="<?php echo htmlspecialchars($rr['currency']); ?>">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label for="date" class="control-label"><i class="fa fa-calendar icon text-default-lter"></i> Tanggal</label>
                                                    <input type="text" class="form-control" name="date" value="<?php echo htmlspecialchars($rr['date']); ?>" placeholder="mm/dd/yyyy" id="datepicker-autoclose">
                                                </div>
                                                <div class="col-sm-6 form-group">
                                                    <label for="footer_website" class="control-label"><i class="fa fa-edge icon text-default-lter"></i> Footer Website</label>
                                                    <input value="<?php echo htmlspecialchars($rr['footer_website']); ?>" placeholder="2016 © name" name="footer_website" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6 text-left">
                                                <br><br>
                                                <button name="Submit" type="submit" class="btn btn-large btn-success">PERBARUI</button>
                                            </div>
                                        </fieldset>
                                    </form>
                                    <fieldset class="col-md-6">
                                        <legend>Ideal Logo PNG dengan dimensi 240 x 45</legend>
                                        <?php
                                        if (isset($_POST['guardar'])) {
                                            try {
                                                $upload_dir = __DIR__ . '/Uploads/';
                                                if (!is_dir($upload_dir)) {
                                                    if (!mkdir($upload_dir, 0755, true)) {
                                                        error_log("Failed to create upload directory: $upload_dir");
                                                        die("<p class='text-danger'>Failed to create upload directory.</p>");
                                                    }
                                                }
                                                if (!is_writable($upload_dir)) {
                                                    error_log("Upload directory not writable: $upload_dir");
                                                    die("<p class='text-danger'>Upload directory is not writable.</p>");
                                                }

                                                $nombre = $_FILES['imagen']['name'] ?? '';
                                                $type = $_FILES['imagen']['type'] ?? '';
                                                $tmp_name = $_FILES['imagen']['tmp_name'] ?? '';
                                                $size = $_FILES['imagen']['size'] ?? 0;

                                                if ($size > 2097152) { // 2MB limit
                                                    error_log("Image upload failed: File size exceeds 2MB");
                                                    header("Location: preferences.php?error=size");
                                                    exit;
                                                }
                                                if (!in_array($type, ['image/png'])) {
                                                    error_log("Image upload failed: Invalid file type ($type)");
                                                    header("Location: preferences.php?error=type");
                                                    exit;
                                                }

                                                $image_data = file_get_contents($tmp_name);
                                                $stmt = dbQuery("UPDATE subir_imagen SET nombre_imagen = ?, imagen = ?, tipo = ? WHERE id = ?", [$nombre, $image_data, $type, 1]);
                                                header("Location: preferences.php?ok");
                                                exit;
                                            } catch (PDOException $e) {
                                                error_log("Image upload error: " . $e->getMessage());
                                                header("Location: preferences.php?error=db");
                                                exit;
                                            } catch (Exception $e) {
                                                error_log("Image upload error: " . $e->getMessage());
                                                header("Location: preferences.php?error=general");
                                                exit;
                                            }
                                        }

                                        if (isset($_GET['ok'])) {
                                            echo '<p>Saved successfully</p>';
                                        }
                                        if (isset($_GET['error'])) {
                                            $errors = [
                                                'size' => 'File size exceeds 2MB limit.',
                                                'type' => 'Only PNG files are allowed.',
                                                'db' => 'Database error occurred during upload.',
                                                'general' => 'An error occurred during upload.'
                                            ];
                                            echo "<p class='text-danger'>" . htmlspecialchars($errors[$_GET['error']] ?? 'Unknown error.') . "</p>";
                                        }
                                        ?>
                                        <form action="preferences.php" enctype="multipart/form-data" method="post">
                                            <label for="imagen" class="control-label"><i class="fa fa-upload icon text-default-lter"></i> UPDATE LOGO</label>
                                            <input type="file" name="imagen" id="imagen" class="form-control" accept="image/png" />
                                            <br><br>
                                            <button type="submit" name="guardar" class="btn btn-large btn-success">UPDATE IMAGE</button>
                                            <br><br>
                                        </form>
                                        <div class="row">
                                            <div class="col-sm-6 form-group">
                                                <label for="currentPassword">Penampakan Logo</label><br>
                                                <img src="image_logo.php?id=1"/>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include("footer.php"); ?>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/tether.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.nicescroll.js"></script>
<script src="assets/plugins/switchery/switchery.min.js"></script>
<script src="assets/plugins/moment/moment.js"></script>
<script src="assets/plugins/timepicker/bootstrap-timepicker.min.js"></script>
<script src="assets/plugins/mjolnic-bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<script src="assets/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
<script src="assets/plugins/clockpicker/bootstrap-clockpicker.js"></script>
<script src="assets/plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
<script src="assets/pages/jquery.form-pickers.init.js"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js"></script>
</body>
</html>