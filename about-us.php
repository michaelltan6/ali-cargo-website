<?php
require_once __DIR__ . '/layanan-lib.php';
require_once __DIR__ . '/gallery-lib.php';
$d = ls_data();
$aboutPhoto = null;
foreach (gallery_load_albums() as $al) { $im = gallery_images($al['id']); if ($im) { $aboutPhoto = 'gallery/' . $al['id'] . '/' . rawurlencode($im[0]); break; } }
$total = 0; foreach ($d['data'] as $rows) { $total += count($rows); }

ls_head('Tentang Kami - Aneka Lintas Indonesia Cargo',
    'PT. Aneka Lintas Indonesia Cargo, ekspedisi cargo sejak 2020. Melayani pengiriman darat dan laut dari Jakarta ke ' . $total . ' kota di seluruh Indonesia.',
    'about-us.php', [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'PT. Aneka Lintas Indonesia Cargo',
        'url' => ALI_SITE,
        'logo' => ALI_SITE . 'logoAli.png',
        'foundingDate' => '2020',
        'description' => 'Ekspedisi cargo yang melayani pengiriman ke seluruh Indonesia.',
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Ruko Hawaii No. 70, City Resort, Cengkareng', 'addressLocality' => 'Jakarta Barat', 'addressCountry' => 'ID'],
    ]);
?>
<section class="ls-hero">
  <div class="ls-wrap">
    <nav class="ls-crumb" aria-label="Navigasi"><a href="index.php">Beranda</a> &rsaquo; Tentang Kami</nav>
    <h1>Tentang Aneka Lintas Indonesia Cargo</h1>
    <p class="ls-lead">Kami menyediakan layanan pengiriman ke seluruh penjuru Indonesia, dengan komitmen agar setiap kiriman tiba di tujuan dengan cepat dan aman.</p>
    <div class="ls-hero-actions">
      <a class="ls-btn" href="<?php echo ls_h(ls_wa('Halo ALI Cargo, saya mau tanya soal layanan pengiriman.')); ?>" target="_blank" rel="noopener">Hubungi kami lewat WhatsApp</a>
      <a class="ls-btn-ghost" href="layanan.php">Lihat layanan</a>
    </div>
  </div>
</section>

<div class="pg-stats"><div class="pg-stats-in">
  <div class="pg-stat"><strong>2020</strong><span>Mulai beroperasi</span></div>
  <div class="pg-stat"><strong><?php echo $total; ?></strong><span>Kota tujuan</span></div>
  <div class="pg-stat"><strong>Darat &amp; Laut</strong><span>Jalur pengiriman</span></div>
  <div class="pg-stat"><strong>Sen - Sab</strong><span>09.00 - 18.00 WIB</span></div>
</div></div>

<section class="ls-wrap ls-section">
  <div class="pg-split">
    <div>
      <h2>Mitra pengiriman untuk bisnis dan perorangan</h2>
      <p>Ekspedisi Aneka Lintas Indonesia Cargo didirikan pada tahun 2020 dengan visi menjadi pemimpin dalam layanan pengiriman cargo di Indonesia. Kami berkomitmen memastikan setiap kiriman tiba di tujuan dengan cepat, aman, dan dengan tarif yang kompetitif.</p>
      <p>Kami berpengalaman menangani berbagai jenis barang, mulai dari paket kecil sampai proyek logistik berskala besar. Solusi pengirimannya fleksibel dan disesuaikan dengan kebutuhan setiap klien. Tim yang profesional dan berdedikasi membuat kami menjadi mitra tepercaya bagi bisnis dan individu di seluruh negeri.</p>
      <p>Jaringan kami mencakup Sumatera, Jawa, Kalimantan, Sulawesi, Bali, NTT, NTB, Maluku, hingga Papua. Kepercayaan pelanggan adalah prioritas utama kami, dan kami terus berinovasi supaya layanannya andal dan efisien, sekaligus mendukung pertumbuhan ekonomi lokal dan nasional.</p>
    </div>
    <aside class="pg-card">
      <h3>Profil perusahaan</h3>
      <dl>
        <dt>Nama</dt><dd>PT. Aneka Lintas Indonesia Cargo</dd>
        <dt>Bidang usaha</dt><dd>Ekspedisi dan pengiriman cargo</dd>
        <dt>Berdiri</dt><dd>2020</dd>
        <dt>Kantor</dt><dd>Ruko Hawaii No. 70, City Resort, Cengkareng, Jakarta Barat</dd>
        <dt>Jam layanan</dt><dd>Senin - Sabtu, 09.00 - 18.00 WIB</dd>
      </dl>
    </aside>
  </div>
  <?php if ($aboutPhoto): ?>
  <figure class="pg-photo"><img src="<?php echo ls_h($aboutPhoto); ?>" alt="Barang kiriman yang ditangani ALI Cargo" loading="lazy"><figcaption>Barang kiriman yang kami tangani. <a href="gallery.php">Lihat gallery</a></figcaption></figure>
  <?php endif; ?>
</section>

<section class="pg-section-alt">
  <div class="ls-wrap ls-section">
    <h2>Yang kami pegang</h2>
    <div class="pg-values">
      <div class="pg-value"><h3>Cepat</h3><p>Estimasi waktu yang jelas untuk setiap kota tujuan, jadi kamu bisa merencanakan pengiriman dengan tenang.</p></div>
      <div class="pg-value"><h3>Aman</h3><p>Kami bertanggung jawab atas kekurangan, kerusakan, atau kehilangan barang yang disebabkan kelalaian kami.</p></div>
      <div class="pg-value"><h3>Tarif kompetitif</h3><p>Penawaran disesuaikan dengan tujuan dan jenis barang, supaya kamu mendapat nilai terbaik.</p></div>
      <div class="pg-value"><h3>Fleksibel</h3><p>Dari paket kecil sampai proyek besar, solusinya mengikuti kebutuhan kamu.</p></div>
    </div>
  </div>
</section>

<section class="ls-wrap ls-section">
  <h2>Jangkauan pengiriman</h2>
  <p>Pilih wilayah untuk melihat kota tujuan dan estimasi waktunya.</p>
  <?php ls_region_links(); ?>
  <p><a href="rute-pengiriman.php">Lihat semua <?php echo $total; ?> rute</a></p>
  <?php ls_cta('Halo ALI Cargo, saya mau tanya soal pengiriman.', 'Hubungi lewat WhatsApp'); ?>
</section>
<?php ls_foot();
