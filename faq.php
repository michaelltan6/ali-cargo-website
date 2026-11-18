<?php
require_once __DIR__ . '/layanan-lib.php';
$faq = [
 ['id'=>'waktu','q'=>'Berapa lama pengiriman dari Jakarta?','a'=>'Tergantung kota tujuan. Beberapa contoh: Cirebon dan Bandung 1-2 hari, Surabaya 2-3 hari, Medan 5-7 hari, Makassar 7-10 hari, Balikpapan 10-12 hari, Papua 21-25 hari. Daftar lengkap ada di halaman Rute. Estimasi bisa bergeser sedikit, terutama untuk pengiriman lewat kapal.'],
 ['id'=>'ongkos','q'=>'Bagaimana cara hitung ongkos kirim?','a'=>'Tarif tersedia per kg dan per m³ (kubikasi), dan tiap tujuan punya minimum pengiriman sendiri. Untuk kubikasi, kalikan panjang × lebar × tinggi dalam meter. Kirim kota tujuan, berat, dan ukuran barang ke tim kami, kami hitungkan mana yang berlaku dan kirim penawarannya.'],
 ['id'=>'antar','q'=>'Apakah ongkos sudah termasuk diantar sampai alamat?','a'=>'Harga berlaku untuk pengantaran di dalam kota tujuan, bukan ke luar kota. Kalau alamat penerima di luar kota tujuan, tanyakan dulu ke tim kami.'],
 ['id'=>'tidak-termasuk','q'=>'Biaya apa saja yang tidak termasuk?','a'=>'Harga pengiriman tidak termasuk biaya bongkar, pajak, dan biaya lain di luar pengiriman.'],
 ['id'=>'penerus','q'=>'Apa artinya kota bertanda bintang (*) di daftar rute?','a'=>'Kota itu dilayani lewat mitra penerus di daerah tujuan. Karena itu ada biaya penerus tambahan per pengiriman. Besarnya kami sampaikan saat memberi penawaran.'],
 ['id'=>'rusak','q'=>'Bagaimana kalau barang rusak atau hilang?','a'=>'Kami bertanggung jawab atas kekurangan, kerusakan, atau kehilangan barang yang disebabkan oleh kelalaian kami.'],
 ['id'=>'penawaran','q'=>'Bagaimana cara minta penawaran?','a'=>'Hubungi kami lewat WhatsApp atau halaman Hubungi Kami. Siapkan kota tujuan, jenis barang, dan perkiraan berat atau ukurannya supaya penawarannya cepat.'],
 ['id'=>'lacak','q'=>'Bisakah saya melacak kiriman?','a'=>'Bisa. Masukkan nomor kiriman di halaman Tracking Kiriman.'],
];
$ld = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[]];
foreach ($faq as $f) { $ld['mainEntity'][] = ['@type'=>'Question','name'=>$f['q'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f['a']]]; }
ls_head('FAQ Pengiriman Cargo dan Cara Hitung Ongkos | ALI Cargo',
    'Jawaban pertanyaan yang sering diajukan: estimasi waktu, cara hitung ongkos per kg dan m³, biaya yang tidak termasuk, dan tanggung jawab atas barang.',
    'faq.php', $ld);
?>
<section class="ls-hero">
  <div class="ls-wrap">
    <nav class="ls-crumb" aria-label="Navigasi"><a href="index.php">Beranda</a> &rsaquo; <a href="layanan.php">Layanan</a> &rsaquo; FAQ</nav>
    <h1>Pertanyaan yang Sering Diajukan</h1>
    <p class="ls-lead">Soal waktu, cara hitung ongkos, dan ketentuan pengiriman.</p>
  </div>
</section>
<section class="ls-wrap ls-section">
  <?php foreach ($faq as $f): ?>
  <details class="ls-faq" id="<?php echo ls_h($f['id']); ?>">
    <summary><?php echo ls_h($f['q']); ?></summary>
    <p><?php echo ls_h($f['a']); ?></p>
  </details>
  <?php endforeach; ?>
  <?php ls_cta('Halo ALI Cargo, saya punya pertanyaan soal pengiriman.', 'Tanya lewat WhatsApp'); ?>
  <p><a href="rute-pengiriman.php">Lihat semua rute</a> &middot; <a href="layanan.php">Kembali ke Layanan</a></p>
</section>
<script>
// Buka pertanyaan yang dituju lewat tautan #id
(function(){var h=location.hash.slice(1);if(h){var e=document.getElementById(h);if(e&&e.tagName==='DETAILS'){e.open=true;}}})();
</script>
<?php ls_foot();
