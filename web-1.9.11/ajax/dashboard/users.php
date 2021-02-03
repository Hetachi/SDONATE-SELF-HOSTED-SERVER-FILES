<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{
    if(isset($_POST["changeusertype"])) {
        $usertype = $_POST["changeusertype"];
        $username = $_POST["username"];
        $sql = $dbcon->prepare("SELECT * FROM users WHERE usertype='admin' LIMIT 1");
        $sql->execute();
        $admins = $sql->fetchAll(PDO::FETCH_ASSOC);
        if($admins[0]["username"] == $_SESSION["username"]){
            if($usertype == "admin"){
                $sql = $dbcon->prepare("UPDATE users SET usertype=:usertype WHERE username=:username");
                $values = array(':usertype' => 'admin', 'username' => $username);
                $sql->execute($values);
            } elseif($usertype == "user") {
                if($admins[0]["username"] != $username){
                    $sql = $dbcon->prepare("UPDATE users SET usertype=:usertype WHERE username=:username");
                    $values = array(':usertype' => 'user', 'username' => $username);
                    $sql->execute($values);
                } else {
                    array_push($pageError, getLangString("cant-set-self-user"));
                }
            }
        } else {
            array_push($pageError, getLangString("cant-change-usertype"));
        }
    }

    if(isset($_POST['editcredit'])){
        $username = $_POST['editcredituser'];
        if(is_numeric($_POST['editcredit'])){
            $sql = $dbcon->prepare("UPDATE users SET credit=:credit WHERE username=:username");
            $values = array(':credit' => $_POST['editcredit'], ':username' => $username);
            $sql->execute($values);
        } else {
            array_push($pageError, getLangString("credit-not-number"));
        }
    }

    if(isset($_POST["revokepackage"])){
        $id = $_POST["revokepackage"];
        $sql = $dbcon->prepare("SELECT * FROM transactions WHERE id=:id");
        $values = array(':id' => $id);
        $sql->execute($values);
        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
        $endCommands = json_decode($results[0]["endcommands"]);
        $player = $results[0]["username"];
        $status = $results[0]["status"];

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

        if($status === "complete - Sandbox"){
            $sql = $dbcon->prepare("UPDATE transactions SET status='revoked - Sandbox' WHERE id=:id");
            $values = array(':id' => $id);
            $sql->execute($values);
        } else {
            $sql = $dbcon->prepare("UPDATE transactions SET status='revoked' WHERE id=:id");
            $values = array(':id' => $id);
            $sql->execute($values);
        }

    }

    if(isset($_POST['assignpackagesubmit'])){

        $packageid = intval($_POST['assignpackagesubmit']);

        $sql = $dbcon->prepare("SELECT * FROM packages WHERE id=:id");
        $values = array(':id' => $packageid);
        $sql->execute($values);
        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
        $package = $results[0];

        $commands = json_decode($package['commands']);
        $paramDisplay = [];
        $paramValues = [];
        $additionalPrice = 0;

        $goToNextStage = false;

        $gameid = $package["game"];
        $sql = $dbcon->prepare("SELECT gamename FROM games WHERE id=:game");
        $values = array(':game' => $gameid);
        $sql->execute($values);
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        $game = $result[0]['gamename'];

        if($game !== "Minecraft"){
            $sql = $dbcon->prepare("SELECT steamid FROM users WHERE username=:username");
            $values = array(':username' => $_POST['assignpackageuser']);
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
            if($game === "Minecraft"){
                $username = $_POST["Minercraft_Username"];
                $vars['Minecraft_Username'] = $username;
                if(preg_match('/^[a-zA-Z0-9_]{1,16}$/', $username) === 1){
                    $goToNextStage = true;
                } else {
                    array_push($pageError, getLangString("invalid-mcusername-error"));
                }
            }
        }

        if($goToNextStage === true){

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

            $ready = 1;
            $sql = $dbcon->prepare("SELECT email FROM users WHERE username=:username");
            $values = array(':username' => $_POST['assignpackageuser']);
            $sql->execute($values);
            $results = $sql->fetchAll(PDO::FETCH_ASSOC);
            $emailAddress = $results[0]['email'];
            $content = [$_SESSION['username'], $package];
            sendEmail("purchasecomplete", $emailAddress, $content);

            $endCommands = processCommands($package, $paramValues, $vars, '', 1);


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

            $sql = $dbcon->prepare("INSERT INTO transactions(purchaser, usernametype, username, game, expires, expiretime, endcommands, transactionid, package, packageid, paymentmethod, value, status, params) VALUES(:purchaser, :usernametype, :username, :game, :expires, :expiretime, :endcommands, :transactionid, :package, :packageid, :paymentmethod, :value, :status, :params)");
            $values = array(':purchaser' => $_POST['assignpackageuser'], ':usernametype' => $usernametype, ':username' => $playerName, ':game' => $game, ':expires' => $package["expires"], ':expiretime' => $expireDate, ':endcommands' => json_encode($endCommands), ':transactionid' => "", ':package' => $package['title'], ':packageid' => $package['id'], ':paymentmethod' => "Assigned", ':value' => 0.00, ':status' => 'assigned', ':params' => json_encode($paramDisplay));
            $sql->execute($values);

        }

    }

    if(isset($_POST["deletetransaction"])){
        $sql = $dbcon->prepare("DELETE FROM transactions WHERE id=:id");
        $values = array(':id' => $_POST["deletetransaction"]);
        $sql->execute($values);
    }

    if(isset($_POST["deletealltransactions"])){
        $sql = $dbcon->prepare("DELETE FROM transactions");
        $sql->execute();
    }

	if(isset($_POST["reruntransactions"])){
		foreach ($_POST as $key => $value) {
			if (substr($key, 0, strlen('rerun-package-')) == 'rerun-package-')
			{
				$packageid = substr($key, strlen('rerun-package-'));
				$sql = $dbcon->prepare("SELECT * FROM transactions WHERE packageid=:packageid");
				$values = [':packageid' => $packageid];
				$sql->execute($values);
				$transactions = $sql->fetchAll(PDO::FETCH_ASSOC);
				foreach ($transactions as $key1 => $value1) {
					if ((strtotime($value1["expiretime"]) > time() && $value1["expires"] > 0) || !isset($_POST['excludexpired']))
					{
						$sql = $dbcon->prepare("SELECT * FROM packages WHERE id=:packageid");
						$values = [':packageid' => $packageid];
						$sql->execute($values);
						$packages = $sql->fetchAll(PDO::FETCH_ASSOC);
						if (isset($packages[0]))
						{

							$package = $packages[0];
							$commands = json_decode($package['commands']);
							$params = json_decode($value1["params"]);
							$paramValues = [];

							foreach ($commands as $key2 => $value2) {
				                foreach($value2->params as $key3 => $value3){
				                    if(substr($value3, 0, 14) === "{{USERCHOICE}}"){
										$param = substr($params[$key2][$key3], strpos(':'));
										$paramValues[$key2][$key3] = $param;
				                    } else {
				                        $paramValues[$key2][$key3] = $value2->params[$key3];
				                    }
				                }
							}

							$gameid = $package["game"];
					        $sql = $dbcon->prepare("SELECT gamename FROM games WHERE id=:game");
					        $values = array(':game' => $gameid);
					        $sql->execute($values);
					        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
					        $game = $result[0]['gamename'];

							$username = $value1['username'];

							if($game !== "Minecraft"){
				                $vars['STEAMID'] = $username;
				                $vars['STEAMUSERNAME'] = '';
				                $authserver = bcsub($username, '76561197960265728') & 1;
				                $authid = (bcsub($username, '76561197960265728')-$authserver)/2;
				                $steamid32 = "STEAM_0:$authserver:$authid";
				                $vars['STEAMID32'] = $steamid32;
								$vars['STEAMID3'] = "U:1:" . $authid * 2;
				                $goToNextStage = true;
					        } else {
					            if($game === "Minecraft"){
					                $username = $_POST["Minercraft_Username"];
					                $vars['Minecraft_Username'] = $username;
					                if(preg_match('/^[a-zA-Z0-9_]{1,16}$/', $username) === 1){
					                    $goToNextStage = true;
					                } else {
					                    array_push($pageError, getLangString("invalid-mcusername-error"));
					                }
					            }
					        }

							$endCommands = false;

							if (isset($_POST['rerunendcommands']))
							{
								$endCommands = true;
							}

							processCommands($package, $paramValues, $vars, '', 1, $endCommands);

						}
					}
				}
			}
		}
	}
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
