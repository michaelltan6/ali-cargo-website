<?php
require_once __DIR__ . '/layanan-lib.php';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sales = [
    ['Sales 1', '6281376927815', '+62 813-7692-7815'],
    ['Sales 2', '6281263537777', '+62 812-6353-7777'],
];
ls_head('Hubungi Kami - Aneka Lintas Indonesia Cargo',
    'Hubungi PT. Aneka Lintas Indonesia Cargo lewat WhatsApp, telepon, atau formulir. Kantor di Ruko Hawaii, Cengkareng, Jakarta Barat. Senin-Sabtu 09.00-18.00.',
    'contact-us.php', [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => 'PT. Aneka Lintas Indonesia Cargo',
        'url' => ALI_SITE,
        'image' => ALI_SITE . 'logoAli.png',
        'telephone' => '+6281263537777',
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Ruko Hawaii No. 70, City Resort, Cengkareng', 'addressLocality' => 'Jakarta Barat', 'addressCountry' => 'ID'],
        'openingHoursSpecification' => [['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'], 'opens' => '09:00', 'closes' => '18:00']],
    ]);
?>
<section class="ls-hero">
  <div class="ls-wrap">
    <nav class="ls-crumb" aria-label="Navigasi"><a href="index.php">Beranda</a> &rsaquo; Hubungi Kami</nav>
    <h1>Hubungi Kami</h1>
    <p class="ls-lead">Mau tanya ongkos, estimasi waktu, atau cara kirim barang? Tim kami siap membantu, Senin sampai Sabtu pukul 09.00 - 18.00 WIB.</p>
  </div>
</section>

<section class="ls-wrap ls-section">
  <div class="pg-contact-cards">
    <div class="pg-card">
      <h3>WhatsApp</h3>
      <p>Cara tercepat untuk minta penawaran. Sebutkan kota tujuan, jenis barang, dan perkiraan berat atau ukurannya.</p>
      <?php foreach ($sales as $s): ?>
      <a class="ls-btn" href="<?php echo ls_h(ls_wa('Halo ALI Cargo, saya mau tanya soal pengiriman.', $s[1])); ?>" target="_blank" rel="noopener"><?php echo ls_h($s[0]); ?>: <?php echo ls_h($s[2]); ?></a>
      <?php endforeach; ?>
    </div>
    <div class="pg-card">
      <h3>Telepon</h3>
      <p>Silakan telepon pada jam layanan.</p>
      <p><a href="tel:+6281263537777"><strong>+62 812-6353-7777</strong></a></p>
      <dl><dt>Jam layanan</dt><dd>Senin - Sabtu, 09.00 - 18.00 WIB</dd></dl>
    </div>
    <div class="pg-card">
      <h3>Kantor</h3>
      <p>PT. Aneka Lintas Indonesia Cargo<br>Ruko Hawaii No. 70, City Resort, Cengkareng<br>Jakarta Barat, Indonesia</p>
      <a href="https://www.google.com/maps/search/?api=1&amp;query=PT.+Aneka+Lintas+Indonesia+Cargo+Cengkareng" target="_blank" rel="noopener">Buka di Google Maps</a>
    </div>
  </div>
</section>

<section class="ls-wrap ls-section" id="kirim-pesan">
  <div class="pg-split">
    <div>
      <h2>Kirim pesan</h2>
      <p>Tidak bisa online? Tulis pesanmu di sini, kami balas dalam 2 jam kerja.</p>
      <?php if ($status === 'ok'): ?>
        <div class="pg-alert pg-alert-ok" role="status">Terima kasih, pesanmu sudah terkirim. Kami balas secepatnya lewat email.</div>
      <?php elseif ($status === 'gagal'): ?>
        <div class="pg-alert pg-alert-err" role="alert">Pesan belum terkirim. Coba lagi sebentar, atau hubungi kami lewat WhatsApp.</div>
      <?php elseif ($status === 'lengkapi'): ?>
        <div class="pg-alert pg-alert-err" role="alert">Data belum lengkap atau emailnya kurang tepat. Periksa lagi, ya.</div>
      <?php elseif ($status === 'tunggu'): ?>
        <div class="pg-alert pg-alert-err" role="alert">Kamu baru saja mengirim pesan. Tunggu sebentar sebelum mengirim lagi.</div>
      <?php endif; ?>
      <form class="pg-form" action="prose-form.php" method="post">
        <label for="f-name">Nama lengkap</label>
        <input type="text" id="f-name" name="name" maxlength="100" required autocomplete="name">
        <label for="f-email">Email</label>
        <input type="email" id="f-email" name="email" maxlength="150" required autocomplete="email">
        <label for="f-subject">Subjek</label>
        <input type="text" id="f-subject" name="subject" maxlength="150" required>
        <label for="f-msg">Pesan</label>
        <textarea id="f-msg" name="comment" maxlength="3000" required></textarea>
        <div class="pg-hp" aria-hidden="true"><label>Kosongkan kolom ini <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <button type="submit" class="ls-btn ls-btn-red">Kirim pesan</button>
      </form>
    </div>
    <div class="pg-map">
      <h2>Lokasi kami</h2>
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.1897611773897!2d106.73005679999999!3d-6.132060599999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f7e3a1692a59%3A0xf2e3270f2673006e!2sPT.%20Aneka%20Lintas%20Indonesia%20Cargo%2C%20Jasa%20pengiriman%20Darat%2C%20Laut%20dan%20Udara.!5e0!3m2!1sen!2sid!4v1713200000000!5m2!1sen!2sid" title="Peta lokasi PT. Aneka Lintas Indonesia Cargo" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      <p class="ls-note">Pin menunjukkan area Ruko Hawaii, City Resort, Cengkareng.</p>
    </div>
  </div>
</section>
<?php ls_foot();
