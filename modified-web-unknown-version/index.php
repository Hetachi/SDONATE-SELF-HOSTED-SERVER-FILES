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

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='showdonators'");
	$sql->execute();
	$result = $sql->fetchAll(PDO::FETCH_ASSOC);
	$showDonators = $result[0]['value'];

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='showtotaldonations'");
	$sql->execute();
	$result = $sql->fetchAll(PDO::FETCH_ASSOC);
	$showTotal = $result[0]['value'];

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='themespinning'");
	$sql->execute();
	$result = $sql->fetchAll(PDO::FETCH_ASSOC);
	$spinningEnabled = $result[0]['value'];

	$sql = $dbcon->prepare("SELECT id, gamename, gameimg FROM games WHERE enabled = 1 ORDER BY gamename");
	$sql->execute();
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($results, "escapeHTML");
	$gamesEnabled = $sql->rowCount();

	$gamehtml = '';

	if($gamesEnabled > 1){

		switch($gamesEnabled){

			case 1:
				$colSize = 12;
			break;

			case 2:
				$colSize = 6;
			break;

			default:
				$colSize = 4;
			break;

		}

		foreach ($results as $key => $value){

			$gamehtml = $gamehtml . ' <div class="col-md-' . $colSize .'">
				<div class="game">
					<a href="packages.php?game=' . $results[$key]['id'] . '">
						<div class="game-img" style="background-image: url(\'' . 'img/games/' . $results[$key]['id'] . '/' . $results[$key]['gameimg'] . '\');"></div>
						<div class="game-name">' . $results[$key]['gamename'] . '</div>
					</a>
				</div>
			</div>';

		}

	} elseif($gamesEnabled === 1) {

		$gameid = $results[0]["id"];
		$placeholderImage = "img/games/" . $gameid . "/" . $results[0]["gameimg"];
		$sql = $dbcon->prepare("SELECT id, name, img FROM servers WHERE enabled = 1 AND game = " . $gameid . " ORDER BY name");
		$sql->execute();
		$results = $sql->fetchAll(PDO::FETCH_ASSOC);
		array_walk_recursive($results, "escapeHTML");
		$serversEnabled = $sql->rowCount();

		switch($serversEnabled){

			case 1:
				$colSize = 12;
			break;

			case 2:
				$colSize = 6;
			break;

			default:
				$colSize = 4;
			break;

		}

		foreach ($results as $key => $value){

			if(empty($results[$key]["img"])){
				$results[$key]["img"] = $placeholderImage;
			} else {
				$results[$key]["img"] = "img/servers/" . $results[$key]["id"] . "/" . $results[$key]["img"];
			}

			$gamehtml = $gamehtml . ' <div class="col-md-' . $colSize .'">
				<div class="game">
					<a href="packages.php?game=' . $gameid . '&server=' . $results[$key]['id'] . '">
						<div class="game-img" style="background-image: url(\'' . $results[$key]['img'] . '\');"></div>
						<div class="game-name">' . $results[$key]['name'] . '</div>
					</a>
				</div>
			</div>';

		}

	} else {
		$gamehtml = '<p style="width: 100%; margin-left: auto; margin-right: auto; font-size: 30px; text-align: center;">There are no games currently enabled!</p>';
	}

	if(isset($_GET['cancelpaypal'])){
		unset($_SESSION['params']);
		unset($_SESSION['paramsdisplay']);
		unset($_SESSION['vars']);
		unset($_SESSION['price']);
		unset($_SESSION['packagekey']);
		unset($_SESSION['paypaltoken']);
	}

?>

<!DOCTYPE html>

<html>

	<head>
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/style.php">
		<link href='font/fonts.css' rel='stylesheet' type='text/css'>
		<script src="js/jquery.js"></script>
		<?php
		if ($spinningEnabled === "true"){
			print('
				<script type="text/javascript" src="js/jqueryrotate.js"></script>
			');
		} else {
			print('
				<script type="text/javascript">var spinningOff = true;</script>
			');
		}
		?>
		<meta charset="utf-8"/>
		<title>SDonate Donation System</title>
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
			<div id="welcome-container">
				<div id="welcome-header">
					<?php
						$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='hometitle'");
						$sql->execute();
						$result = $sql->fetchAll(PDO::FETCH_ASSOC);
						print($result[0]['value']);
					?>
				</div>
				<div id="welcome-message">
					<?php
						$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='homeparagraph'");
						$sql->execute();
						$result = $sql->fetchAll(PDO::FETCH_ASSOC);
						print($result[0]['value']);
					?>
				</div>

				<?php
				if($showTotal === "1"){
					$sql = $dbcon->prepare("SELECT * FROM transactions");
					$sql->execute();
					$results = $sql->fetchAll(PDO::FETCH_ASSOC);
					$totalvalue = 0.00;
					foreach($results as $key => $value){
						if($results[$key]['status'] == 'complete' || $results[$key]['status'] == 'revoked'){
							$totalvalue = $totalvalue + $results[$key]['value'];
						}
					}
					print('<p style="margin-bottom: 50px; font-size: 40px; font-weight: 600;">' . getLangString("total-donations") . ": " . $currencysymbol . number_format((float)$totalvalue, 2, '.', '') . '</p>');
				}

				if($showDonators === "1"){

					$sql = $dbcon->prepare("SELECT * FROM transactions ORDER BY time DESC");
					$sql->execute();
					$transactions = $sql->fetchAll(PDO::FETCH_ASSOC);

					print('
					<div id="donator-statistics" style="display: block; max-width: 1300px; margin-left: auto; margin-right: auto;">
						<div class="row">
							<div class="col-md-6">
								<div class="dashboard-stat-small">
									<div class="statistics-title">' . getLangString("recent-donations") . '</div>
									<div class="statistics-content table-responsive">
										<table class="table">
											<thead>
												<tr>
													<th>' . getLangString("username") . '</th>
													<th>' . getLangString("value") . ' (' . $currencycode . ')</th>
												</tr>
											</thead>
											<tbody>');

					$got = 0;
					$i = 0;

					while($got < 5){
						if($i < count($transactions)){
							if(!empty($transactions[$i])){
								if($transactions[$i]["value"] != 0.00 AND ($transactions[$i]["status"] === "complete" OR $transactions[$i]["status"] === "revoked")){
									print('
									<tr>
										<td style="text-align: left;">' . htmlspecialchars($transactions[$i]["purchaser"], ENT_QUOTES, 'UTF-8') . '</td>
										<td style="text-align: left;">' . $transactions[$i]["value"] . '</td>
									</tr>
									');
									$got++;
								}
							}
							$i++;
						} else {
							for($j = 0; $j < (5 - $got); $j++){
								print('<tr><td>&nbsp;</td><td>&nbsp;</td></tr>');
							}
							$got = 5;
							break;
						}
					}

					print('
											</tbody>
										</table>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<div class="dashboard-stat-small">
									<div class="statistics-title">' . getLangString("top-donators") . '</div>
									<div class="statistics-content table-responsive">
										<table class="table">
											<thead>
												<tr>
													<th>' . getLangString("username") . '</th>
													<th>' . getLangString("value") . ' (' . $currencycode . ')</th>
												</tr>
											</thead>
											<tbody>');

					$got = 0;
					$i = 0;
					$users = [];

					foreach($transactions as $key => $value){
						if($value["status"] === "complete" OR $value["status"] === "revoked"){
							if(array_key_exists($value["purchaser"], $users)){
								$users[$value["purchaser"]] = $users[$value["purchaser"]] + $value["value"];
							} else {
								$users[$value["purchaser"]] = (float)$value["value"];
							}
						}
					}

					arsort($users, SORT_NUMERIC);

					$keys = array_keys($users);

					while($got < 5){
						if($i < count($users)){
							print('
							<tr>
								<td style="text-align: left;">' . $keys[$i] . '</td>
								<td style="text-align: left;">' . number_format($users[$keys[$i]], 2, '.', '') . '</td>
							</tr>
							');
							$got++;
							$i++;
						} else {
							for($j = 0; $j < (5 - $got); $j++){
								print('<tr><td>&nbsp;</td><td>&nbsp;</td></tr>');
							}
							$got = 5;
							break;
						}
					}

					print('
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
					');

				}
				?>

			</div>

			<div id="news-container" style="width: 100%; max-width: 1300px; margin-left: auto; margin-right: auto;">
				<?php

				if(count($news) > 0){
					print('<p style="font-size: 30px;">' . getLangString("news") . ':</p>');
					$value = $news[0];
					$date = parseDate($value["date"]);
					print('
					<div id="news-item" class="dashboard-stat-large">
						<div class="statistics-title">' . $value["title"] . '</div>
						<div class="statistics-content">
							<p style="font-size: 25px; padding: 10px;">' . $value["content"] . '</p>
							<p style="font-size: 20px; padding: 10px;">By ' . $value["author"] . ' on ' . $date . '</p>
						</div>
					</div>
					<a href="news.php"><div class="submit-button" style="margin-left: auto; margin-right: auto; position: relative; display: inline-block; left: 50%;  transform: translateX(-50%); -webkit-transform: translateX(-50%);">' . getLangString("view-all-news") . '</div></a>
					');
				}

				?>
			</div>

			<div class="container-fluid">
				<div class="row">
					<?php print($gamehtml); ?>
				</div>
			</div>
		</div>
		<div class="steam" style="width: 865px; margin:  0 auto; text-align: center;">
			<h3 class="steamh3" style="font-size: 30px; color: #5d9d39; text-align: center;">YOU CAN BUY WITH STEAM ITEMS AS WELL SEND TRADE OFFER TROUGH STEAM!</h3>
			<a href="https://steamcommunity.com/profiles/76561198157589509"><img class="steamlogo" style="background: black; padding: 10px;border-radius: 10px;" src="https://steamstore-a.akamaihd.net/public/shared/images/header/globalheader_logo.png"/></a>
			<p style="color: red;font-weight: bold;">IN DESCRIPTION OF THE TRADE WRITE WHICH PACKAGE YOU ARE BUYING!</p>
		</div>
		<div id="footer">
			<?php printFooter(); ?>
		</div>

		<script src="js/bootstrap.js"></script>
		<script src="js/main.js"></script>

	</body>

</html>
