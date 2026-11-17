<?php
// Daftar kota untuk isian Kota asal / Kota tujuan. Boleh juga mengetik kota lain.
// Kota yang pernah dipakai di data pengiriman ikut muncul otomatis (ali_city_options).
$ali_city_base = array("Jakarta", "Jakarta Barat", "Jakarta Utara", "Jakarta Timur", "Jakarta Selatan", "Jakarta Pusat", "Tangerang", "Tangerang Selatan", "Serang", "Cilegon", "Bekasi", "Bogor", "Depok", "Cikarang", "Karawang", "Purwakarta", "Subang", "Cirebon", "Bandung", "Cimahi", "Sukabumi", "Cianjur", "Garut", "Tasikmalaya", "Indramayu", "Majalengka", "Kuningan", "Semarang", "Solo", "Surakarta", "Yogyakarta", "Magelang", "Pekalongan", "Tegal", "Brebes", "Purwokerto", "Cilacap", "Kudus", "Jepara", "Pati", "Salatiga", "Klaten", "Boyolali", "Kebumen", "Surabaya", "Sidoarjo", "Gresik", "Malang", "Pasuruan", "Probolinggo", "Mojokerto", "Jombang", "Kediri", "Blitar", "Madiun", "Jember", "Banyuwangi", "Lamongan", "Tuban", "Bojonegoro", "Banda Aceh", "Lhokseumawe", "Medan", "Binjai", "Pematangsiantar", "Tebing Tinggi", "Kisaran", "Sibolga", "Padang", "Bukittinggi", "Pekanbaru", "Dumai", "Batam", "Tanjungpinang", "Jambi", "Palembang", "Lubuklinggau", "Bengkulu", "Bandar Lampung", "Metro Lampung", "Pangkalpinang", "Pontianak", "Singkawang", "Palangkaraya", "Sampit", "Banjarmasin", "Banjarbaru", "Balikpapan", "Samarinda", "Bontang", "Tarakan", "Denpasar", "Mataram", "Kupang", "Labuan Bajo", "Makassar", "Parepare", "Palopo", "Manado", "Palu", "Kendari", "Gorontalo", "Mamuju", "Ambon", "Ternate", "Sorong", "Manokwari", "Jayapura", "Merauke", "Timika", "Biak");

function ali_city_options() {
    global $ali_city_base;
    $set = array();
    foreach ($ali_city_base as $c) { $set[mb_strtolower($c)] = $c; }
    try {
        foreach (array('pickup_time', 'origin_city') as $col) {
            try {
                $stmt = dbQuery("SELECT DISTINCT $col AS k FROM courier WHERE $col IS NOT NULL AND $col <> ''");
            } catch (Exception $e) { continue; }
            while ($r = dbFetchAssoc($stmt)) {
                $v = trim(html_entity_decode((string)$r['k'], ENT_QUOTES, 'UTF-8'));
                if ($v !== '' && mb_strlen($v) <= 60 && !isset($set[mb_strtolower($v)])) { $set[mb_strtolower($v)] = $v; }
            }
        }
    } catch (Exception $e) {}
    $list = array_values($set);
    sort($list, SORT_NATURAL | SORT_FLAG_CASE);
    return $list;
}

// <datalist> siap pakai; hubungkan dengan <input list="kota-list">.
function ali_city_datalist($id = 'kota-list') {
    $h = '<datalist id="' . htmlspecialchars($id) . '">';
    foreach (ali_city_options() as $c) { $h .= '<option value="' . htmlspecialchars($c) . '">'; }
    return $h . '</datalist>';
}
