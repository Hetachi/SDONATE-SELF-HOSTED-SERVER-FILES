<?php

require(dirname(__FILE__) . '/require/classes.php');
$db = new DataBase();

$sdonateapiurl = 'https://sdonate.com/api.php';

$demoMode = false;

$customDir = true;

if(empty($dir)){
	$customDir = false;
	$usingHTTPS = false;

	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'){
	    $usingHTTPS = true;
	} elseif(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'){
	    $usingHTTPS = true;
	}

	$protocol = $usingHTTPS ? 'https' : 'http';

	$path = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname(__FILE__));

	$baseURL = $protocol . '://' . $_SERVER['SERVER_NAME'] . "/";

	$requestURI = $_SERVER['REQUEST_URI'];
	$requestURI = str_replace("http://", "", $requestURI);
	$requestURI = str_replace("https://", "", $requestURI);
	$requestURI = str_replace($_SERVER['SERVER_NAME'], "", $requestURI);
	$requestComponents = explode("/", $requestURI);
	$pathComponents = explode("/", dirname(__FILE__));
	$i = 1;
	foreach ($pathComponents as $key => $value) {
		if($value == $requestComponents[$i]){
			$i++;
		} else {
			unset($pathComponents[$key]);
		}
	}

	$dir = $baseURL . implode("/", $pathComponents);
}
if(substr($dir, 0, 4) !== "http"){
	$dir = "http://" . $dir;
}
$dir = rtrim($dir, "/") . "/";

function escapeHTML(&$value){
    $value = htmlspecialchars($value, ENT_QUOTES|ENT_SUBSTITUTE);
}

function printFooter(){
	global $dbcon;
	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='tos'");
	$sql->execute();
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	$tos = addslashes($results[0]['value']);
	$tos = str_replace(array("\n", "\r"), '', $tos);
    print('

        <a href="" target="_blank"><p class="footer-text-left">© SDonate Donation System ' . date('Y') . '</a> - <a href="#" onclick="showTOS();">' . getLangString("tos") . '</a></p>
        <p class="footer-text-right">Powered by Steam</p>
		<script>
			function showTOS(){
				var html = \'' . $tos . '\';
				showError2(html);
			}
		</script>
    ');
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function getLangString($string){
    require('lang.php');
    if(isset($lang[$chosenLang][$string])){
        return $lang[$chosenLang][$string];
    }
    return $lang["en"][$string];
}

function sanitiseUserInput($input){
    $returnString = str_replace("|NEWVAL|", "", $input);
    return $returnString;
}

function processCommands($package, $paramValues, $vars, $transactionid, $ready, $runEndCommands = true){

    global $dbcon;

    $packageCommands = json_decode($package["commands"]);

    foreach (json_decode($package["servers"]) as $key => $value) {
        $sql = $dbcon->prepare("SELECT * FROM servers WHERE id=:id");
        $values = array(':id' => $value);
        $sql->execute($values);
        $serverInfo[$value] = $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    $gameid = $package["game"];
    $sql = $dbcon->prepare("SELECT gamename FROM games WHERE id=:game");
    $values = array(':game' => $gameid);
    $sql->execute($values);
    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    $game = $result[0]['gamename'];
    $endCommands = [];

    if(isset($vars['Minecraft_Username'])){
        $playerName = $vars['Minecraft_Username'];
        $usernametype = "Minecraft Username";
    } else {
        $playerName = $vars['STEAMID'];
        $usernametype = "Steam ID";
    }

    foreach ($packageCommands as $key => $value) {

        $sql = $dbcon->prepare("SELECT * FROM actions WHERE (game=:game OR game='all') AND name=:name");
        $values = array(':game' => $game, ':name' => $value->name);
        $sql->execute($values);
        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
        $type = $results[0]["type"];

        $startCommand = $value->startcommand;
        $endCommand = $value->endcommand;

        foreach ($paramValues[$key] as $key1 => $value1) {
            $needle = "{{INPUT=" . $value->paramnames[$key1] . "|TYPE=" . $value->paramtypes[$key1] . "}}";
            if($game === "Garry's Mod"){
                $startCommand = str_replace($needle, addslashes($value1), $startCommand);
                $endCommand = str_replace($needle, addslashes($value1), $endCommand);
            } else {
                $startCommand = str_replace($needle, $value1, $startCommand);
                $endCommand = str_replace($needle, $value1, $endCommand);
            }
        }

        foreach ($vars as $key1 => $value1) {
            $startCommand = str_replace("{{VAR=" . $key1 . "}}", $value1, $startCommand);
            $endCommand = str_replace("{{VAR=" . $key1 . "}}", $value1, $endCommand);
        }

        foreach ($value->servers as $key2 => $value2) {
            if(!empty($endCommand) AND isset($serverInfo[$value2][0])){
                $id = $serverInfo[$value2][0]['id'];
                array_push($endCommands, [$id, $endCommand, $value->execute]);
            }
        }

        if($type !== "special"){
            if($game === "Minecraft" OR $game === "Garry's Mod" OR (($game === "Rust" OR $game === "Counter-Strike: Global Offensive" OR $game === "Team Fortress 2" OR $game === "Left 4 Dead 2") AND strpos($value->name, "RCON") === false)){

                if($value->execute === "onjoin" || $value->execute === "choiceonjoin"){

                    foreach ($value->servers as $key2 => $value2) {
                        if(isset($serverInfo[$value2][0])){
                            $sql = $dbcon->prepare("INSERT INTO commandstoexecute(time, server, port, command, player, ready, transactionid) VALUES(NOW(), :server, :port, :command, :player, :ready, :transactionid)");
                            $values = array(':server' => $serverInfo[$value2][0]['ip'], ':port' => $serverInfo[$value2][0]['port'], ':command' => $startCommand, ':player' => $playerName, ':ready' => $ready, ':transactionid' => $transactionid);
                            $sql->execute($values);
                        }
                    }

                    if($package["expires"] > 0 && !empty($endCommand) && $runEndCommands == true){
                        $expireTimeInt = time() + ($package["expires"] * 86400);
                        $expireDate = date("Y-m-d H:i:s", $expireTimeInt);
                        foreach ($value->servers as $key2 => $value2) {
                            if(isset($serverInfo[$value2][0])){
                                $sql = $dbcon->prepare("INSERT INTO commandstoexecute(time, server, port, command, player, ready, transactionid) VALUES(:time, :server, :port, :command, :player, :ready, :transactionid)");
                                $values = array(':time' => $expireDate, ':server' => $serverInfo[$value2][0]['ip'], ':port' => $serverInfo[$value2][0]['port'], ':command' => $endCommand, ':player' => $playerName, ':ready' => $ready, ':transactionid' => $transactionid);
                                $sql->execute($values);
                            }
                        }
                    }

                } else {

                    foreach ($value->servers as $key2 => $value2) {
                        if(isset($serverInfo[$value2][0])){
                            $sql = $dbcon->prepare("INSERT INTO commandstoexecute(time, server, port, command, player, executenow, ready, transactionid) VALUES(NOW(), :server, :port, :command, :player, 1, :ready, :transactionid)");
                            $values = array(':server' => $serverInfo[$value2][0]['ip'], ':port' => $serverInfo[$value2][0]['port'], ':command' => $startCommand, ':player' => $playerName, ':ready' => $ready, ':transactionid' => $transactionid);
                            $sql->execute($values);
                        }
                    }

                    if($package["expires"] > 0 && !empty($endCommand) && $runEndCommands == true){
                        $expireTimeInt = time() + ($package["expires"] * 86400);
                        $expireDate = date("Y-m-d H:i:s", $expireTimeInt);
                        foreach ($value->servers as $key2 => $value2) {
                            if(isset($serverInfo[$value2][0])){
                                $sql = $dbcon->prepare("INSERT INTO commandstoexecute(time, server, port, command, player, executenow, ready, transactionid) VALUES(:time, :server, :port, :command, :player, 1, :ready, :transactionid)");
                                $values = array(':time' => $expireDate, ':server' => $serverInfo[$value2][0]['ip'], ':port' => $serverInfo[$value2][0]['port'], ':command' => $endCommand, ':player' => $playerName, ':ready' => $ready, ':transactionid' => $transactionid);
                                $sql->execute($values);
                            }
                        }
                    }

                }

            } else {

                foreach ($value->servers as $key2 => $value2) {
                    if(isset($serverInfo[$value2][0])){
                        sendRCONCommand($serverInfo[$value2][0]['ip'], $serverInfo[$value2][0]['port'], $serverInfo[$value2][0]['rconpass'], $startCommand);
                    }
                }

            }
        } else {

            if($value->name === "MySQL Query"){

                $paramValues = $paramValues[0];
                $dbhost1 = $paramValues[0];
                $dbname1 = $paramValues[1];
                $dbusername1 = $paramValues[2];
                $dbpassword1 = $paramValues[3];

				foreach ($vars as $key1 => $value1) {
		            $paramValues[4] = str_replace("{{VAR=" . $key1 . "}}", $value1, $paramValues[4]);
		            $paramValues[5] = str_replace("{{VAR=" . $key1 . "}}", $value1, $paramValues[5]);
		        }

                try {
                	$dbcon1 = new PDO("mysql:host=$dbhost1;dbname=$dbname1", $dbusername1, $dbpassword1);
                	$dbcon1->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                } catch(PDOException $e){
                    $sql = $dbcon->prepare("INSERT INTO logs(time, errortype, errorcode, error) VALUES(NOW(), :errortype, :errorcode, :error)");
                    $values = array(':errortype' => 'MySQL Error', ':errorcode' => $e->getCode(), ':error' => $e->getMessage());
                    $sql->execute($values);
                }
                $sql1 = $dbcon1->prepare($paramValues[4]);
                $sql1->execute(explode("|NEWVAL|", $paramValues[5]));
            }

            if($value->name === "CombineControl Donation"){

                $paramValues = $paramValues[0];
                $dbhost1 = $paramValues[0];
                $dbname1 = $paramValues[1];
                $dbusername1 = $paramValues[2];
                $dbpassword1 = $paramValues[3];

                try {
                	$dbcon1 = new PDO("mysql:host=$dbhost1;dbname=$dbname1", $dbusername1, $dbpassword1);
                	$dbcon1->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                } catch(PDOException $e){
                    $sql = $dbcon->prepare("INSERT INTO logs(time, errortype, errorcode, error) VALUES(NOW(), :errortype, :errorcode, :error)");
                    $values = array(':errortype' => 'MySQL Error', ':errorcode' => $e->getCode(), ':error' => $e->getMessage());
                    $sql->execute($values);
                }
                $sql1 = $dbcon1->prepare("INSERT INTO cc_donations(SteamID, CharID, DonationType, DonationData) VALUES(?, ?, ?, ?)");
                $sql1->execute([$vars["STEAMID32"], $paramValues[4], $paramValues[5], $paramValues[6]]);
            }

        }
    }
    return $endCommands;
}

function sendRCONCommand($server, $port, $rconpass, $command){

    global $sdonateapiurl;
    global $sdonateapi;
    global $dbcon;

    $url = $sdonateapiurl;
    $data = array('action' => 'rconcommand', 'apikey' => $sdonateapi, 'server' => $server, 'port' => $port, 'rconpass' => $rconpass, 'command' => $command);
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
        array_push($pageError, "There was an error processing this request.");
    } elseif($result === "apiproblem") {
        array_push($pageError, "There is a problem with this store's SDonate API key, please alert the store owner.");
    } elseif($result === "commandran") {
        return $result;
    } else {
        $sql = $dbcon->prepare("INSERT INTO logs(time, errortype, error) VALUES(NOW(), :errortype, :error)");
        $values = array(':errortype' => 'RCON Error', ':error' => 'Error with RCON Command on server with IP ' . $server . ' and port ' . $port . ': ' . $result);
        $sql->execute($values);
        return false;
    }
}

function sendEmail($type, $emailAddress, $content){

    global $dbcon;
    global $dir;
    global $sdonateapiurl;
    global $sdonateapi;

    $sql = $dbcon->prepare("SELECT * FROM settings");
    $sql->execute();
    $results = $sql->fetchAll(PDO::FETCH_ASSOC);

    $storeName = $results[2]['value'];
    $logo = $results[1]['value'];
    $SMTPServer = $results[23]['value'];
    $SMTPPort = $results[24]['value'];
    $security = $results[25]['value'];
    $senderEmail = $results[26]['value'];
    $senderPassword = $results[27]['value'];
    $emailEnabled = $results[30]['value'];
    $color = $results[31]['value'];

    if($emailEnabled === "1"){
        if(substr($logo, 0, 4) === "img/"){
            $logo = $dir . $logo;
        } else {
            $logo = "https://sdonate.com/img/logo.png";
        }

        switch ($type) {
            case 'purchasecomplete':
                $body = $results[28]['value'];
                $body = str_replace("{{VAR=Username}}", $content[0], $body);
                $body = str_replace("{{VAR=Package}}", $content[1]["title"], $body);
                $body = str_replace("{{VAR=Store Name}}", $storeName, $body);
                $subject = $results[29]['value'];
                $subject = str_replace("{{VAR=Username}}", $content[0], $subject);
                $subject = str_replace("{{VAR=Package}}", $content[1]["title"], $subject);
                $subject = str_replace("{{VAR=Store Name}}", $storeName, $subject);
                break;

            default:

                break;
        }

        $url = $sdonateapiurl;
        $data = array('action' => 'sendemail',
            'apikey' => $sdonateapi,
            'smtpserver' => $SMTPServer,
            'smtpport' => $SMTPPort,
            'security' => $security,
            'sender' => $senderEmail,
            'senderpassword' => $senderPassword,
            'from' => $storeName,
            'to' => $emailAddress,
            'subject' => $subject,
            'body' => $body,
            'logo' => $logo,
            'color' => $color
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

        if($result === "emailsent") {
            return true;
        } else {
            $sql = $dbcon->prepare("INSERT INTO logs(time, errortype, error) VALUES(NOW(), :errortype, :error)");
            $values = array(':errortype' => 'Email Error', ':error' => 'Error sending email to ' . $emailAddress . ': ' . $result);
            $sql->execute($values);
            return false;
        }
    }

}

function getSalePrice($packageid, $additionalPrice = 0){

	global $dbcon;

	$sql = $dbcon->prepare("SELECT * FROM packages WHERE id=:id");
	$values = [':id' => $packageid];
	$sql->execute($values);
	$package = $sql->fetch(PDO::FETCH_ASSOC);

	$sql = $dbcon->prepare("SELECT * FROM sales WHERE starts <= NOW() AND ends >= NOW()");
	$sql->execute();
	$sales = $sql->fetchAll(PDO::FETCH_ASSOC);

	$bestPrice = $package['price'] + $additionalPrice;
	foreach ($sales as $key => $value) {
		$salePackages = json_decode($value['packages']);
		if (in_array($package['id'], $salePackages)) {
			if ($value['discounttype'] == "Percent Off") {
				$salePrice = ($package['price'] + $additionalPrice) - (($value['discount']/100) * ($package['price'] + $additionalPrice));
			} elseif ($value['discounttype'] == "Money Off") {
				$salePrice = ($package['price'] + $additionalPrice) - $value['discount'];
			} elseif ($value['discounttype'] == "Set Price") {
				$salePrice = $value['discount'] + $additionalPrice;
			}
			if ($salePrice < $bestPrice) {
				$bestPrice = $salePrice;
			}
		}
	}
	if ($bestPrice < 0) {
		$bestPrice = 0;
	}

	return number_format($bestPrice, 2);

}

function validCoupon($couponCode, $username, $packageid){

	if (empty($couponCode)){
		return true;
	}

	global $dbcon;

	$sql = $dbcon->prepare("SELECT * FROM coupons WHERE code=:code");
	$values = [':code' => $couponCode];
	$sql->execute($values);
	$coupon = $sql->fetch(PDO::FETCH_ASSOC);

	if (!isset($coupon['code'])){
		return "Invalid coupon";
	} else {
		if (strtotime($coupon['ends']) < time()) {
			return "Coupon has expired";
		} else {
			if (!in_array($packageid, json_decode($coupon['packages']))){
				return "This coupon cannot be used on this package";
			}
		}
	}

	$sql = $dbcon->prepare("SELECT id FROM transactions WHERE coupon=:coupon");
	$values = [':coupon' => $couponCode];
	$sql->execute($values);
	$couponUses = $sql->rowCount();

	$sql = $dbcon->prepare("SELECT id FROM transactions WHERE coupon=:coupon AND purchaser=:purchaser");
	$values = [':coupon' => $couponCode, ':purchaser' => $username];
	$sql->execute($values);
	$personalCouponUses = $sql->rowCount();

	if ($couponUses < $coupon['maxuses']){
		if ($personalCouponUses < $coupon['maxusesperperson']){
			return true;
		} else {
			return "You have used this coupon the maximum number of times";
		}
	} else {
		return "Coupon has reached maximum uses";
	}

}

function applyCoupon($price, $couponCode){

	if (empty($couponCode)){
		return $price;
	}

	global $dbcon;

	$sql = $dbcon->prepare("SELECT * FROM coupons WHERE code=:code");
	$values = [':code' => $couponCode];
	$sql->execute($values);
	$coupon = $sql->fetch(PDO::FETCH_ASSOC);

	$salePrice = $price;

	if ($coupon['discounttype'] == "Percent Off") {
		$salePrice = $price - (($coupon['discount']/100) * $price);
		error_log($salePrice);
	} elseif ($coupon['discounttype'] == "Money Off") {
		$salePrice = $price - $coupon['discount'];
	} elseif ($coupon['discounttype'] == "Set Price") {
		$salePrice = $coupon['discount'];
	}

	return number_format($salePrice, 2);

}

function parseDate($date){
    $day = date('jS', strtotime($date));
    $month = date('F', strtotime($date));
    $year = date('Y', strtotime($date));
    $time = date('G:i:s', strtotime($date));
    return $day . " " . $month . " " . $year . " " . $time;
}

function tableExists($pdo, $table){
	try {
		$result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
	} catch (Exception $e) {
		return FALSE;
	}
	return $result !== FALSE;
}

function columnExists($pdo, $table, $column){
	$sql = $pdo->prepare("SHOW COLUMNS FROM $table");

    $sql->execute();
    $raw_column_data = $sql->fetchAll();
	$columnNames = [];

    foreach($raw_column_data as $outer_key => $array){
        foreach($array as $inner_key => $value){

            if ($inner_key === 'Field'){
                    if (!(int)$inner_key){
                        $columnNames[] = $value;
                    }
                }
        }
    }

	foreach ($columnNames as $key => $value) {
		if ($value == $column){
			return true;
		}
	}
	return false;
}

require('lang.php');

try {
    $dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
    $dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch(PDOException $e){
    echo 'MySQL Error:' . $e->getMessage();
exit();
}

if(tableExists($dbcon, 'settings') !== FALSE){
    $sql = $dbcon->prepare("SELECT * FROM settings WHERE setting='currentversion'");
    $sql->execute();
    $resultsCount = $sql->rowCount();

    if($resultsCount < 1){
        $currentversion = "1.0.8";
        $version = "1.0.0";
        $sql = $dbcon->prepare("INSERT INTO settings(setting, value) VALUES('currentversion', :version)");
        $values = array(':version' => $currentversion);
        $sql->execute($values);
    } else {
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        $version = $result[0]['value'];
    }

    if(version_compare($version, "1.0.8") === -1){
        $sql = $dbcon->prepare("INSERT INTO settings(setting, value) VALUES('circleimages', '1')");
        $sql->execute();
        $sql = $dbcon->prepare("UPDATE settings SET value='1.0.8' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.0.8";
    }
    if(version_compare($version, "1.0.9") === -1){
        $sql = $dbcon->prepare("ALTER TABLE transactions ADD packageid TEXT");
        $sql->execute();
        $sql = $dbcon->prepare("UPDATE settings SET value='1.0.9' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.0.9";
    }
    if(version_compare($version, "1.0.10") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.0.10' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.0.10";
    }
    if(version_compare($version, "1.0.11") === -1){
        $sql = $dbcon->prepare("UPDATE actions SET startcommand=:startcommand, endcommand=:endcommand WHERE name='Add to Group - ULX'");
        $values = array(':startcommand' => "RunConsoleCommand(\"ulx\", \"adduserid\", \"{{VAR=STEAMID32}}\", \"{{INPUT=Rank|TYPE=varchar}}\")", ':endcommand' => "RunConsoleCommand(\"ulx\", \"removeuserid\", \"{{VAR=STEAMID32}}\")");
        $sql->execute($values);
        $sql = $dbcon->prepare("UPDATE settings SET value='1.0.11' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.0.11";
    }
    if(version_compare($version, "1.1.0") === -1){
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('maintheme', '0')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('mainfontcolor', '#242424')");
    	$dbcon->query("INSERT INTO settings(setting, value) VALUES('secondaryfontcolor', '#FFFFFF')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('themefont', 'Raleway')");

        $games[0] = array(':id' => 3, ':gamename' => 'Rust', ':gameimg' => 'rust.png', ':oldimg' => 'rust.png', ':usernametype' => 'Steam', ':enabled' => 0);
    	$games[1] = array(':id' => 4, ':gamename' => 'Counter-Strike: Global Offensive', ':gameimg' => 'csgo.png', ':oldimg' => 'csgo.png', ':usernametype' => 'Steam', ':enabled' => 0);
        $games[2] = array(':id' => 5, ':gamename' => 'Team Fortress 2', ':gameimg' => 'tf2.png', ':oldimg' => 'tf2.png', ':usernametype' => 'Steam', ':enabled' => 0);
        $games[3] = array(':id' => 6, ':gamename' => 'Left 4 Dead 2', ':gameimg' => 'l4d2.png', ':oldimg' => 'l4d2.png', ':usernametype' => 'Steam', ':enabled' => 0);
        $games[4] = array(':id' => 7, ':gamename' => 'ARK: Survival Evolved', ':gameimg' => 'ase.png', ':oldimg' => 'ase.png', ':usernametype' => 'Steam', ':enabled' => 0);
    	$sql = $dbcon->prepare("INSERT INTO games(id, gamename, gameimg, oldimg, usernametype, enabled) VALUES(:id, :gamename, :gameimg, :oldimg, :usernametype, :enabled)");

    	foreach ($games as $key => $value) {
    		$sql->execute($value);
    	}

        $actions[0] = array(':game' => 'ARK: Survival Evolved', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');
    	$actions[1] = array(':game' => 'Counter-Strike: Global Offensive', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');
    	$actions[2] = array(':game' => 'Left 4 Dead 2', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');
    	$actions[3] = array(':game' => 'Rust', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');
    	$actions[4] = array(':game' => 'Team Fortress 2', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');

    	$sql = $dbcon->prepare("INSERT INTO actions(game, name, execute, startcommand, endcommand, type) VALUES(:game, :name, :execute, :startcommand, :endcommand, :type)");

    	foreach ($actions as $key => $value) {
    		$sql->execute($value);
    	}

        $sql = $dbcon->prepare("UPDATE settings SET value='1.1.0' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.1.0";
    }
    if(version_compare($version, "1.1.2") === -1){
        ini_set('session.cookie_domain', substr($_SERVER['SERVER_NAME'],strpos($_SERVER['SERVER_NAME'],"."),100));
        $sql = $dbcon->prepare("UPDATE settings SET value='1.1.2' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.1.2";
    }
    if(version_compare($version, "1.1.3") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.1.3' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.1.3";
    }
    if(version_compare($version, "1.1.5") === -1){
        $sql = $dbcon->prepare("ALTER TABLE commandstoexecute ADD ready TINYINT DEFAULT 1");
        $sql->execute();
        $sql = $dbcon->prepare("ALTER TABLE commandstoexecute ADD transactionid TEXT");
        $sql->execute();
        $sql = $dbcon->prepare("UPDATE actions SET execute='now' WHERE name='Add to Group - ULX'");
        $sql->execute();
        $sql = $dbcon->prepare("UPDATE actions SET startcommand=:startcommand, endcommand=:endcommand WHERE name='Console Command' AND game='Garry\'s Mod'");
        $values = array(':startcommand' => 'game.ConsoleCommand("{{INPUT=Command|TYPE=varchar}}\n")', ':endcommand' => 'game.ConsoleCommand("{{INPUT=Package Expiry Command|TYPE=varchar}}\n")');
        $sql->execute($values);
        $sql = $dbcon->prepare("UPDATE settings SET value='1.1.5' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.1.5";
    }
    if(version_compare($version, "1.2") === -1){
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('starpassenabled', '0')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('starpasscode', '')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('paypalenabled', '1')");
        $dbcon->query("UPDATE actions SET startcommand='while true do if player.GetBySteamID64(\"{{VAR=STEAMID}}\"):getDarkRPVar(\"money\") then player.GetBySteamID64(\"{{VAR=STEAMID}}\"):addMoney({{INPUT=Money|TYPE=numeric}}) return false end end' WHERE name='DarkRP Add Money'");
        $sql = $dbcon->prepare("ALTER TABLE users ADD credit DECIMAL(11,2) DEFAULT 0.00");
        $sql->execute();
        $sql = $dbcon->prepare("UPDATE settings SET value='1.2' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.2";
    }
    if(version_compare($version, "1.2.1") === -1){
        $sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='paypalenabled'");
        $sql->execute();
        $rowCount = $sql->rowCount();
        if($rowCount < 1){
            $dbcon->query("INSERT INTO settings(setting, value) VALUES('starpassenabled', '0')");
            $dbcon->query("INSERT INTO settings(setting, value) VALUES('starpasscode', '')");
            $dbcon->query("INSERT INTO settings(setting, value) VALUES('paypalenabled', '1')");
            $sql = $dbcon->prepare("ALTER TABLE users ADD credit DECIMAL(11,2) DEFAULT 0.00");
            $sql->execute();
        }
        $sql = $dbcon->prepare("UPDATE settings SET value='1.2.1' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.2.1";
    }
    if(version_compare($version, "1.2.2") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.2.2' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.2.2";
    }
    if(version_compare($version, "1.2.4") === -1){
        $sql = $dbcon->prepare("UPDATE actions SET startcommand=:startcommand WHERE name='Pointshop 2 Standard Points'");
        $values = array(':startcommand' => "player.GetBySteamID64(\"{{VAR=STEAMID}}\"):PS2_AddStandardPoints({{INPUT=Points|TYPE=numeric}}, \"Purchased\")");
        $sql->execute($values);
        $sql = $dbcon->prepare("UPDATE settings SET value='1.2.4' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.2.4";
    }
    if(version_compare($version, "1.2.5") === -1){
        $sql = $dbcon->prepare("UPDATE actions SET startcommand=:startcommand WHERE name='Pointshop 2 Standard Points'");
        $values = array(':startcommand' => 'game.ConsoleCommand("ps2_addpoints {{VAR=STEAMID32}} points {{INPUT=Points|TYPE=numeric}}\n")');
        $sql->execute($values);
        $sql = $dbcon->prepare("UPDATE actions SET startcommand=:startcommand WHERE name='Pointshop 2 Premium Points'");
        $values = array(':startcommand' => 'game.ConsoleCommand("ps2_addpoints {{VAR=STEAMID32}} premiumPoints {{INPUT=Points|TYPE=numeric}}\n")');
        $sql->execute($values);
        $sql = $dbcon->prepare("UPDATE settings SET value='1.2.5' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.2.5";
    }
    if(version_compare($version, "1.2.7") === -1){
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('paymentmode', 'directpurchase')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('paypalemail', '')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('creditsenabled', '0')");
        $sql = $dbcon->prepare("UPDATE settings SET value='1.2.7' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.2.7";
    }
    if(version_compare($version, "1.3.3") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.3.3' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.3.3";
    }
    if(version_compare($version, "1.4.0") === -1){
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('smtpserver', 'smtp.example.com')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('smtpport', '465')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('smtpsecurity', 'ssl')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('senderemailaddress', 'youremail@gmail.com')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('senderemailpassword', '')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('purchasemessage', 'Thank you for your purchasing {{VAR=Package}}, {{VAR=Username}}!')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('purchasesubject', 'Thanks for your purchase from {{VAR=Store Name}}, {{VAR=Username}}.')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('emailenabled', '0')");
        $sql = $dbcon->prepare("UPDATE settings SET value='1.4.0' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.4.0";
    }
    if(version_compare($version, "1.5.0") === -1){
        if(tableExists($dbcon, 'news') === FALSE){
        	$sql = "CREATE table news(
        		id INT(11) AUTO_INCREMENT PRIMARY KEY,
        		date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        		author TEXT,
        		title TEXT,
        		content TEXT);";
        	$dbcon->exec($sql);
        }
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('emailcolor', '#0BB5FF')");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('showdonators', '0')");
        $sql = $dbcon->prepare("UPDATE settings SET value='1.5.0' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.5.0";
    }
    if(version_compare($version, "1.5.1") === -1){
        $sql = $dbcon->prepare("INSERT INTO actions(game, name, execute, startcommand, endcommand, type) VALUES(:game, :name, :execute, :startcommand, :endcommand, :type)");
        $values = array(':game' => "Garry's Mod", ':name' => "Add to Group - ServerGuard", ':execute' => "onjoin", ':startcommand' => 'RunConsoleCommand("serverguard_setrank", "{{VAR=STEAMID}}", "{{INPUT=Rank|TYPE=varchar}}")', ':endcommand' => 'RunConsoleCommand("serverguard_setrank", "{{VAR=STEAMID}}", "{{INPUT=Rank on Expire|TYPE=varchar}}")', ':type' => 'premade');
        $sql->execute($values);
        $sql = $dbcon->prepare("UPDATE settings SET value='1.5.1' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.5.1";
    }
    if(version_compare($version, "1.5.2") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.5.2' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.5.2";
    }
    if(version_compare($version, "1.5.3") === -1){
        $sql = $dbcon->prepare("UPDATE actions SET name=:name, endcommand=:endcommand, execute=:execute WHERE name='RCON Console Command' AND game='Rust'");
        $values = array('name' => "Custom Console Command", ':endcommand' => "{{INPUT=Expiration Command|TYPE=varchar}}", ':execute' => 'choice');
        $sql->execute($values);
        $sql = $dbcon->prepare("INSERT INTO actions(game, name, execute, startcommand, endcommand, type) VALUES(:game, :name, :execute, :startcommand, :endcommand, :type)");
        $values = array(':game' => "Rust", ':name' => "Give Item", ':execute' => "onjoin", ':startcommand' => 'inventory.giveto {{VAR=STEAMID}} "{{INPUT=Item Shortname|TYPE=varchar}}" "{{INPUT=Amount|TYPE=numeric}}"', ':endcommand' => "", ':type' => "premade");
        $sql->execute($values);
        $values = array(':game' => "Rust", ':name' => "Give Blueprint", ':execute' => "onjoin", ':startcommand' => 'inventory.giveblueprintto "{{VAR=STEAMID}}" "{{INPUT=Item Shortname|TYPE=varchar}}"', ':endcommand' => "", ':type' => "premade");
        $sql->execute($values);
        $sql = $dbcon->prepare("UPDATE settings SET value='1.5.3' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.5.3";
    }
    if(version_compare($version, "1.5.4") === -1){
        $sql = $dbcon->prepare('UPDATE actions SET startcommand=\'inventory.giveblueprintto "{{VAR=STEAMID}}" "{{INPUT=Item Shortname|TYPE=varchar}}"\' WHERE name=\'Give Blueprint\'');
        $sql->execute();
        $sql = $dbcon->prepare("UPDATE settings SET value='1.5.4' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.5.4";
    }
    if(version_compare($version, "1.5.5") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.5.5' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.5.5";
    }
    if(version_compare($version, "1.6.0") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.6.0' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.6.0";
    }
    if(version_compare($version, "1.7.0") === -1){
        $sql = $dbcon->prepare("INSERT INTO actions(game, name, execute, startcommand, endcommand, type) VALUES(:game, :name, :execute, :startcommand, :endcommand, :type)");
        $values = array(':game' => "Counter-Strike: Global Offensive", ':name' => "Custom Console Command", ':execute' => "choice", ':startcommand' => '{{INPUT=Command|TYPE=varchar}}', ':endcommand' => "{{INPUT=Expiration Command|TYPE=varchar}}", ':type' => "premade");
        $sql->execute($values);
        $values = array(':game' => "Team Fortress 2", ':name' => "Custom Console Command", ':execute' => "choice", ':startcommand' => '{{INPUT=Command|TYPE=varchar}}', ':endcommand' => "{{INPUT=Expiration Command|TYPE=varchar}}", ':type' => "premade");
        $sql->execute($values);
        $values = array(':game' => "Left 4 Dead 2", ':name' => "Custom Console Command", ':execute' => "choice", ':startcommand' => '{{INPUT=Command|TYPE=varchar}}', ':endcommand' => "{{INPUT=Expiration Command|TYPE=varchar}}", ':type' => "premade");
        $sql->execute($values);
        $values = array(':game' => "all", ':name' => "MySQL Query", ':execute' => "now", ':startcommand' => '{{INPUT=DB Host|TYPE=varchar}}{{INPUT=DB Name|TYPE=varchar}}{{INPUT=DB Username|TYPE=varchar}}{{INPUT=DB Password|TYPE=varchar}}{{INPUT=DB Query|TYPE=varchar}}{{INPUT=DB Prepared Values|TYPE=varchar}}', ':endcommand' => "", ':type' => "special");
        $sql->execute($values);
        $sql = $dbcon->prepare("ALTER TABLE packages ADD sortorder INT(11) DEFAULT 1");
        $sql->execute();
        $sql = $dbcon->prepare("UPDATE settings SET value='1.7.0' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.7.0";
    }
    if(version_compare($version, "1.7.1") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.7.1' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.7.1";
    }
    if(version_compare($version, "1.7.2") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.7.2' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.7.2";
    }
    if(version_compare($version, "1.7.3") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.7.3' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.7.3";
        $sql = $dbcon->prepare("INSERT INTO actions(game, name, execute, startcommand, endcommand, type) VALUES(:game, :name, :execute, :startcommand, :endcommand, :type)");
        $values = array(':game' => "Garry's Mod", ':name' => "CombineControl Donation", ':execute' => "now", ':startcommand' => '{{INPUT=DB Host|TYPE=varchar}}{{INPUT=DB Name|TYPE=varchar}}{{INPUT=DB Username|TYPE=varchar}}{{INPUT=DB Password|TYPE=varchar}}{{INPUT=Character ID|TYPE=numeric}}{{INPUT=Donation Type|TYPE=numeric}}{{INPUT=Donation Data|TYPE=varchar}}', ':endcommand' => "", ':type' => "special");
        $sql->execute($values);
    }
    if(version_compare($version, "1.7.4") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.7.4' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.7.4";
    }
    if(version_compare($version, "1.7.6") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.7.6' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.7.6";
    }
    if(version_compare($version, "1.7.7") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.7.7' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.7.7";
    }
    if(version_compare($version, "1.7.8") === -1){
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('showtotaldonations', 0)");
        $dbcon->query("INSERT INTO settings(setting, value) VALUES('defaultlanguage', 'en')");
        $dbcon->query("ALTER TABLE packages ADD giftable INT(1) DEFAULT 1");
        $sql = $dbcon->prepare("UPDATE settings SET value='1.7.8' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.7.8";
    }
    if(version_compare($version, "1.7.10") === -1){
        $dbcon->query("ALTER TABLE packages ADD hiddenservers TEXT");
		$dbcon->query("UPDATE packages SET hiddenservers='[]'");
        $sql = $dbcon->prepare("UPDATE settings SET value='1.7.10' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.7.10";
    }
	if(version_compare($version, "1.8.0") === -1){
		if(tableExists($dbcon, 'paypalpayments') === FALSE){
        	$sql = "CREATE table paypalpayments(
        		id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        		created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        		username TEXT,
        		price DECIMAL(11,2),
        		package TEXT,
				params TEXT,
				paramsdisplay TEXT,
				vars TEXT,
				packagekey INT(11),
				status TEXT);";
        	$dbcon->exec($sql);
        }
		$dbcon->query("INSERT INTO settings(setting, value) VALUES('paypalbutton', '_xclick')");
        $sql = $dbcon->prepare("UPDATE settings SET value='1.8.0' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.8.0";
    }
	if(version_compare($version, "1.8.2") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.8.2' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.8.2";
    }
	if(version_compare($version, "1.8.3") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.8.3' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.8.3";
    }
	if(version_compare($version, "1.8.5") === -1){
        $sql = $dbcon->prepare("UPDATE settings SET value='1.8.5' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.8.5";
    }
	if(version_compare($version, "1.8.6") === -1){
		$dbcon->query("INSERT INTO settings(setting, value) VALUES('purchasecompletemessage', 'Purchase complete! You may need to reconnect to the server for it to apply.')");
        $sql = $dbcon->prepare("UPDATE settings SET value='1.8.6' WHERE setting='currentversion'");
        $sql->execute();
        $version = "1.8.6";
    }
	if(version_compare($version, "1.8.8") === -1){
		$db->query("ALTER TABLE packages ADD cansubscribe INT(1) DEFAULT 0");
        $version = Settings::UpdateVersion("1.8.8");
    }
	if(version_compare($version, "1.8.9") === -1){
        $version = Settings::UpdateVersion("1.8.9");
    }
	if(version_compare($version, "1.8.10") === -1){
		$db->query("ALTER TABLE paypalpayments MODIFY package MEDIUMTEXT");
        $version = Settings::UpdateVersion("1.8.10");
    }
	if(version_compare($version, "1.8.11") === -1){
        $version = Settings::UpdateVersion("1.8.11");
    }
	if(version_compare($version, "1.8.13") === -1){
        $version = Settings::UpdateVersion("1.8.13");
    }
	if(version_compare($version, "1.8.14") === -1){
		$sql = $dbcon->prepare("INSERT INTO actions(game, name, execute, startcommand, endcommand, type) VALUES(:game, :name, :execute, :startcommand, :endcommand, :type)");
        $values = array(':game' => "Garry's Mod", ':name' => "Popup Notification", ':execute' => "onjoin", ':startcommand' => 'SDonatePopupNotification("{{INPUT=Logo URL (Optional, must be direct link to image file)|TYPE=varchar}}", "{{INPUT=Message (Use %username% to replace with their Steam username)|TYPE=varchar}}", "{{VAR=STEAMID}}")', ':endcommand' => "", ':type' => "premade");
        $sql->execute($values);
        $version = Settings::UpdateVersion("1.8.14");
    }
	if(version_compare($version, "1.8.15") === -1){
        $version = Settings::UpdateVersion("1.8.15");
    }
	if(version_compare($version, "1.9.0") === -1){
		if(tableExists($dbcon, 'coupons') === FALSE){
        	$sql = "CREATE table coupons(
        		id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				code TINYTEXT,
        		packages TEXT,
        	 	discounttype TINYTEXT,
        		discount FLOAT,
			 	ends TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				maxuses INT(11) UNSIGNED,
				maxusesperperson INT(11) UNSIGNED);";
        	$dbcon->exec($sql);
        }
		if(tableExists($dbcon, 'sales') === FALSE){
        	$sql = "CREATE table sales(
        		id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				name TINYTEXT,
        		packages TEXT,
        	 	discounttype TINYTEXT,
        		discount FLOAT,
				starts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			 	ends TIMESTAMP DEFAULT '2030-01-01 00:00:00');";
        	$dbcon->exec($sql);
        }

		if (!columnExists($dbcon, 'transactions', 'coupon')){
			$dbcon->query("ALTER TABLE transactions ADD coupon TINYTEXT");
		}
		if (!columnExists($dbcon, 'paypalpayments', 'coupon')){
			$dbcon->query("ALTER TABLE paypalpayments ADD coupon TINYTEXT");
		}
		Settings::Create('customcss', '');
        $version = Settings::UpdateVersion("1.9.0");
    }
	if(version_compare($version, "1.9.1") === -1){
		if (!columnExists($dbcon, 'transactions', 'coupon')){
			$dbcon->query("ALTER TABLE transactions ADD coupon TINYTEXT");
		}
		if (!columnExists($dbcon, 'paypalpayments', 'coupon')){
			$dbcon->query("ALTER TABLE paypalpayments ADD coupon TINYTEXT");
		}
        $version = Settings::UpdateVersion("1.9.1");
	}
	if(version_compare($version, "1.9.2") === -1){
        $version = Settings::UpdateVersion("1.9.2");
  }
	if(version_compare($version, "1.9.3") === -1){
        $version = Settings::UpdateVersion("1.9.3");
  }
	if(version_compare($version, "1.9.4") === -1){
        $version = Settings::UpdateVersion("1.9.4");
  }
	if(version_compare($version, "1.9.5") === -1){
        $version = Settings::UpdateVersion("1.9.5");
  }
	if(version_compare($version, "1.9.7") === -1){
        $version = Settings::UpdateVersion("1.9.7");
  }
}

?>
