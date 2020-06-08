<?php

require_once('../config.php');

try {
	$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
	$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch(PDOException $e){
	echo 'MySQL Error:' . $e->getMessage();
exit();
}

$sql = $dbcon->prepare("SELECT value FROM settings");
$sql->execute();
$results = $sql->fetchAll(PDO::FETCH_ASSOC);
$maincolour = $results[4]['value'];
$secondarycolour = $results[5]['value'];
$circleImages = $results[12]['value'];
$maintheme = $results[13]['value'];
$mainfontcolor = $results[14]['value'];
$secondaryfontcolor = $results[15]['value'];
$mainfont = $results[16]['value'];

header("Content-type: text/css; charset: UTF-8");

if($maintheme == "1"){
	require('themes/grey.php');
} else {
	require('themes/main.php');
}

$customCSS = Settings::Get('customcss');

print($customCSS);

?>
