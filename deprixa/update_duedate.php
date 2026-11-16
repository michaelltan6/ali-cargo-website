<?php
header('Content-Type: application/json');
require_once('database.php');
require_once('library.php');
requireAdmin(true);
if (!sameOriginOk()) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Permintaan ditolak.']); exit; }

// Aktifkan tempo: jatuh tempo = tanggal mulai + lama tempo (7/14/30 hari) yang dipilih saat pengiriman dibuat.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'activate_tempo') {
    require_once('courier-lib.php');
    $fail = function ($m) { echo json_encode(['success' => false, 'message' => $m]); exit; };
    $cid = (int)($_POST['cid'] ?? 0);
    $start = (string)($_POST['start_date'] ?? '');
    $d = DateTime::createFromFormat('Y-m-d', $start);
    if (!$d || $d->format('Y-m-d') !== $start) { $fail('Tanggal mulai tidak valid.'); }
    if (!courier_columns_ready()) { $fail('Kolom tempo belum siap. Coba muat ulang halaman.'); }
    $row = dbFetchAssoc(dbQuery("SELECT payment_term FROM courier WHERE cid = ?", [$cid]));
    if (!$row) { $fail('Pengiriman tidak ditemukan.'); }
    $term = (string)($row['payment_term'] ?? '');
    if (!in_array($term, ['7', '14', '30'], true)) { $fail('Pengiriman ini tidak memakai tempo 7/14/30 hari.'); }
    $due = (clone $d)->modify('+' . $term . ' days')->format('Y-m-d');
    dbQuery("UPDATE courier SET tempo_start = ?, due_date = ? WHERE cid = ?", [$start, $due, $cid]);
    echo json_encode(['success' => true, 'message' => 'Tempo aktif. Jatuh tempo ' . $due, 'due_date' => $due, 'tempo_start' => $start]);
    exit;
}

try {
    // (log data dimatikan) error_log("update_duedate.php started, POST data: " . print_r($_POST, true));
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_duedate' && isset($_POST['cid'])) {
        $cid = filter_var($_POST['cid'], FILTER_SANITIZE_NUMBER_INT);
        $due_date = !empty($_POST['due_date']) ? filter_var($_POST['due_date'], FILTER_SANITIZE_STRING) : null;
        error_log("Processing update for cid=$cid, due_date=$due_date");

        $stmt = dbQuery("UPDATE courier SET due_date = :due_date WHERE cid = :cid", [
            ':due_date' => $due_date,
            ':cid' => $cid
        ]);

        if ($stmt->rowCount() > 0) {
            error_log("Successfully updated due_date for cid $cid to $due_date");
            echo json_encode(['success' => true, 'message' => 'Due date updated', 'due_date' => $due_date]);
        } else {
            error_log("No rows updated for cid $cid, due_date $due_date (record not found or no change)");
            // Check if record exists
            $checkStmt = dbQuery("SELECT cid FROM courier WHERE cid = :cid", [':cid' => $cid]);
            if (dbNumRows($checkStmt) > 0) {
                echo json_encode(['success' => false, 'message' => 'No changes made (due_date already set?)']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
            }
        }
    } else {
        // (log data dimatikan) error_log("Invalid request to update_duedate.php: " . print_r($_POST, true));
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
} catch (PDOException $e) {
    error_log("Database error in update_duedate.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in update_duedate.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>