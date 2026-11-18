<?php
require_once __DIR__ . '/layanan-lib.php';
$hm_d = ls_data();
$hm_cities = 0; $hm_min = 999;
foreach ($hm_d['data'] as $rows) { $hm_cities += count($rows); $r = ls_range($rows); $hm_min = min($hm_min, $r[0]); }
$hm_regions = count($hm_d['order']);
function hm_find($name) { global $hm_d; foreach ($hm_d['data'] as $rows) { foreach ($rows as $r) { if (stripos($r['kota'], $name) === 0) { return $r; } } } return null; }
$hm_wa = function ($t) { return ls_h(ls_wa($t)); };
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <title>Pengiriman Cargo Terpercaya - Aneka Lintas Indonesia Cargo</title>
  <meta name="description" content="Aneka Lintas Indonesia Cargo menyediakan jasa pengiriman cargo ke seluruh Indonesia. Lacak kiriman Anda dengan mudah dan dapatkan layanan terbaik!">
  <meta name="keywords" content="pengiriman cargo, jasa ekspedisi, lacak kiriman, cargo Indonesia, Aneka Lintas Indonesia" />
  <meta name="author" content="Aneka Lintas Indonesia Cargo">	
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="canonical" href="https://www.anekalintasindcargo.com/" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="Aneka Lintas Indonesia Cargo" />
  <meta property="og:locale" content="id_ID" />
  <meta property="og:title" content="Pengiriman Cargo Terpercaya - Aneka Lintas Indonesia Cargo" />
  <meta property="og:description" content="Aneka Lintas Indonesia Cargo menyediakan jasa pengiriman cargo ke seluruh Indonesia. Lacak kiriman Anda dengan mudah dan dapatkan layanan terbaik!" />
  <meta property="og:url" content="https://www.anekalintasindcargo.com/" />
  <meta property="og:image" content="https://www.anekalintasindcargo.com/deprixa_components/images/slider/img1.png" />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="icon" type="image/png" href="logoAli.png">

  <!-- Styles -->
  <link href="deprixa_components/content/cssefe4.css" rel="stylesheet"/>
  <link href="deprixa_components/styles/track-order.css" rel="stylesheet"/>	
  <link rel="stylesheet" href="deprixa_components/hub/css/global.css" />
  <link rel="stylesheet" href="deprixa_components/hub/css/services.css" />
  <link rel="stylesheet" href="deprixa_components/hub/css/dSwiper.css" />
  <link rel="stylesheet" href="deprixa_components/hub/css/bootstrap-mpd.css"/>				
  <link href="deprixa_components/styles/home1d2d.css" rel="stylesheet"/>
  <link rel="stylesheet" href="deprixa_components/styles/default.css" /> 
  <!-- Zebra DatePicker CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/zebra_datepicker@1.9.18/dist/css/bootstrap/zebra_datepicker.min.css" />

  <style>
    /* Header CSS */
    .header-wrap { position: relative; height: 120px; overflow: hidden; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.45); z-index: 10; }
    .white-part, .red-part { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
    .white-part { background-color: white; z-index: 1; }
    .red-part { background-color: #ff0000; clip-path: polygon(55% 0, 100% 0, 100% 100%, 45% 100%); z-index: 2; }
    .header-content { position: relative; z-index: 3; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; height: 100%; }
    .logo-link { display: flex; align-items: center; text-decoration: none !important; margin-left: 60px; }
    .logo-image { max-height: 85px; margin-right: 20px; }
    .company-name { font-size: 2.6rem; font-weight: 800; color: #222; line-height: 1.1; }
    .company-name span { display: block; }
    .menu-nav { display: flex; gap: 30px; margin-right: 80px; }
    .menu-nav a { color: #fff; font-weight: 700; font-size: 1.8rem; text-decoration: none; border-right: 1px solid rgba(255, 255, 255, 0.4); padding-right: 16px; }
    .menu-nav a:last-child { border-right: none; }
    .menu-nav a:hover { color: #ffdcdc; }
    @media (max-width: 768px) { 
      .header-content { flex-direction: column; padding: 20px; text-align: center; } 
      .logo-link { margin: 0; flex-direction: column; } 
      .menu-nav { flex-direction: column; gap: 10px; margin-right: 0; } 
      .company-name { font-size: 2rem; text-align: center; } 
    }

    /* Main Content with Video Background */
    .fw {
      position: relative;
      overflow: hidden;
      padding: 20px 0;
      min-height: 400px; /* Ensure video has space */
    }
    .fw video {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: -1;
    }
    .fw .content-overlay {
      position: relative;
      z-index: 1;
      background-color: rgba(0, 0, 0, 0.5); /* Overlay for text readability */
      padding: 20px;
      color: #fff;
    }
    .slider-wrapper { position: relative; z-index: 2; }
    .trackorder-boxes { position: relative; z-index: 2; }
    .trackorder-boxes .pod { background-color: rgba(255, 255, 255, 0.8); padding: 15px; border-radius: 8px; }
    h5 { color: #fff; position: relative; z-index: 2; }

    /* Button Styling */
    .btn-primary {
      background-color: #333; /* Black background */
      color: #fff; /* White text for visibility */
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      font-weight: 700;
      font-size: 1.6rem;
      transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
      background-color: #444; /* Lighter gray for hover */
    }

    /* Marquee for Client Logos */
    .index_partners {
      padding: 40px 20px;
      background-color: #fff; /* Changed from #f9f9f9 to match page background */
      text-align: center;
      overflow: hidden;
    }
    .jt_index_2_top {
      font-size: 2.5rem; /* Increased from 2rem */
      font-weight: 700;
      margin-bottom: 20px;
      color: #333;
    }
    .marquee-container {
      width: 100%;
      overflow: hidden;
      white-space: nowrap;
      height: 140px; /* Fits larger logos */
    }
    .marquee-content {
      display: inline-flex;
      animation: marquee 30s linear infinite;
    }
    .marquee-content:hover {
      animation-play-state: paused;
    }
    .marquee-content li {
      flex: 0 0 auto;
      margin: 0 25px;
    }
    .marquee-content img {
      width: 180px;
      height: 108px;
      object-fit: contain;
    }
    @keyframes marquee {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }
    @media (max-width: 768px) {
      .marquee-container { height: 120px; }
      .marquee-content img { width: 144px; height: 86px; }
      .marquee-content li { margin: 0 20px; }
      .jt_index_2_top { font-size: 2rem; /* Responsive size for mobile */ }
    }

    /* Fix Nivo Slider bullet issue */
    .nivo-controlNav a { background: none !important; }
  </style>

  <!-- Schema Markup -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Aneka Lintas Indonesia Cargo",
    "url": "https://www.anekalintasindcargo.com/",
    "logo": "https://www.anekalintasindcargo.com/logoAli.png",
    "description": "Aneka Lintas Indonesia Cargo menyediakan jasa pengiriman cargo terpercaya ke seluruh Indonesia dengan pelacakan real-time dan layanan terbaik.",
    "sameAs": [
      "https://www.facebook.com/anekalintasindonesiacargo",
      "https://www.instagram.com/anekalintasindonesiacargo"
    ]
  }
  </script>
  <link rel="stylesheet" href="deprixa_components/styles/ali-site.css?v=<?php echo @filemtime(__DIR__ . '/deprixa_components/styles/ali-site.css'); ?>" />
</head>

<body>
  <!-- HEADER -->
  <header class="header-wrap">
    <div class="white-part"></div>
    <div class="red-part"></div>
    <div class="header-content">
      <a href="index.php" class="logo-link">
        <img src="logoAli.png" alt="Logo Aneka Lintas Indonesia Cargo" class="logo-image">
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

  <!-- HERO -->
  <section class="hm-hero">
    <video autoplay loop muted playsinline preload="metadata" aria-hidden="true">
      <source src="deprixa_components/videos/mixkit-hong-kong-cranes-a-container-port-working-31094-hd-ready.mp4" type="video/mp4">
    </video>
    <div class="hm-hero-shade"></div>
    <div class="hm-wrap hm-hero-grid">
      <div class="hm-hero-text">
        <span class="hm-eyebrow">Jasa ekspedisi cargo dari Jakarta</span>
        <h1>Kirim cargo ke seluruh Indonesia, tanpa ribet.</h1>
        <p>Darat, laut, dan udara. Estimasi waktu jelas per kota tujuan, dan kamu bisa memantau kiriman kapan saja lewat nomor resi.</p>
        <div class="hm-hero-btns">
          <a class="hm-btn hm-btn-red" href="<?php echo $hm_wa('Halo ALI Cargo, saya mau tanya ongkos kirim.'); ?>" target="_blank" rel="noopener">Minta penawaran via WhatsApp</a>
          <a class="hm-btn hm-btn-ghost" href="rute-pengiriman.php">Lihat rute dan estimasi</a>
        </div>
      </div>
      <form class="hm-track" action="tracking-result.php" method="post" id="form">
        <h2>Lacak kiriman</h2>
        <p>Masukkan nomor resi dari kami.</p>
        <input type="text" name="Consignment" id="Consignment" placeholder="Contoh: ALI 7507" required autocomplete="off">
        <button type="submit" name="Submit">Cek status kiriman</button>
      </form>
    </div>
  </section>

  <!-- ANGKA -->
  <section class="hm-stats">
    <div class="hm-wrap hm-stats-grid">
      <div><b><?php echo (int)$hm_cities; ?></b><span>kota tujuan</span></div>
      <div><b><?php echo (int)$hm_regions; ?></b><span>wilayah dilayani</span></div>
      <div><b>mulai <?php echo (int)$hm_min; ?> hari</b><span>estimasi tercepat</span></div>
      <div><b>Online</b><span>pelacakan lewat nomor resi</span></div>
    </div>
  </section>

  <!-- KLIEN -->
<?php $hm_logos = [
  ['idmarco.png', 'Indomarco'],
  ['Mowilex.png', 'Mowilex'],
  ['fastrata_logo.png', 'Fastrata'],
  ['nippon-paint-logo.png', 'Nippon Paint'],
  ['pyramida.png', 'Pyramida'],
  ['logo gemilang manju makmur.png', 'Gemilang Manju Makmur'],
  ['logo-quadra_square-BW-023-Copy-2048x483.png', 'Quadra'],
  ['3_logo_color.png', '3'],
  ['Sosro.png', 'Sosro'],
  ['kpm.png', 'KPM'],
  ['cahayaanugra.png', 'Cahaya Anugra'],
]; ?>
  <section class="index_partners hm-clients">
    <div class="jt_index_2_top">Dipercaya oleh</div>
    <div class="marquee-container">
      <ul class="marquee-content">
        <?php for ($hm_i = 0; $hm_i < 2; $hm_i++) { foreach ($hm_logos as $hm_l) { ?>
        <li><img src="deprixa_components/images/global/courier-logos/<?php echo ls_h($hm_l[0]); ?>" alt="<?php echo ls_h($hm_l[1]); ?> - Klien Aneka Lintas Indonesia Cargo" loading="lazy" /></li>
        <?php } } ?>
      </ul>
    </div>
  </section>

  <!-- LAYANAN -->
  <section class="hm-sec">
    <div class="hm-wrap">
      <h2 class="hm-title">Layanan kami</h2>
      <p class="hm-lead">Pilih cara kirim yang paling cocok dengan barang dan jadwalmu.</p>
      <div class="hm-cards3">
        <article class="hm-card">
          <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M4 14h26v18H4zM30 20h8l6 7v5H30z" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><circle cx="14" cy="35" r="4" fill="#fff" stroke="currentColor" stroke-width="2.5"/><circle cx="37" cy="35" r="4" fill="#fff" stroke="currentColor" stroke-width="2.5"/></svg>
          <h3>Cargo darat</h3>
          <p>Truk ke kota-kota di Jawa, Bali, dan sebagian besar Sumatera. Cocok untuk barang yang perlu cepat sampai.</p>
          <a href="layanan.php">Lihat tujuan dan estimasi &rarr;</a>
        </article>
        <article class="hm-card">
          <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M6 28h36l-5 10H11zM14 28V18h14v10M28 22h6v6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M4 43c4 0 4-2 8-2s4 2 8 2 4-2 8-2 4 2 8 2 4-2 8-2" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
          <h3>Cargo laut</h3>
          <p>Kapal reguler ke Kalimantan, Sulawesi, NTT-NTB, Maluku, Papua, dan beberapa kota di Sumatera. Hemat untuk barang besar dan berat.</p>
          <a href="layanan.php">Lihat tujuan dan estimasi &rarr;</a>
        </article>
        <article class="hm-card">
          <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M44 8L4 22l14 5 5 14zM18 27L44 8" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/></svg>
          <h3>Cargo udara</h3>
          <p>Untuk kiriman mendesak. Ongkos dan jadwal disesuaikan dengan tujuan, jadi tanyakan dulu ke tim kami.</p>
          <a href="<?php echo $hm_wa('Halo ALI Cargo, saya mau tanya kirim cargo udara.'); ?>" target="_blank" rel="noopener">Tanya lewat WhatsApp &rarr;</a>
        </article>
      </div>
    </div>
  </section>

  <!-- ALASAN -->
  <section class="hm-sec hm-alt">
    <div class="hm-wrap">
      <h2 class="hm-title">Kenapa kirim lewat kami</h2>
      <div class="hm-cards4">
        <div class="hm-why"><b>Bisa dilacak</b><span>Setiap kiriman punya nomor resi. Status dan riwayat perjalanannya bisa dicek kapan saja.</span></div>
        <div class="hm-why"><b>Estimasi jelas</b><span>Waktu tempuh per kota tujuan sudah tertulis, jadi kamu bisa merencanakan sebelum kirim.</span></div>
        <div class="hm-why"><b>Diantar di kota tujuan</b><span>Barang diantar sampai alamat penerima di dalam kota tujuan.</span></div>
        <div class="hm-why"><b>Bertanggung jawab</b><span>Kerusakan atau kehilangan yang disebabkan kelalaian kami menjadi tanggung jawab kami.</span></div>
      </div>
    </div>
  </section>

  <!-- CARA KERJA -->
  <section class="hm-sec">
    <div class="hm-wrap">
      <h2 class="hm-title">Cara kirim barang</h2>
      <ol class="hm-steps">
        <li><b>Hubungi kami</b><span>Sebutkan kota tujuan, jenis barang, dan perkiraan berat atau ukurannya.</span></li>
        <li><b>Terima penawaran</b><span>Kami kirim ongkos dan estimasi waktu. Setuju, kita lanjut.</span></li>
        <li><b>Barang dikirim</b><span>Kamu dapat nomor resi untuk memantau perjalanan barang.</span></li>
        <li><b>Barang sampai</b><span>Barang diantar ke penerima, dan status di halaman tracking ikut berubah.</span></li>
      </ol>
    </div>
  </section>

  <!-- RUTE POPULER -->
  <section class="hm-sec hm-alt">
    <div class="hm-wrap">
      <h2 class="hm-title">Contoh rute dan estimasi</h2>
      <p class="hm-lead">Dari Jakarta. Daftar lengkap ada di halaman rute.</p>
      <div class="hm-routes">
        <?php foreach (['Bandung', 'Surabaya', 'Medan', 'Makassar', 'Balikpapan', 'Denpasar'] as $hm_n) { $hm_r = hm_find($hm_n); if (!$hm_r) { continue; } ?>
        <a class="hm-route" href="rute-pengiriman.php">
          <span class="hm-route-to">Jakarta &rarr; <b><?php echo ls_h($hm_r['kota']); ?></b></span>
          <span class="hm-route-meta"><?php echo ls_h(ls_jenis($hm_r['jenis'])); ?></span>
          <span class="hm-route-days"><?php echo ls_h($hm_r['hari']); ?> hari</span>
        </a>
        <?php } ?>
      </div>
      <p class="hm-more"><a href="rute-pengiriman.php">Lihat semua rute pengiriman &rarr;</a></p>
    </div>
  </section>

  <!-- FAQ -->
  <section class="hm-sec">
    <div class="hm-wrap hm-narrow">
      <h2 class="hm-title">Pertanyaan yang sering diajukan</h2>
      <details class="hm-faq"><summary>Berapa lama pengiriman dari Jakarta?</summary><p>Tergantung kota tujuan. Contohnya Bandung dan Surabaya 2-3 hari, Medan 5-7 hari, Makassar 7-10 hari. Daftar lengkap ada di halaman rute.</p></details>
      <details class="hm-faq"><summary>Bagaimana cara hitung ongkos kirim?</summary><p>Tarif per kg dan per m³ (kubikasi), dan tiap tujuan punya minimum pengiriman sendiri. Kirim kota tujuan, berat, dan ukuran barang, nanti kami hitungkan.</p></details>
      <details class="hm-faq"><summary>Bisakah saya melacak kiriman?</summary><p>Bisa. Masukkan nomor resi di kotak "Lacak kiriman" di atas atau di halaman Tracking Kiriman.</p></details>
      <details class="hm-faq"><summary>Bagaimana kalau barang rusak atau hilang?</summary><p>Kami bertanggung jawab atas kekurangan, kerusakan, atau kehilangan barang yang disebabkan oleh kelalaian kami.</p></details>
      <p class="hm-more"><a href="faq.php">Lihat semua pertanyaan &rarr;</a></p>
    </div>
  </section>

  <!-- AJAKAN -->
  <section class="hm-cta">
    <div class="hm-wrap hm-cta-in">
      <div><h2>Siap kirim barang?</h2><p>Kirim kota tujuan dan jenis barangmu. Tim kami balas dengan penawaran.</p></div>
      <div class="hm-hero-btns">
        <a class="hm-btn hm-btn-white" href="<?php echo $hm_wa('Halo ALI Cargo, saya mau minta penawaran pengiriman.'); ?>" target="_blank" rel="noopener">Chat WhatsApp</a>
        <a class="hm-btn hm-btn-ghost" href="contact-us.php">Hubungi kami</a>
      </div>
    </div>
  </section>

  <?php include_once "footer.php"; ?>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
  <script src="deprixa_components/scripts/CookieManager.js"></script>
  <script src="deprixa_components/scripts/MPD/Common/ga-events.js"></script>
  <script src="deprixa_components/scripts/placeholder-shim.js"></script>
  <script src="deprixa_components/scripts/trimFields.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/zebra_datepicker@1.9.18/dist/zebra_datepicker.min.js"></script>
  <script>
    jQuery(document).ready(function($) {
      "use strict";
      var video = document.querySelector('.hm-hero video');
      if (video && video.play) { var p = video.play(); if (p && p.catch) { p.catch(function() {}); } }
    });
  </script>
</body>
</html>