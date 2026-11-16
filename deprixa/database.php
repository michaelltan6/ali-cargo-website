<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

$cfg = @include __DIR__ . '/config.local.php';
if (!is_array($cfg)) {
    error_log('config.local.php tidak ditemukan di deprixa/');
    die('Konfigurasi database belum dibuat.');
}

try {
    $dbConn = new PDO(
        $cfg['dsn'],
        $cfg['user'],
        $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check logs.");
}

function dbQuery($sql, $params = []) {
    global $dbConn;
    try {
        $stmt = $dbConn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        throw $e;
    }
}

function dbFetchAssoc($stmt) {
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function dbNumRows($stmt) {
    return $stmt->rowCount();
}
?>