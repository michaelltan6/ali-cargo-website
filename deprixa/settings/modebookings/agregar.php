<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

require_once('../../database.php');
require_once __DIR__ . '/../_common.php';
st_auth(false);

// Validate POST data
$name = $_POST['name'] ?? '';
$services = $_POST['services'] ?? '';
$deliverytime = $_POST['deliverytime'] ?? '';
$observations = $_POST['observations'] ?? '';
$estado = isset($_POST['estado']) ? 1 : 0;

// Basic validation to avoid empty fields
if (empty($name)) {
    echo json_encode(['msg' => 'nomvacio']);
    exit;
}
if (empty($services)) {
    echo json_encode(['msg' => 'apevacio']);
    exit;
}
if (empty($deliverytime)) {
    echo json_encode(['msg' => 'telvacio']);
    exit;
}
if (empty($observations)) {
    echo json_encode(['msg' => 'emavacio']);
    exit;
}

try {
    // Insert into mode_bookings using PDO
    $stmt = dbQuery(
        "INSERT INTO mode_bookings (name, services, deliverytime, observations, estado) VALUES (?, ?, ?, ?, ?)",
        [$name, $services, $deliverytime, $observations, $estado]
    );
    echo json_encode(['msg' => 'ok']);
} catch (PDOException $e) {
    error_log("agregar.php insert failed: " . $e->getMessage());
    echo json_encode(['msg' => 'error', 'details' => htmlspecialchars($e->getMessage())]);
}
?>