<?php

	$currentPage = "dashboard";

	require('sessionname.php');

	if(!isset($_SESSION)){
	    session_start();
	}

	if(!isset($_SESSION['admin'])){
		header('Location: index.php');
		exit();
	}

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
		<script src='js/tinymce/tinymce.min.js'></script>
		<meta charset="utf-8"/>
		<title>SDonate Donation System - Dashboard</title>
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
							print('<a href="dashboard.php"><li class="hidden-list-button active"><span class="glyphicon glyphicon-cog" style="margin-right: 10px;"></span>Admin</li></a>');
						}
					}

					if(!isset($_SESSION['username'])) {
						print('<a href="login.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span>' . getLangString("login") . '</li></a>');
					} else {
						print('<a href="account.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span>' . getLangString("account") . '</li></a>');
					}
				?>

			</ul>

			<div id="dashboard-container">
				<div id="side-navbar-container">
					<ul id="side-navbar">
						<a href="?action=stats"><li class="side-navbar-button"><span class="glyphicon glyphicon-stats" style="margin-right: 10px;"></span><?= getLangString("statistics"); ?></li></a>
						<a href="?action=settings"><li class="side-navbar-button"><span class="glyphicon glyphicon-cog" style="margin-right: 10px;"></span><?= getLangString("general-settings") ?></li></a>
						<a href="?action=games"><li class="side-navbar-button"><span class="glyphicon glyphicon-cd" style="margin-right: 10px;"></span><?= getLangString("games") ?></li></a>
						<a href="?action=servers"><li class="side-navbar-button"><span class="glyphicon glyphicon-list" style="margin-right: 10px;"></span><?= getLangString("servers") ?></li></a>
						<a href="?action=packages"><li class="side-navbar-button"><span class="glyphicon glyphicon-shopping-cart" style="margin-right: 10px;"></span><?= getLangString("packages") ?></li></a>
						<a href="?action=users"><li class="side-navbar-button"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span><?= getLangString("users") ?></li></a>
						<a href="?action=theme"><li class="side-navbar-button"><span class="glyphicon glyphicon-edit" style="margin-right: 10px;"></span><?= getLangString("theme-editor") ?></li></a>
						<a href="?action=email"><li class="side-navbar-button"><span class="glyphicon glyphicon-envelope" style="margin-right: 10px;"></span><?= getLangString("email") ?></li></a>
						<a href="?action=tos"><li class="side-navbar-button"><span class="glyphicon glyphicon-align-center" style="margin-right: 10px;"></span><?= getLangString("tos") ?></li></a>
						<a href="?action=sales"><li class="side-navbar-button"><span class="glyphicon glyphicon-tag" style="margin-right: 10px;"></span>Sales/Coupons</li></a>
						<a href="?action=news"><li class="side-navbar-button"><span class="glyphicon glyphicon-calendar" style="margin-right: 10px;"></span><?= getLangString("news") ?></li></a>
						<a href="?action=logs"><li class="side-navbar-button"><span class="glyphicon glyphicon-wrench" style="margin-right: 10px;"></span>Debugging</li>
						<a href="?action=info"><li class="side-navbar-button"><span class="glyphicon glyphicon-info-sign" style="margin-right: 10px;"></span><?= getLangString("info"); ?></li></a>
					</ul>
				</div>
				<select class="dropdown" id="dashboard-menu-dropdown">
					<option class="dashboard-menu-dropdown-option" value="stats"><?= getLangString("statistics"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="settings"><?= getLangString("general-settings"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="games"><?= getLangString("games"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="servers"><?= getLangString("servers"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="packages"><?= getLangString("packages"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="users"><?= getLangString("users"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="theme"><?= getLangString("theme-editor"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="email"><?= getLangString("email"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="tos"><?= getLangString("tos"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="sales">Sales/Coupons</option>
					<option class="dashboard-menu-dropdown-option" value="news"><?= getLangString("news"); ?></option>
					<option class="dashboard-menu-dropdown-option" value="logs">Debugging</option>
					<option class="dashboard-menu-dropdown-option" value="info"><?= getLangString("info"); ?></option>
				</select>

				<script>
					<?php
						if(isset($_GET['action'])){
							$action = $_GET['action'];
						} else {
							$action = 'stats';
						}
					?>
					var currentPage = <?php print("'" . $action . "';"); ?>
					$("select option[value=" + currentPage +"]").attr("selected","selected");

					$('#dashboard-menu-dropdown').change(function(){
						window.location = '?action=' + $(this).val();
					})
				</script>

<?php

	if(isset($_GET['action'])){
		$action = $_GET['action'];
	} else {
		$action = 'stats';
	}

	switch($action){
		case 'settings':
			require('components/dashboard/settings.php');
			break;

		case 'games':
			require('components/dashboard/games.php');
			break;

		case 'servers':
			require('components/dashboard/servers.php');
			break;

		case 'packages':
			require('components/dashboard/packages.php');
			break;

		case 'users':
			if(isset($_GET['id'])){
				require('components/dashboard/usersusername.php');
			} else {
				require('components/dashboard/users.php');
			}
			break;

		case 'email':
			require('components/dashboard/email.php');
			break;

		case 'theme':
			require('components/dashboard/theme.php');
			break;

		case 'tos':
			require('components/dashboard/tos.php');
			break;

		case 'sales':
			require('components/dashboard/sales.php');
			break;

		case 'news':
			require('components/dashboard/news.php');
			break;

		case 'logs':
			require('components/dashboard/logs.php');
			break;

		case 'info':
			require('components/dashboard/info.php');
			break;

		default:
			require('components/dashboard/stats.php');
			break;

	}

?>

			</div>
		</div>
		<div id="footer">
			<?php printFooter(); ?>
		</div>

		<script src="js/bootstrap.js"></script>
		<script src="js/main.js"></script>
		<script>

			function listenForSubmit(){
				$('form').on('submit', function (e) {
					e.preventDefault();
					$.ajax({
						type: 'post',
						url: $(this).attr('action'),
						data: new FormData( this ),
						processData: false,
						contentType: false,
						success: function (data) {
							if($.trim(data)){
								$('#errorbox-content-1').remove();
								$('#errorbox-bottom-1').append('<div id="errorbox-content">' + data + '</div>');
								if($('#table-container-1').css('display') == 'none'){
									showError1();
								}
							} else {
								submissionSuccess();
							}
						}
					});
				});
			}

			function dashboardMenu(){
				if(window.innerWidth < 768){
					$('#side-navbar-container').hide();
					$('#dashboard-content-container').css('margin-left', '20px');
					$('#dashboard-menu-dropdown').show();
				} else {
					$('#dashboard-menu-dropdown').hide();
					$('#side-navbar-container').show();
					$('#dashboard-content-container').css('margin-left', '290px');
				}
			}

			window.addEventListener("load", function(){
				dashboardMenu();
			});

			window.addEventListener("resize", function(){
				dashboardMenu();
			});

			listenForSubmit();

		</script>

	</body>
