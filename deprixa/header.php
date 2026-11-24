<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
}
require_once('database.php');
require_once('library.php');

// Check authentication
if (!function_exists('isUser') || !isUser()) {
    error_log("User not authenticated in header.php");
    header("Location: ../login.php");
    exit;
}
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
    <link rel="stylesheet" href="assets/plugins/morris/morris.css">
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
    <script src="assets/js/modernizr.min.js"></script>
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','../../../www.google-analytics.com/analytics.js','ga');
        ga('create', 'UA-79190402-1', 'auto');
        ga('send', 'pageview');
    </script>
    <link rel="stylesheet" href="assets/css/ali-admin.css?v=<?php echo @filemtime(__DIR__ . '/assets/css/ali-admin.css'); ?>">
<style>
        /* Menu bertambah (CUSTOMER), rapatkan jarak antar menu supaya tidak turun ke baris kedua */
        @media (min-width: 992px) {
            #topnav .navigation-menu > li > a { padding-left: 13px !important; padding-right: 13px !important; }
        }
    </style>
</head>
<body>
    <header id="topnav">
        <div class="topbar-main">
            <div class="container">
                <div class="topbar-left">
                    <a href="admin.php" class="logo" style="text-decoration: none;">
                        <div style="display: flex; align-items: center;">
                            <h1 style="font-size: 30px;">PT. ANEKA LINTAS INDONESIA</h1>
                        </div>
                    </a>
                </div>
                <div class="menu-extras">
                    <ul class="nav navbar-nav pull-right">
                        <li class="nav-item">
                            <a class="navbar-toggle">
                                <div class="lines">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </a>
                        </li>
                        <li class="nav-item dropdown notification-list">
                            <a class="nav-link dropdown-toggle arrow-none waves-effect waves-light nav-user" data-toggle="dropdown" href="#" role="button"
                               aria-haspopup="false" aria-expanded="false">
                                <img src="assets/images/users/avatar-1.jpg" alt="user" class="img-circle">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-arrow profile-dropdown" aria-labelledby="Preview">
                                <div class="dropdown-item noti-title">
                                    <h5 class="text-overflow"><small>Selamat Datang ! <?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Guest'); ?></small></h5>
                                </div>
                                <?php if (function_exists('ali_is_owner') && ali_is_owner()) { ?>
                                    <a href="preferences.php" class="dropdown-item notify-item">
                                        <i class="zmdi zmdi-settings"></i> <span>Pengaturan</span>
                                    </a>
                                <?php } ?>
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Employee') { ?>
                                    <a href="preferences_user.php" class="dropdown-item notify-item">
                                        <i class="zmdi zmdi-settings"></i> <span>Pengaturan</span>
                                    </a>
                                <?php } ?>
                                <a href="process.php?action=logOut" class="dropdown-item notify-item">
                                    <i class="zmdi zmdi-power"></i> <span>Keluar</span>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
        <div class="navbar-custom">
            <div class="container">
                <div id="navigation">
                    <ul class="navigation-menu">
                        <li>
                            <a href="admin.php"><i class="zmdi zmdi-view-dashboard"></i> <span> Dashboard </span></a>
                        </li>
                        <?php if (function_exists('ali_is_owner') && ali_is_owner()) { ?>
                            <li class="has-submenu">
                                <a href="#"><i class="fa fa-users"></i> <span> MANAJEMEN AKUN </span></a>
                                <ul class="submenu">
                                    <li><a href="add-new-users-admin.php"><i class="fa fa-users"></i> Akun Administrator</a></li>
                                    <li><a href="add-new-users.php"><i class="fa fa-users"></i> Akun Pegawai</a></li>
                                    <li><a href="customer.php"><i class="fa fa-user-plus"></i> Akun Pelanggan</a></li>
                                </ul>
                            </li>
                            <li class="has-submenu">
                                <a href="#"><i class="fa fa-gear"></i> <span> PENGATURAN </span></a>
                                <ul class="submenu">
                                    <li><a href="service_mode.php"><i class="fa fa-gift"></i> Tipe Produk</a></li>
                                    <li><a href="type-of-shipments.php"><i class="fa fa-truck"></i> Tipe Pengiriman</a></li>
                                    <li><a href="add-office.php"><i class="fa fa-map-marker"></i> Lokasi</a></li>
                                    <li><a href="gallery-manager.php"><i class="fa fa-picture-o"></i> Gallery Website</a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <li class="has-submenu">
                            <a href="add-courier.php"><i class="fa fa-cubes"></i> <span> TAMBAH PENGIRIMAN </span></a>
                        </li>
                        <li class="has-submenu">
                            <a href="customer-profile.php"><i class="fa fa-users"></i> <span> CUSTOMER </span></a>
                        </li>
                        <li class="has-submenu">
                            <a href="catatan.php"><i class="fa fa-th-large"></i> <span> CATATAN </span></a>
                        </li>
                        <?php
                        try {
                            $stmt = dbQuery("SELECT * FROM online_booking WHERE status = ?", ['Pending']);
                            $nobookings = dbNumRows($stmt);
                        } catch (PDOException $e) {
                            error_log("Header PDO Error: " . $e->getMessage());
                            $nobookings = 0;
                        }
                        ?>
                        <!-- <li class="has-submenu">
                            <a href="online-bookings.php"><i class="fa fa-envelope"> <font color="#FF0000"><strong><?php echo $nobookings; ?></strong></font></i> <span> ONLINE PESANAN</span></a>
                            <ul class="submenu">
                                <li><a href="transfer-bank.php"><i class="fa fa-money"></i> Daftar Pembayaran, Transfer Bank</a></li>
                            </ul>
                        </li> -->
                        <li class="has-submenu">
                            <a href="#"><i class="zmdi zmdi-collection-text"></i> <span> LAPORAN </span></a>
                            <ul class="submenu">
                                <li><a href="laporan-selesai.php"><i class="fa fa-check-circle"></i> Pengiriman selesai</a></li>
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Administrator') { ?>
                                <li><a href="laporan-piutang.php"><i class="fa fa-credit-card"></i> Piutang dan tempo</a></li>
                                <li><a href="laporan-lunas.php"><i class="fa fa-money"></i> Sudah dibayar</a></li>
                                <li><a href="laporan-rekap.php"><i class="fa fa-bar-chart"></i> Rekap bulanan</a></li>
                                <li><a href="export-monthly.php"><i class="fa fa-file-excel-o"></i> Export Excel bulanan</a></li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Administrator') { ?>
                            <li class="has-submenu">
                                <a><span> | </span></a>
                            </li>
                            <li class="has-submenu">
                                <a href="preferences.php"><i class="fa fa-briefcase"></i> <span> KANTOR </span></a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </header>