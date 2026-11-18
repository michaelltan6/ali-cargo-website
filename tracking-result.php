<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
require_once('deprixa/database.php');

// Redirect if no consignment number is provided
if (empty($_POST['Consignment'])) {
    header("Location: tracking");
    exit;
}

$cons = isset($_POST['Consignment']) ? trim($_POST['Consignment']) : '';

// Proceed with query if $cons is set
if ($cons) {
    $sql = "SELECT c.*, ci.item_name, ci.quantity, ci.length, ci.width, ci.height, ci.weight AS item_weight, ci.volume 
            FROM courier c 
            LEFT JOIN courier_items ci ON c.cid = ci.courier_id 
            WHERE REPLACE(UPPER(c.cons_no), ' ', '') = REPLACE(UPPER(:cons), ' ', '')";
    $result = dbQuery($sql, [':cons' => $cons]);
    $no = dbNumRows($result);

    if ($no > 0) {
        // Fetch all rows
        if (function_exists('dbFetchAll')) {
            $data = dbFetchAll($result);
        } else {
            $data = [];
            while ($row = dbFetchAssoc($result)) {
                $data[] = $row;
            }
        }
        $courier_data = $data[0];
        $items = array_filter($data, fn($r) => !empty($r['item_name']));

        // Extract relevant fields
        extract($courier_data, EXTR_SKIP); // Skip undefined indices to avoid notices

        // Calculate totals
        $total_quantity = 0;
        $total_weight_kg = 0;
        $total_volume_m3 = 0;
        foreach ($items as $item) {
            $quantity = is_numeric($item['quantity']) ? (float)$item['quantity'] : 0;
            $item_weight = is_numeric($item['item_weight']) ? (float)$item['item_weight'] : 0;
            $volume_m3 = is_numeric($item['volume']) ? (float)$item['volume'] : 0;
            $total_quantity += $quantity;
            $total_weight_kg += $quantity * $item_weight;
            $total_volume_m3 += $quantity * $volume_m3;
        }

        // Tampilan: status mengikuti yang diatur admin
        $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $fmt_date = function ($v, $withDay = false) use ($bulan, $hari) {
            $v = trim((string)$v);
            if ($v === '' || strpos($v, '0000') === 0) { return ''; }
            $t = strtotime($v);
            if (!$t) { return ''; }
            return ($withDay ? $hari[(int)date('w', $t)] . ', ' : '') . (int)date('j', $t) . ' ' . $bulan[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
        };
        $status_raw = trim((string)($status ?? ''));
        $status_key = strtolower(str_replace(['-', '_'], ' ', $status_raw));
        if ($status_key === 'delivered') { $status_label = 'Delivered'; $status_class = 'done'; $step = 3; }
        elseif ($status_key === 'on hold') { $status_label = 'On Hold'; $status_class = 'hold'; $step = 2; }
        elseif ($status_key === 'in transit' || $status_key === 'on delivery') { $status_label = 'On Delivery'; $status_class = 'go'; $step = 2; }
        else { $status_label = $status_raw !== '' ? $status_raw : '-'; $status_class = 'go'; $step = 1; }
        $pay_term = (string)($payment_term ?? '');
        if (in_array($pay_term, ['7', '14', '30'], true)) { $pay_label = 'Credit'; }
        elseif ($pay_term === 'cash') { $pay_label = 'Cash'; }
        else { $pay_label = (($book_mode ?? '') === 'ToPay') ? 'Credit' : 'Cash'; }
        $eta_text = $fmt_date($eta ?? '', true);
        $etd_text = $fmt_date($etd ?? '');
        $deliv_text = ($step === 3) ? $fmt_date($delivered_date ?? '', true) : '';
        $recv_date = '';
        foreach (['collection_date', 'pick_date'] as $k) { if (trim((string)($courier_data[$k] ?? '')) !== '' && strpos((string)$courier_data[$k], '0000') !== 0) { $recv_date = substr((string)$courier_data[$k], 0, 10); break; } }
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $origin = trim((string)($origin_city ?? ''));
        $dest = trim((string)($pickup_time ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />	    
    <title>Tracking Result | ALI Cargo</title>
    <meta name="description" content="ALI Cargo - Tracking pengiriman"/>
    <meta name="keywords" content="" />
    <meta name="author" content="">	
    <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, follow" />
    <link rel="icon" type="image/png" href="logoAli.png" />
    <meta name="robots" content="noindex, follow" />
    <link rel="stylesheet" href="deprixa/asset1/css/font-awesome.min.css" type="text/css" media="screen">  
    <link href="deprixa_components/content/cssefe4.css" rel="stylesheet"/>
    <link rel="stylesheet" href="deprixa/css/tracking.css" type="text/css" />   
    <link href="deprixa_components/styles/track-order.css" rel="stylesheet" />
    <link href="deprixa/css/style.css" rel="stylesheet" media="all">
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,600,700,800' rel='stylesheet' type='text/css'>
    <style>
        .header-wrap { position: relative; height: 100px; overflow: hidden; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.45); z-index: 10; }
        .white-part, .red-part { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .white-part { background-color: white; z-index: 1; }
        .red-part { background-color: #ff0000; clip-path: polygon(55% 0, 100% 0, 100% 100%, 45% 100%); z-index: 2; }
        .header-content { position: relative; z-index: 3; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; height: 100%; }
        .logo-link { display: flex; align-items: center; text-decoration: none !important; margin-left: 30px; }
        .logo-image { max-height: 70px; margin-right: 15px; }
        .company-name { font-size: 1.8rem; font-weight: 800; color: #222; line-height: 1.1; }
        .company-name span { display: block; }
        .menu-nav { display: flex; gap: 20px; margin-right: 40px; }
        .menu-nav a { color: #fff; font-weight: 700; font-size: 1.4rem; text-decoration: none; border-right: 1px solid rgba(255, 255, 255, 0.4); padding-right: 12px; }
        .menu-nav a:last-child { border-right: none; }
        .menu-nav a:hover { color: #ffdcdc; }
        @media (max-width: 768px) { .header-content { flex-direction: column; padding: 15px; text-align: center; } .logo-link { margin: 0; flex-direction: column; } .menu-nav { flex-direction: column; gap: 8px; margin-right: 0; } .company-name { font-size: 1.6rem; text-align: center; } .logo-image { max-height: 60px; } }

        .Finished { background: #363C56; }
        .Delayed { background: #F76063; }
        .On-Hold { background: #4ECCDB; }
        .Landed { background: #FF8A4B; }
        .label { padding: 6px; }
        .In-Transit { background: #00D96D; }
        .Delivered { background: #FFBF00; }

        .btn-system { background-color: #333; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; text-decoration: none; font-weight: 700; font-size: 1.4rem; transition: background-color 0.3s ease; }
        .btn-system:hover { background-color: #444; }

        table, th, td { font-size: 1.2rem; color: #333; }
        h2 { font-size: 1.6rem; }
        h3 { font-size: 1.4rem; }

        .status-booking { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        @media (max-width: 768px) { .status-booking { flex-direction: column; gap: 10px; align-items: flex-start; } }

        .travel-history { margin-bottom: 30px; }

        .iframe-container {
            position: relative;
            overflow: hidden;
            padding-top: 75%;
            margin: 20px 0;
            max-width: 1200px;
        }
        .iframe-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .progress-container {
            position: relative;
            width: 100%;
            height: 30px;
            margin-top: 45px;
        }
        .progress {
            height: 100%;
            background-color: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        .progress-bar {
            height: 100%;
            background-color: #00D96D;
            transition: width 1s ease-in-out;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }
        .progress-text {
            color: white;
            margin-right: 10px;
            font-weight: bold;
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
        }
        .cartoon-box {
            position: absolute;
            height: 70px;
            width: auto;
            top: -70px;
            transform: translateX(-50%);
            transition: left 1s ease-in-out;
            z-index: 2;
        }

        footer, footer p, footer h3, .footer-text { font-size: 1.2rem !important; color: #333; }
        footer a, .footer-links a, .whatsapp-btn, #back-top { font-size: 1rem !important; color: #333; }
        .whatsapp-btn, #back-top { background-color: #333; color: #fff; padding: 6px 12px; border-radius: 5px; text-decoration: none; transition: background-color 0.3s ease; }
        .whatsapp-btn:hover, #back-top:hover { background-color: #444; }

        /* Halaman tracking - ringkas */
        .trk-wrap { max-width: 1040px; margin: 0 auto; padding: 18px 12px 40px; font-family: "Open Sans", Arial, sans-serif; color: #263238; }
        .trk-wrap * { box-sizing: border-box; }
        .trk-card { margin-left: 0 !important; margin-right: 0 !important; width: auto !important; float: none !important; background: #fff; border: 1px solid #e3e7ea; border-radius: 10px; padding: 16px 18px; margin-bottom: 14px; }
        .trk-card h3 { text-align: left !important; font-size: 1.3rem; margin: 0 0 12px; font-weight: 700; color: #263238; text-transform: uppercase; letter-spacing: .04em; }
        .trk-top { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 14px; width: 100%; }
        .trk-top > .trk-card { flex: 1 1 340px; margin-bottom: 0; }
        .trk-no { font-size: 2.6rem; font-weight: 800; color: #e53935; line-height: 1.1; margin: 4px 0 10px; }
        .trk-tags { display: flex; gap: 8px; flex-wrap: wrap; }
        .trk-pill { display: inline-block; padding: 5px 12px; border-radius: 999px; font-size: 1.2rem; font-weight: 700; color: #fff; }
        .trk-pill.go { background: #00a65a; } .trk-pill.hold { background: #f39c12; } .trk-pill.done { background: #1565c0; }
        .trk-pill.pay { background: #eef1f3; color: #37474f; }
        .trk-lbl { font-size: 1.1rem; text-transform: uppercase; letter-spacing: .06em; color: #78909c; font-weight: 700; }
        .trk-eta { background: #fff8e1; border-color: #ffe08a; text-align: center; }
        .trk-eta .big { font-size: 2.1rem; font-weight: 800; color: #263238; margin: 6px 0 4px; line-height: 1.2; }
        .trk-eta .sub { font-size: 1.2rem; color: #607d8b; }
        .trk-steps { display: flex; align-items: center; margin: 4px 0 0; }
        .trk-step { flex: 1; text-align: center; position: relative; font-size: 1.15rem; color: #90a4ae; }
        .trk-step:before { content: ""; display: block; width: 14px; height: 14px; border-radius: 50%; background: #cfd8dc; margin: 0 auto 6px; position: relative; z-index: 1; }
        .trk-step:after { content: ""; position: absolute; top: 6px; left: 50%; width: 100%; height: 2px; background: #cfd8dc; }
        .trk-step:last-child:after { display: none; }
        .trk-step.on { color: #263238; font-weight: 700; }
        .trk-step.on:before { background: #00a65a; }
        .trk-step.on:after { background: #00a65a; }
        .trk-step.cur:before { box-shadow: 0 0 0 4px rgba(0,166,90,.2); }
        .trk-step.hold:before { background: #f39c12; box-shadow: 0 0 0 4px rgba(243,156,18,.25); }
        .trk-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px 18px; }
        .trk-info b { display: block; font-size: 1.3rem; font-weight: 600; margin-top: 2px; word-break: break-word; }
        .trk-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; padding-top: 14px; border-top: 1px dashed #dfe4e7; }
        .trk-parties b { display: block; font-size: 1.4rem; margin-top: 2px; }
        .trk-note { margin-top: 12px; padding: 8px 12px; background: #f5f7f8; border-radius: 6px; font-size: 1.2rem; color: #455a64; }
        .trk-table { width: 100%; border-collapse: collapse; }
        .trk-table th { text-align: left; font-size: 1.05rem; text-transform: uppercase; letter-spacing: .05em; color: #78909c; font-weight: 700; padding: 6px 8px; border-bottom: 2px solid #e3e7ea; }
        .trk-table td { padding: 8px; border-bottom: 1px solid #eef1f3; font-size: 1.25rem; vertical-align: top; }
        .trk-table .num { text-align: right; white-space: nowrap; }
        .trk-table tfoot td { font-weight: 700; background: #f5f7f8; border-bottom: 0; }
        .trk-scroll { overflow-x: auto; }
        @media (max-width: 760px) {
            .trk-top, .trk-parties { grid-template-columns: 1fr; }
            .trk-info { grid-template-columns: 1fr 1fr; }
            .trk-no { font-size: 2.2rem; } .trk-eta .big { font-size: 2rem; }
        }
    </style>
  <link rel="stylesheet" href="deprixa_components/styles/ali-site.css?v=<?php echo @filemtime(__DIR__ . '/deprixa_components/styles/ali-site.css'); ?>" />
</head>

<body>
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
                <a href="gallery.php">Gallery</a>
                <a href="contact-us">Hubungi Kami</a>
                <a href="login.php">LOGIN</a>
            </nav>
        </div>
    </header>

    <div class="slide"></div>
    <main class="slide">
        <div class="fw">
            <section class="title">
                <header>
                    <h1><img src="deprixa_components/images/global/tracking-search.png" />Shipment Tracking</h1>					                   
                </header>
                <div class="media-left"></div>
            </section>
        </div>  

        <div class="trk-wrap">
            <div class="trk-top">
                <section class="trk-card">
                    <div class="trk-lbl">Nomor tracking</div>
                    <div class="trk-no"><?php echo $e($cons_no); ?></div>
                    <div class="trk-tags">
                        <span class="trk-pill <?php echo $status_class; ?>"><?php echo $e($status_label); ?></span>
                        <span class="trk-pill pay">Payment Terms: <?php echo $e($pay_label); ?></span>
                    </div>
                    <div class="trk-steps" style="margin-top:18px">
                        <div class="trk-step on<?php echo $step === 1 ? ' cur' : ''; ?>">Barang diterima</div>
                        <div class="trk-step<?php echo $step >= 2 ? ' on' : ''; ?><?php echo $step === 2 ? ($status_class === 'hold' ? ' hold' : ' cur') : ''; ?>"><?php echo $status_class === 'hold' ? 'On Hold' : 'On Delivery'; ?></div>
                        <div class="trk-step<?php echo $step >= 3 ? ' on cur' : ''; ?>">Delivered</div>
                    </div>
                </section>
                <section class="trk-card trk-eta">
                    <?php if ($step === 3): ?>
                        <div class="trk-lbl">Pengiriman selesai</div>
                        <div class="big"><?php echo $deliv_text !== '' ? $e($deliv_text) : 'Sudah diterima'; ?></div>
                        <div class="sub"><?php echo $eta_text !== '' ? 'Estimasi awal: ' . $e($eta_text) : 'Terima kasih telah menggunakan ALI Cargo'; ?></div>
                    <?php else: ?>
                        <div class="trk-lbl">Estimasi tiba (ETA)</div>
                        <div class="big"><?php echo $eta_text !== '' ? $e($eta_text) : 'Belum ditentukan'; ?></div>
                        <div class="sub"><?php echo $eta_text !== '' ? 'Estimasi dapat berubah mengikuti kondisi di lapangan' : 'Jadwal akan diperbarui oleh tim kami'; ?></div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="trk-card">
                <h3>Additional Information</h3>
                <div class="trk-info">
                    <div><span class="trk-lbl">Origin</span><b><?php echo $origin !== '' ? $e($origin) : '-'; ?></b></div>
                    <div><span class="trk-lbl">Destination</span><b><?php echo $dest !== '' ? $e($dest) : '-'; ?></b></div>
                    <div><span class="trk-lbl">Services</span><b><?php echo !empty($mode) ? $e($mode) : '-'; ?></b></div>
                    <div><span class="trk-lbl">Tanggal diterima</span><b><?php echo $recv_date !== '' ? $e($fmt_date($recv_date)) : '-'; ?></b></div>
                    <?php if ($etd_text !== ''): ?><div><span class="trk-lbl">Tanggal berangkat</span><b><?php echo $e($etd_text); ?></b></div><?php endif; ?>
                </div>
                <div class="trk-parties">
                    <div><span class="trk-lbl">Shipper</span><b><?php echo $e($ship_name ?? '-'); ?></b></div>
                    <div><span class="trk-lbl">Consignee</span><b><?php echo $e($rev_name ?? '-'); ?></b></div>
                </div>
                <?php if (trim((string)($comments ?? '')) !== ''): ?>
                    <div class="trk-note"><b>Catatan:</b> <?php echo $e($comments); ?></div>
                <?php endif; ?>
            </section>

            <section class="trk-card">
                <h3>Cargo Details</h3>
                <div class="trk-scroll">
                <table class="trk-table">
                    <thead><tr><th>Nama barang</th><th class="num">Qty</th><th class="num">P (cm)</th><th class="num">L (cm)</th><th class="num">T (cm)</th><th class="num">Berat (kg)</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item):
                        $n = function ($v, $d) { return is_numeric($v) ? number_format((float)$v, $d, '.', '') : '-'; };
                        $kg = function ($v) { if (!is_numeric($v)) { return '-'; } $t = number_format((float)$v, 3, ',', '.'); return rtrim(rtrim($t, '0'), ','); }; ?>
                        <tr>
                            <td><?php echo $e($item['item_name'] ?? '-'); ?></td>
                            <td class="num"><?php echo $n($item['quantity'], 0); ?></td>
                            <td class="num"><?php echo $n($item['length'], 0); ?></td>
                            <td class="num"><?php echo $n($item['width'], 0); ?></td>
                            <td class="num"><?php echo $n($item['height'], 0); ?></td>
                            <td class="num"><?php echo $kg($item['item_weight']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr>
                        <td>Total</td>
                        <td class="num"><?php echo number_format($total_quantity, 0, '.', ''); ?></td>
                        <td class="num" colspan="3">Volume: <?php echo rtrim(rtrim(number_format($total_volume_m3, 3, ',', '.'), '0'), ','); ?> m³</td>
                        <td class="num"><?php echo rtrim(rtrim(number_format($total_weight_kg, 3, ',', '.'), '0'), ','); ?></td>
                    </tr></tfoot>
                </table>
                </div>
            </section>

            <section class="trk-card">
                <h3>Travel History</h3>
                <?php
                $hist = dbQuery("SELECT * FROM courier_track WHERE cid = :cid ORDER BY bk_time DESC", [':cid' => $cid]);
                $rows = [];
                while ($row = dbFetchAssoc($hist)) { $rows[] = $row; }
                if ($rows): ?>
                    <div class="trk-scroll">
                    <table class="trk-table">
                        <thead><tr><th>Tanggal / waktu</th><th>Lokasi terakhir</th><th>Status</th><th>Keterangan</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td style="white-space:nowrap"><?php echo $e($row['bk_time']); ?></td>
                                <td><?php echo $e($row['pick_time']); ?></td>
                                <td><?php echo $e($row['status']); ?></td>
                                <td><?php echo $e($row['comments']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <p style="margin:0;color:#78909c">Belum ada riwayat perjalanan.</p>
                <?php endif; ?>
                <?php if (!empty($tracking_link)): ?>
                    <h3 style="margin-top:18px">Live Tracking</h3>
                    <div class="iframe-container">
                        <iframe src="<?php echo $e($tracking_link); ?>" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        </main>

        <?php include_once "footer.php"; ?>

       <!-- ... (Previous HTML/PHP content remains unchanged until the script section) ... -->

<script src="deprixa_components/bundles/jquery"></script>
<script src="deprixa_components/bundles/bootstrap"></script>
<script src="deprixa_components/bundles/modernizr"></script>
<script src="deprixa_components/Scripts/CookieManager.js"></script>
<script src="deprixa_components/Scripts/MPD/Common/ga-events.js"></script>   
<script src="deprixa_components/bundles/jqueryval"></script>
<script src="deprixa_components/scripts/tracking.js"></script>

</body>
</html>
<?php
    } else {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />	    
    <title>PT. ANEKA LINTAS INDONESIA CARGO</title>
    <meta name="description" content=""/>
    <meta name="keywords" content="" />
    <meta name="author" content="">	
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" href="logoAli.png" />
    <meta name="robots" content="noindex, follow" />
    <link rel="stylesheet" href="deprixa/asset1/css/font-awesome.min.css" type="text/css" media="screen">  
    <link href="deprixa_components/content/cssefe4.css" rel="stylesheet"/>
    <link rel="stylesheet" href="deprixa/css/tracking.css" type="text/css" />   
    <link href="deprixa_components/styles/track-order.css" rel="stylesheet" />
    <link href="deprixa/css/style.css" rel="stylesheet" media="all">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,600' rel='stylesheet' type='text/css'>
    <style>
        .header-wrap { position: relative; height: 100px; overflow: hidden; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.45); z-index: 10; }
        .white-part, .red-part { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .white-part { background-color: white; z-index: 1; }
        .red-part { background-color: #ff0000; clip-path: polygon(55% 0, 100% 0, 100% 100%, 45% 100%); z-index: 2; }
        .header-content { position: relative; z-index: 3; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; height: 100%; }
        .logo-link { display: flex; align-items: center; text-decoration: none !important; margin-left: 30px; }
        .logo-image { max-height: 70px; margin-right: 15px; }
        .company-name { font-size: 1.8rem; font-weight: 800; color: #222; line-height: 1.1; }
        .company-name span { display: block; }
        .menu-nav { display: flex; gap: 20px; margin-right: 40px; }
        .menu-nav a { color: #fff; font-weight: 700; font-size: 1.4rem; text-decoration: none; border-right: 1px solid rgba(255, 255, 255, 0.4); padding-right: 12px; }
        .menu-nav a:last-child { border-right: none; }
        .menu-nav a:hover { color: #ffdcdc; }
        @media (max-width: 768px) { .header-content { flex-direction: column; padding: 15px; text-align: center; } .logo-link { margin: 0; flex-direction: column; } .menu-nav { flex-direction: column; gap: 8px; margin-right: 0; } .company-name { font-size: 1.6rem; text-align: center; } .logo-image { max-height: 60px; } }

        .btn-system { background-color: #333; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; text-decoration: none; font-weight: 700; font-size: 1.4rem; transition: background-color 0.3s ease; }
        .btn-system:hover { background-color: #444; }

        table, th, td { font-size: 1.2rem; color: #333; }
        h2 { font-size: 1.6rem; }
        h3 { font-size: 1.4rem; }

        .status-booking { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        @media (max-width: 768px) { .status-booking { flex-direction: column; gap: 10px; align-items: flex-start; } }

        .travel-history { margin-bottom: 30px; }

        footer, footer p, footer h3, .footer-text { font-size: 1.2rem !important; color: #333; }
        footer a, .footer-links a, .whatsapp-btn, #back-top { font-size: 1rem !important; color: #333; }
        .whatsapp-btn, #back-top { background-color: #333; color: #fff; padding: 6px 12px; border-radius: 5px; text-decoration: none; transition: background-color 0.3s ease; }
        .whatsapp-btn:hover, #back-top:hover { background-color: #444; }
    </style>
  <link rel="stylesheet" href="deprixa_components/styles/ali-site.css?v=<?php echo @filemtime(__DIR__ . '/deprixa_components/styles/ali-site.css'); ?>" />
</head>

<body>
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
                <a href="tracking">Lacak Kiriman Anda</a>
                <a href="about-us">Tentang Kami</a>
                <a href="gallery.php">Gallery</a>
                <a href="contact-us">Hubungi Kami</a>
                <a href="login.php">LOGIN</a>
            </nav>
        </div>
    </header>

    <div class="slide"></div>
    <main class="slide">
        <div class="fw">
            <section class="title">
                <header>
                    <h1><img src="deprixa_components/images/error.png" />Parcel Tracking</h1>					                   
                </header>
                <div class="media-left"></div>
            </section>
        </div>  
        <div class="container">
            <div class="page-content">					
                <div class="text-center">
                    <h1><img src="deprixa_components/images/error.png" /></h1>
                    <h3>Tracking number not found,</h3>
                    <p><span style="color:#FF0000"><?php echo htmlspecialchars($cons); ?></span> check the number or Contact Us.</p>
                    <div class="text-center"><a href="/" class="btn-system btn-small">Back To Home</a></div>
                </div>					
            </div>
        </div>
    </main>

    <?php include_once "footer.php"; ?>

    <script src="deprixa_components/bundles/jquery"></script>
    <script src="deprixa_components/bundles/bootstrap"></script>
    <script src="deprixa_components/bundles/modernizr"></script>
    <script src="deprixa_components/scripts/CookieManager.js"></script>
    <script src="deprixa_components/scripts/MPD/Common/ga-events.js"></script>    
    <script src="deprixa_components/bundles/jqueryval"></script>
    <script src="deprixa_components/scripts/tracking.js"></script>
    <script>
        function myFunction() {
            window.print();
        }
    </script>
</body>
</html>
<?php
    }
}
?>