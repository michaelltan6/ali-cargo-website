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