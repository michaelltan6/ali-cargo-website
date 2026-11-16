<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
require_once('deprixa/database.php');

// Redirect if no consignment number is provided
if (empty($_POST['Consignment'])) {
    header("Location: tracking.php");
    exit;
}

$cons = isset($_POST['Consignment']) ? trim($_POST['Consignment']) : '';

// Proceed with query if $cons is set
if ($cons) {
    $sql = "SELECT c.*, ci.item_name, ci.quantity, ci.length, ci.width, ci.height, ci.weight AS item_weight, ci.volume 
            FROM courier c 
            LEFT JOIN courier_items ci ON c.cid = ci.courier_id 
            WHERE c.cons_no = :cons";
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

        // Determine progress percentage using etd and eta, tied to ETA
        $progress_percentage = 0;
        $eta_display = !empty($eta) ? date('Y-m-d', strtotime($eta)) : 'Not set';
        if (!empty($etd) && !empty($eta)) {
            $etdDate = new DateTime($etd, new DateTimeZone('Asia/Jakarta'));
            $etaDate = new DateTime($eta, new DateTimeZone('Asia/Jakarta'));
            $now = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // 04:11 PM WIB, August 05, 2025
            $totalDuration = $etaDate->getTimestamp() - $etdDate->getTimestamp();
            $elapsed = $now->getTimestamp() - $etdDate->getTimestamp();
            if ($elapsed < 0) $elapsed = 0; // Progress starts at 0% before ETD
            if ($elapsed >= $totalDuration) $elapsed = $totalDuration; // 100% at or after ETA
            $progress_percentage = $totalDuration > 0 ? ($elapsed / $totalDuration) * 100 : 0;
            $progress_percentage = min(100, max(0, $progress_percentage));
            if ($status === 'Delivered') $progress_percentage = 100; // Override for Delivered status
        } else {
            $progress_percentage = 0; // Default to 0% if dates are incomplete
        }
        $progress_percentage = number_format($progress_percentage, 2);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />	    
    <title>Tracking Result | ALI Cargo</title>
    <meta name="description" content="Courier Deprixa V2.5"/>
    <meta name="keywords" content="" />
    <meta name="author" content="">	
    <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, follow" />
    <link rel="icon" type="image/png" href="logoAli.png" />
    <link rel="canonical" href="tracking-result.php" />
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
    </style>
  <link rel="stylesheet" href="deprixa_components/styles/ali-site.css?v=20261114" />
</head>

<body>
    <header class="header-wrap">
        <div class="white-part"></div>
        <div class="red-part"></div>
        <div class="header-content">
            <a href="index.php" class="logo-link">
                <img src="logoAli.png" alt="Logo Aneka Lintas Indonesia Cargo" class="logo-image">
                <div class="company-name">
                    <span>ANEKA LINTAS</span>
                    <span>INDONESIA</span>
                    <span>CARGO</span>
                </div>
            </a>
            <nav class="menu-nav">
                <a href="layanan.php">Layanan</a>
                <a href="tracking.php">Tracking Kiriman</a>
                <a href="about-us.php">Tentang Kami</a>
                <a href="gallery.php">Gallery</a>
                <a href="contact-us.php">Hubungi Kami</a>
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

        <div class="container">
            <div>
                <table border="0" align="center" width="100%">
                    <div class="row">
                        <div class="col-md-6">
                            <h3><i class="fa fa-barcode" style="width: 25px; font-size: 24px; float: left;"></i> <span style="color:#FF4000"><strong><?php echo htmlspecialchars($cons_no); ?></strong></span></h3>
                        </div>
                        <div class="col-md-6">
                            <h3 class="status-booking">
                                <span><strong>Current Status</strong>: <span class="label <?php echo htmlspecialchars(str_replace(' ', '-', $status)); ?> label-large"><span style="font-size:1rem; color:white"><?php echo htmlspecialchars($status); ?></span></span></span>   
                                <span><strong>Booking Mode</strong>: <span class="label label-danger"><i class="fa fa-money"></i><span style="font-size:1rem; color:white"> <?php echo htmlspecialchars($book_mode); ?></span></span></span>
                            </h3>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr></tr>
                        </table>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-md-4"> 
                            <strong>Estimated Time of Arrival (ETA)</strong><br />
                            <?php echo htmlspecialchars($eta_display); ?>, By End Of Day 
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-md-12">
                            <h2>Additional Information</h2>
                        </div>
                        <br/>
                        <div class="col-md-4"> 
                            <strong>Origin:</strong> <?php echo htmlspecialchars($invice_no ?? 'N/A'); ?><br />
                            <strong>Destination:</strong> <?php echo htmlspecialchars($pickup_time ?? 'N/A'); ?><br />
                            <strong>Services:</strong> <?php echo htmlspecialchars($mode ?? 'N/A'); ?><br />
                            <strong>Type:</strong> <?php echo htmlspecialchars($ship_type ?? 'N/A'); ?><br />
                            <strong>Pickup Date/Time:</strong> <?php echo htmlspecialchars($collection_date ?? 'N/A'); ?><br/>   
                            <strong>Description:</strong> <?php echo htmlspecialchars($comments ?? 'N/A'); ?>
                        </div>
                        <div class="col-md-4"> 
                            <strong>Shipper Info</strong><br />
                            <strong>Name:</strong> <?php echo htmlspecialchars($ship_name ?? 'N/A'); ?><br />
                            <strong>Phone:</strong> <?php echo htmlspecialchars($shipper_phone ?? 'N/A'); ?><br />
                            <strong>Address:</strong> <?php echo htmlspecialchars($shipper_address ?? 'N/A'); ?> 
                        </div>
                        <div class="col-md-4"> 
                            <strong>Consignee Information</strong><br />
                            <strong>Name:</strong> <?php echo htmlspecialchars($rev_name ?? 'N/A'); ?><br />
                            <strong>Phone:</strong> <?php echo htmlspecialchars($receiver_phone ?? 'N/A'); ?><br />
                            <strong>Address:</strong> <?php echo htmlspecialchars($receiver_address ?? 'N/A'); ?>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-md-12">
                            <h2>Cargo Details</h2>
                        </div>
                        <br/>
                        <div class="col-md-12">
                            <table class="table table-bordered table-striped table-hover">
                                <tr class="car_bold col_dark_bold" align="center">
                                    <td><strong>Quantity</strong></td>
                                    <td><strong>Nama Barang</strong></td>
                                    <td><strong>Length (cm)</strong></td>
                                    <td><strong>Width (cm)</strong></td>
                                    <td><strong>Height (cm)</strong></td>
                                    <td><strong>Weight (kg)</strong></td>
                                </tr>
                                <?php
                                foreach ($items as $item) {
                                    $quantity = is_numeric($item['quantity']) ? number_format((int)$item['quantity'], 0, '.', '') : ($item['quantity'] ?? 'N/A');
                                    $item_name = htmlspecialchars($item['item_name'] ?? 'N/A');
                                    $length = is_numeric($item['length']) ? number_format((float)$item['length'], 3, '.', '') : ($item['length'] ?? 'N/A');
                                    $width = is_numeric($item['width']) ? number_format((float)$item['width'], 3, '.', '') : ($item['width'] ?? 'N/A');
                                    $height = is_numeric($item['height']) ? number_format((float)$item['height'], 3, '.', '') : ($item['height'] ?? 'N/A');
                                    $item_weight = is_numeric($item['item_weight']) ? number_format((float)$item['item_weight'], 3, '.', '') : ($item['item_weight'] ?? 'N/A');
                                    echo '<tr align="center">
                                            <td>' . $quantity . '</td>
                                            <td>' . $item_name . '</td>
                                            <td>' . $length . '</td>
                                            <td>' . $width . '</td>
                                            <td>' . $height . '</td>
                                            <td>' . $item_weight . '</td>
                                          </tr>';
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-md-12">
                            <h2>Summary</h2>
                        </div>
                        <br/>
                        <div class="col-md-12">
                            <table class="table table-bordered table-striped table-hover">
                                <tr class="car_bold col_dark_bold" align="center">
                                    <td><strong>Total Quantity</strong></td>
                                    <td><strong>Total Weight (kg)</strong></td>
                                    <td><strong>Total Volume (m³)</strong></td>
                                </tr>
                                <tr align="center">
                                    <td><?php echo number_format($total_quantity, 0, '.', ''); ?></td>
                                    <td><?php echo number_format($total_weight_kg, 3, '.', ''); ?></td>
                                    <td><?php echo number_format($total_volume_m3, 3, '.', ''); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-md-12 travel-history">
                            <h3>Progress Tracker</h3>
                            <div class="progress-container">
                                <div class="progress">
                                    <div id="progress-bar" class="progress-bar" role="progressbar" style="width: <?php echo htmlspecialchars($progress_percentage); ?>%;">
                                        <span class="progress-text"><?php echo htmlspecialchars($progress_percentage); ?>%</span>
                                    </div>
                                </div>
                                <img id="cartoon-box" src="deprixa/assets/images/cartoon-box.png" alt="Cartoon Box" class="cartoon-box">
                            </div>
                            <small>Estimated completion by <?php echo htmlspecialchars($eta_display); ?>.</small>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-md-12 travel-history">
                            <h2>Travel History</h2>
                            <br/>
                            <?php
                            $result = dbQuery("SELECT * FROM courier_track WHERE cid = :cid AND cons_no = :cons_no ORDER BY bk_time", [
                                ':cid' => $cid,
                                ':cons_no' => $cons_no
                            ]);

                            if (dbNumRows($result) > 0) {
                                echo '<table class="table table-bordered table-striped table-hover" >
                                        <tr class="car_bold col_dark_bold" align="center">
                                            <td><strong>Tracking No</strong></td>
                                            <td><strong>Last Location </strong></td>
                                            <td><strong>Status</strong></td>
                                            <td><strong>Date / Time</strong></td>
                                            <td><strong>Information</strong></td>                                                                                          
                                        </tr>';
                                while ($row = dbFetchAssoc($result)) {
                                    echo '<tr align="center">
                                            <td>' . htmlspecialchars($row['cons_no']) . '</td>
                                            <td>' . htmlspecialchars($row['pick_time']) . '</td>
                                            <td>' . htmlspecialchars($row['status']) . '</td>
                                            <td>' . htmlspecialchars($row['bk_time']) . '</td>                
                                            <td>' . htmlspecialchars($row['comments']) . '</td>
                                          </tr>';
                                }
                                echo '</table>';
                            } else {
                                echo '<tr>
                                        <td colspan="5" >No results found</td>
                                      </tr>';
                            }
                            ?>
                            <?php if (!empty($tracking_link)): ?>
                                <div>
                                    <h3>Live Tracking</h3>
                                    <div class="iframe-container">
                                        <iframe src="<?php echo htmlspecialchars($tracking_link); ?>" allowfullscreen></iframe>
                                    </div>
                                    <p>Tracking data provided by the linked service. Refresh to update.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </table>
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
<script>
    function myFunction() {
        window.print();
    }

    function updateProgress() {
        const etd = new Date('<?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($etd ?? 'now + 1 day'))); ?>').getTime();
        const eta = new Date('<?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($eta ?? 'now + 2 days'))); ?>').getTime();
        const now = new Date().getTime();
        const totalDuration = eta - etd;
        let elapsed = now - etd;
        if (elapsed < 0) elapsed = 0; // Progress starts at 0% before ETD
        if (elapsed >= totalDuration) elapsed = totalDuration; // 100% at or after ETA
        let progress = totalDuration > 0 ? (elapsed / totalDuration) * 100 : 0;
        progress = Math.min(100, Math.max(0, progress));
        if ('<?php echo htmlspecialchars($status); ?>' === 'Delivered') progress = 100;
        if ('<?php echo htmlspecialchars($eta ?? ''); ?>' === '') progress = 0;
        const progressBar = document.getElementById('progress-bar');
        const cartoonBox = document.getElementById('cartoon-box');
        if (progressBar && cartoonBox) {
            progressBar.style.width = Math.round(progress) + '%';
            progressBar.querySelector('.progress-text').textContent = Math.round(progress) + '%';
            const progressWidth = progressBar.offsetWidth;
            const containerWidth = progressBar.parentElement.parentElement.offsetWidth;
            const boxPosition = (progress / 100) * containerWidth - (cartoonBox.offsetWidth / 2);
            cartoonBox.style.left = boxPosition + 'px';
        }
    }

    // Run immediately on page load and then every minute
    document.addEventListener('DOMContentLoaded', updateProgress);
    setInterval(updateProgress, 60000); // Update every minute
</script>
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
    <link rel="canonical" href="tracking-result.php" />
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
  <link rel="stylesheet" href="deprixa_components/styles/ali-site.css?v=20261114" />
</head>

<body>
    <header class="header-wrap">
        <div class="white-part"></div>
        <div class="red-part"></div>
        <div class="header-content">
            <a href="index.php" class="logo-link">
                <img src="logoAli.png" alt="Logo Aneka Lintas Indonesia Cargo" class="logo-image">
                <div class="company-name">
                    <span>ANEKA LINTAS</span>
                    <span>INDONESIA</span>
                    <span>CARGO</span>
                </div>
            </a>
            <nav class="menu-nav">
                <a href="layanan.php">Layanan</a>
                <a href="tracking.php">Lacak Kiriman Anda</a>
                <a href="about-us.php">Tentang Kami</a>
                <a href="gallery.php">Gallery</a>
                <a href="contact-us.php">Hubungi Kami</a>
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
                    <div class="text-center"><a href="index.php" class="btn-system btn-small">Back To Home</a></div>
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