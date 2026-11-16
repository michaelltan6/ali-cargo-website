<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
require_once('deprixa/database-settings.php');
require_once('deprixa/library.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <title>Tracking Kiriman | ALI Cargo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="canonical" href="https://www.anekalintasindcargo.com/tracking.php" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="Aneka Lintas Indonesia Cargo" />
  <meta property="og:locale" content="id_ID" />
  <meta property="og:title" content="Tracking Kiriman | ALI Cargo" />
  <meta property="og:description" content="Lacak kiriman Anda dengan nomor resi ALI Cargo" />
  <meta property="og:url" content="https://www.anekalintasindcargo.com/tracking.php" />
  <meta property="og:image" content="https://www.anekalintasindcargo.com/deprixa_components/images/slider/img1.png" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="description" content="Lacak kiriman Anda dengan nomor resi ALI Cargo">
  <link rel="icon" type="image/png" href="logoAli.png">

  <!-- CSS Global -->
  <link rel="stylesheet" href="deprixa_components/hub/css/global.css" />
  <link rel="stylesheet" href="deprixa_components/hub/css/bootstrap-mpd.css" />
  <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
  <link href="deprixa_components/content/cssefe4.css" rel="stylesheet" />
  <link href="deprixa_components/styles/track-order.css" rel="stylesheet" />

  <!-- Styling Header & Konsistensi -->
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

    .track-num h3 {
      color: white;
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
    }
  </style>
  <link rel="stylesheet" href="deprixa_components/styles/ali-site.css?v=<?php echo @filemtime(__DIR__ . '/deprixa_components/styles/ali-site.css'); ?>" />
</head>

<body>

<!-- ✅ HEADER -->
<header class="header-wrap">
  <div class="white-part"></div>
  <div class="red-part"></div>
  <div class="header-content">
    <a href="index.php" class="logo-link">
      <img src="logoAli.png" alt="Logo" class="logo-image">
      <div class="company-name">
        <span>ANEKA LINTAS</span>
        <span>INDONESIA</span>
        <span>CARGO</span>
      </div>
    </a>
    <nav class="menu-nav">
      <a href="layanan.php">Layanan</a>
      <a href="tracking.php">Tracking Kiriman</a>
      <a href="about-us.php">Tentang Kami</a>
      <a href="gallery.php">Gallery</a>
      <a href="contact-us.php">Hubungi Kami</a>
      <a href="login.php">LOGIN</a>
    </nav>
  </div>
</header>

<main class="slide">

  <div class="fw">
    <section class="title">
      <header>
        <h1><img src="deprixa_components/images/global/tracking-search.png" /> Lacak Kiriman Anda</h1>
        <p class="mobHide">
          Masukkan nomor resi seperti <strong>ALI12345</strong> untuk mengetahui status kiriman Anda.
        </p>
      </header>
    </section>
  </div>

  <div class="fw" style="background-color:#ff0000">
    <section class="track-num">
      <h3>MASUKKAN NOMOR RESI</h3>
      <div class="search-bar">
        <form action="tracking-result.php" method="post" name="form" id="form">
          <div class="form-group mob-track">
            <div class="input-group">
              <input type="text" class="form-control" name="Consignment" id="Consignment" placeholder="Contoh: ALI12345">
            </div>
          </div>
          <button type="submit" class="btn btn-primary" name="Submit" onclick="MM_validateForm('Consignment','','R');return document.MM_returnValue">
            <img src="deprixa/images/Tracking.png" alt="Track" /> Tracking Kiriman Saya
          </button>
          <div class="wait-message" style="text-align:center; display:none;">
            <img src="deprixa_components/images/global/loading-green.gif" /> Sedang mencari...
          </div>
        </form>
      </div>
    </section>
  </div>

  <div class="fw grey-bg">
    <section class="trackorder-boxes" style="display:flex; justify-content:center">
      <div class="col-sm-6">
        <div class="pod">
          <div class="media-body">
            <span class="track-icon-back3 mpdRed"></span>
            <h3>BUTUH BANTUAN?</h3>
            <p>Jika Anda memiliki pertanyaan tentang pengiriman, silakan hubungi kami langsung.</p>
            <a href="contact-us.php" class="btn btn-primary">Hubungi Kami</a>
          </div>
        </div>
      </div>
    </section>
  </div>
</main>

<!-- ✅ FOOTER -->
<?php include_once "footer.php"; ?>

<!-- ✅ SCRIPT -->
<script src="deprixa_components/bundles/jquery"></script>
<script src="deprixa_components/bundles/bootstrap"></script>
<script src="deprixa_components/bundles/modernizr"></script>
<script src="deprixa_components/scripts/CookieManager.js"></script>
<script src="deprixa_components/scripts/MPD/Common/ga-events.js"></script>
<script src="deprixa_components/bundles/jqueryval"></script>
<script src="deprixa_components/scripts/tracking.js"></script>
<script src="deprixa_components/scripts/placeholder-shim.js"></script>
<script src="deprixa_components/scripts/trimFields.js"></script>

</body>
</html>
