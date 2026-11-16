<?php
require_once __DIR__ . '/layanan-lib.php';
$d = ls_data();
$total = 0; $all = [];
foreach ($d['order'] as $k) { $total += count($d['data'][$k]); }
ls_head('Rute dan Estimasi Pengiriman dari Jakarta | ALI Cargo',
    'Daftar ' . $total . ' kota tujuan pengiriman dari Jakarta ke seluruh Indonesia beserta estimasi waktu dan jenis pengiriman, darat atau kapal reguler.',
    'rute-pengiriman.php');
?>
<section class="ls-hero">
  <div class="ls-wrap">
    <nav class="ls-crumb" aria-label="Navigasi"><a href="index.php">Beranda</a> &rsaquo; <a href="layanan.php">Layanan</a> &rsaquo; Rute</nav>
    <h1>Rute dan Estimasi Pengiriman dari Jakarta</h1>
    <p class="ls-lead">Kami melayani <?php echo $total; ?> kota tujuan di Sumatera, Jawa, Bali, NTT dan NTB, Kalimantan, Sulawesi, Maluku, dan Papua. Cari kotamu di bawah untuk lihat jenis pengiriman dan estimasi harinya.</p>
  </div>
</section>

<section class="ls-wrap ls-section">
  <label class="ls-search"><span>Cari kota tujuan</span>
    <input type="search" id="ls-q" placeholder="Contoh: Makassar" autocomplete="off" />
  </label>
  <p id="ls-empty" class="ls-note" style="display:none">Kota itu belum ada di daftar. Coba tanya langsung ke tim kami, mungkin masih bisa dilayani.</p>

  <?php foreach ($d['order'] as $k): ?>
  <div class="ls-region" data-region>
    <h2><a href="<?php echo ls_h(ls_page($k)); ?>"><?php echo ls_h($d['name'][$k]); ?></a> <small><?php echo count($d['data'][$k]); ?> kota</small></h2>
    <?php ls_table($d['data'][$k]); ?>
  </div>
  <?php endforeach; ?>

  <?php ls_note_penerus(); ?>
  <?php ls_cta('Halo ALI Cargo, saya mau tanya pengiriman dari Jakarta.'); ?>
</section>

<script>
(function () {
  var q = document.getElementById('ls-q'), empty = document.getElementById('ls-empty');
  if (!q) return;
  q.addEventListener('input', function () {
    var v = q.value.trim().toLowerCase(), shown = 0;
    document.querySelectorAll('[data-region]').forEach(function (reg) {
      var any = 0;
      reg.querySelectorAll('tbody tr').forEach(function (tr) {
        var ok = !v || tr.getAttribute('data-q').indexOf(v) > -1;
        tr.style.display = ok ? '' : 'none'; if (ok) any++;
      });
      reg.style.display = any ? '' : 'none'; shown += any;
    });
    empty.style.display = shown ? 'none' : '';
  });
})();
</script>
<?php ls_foot();
