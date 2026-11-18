<?php
// Header bersama untuk halaman kebijakan, pendaftaran, dan sejenisnya.
// Markup-nya sama dengan header di beranda, tampilannya diatur di
// deprixa_components/styles/ali-site.css
?>
<link rel="stylesheet" href="deprixa_components/styles/ali-site.css?v=<?php echo @filemtime(__DIR__ . '/deprixa_components/styles/ali-site.css'); ?>" />
<header class="header-wrap">
  <div class="white-part"></div>
  <div class="red-part"></div>
  <div class="header-content">
    <a href="/" class="logo-link">
      <img src="logoAli.png" alt="Logo Aneka Lintas Indonesia Cargo" class="logo-image">
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
      <a href="gallery">Gallery</a>
      <a href="contact-us">Hubungi Kami</a>
      <a href="login.php">LOGIN</a>
    </nav>
  </div>
</header>
