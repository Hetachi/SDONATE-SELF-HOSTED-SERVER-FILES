<?php

	$currentPage = "login";

	require 'steamauth/steamauth.php';

	if(!isset($_SESSION['returnurl'])){
		$_SESSION['returnurl'] = 'index.php';
	}

	require_once('config.php');

	try {
		$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
		$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	} catch(PDOException $e){
		echo 'MySQL Error:' . $e->getMessage();
	exit();
	}

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='loginmode'");
	$sql->execute();
	$result = $sql->fetchAll(PDO::FETCH_ASSOC);
	if($result[0]['value'] == 1 && !isset($_GET['login'])){
		header('Location: login.php?login');
		exit();
	}

?>

<!DOCTYPE html>

<html>

	<head>
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/style.php">
		<link href='font/fonts.css' rel='stylesheet' type='text/css'>
		<script src="js/jquery.js"></script>
		<script type="text/javascript" src="js/jqueryrotate.js"></script>
		<script src='https://www.google.com/recaptcha/api.js'></script>
		<meta charset="utf-8"/>
		<title>SDonate Donation System</title>
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
						print('<a href="login.php"><li class="hidden-list-button active"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span>' . getLangString("login") . '</li></a>');
					} else {
						print('<a href="account.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span>' . getLangString("account") . '</li></a>');
					}
				?>

			</ul>
			<div id="login-container">
				<form action="ajax/login.php" id="login-form" method="post">
					<p id="errorbox-title">Log In</p>
					<input type="hidden" name="login-type" id="type" value="login">
					<p class="setting-title">Username</p>
					<input style="margin-bottom: 20px;" type="text" name="username" id="username" class="settings-text-input" placeholder="Username">
					<p class="setting-title">Password</p>
					<input style="margin-bottom: 20px;" type="password" name="password" id="password" class="settings-text-input" placeholder="Password">
					<a class="underlined-link" href="#" onclick="forgotPassword();" style="display: block; margin-bottom: 20px; font-size: 20px;">Forgotten your password?</a>
					<?php if(!empty($recaptchasitekey) AND !empty($recaptchasecretkey)){print('<div class="g-recaptcha" data-sitekey="' . $recaptchasitekey . '"></div>');} ?>
					<button class="submit-button" type="button" name="login" style="display: inline-block; margin-left: 0px;" onclick="loginUser();">Log In</button>
					<button class="submit-button" type="button" name="register" style="display: inline-block; margin-left: 0px; float: right;" onclick="registerUser();">Register</button>
					<p class="setting-title" style="text-align: center;">Or</p>
				</form>
				<div id="steam-login-container">
					<?php loginbutton('rectangle'); ?>
				</div>
			</div>
		</div>
		<div id="footer">
			<?php printFooter(); ?>
		</div>

		<script src="js/bootstrap.js"></script>
		<script src="js/main.js"></script>

		<script>

			var registerCaptcha;

			function forgotPassword(){
				var html = '<form action="ajax/forgotpassword.php" id="password-form" method="post">\n' +
						'<p id="errorbox-title">Retrieve Password</p>\n' +
						'<input type="hidden" name="forgotpassword" id="type" value="forgotpassword">\n' +
						'<p class="setting-title">Username</p>\n' +
						'<input style="margin-bottom: 20px;" type="text" name="username" id="username" class="settings-text-input" placeholder="Username">\n' +
						'<button class="submit-button" type="submit" name="retrievepassword" style="display: block; margin-left: 0px;">Retrieve Password</button>\n' +
					'</form>';
				showError(html);
				listenForForgotSubmit();
			}

			function loginUser(){
				$('#login-form').submit();
				addLoadingCircle($('#login-container'));
			}

			function registerUser(){
				var html = '<form action="ajax/login.php" id="register-form" method="post">\n' +
						'<p id="errorbox-title">Register</p>\n' +
						'<input type="hidden" name="login-type" id="type" value="register">\n' +
						'<p class="setting-title">Username</p>\n' +
						'<input style="margin-bottom: 20px;" type="text" name="username" id="username" class="settings-text-input" placeholder="Username">\n' +
						'<p class="setting-title">Email Address</p>\n' +
						'<input style="margin-bottom: 20px;" type="text" name="email" id="email" class="settings-text-input" placeholder="Email Address">\n' +
						'<p class="setting-title">Password</p>\n' +
						'<input style="margin-bottom: 20px;" type="password" name="password" id="password" class="settings-text-input" placeholder="Password">\n' +
						'<p class="setting-title">Confirm Password</p>\n' +
						'<input style="margin-bottom: 20px;" type="password" name="confirm-password" id="confirm-password" class="settings-text-input" placeholder="Confirm Password">\n' +
						<?php if(!empty($recaptchasitekey) AND !empty($recaptchasecretkey)){print('\'<div id="register-captcha" class="g-recaptcha" data-sitekey="' . $recaptchasitekey . '"></div>\\n\' +');} ?>
						'<button class="submit-button" type="submit" name="register" style="display: block; margin-left: 0px;">Register</button>\n' +
					'</form>';
				showError(html);
				<?php
					if(!empty($recaptchasitekey) AND !empty($recaptchasecretkey)){
						print("
							registerCaptcha = grecaptcha.render('register-captcha', {
								'sitekey' : '" . $recaptchasitekey . "'
							});");
					}
				?>
				listenForRegisterSubmit();
			}

			function listenForSubmit(){

				$('#login-form').on('submit', function (e) {
					e.preventDefault();
					$.ajax({
						type: 'post',
						url: $(this).attr('action'),
						data: new FormData( this ),
						processData: false,
						contentType: false,
						success: function (data) {
							if($.trim(data)){
								removeLoadingCircle($('#login-container'));
								<?php if(!empty($recaptchasitekey) AND !empty($recaptchasecretkey)){print('grecaptcha.reset();');}?>
								$('#errorbox-content-1').remove();
								$('#errorbox-bottom-1').append('<div id="errorbox-content-1">' + data + '</div>');
								if($('#table-container-1').css('display') == 'none'){
									showError1();
								}
							} else {
								window.location.replace(<?php print('"' . $_SESSION['returnurl'] . '"'); ?>);
							}
						}
					});
				});

			}

			function listenForRegisterSubmit(){

				$('#register-form').on('submit', function (e) {
					e.preventDefault();
					addLoadingCircle($('#register-form'));
					$.ajax({
						type: 'post',
						url: $(this).attr('action'),
						data: new FormData( this ),
						processData: false,
						contentType: false,
						success: function (data) {
							if($.trim(data)){
								removeLoadingCircle($('#register-form'));
								<?php if(!empty($recaptchasitekey) AND !empty($recaptchasecretkey)){print('grecaptcha.reset(registerCaptcha);');}?>
								$('#errorbox-content-1').remove();
								$('#errorbox-bottom-1').append('<div id="errorbox-content-1">' + data + '</div>');
								if($('#table-container-1').css('display') == 'none'){
									showError1();
								}
							} else {
								window.location.replace(<?php print('"' . $_SESSION['returnurl'] . '"'); ?>);
							}
						}
					});
				});

			}

			function listenForForgotSubmit(){

				$('#password-form').on('submit', function (e) {
					e.preventDefault();
					addLoadingCircle($('#password-form'));
					$.ajax({
						type: 'post',
						url: $(this).attr('action'),
						data: new FormData( this ),
						processData: false,
						contentType: false,
						success: function (data) {
							if($.trim(data)){
								removeLoadingCircle($('#password-form'));
								$('#errorbox-content-1').remove();
								$('#errorbox-bottom-1').append('<div id="errorbox-content-1">' + data + '</div>');
								if($('#table-container-1').css('display') == 'none'){
									showError1();
								}
							} else {
								removeLoadingCircle($('#password-form'));
								$('#errorbox-content-1').remove();
								$('#errorbox-bottom-1').append('If a valid email is linked to this account an email has been sent to that address.');
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
	</body>

</html>
