<?php

	$currentPage = "starpass";

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

    $sql = $dbcon->prepare("SELECT * FROM settings WHERE setting='starpassenabled'");
    $sql->execute();
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    $starpassEnabled = $result[0]['value'];

    if($starpassEnabled !== "1"){
        header('Location: index.php');
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

					if(!isset($_SESSION['username'])) { ?>
						<a href="login.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span><?= getLangString("login") ?></li></a>
					<?php } else { ?>
						<a href="account.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span><?= getLangString("account") ?></li></a>
					<?php } ?>
				?>

			</ul>
            <?php
                if($starpassEnabled == "1"){
                    $sql = $dbcon->prepare("SELECT * FROM settings WHERE setting='starpasscode'");
                    $sql->execute();
                    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
                    $starpassCode = $result[0]['value'];
                    $pubID = get_string_between($starpassCode, 'error_code2.php?idd=', '&idp=');
            ?>
                        <div class="statistics-box" style="margin-top: 30px;">
                            <div class="statistics-title">StarPass</div>
                            <div class="statistics-content">
                                <?= getLangString("add-€3-credit") ?><br>
                                <div id="starpass_<?= $pubID ?>"></div><script type="text/javascript" src="https://script.starpass.fr/script.php?idd=<?= $pubID ?>&amp;verif_en_php=1&amp;datas="></script><noscript>Please activate Javascript on your internet browser.<br /><a href="http://www.starpass.fr/">StarPass Micro-payment</a></noscript>
                            </div>
                        </div>
            <?php } ?>
        </div>
        <div id="footer">
			<?php printFooter(); ?>
		</div>

		<script src="js/bootstrap.js"></script>
		<script src="js/main.js"></script>
        <script>
            <?php
                if(!isset($_SESSION['username'])){
                    print("
						$('#errorbox-content').remove();
						$('#errorbox-bottom').append('You are not signed in, you must sign in for credit to apply to your account.');
						if($('#table-container').css('display') == 'none'){
							showError();
						}
					");
                }
            ?>
        </script>

	</body>

</html>
