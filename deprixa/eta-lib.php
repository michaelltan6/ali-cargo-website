<?php
// Estimasi waktu pengiriman per kota tujuan, diambil dari kolom "Waktu (hari)" di file PL (Tarif Reguler).
// Hanya lama pengiriman; tidak ada harga di file ini. Ubah baris di bawah kalau PL berubah.
// Format: nama kota, hari minimal, hari maksimal, jenis (Darat / Kapal Reg), wilayah laporan (JAWA / SUMATERA / TIMUR).

function ali_eta_rows() {
    static $rows = array(
    array('BANDA ACEH', 10, 12, 'Darat', 'SUMATERA'),
    array('MEDAN', 5, 7, 'Darat', 'SUMATERA'),
    array('PEMATANG SIANTAR', 8, 10, 'Darat', 'SUMATERA'),
    array('BINJAI', 7, 10, 'Darat', 'SUMATERA'),
    array('PEKANBARU', 5, 7, 'Darat', 'SUMATERA'),
    array('DUMAI', 10, 12, 'Darat', 'SUMATERA'),
    array('PADANG', 5, 7, 'Darat', 'SUMATERA'),
    array('JAMBI', 4, 5, 'Darat', 'SUMATERA'),
    array('BENGKULU', 5, 7, 'Darat', 'SUMATERA'),
    array('PALEMBANG', 5, 7, 'Darat', 'SUMATERA'),
    array('BATURAJA', 10, 12, 'Darat', 'SUMATERA'),
    array('BANDAR LAMPUNG', 2, 3, 'Darat', 'SUMATERA'),
    array('BATAM', 14, 16, 'Kapal Reg', 'SUMATERA'),
    array('TG. BALAI KARIMUN', 14, 16, 'Kapal Reg', 'SUMATERA'),
    array('TG. PINANG', 14, 16, 'Kapal Reg', 'SUMATERA'),
    array('BELITUNG', 3, 5, 'Kapal Reg', 'SUMATERA'),
    array('PANGKAL PINANG', 3, 5, 'Kapal Reg', 'SUMATERA'),
    array('SURABAYA', 2, 3, 'Darat', 'JAWA'),
    array('BANYUWANGI', 4, 5, 'Darat', 'JAWA'),
    array('BLITAR', 4, 5, 'Darat', 'JAWA'),
    array('BOJONEGORO', 4, 5, 'Darat', 'JAWA'),
    array('BONDOWOSO', 4, 5, 'Darat', 'JAWA'),
    array('PROBOLINGGO', 4, 5, 'Darat', 'JAWA'),
    array('PONOROGO', 4, 5, 'Darat', 'JAWA'),
    array('PACITAN', 4, 6, 'Darat', 'JAWA'),
    array('PASURUAN', 4, 5, 'Darat', 'JAWA'),
    array('PAMEKASAN', 4, 5, 'Darat', 'JAWA'),
    array('SAMPANG', 4, 5, 'Darat', 'JAWA'),
    array('SIDOARJO', 2, 3, 'Darat', 'JAWA'),
    array('SITUBONDO', 4, 5, 'Darat', 'JAWA'),
    array('SUMENEP', 4, 5, 'Darat', 'JAWA'),
    array('GRESIK', 3, 4, 'Darat', 'JAWA'),
    array('JOMBANG', 3, 4, 'Darat', 'JAWA'),
    array('JEMBER', 4, 5, 'Darat', 'JAWA'),
    array('MALANG', 3, 4, 'Darat', 'JAWA'),
    array('MOJOKERTO', 4, 5, 'Darat', 'JAWA'),
    array('MADIUN', 4, 5, 'Darat', 'JAWA'),
    array('KEDIRI', 4, 5, 'Darat', 'JAWA'),
    array('TUBAN', 4, 5, 'Darat', 'JAWA'),
    array('TUREN', 5, 6, 'Darat', 'JAWA'),
    array('TULUNG AGUNG', 4, 5, 'Darat', 'JAWA'),
    array('BANDUNG', 2, 3, 'Darat', 'JAWA'),
    array('CIREBON', 1, 2, 'Darat', 'JAWA'),
    array('CIMAHI', 2, 3, 'Darat', 'JAWA'),
    array('PADALARANG', 2, 3, 'Darat', 'JAWA'),
    array('SUKABUMI', 2, 3, 'Darat', 'JAWA'),
    array('SUMEDANG', 2, 3, 'Darat', 'JAWA'),
    array('TASIK (GARUT)', 2, 3, 'Darat', 'JAWA'),
    array('CIANJUR', 2, 3, 'Darat', 'JAWA'),
    array('CIPANAS', 2, 3, 'Darat', 'JAWA'),
    array('SUBANG', 2, 3, 'Darat', 'JAWA'),
    array('PURWAKARTA', 3, 4, 'Darat', 'JAWA'),
    array('LEBAK', 3, 4, 'Darat', 'JAWA'),
    array('SEMARANG', 1, 2, 'Darat', 'JAWA'),
    array('SOLO', 2, 3, 'Darat', 'JAWA'),
    array('SALATIGA', 2, 3, 'Darat', 'JAWA'),
    array('YOGYAKARTA', 2, 3, 'Darat', 'JAWA'),
    array('KLATEN', 2, 3, 'Darat', 'JAWA'),
    array('KENDAL', 3, 4, 'Darat', 'JAWA'),
    array('MAGELANG', 3, 4, 'Darat', 'JAWA'),
    array('PURWOKERTO', 3, 4, 'Darat', 'JAWA'),
    array('TEGAL', 3, 4, 'Darat', 'JAWA'),
    array('CILACAP', 4, 5, 'Darat', 'JAWA'),
    array('BANYUMAS/WANGON', 4, 5, 'Darat', 'JAWA'),
    array('KUDUS', 3, 4, 'Darat', 'JAWA'),
    array('DEMAK', 3, 4, 'Darat', 'JAWA'),
    array('PATI', 3, 4, 'Darat', 'JAWA'),
    array('KEBUMEN', 3, 4, 'Darat', 'JAWA'),
    array('PEKALONGAN', 3, 4, 'Darat', 'JAWA'),
    array('SLEMAN', 3, 4, 'Darat', 'JAWA'),
    array('BLORA', 3, 4, 'Darat', 'JAWA'),
    array('REMBANG', 3, 4, 'Darat', 'JAWA'),
    array('DENPASAR', 6, 8, 'Darat', 'TIMUR'),
    array('BADUNG', 6, 8, 'Darat', 'TIMUR'),
    array('BULELENG', 7, 9, 'Darat', 'TIMUR'),
    array('BANGLI', 7, 9, 'Darat', 'TIMUR'),
    array('GIANYAR', 7, 9, 'Darat', 'TIMUR'),
    array('JIMBARAN', 7, 9, 'Darat', 'TIMUR'),
    array('NEGARA', 7, 9, 'Darat', 'TIMUR'),
    array('KARANG ASEM', 7, 9, 'Darat', 'TIMUR'),
    array('KLUNGKUNG', 7, 9, 'Darat', 'TIMUR'),
    array('SINGARAJA', 7, 9, 'Darat', 'TIMUR'),
    array('KUPANG', 14, 16, 'Kapal Reg', 'TIMUR'),
    array('LOMBOK', 8, 10, 'Darat', 'TIMUR'),
    array('MATARAM', 8, 10, 'Kapal Reg', 'TIMUR'),
    array('SUMBAWA', 8, 10, 'Kapal Reg', 'TIMUR'),
    array('ENDE', 14, 16, 'Kapal Reg', 'TIMUR'),
    array('MAUMERE', 18, 20, 'Kapal Reg', 'TIMUR'),
    array('BIMA', 12, 14, 'Kapal Reg', 'TIMUR'),
    array('BANJARMASIN', 10, 12, 'Kapal Reg', 'TIMUR'),
    array('BALIKPAPAN', 10, 12, 'Kapal Reg', 'TIMUR'),
    array('SAMARINDA', 10, 12, 'Kapal Reg', 'TIMUR'),
    array('PONTIANAK', 5, 6, 'Kapal Reg', 'TIMUR'),
    array('KETAPANG', 8, 10, 'Kapal Reg', 'TIMUR'),
    array('SINTANG', 10, 12, 'Kapal Reg', 'TIMUR'),
    array('PINOH', 10, 12, 'Kapal Reg', 'TIMUR'),
    array('PALANGKARAYA', 10, 12, 'Kapal Reg', 'TIMUR'),
    array('PANGKALAN BUN', 14, 16, 'Kapal Reg', 'TIMUR'),
    array('SAMPIT', 8, 10, 'Kapal Reg', 'TIMUR'),
    array('BERAU', 14, 16, 'Kapal Reg', 'TIMUR'),
    array('TARAKAN', 18, 21, 'Kapal Reg', 'TIMUR'),
    array('MAKASAR', 7, 10, 'Kapal Reg', 'TIMUR'),
    array('PALOPO', 10, 15, 'Kapal Reg', 'TIMUR'),
    array('BULUKUMBA', 10, 15, 'Kapal Reg', 'TIMUR'),
    array('PARE-PARE', 10, 15, 'Kapal Reg', 'TIMUR'),
    array('MANADO', 10, 12, 'Kapal Reg', 'TIMUR'),
    array('BITUNG', 17, 19, 'Kapal Reg', 'TIMUR'),
    array('KENDARI', 17, 19, 'Kapal Reg', 'TIMUR'),
    array('MAMUJU', 17, 19, 'Kapal Reg', 'TIMUR'),
    array('PALU', 17, 19, 'Kapal Reg', 'TIMUR'),
    array('POSO', 17, 19, 'Kapal Reg', 'TIMUR'),
    array('LUWUK BANGGAI', 25, 28, 'Kapal Reg', 'TIMUR'),
    array('GORONTALO', 17, 19, 'Kapal Reg', 'TIMUR'),
    array('AMBON', 14, 16, 'Kapal Reg', 'TIMUR'),
    array('TERNATE', 16, 19, 'Kapal Reg', 'TIMUR'),
    array('JAYAPURA', 21, 25, 'Kapal Reg', 'TIMUR'),
    array('ABEPURA', 21, 25, 'Kapal Reg', 'TIMUR'),
    array('MERAUKE', 21, 25, 'Kapal Reg', 'TIMUR'),
    array('TIMIKA', 21, 25, 'Kapal Reg', 'TIMUR'),
    array('SORONG', 21, 25, 'Kapal Reg', 'TIMUR'),
    );
    return $rows;
}

// nama lain yang umum dipakai -> nama di PL
function ali_eta_alias() {
    return array(
    'MAKASSAR' => 'MAKASAR', 'UJUNG PANDANG' => 'MAKASAR', 'TANJUNG PINANG' => 'TG PINANG', 'TANJUNGPINANG' => 'TG PINANG',
    'TANJUNG BALAI KARIMUN' => 'TG BALAI KARIMUN', 'KARIMUN' => 'TG BALAI KARIMUN', 'TANJUNG PANDAN' => 'BELITUNG',
    'SURAKARTA' => 'SOLO', 'JOGJA' => 'YOGYAKARTA', 'YOGYA' => 'YOGYAKARTA', 'LAMPUNG' => 'BANDAR LAMPUNG',
    'TASIKMALAYA' => 'TASIK', 'GARUT' => 'TASIK', 'WANGON' => 'BANYUMAS', 'PEMATANGSIANTAR' => 'PEMATANG SIANTAR',
    );
}

function ali_eta_squash($s) {
    $s = strtoupper(trim((string)$s));
    $s = preg_replace('/\b(KOTA|KAB|KABUPATEN|CITY|REGENCY)\b\.?/', ' ', $s);
    $s = preg_replace('/\bTG\b\.?|\bTANJUNG\b/', 'TG', $s);
    return preg_replace('/[^A-Z0-9]/', '', $s);
}

// Susun indeks: kunci (tanpa spasi/tanda baca) -> [min, max, jenis]
function ali_eta_index() {
    static $idx = null;
    if ($idx !== null) { return $idx; }
    $idx = array();
    foreach (ali_eta_rows() as $r) {
        $variants = preg_split('/[\/()]+/', $r[0]);
        $variants[] = $r[0];
        foreach ($variants as $v) {
            $k = ali_eta_squash($v);
            if ($k !== '') { $idx[$k] = array($r[1], $r[2], $r[3], $r[0], $r[4]); }
        }
    }
    foreach (ali_eta_alias() as $from => $to) {
        $kt = ali_eta_squash($to);
        if ($to !== '' && isset($idx[$kt])) { $idx[ali_eta_squash($from)] = $idx[$kt]; }
    }
    return $idx;
}

// Cari estimasi untuk teks kota. Hasil: array(min, max, jenis, nama PL) atau null kalau tidak ada di PL.
function ali_eta_lookup($city) {
    $n = ali_eta_squash($city);
    if (strlen($n) < 3) { return null; }
    $idx = ali_eta_index();
    if (isset($idx[$n])) { return $idx[$n]; }
    $best = null; $bestLen = 0;
    foreach ($idx as $k => $v) {
        if (strlen($k) >= 5 && strlen($k) > $bestLen && strpos($n, $k) !== false) { $best = $v; $bestLen = strlen($k); }
    }
    return $best;
}

// ETA = tanggal keberangkatan + hari maksimal dari PL (pilihan aman). Format Y-m-d, atau null.
function ali_eta_compute($departYmd, $city) {
    $d = DateTime::createFromFormat('Y-m-d', (string)$departYmd);
    if (!$d || $d->format('Y-m-d') !== (string)$departYmd) { return null; }
    $e = ali_eta_lookup($city);
    if (!$e) { return null; }
    return $d->modify('+' . (int)$e[1] . ' days')->format('Y-m-d');
}

// Untuk dipakai di JavaScript: kunci squash -> [min, max]
function ali_eta_json() {
    $out = array();
    foreach (ali_eta_index() as $k => $v) { $out[$k] = array($v[0], $v[1], $v[2]); }
    return json_encode($out);
}

// Skrip kecil untuk form: mengisi ETA otomatis dari kota tujuan + tanggal keberangkatan.
// Butuh elemen #pickup_time (kota), #schedule (tanggal berangkat), #eta (tanggal tiba), #eta-hint (keterangan).
function ali_eta_script() {
    $json = ali_eta_json();
    return <<<JS
<script>
(function () {
    var DATA = $json;
    function squash(s) {
        s = String(s || '').toUpperCase().replace(/\b(KOTA|KAB|KABUPATEN|CITY|REGENCY)\b\.?/g, ' ').replace(/\bTG\b\.?|\bTANJUNG\b/g, 'TG');
        return s.replace(/[^A-Z0-9]/g, '');
    }
    function lookup(city) {
        var n = squash(city);
        if (n.length < 3) { return null; }
        if (DATA[n]) { return DATA[n]; }
        var best = null, bl = 0;
        for (var k in DATA) { if (k.length >= 5 && k.length > bl && n.indexOf(k) !== -1) { best = DATA[k]; bl = k.length; } }
        return best;
    }
    function addDays(ymd, d) {
        var p = ymd.split('-'); var dt = new Date(Date.UTC(+p[0], +p[1] - 1, +p[2] + d));
        return dt.toISOString().slice(0, 10);
    }
    var city = document.getElementById('pickup_time'), dep = document.getElementById('schedule'),
        eta = document.getElementById('eta'), hint = document.getElementById('eta-hint');
    if (!city || !dep || !eta) { return; }
    function auto() {
        var e = lookup(city.value), d = dep.value;
        return { e: e, eta: (e && d) ? addDays(d, e[1]) : '' };
    }
    var manual = false;
    (function init() { var a = auto(); manual = eta.value !== '' && eta.value !== a.eta; })();
    eta.addEventListener('input', function () { manual = eta.value !== ''; refreshHint(); });
    function refreshHint() {
        var a = auto();
        if (!hint) { return; }
        if (!a.e) { hint.textContent = city.value ? 'Kota ini belum ada di PL. Isi ETA sendiri kalau perlu.' : 'Isi kota tujuan dan tanggal keberangkatan, ETA terhitung otomatis.'; }
        else if (!dep.value) { hint.textContent = 'Estimasi PL: ' + a.e[0] + '-' + a.e[1] + ' hari (' + a.e[2] + '). Isi tanggal keberangkatan untuk menghitung ETA.'; }
        else { hint.textContent = 'Estimasi PL: ' + a.e[0] + '-' + a.e[1] + ' hari (' + a.e[2] + '). ETA memakai batas atas.' + (manual ? ' Sedang diatur manual.' : ''); }
    }
    function recalc() { if (!manual) { eta.value = auto().eta; } refreshHint(); }
    city.addEventListener('input', recalc); city.addEventListener('change', recalc);
    dep.addEventListener('input', recalc); dep.addEventListener('change', recalc);
    refreshHint();
})();
</script>
JS;
}
