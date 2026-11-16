<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

require_once('../../database.php');
require_once __DIR__ . '/../_common.php';
st_auth(false);

$id = $_POST['id'] ?? '';

try {
    // Get current estado
    $stmt = dbQuery("SELECT estado FROM mode_bookings WHERE id = ?", [$id]);
    $estado = dbFetchAssoc($stmt);

    if (!$estado) {
        echo json_encode(['msg' => 'error', 'details' => 'Record not found']);
        exit;
    }

    // Toggle estado
    $newEstado = $estado['estado'] == '1' ? '0' : '1';
    $stmt = dbQuery("UPDATE mode_bookings SET estado = ? WHERE id = ?", [$newEstado, $id]);
    echo json_encode(['msg' => 'ok']);
} catch (PDOException $e) {
    error_log("estado.php update failed: " . $e->getMessage());
    echo json_encode(['msg' => 'error', 'details' => htmlspecialchars($e->getMessage())]);
}
?>