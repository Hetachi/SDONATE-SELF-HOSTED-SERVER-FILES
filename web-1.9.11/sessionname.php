<?php
    require_once(dirname(__FILE__) . "/config.php");
    $sessionName = substr(strrchr(__DIR__, DIRECTORY_SEPARATOR), 1);
    if(empty($sessionName) || $_SERVER['SERVER_NAME'] !== "sdonate.com"){
        $sessionName = "sdonate";
    }
    session_name($sessionName);
    if(!isset($_SESSION)){
	    session_start();
	}
?>
