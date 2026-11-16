<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

require_once('../../database.php');
require_once __DIR__ . '/../_common.php';
st_auth(true);

try {
    // Fetch all records from mode_bookings
    $stmt = dbQuery("SELECT * FROM mode_bookings");
    $datos = [];
    while ($row = dbFetchAssoc($stmt)) {
        $datos[] = $row;
    }
    echo json_encode($datos);
} catch (PDOException $e) {
    error_log("listar.php query failed: " . $e->getMessage());
    echo json_encode(['error' => htmlspecialchars($e->getMessage())]);
}
?>