<?php
require_once __DIR__ . '/../deprixa/portal-lib.php';
unset($_SESSION['cust_uid'], $_SESSION['cust_fp'], $_SESSION['pt_csrf']);
session_regenerate_id(true);
header('Location: /portal/login.php');
exit;
