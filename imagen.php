<?php 
header("Content-type: image/png");
$im=@imagecreatetruecolor(120, 42);
$color_fondo=imagecolorallocate($im,102,102,153);#color negro
$color_texto=imagecolorallocate($im,255,255,255);#color blanco
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
$key='';
for($x=15; $x<=95; $x+=20){
	$key.=($num=rand(0,9));
	imagechar($im,rand(3,5),$x,rand(2,14),$num,$color_texto);
}
imagepng($im);
imagedestroy($im);
$_SESSION['img_number']=$key;
?> 