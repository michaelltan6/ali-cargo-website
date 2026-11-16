<?php
// Start output buffering immediately at the top to capture all output
ob_start();

// Disable any existing output and set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ensure no PHP errors are displayed
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fallback definition for limpiar() to preserve valid date formats
if (!function_exists('limpiar')) {
    function limpiar($value) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value; // Preserve date format YYYY-MM-DD
        }
        return htmlspecialchars(strip_tags($value));
    }
}

// Include required files with output buffering to prevent unexpected output
ob_start();
require_once('database.php');
$databaseOutput = ob_get_clean();

ob_start();
require_once('library.php');
$libraryOutput = ob_get_clean();

// Log any output from included files for debugging
if (!empty($databaseOutput)) {
    error_log("Unexpected output from database.php: " . $databaseOutput);
}
if (!empty($libraryOutput)) {
    error_log("Unexpected output from library.php: " . $libraryOutput);
}

// Attempt to call isUser() and handle its output
ob_start();
$isUserResult = isUser();
$userOutput = ob_get_clean();
if (!empty($userOutput)) {
    error_log("Unexpected output from isUser(): " . $userOutput);
}

// Check session authentication manually as a fallback
$authenticated = false;
if (isset($_SESSION['user_name']) && isset($_SESSION['user_type'])) {
    $authenticated = true;
    error_log("User authenticated: " . $_SESSION['user_name'] . ", type: " . $_SESSION['user_type']);
} else {
    error_log("Session variables user_name or user_type not set.");
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Initialize response
$response = ['success' => false, 'message' => 'Unknown error'];

// Clear any existing output buffer before processing
ob_clean();

try {
    if (!isset($_GET['action'])) {
        throw new Exception("Missing action.");
    }

    error_log("Received action: {$_GET['action']}, Method: {$_SERVER['REQUEST_METHOD']}");

    if ($_GET['action'] === 'delivered' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!$authenticated) {
            throw new Exception("Authentication required to update delivery status.");
        }
        $cid = $_GET['cid'] ?? 0;
        if ($cid <= 0) {
            throw new Exception("Invalid courier ID.");
        }
        $stmt = dbQuery("SELECT * FROM courier WHERE cid = ?", [$cid]);
        if (dbNumRows($stmt) == 0) {
            throw new Exception("No courier found with ID: $cid");
        }
        $stmt = dbQuery("UPDATE courier SET status = 'Delivered', progress_percentage = 100, last_updated = NOW() WHERE cid = ?", [$cid]);
        $response = [
            'success' => true,
            'message' => 'Status pengiriman berhasil diubah.',
            'redirect' => 'admin.php'
        ];
        error_log("Delivery status updated for cid: $cid");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_GET['action'] === 'company') {
            if (!$authenticated) {
                throw new Exception("Authentication required to update company details.");
            }
            $cname = limpiar($_POST['cname'] ?? '');
            $nit = limpiar($_POST['nit'] ?? '');
            $cphone = limpiar($_POST['cphone'] ?? '');
            $bemail = limpiar($_POST['bemail'] ?? '');
            $cemail = limpiar($_POST['cemail'] ?? '');
            $caddress = limpiar($_POST['caddress'] ?? '');
            $country = limpiar($_POST['country'] ?? '');
            $city = limpiar($_POST['city'] ?? '');
            $website = limpiar($_POST['website'] ?? '');
            $currency = limpiar($_POST['currency'] ?? '');
            $date = limpiar($_POST['date'] ?? '');
            $footer_website = limpiar($_POST['footer_website'] ?? '');

            if (empty($cname) || empty($caddress) || empty($country) || empty($city) || empty($currency)) {
                throw new Exception("Required fields are missing.");
            }

            $stmt = dbQuery(
                "UPDATE company SET cname = ?, nit = ?, cphone = ?, bemail = ?, cemail = ?, caddress = ?, country = ?, city = ?, website = ?, currency = ?, date = ?, footer_website = ? WHERE id = ?",
                [$cname, $nit, $cphone, $bemail, $cemail, $caddress, $country, $city, $website, $currency, $date, $footer_website, 1]
            );
            $response = [
                'success' => true,
                'message' => 'Company details updated successfully.',
                'redirect' => 'preferences.php?ok'
            ];
        } elseif ($_GET['action'] === 'add-cons') {
            if (!$authenticated) {
                throw new Exception("Authentication required to add consignment.");
            }
            error_log("Action received: add-cons");
            error_log("POST data: " . print_r($_POST, true));

            $cons_no = limpiar($_POST['cons_no'] ?? '');
            $ship_name = limpiar($_POST['ship_name'] ?? '');
            $rev_name = limpiar($_POST['rev_name'] ?? '');
            $collection_date = limpiar($_POST['pick_date'] ?? ''); // Changed from collection_date to match form
            $book_mode = limpiar($_POST['book_mode'] ?? '');
            $user = $_SESSION['user_name'] ?? 'Unknown';
            $status = limpiar($_POST['status'] ?? 'In-Transit');
            $shipping_subtotal = str_replace(['.', ','], '', $_POST['shipping_subtotal'] ?? 0);
            $book_date = date('Y-m-d H:i:s');
            $shipper_address = limpiar($_POST['shipper_address'] ?? '');
            $shipper_phone = limpiar($_POST['shipper_phone'] ?? '');
            $shipper_cc = limpiar($_POST['shipper_cc'] ?? '');
            $receiver_address = limpiar($_POST['receiver_address'] ?? '');
            $receiver_phone = limpiar($_POST['receiver_phone'] ?? '');
            $receiver_cc = limpiar($_POST['receiver_cc'] ?? '');
            $receiver_email = limpiar($_POST['receiver_email'] ?? '');
            $ship_type = limpiar($_POST['ship_type'] ?? '');
            $mode = limpiar($_POST['mode'] ?? '');
            $total_declarate = str_replace(['.', ','], '', $_POST['total_declarate'] ?? 0);
            $office = limpiar($_POST['office'] ?? '');
            $pickup_time = limpiar($_POST['pickup_time'] ?? '');
            $etd = limpiar($_POST['schedule'] ?? ''); // Changed to match form field name
            $eta = limpiar($_POST['eta'] ?? '');
            $comments = limpiar($_POST['comments'] ?? '');
            $weight = floatval(str_replace(',', '.', $_POST['weight'] ?? 0));
            $total_volume = floatval(str_replace(',', '.', $_POST['total_volume'] ?? 0));
            $qty = floatval(str_replace(['.', ','], '', $_POST['qty'] ?? 0));
            $freight = floatval(str_replace(['.', ','], '', $_POST['freight'] ?? 0));
            $variable = floatval(str_replace(['.', ','], '', $_POST['variable'] ?? 0));
            $status_delivered = limpiar($_POST['status_delivered'] ?? 'Pending');
            $officename = limpiar($_POST['officename'] ?? $_SESSION['user_type'] ?? 'Unknown');
            $tracking_link = $_POST['tracking_link'] ?? null;

            // Validate dates
            $collectionDate = DateTime::createFromFormat('Y-m-d', $collection_date, new DateTimeZone('Asia/Jakarta'));
            if (!$collectionDate) {
                $collectionDate = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // 09:38 AM WIB, August 06, 2025
                $collection_date = $collectionDate->format('Y-m-d');
                error_log("Invalid collection_date, defaulted to: $collection_date");
            } else {
                $collection_date = $collectionDate->format('Y-m-d');
            }

            $etdDate = DateTime::createFromFormat('Y-m-d', $etd, new DateTimeZone('Asia/Jakarta'));
            if (!$etdDate) {
                $etdDate = clone $collectionDate;
                $etdDate->modify('+1 day');
                $etd = $etdDate->format('Y-m-d');
                error_log("Invalid ETD, defaulted to: $etd");
            } else {
                $etd = $etdDate->format('Y-m-d');
            }

            $etaDate = DateTime::createFromFormat('Y-m-d', $eta, new DateTimeZone('Asia/Jakarta'));
            if (!$etaDate) {
                $etaDate = clone $etdDate;
                $etaDate->modify('+2 days');
                $eta = $etaDate->format('Y-m-d');
                error_log("Invalid ETA, defaulted to: $eta");
            } else {
                $eta = $etaDate->format('Y-m-d');
            }
            if ($etaDate <= $etdDate || $etdDate <= $collectionDate) {
                if ($etdDate <= $collectionDate) {
                    $etdDate->modify('+1 day');
                    $etd = $etdDate->format('Y-m-d');
                    error_log("ETD adjusted to be after collection_date: $etd");
                }
                if ($etaDate <= $etdDate) {
                    $etaDate->modify('+1 day');
                    $eta = $etaDate->format('Y-m-d');
                    error_log("ETA adjusted to be after ETD: $eta");
                }
            }

            // Initial progress percentage
            $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // 09:38 AM WIB, August 06, 2025
            $totalDuration = $etaDate->getTimestamp() - $etdDate->getTimestamp();
            $elapsed = $currentDateTime->getTimestamp() - $etdDate->getTimestamp();
            if ($elapsed < 0) $elapsed = 0;
            if ($elapsed > $totalDuration) $elapsed = $totalDuration;
            $progress_percentage = ($totalDuration > 0) ? min(100, max(0, ($elapsed / $totalDuration) * 100)) : 0;

            if (empty($cons_no) || empty($ship_name) || empty($rev_name)) {
                throw new Exception("Consignment No, Shipper Name, and Receiver Name are required.");
            }
            if (empty($_POST['item_name']) || !is_array($_POST['item_name'])) {
                throw new Exception("At least one item is required.");
            }
            // Kode/ID customer (shipper_cc, receiver_cc) boleh kosong. Sisanya wajib.
            $wajib = [
                'Nomor kontak pengirim' => $shipper_phone, 'Alamat pengirim' => $shipper_address,
                'Nomor kontak penerima' => $receiver_phone, 'Alamat penerima' => $receiver_address,
                'Email penerima' => $receiver_email, 'Produk / tipe barang' => $ship_type,
                'Service (Mode)' => $mode, 'Kantor asal' => $office, 'Kota tujuan' => $pickup_time,
            ];
            $kurang = [];
            foreach ($wajib as $label => $nilai) { if (trim((string)$nilai) === '' || $nilai === '0') { $kurang[] = $label; } }
            if ($kurang) {
                throw new Exception("Lengkapi dulu: " . implode(', ', $kurang) . ".");
            }

            $stmt = dbQuery("SELECT COUNT(*) AS count FROM courier WHERE cons_no = ?", [$cons_no]);
            if (dbFetchAssoc($stmt)['count'] > 0) {
                throw new Exception("Tracking number already exists: $cons_no");
            }

            $params = [
                $cons_no, $book_mode, $ship_name, $rev_name, $collection_date, $user, $status, $shipping_subtotal, $book_date,
                $shipper_address, $shipper_phone, $shipper_cc, $receiver_address, $receiver_phone, $receiver_cc,
                $receiver_email, $ship_type, $mode, $total_declarate, $office, $pickup_time, $etd, $eta, $comments, $weight, $total_volume,
                $qty, $freight, $variable, $status_delivered, $officename, $tracking_link, $progress_percentage
            ];
            error_log("Insert parameters for add-cons: " . print_r($params, true));

            global $dbConn;
            if (!isset($dbConn)) {
                throw new Exception("Database connection not established.");
            }
            $dbConn->beginTransaction();
            try {
                $query = "INSERT INTO courier (
                    cons_no, book_mode, ship_name, rev_name, collection_date, user, status, shipping_subtotal, book_date,
                    shipper_address, shipper_phone, shipper_cc, receiver_address, receiver_phone, receiver_cc,
                    receiver_email, ship_type, mode, total_declarate, office, pickup_time, etd, eta, comments, weight, total_volume,
                    qty, freight, variable, status_delivered, officename, tracking_link, progress_percentage
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = dbQuery($query, $params);
                $courier_id = $dbConn->lastInsertId();
                error_log("Inserted courier record with ID: $courier_id, collection_date: $collection_date, etd: $etd, eta: $eta");

                foreach ($_POST['item_name'] as $i => $item_name) {
                    if (empty($item_name)) continue;
                    $stmt = dbQuery(
                        "INSERT INTO courier_items (courier_id, item_name, quantity, length, width, height, weight, volume) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $courier_id,
                            limpiar($item_name),
                            floatval($_POST['item_quantity'][$i] ?? 1),
                            floatval($_POST['item_length'][$i] ?? 0),
                            floatval($_POST['item_width'][$i] ?? 0),
                            floatval($_POST['item_height'][$i] ?? 0),
                            floatval($_POST['item_weight'][$i] ?? 0),
                            floatval($_POST['item_volume'][$i] ?? 0)
                        ]
                    );
                }

                $dbConn->commit();
                error_log("Transaction committed for cons_no: $cons_no");

                // Tautkan ke Customer Profile. Kalau bagian ini gagal, pengiriman tetap tersimpan.
                try {
                    require_once __DIR__ . '/customer-lib.php';
                    $linkId = (int)($_POST['customer_id'] ?? 0);
                    if ($linkId <= 0 && !empty($_POST['save_customer']) && trim($_POST['ship_name'] ?? '') !== '') {
                        $cpf = cp_fields([
                            'company_name' => $_POST['ship_name'] ?? '',
                            'pic_name'     => $_POST['shipper_pic'] ?? '',
                            'pic_phone'    => $_POST['shipper_phone'] ?? '',
                            'address'      => $_POST['shipper_address'] ?? '',
                            'code'         => $_POST['shipper_cc'] ?? '',
                        ]);
                        if (cp_validate($cpf) === '') { $linkId = cp_create($cpf); }
                    }
                    if ($linkId > 0 && cp_get($linkId) && cp_courier_has_customer_col()) {
                        dbQuery("UPDATE courier SET customer_id = ? WHERE cid = ?", [$linkId, $courier_id]);
                    }
                } catch (Exception $e) {
                    error_log("Tautan customer gagal: " . $e->getMessage());
                }
                $response = [
                    'success' => true,
                    'message' => 'Pengiriman berhasil ditambahkan.',
                    'redirect' => 'admin.php'
                ];
            } catch (Exception $e) {
                $dbConn->rollBack();
                error_log("Transaction rolled back: " . $e->getMessage());
                throw $e;
            }
        } elseif ($_GET['action'] === 'update-addcourier') {
            if (!$authenticated) {
                throw new Exception("Authentication required to update consignment.");
            }
            $cid = $_POST['cid'] ?? 0;
            if ($cid <= 0) {
                throw new Exception("Invalid courier ID.");
            }
            $stmt = dbQuery("SELECT * FROM courier WHERE cid = ?", [$cid]);
            if (dbNumRows($stmt) == 0) {
                throw new Exception("No courier found with ID: $cid");
            }

            $data = [
                'cons_no' => limpiar($_POST['cons_no'] ?? ''),
                'ship_name' => limpiar($_POST['ship_name'] ?? ''),
                'rev_name' => limpiar($_POST['rev_name'] ?? ''),
                'collection_date' => limpiar($_POST['pick_date'] ?? ''), // Match form field
                'book_mode' => limpiar($_POST['book_mode'] ?? ''),
                'status' => limpiar($_POST['status'] ?? ''),
                'shipping_subtotal' => str_replace(['.', ','], '', $_POST['shipping_subtotal'] ?? 0),
                'shipper_address' => limpiar($_POST['shipper_address'] ?? ''),
                'shipper_phone' => limpiar($_POST['shipper_phone'] ?? ''),
                'shipper_cc' => limpiar($_POST['shipper_cc'] ?? ''),
                'receiver_address' => limpiar($_POST['receiver_address'] ?? ''),
                'receiver_phone' => limpiar($_POST['receiver_phone'] ?? ''),
                'receiver_cc' => limpiar($_POST['receiver_cc'] ?? ''),
                'receiver_email' => limpiar($_POST['receiver_email'] ?? ''),
                'ship_type' => limpiar($_POST['ship_type'] ?? ''),
                'mode' => limpiar($_POST['mode'] ?? ''),
                'total_declarate' => str_replace(['.', ','], '', $_POST['total_declarate'] ?? 0),
                'office' => limpiar($_POST['office'] ?? ''),
                'pickup_time' => limpiar($_POST['pickup_time'] ?? ''),
                'etd' => limpiar($_POST['schedule'] ?? ''), // Match form field
                'eta' => limpiar($_POST['eta'] ?? ''),
                'comments' => limpiar($_POST['comments'] ?? ''),
                'weight' => floatval(str_replace(',', '.', $_POST['weight'] ?? 0)),
                'total_volume' => floatval(str_replace(',', '.', $_POST['total_volume'] ?? 0)),
                'qty' => floatval(str_replace(['.', ','], '', $_POST['qty'] ?? 0)),
                'tracking_link' => $_POST['tracking_link'] ?? null
            ];

            // Validate dates
            $collectionDate = DateTime::createFromFormat('Y-m-d', $data['collection_date'], new DateTimeZone('Asia/Jakarta'));
            if (!$collectionDate) {
                $collectionDate = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // 09:38 AM WIB, August 06, 2025
                $data['collection_date'] = $collectionDate->format('Y-m-d');
                error_log("Invalid collection_date for cid $cid, defaulted to: " . $data['collection_date']);
            } else {
                $data['collection_date'] = $collectionDate->format('Y-m-d');
            }

            $etdDate = DateTime::createFromFormat('Y-m-d', $data['etd'], new DateTimeZone('Asia/Jakarta'));
            if (!$etdDate) {
                $etdDate = clone $collectionDate;
                $etdDate->modify('+1 day');
                $data['etd'] = $etdDate->format('Y-m-d');
                error_log("Invalid ETD for cid $cid, defaulted to: " . $data['etd']);
            } else {
                $data['etd'] = $etdDate->format('Y-m-d');
            }

            $etaDate = DateTime::createFromFormat('Y-m-d', $data['eta'], new DateTimeZone('Asia/Jakarta'));
            if (!$etaDate) {
                $etaDate = clone $etdDate;
                $etaDate->modify('+2 days');
                $data['eta'] = $etaDate->format('Y-m-d');
                error_log("Invalid ETA for cid $cid, defaulted to: " . $data['eta']);
            } else {
                $data['eta'] = $etaDate->format('Y-m-d');
            }
            if ($etaDate <= $etdDate || $etdDate <= $collectionDate) {
                if ($etdDate <= $collectionDate) {
                    $etdDate->modify('+1 day');
                    $data['etd'] = $etdDate->format('Y-m-d');
                    error_log("ETD adjusted to be after collection_date for cid $cid: " . $data['etd']);
                }
                if ($etaDate <= $etdDate) {
                    $etaDate->modify('+1 day');
                    $data['eta'] = $etaDate->format('Y-m-d');
                    error_log("ETA adjusted to be after ETD for cid $cid: " . $data['eta']);
                }
            }

            // Recalculate progress_percentage server-side
            $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // 09:38 AM WIB, August 06, 2025
            $totalDuration = $etaDate->getTimestamp() - $etdDate->getTimestamp();
            $elapsed = $currentDateTime->getTimestamp() - $etdDate->getTimestamp();
            if ($elapsed < 0) $elapsed = 0;
            if ($elapsed > $totalDuration) $elapsed = $totalDuration;
            if ($data['status'] === 'Delivered') $elapsed = $totalDuration;
            $progress_percentage = ($totalDuration > 0) ? min(100, max(0, ($elapsed / $totalDuration) * 100)) : 0;
            $data['progress_percentage'] = round($progress_percentage);
            error_log("Recalculated progress_percentage: {$data['progress_percentage']} for cid: $cid, collection_date: {$data['collection_date']}, etd: {$data['etd']}, eta: {$data['eta']}");

            $stmt = dbQuery("SELECT COUNT(*) AS count FROM courier WHERE cons_no = ? AND cid != ?", [$data['cons_no'], $cid]);
            if (dbFetchAssoc($stmt)['count'] > 0) {
                throw new Exception("Tracking number already exists for a different courier.");
            }

            $stmt = dbQuery(
                "UPDATE courier SET 
                    cons_no = ?, ship_name = ?, rev_name = ?, collection_date = ?, book_mode = ?, status = ?, shipping_subtotal = ?, 
                    shipper_address = ?, shipper_phone = ?, shipper_cc = ?, receiver_address = ?, receiver_phone = ?, receiver_cc = ?, receiver_email = ?, ship_type = ?, mode = ?, 
                    total_declarate = ?, office = ?, pickup_time = ?, etd = ?, eta = ?, comments = ?, weight = ?, total_volume = ?, qty = ?, tracking_link = ?, 
                    progress_percentage = ?, last_updated = NOW()
                    WHERE cid = ?",
                [
                    $data['cons_no'], $data['ship_name'], $data['rev_name'], $data['collection_date'], $data['book_mode'], $data['status'], $data['shipping_subtotal'],
                    $data['shipper_address'], $data['shipper_phone'], $data['shipper_cc'], $data['receiver_address'], $data['receiver_phone'], $data['receiver_cc'], $data['receiver_email'], $data['ship_type'], $data['mode'], 
                    $data['total_declarate'], $data['office'], $data['pickup_time'], $data['etd'], $data['eta'], $data['comments'], $data['weight'], $data['total_volume'], $data['qty'], $data['tracking_link'],
                    $data['progress_percentage'], $cid
                ]
            );
            error_log("Updated courier record with cid: $cid, collection_date: {$data['collection_date']}, etd: {$data['etd']}, eta: {$data['eta']}, progress_percentage: {$data['progress_percentage']}");

            // Update courier items
            if (isset($_POST['item_name']) && is_array($_POST['item_name'])) {
                dbQuery("DELETE FROM courier_items WHERE courier_id = ?", [$cid]);
                error_log("Deleted existing items for cid: $cid");

                foreach ($_POST['item_name'] as $i => $item_name) {
                    $item_name = limpiar($item_name ?? '');
                    if (empty($item_name)) continue;

                    $quantity = floatval($_POST['item_quantity'][$i] ?? 1);
                    $length = floatval($_POST['item_length'][$i] ?? 0);
                    $width = floatval($_POST['item_width'][$i] ?? 0);
                    $height = floatval($_POST['item_height'][$i] ?? 0);
                    $weight = floatval($_POST['item_weight'][$i] ?? 0);
                    $volume = floatval($_POST['item_volume'][$i] ?? 0);

                    if ($volume <= 0 && $length > 0 && $width > 0 && $height > 0) {
                        $volume = ($length * $width * $height) / 1000000; // cm³ to m³
                    }

                    $stmt = dbQuery(
                        "INSERT INTO courier_items (courier_id, item_name, quantity, length, width, height, weight, volume) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$cid, $item_name, $quantity, $length, $width, $height, $weight, $volume]
                    );
                    error_log("Inserted item: $item_name, qty: $quantity, volume: $volume for cid: $cid");
                }
            }

            $response = [
                'success' => true,
                'message' => 'Pengiriman berhasil diperbarui.',
                'redirect' => 'admin.php'
            ];
        } elseif ($_GET['action'] === 'update-status') {
            if (!$authenticated) {
                throw new Exception("Authentication required to update status.");
            }
            $cid = $_POST['cid'] ?? 0;
            if ($cid <= 0) {
                throw new Exception("Invalid courier ID.");
            }
            $stmt = dbQuery("SELECT * FROM courier WHERE cid = ?", [$cid]);
            if (dbNumRows($stmt) == 0) {
                throw new Exception("No courier found with ID: $cid");
            }

            $status = limpiar($_POST['status'] ?? '');
            $pick_time = limpiar($_POST['pick_time'] ?? '');
            $comments = limpiar($_POST['comments'] ?? '');
            $user = limpiar($_POST['user'] ?? $_SESSION['user_name'] ?? 'Unknown');
            $cons_no = limpiar($_POST['cons_no'] ?? '');

            $stmt = dbQuery(
                "UPDATE courier SET status = ?, pick_time = ?, comments = ?, user = ?, last_updated = NOW() WHERE cid = ?",
                [$status, $pick_time, $comments, $user, $cid]
            );

            $stmt = dbQuery(
                "INSERT INTO courier_track (cid, cons_no, status, pick_time, comments, user, bk_time) VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $cons_no, $status, $pick_time, $comments, $user]
            );

            // Recalculate progress_percentage if status changes to Delivered
            if ($status === 'Delivered') {
                $stmt = dbQuery("SELECT etd, eta FROM courier WHERE cid = ?", [$cid]);
                $row = dbFetchAssoc($stmt);
                $etdDate = new DateTime($row['etd'], new DateTimeZone('Asia/Jakarta'));
                $etaDate = new DateTime($row['eta'], new DateTimeZone('Asia/Jakarta'));
                $progress_percentage = 100;
                dbQuery("UPDATE courier SET progress_percentage = ?, last_updated = NOW() WHERE cid = ?", [$progress_percentage, $cid]);
                error_log("Status set to Delivered, progress_percentage set to 100 for cid: $cid");
            } else {
                // Recalculate for other statuses based on current time
                $stmt = dbQuery("SELECT etd, eta FROM courier WHERE cid = ?", [$cid]);
                $row = dbFetchAssoc($stmt);
                $etdDate = new DateTime($row['etd'], new DateTimeZone('Asia/Jakarta'));
                $etaDate = new DateTime($row['eta'], new DateTimeZone('Asia/Jakarta'));
                $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // 09:38 AM WIB, August 06, 2025
                $totalDuration = $etaDate->getTimestamp() - $etdDate->getTimestamp();
                $elapsed = $currentDateTime->getTimestamp() - $etdDate->getTimestamp();
                if ($elapsed < 0) $elapsed = 0;
                if ($elapsed > $totalDuration) $elapsed = $totalDuration;
                $progress_percentage = ($totalDuration > 0) ? min(100, max(0, ($elapsed / $totalDuration) * 100)) : 0;
                dbQuery("UPDATE courier SET progress_percentage = ?, last_updated = NOW() WHERE cid = ?", [round($progress_percentage), $cid]);
                error_log("Recalculated progress_percentage: $progress_percentage for cid: $cid");
            }

            $response = [
                'success' => true,
                'message' => 'Status pengiriman berhasil diubah.',
                'redirect' => 'admin.php'
            ];
        } elseif ($_GET['action'] === 'update_bookmode') {
            if (!$authenticated) {
                throw new Exception("Authentication required to update booking mode.");
            }
            $cid = $_POST['cid'] ?? 0;
            if ($cid <= 0) {
                throw new Exception("Invalid courier ID.");
            }
            $stmt = dbQuery("SELECT * FROM courier WHERE cid = ?", [$cid]);
            if (dbNumRows($stmt) == 0) {
                throw new Exception("No courier found with ID: $cid");
            }

            $book_mode = limpiar($_POST['book_mode'] ?? '');

            if (empty($book_mode)) {
                throw new Exception("Booking mode is required.");
            }

            $stmt = dbQuery("UPDATE courier SET book_mode = ?, last_updated = NOW() WHERE cid = ?", [$book_mode, $cid]);
            $response = [
                'success' => true,
                'message' => 'Booking mode updated successfully.',
                'redirect' => 'list-of-shipping-topaid.php'
            ];
        } elseif ($_GET['action'] === 'logOut') {
            if (!$authenticated) {
                throw new Exception("No active session to log out.");
            }
            session_unset();
            session_destroy();
            $response = [
                'success' => true,
                'message' => 'Logged out successfully.',
                'redirect' => '../login.php'
            ];
        } else {
            throw new Exception("Invalid action.");
        }
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage() . " at line " . $e->getLine());
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

// Clear buffer and send JSON response
ob_end_clean();
echo json_encode($response);
exit;
?>