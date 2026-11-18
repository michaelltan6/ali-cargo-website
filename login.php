<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
}
require_once('deprixa/database.php');
require_once('deprixa/library.php');
require_once('deprixa/funciones.php');

// Check if user is already logged in and session is valid
if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name']) && isUser()) {
    if ($_SESSION['user_type'] === 'client') {
        header("Location: deprixa/panel-customer/customer.php");
        exit;
    } else {
        header("Location: deprixa/admin.php");
        exit;
    }
} else {
    // Clear session if invalid or not logged in
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['user']) ? trim($_POST['user']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    if (empty($username) || empty($password)) {
        $error = "Please enter both email and password.";
        error_log("Login attempt failed: Empty username or password");
    } else {
        $error = verify_users($username, $password);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, follow" />
    <meta name="description" content="PT. ANEKA LINTAS INDONESIA - Admin Login">
    <meta name="keywords" content="courier, shipping, logistics">
    <meta name="author" content="PT. ANEKA LINTAS INDONESIA">
    <title>PT. ANEKA LINTAS INDONESIA CARGO</title>
    <link rel="icon" type="image/png" href="logoAli.png">
    <link rel="icon" type="image/png" href="favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="favicon-16x16.png" sizes="16x16">
    <!-- CSS Global -->
    <link rel="stylesheet" href="deprixa_components/hub/css/global.css" />
    <link rel="stylesheet" href="deprixa_components/hub/css/bootstrap-mpd.css" />
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link href="deprixa_components/content/cssefe4.css" rel="stylesheet" />
    <link href="deprixa_components/styles/login.css" rel="stylesheet" />
    <style>
        .header-wrap {
            position: relative;
            height: 120px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.45);
            z-index: 10;
        }
        .white-part,
        .red-part {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .white-part {
            background-color: white;
            z-index: 1;
        }
        .red-part {
            background-color: #ff0000;
            clip-path: polygon(55% 0, 100% 0, 100% 100%, 45% 100%);
            z-index: 2;
        }
        .header-content {
            position: relative;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            height: 100%;
        }
        .logo-link {
            display: flex;
            align-items: center;
            text-decoration: none !important;
            margin-left: 60px;
        }
        .logo-image {
            max-height: 85px;
            margin-right: 20px;
        }
        .company-name {
            font-size: 2.6rem;
            font-weight: 800;
            color: #222;
            line-height: 1.1;
        }
        .company-name span {
            display: block;
        }
        .menu-nav {
            display: flex;
            gap: 30px;
            margin-right: 80px;
        }
        .menu-nav a {
            color: #fff;
            font-weight: 700;
            font-size: 1.8rem;
            text-decoration: none;
            border-right: 1px solid rgba(255, 255, 255, 0.4);
            padding-right: 16px;
        }
        .menu-nav a:last-child {
            border-right: none;
        }
        .menu-nav a:hover {
            color: #ffdcdc;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-primary {
            width: 100%;
            padding: 10px;
            background: #007bff;
            border: none;
            color: #fff;
            border-radius: 4px;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .alert-danger {
            color: #721c24;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                padding: 20px;
                text-align: center;
            }
            .logo-link {
                margin: 0;
                flex-direction: column;
            }
            .menu-nav {
                flex-direction: column;
                gap: 10px;
                margin-right: 0;
            }
            .company-name {
                font-size: 2rem;
                text-align: center;
            }
            .login-container {
                margin: 50px auto;
                padding: 15px;
            }
        }
    </style>
  <link rel="stylesheet" href="deprixa_components/styles/ali-site.css?v=<?php echo @filemtime(__DIR__ . '/deprixa_components/styles/ali-site.css'); ?>" />
</head>
<body>
    <!-- Header -->
    <header class="header-wrap">
        <div class="white-part"></div>
        <div class="red-part"></div>
        <div class="header-content">
            <a href="/" class="logo-link">
                <img src="logoAli.png" alt="Logo" class="logo-image">
                <div class="company-name">
                    <span>ANEKA LINTAS</span>
                    <span>INDONESIA</span>
                    <span>CARGO</span>
                </div>
            </a>
            <nav class="menu-nav">
                <a href="layanan">Layanan</a>
                <a href="tracking">Tracking Kiriman</a>
                <a href="about-us">Tentang Kami</a>
                <a href="gallery.php">Gallery</a>
                <a href="contact-us">Hubungi Kami</a>
                <a href="login.php">LOGIN</a>
            </nav>
        </div>
    </header>

    <main class="slide">
        <section class="login">
            <div class="login-container">
                <h2>Masuk</h2>
                <?php if ($error) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>
                <form name="form1" autocomplete="off" method="post" action="login.php">
                    <div class="form-group">
                        <label for="user">Email</label>
                        <input class="form-control" data-val="true" data-val-required="Please enter an email" name="user" id="user" type="text" value="<?php echo htmlspecialchars($username ?? ''); ?>" />
                        <span class="field-validation-valid text-danger" data-valmsg-for="user" data-valmsg-replace="true"></span>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input class="form-control password" data-val="true" data-val-required="Please enter a password" name="password" id="password" type="password" />
                        <span class="field-validation-valid text-danger" data-valmsg-for="password" data-valmsg-replace="true"></span>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Log in</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php
    try {
        include_once "footer.php";
    } catch (Exception $e) {
        error_log("Failed to include footer.php: " . $e->getMessage());
        echo "<p class='text-danger'>Error including footer: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>

    <!-- Scripts -->
    <script src="deprixa_components/bundles/jquery"></script>
    <script src="deprixa_components/bundles/bootstrap"></script>
    <script src="deprixa_components/bundles/modernizr"></script>
    <script src="deprixa_components/scripts/CookieManager.js"></script>
    <script src="deprixa_components/scripts/MPD/Common/ga-events.js"></script>
    <script src="deprixa_components/bundles/jqueryval"></script>
    <script src="deprixa_components/scripts/trimFields.js"></script>
</body>
</html>