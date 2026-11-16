<?php
require_once('database.php');

function getParam($param, $default) {
    return isset($param) ? $param : $default;
}

function sqlValue($value, $type) {
    if ($value === null || $value === '') {
        return 'NULL';
    }
    switch ($type) {
        case "text":
        case "date":
            return "'" . addslashes($value) . "'";
        case "int":
            return intval($value);
        case "double":
            return doubleval($value);
        default:
            return "'" . addslashes($value) . "'";
    }
}

function nombre($doc) {
    global $dbConn;
    try {
        $stmt = dbQuery("SELECT name_parson FROM manager_admin WHERE pwd = ?", [$doc]);
        $row = dbFetchAssoc($stmt);
        return $row ? $row['name_parson'] : 'ERROR';
    } catch (PDOException $e) {
        error_log("Error in nombre: " . $e->getMessage());
        return 'ERROR';
    }
}

function nombre1($doc, $user) {
    global $dbConn;
    try {
        $stmt = dbQuery("SELECT name_parson FROM manager_user WHERE name = ? AND pwd = ?", [$user, $doc]);
        $row = dbFetchAssoc($stmt);
        return $row ? $row['name_parson'] : 'ERROR';
    } catch (PDOException $e) {
        error_log("Error in nombre1: " . $e->getMessage());
        return 'ERROR';
    }
}

function consultar($campo, $tabla, $where) {
    global $dbConn;
    try {
        $stmt = dbQuery("SELECT * FROM $tabla WHERE $where");
        $row = dbFetchAssoc($stmt);
        return $row ? $row[$campo] : '';
    } catch (PDOException $e) {
        error_log("Error in consultar: " . $e->getMessage());
        return '';
    }
}

function claves($pwd) {
    $llave1 = 'Gbj49j4jljdh2323n5nNnHHnFFG5JJ';
    $llave2 = 'HNFkkstjotBhrMi489ndVjdllu75jH';
    $pwd2 = strrev($pwd);
    return sha1($llave1 . $llave2 . $pwd . $llave1 . $pwd2 . $pwd . $llave2 . $pwd2 . $llave2 . $llave1 . $pwd2);
}

// ---- Password: hash modern (bcrypt) dengan dukungan hash lama (sha1) ----
// Hash lama tetap bisa dipakai login; begitu berhasil, otomatis diganti ke bcrypt.

function ali_is_modern_hash($stored) {
    return is_string($stored) && strncmp($stored, '$2y$', 4) === 0;
}

// Pastikan kolom pwd cukup panjang untuk bcrypt (60 karakter). Hasil di-cache per request.
function ali_pwd_column_ok($table) {
    static $cache = array();
    if (isset($cache[$table])) { return $cache[$table]; }
    if (!in_array($table, array('manager_admin', 'manager_user'), true)) { return false; }
    $ok = false;
    try {
        global $dbConn;
        $driver = ($dbConn instanceof PDO) ? $dbConn->getAttribute(PDO::ATTR_DRIVER_NAME) : '';
        if ($driver === 'sqlite') {
            $ok = true;
        } else {
            $stmt = dbQuery("SELECT CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'pwd'", array($table));
            $row = dbFetchAssoc($stmt);
            $len = $row ? (int)$row['len'] : 0;
            $ok = ($len >= 60);
            if (!$ok && $row) {
                try {
                    dbQuery("ALTER TABLE `$table` MODIFY `pwd` VARCHAR(255) NOT NULL");
                    $ok = true;
                } catch (Exception $e) {
                    error_log('ali_pwd_column_ok alter gagal: ' . $e->getMessage());
                }
            }
        }
    } catch (Exception $e) {
        error_log('ali_pwd_column_ok: ' . $e->getMessage());
    }
    return $cache[$table] = $ok;
}

// Hash untuk disimpan. Kalau kolom belum bisa menampung bcrypt, pakai cara lama supaya login tidak rusak.
function ali_make_hash($plain, $table) {
    if (ali_pwd_column_ok($table)) {
        return password_hash($plain, PASSWORD_BCRYPT);
    }
    return claves($plain);
}

// Cocokkan password dengan hash tersimpan (bcrypt atau sha1 lama).
function ali_check_password($plain, $stored) {
    if (!is_string($stored) || $stored === '') { return false; }
    if (ali_is_modern_hash($stored)) { return password_verify($plain, $stored); }
    return hash_equals($stored, claves($plain));
}

// Sidik jari hash tersimpan. Ini yang disimpan di sesi, supaya hash asli tidak pernah ada di sesi
// dan sesi otomatis tidak berlaku lagi kalau password diganti.
function ali_pwd_fp($stored) {
    return hash('sha256', 'ali-session|' . $stored);
}

// Ganti hash lama ke bcrypt setelah login berhasil. Mengembalikan hash yang berlaku sekarang.
function ali_upgrade_hash($table, $name, $plain, $stored) {
    if (ali_is_modern_hash($stored) && !password_needs_rehash($stored, PASSWORD_BCRYPT)) { return $stored; }
    if (!ali_pwd_column_ok($table)) { return $stored; }
    try {
        $new = password_hash($plain, PASSWORD_BCRYPT);
        $stmt = dbQuery("UPDATE `$table` SET pwd = ? WHERE name = ? AND pwd = ?", array($new, $name, $stored));
        return $new;
    } catch (Exception $e) {
        error_log('ali_upgrade_hash: ' . $e->getMessage());
        return $stored;
    }
}

function get($valor, $tabla, $campo) {
    global $dbConn;
    try {
        $stmt = dbQuery("SELECT $campo FROM $tabla");
        while ($row = dbFetchAssoc($stmt)) {
            if ($valor == claves($row[$campo])) {
                return $row[$campo];
            }
        }
        return 'error';
    } catch (PDOException $e) {
        error_log("Error in get: " . $e->getMessage());
        return 'error';
    }
}

function mensajes($mensaje, $user) {
    if ($user == 'verde') {
        $user = 'alert alert-success';
    } elseif ($user == 'rojo') {
        $user = 'alert alert-error';
    } elseif ($user == 'azul') {
        $user = 'alert alert-info';
    }
    return '<div class="' . $user . '" align="center">
              <button type="button" class="close" data-dismiss="alert">×</button>
              <strong>' . $mensaje . '</strong>
            </div>';
}

function formato($valor) {
    return number_format($valor, 2, '.', ',');
}

// New function to sanitize input
function limpiar($value) {
    if (!is_scalar($value)) {
        return '';
    }
    $value = trim($value);
    $value = strip_tags($value); // Remove HTML tags
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); // Escape special characters
    return $value;
}
?>