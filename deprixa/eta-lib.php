<?php
// Estimasi waktu pengiriman per kota tujuan. Hanya lama pengiriman (tanpa harga).
//   Reguler : dari kolom "Waktu (hari)" di file PL (Tarif Reguler).
//   Express : dari daftar harga Express (kolom "Waktu").
// Format: nama kota, hari minimal, hari maksimal, jenis armada, wilayah laporan (JAWA / SUMATERA / TIMUR).
// Ubah baris di bawah kalau PL berubah.

function ali_eta_rows($table = 'reg') {
    static $reg = array(
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
        array('BANDUNG', 1, 2, 'Darat', 'JAWA'),
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
    static $exp = array(
        array('BANDA ACEH', 6, 7, 'Darat', 'SUMATERA'),
        array('MEDAN', 3, 4, 'Darat', 'SUMATERA'),
        array('SIANTAR', 3, 4, 'Darat', 'SUMATERA'),
        array('SIBOLGA', 3, 4, 'Darat', 'SUMATERA'),
        array('PEKANBARU', 3, 4, 'Darat', 'SUMATERA'),
        array('DUMAI', 4, 5, 'Darat', 'SUMATERA'),
        array('PALEMBANG', 2, 3, 'Darat', 'SUMATERA'),
        array('LAHAT', 3, 4, 'Darat', 'SUMATERA'),
        array('PADANG', 3, 4, 'Darat', 'SUMATERA'),
        array('BUKIT TINGGI', 3, 4, 'Darat', 'SUMATERA'),
        array('PAYAHKUMBUH', 2, 3, 'Darat', 'SUMATERA'),
        array('BANGKA', 3, 4, 'Darat', 'SUMATERA'),
        array('JAMBI', 2, 3, 'Darat', 'SUMATERA'),
        array('BATAM', 6, 7, 'Darat', 'SUMATERA'),
        array('BINTAN', 6, 7, 'Darat', 'SUMATERA'),
        array('SURABAYA', 1, 1, 'Kereta Exp', 'JAWA'),
        array('KUPANG', 11, 12, 'Kapal Exp', 'TIMUR'),
        array('LABUAN BAJO', 11, 12, 'Kapal Exp', 'TIMUR'),
        array('RUTENG', 14, 16, 'Kapal Exp', 'TIMUR'),
        array('ENDE', 11, 12, 'Kapal Exp', 'TIMUR'),
        array('MAUMERE', 11, 12, 'Kapal Exp', 'TIMUR'),
        array('ATAMBUA', 14, 16, 'Kapal Exp', 'TIMUR'),
        array('ALOR', 14, 16, 'Kapal Exp', 'TIMUR'),
        array('MATARAM', 7, 8, 'Kapal Exp', 'TIMUR'),
        array('SUMBAWA', 7, 8, 'Kapal Exp', 'TIMUR'),
        array('LOMBOK TIMUR', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('LOMBOK TENGAH', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('LOMBOK UTARA', 9, 10, 'Kapal Exp', 'TIMUR'),
        array('LOMBOK BARAT', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('BIMA', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('DOMPU', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('BANJARMASIN', 5, 6, 'Kapal Exp', 'TIMUR'),
        array('BANJARBARU', 6, 7, 'Kapal Exp', 'TIMUR'),
        array('AMUNTAI', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('KOTA BARU', 9, 10, 'Kapal Exp', 'TIMUR'),
        array('BALIKPAPAN', 5, 6, 'Kapal Exp', 'TIMUR'),
        array('SAMARINDA', 6, 8, 'Kapal Exp', 'TIMUR'),
        array('BONTANG', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('BERAU', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('BULUNGAN', 12, 14, 'Kapal Exp', 'TIMUR'),
        array('TARAKAN', 16, 18, 'Kapal Exp', 'TIMUR'),
        array('NUNUKAN', 20, 22, 'Kapal Exp', 'TIMUR'),
        array('PONTIANAK', 5, 6, 'Kapal Exp', 'TIMUR'),
        array('KETAPANG', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('SINGKAWANG', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('SAMBAS', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('SINTANG', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('PINOH', 12, 14, 'Kapal Exp', 'TIMUR'),
        array('PALANGKARAYA', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('PANGKALAN BUN', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('SAMPIT', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('KUALA KAPUAS', 9, 10, 'Kapal Exp', 'TIMUR'),
        array('MAKASAR', 4, 5, 'Kapal Exp', 'TIMUR'),
        array('MAROS', 6, 7, 'Kapal Exp', 'TIMUR'),
        array('GOWA', 7, 8, 'Kapal Exp', 'TIMUR'),
        array('PALOPO', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('BULUKUMBA', 7, 8, 'Kapal Exp', 'TIMUR'),
        array('PARE-PARE', 8, 9, 'Kapal Exp', 'TIMUR'),
        array('BONE', 9, 10, 'Kapal Exp', 'TIMUR'),
        array('SIDRAP', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('PINRANG', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('TANAH TORAJA', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('POLEWALI MANDAR', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('MANGKUTANA', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('MAMUJU', 10, 13, 'Kapal Exp', 'TIMUR'),
        array('PALU', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('PARIGI/MOUNTONG', 12, 14, 'Kapal Exp', 'TIMUR'),
        array('POSO', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('LUWUK BANGGAI', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('TOLI TOLI', 11, 12, 'Kapal Exp', 'TIMUR'),
        array('MANADO', 14, 16, 'Kapal Exp', 'TIMUR'),
        array('GORONTALO', 12, 13, 'Kapal Exp', 'TIMUR'),
        array('BITUNG', 15, 17, 'Kapal Exp', 'TIMUR'),
        array('MINAHASA', 17, 19, 'Kapal Exp', 'TIMUR'),
        array('KENDARI', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('KOLAKA', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('KONAWE', 14, 15, 'Kapal Exp', 'TIMUR'),
        array('BAU BAU', 14, 15, 'Kapal Exp', 'TIMUR'),
        array('BUTON', 20, 22, 'Kapal Exp', 'TIMUR'),
        array('WAKATOBI', 20, 22, 'Kapal Exp', 'TIMUR'),
        array('AMBON', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('TERNATE', 10, 11, 'Kapal Exp
Kapal Exp', 'TIMUR'),
        array('TUAL', 12, 14, 'Kapal Exp
Kapal Exp', 'TIMUR'),
        array('JAYAPURA', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('ABEPURA', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('SORONG', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('MANOKWARI', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('FAKFAK', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('NABIRE', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('BIAK', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('SERUI', 10, 12, 'Kapal Exp', 'TIMUR'),
        array('TIMIKA', 15, 17, 'Kapal Exp', 'TIMUR'),
        array('MERAUKE', 18, 20, 'Kapal Exp', 'TIMUR'),
    );
    return $table === 'exp' ? $exp : $reg;
}

// nama lain yang umum dipakai -> nama di tabel
function ali_eta_alias() {
    return array(
        'MAKASSAR' => 'MAKASAR', 'UJUNG PANDANG' => 'MAKASAR', 'TANJUNG PINANG' => 'TG PINANG', 'TANJUNGPINANG' => 'TG PINANG',
        'TANJUNG BALAI KARIMUN' => 'TG BALAI KARIMUN', 'KARIMUN' => 'TG BALAI KARIMUN', 'TANJUNG PANDAN' => 'BELITUNG',
        'SURAKARTA' => 'SOLO', 'JOGJA' => 'YOGYAKARTA', 'YOGYA' => 'YOGYAKARTA', 'LAMPUNG' => 'BANDAR LAMPUNG',
        'TASIKMALAYA' => 'TASIK', 'GARUT' => 'TASIK', 'WANGON' => 'BANYUMAS', 'PEMATANGSIANTAR' => 'PEMATANG SIANTAR',
        'PEMATANG SIANTAR' => 'SIANTAR', 'BUKITTINGGI' => 'BUKIT TINGGI', 'PAYAKUMBUH' => 'PAYAHKUMBUH', 'PAREPARE' => 'PARE-PARE',
        'BAUBAU' => 'BAU BAU', 'TOLITOLI' => 'TOLI TOLI', 'KOTABARU' => 'KOTA BARU', 'PANGKAL PINANG' => 'BANGKA', 'PANGKALPINANG' => 'BANGKA',
    );
}

function ali_eta_squash($s) {
    $s = strtoupper(trim((string)$s));
    $s = preg_replace('/\b(KOTA|KAB|KABUPATEN|CITY|REGENCY)\b\.?/', ' ', $s);
    $s = preg_replace('/\bTG\b\.?|\bTANJUNG\b/', 'TG', $s);
    return preg_replace('/[^A-Z0-9]/', '', $s);
}

// Indeks per tabel: kunci (tanpa spasi/tanda baca) -> [min, max, jenis, nama, wilayah]
function ali_eta_index($table = 'reg') {
    static $cache = array();
    if (isset($cache[$table])) { return $cache[$table]; }
    $idx = array();
    foreach (ali_eta_rows($table) as $r) {
        $variants = preg_split('/[\/()]+/', $r[0]);
        $variants[] = $r[0];
        foreach ($variants as $v) {
            $k = ali_eta_squash($v);
            if ($k !== '') { $idx[$k] = array($r[1], $r[2], $r[3], $r[0], $r[4]); }
        }
    }
    foreach (ali_eta_alias() as $from => $to) {
        $kt = ali_eta_squash($to); $kf = ali_eta_squash($from);
        if ($to !== '' && isset($idx[$kt]) && !isset($idx[$kf])) { $idx[$kf] = $idx[$kt]; }
    }
    return $cache[$table] = $idx;
}

function ali_eta_is_express($mode) {
    return stripos((string)$mode, 'express') !== false || preg_match('/\bexp\b/i', (string)$mode) === 1;
}

function ali_eta_find($city, $table) {
    $n = ali_eta_squash($city);
    if (strlen($n) < 3) { return null; }
    $idx = ali_eta_index($table);
    if (isset($idx[$n])) { return $idx[$n]; }
    $best = null; $bestLen = 0;
    foreach ($idx as $k => $v) {
        if (strlen($k) >= 5 && strlen($k) > $bestLen && strpos($n, $k) !== false) { $best = $v; $bestLen = strlen($k); }
    }
    return $best;
}

// Cari estimasi untuk teks kota. Mode Express memakai tabel Express (kalau kota tidak ada, jatuh ke Reguler).
// Hasil: array(min, max, jenis, nama, wilayah) atau null.
function ali_eta_lookup($city, $mode = '') {
    if (ali_eta_is_express($mode)) {
        $r = ali_eta_find($city, 'exp');
        if ($r) { return $r; }
    }
    $r = ali_eta_find($city, 'reg');
    if ($r) { return $r; }
    return ali_eta_find($city, 'exp'); // kota yang hanya ada di daftar Express
}

// ETA = tanggal keberangkatan + hari maksimal. Format Y-m-d, atau null.
function ali_eta_compute($departYmd, $city, $mode = '') {
    $d = DateTime::createFromFormat('Y-m-d', (string)$departYmd);
    if (!$d || $d->format('Y-m-d') !== (string)$departYmd) { return null; }
    $e = ali_eta_lookup($city, $mode);
    if (!$e) { return null; }
    return $d->modify('+' . (int)$e[1] . ' days')->format('Y-m-d');
}

// Untuk JavaScript: {reg: {kunci: [min, max, jenis]}, exp: {...}}
function ali_eta_json() {
    $out = array('reg' => array(), 'exp' => array());
    foreach (array('reg', 'exp') as $t) {
        foreach (ali_eta_index($t) as $k => $v) { $out[$t][$k] = array($v[0], $v[1], $v[2]); }
    }
    return json_encode($out);
}

// Skrip form: isi ETA otomatis dari kota tujuan + mode layanan + tanggal keberangkatan.
// Butuh #pickup_time (kota), #schedule (tanggal berangkat), #eta (tanggal tiba), #eta-hint (keterangan); #mode (layanan) opsional.
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
    function find(city, table) {
        var n = squash(city), T = DATA[table];
        if (n.length < 3) { return null; }
        if (T[n]) { return T[n]; }
        var best = null, bl = 0;
        for (var k in T) { if (k.length >= 5 && k.length > bl && n.indexOf(k) !== -1) { best = T[k]; bl = k.length; } }
        return best;
    }
    function isExpress(m) { return /express|\bexp\b/i.test(String(m || '')); }
    function lookup(city, mode) {
        var r = null;
        if (isExpress(mode)) { r = find(city, 'exp'); if (r) { return r; } }
        return find(city, 'reg') || find(city, 'exp');
    }
    function addDays(ymd, d) {
        var p = ymd.split('-'); var dt = new Date(Date.UTC(+p[0], +p[1] - 1, +p[2] + d));
        return dt.toISOString().slice(0, 10);
    }
    var city = document.getElementById('pickup_time'), dep = document.getElementById('schedule'),
        eta = document.getElementById('eta'), hint = document.getElementById('eta-hint'), modeEl = document.getElementById('mode');
    if (!city || !dep || !eta) { return; }
    function modeText() {
        if (!modeEl) { return ''; }
        var o = modeEl.options ? modeEl.options[modeEl.selectedIndex] : null;
        return (o ? o.text + ' ' : '') + modeEl.value;
    }
    function auto() {
        var e = lookup(city.value, modeText()), d = dep.value;
        return { e: e, eta: (e && d) ? addDays(d, e[1]) : '' };
    }
    // ETA ikut terhitung ulang tiap kota / mode / tanggal berangkat berubah, kecuali ETA baru saja diketik sendiri di halaman ini
    var manual = false;
    eta.addEventListener('input', function () { manual = eta.value !== ''; refreshHint(); });
    function refreshHint() {
        var a = auto();
        if (!hint) { return; }
        var ex = isExpress(modeText()) ? 'Express' : 'Reguler';
        if (!a.e) { hint.textContent = city.value ? 'Kota ini belum ada di daftar waktu pengiriman. Isi ETA sendiri kalau perlu.' : 'Isi kota tujuan dan tanggal keberangkatan, ETA terhitung otomatis.'; }
        else if (!dep.value) { hint.textContent = 'Estimasi ' + ex + ': ' + a.e[0] + '-' + a.e[1] + ' hari (' + a.e[2] + '). Isi tanggal keberangkatan untuk menghitung ETA.'; }
        else { hint.textContent = 'Estimasi ' + ex + ': ' + a.e[0] + '-' + a.e[1] + ' hari (' + a.e[2] + '). ETA memakai batas atas.' + (manual ? ' ETA sedang kamu atur manual.' : ''); }
    }
    function recalc() { if (!manual) { eta.value = auto().eta; } refreshHint(); }
    ['input', 'change'].forEach(function (ev) {
        city.addEventListener(ev, recalc); dep.addEventListener(ev, recalc);
        if (modeEl) { modeEl.addEventListener(ev, recalc); }
    });
    refreshHint();
})();
</script>
JS;
}
