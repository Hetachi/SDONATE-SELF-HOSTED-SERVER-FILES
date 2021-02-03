<?php
require_once('config.php');

try {
	$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
	$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch(PDOException $e){
	echo 'MySQL Error:' . $e->getMessage();
	exit();
}

$sql = $dbcon->prepare("SELECT value FROM settings");
$sql->execute();
$result = $sql->fetchAll(PDO::FETCH_ASSOC);

$pageError = [];

require('sessionname.php');

if(!isset($_SESSION)){
    session_start();
}

if(isset($_POST['buypackage'])){

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='paypalsandbox'");
	$sql->execute();
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	$sandbox = $results[0]['value'];

	if(isset($_SESSION['username'])){
		$packageid = intval($_POST['buypackage']);
		$sql = $dbcon->prepare("SELECT email, usertype FROM users WHERE username=:username");
		$values = array(':username' => $_SESSION['username']);
		$sql->execute($values);
		$results = $sql->fetchAll(PDO::FETCH_ASSOC);

		if($sandbox === "0" || ($sandbox === "1" AND $results[0]['usertype'] === "admin")){
			if(empty($results[0]['email'])){
				array_push($pageError, getLangString("must-add-email-error"));
			} else {
				$sql = $dbcon->prepare("SELECT game FROM packages WHERE id=:id");
				$values = array(':id' => $packageid);
				$sql->execute($values);
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				$gameid = $results[0]['game'];
				$sql = $dbcon->prepare("SELECT usernametype FROM games WHERE id=:id");
				$values = array(':id' => $gameid);
				$sql->execute($values);
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				if($results[0]['usernametype'] === "Steam"){
					$sql = $dbcon->prepare("SELECT steamid FROM users WHERE username=:username");
					$values = array(':username' => $_SESSION['username']);
					$sql->execute($values);
					$results = $sql->fetchAll(PDO::FETCH_ASSOC);
					if(empty($results[0]['steamid'])){
						array_push($pageError, getLangString("must-add-steam-error"));
					}
				}
			}
		} else {
			array_push($pageError, getLangString("paypal-sandbox-enabled-admin-error"));
		}

	} else {
		array_push($pageError, getLangString("buy-loggedout-error"));
	}
}

if(isset($_POST['checkoutsubmit'])){

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='paypalsandbox'");
	$sql->execute();
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	$sandbox = $results[0]['value'];

	$goToNextStage = false;
	$allowedToPurchase = false;

	if(isset($_SESSION['username'])){
		if(isset($_POST['tos-checkbox'])){
			$validCoupon = validCoupon($_POST['coupon'], $_SESSION['username'], $_POST['checkoutpackage']);
			if ($validCoupon === true){
				$packageid = intval($_POST['checkoutpackage']);
				$paymentMethod = $_POST['paymentmethod'];
				$sql = $dbcon->prepare("SELECT email, usertype FROM users WHERE username=:username");
				$values = array(':username' => $_SESSION['username']);
				$sql->execute($values);
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);

				if($sandbox === "0" || ($sandbox === "1" AND $results[0]['usertype'] === "admin") || $paymentMethod === "starpass" || $paymentMethod === "credit"){
					if(empty($results[0]['email'])){
						array_push($pageError, getLangString("must-add-email-error"));
					} else {
						$sql = $dbcon->prepare("SELECT id, game, maxpurchases, paywhatyouwant, giftable FROM packages WHERE id=:id");
						$values = array(':id' => $packageid);
						$sql->execute($values);
						$results = $sql->fetchAll(PDO::FETCH_ASSOC);
						$gameid = $results[0]['game'];
						$maxpurchases = $results[0]['maxpurchases'];
						$packagePrice = getSalePrice($results[0]['id']);
						$packagePrice = applyCoupon($packagePrice, $_POST['coupon']);
						$_SESSION['coupon'] = $_POST['coupon'];
						$packagePWYW = $results[0]['paywhatyouwant'];
						$giftable = $results[0]['giftable'];
						$sql = $dbcon->prepare("SELECT usernametype FROM games WHERE id=:id");
						$values = array(':id' => $gameid);
						$sql->execute($values);
						$results = $sql->fetchAll(PDO::FETCH_ASSOC);
						$vars = [];
						$priceOK = false;

						if($packagePWYW == "1"){
							$userPrice = $_POST['pwywvalue'];
							if($userPrice >= $packagePrice){
								$_SESSION["price"] = $userPrice;
								$priceOK = true;
							} else {
								array_push($pageError, getLangString("invalid-pwyw-error"));
							}
						} else {
							$_SESSION["price"] = $packagePrice;
							$priceOK = true;
						}

						if($priceOK === true){
							if($results[0]['usernametype'] === "Steam"){
								if(!isset($_POST['gift-checkbox']) OR $giftable == 0){
									$sql = $dbcon->prepare("SELECT steamid FROM users WHERE username=:username");
									$values = array(':username' => $_SESSION['username']);
									$sql->execute($values);
									$results = $sql->fetchAll(PDO::FETCH_ASSOC);
									if(empty($results[0]['steamid'])){
										array_push($pageError, getLangString("must-add-steam-error"));
									} else {
										$username = $results[0]['steamid'];
										$vars['STEAMID'] = $username;
										$url = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $steamapi . "&steamids=" . $results[0]['steamid']);
										if($url === false){
											$steamUsername = $_SESSION['username'];
											$steamUsername = str_replace("'", "", $steamUsername);
											$steamUsername = str_replace('"', "", $steamUsername);
											$steamUsername = str_replace('|NEWVAL|', "", $steamUsername);
											$vars['STEAMUSERNAME'] = $steamUsername;
										} else {
											$content = json_decode($url, true);
											$steamUsername = $content['response']['players'][0]['personaname'];
											$steamUsername = str_replace("'", "", $steamUsername);
											$steamUsername = str_replace('"', "", $steamUsername);
											$steamUsername = str_replace('|NEWVAL|', "", $steamUsername);
											$vars['STEAMUSERNAME'] = $steamUsername;
										}
										$authserver = bcsub($username, '76561197960265728') & 1;
										$authid = (bcsub($username, '76561197960265728')-$authserver)/2;
										$steamid32 = "STEAM_0:$authserver:$authid";
										$vars['STEAMID32'] = $steamid32;
										$vars['STEAMID3'] = "U:1:" . $authid * 2;
										$goToNextStage = true;
									}
								} else {
									if(is_numeric($_POST["gift-id"])){
										$username = $_POST['gift-id'];
										$vars['STEAMID'] = $username;
										$url = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $steamapi . "&steamids=" . $username);
										if($url === false){
											$steamUsername = $_SESSION['username'];
											$steamUsername = str_replace("'", "", $steamUsername);
											$steamUsername = str_replace('"', "", $steamUsername);
											$steamUsername = str_replace('|NEWVAL|', "", $steamUsername);
											$vars['STEAMUSERNAME'] = $steamUsername;
										} else {
											$content = json_decode($url, true);
											$steamUsername = $content['response']['players'][0]['personaname'];
											$steamUsername = str_replace("'", "", $steamUsername);
											$steamUsername = str_replace('"', "", $steamUsername);
											$steamUsername = str_replace('|NEWVAL|', "", $steamUsername);
											$vars['STEAMUSERNAME'] = $steamUsername;
										}
										$authserver = bcsub($username, '76561197960265728') & 1;
										$authid = (bcsub($username, '76561197960265728')-$authserver)/2;
										$steamid32 = "STEAM_0:$authserver:$authid";
										$vars['STEAMID32'] = $steamid32;
										$vars['STEAMID3'] = "U:1:" . $authid * 2;
										$goToNextStage = true;
									} else {
										array_push($pageError, getLangString("invalid-steamid-error"));
									}
								}
							} else {
								if($results[0]['usernametype'] === "Minecraft Username"){
									$username = $_POST["Minecraft_Username"];
									$vars['Minecraft_Username'] = $username;
									if(preg_match('/^[a-zA-Z0-9_]{1,16}$/', $username) === 1){
										$goToNextStage = true;
									} else {
										array_push($pageError, getLangString("invalid-mcusername-error"));
									}
								}
							}
						}
					}
				} else {
					array_push($pageError, getLangString("paypal-sandbox-enabled-admin-error"));
				}
			} else {
				array_push($pageError, $validCoupon);
			}
		} else {
			array_push($pageError, getLangString("accept-tos-error"));
		}
	} else {
		array_push($pageError, getLangString("buy-loggedout-error"));
	}

	if($goToNextStage === true){

		$sql = $dbcon->prepare("SELECT expires, expiretime FROM transactions WHERE packageid=:packageid AND username=:username");
		$values = array(':packageid' => $packageid, ':username' => $username);
		$sql->execute($values);
		$purchaseCount = $sql->rowCount();
		$purchases = $sql->fetchAll(PDO::FETCH_ASSOC);

		if(($purchaseCount >= $maxpurchases) AND ($maxpurchases > 0)){
			array_push($pageError, getLangString("max-purchases-exceeded-error") . $maxpurchases . ".");
		} else {

			$allowedToPurchase = true;

			foreach ($purchases as $key => $value) {
				if($value["expires"] == 1){
					if($value["expiretime"] >= time()){
						$allowedToPurchase = false;
						break;
					}
				}
			}

		}

	}

	if($allowedToPurchase === true){

		$sql = $dbcon->prepare("SELECT * FROM packages WHERE id=:id");
		$values = array(':id' => $packageid);
		$sql->execute($values);
		$results = $sql->fetchAll(PDO::FETCH_ASSOC);
		$package = $results[0];

		$commands = json_decode($package['commands']);
		$paramDisplay = [];
		$paramValues = [];
		$additionalPrice = 0;

		foreach ($commands as $key => $value) {
			foreach($value->params as $key1 => $value1){
				if(substr($value1, 0, 14) === "{{USERCHOICE}}"){
					$choices = json_decode(substr($value1, 14));
					if($value->paramtypes[$key1] === "varcharmulti"){
						$chosenNames = [];
						$chosenValues = [];
						foreach ($choices[0] as $key2 => $value2) {
							if(isset($_POST['choice-checkbox-' . $key . '-' . $key1 . '-' . $key2])){
								array_push($chosenValues, $choices[1][$key2]);
								array_push($chosenNames, $choices[0][$key2]);
								$additionalPrice += $choices[2][$key2];
							}
						}
						if(count($chosenValues) > 0){
							$paramValues[$key][$key1] =  implode(",", $chosenValues);
							$paramDisplay[$key][$key1] = $value->paramnames[$key1] . ": " . implode(",", $chosenNames);
						} else {
							$allowedToPurchase = false;
							array_push($pageError, getLangString("param-need-select-error") . $value->paramnames[$key1] . "\".");
						}
					} elseif($value->paramtypes[$key1] === "bool") {
						if(isset($_POST['param-' . $key . '-' . $key1])){
							$paramValues[$key][$key1] = "1";
							$paramDisplay[$key][$key1] = $value->paramnames[$key1] . ": " . "Yes";
						} else {
							$paramValues[$key][$key1] = "0";
							$paramDisplay[$key][$key1] = $value->paramnames[$key1] . ": " . "No";
						}
					} else {
						$inputType = $value->paramtypes[$key1];
						if($inputType === "numeric"){
							if(ctype_digit($_POST['param-' . $key . '-' . $key1]) || (substr($_POST['param-' . $key . '-' . $key1], 0, 1) == "-" && ctype_digit(substr($_POST['param-' . $key . '-' . $key1], 1, strlen($_POST['param-' . $key . '-' . $key1]) - 1)))){
								$paramValues[$key][$key1] = $_POST['param-' . $key . '-' . $key1];
								$paramDisplay[$key][$key1] = $value->paramnames[$key1] . ": " . $_POST['param-' . $key . '-' . $key1];
							} else {
								$allowedToPurchase = false;
								array_push($pageError, '"' . $value->paramnames[$key1] . getLangString("param-integer-error"));
							}
						} else {
							$paramVal = sanitiseUserInput($_POST['param-' . $key . '-' . $key1]);
							$paramValues[$key][$key1] = $paramVal;
							$paramDisplay[$key][$key1] = $value->paramnames[$key1] . ": " . $_POST['param-' . $key . '-' . $key1];
						}
					}
				} else {
					$paramValues[$key][$key1] = $value->params[$key1];
				}
			}
		}

		if($allowedToPurchase === true){

			$totalPrice = getSalePrice($package['id'], $additionalPrice);
			$totalPrice = applyCoupon($totalPrice, $_SESSION['coupon']);

			$_SESSION['package'] = $package;
			$_SESSION['params'] = $paramValues;
			$_SESSION['paramsdisplay'] = $paramDisplay;
			$_SESSION['vars'] = $vars;
			$_SESSION['price'] = $totalPrice;

			if($packagePWYW == "1"){
				if($userPrice >= $totalPrice){
					$_SESSION["price"] = $userPrice;
					$totalPrice = $userPrice;
				} else {
					array_push($pageError, getLangString("invalid-pwyw-error"));
				}
			}

			$_SESSION['packagekey'] = $_POST['checkoutpackagekey'];

			$packageJSON = json_encode($package);
			$paramsJSON = json_encode($paramValues);
			$paramsDisplayJSON = json_encode($paramDisplay);
			$varsJSON = json_encode($vars);

			if($paymentMethod === "paypal"){
				$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='paypalemail'");
				$sql->execute();
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				$result = $results[0]['value'];
				$sql = $dbcon->prepare("INSERT INTO paypalpayments(created, username, price, package, params, paramsdisplay, vars, packagekey, status, coupon) VALUES(NOW(), ?, ?, ?, ?, ?, ?, ?, 'waiting', ?)");
				$values = [$_SESSION['username'], $totalPrice, $packageJSON, $paramsJSON, $paramsDisplayJSON, $varsJSON, $_POST['checkoutpackagekey'], $_SESSION['coupon']];
				$sql->execute($values);
				$values = [];
				$values[0] = $dbcon->lastInsertId();
				$values[1] = $totalPrice;
				$values[2] = $result;
				if ($totalPrice != 0){
					print("ID:" . implode("|~|", $values));
				} else {
					print("GOTOURL:" . $dir . "packages.php?game=" . $_POST['checkoutgame'] . "&server=" . $_POST['checkoutserver'] . "&creditreturn=");
				}
			} elseif($paymentMethod === "starpass"){
				$sql = $dbcon->prepare("SELECT credit FROM users WHERE username=:username");
				$values = array(':username' => $_SESSION['username']);
				$sql->execute($values);
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				$credit = $results[0]['credit'];
				if($credit < $_SESSION['price']){
					array_push($pageError, getLangString("insufficient-credits-starpass-error"));
				} else {
					print("GOTOURL:" . $dir . "packages.php?game=" . $_POST['checkoutgame'] . "&server=" . $_POST['checkoutserver'] . "&creditreturn=");
				}
			} elseif($paymentMethod === "credit"){
				$sql = $dbcon->prepare("SELECT credit FROM users WHERE username=:username");
				$values = array(':username' => $_SESSION['username']);
				$sql->execute($values);
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				$credit = $results[0]['credit'];
				if($credit < $_SESSION['price']){
					array_push($pageError, getLangString("insufficient-credits-error"));
				} else {
					print("GOTOURL:" . $dir . "packages.php?game=" . $_POST['checkoutgame'] . "&server=" . $_POST['checkoutserver'] . "&creditreturn=");
				}
			}
		}

	}

}

if(isset($_POST['confirmpaypalpurchase'])){
	if(isset($_SESSION['paypaltoken']) && isset($_SESSION['username'])){
		if(isset($_POST['tos-checkbox'])){
			if(isset($_POST['confirmation-checkbox'])){

				$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='paypalsandbox'");
				$sql->execute();
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				$sandbox = $results[0]['value'];

				if($sandbox === "1"){
					$sandboxmode = "true";
				} else {
					$sandboxmode = "false";
				}

				$url = $sdonateapiurl;
				$data = array(
					'action' => "paypalcomplete",
					'apikey' => $sdonateapi,
					'token' => $_SESSION['paypaltoken'],
					'packagename' => $_SESSION["package"]["title"],
					'sandbox' => $sandboxmode,
					'amount' => $_SESSION["price"],
					'ipnurl' => $dir . 'paypalipn.php',
					'currencycode' => $currencycode
				);
				$options = array(
					'http' => array(
						'header'  => "Content-type: application/x-www-form-urlencoded",
						'method'  => 'POST',
						'content' => http_build_query($data),
					),
				);
				$context  = stream_context_create($options);
				$result = file_get_contents($url, false, $context);

				if($result === FALSE){
					array_push($pageError, getLangString("sdonate-server-connection-error"));
				} elseif($result === "apiproblem"){
					array_push($pageError, getLangString("api-key-problem"));
				} elseif($result === "credentialsnotsetup"){
					array_push($pageError, getLangString("paypal-credentials-error"));
				} elseif($result === "paypalservererror"){
					array_push($pageError, getLangString("paypal-connection-error"));
				} elseif($result === "docheckouterror"){
					array_push($pageError, getLangString("paypal-funding-error"));
				} else {
					$result = json_decode($result);
					if($result->ACK === "Success"){
						if($result->PAYMENTINFO_0_PAYMENTSTATUS !== "Completed" AND $result->PAYMENTINFO_0_PAYMENTSTATUS !== "In-Progress" AND $result->PAYMENTINFO_0_PAYMENTSTATUS !== "Pending" AND $result->PAYMENTINFO_0_PAYMENTSTATUS !== "Processed" AND $result->PAYMENTINFO_0_PAYMENTSTATUS !== "Completed-Funds-Held"){
							array_push($pageError, getLangString("paypal-notcharged-error"));
						} else {

							$package = $_SESSION['package'];
							$paramValues = $_SESSION['params'];
							$paramDisplay = $_SESSION['paramsdisplay'];
							$vars = $_SESSION['vars'];
							$packageCommands = json_decode($package["commands"]);
							$serverInfo = [];
							$playerName = "";
							$transactionid = $result->PAYMENTINFO_0_TRANSACTIONID;

							if($result->PAYMENTINFO_0_PAYMENTSTATUS === "Completed" OR $result->PAYMENTINFO_0_PAYMENTSTATUS === "Completed-Funds-Held"){
								$status = "complete";
								$ready = 1;
								$sql = $dbcon->prepare("SELECT email FROM users WHERE username=:username");
								$values = array(':username' => $_SESSION['username']);
								$sql->execute($values);
								$results = $sql->fetchAll(PDO::FETCH_ASSOC);
								$emailAddress = $results[0]['email'];
								$content = [$_SESSION['username'], $package];
								sendEmail("purchasecomplete", $emailAddress, $content);
							} else {
								$status = "pending";
								$ready = 0;
								print(getLangString("paypal-pending-error"));
							}

							$endCommands = processCommands($package, $paramValues, $vars, $transactionid, $ready);

							$gameid = $package["game"];
							$sql = $dbcon->prepare("SELECT gamename FROM games WHERE id=:game");
							$values = array(':game' => $gameid);
							$sql->execute($values);
							$result = $sql->fetchAll(PDO::FETCH_ASSOC);
							$game = $result[0]['gamename'];

							if(isset($vars['Minecraft_Username'])){
								$playerName = $vars['Minecraft_Username'];
								$usernametype = "Minecraft Username";
							} else {
								$playerName = $vars['STEAMID'];
								$usernametype = "Steam ID";
							}

							if($package["expires"] > 0){
								$expireTimeInt = time() + ($package["expires"] * 86400);
								$expireDate = date("Y-m-d H:i:s", $expireTimeInt);
							} else {
								$expireDate = date("Y-m-d H:i:s");
							}

							if($sandbox == 0){
								$sql = $dbcon->prepare("INSERT INTO transactions(purchaser, usernametype, username, game, expires, expiretime, endcommands, transactionid, package, packageid, paymentmethod, value, status, params) VALUES(:purchaser, :usernametype, :username, :game, :expires, :expiretime, :endcommands, :transactionid, :package, :packageid, :paymentmethod, :value, :status, :params)");
								$values = array(':purchaser' => $_SESSION['username'], ':usernametype' => $usernametype, ':username' => $playerName, ':game' => $game, ':expires' => $package["expires"], ':expiretime' => $expireDate, ':endcommands' => json_encode($endCommands), ':transactionid' => $transactionid, ':package' => $package['title'], ':packageid' => $package['id'], ':paymentmethod' => "PayPal", ':value' => sprintf("%.2f",$_SESSION['price']), ':status' => $status, ':params' => json_encode($paramDisplay));
								$sql->execute($values);
							} else {
								$sql = $dbcon->prepare("INSERT INTO transactions(purchaser, usernametype, username, game, expires, expiretime, endcommands, transactionid, package, packageid, paymentmethod, value, status, params) VALUES(:purchaser, :usernametype, :username, :game, :expires, :expiretime, :endcommands, :transactionid, :package, :packageid, :paymentmethod, :value, :status, :params)");
								$values = array(':purchaser' => $_SESSION['username'], ':usernametype' => $usernametype, ':username' => $playerName, ':game' => $game, ':expires' => $package["expires"], ':expiretime' => $expireDate, ':endcommands' => json_encode($endCommands), ':transactionid' => $transactionid, ':package' => $package['title'], ':packageid' => $package['id'], ':paymentmethod' => "PayPal", ':value' => sprintf("%.2f",$_SESSION['price']), ':status' => $status . ' - Sandbox', ':params' => json_encode($paramDisplay));
								$sql->execute($values);
							}

						}
					} else {
						$result = (array) $result;
						for($i = 0; $i < 100; $i++){
							if(isset($result['L_ERRORCODE' . $i])){
								$sql = $dbcon->prepare("INSERT INTO logs(time, errortype, errorcode, error) VALUES(NOW(), :errortype, :errorcode, :error)");
								$values = array(':errortype' => 'PayPal Error', ':errorcode' => $result['L_ERRORCODE' . $i], ':error' => ($result['L_SHORTMESSAGE' . $i] . ' - ' . $result['L_LONGMESSAGE' . $i]));
								$sql->execute($values);
							} else {
								$i = 100;
								break;
							}
						}
						array_push($pageError, getLangString("paypal-generic-error"));
					}

					unset($_SESSION['paypaltoken']);

				}
			} else {
				array_push($pageError, getLangString("confirm-details-error"));
			}
		} else {
			array_push($pageError, getLangString("accept-tos-error"));
		}

	} else {
		print(getLangString("generic-payment-error"));
	}
}

if(isset($_POST['confirmcreditpurchase'])){
	if(isset($_POST['tos-checkbox'])){
		if(isset($_POST['confirmation-checkbox'])){
			$package = $_SESSION['package'];
			$paramValues = $_SESSION['params'];
			$paramDisplay = $_SESSION['paramsdisplay'];
			$vars = $_SESSION['vars'];
			$packageCommands = json_decode($package["commands"]);
			$serverInfo = [];
			$playerName = "";
			$ready = 1;

			$sql = $dbcon->prepare("SELECT credit FROM users WHERE username=:username");
			$values = array(':username' => $_SESSION['username']);
			$sql->execute($values);
			$results = $sql->fetchAll(PDO::FETCH_ASSOC);
			$credit = $results[0]['credit'];

			if($credit < $_SESSION['price']){
				array_push($pageError, getLangString("insufficient-credits-error"));
			} else {
				$credit = $credit - $_SESSION['price'];
				$sql = $dbcon->prepare("UPDATE users SET credit=:credit WHERE username=:username");
				$values = array(':credit' => $credit, ':username' => $_SESSION['username']);
				$sql->execute($values);
				$endCommands = processCommands($package, $paramValues, $vars, "Credit", 1);

				$gameid = $package["game"];
				$sql = $dbcon->prepare("SELECT gamename FROM games WHERE id=:game");
				$values = array(':game' => $gameid);
				$sql->execute($values);
				$result = $sql->fetchAll(PDO::FETCH_ASSOC);
				$game = $result[0]['gamename'];

				if(isset($vars['Minecraft_Username'])){
					$playerName = $vars['Minecraft_Username'];
					$usernametype = "Minecraft Username";
				} else {
					$playerName = $vars['STEAMID'];
					$usernametype = "Steam ID";
				}

				if($package["expires"] > 0){
					$expireTimeInt = time() + ($package["expires"] * 86400);
					$expireDate = date("Y-m-d H:i:s", $expireTimeInt);
				} else {
					$expireDate = date("Y-m-d H:i:s");
				}

				$sql = $dbcon->prepare("INSERT INTO transactions(purchaser, usernametype, username, game, expires, expiretime, endcommands, transactionid, package, packageid, paymentmethod, value, status, params, coupon) VALUES(:purchaser, :usernametype, :username, :game, :expires, :expiretime, :endcommands, :transactionid, :package, :packageid, :paymentmethod, :value, :status, :params, :coupon)");
				$values = array(':purchaser' => $_SESSION['username'], ':usernametype' => $usernametype, ':username' => $playerName, ':game' => $game, ':expires' => $package["expires"], ':expiretime' => $expireDate, ':endcommands' => json_encode($endCommands), ':transactionid' => 'Credit Purchase', ':package' => $package['title'], ':packageid' => $package['id'], ':paymentmethod' => "Credits", ':value' => '0.00', ':status' => 'complete', ':params' => json_encode($paramDisplay), ':coupon' => $_SESSION['coupon']);
				$sql->execute($values);

				$sql = $dbcon->prepare("SELECT email FROM users WHERE username=:username");
				$values = array(':username' => $_SESSION['username']);
				$sql->execute($values);
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				$emailAddress = $results[0]['email'];
				$content = [$_SESSION['username'], $package];
				sendEmail("purchasecomplete", $emailAddress, $content);
			}
		} else {
			array_push($pageError, getLangString("confirm-details-error"));
		}
	} else {
		array_push($pageError, getLangString("accept-tos-error"));
	}
}

if(isset($_POST['creditpurchase'])){
	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='paypalemail'");
	$sql->execute();
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	$result = $results[0]['value'];
	echo 'ID:' . $result;
}

if(isset($_POST['paypalstatus'])){
	$sql = $dbcon->prepare("SELECT * FROM paypalpayments WHERE id=:id");
	$values = array(':id' => $_POST['paypalstatus']);
	$sql->execute($values);
	$results = $sql->fetch(PDO::FETCH_ASSOC);
	if (isset($_SESSION['username']) && isset($results['username'])) {
		if($results['username'] === $_SESSION['username']){
			print($results['status']);
		}
	}
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}

?>
