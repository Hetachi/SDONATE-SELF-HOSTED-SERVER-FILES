<?php

	try {
		$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
		$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	} catch(PDOException $e){
		echo 'MySQL Error:' . $e->getMessage();
	exit();
	}

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='maintenancemode'");
	$sql->execute();
	$result = $sql->fetchAll(PDO::FETCH_ASSOC);	$maintenanceEnabled = $result[0]['value'];

	if($maintenanceEnabled === "1"){

		$currentPage = "maintenance";

		print('
			<!DOCTYPE html>
			<html>

				<head>
					<link rel="stylesheet" href="css/bootstrap.min.css">
					<link rel="stylesheet" href="css/style.php">
					<link href="font/fonts.css" rel="stylesheet" type="text/css">
					<script src="js/jquery.js"></script>
					<meta charset="utf-8"/>
					<title>SDonate Donation System</title>
				</head>

				<body>');

		require('components/topnavbar.php');

		print('
					<div id="welcome-container">
						<div id="welcome-header">This store is currently closed for maintenance.</div>
						<div id="welcome-message">You have not reached this page in error, this site is undergoing planned maintenance and should return shortly. Please come back at a later time!</div>
					</div>
					<div id="footer">');
		printFooter();
		print('
					</div>

					<script>
						function positionMessage(){
							var messageHeight = $("#welcome-container").height();
							var windowHeight = $(window).height();
							var margin = (windowHeight - messageHeight) / 2;
							$("#welcome-container").css("marginTop", margin);
						}

						window.addEventListener("load", function(){
							positionMessage();
						});

						window.addEventListener("resize", function(){
							positionMessage();
						});
					</script>
					<script src="js/main.js"></script>
				</body>
				<script src="js/bootstrap.js"></script>
		');

		exit();

	}

?>
