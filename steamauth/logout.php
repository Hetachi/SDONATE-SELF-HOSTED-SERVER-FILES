<?php
require('sessionname.php');
include("settings.php");
header("Location: ../".$steamauth['logoutpage']);
if(!isset($_SESSION)){
    session_start();
}
unset($_SESSION['steamid']);
unset($_SESSION['steam_uptodate']);
?>
