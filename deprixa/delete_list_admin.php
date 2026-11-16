<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once('database.php');

if (isset($_GET['action']) && $_GET['action'] === 'del' && isset($_GET['cid'])) {
    $cid = intval($_GET['cid']);

    try {
        $stmt = $dbConn->prepare("DELETE FROM courier WHERE cid = :cid");
        $stmt->bindParam(':cid', $cid, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    } catch (PDOException $e) {
        echo "❌ Gagal menghapus data. Error: " . $e->getMessage();
    }
} else {
    echo "❌ Parameter tidak lengkap.";
}
?>