<?php
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
require_once("database.php");
require_once('library.php');
requireAdmin();
function generatePagination(){
	$per_page = 100000000;
	//Calculating no of pages
	$sql = "SELECT * FROM courier";
	$result = dbQuery($sql);
	$count = dbNumRows($result);
	$pages = ceil($count/$per_page);
	$pageno = "<ul id=\"pagination\">";
	for($i=1; $i<=$pages; $i++)	{
		$pageno .= "<a class=\"pageno\" title=\"Page #. $i\" href=\"?page=$i\"><li id=\".$i.\">".$i."</li></a> ";
	}
	$pageno .= 	"</ul>";
	return $pageno;
}

function getPageData(){
	$per_page = 100000000;
	$page = max(1, (int)($_GET['page'] ?? 1));
	$start = ($page-1)*$per_page;
	$sql = "SELECT * FROM courier WHERE status_delivered = 'Delivered' and officename='".$_SESSION["user_type"]."' ORDER BY cid DESC LIMIT $start, $per_page";
	$result = dbQuery($sql);
	$records = array();
	while($row = dbFetchAssoc($result)){
		extract($row);
		$records[] = array("cid" => $cid,
			"cons_no" => $cons_no,
			"ship_name" => $ship_name,
			"rev_name" => $rev_name,
			"pick_date" => $pick_date,
			"pick_time" => $pick_time,
			"officename" => $officename,
			"status_delivered" => $status_delivered);
	}//while
	return $records;
}


?>