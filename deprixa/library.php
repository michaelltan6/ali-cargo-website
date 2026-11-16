<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('database.php');

function isUser() {
    global $dbConn;
    if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_type'])) {
        error_log("Session variables user_name or user_type not set.");
        return false;
    }
    try {
        $stmt = dbQuery("SELECT COUNT(*) as cnt FROM manager_admin WHERE name = ? AND pwd = ?", [$_SESSION['user_name'], $_SESSION['pwd']]);
        $row = dbFetchAssoc($stmt);
        if ($row['cnt'] > 0) {
            return true;
        }
        error_log("User not found in manager_admin: " . $_SESSION['user_name']);
        return false;
    } catch (PDOException $e) {
        error_log("isUser query failed: " . $e->getMessage());
        return false;
    }
}

function verify_users($user, $password) {
    global $dbConn;
    if (empty($user) || empty($password)) {
        return "Email and password are required.";
    }
    try {
        $hashed_password = claves($password);
        $stmt = dbQuery("SELECT name, name_parson, 'Administrator' as user_type FROM manager_admin WHERE name = ? AND pwd = ?", [$user, $hashed_password]);
        $row = dbFetchAssoc($stmt);
        if ($row) {
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['pwd'] = $hashed_password;
            error_log("Admin login successful: " . $user);
            header("Location: deprixa/admin.php");
            exit;
        }
        error_log("Login failed for user: " . $user);
        return "Invalid email or password.";
    } catch (PDOException $e) {
        error_log("verify_users query failed: " . $e->getMessage());
        return "Login error: " . htmlspecialchars($e->getMessage());
    }
}
?>