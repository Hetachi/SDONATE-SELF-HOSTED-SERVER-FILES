<?php

	$currentPage = "store";

	require('sessionname.php');
	require("require/classes.php");

	if(!isset($_SESSION)){
	    session_start();
	}

	ob_start();

	require_once('config.php');

	require 'maintenance.php';

	try {
		$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
		$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	} catch(PDOException $e){
		echo 'MySQL Error:' . $e->getMessage();
	exit();
	}

	if(isset($_GET['game'])){
		if(isset($_GET['server'])){
			if(isset($_GET['package'])){
				$package = $_GET['package'];
			} else {
				$server = $_GET['server'];
			}
		} else {
			$game = $_GET['game'];
		}
	}

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='tos'");
	$sql->execute();
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	$tos = addslashes($results[0]['value']);
	$tos = str_replace(array("\n", "\r"), '', $tos);

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='themespinning'");
	$sql->execute();
	$result = $sql->fetchAll(PDO::FETCH_ASSOC);
	$spinningEnabled = $result[0]['value'];

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='paypalbutton'");
	$sql->execute();
	$result = $sql->fetch(PDO::FETCH_ASSOC);
	$buttonType = $result['value'];

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='paypalsandbox'");
	$sql->execute();
	$result = $sql->fetch(PDO::FETCH_ASSOC);
	$paypalURL = "https://www.paypal.com/cgi-bin/webscr";
	if($result['value'] == "1"){
		$paypalURL = "https://sandbox.paypal.com/cgi-bin/webscr";
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
				<a href="index.php"><li class="hidden-list-button"><span class="glyphicon glyphicon-home" style="margin-right: 10px;"></span><?= getLangString("home"); ?></li></a>
				<a href="packages.php"><li class="hidden-list-button active"><span class="glyphicon glyphicon-shopping-cart" style="margin-right: 10px;"></span><?= getLangString("store"); ?></li></a>

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
			<div id="content">
				<div id="content-container">
				<?php

					if(isset($_GET['game']) && !isset($_GET['server'])){
						$sql = $dbcon->prepare("SELECT gamename FROM games WHERE id=:id AND enabled=:enabled");
						$values = array(':id' => $_GET['game'], ':enabled' => '1');
						$sql->execute($values);
						$gamecount = $sql->rowCount();

						if($gamecount === 1){

							$results = $sql->fetchAll(PDO::FETCH_ASSOC);

							$gamename = $results[0]['gamename'];
							$sql = $dbcon->prepare("SELECT id, name, img FROM servers WHERE game=:game AND enabled=:enabled ORDER BY name");
							$values = array(':game' => $_GET['game'], ':enabled' => 1);
							$sql->execute($values);
							$servercount = $sql->rowCount();

							if($servercount > 0){

								print('
									<div id="server-container">
										<div id="welcome-header">
											' . getLangString("servers-for") . $gamename . ':
										</div>
								');
								$results = $sql->fetchAll(PDO::FETCH_ASSOC);
								array_walk_recursive($results, "escapeHTML");

								foreach ($results as $key => $value) {
									if(empty($results[$key]['img'])){

										$sql = $dbcon->prepare("SELECT id, gameimg FROM games WHERE gamename=:gamename");
										$values = array(':gamename' => $gamename);
										$sql->execute($values);
										$gameresult = $sql->fetchAll(PDO::FETCH_NUM);

										$results[$key]['img'] = 'img/games/' . $gameresult[0][0] . '/' . $gameresult[0][1];

									} else {
										$results[$key]['img'] = 'img/servers/' . $results[$key]['id'] . '/' . $results[$key]['img'];
									}
								}

								$numServers = count($results);

								switch($numServers){
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

								$serverhtml = '';

								foreach ($results as $key => $value) {
									$serverhtml = $serverhtml . ' <div class="col-md-' . $colSize .'">
										<div class="game">
											<a href="packages.php?game=' . $_GET['game'] . '&server=' . $results[$key]['id'] . '">
												<div class="game-img" style="background-image: url(\'' . $results[$key]['img'] . '\');"></div>
												<div class="game-name">' . $results[$key]['name'] . '</div>
											</a>
										</div>
									</div>';
								}

								print('
										<div class="container-fluid">
											<div class="row">
												' . $serverhtml . '
												<div class="col-md-12"><button class="submit-button" onclick="goBack(\'game\');" style="margin-left: auto; margin-right: auto;">' . getLangString("back") . '</button></div>
											</div>
										</div>
									</div>
								');
							} else {
								print('
									<div id="server-container">
										<p>' . getLangString("no-servers-enabled") . $gamename . '!</p>
										<div class="col-md-12"><button class="submit-button" onclick="goBack(\'game\');" style="margin-left: auto; margin-right: auto;">' . getLangString("back") . '</button></div>
									</div>
								');
							}
						} else {
							ob_end_clean();
							header('Location: index.php');
							exit();
						}
					} elseif (isset($_GET['game']) && isset($_GET['server'])) {

						$gamesql = $dbcon->prepare("SELECT gamename, usernametype FROM games WHERE id=:id AND enabled=:enabled");
						$values = array(':id' => $_GET['game'], ':enabled' => '1');
						$gamesql->execute($values);
						$gamecount = $gamesql->rowCount();

						$salesql = $dbcon->prepare("SELECT * FROM sales WHERE starts <= NOW() AND ends >= NOW()");
						$salesql->execute();
						$sales = $salesql->fetchAll(PDO::FETCH_ASSOC);

						$sql = $dbcon->prepare("SELECT name FROM servers WHERE id=:id AND enabled=:enabled");
						$values = array(':id' => $_GET['server'], ':enabled' => '1');
						$sql->execute($values);
						$servercount = $sql->rowCount();

						if($gamecount === 1 && $servercount === 1){

							$gameresult = $gamesql->fetchAll(PDO::FETCH_ASSOC);

							$gamename = $gameresult[0]['gamename'];
							$usernametype = $gameresult[0]['usernametype'];
							$results = $sql->fetchAll(PDO::FETCH_ASSOC);
							array_walk_recursive($results, "escapeHTML");

							$servername = $results[0]['name'];
							$sql = $dbcon->prepare("SELECT * FROM packages WHERE game=:game AND enabled=:enabled ORDER BY sortorder, title");
							$values = array(':game' => $_GET['game'], ':enabled' => 1);
							$sql->execute($values);
							$packages = $sql->fetchAll(PDO::FETCH_ASSOC);
							array_walk_recursive($packages, "escapeHTML");

							$packagecount = 0;

							foreach ($packages as $key => $value) {

								$packageservers = json_decode(htmlspecialchars_decode($packages[$key]['servers']));
								$packagehiddenservers = json_decode(htmlspecialchars_decode($packages[$key]['hiddenservers']));
								$packages[$key]["commands"] = htmlspecialchars_decode($packages[$key]["commands"]);
								$packages[$key]["description"] = htmlspecialchars_decode($packages[$key]["description"]);
								$packageinserver = FALSE;

								foreach ($packageservers as $key1 => $value1) {
									if($packageservers[$key1] === $_GET['server']){
										$packageHidden = false;
										foreach ($packagehiddenservers as $key2 => $value2) {
											if($packagehiddenservers[$key2] === $_GET['server']){
												$packageHidden = true;
											}
										}
										if ($packageHidden === false){
											$packageinserver = TRUE;
											$packagecount++;
										}
									}
								}

								if($packageinserver === FALSE){
									unset($packages[$key]);
								}

							}

							$packages = array_values($packages);

							if($packagecount > 0){

								print('
									<div id="server-container">
										<div id="welcome-header">
											' . getLangString("packages-for") . $servername . ':
										</div>
								');

								foreach ($packages as $key => $value) {
									if(empty($packages[$key]['img'])){

										$sql = $dbcon->prepare("SELECT id, gameimg FROM games WHERE gamename=:gamename");
										$values = array(':gamename' => $gamename);
										$sql->execute($values);
										$gameresult = $sql->fetchAll(PDO::FETCH_NUM);


										$packages[$key]['img'] = 'img/games/' . $gameresult[0][0] . '/' . $gameresult[0][1];

									} else {
										$packages[$key]['img'] = 'img/packages/' . $packages[$key]['id'] . '/' . $packages[$key]['img'];
									}

									// Get package price after sale is applied
									$packages[$key]['finalprice'] = getSalePrice($value['id']);

								}

								$numPackages = count($packages);

								switch($numPackages){
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

								$packagehtml = '';

								foreach ($packages as $key => $value) {

									$originalPrice = '';
									if ($packages[$key]['price'] != $packages[$key]['finalprice']){
										$originalPrice = '<del>' . $currencysymbol . $packages[$key]['price'] . '</del> ';
									}

									$priceInfo = $originalPrice . $currencysymbol . $packages[$key]["finalprice"];

									if($packages[$key]["expires"] > 0){
										$priceInfo .= " - " . (float)$packages[$key]["expires"] . " " . getLangString("days");
									}
									$packagehtml = $packagehtml . ' <div class="col-md-' . $colSize .'">
										<div class="game">
											<a href="#" onclick="showPackage(' . $key . ')">
												<div class="game-img" style="background-image: url(\'' . $packages[$key]['img'] . '\');"></div>
												<div class="game-name">' . $packages[$key]['title'] . '</div>
												<div class="package-price-info">' . $priceInfo . '</div>
											</a>
										</div>
									</div>';
								}

								print('
										<div class="container-fluid">
											<div class="row">
												' . $packagehtml . '
												<div class="col-md-12"><button class="submit-button" onclick="goBack(\'server\');" style="margin-left: auto; margin-right: auto;">' . getLangString("back") . '</button></div>
											</div>
										</div>
									</div>
								');
							} else {
								print('
									<div id="server-container">
										<p>' . getLangString("no-packages-enabled") . $servername . '! ' . getLangString("packages-guide") . '</p>
										<div class="col-md-12"><button class="submit-button" onclick="goBack(\'server\');" style="margin-left: auto; margin-right: auto;">' . getLangString("back") . '</button></div>
									</div>
								');
							}
						} else {
							ob_end_clean();
							header('Location: index.php');
							exit();
						}

					} else {

						$sql = $dbcon->prepare("SELECT id, gamename, gameimg FROM games WHERE enabled = 1 ORDER BY gamename");
						$sql->execute();
						$results = $sql->fetchAll(PDO::FETCH_ASSOC);

						$numGames = count($results);

						if($numGames > 1){

							print('
								<div id="server-container">
									<div id="welcome-header">
										' . getLangString("select-a-game") . '
									</div>
							');

							switch($numGames){
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

							$gamehtml = '';

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

							print('
											<div class="container-fluid">
												<div class="row">
													' . $gamehtml . '
												</div>
											</div>
										</div>
									');

						} else {
							ob_end_clean();
							header('Location: packages.php?game=' . $results[0]['id']);
							exit();
						}
					}

				?>
				</div>
			</div>
		</div>
		<div id="footer">
			<?php printFooter(); ?>
		</div>

		<script src="js/bootstrap.js"></script>
		<script src="js/main.js"></script>
		<script>

			<?php
				$returnGame = '';
				if(isset($_GET['game'])){
					$returnGame = $_GET['game'];
				}
			?>

			var returnURL = "<?=$dir?>packages.php?game=<?=$returnGame?>&paypalreturn=";

			function goBack(type){

				function getSearchParameters() {
					  var prmstr = window.location.search.substr(1);
					  return prmstr != null && prmstr != "" ? transformToAssocArray(prmstr) : {};
				}

				function transformToAssocArray( prmstr ) {
					var params = {};
					var prmarr = prmstr.split("&");
					for ( var i = 0; i < prmarr.length; i++) {
						var tmparr = prmarr[i].split("=");
						params[tmparr[0]] = tmparr[1];
					}
					return params;
				}

				var params = getSearchParameters();

				if(type === "server"){
					window.location = 'packages.php?game=' + params['game'];
				} else {
					window.location = 'index.php';
				}

			}

			function listenForCheckoutSubmit(){
				$('form').on('submit', function (e) {
					if($(this).attr('action') === "ajax.php"){
						addLoadingCircle($("#errorbox-bottom-1"));
						e.preventDefault();
						$.ajax({
							type: 'post',
							url: 'ajax.php',
							data: new FormData( this ),
			  				processData: false,
			  				contentType: false,
							success: function (data) {
								if($.trim(data).substring(0, 8) === "GOTOURL:"){
									location.href = $.trim(data).substring(8);
								} else if($.trim(data).substring(0, 3) === "ID:") {
									var newString = $.trim(data).substring(3);
									var details = newString.split("|~|");
									$("#paypal-form").attr("action", "<?php echo $paypalURL; ?>");
									$("#paypal-form-number").val(details[0]);
									$("#paypal-form-return").val(returnURL + details[0]);
									$("#paypal-form-price").val(details[1]);
									$("#paypal-form-email").val(details[2]);
									setTimeout(function(){
										$("#paypal-form").submit();
									}, 2000);
								} else {
									removeLoadingCircle($("#errorbox-bottom-1"));
									$('#errorbox-content-1').remove();
									$('#errorbox-bottom-1').append('<div id="errorbox-content-1">' + data + '</div>');
									//listenForCheckoutSubmit();
								}
							}
						});
					}
				});
			}

			function getPayPalStatus(){
				var formData = new FormData();
				formData.append('paypalstatus', transactionID);
				$.ajax({
					type: 'post',
					url:  'ajax.php',
					data: formData,
					processData: false,
					contentType: false,
					success: function (data) {
						if($.trim(data) === "success"){
							removeLoadingCircle($("#paypal-spinner"));
							showError(<?=json_encode(Settings::Get("purchasecompletemessage"))?>);
							clearInterval(statusTimer);
						}
					}
				});
			}

		<?php

			if(isset($_GET['paypalreturn'])){
				print('
				var transactionID = ' . intval($_GET['paypalreturn']) . ';
				var html = \'Waiting for PayPal to complete transaction. This message will be updated every 5 seconds. You can safely leave this page now and you will receive your package when it is ready.\n\' +
				\'<div id="paypal-spinner"></div>\';
				showError(html);
				addLoadingCircle($("#paypal-spinner"));
				var statusTimer = setInterval(getPayPalStatus, 5000);
				');
			}

			if (isset($_GET['game']) && isset($_GET['server'])) {

				$sql = $dbcon->prepare("SELECT * FROM settings WHERE setting='paypalenabled'");
				$sql->execute();
				$result = $sql->fetchAll(PDO::FETCH_ASSOC);
				$paypalEnabled = $result[0]['value'];

				$sql = $dbcon->prepare("SELECT * FROM settings WHERE setting='starpassenabled'");
				$sql->execute();
				$result = $sql->fetchAll(PDO::FETCH_ASSOC);
				$starpassEnabled = $result[0]['value'];

				$sql = $dbcon->prepare("SELECT * FROM settings WHERE setting='creditsenabled'");
				$sql->execute();
				$result = $sql->fetchAll(PDO::FETCH_ASSOC);
				$creditsEnabled = $result[0]['value'];

				$sql = $dbcon->prepare("SELECT * FROM settings WHERE setting='paymentmode'");
				$sql->execute();
				$result = $sql->fetchAll(PDO::FETCH_ASSOC);
				$paymentMode = $result[0]['value'];

				$paymentCode = "";
				$paymentButtons = "";
				$form = "";

				if($paymentMode == "directpurchase"){
					if($paypalEnabled == "1"){
						$paymentCode .= "'<input type=\"radio\" class=\"payment-radio\" name=\"paymentmethod\" value=\"paypal\" onclick=\"updatePaymentMethod(\'paypal\')\">PayPal<br>\\n' +";
						$paymentButtons .= "'<input class=\"buy-button-1 paypal-button\" type=\"image\" id=\"paypal-checkout-button\" src=\"https://www.paypalobjects.com/webstatic/en_US/btn/btn_checkout_pp_142x27.png\" alt=\"Check Out with PayPal\">\\n' +";
						$form = "'<form id=\"paypal-form\" type=\"post\">\\n' +
							'<input type=\"hidden\" name=\"cmd\" value=\"" . $buttonType . "\">\\n' +
							'<input type=\"hidden\" name=\"notify_url\" value=\"" . $dir . "paypalipn.php" . "\">\\n' +
							'<input type=\"hidden\" name=\"amount\" value=\"0.00\" id=\"paypal-form-price\">\\n' +
							'<input type=\"hidden\" name=\"business\" id=\"paypal-form-email\">\\n' +
							'<input type=\"hidden\" name=\"currency_code\" value=\"" . $currencycode . "\">\\n' +
							'<input type=\"hidden\" name=\"no_shipping\" value=\"1\">\\n' +
							'<input type=\"hidden\" name=\"item_number\" id=\"paypal-form-number\">\\n' +
							'<input type=\"hidden\" name=\"return\" id=\"paypal-form-return\">\\n' +
							'<input type=\"hidden\" name=\"cancel_return\" value=\"" . $dir . "packages.php\">\\n' +
							'<input type=\"hidden\" name=\"item_name\" id=\"paypal-form-package\">\\n' +
							'</form>\\n' +";
					}

					if($starpassEnabled == "1"){
						$paymentCode .= "'<input type=\"radio\" class=\"payment-radio\" name=\"paymentmethod\" value=\"starpass\" onclick=\"updatePaymentMethod(\'starpass\')\">StarPass<br>\\n' +";
						$paymentButtons .= "'<button style=\"margin-top: 20px;\" class=\"submit-button buy-button buy-button-1 starpass-button\">Buy with StarPass</button>\\n' +";
					}

					if($creditsEnabled == "1"){
						$paymentCode .= "'<input type=\"radio\" class=\"payment-radio\" name=\"paymentmethod\" value=\"credit\" onclick=\"updatePaymentMethod(\'credit\')\">Credit<br>\\n' +";
						$paymentButtons .= "'<button style=\"margin-top: 20px;\" class=\"submit-button buy-button buy-button-1 credit-button\">Buy with Credit</button>\\n' +";
					}

					if($paypalEnabled != "1" AND $starpassEnabled != "1"){
						$paymentCode = "'<span style=\"color: red;\">You have no payment options enabled!</span>\\n' +";
					}
				} else {
					$paymentCode .= "'<input type=\"radio\" class=\"payment-radio\" name=\"paymentmethod\" value=\"credit\" onclick=\"updatePaymentMethod(\'credit\')\">Credit<br>\\n' +";
					$paymentButtons .= "'<button style=\"margin-top: 20px;\" class=\"submit-button buy-button buy-button-1 credit-button\">Buy with Credit</button>\\n' +";

					if($starpassEnabled == "1"){
						$paymentCode .= "'<input type=\"radio\" class=\"payment-radio\" name=\"paymentmethod\" value=\"starpass\" onclick=\"updatePaymentMethod(\'starpass\')\">StarPass<br>\\n' +";
						$paymentButtons .= "'<button style=\"margin-top: 20px;\" class=\"submit-button buy-button buy-button-1 starpass-button\">Buy with StarPass</button>\\n' +";
					}
				}

				$freePaymentCode = "'<input type=\"radio\" class=\"payment-radio\" name=\"paymentmethod\" value=\"credit\" onclick=\"updatePaymentMethod(\'credit\')\" id=\"free-button\">Free<br>\\n' +";
				$freePaymentButton = "'<button style=\"margin-top: 20px;\" class=\"submit-button buy-button buy-button-1 free-button\">Buy for Free</button>\\n' +";

				print('
					var usernametype = \'' . $usernametype . '\';
					var packages = ' . json_encode($packages) . ';

					function updatePaymentMethod(paymentMethod){
						$(".buy-button-1").hide();
						$("." + paymentMethod + "-button").show();
					}

					$("input[type=radio][name=paymentmethod]").change(function() {
						updatePaymentMethod($(this).val());
					});

					function updateTotalPrice(key){
						var price = Number(packages[key].finalprice);
						var pwyw = packages[key].paywhatyouwant;
						if(pwyw == "1"){
							if($.isNumeric($("#pwywvalue").val())){
								if($("#pwywvalue").val() >= price){
									price = Number($("#pwywvalue").val());
								}
							}
						}
						$(".param-checkbox").each(function(key, value){
							if(value.checked){
								price = price + Number($(this).attr("data-price"));
							}
						});
						$("#total-price").html("Total: ' . $currencysymbol . '" + price.toFixed(2) + " ' . $currencycode . '");
					}

					function buyPackage(packageid){
						data1 = new FormData;
						data1.append("buypackage", packageid);
						$.ajax({
							type: "post",
							url: "ajax.php",
							data: data1,
			  				processData: false,
			  				contentType: false,
							success: function (data) {
								if($.trim(data)){
									$("#errorbox-content-1").remove();
									$("#errorbox-bottom-1").append(\'<div id="errorbox-content">\' + data + \'</div>\');
									if($("#table-container-1").css("display") == "none"){
										showError1();
									}
								} else {
									$.each(packages, function(key, value){
										if(value.id == packageid){
											var html = \'<form id="package-params" action="ajax.php" method="post" enctype="multipart/form-data">\n\' +
												\'<input type="hidden" name="checkoutsubmit" value="">\n\' +
												\'<input type="hidden" name="checkoutpackage" value="\' + packageid + \'">\n\' +
												\'<input type="hidden" name="checkoutpackagekey" value="\' + key + \'">\n\' +
												\'<input type="hidden" name="checkoutgame" value="' . $_GET['game'] . '">\n\' +
												\'<input type="hidden" name="checkoutserver" value="' . $_GET['server'] . '">\n\' +
												\'<p id="errorbox-title">Purchase \' + packages[key].title + \'</p>\n\';
											if(usernametype !== "Steam"){
												html += \'<p class="setting-title">\' + usernametype + \'</p>\n\' +
													\'<input class="settings-text-input" type="text" name="\' + usernametype + \'">\n\';
											}
											if(packages[key].paywhatyouwant == 1){
												html += \'<p class="setting-title">' . getLangString("amount-to-pay") . $currencysymbol . '\' + packages[key].finalprice + \')</p>\n\' +
													\'<input class="settings-text-input" id="pwywvalue" type="text" name="pwywvalue" oninput="updateTotalPrice(\' + key + \');">\n\';
											}
											var packageCommands = JSON.parse(packages[key].commands);
											$.each(packageCommands, function(key1, value1){
												$.each(value1.params, function(key2, value2){
													if(value2.substring(0, 14) === "{{USERCHOICE}}"){
														var userchoices = JSON.parse(value2.substring(14));
														if(userchoices[0].length > 0){
															html += \'<p class="setting-title">\' + value1.paramnames[key2] + \'</p>\n\' +
																\'<div class="checkbox-container" style="display: block;">\n\';
															$.each(userchoices[0], function(key3, value3){

																var dataPrice = userchoices[2][key3];
																var labelPrice = "";

																if(userchoices[2][key3] > 0){
																	labelPrice = " (+' . $currencysymbol . '" + Number(userchoices[2][key3]).toFixed(2) + ")";
																}

																html += \'<label for="choice-checkbox-\' + key1 + \'-\' + key2 + \'-\' + key3 + \'" style="display: block; float: left; clear: both;">\' + userchoices[0][key3] + labelPrice + \'</label>\n\' +
																	\'<input class="param-checkbox" id="choice-checkbox-\' + key1 + \'-\' + key2 + \'-\' + key3 + \'" name="choice-checkbox-\' + key1 + \'-\' + key2 + \'-\' + key3 + \'" style="display: block; float: right;" type="checkbox" data-price="\' + dataPrice + \'" onclick="updateTotalPrice(\' + key + \');">\n\';

															});

															html += \'</div>\n\';

														} else {
															if(value1.paramtypes[key2] === "bool"){
																html += \'<p class="setting-title">\' + value1.paramnames[key2] + \'</p>\n\' +
																	\'<label for="param-\' + key1 + \'-\' + key2 + \'" style="display: block; float: left; clear: both;">Yes</label>\n\' +
																	\'<input type="checkbox" id="param-\' + key1 + \'-\' + key2 + \'" style="display: block; float: right;" type="checkbox" name="param-\' + key1 + \'-\' + key2 + \'">\n\';
															} else {
																html += \'<p class="setting-title">\' + value1.paramnames[key2] + \'</p>\n\' +
																	\'<input class="settings-text-input" type="text" name="param-\' + key1 + \'-\' + key2 + \'">\n\';
															}
														}
													}
												});
											});
											html += \'<div class="checkbox-container" style="display: block;">\n\' +
												\'<label id="gift-checkbox-label" for="gift-checkbox" style="display: none; float: left; clear: both; margin-top: 20px;">' . getLangString("gift") . '</label>\n\' +
												\'<input id="gift-checkbox" name="gift-checkbox" style="display: none; float: right; margin-top: 25px;" type="checkbox">\n\' +
												\'<div id="steam-gift-details" style="display: none;">\n\' +
													\'<p class="setting-title">' . getLangString("steam-id-to-send-gift") . '<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the Steam ID of the gift receiver. This needs to be in the format 76561198134262586 and NOT like STEAM_0:0:86998429.">?</button></p>\n\' +
													\'<input id="gift-id" name="gift-id" class="settings-text-input">\n\' +
												\'</div>\n\' +
												\'<label for="tos-checkbox" style="display: block; float: left; clear: both; margin-top: 20px;">' . getLangString("i-have-read") . '<a href="#" onclick="showTOS();" class="underlined-link">' . getLangString("tos") . '</a></label>\n\' +
												\'<input id="tos-checkbox" name="tos-checkbox" style="display: block; float: right; margin-top: 25px;" type="checkbox">\n\' +
												\'<p class="setting-title">Coupon Code (Optional)</p>\n\' +
												\'<input class="settings-text-input" type="text" name="coupon">\n\' +
												\'</div>\n\' +
												\'<p class="setting-title" id="total-price"></p>\n\' +'
												. $paymentCode .
												$paymentButtons . '
												\'</form>\n\' +'
												. $form .
												'\'<p></p>\n\';

											showError1(html);
											updateTotalPrice(key);
											listenForCheckoutSubmit();
											enableToolTips();

											$("#paypal-form-package").val(packages[key].title);

											if(packages[key].giftable == 1){
												$("#gift-checkbox").show();
												$("#gift-checkbox-label").show();
												$("#gift-checkbox").change(function(){
													if(usernametype == "Steam"){
														if($(this).is(":checked")){
															$(this).nextAll("#steam-gift-details").first().show();
														} else {
															$(this).nextAll("#steam-gift-details").first().hide();
														}
													}
												});
											}

											$(".buy-button-1").hide();
											$(".payment-radio").first().attr("checked", true);
											$(".buy-button-1").first().show();

										}
									});
								}
							}
						});
					}

					function showPackage(key){

						var html = \'\' +
							\'<div id="welcome-header" style="width: 100%; text-align: center; font-size: 50px;">\' + packages[key]["title"] + \'</div>\n\' +
							\'<div class="game-img" style="background-image: url(\\\'\' + packages[key]["img"] + \'\\\'); margin-bottom: 30px;"></div>\n\' +
							\'<div class="package-price">' . $currencysymbol . '\' + packages[key]["finalprice"] + \' ' . $currencycode . '</div>\n\' +
							\'<div class="package-description">\' + packages[key]["description"] + \'</div>\n\' +
							\'<button class="submit-button buy-button" onclick="buyPackage(\\\'\' + packages[key]["id"] + \'\\\')">' . getLangString("buy-now") . '</button>\';

						showError(html);

					}

					function listenForCompletionSubmit(dataName){
						$("form").on("submit", function (e) {
							addLoadingCircle($("#errorbox-bottom-1"));
							data1 = new FormData(this);
							data1.append(dataName, "");
							e.preventDefault();
							$.ajax({
								type: "post",
								url: "ajax.php",
								data: data1,
				  				processData: false,
				  				contentType: false,
								success: function (data) {
									if($.trim(data)){
										addLoadingCircle($("#errorbox-bottom-1"));
										$("#errorbox-content-1").remove();
										$("#errorbox-bottom-1").append("<div id=\"errorbox-content-1\">" + data + "</div>");
									} else {
										addLoadingCircle($("#errorbox-bottom-1"));
										showError1("Purchase successfully completed, you may need to reconnect to the server for it to apply.");
									}
								}
							});
						});
					}

					function cancelPayPalPurchase(){
						location.href = "index.php?cancelpaypal=";
					}

				');

				if(isset($_GET['creditreturn'])){

					print('
						showPackage(' . $_SESSION['packagekey'] . ');
						var html = \'\' +
							\'<form action="ajax.php" method="post" enctype="multipart/form-data">\n\' +
							\'<p id="errorbox-title">Confirm Purchase</p>\n\' +
							\'<p id="confirmation-title">Make sure the information below is correct before confirming your purchase.</p>\n\' +
							\'<p class="confirmation-listing">' . getLangString("package") . ': \' + packages[' . $_SESSION['packagekey'] . ']["title"] + \'</p>\n\';
					');

					if(isset($_SESSION['vars']['Minecraft_Username'])){
						print('html += \'<p class="confirmation-listing">Minecraft Username: ' . $_SESSION['vars']['Minecraft_Username'] . '</p>\n\';' . PHP_EOL);
					} else {
						print('html += \'<p class="confirmation-listing">Steam ID: ' . $_SESSION['vars']['STEAMID'] . '</p>\n\';' . PHP_EOL);
					}

					foreach ($_SESSION['paramsdisplay'] as $key => $value) {
						foreach ($value as $key1 => $value1) {
							print('html += \'<p class="confirmation-listing">' . htmlspecialchars($value1, ENT_QUOTES|ENT_SUBSTITUTE) . '</p>\n\';' . PHP_EOL);
						}
					}

					print('
						html += \'<div class="checkbox-container" style="display: block; margin-top: 20px;">\n\' +
							\'<label for="confirmation-checkbox" style="display: block; float: left; clear: both; margin-top: 20px;">I confirm that the information shown above is correct.</label>\n\' +
							\'<input id="confirmation-checkbox" name="confirmation-checkbox" style="display: block; float: right; margin-top: 25px;" type="checkbox">\n\' +
							\'</div>\n\' +
							\'<div class="checkbox-container" style="display: block;">\n\' +
							\'<label for="tos-checkbox" style="display: block; float: left; clear: both; margin-top: 20px;">' . getLangString("i-have-read") . '<a href="#" onclick="showTOS();" class="underlined-link">' . getLangString("tos") . '</a></label>\n\' +
							\'<input id="tos-checkbox" name="tos-checkbox" style="display: block; float: right; margin-top: 25px;" type="checkbox">\n\' +
							\'</div>\n\' +
							\'<p class="setting-title" id="total-price">Total Price: ' . $currencysymbol .  sprintf("%.2f",$_SESSION['price']) . ' ' . $currencycode . '</p>\n\' +
							\'<button class="submit-button buy-button" type="submit" style="margin-top: 20px;">Confirm Purchase</button>\n\' +
							\'<button class="submit-button buy-button" type="button" onclick="cancelPayPalPurchase();">Cancel</button>\n\' +
							\'</form>\';
					');

					print(
						'showError1(html);
						listenForCompletionSubmit("confirmcreditpurchase");');

				}

			}

		?>

		</script>

	</body>

</html>

<?php
	ob_end_flush();
?>
