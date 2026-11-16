<?php
require_once __DIR__ . '/layanan-lib.php';
$d = ls_data();
$total = 0; $darat = 0; $laut = 0;
foreach ($d['data'] as $rows) foreach ($rows as $r) { $total++; if ($r['jenis'] === 'darat') $darat++; else $laut++; }
ls_head('Layanan Ekspedisi Cargo Darat dan Laut ke Seluruh Indonesia | ALI Cargo',
    'Layanan ekspedisi cargo darat dan laut dari Jakarta ke ' . $total . ' kota di seluruh Indonesia. Aneka Lintas Indonesia Cargo, minta penawaran lewat WhatsApp.',
    'layanan.php');
?>
<section class="ls-hero">
  <div class="ls-wrap">
    <nav class="ls-crumb" aria-label="Navigasi"><a href="index.php">Beranda</a> &rsaquo; Layanan</nav>
    <h1>Layanan Ekspedisi Cargo ke Seluruh Indonesia</h1>
    <p class="ls-lead">PT. Aneka Lintas Indonesia Cargo mengirim barang dari Jakarta ke <?php echo $total; ?> kota tujuan, lewat jalur darat dan laut. Kami bantu pilih cara yang paling pas untuk barang dan tujuan kamu.</p>
  </div>
</section>

<section class="ls-wrap ls-section">
  <div class="ls-cards">
    <article class="ls-card">
      <h2>Cargo Darat</h2>
      <p>Pengiriman memakai truk ke kota-kota di Sumatera, Jawa, Bali, dan Lombok. Cocok kalau kamu butuh barang cepat sampai di pulau yang tersambung jalan.</p>
      <p class="ls-meta"><?php echo $darat; ?> kota tujuan</p>
    </article>
    <article class="ls-card">
      <h2>Cargo Laut</h2>
      <p>Pengiriman memakai kapal reguler ke Kalimantan, Sulawesi, Maluku, Papua, NTT, NTB, dan sebagian Sumatera. Pilihan hemat untuk barang yang tidak mengejar waktu.</p>
      <p class="ls-meta"><?php echo $laut; ?> kota tujuan</p>
    </article>
    <article class="ls-card">
      <h2>Cargo Udara</h2>
      <p>Untuk kiriman yang perlu cepat. Ketersediaan dan estimasinya menyesuaikan tujuan, jadi tanyakan langsung ke tim kami.</p>
      <p class="ls-meta">Sesuai permintaan</p>
    </article>
  </div>
  <?php ls_cta('Halo ALI Cargo, saya mau tanya layanan pengiriman dari Jakarta.'); ?>
</section>

<section class="ls-wrap ls-section">
  <h2>Pilih wilayah tujuan</h2>
  <p>Setiap wilayah punya daftar kota dan estimasi harinya sendiri.</p>
  <?php ls_region_links(); ?>
  <p><a href="rute-pengiriman.php">Lihat semua <?php echo $total; ?> rute sekaligus</a></p>
</section>

<section class="ls-wrap ls-section">
  <h2>Cara kerjanya</h2>
  <ol class="ls-steps">
    <li><strong>Hubungi kami.</strong> Sebutkan kota tujuan, jenis barang, dan perkiraan berat atau ukurannya lewat WhatsApp.</li>
    <li><strong>Terima penawaran.</strong> Kami hitung ongkosnya dan jelaskan estimasi waktu.</li>
    <li><strong>Barang dikirim.</strong> Kamu bisa memantau kirimannya lewat halaman <a href="tracking.php">Tracking</a>.</li>
  </ol>
  <p>Ada pertanyaan soal biaya, waktu, atau ketentuan? Lihat <a href="faq.php">pertanyaan yang sering diajukan</a>.</p>
</section>
<?php ls_foot();
