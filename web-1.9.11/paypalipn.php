<?php

require_once 'config.php';

try {
    $dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
    $dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch(PDOException $e){
    echo 'MySQL Error:' . $e->getMessage();
exit();
}

$sql = $dbcon->prepare("SELECT * FROM settings WHERE setting='paypalsandbox'");
$sql->execute();
$results = $sql->fetchAll(PDO::FETCH_ASSOC);
$sandboxmode = $results[0]['value'];
// CONFIG: Enable debug mode. This means we'll log requests into 'ipn.log' in the same directory.
// Especially useful if you encounter network errors or other intermittent problems with IPN (validation).
// Set this to 0 once you go live or don't require logging.
define("DEBUG", 0);
// Set to 0 once you're ready to go live
if($sandboxmode == "1"){
    define("USE_SANDBOX", 1);
} else {
    define("USE_SANDBOX", 0);
}
define("LOG_FILE", "./ipn.log");
// Read POST data
// reading posted data directly from $_POST causes serialization
// issues with array data in POST. Reading raw POST data from input stream instead.
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
	$keyval = explode ('=', $keyval);
	if (count($keyval) == 2)
		$myPost[$keyval[0]] = urldecode($keyval[1]);
}
// read the post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';
if(function_exists('get_magic_quotes_gpc')) {
	$get_magic_quotes_exists = true;
}
foreach ($myPost as $key => $value) {
	if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
		$value = urlencode(stripslashes($value));
	} else {
		$value = urlencode($value);
	}
	$req .= "&$key=$value";
}
// Post IPN data back to PayPal to validate the IPN data is genuine
// Without this step anyone can fake IPN data
if(USE_SANDBOX == true) {
	$paypal_url = "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr";
} else {
	$paypal_url = "https://ipnpb.paypal.com/cgi-bin/webscr";
}
$ch = curl_init($paypal_url);
if ($ch == FALSE) {
	return FALSE;
}
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
if(DEBUG == true) {
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
}
// CONFIG: Optional proxy configuration
//curl_setopt($ch, CURLOPT_PROXY, $proxy);
//curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
// Set TCP timeout to 30 seconds
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
// CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
// of the certificate as shown below. Ensure the file is readable by the webserver.
// This is mandatory for some environments.
$cert = __DIR__ . "/ca/cacert.pem";
curl_setopt($ch, CURLOPT_CAINFO, $cert);
$res = curl_exec($ch);
if (curl_errno($ch) != 0) // cURL error
	{
	if(DEBUG == true) {
		error_log(date('[Y-m-d H:i e] '). "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL, 3, LOG_FILE);
	}
	curl_close($ch);
	exit;
} else {
		// Log the entire HTTP response if debug is switched on.
		if(DEBUG == true) {
			error_log(date('[Y-m-d H:i e] '). "Raw PayPal POST data: " . $raw_post_data. PHP_EOL, 3, LOG_FILE);
			error_log(date('[Y-m-d H:i e] '). "HTTP request of validation request:". curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req" . PHP_EOL, 3, LOG_FILE);
			error_log(date('[Y-m-d H:i e] '). "HTTP response of validation request: $res" . PHP_EOL, 3, LOG_FILE);
		}
		curl_close($ch);
}
// Inspect IPN validation result and act accordingly
// Split response headers and payload, a better way for strcmp
$tokens = explode("\r\n\r\n", trim($res));
$res = trim(end($tokens));
if (strcmp ($res, "VERIFIED") == 0) {
	// check whether the payment_status is Completed
	// check that txn_id has not been previously processed
	// check that receiver_email is your PayPal email
	// check that payment_amount/payment_currency are correct
	// process payment and mark item as paid.
	// assign posted variables to local variables
	//$item_name = $_POST['item_name'];
	//$item_number = $_POST['item_number'];
	//$payment_status = $_POST['payment_status'];
	//$payment_amount = $_POST['mc_gross'];
	//$payment_currency = $_POST['mc_currency'];
	//$txn_id = $_POST['txn_id'];
	//$receiver_email = $_POST['receiver_email'];
	//$payer_email = $_POST['payer_email'];

    $transactionid = $_POST['txn_id'];
    $status = $_POST['payment_status'];

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='paypalemail'");
	$sql->execute();
	$result = $sql->fetchAll(PDO::FETCH_ASSOC);
	$paypalEmail = strtolower($result[0]['value']);

    if(!isset($_POST['case_type'])){
        if(($status === "Completed" OR $status === "Completed-Funds-Held") AND (strtolower($_POST['business']) == $paypalEmail || strtolower($_POST['receiver_email']) == $paypalEmail)){
            if(!empty($_POST["item_number"]) || !empty($_POST['item_number1'])){
				if (!empty($_POST['item_number'])){
					$itemNumber = $_POST['item_number'];
				} else {
					$itemNumber = $_POST['item_number1'];
				}
				$sql = $dbcon->prepare("SELECT * FROM paypalpayments WHERE id=:id");
				$values = array(':id' => $itemNumber);
				$sql->execute($values);
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				$rowCount = $sql->rowCount();
				if($rowCount > 0){
					if($results[0]['status'] !== 'success'){
						$sql = $dbcon->prepare("UPDATE paypalpayments SET status='success' WHERE id=:id");
						$values = array(':id' => $itemNumber);
						$sql->execute($values);
						$username = $results[0]['username'];
						$price = $results[0]['price'];
						if(($_POST['mc_gross'] == $price || $_POST['mc_gross_1']) AND $_POST['mc_currency'] == $currencycode){
							$package = (array)json_decode($results[0]['package']);
							$paramValues = (array)json_decode($results[0]['params']);
							$paramDisplay = (array)json_decode($results[0]['paramsdisplay']);
							$vars = (array)json_decode($results[0]['vars']);
							$packageCommands = (array)json_decode($package["commands"]);
							$serverInfo = [];
							$playerName = "";
							$transactionid = $_POST['txn_id'];
							$ready = 1;

							$endCommands = processCommands($package, $paramValues, $vars, $transactionid, $ready);

							$sql = $dbcon->prepare("SELECT email FROM users WHERE username=:username");
							$values = array(':username' => $results[0]['username']);
							$sql->execute($values);
							$emailresults = $sql->fetchAll(PDO::FETCH_ASSOC);
							$emailAddress = $emailresults[0]['email'];
							$content = [$results[0]['username'], $package];
							sendEmail("purchasecomplete", $emailAddress, $content);

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

							$status = "complete";

							if($sandboxmode == 0){
								$sql = $dbcon->prepare("INSERT INTO transactions(purchaser, usernametype, username, game, expires, expiretime, endcommands, transactionid, package, packageid, paymentmethod, value, status, params, coupon) VALUES(:purchaser, :usernametype, :username, :game, :expires, :expiretime, :endcommands, :transactionid, :package, :packageid, :paymentmethod, :value, :status, :params, :coupon)");
								$values = array(':purchaser' => $username, ':usernametype' => $usernametype, ':username' => $playerName, ':game' => $game, ':expires' => $package["expires"], ':expiretime' => $expireDate, ':endcommands' => json_encode($endCommands), ':transactionid' => $transactionid, ':package' => $package['title'], ':packageid' => $package['id'], ':paymentmethod' => "PayPal", ':value' => sprintf("%.2f",$price), ':status' => $status, ':params' => json_encode($paramDisplay), ':coupon' => $results[0]['coupon']);
								$sql->execute($values);
							} else {
								$sql = $dbcon->prepare("INSERT INTO transactions(purchaser, usernametype, username, game, expires, expiretime, endcommands, transactionid, package, packageid, paymentmethod, value, status, params, coupon) VALUES(:purchaser, :usernametype, :username, :game, :expires, :expiretime, :endcommands, :transactionid, :package, :packageid, :paymentmethod, :value, :status, :params, :coupon)");
								$values = array(':purchaser' => $username, ':usernametype' => $usernametype, ':username' => $playerName, ':game' => $game, ':expires' => $package["expires"], ':expiretime' => $expireDate, ':endcommands' => json_encode($endCommands), ':transactionid' => $transactionid, ':package' => $package['title'], ':packageid' => $package['id'], ':paymentmethod' => "PayPal", ':value' => sprintf("%.2f",$price), ':status' => $status . ' - Sandbox', ':params' => json_encode($paramDisplay), ':coupon' => $results[0]['coupon']);
								$sql->execute($values);
							}
						}
					}
				}
            } elseif(!empty($_POST['custom'])) {
				if($_POST['mc_currency'] == $currencycode AND isset($_POST['custom'])){
					$sql = $dbcon->prepare("SELECT * FROM transactions WHERE transactionid=:transactionid");
					$values = array(':transactionid' => $transactionid);
					$sql->execute($values);
					if($sql->rowCount() < 1){
	                    $sql = $dbcon->prepare("SELECT * FROM users WHERE id=:id");
	            		$values = array(':id' => $_POST['custom']);
	            		$sql->execute($values);
	            		$results = $sql->fetchAll(PDO::FETCH_ASSOC);
						$username = $results[0]['username'];
	                    $credit = $results[0]['credit'];
						if (!empty($_POST['mc_gross'])){
							$credit += $_POST['mc_gross'];
						} else {
							$credit += $_POST['mc_gross_1'];
						}
	                    $sql = $dbcon->prepare("UPDATE users SET credit=:credit WHERE id=:id");
	                    $values = array(':credit' => $credit, ':id' => $results[0]['id']);
	                    $sql->execute($values);
	                    $results[0]['credit'] = $credit;
	                    $sql = $dbcon->prepare("INSERT INTO transactions(purchaser, usernametype, username, game, expires, expiretime, endcommands, transactionid, package, packageid, paymentmethod, value, status, params) VALUES(:purchaser, :usernametype, :username, :game, :expires, NOW(), :endcommands, :transactionid, :package, :packageid, :paymentmethod, :value, :status, :params)");
	                    $values = array(':purchaser' => $username, ':usernametype' => "", ':username' => "", ':game' => "PayPal Credit Purchase", ':expires' => 0, ':endcommands' => "[]", ':transactionid' => $transactionid, ':package' => 'PayPal Credit Purchase', ':packageid' => -1, ':paymentmethod' => "PayPal", ':value' => $_POST['mc_gross'], ':status' => 'complete', ':params' => '[]');
	                    $sql->execute($values);
					}
                }
			}
        }
    } else {
        if($_POST['case_type'] === "chargeback" || $_POST['case_type'] === "bankreturn" || $_POST['case_type'] === "dispute"){

            $sql = $dbcon->prepare("SELECT * FROM transactions WHERE transactionid=:transactionid AND paymentmethod='PayPal'");
			$values = array(':transactionid' => $transactionid);
			$sql->execute($values);
			$results = $sql->fetchAll(PDO::FETCH_ASSOC);
            $player = $results[0]["username"];
            $endCommands = json_decode($results[0]["endcommands"]);

            foreach ($endCommands as $key => $value) {

				$server = $value[0];
				$sql = $dbcon->prepare("SELECT * FROM servers WHERE id=:id");
				$values = array(':id' => $server);
				$sql->execute($values);
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				$execute = "onjoin";

				if(isset($value[2])){
					$execute = $value[2];
				}

				$sql = $dbcon->prepare("DELETE FROM commandstoexecute WHERE player=:player AND command=:command AND server=:server AND port=:port LIMIT 1");
				$values = array(':player' => $player, ':command' => $value[1], ':server' => $results[0]['ip'], ':port' => $results[0]['port']);
				$sql->execute($values);

				if($execute === "onjoin" OR $execute === "choiceonjoin"){
					$sql = $dbcon->prepare("INSERT INTO commandstoexecute(time, server, port, command, player, ready, transactionid) VALUES(NOW(), :server, :port, :command, :player, :ready, :transactionid)");
					$values = array(':server' => $results[0]['ip'], ':port' => $results[0]['port'], ':command' => $value[1], ':player' => $player, ':ready' => '1', ':transactionid' => '');
					$sql->execute($values);
				} else {
					$sql = $dbcon->prepare("INSERT INTO commandstoexecute(time, server, port, command, player, executenow, ready, transactionid) VALUES(NOW(), :server, :port, :command, :player, 1, :ready, :transactionid)");
					$values = array(':server' => $results[0]['ip'], ':port' => $results[0]['port'], ':command' => $value[1], ':player' => $player, ':ready' => '1', ':transactionid' => '');
					$sql->execute($values);
				}

			}

            if($_POST['case_type'] === "chargeback" || $_POST['case_type'] === "bankreturn"){
                $sql = $dbcon->prepare("UPDATE transactions SET status='chargeback' WHERE transactionid=:transactionid AND paymentmethod='PayPal'");
                $values = array(':transactionid' => $transactionid);
                $sql->execute($values);
            } else {
                $sql = $dbcon->prepare("UPDATE transactions SET status='dispute' WHERE transactionid=:transactionid AND paymentmethod='PayPal'");
                $values = array(':transactionid' => $transactionid);
                $sql->execute($values);
            }

        }
    }

	if(DEBUG == true) {
		error_log(date('[Y-m-d H:i e] '). "Verified IPN: $req ". PHP_EOL, 3, LOG_FILE);
	}
} else if (strcmp ($res, "INVALID") == 0) {
	// log for manual investigation
	// Add business logic here which deals with invalid IPN messages
	if(DEBUG == true) {
		error_log(date('[Y-m-d H:i e] '). "Invalid IPN: $req" . PHP_EOL, 3, LOG_FILE);
	}
}
?>
