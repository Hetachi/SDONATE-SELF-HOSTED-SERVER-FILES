<?php

	$currentPage = "forgotpassword";

	require('sessionname.php');

	if(!isset($_SESSION)){
	    session_start();
	}

	$_SESSION['returnurl'] = 'index.php';

	require_once('config.php');

	try {
		$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
		$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	} catch(PDOException $e){
		echo 'MySQL Error:' . $e->getMessage();
	exit();
	}

?>

<!DOCTYPE html>

<html>

	<head>
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/style.php">
		<link rel="stylesheet" href="css/quill.snow.css">
		<link href='font/fonts.css' rel='stylesheet' type='text/css'>
		<script src="js/jquery.js"></script>
		<script type="text/javascript" src="js/jqueryrotate.js"></script>
		<script src="js/chart.js"></script>
		<script src="js/quill.js"></script>
		<script src='https://www.google.com/recaptcha/api.js'></script>
		<meta charset="utf-8"/>
	</head>

	<body>

		<?php require('components/topnavbar.php'); ?>

		<div id="content-container">
			<ul id="hidden-list">
				<a href="index.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-home" style="margin-right: 10px;"></span><?= getLangString("home"); ?></li></a>
				<a href="packages.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-shopping-cart" style="margin-right: 10px;"></span><?= getLangString("store"); ?></li></a>

				<?php
					if(isset($_SESSION['admin'])){
						if($_SESSION['admin'] === true){
							print('<a href="dashboard.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-cog" style="margin-right: 10px;"></span>Admin</li></a>');
						}
					}

					if(!isset($_SESSION['username'])) {
						print('<a href="login.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span>' . getLangString("login") . '</li></a>');
					} else {
						print('<a href="account.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span>' . getLangString("account") . '</li></a>');
					}
				?>

			</ul>
			<div id="login-container">
				<form action="ajax/login.php" id="resetpassword-form" method="post">
					<p id="errorbox-title">Reset Password</p>
					<input type="hidden" name="login-type" id="type" value="resetpassword">
					<input type="hidden" name="reset-password-key" id="reset-password-key" value="<?php print($_GET['resetkey']); ?>">
					<input type="hidden" name="username" id="username" value="<?php print($_GET['username']); ?>">
					<p class="setting-title">Password</p>
					<input style="margin-bottom: 20px;" type="password" name="password" id="password" class="settings-text-input" placeholder="Password">
					<p class="setting-title">Confirm Password</p>
					<input style="margin-bottom: 20px;" type="password" name="confirmpassword" id="confirmpassword" class="settings-text-input" placeholder="Password">
					<div class="g-recaptcha" data-sitekey=<?php print('"' . $recaptchasitekey . '"') ?>></div>
					<button class="submit-button" type="submit" style="display: inline-block; margin-left: 0px;">Reset Password</button>
				</form>
			</div>
		</div>
		<div id="footer">
			<?php printFooter(); ?>
		</div>

		<script src="js/bootstrap.js"></script>
		<script src="js/main.js"></script>

		<script>

			function listenForSubmit(){

				$('#resetpassword-form').on('submit', function (e) {
					e.preventDefault();
					$.ajax({
						type: 'post',
						url: $(this).attr('action'),
						data: new FormData( this ),
						processData: false,
						contentType: false,
						success: function (data) {
							if($.trim(data)){
								grecaptcha.reset();
								$('#errorbox-content-1').remove();
								$('#errorbox-bottom-1').append('<div id="errorbox-content-1">' + data + '</div>');
								if($('#table-container-1').css('display') == 'none'){
									showError1();
								}
							} else {
								grecaptcha.reset();
								$('#errorbox-content-1').remove();
								$('#errorbox-bottom-1').append('Password successfully changed!');
								if($('#table-container-1').css('display') == 'none'){
									showError1();
								}
							}
						}
					});
				});

			}

			listenForSubmit();

		</script>
