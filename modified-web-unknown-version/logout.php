<?php

require('sessionname.php');

if(!isset($_SESSION)){
	session_start();
}

if(isset($_SESSION['username'])){
	session_destroy();
}
header("Location: index.php");

?>
