<?php
// Ensure no output before JSON
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');
session_start();
require_once('database.php');
require_once('library.php');

// Prevent any output from included files
ob_start();
include_once('database.php');
include_once('library.php');
$included_output = ob_get_clean();
if (!empty($included_output)) {
    error_log("Unexpected output from included files: " . $included_output);
}

header('Content-Type: application/json; charset=utf-8');

try {
    isUser();
    error_log("Session data in update_bookmode.php: " . print_r($_SESSION, true));
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'update_bookmode') {
        throw new Exception("Invalid request.");
    }
    $cid = intval($_POST['cid']);
    $book_mode = $_POST['book_mode'];
    if (!in_array($book_mode, ['Paid', 'ToPay', 'Cash-on-Delivery'])) {
        throw new Exception("Invalid book mode.");
    }
    error_log("Processing update for cid=$cid, book_mode=$book_mode");
    $sql = "UPDATE courier SET book_mode = :book_mode WHERE cid = :cid";
    $stmt = dbQuery($sql, ['book_mode' => $book_mode, 'cid' => $cid]);
    if ($stmt->rowCount() === 0) {
        throw new Exception("No rows updated or shipment not found.");
    }
    // Verify update
    $verify = dbFetchAssoc(dbQuery("SELECT cid, cons_no, book_mode, officename, status FROM courier WHERE cid = :cid", ['cid' => $cid]));
    error_log("Verified data for cid=$cid: " . print_r($verify, true));
    $response = ['success' => true, 'message' => 'Status pembayaran berhasil diperbarui.', 'book_mode' => $book_mode];
    error_log("JSON response: " . json_encode($response));
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Update error: " . $e->getMessage());
    http_response_code(500);
    $response = ['success' => false, 'message' => 'Error: ' . htmlspecialchars($e->getMessage())];
    error_log("JSON error response: " . json_encode($response));
    echo json_encode($response);
}
ob_end_clean();
exit;
?>