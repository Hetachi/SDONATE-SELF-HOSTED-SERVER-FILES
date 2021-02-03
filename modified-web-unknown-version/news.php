<?php

	require('sessionname.php');

	if(!isset($_SESSION)){
	    session_start();
	}

	require_once('config.php');

	require 'maintenance.php';

	try {
		$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
		$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	} catch(PDOException $e){
		echo 'MySQL Error:' . $e->getMessage();
	exit();
	}

	$sql = $dbcon->prepare("SELECT * FROM news ORDER BY date DESC");
	$sql->execute();
	$news = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($news, "escapeHTML");

?>

<!DOCTYPE html>

<html>

	<head>
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/style.php">
		<link href='font/fonts.css' rel='stylesheet' type='text/css'>
		<script src="js/jquery.js"></script>
		<meta charset="utf-8"/>
		<title>SDonate Donation System - News</title>
	</head>

	<body>

		<?php require('components/topnavbar.php'); ?>

        <div id="content-container">
			<ul id="hidden-list">
				<a href="index.php"><li class="hidden-list-button active"><span class="glyphicon glyphicon-home" style="margin-right: 10px;"></span><?= getLangString("home"); ?></li></a>
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

			<div id="news-container" style="width: 100%; max-width: 1300px; margin-left: auto; margin-right: auto;">
				<?php

				if(count($news) > 0){
					print('<p style="font-size: 30px;">' . getLangString("news") . ':</p>');
                    foreach($news as $key => $value){
    					$date = parseDate($value["date"]);
    					print('
    					<div id="news-item" class="dashboard-stat-large">
    						<div class="statistics-title">' . $value["title"] . '</div>
    						<div class="statistics-content">
    							<p style="font-size: 25px; padding: 10px;">' . $value["content"] . '</p>
    							<p style="font-size: 20px; padding: 10px;">By ' . $value["author"] . ' on ' . $date . '</p>
    						</div>
    					</div>
    					');
                    }
                    print('<a href="index.php"><div class="submit-button" style="margin-left: auto; margin-right: auto; position: relative; display: inline-block; left: 50%;  transform: translateX(-50%); -webkit-transform: translateX(-50%);">' . getLangString("back") . '</div></a>');
				} else {
                    print('<p style="font-size: 30px;">' . getLangString("no-news") . '</p>');
                }

				?>
			</div>

		</div>
		<div id="footer">
			<?php printFooter(); ?>
		</div>

		<script src="js/bootstrap.js"></script>
		<script src="js/main.js"></script>

	</body>

</html>
