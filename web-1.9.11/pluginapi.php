<?php

	require_once('config.php');

	try {
		$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
		$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	} catch(PDOException $e){
		echo 'MySQL Error:' . $e->getMessage();
	exit();
	}

	if(isset($_GET['game'])){
		if(isset($_GET['apikey'])){
			if($_GET['apikey'] === $sdonateapi){

				if($_GET['game'] === "minecraft"){

					if(isset($_GET['confirmcommand'])){

						$sql = $dbcon->prepare("DELETE FROM commandstoexecute WHERE command=:command AND server=:server AND port=:port LIMIT 1");
						$values = array(':command' => $_GET['confirmcommand'], ':server' => $_GET['ip'], ':port' => $_GET['port']);
						$sql->execute($values);


					} else {

						if(!isset($_GET['checkcommands'])){
							$sql = $dbcon->prepare("SELECT * FROM commandstoexecute WHERE player=:player AND server=:server AND time <= NOW() AND port=:port AND ready=1");
							$values = array(':player' => $_GET['player'], ':server' => $_GET['ip'], ':port' => $_GET['port']);
						} else {
							$sql = $dbcon->prepare("SELECT * FROM commandstoexecute WHERE executenow=1 AND server=:server AND time <= NOW() AND port=:port AND ready=1");
							$values = array(':server' => $_GET['ip'], ':port' => $_GET['port']);
						}

						$sql->execute($values);
						$results = $sql->fetchAll(PDO::FETCH_ASSOC);
						$commandCount = $sql->rowCount();

						if($commandCount > 0){

							$commands = array();

							foreach ($results as $key => $value) {
								$command = $results[$key]['command'];
								array_push($commands, $command);
							}

							if(count($commands > 0)){
								$commandsToExecute = json_encode($commands);
								print($commandsToExecute);
							} else {
								print("No commands");
							}

						} else {
							print("No commands");
						}
					}

				} elseif($_GET['game'] === "gmod" || $_GET['game'] === "rust" || $_GET['game'] === "sourcemod"){

					if(isset($_GET['confirmcommand'])){

						$sql = $dbcon->prepare("DELETE FROM commandstoexecute WHERE command=:command AND server=:server AND port=:port LIMIT 1");
						$values = array(':command' => $_GET['confirmcommand'], ':server' => $_GET['ip'], ':port' => $_GET['port']);
						$sql->execute($values);

					} elseif(isset($_GET['addstartupcommand'])) {

						$sql = $dbcon->prepare("INSERT INTO startupcommands(server, port, command) VALUES(:server, :port, :command)");
						$values = array(':server' => $_GET['ip'], ':port' => $_GET['port'], ':command' => $_GET['addstartupcommand']);
						$sql->execute($values);

					} elseif(isset($_GET['deletestartupcommand'])) {

						$sql = $dbcon->prepare("DELETE FROM startupcommands WHERE command=:command AND server=:server AND port=:port LIMIT 1");
						$values = array(':command' => $_GET['deletestartupcommand'], ':server' => $_GET['ip'], ':port' => $_GET['port']);
						$sql->execute($values);

					} elseif(isset($_GET['getstartupcommands'])) {

						$sql = $dbcon->prepare("SELECT * FROM startupcommands WHERE server=:server AND port=:port");
						$values = array(':server' => $_GET['ip'], ':port' => $_GET['port']);
						$sql->execute($values);
						$results = $sql->fetchAll(PDO::FETCH_ASSOC);
						$commandCount = $sql->rowCount();

						if($commandCount > 0){

							$commands = array();

							foreach ($results as $key => $value) {
								$command = $results[$key]['command'];
								array_push($commands, $command);
							}

							if(count($commands > 0)){
								$commandsToExecute = json_encode($commands);
								print($commandsToExecute);
							} else {
								print("No commands");
							}

						} else {
							print("No commands");
						}

					} else {

						if(!isset($_GET['checkcommands'])){
							$sql = $dbcon->prepare("SELECT * FROM commandstoexecute WHERE player=:player AND server=:server AND time <= NOW() AND port=:port AND ready=1");
							$values = array(':player' => $_GET['player'], ':server' => $_GET['ip'], ':port' => $_GET['port']);
						} else {
							$sql = $dbcon->prepare("SELECT * FROM commandstoexecute WHERE executenow=1 AND server=:server AND time <= NOW() AND port=:port AND ready=1");
							$values = array(':server' => $_GET['ip'], ':port' => $_GET['port']);
						}

						$sql->execute($values);
						$results = $sql->fetchAll(PDO::FETCH_ASSOC);
						$commandCount = $sql->rowCount();

						if($commandCount > 0){

							$commands = array();

							foreach ($results as $key => $value) {
								$command = $results[$key]['command'];
								array_push($commands, $command);
							}

							if(count($commands > 0)){
								$commandsToExecute = json_encode($commands);
								if ($_GET['game'] === "sourcemod"){
									$commandsToExecute = implode("*|NEXTCOMMAND|*", $commands);
								}
								print($commandsToExecute);
							} else {
								print("No commands");
							}

						} else {
							print("No commands");
						}

					}

				}
			}
		}
	}

?>
