<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{
    if(isset($_POST['deleteimage'])){
        $sql = $dbcon->prepare("UPDATE servers SET img='' WHERE id=:id");
        $value = array(':id' => $_POST['id']);
        $sql->execute($value);
    }

    if(isset($_POST['testserver'])){

        $serverid = $_POST['testserver'];
        $command = $_POST['command'];

        $sql = $dbcon->prepare("SELECT steamid FROM users WHERE username=:username");
        $values = array(':username' => $_SESSION['username']);
        $sql->execute($values);
        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
        $steamid = $results[0]['steamid'];

        $sql = $dbcon->prepare("SELECT * FROM servers WHERE id=:id");
        $values = array(':id' => $serverid);
        $sql->execute($values);
        $rowcount = $sql->rowCount();

        if($rowcount > 0){
            $serverInfo = $sql->fetchAll(PDO::FETCH_ASSOC);
            $gameid = $serverInfo[0]['game'];
            $sql = $dbcon->prepare("SELECT gamename FROM games WHERE id=:id");
            $values = array(':id' => $gameid);
            $sql->execute($values);
            $gameinfo = $sql->fetchAll(PDO::FETCH_ASSOC);

            if($gameinfo[0]['gamename'] === "Garry's Mod"){
                $command = 'game.ConsoleCommand("' . addslashes($command) . '\n")';
                $sql = $dbcon->prepare("INSERT INTO commandstoexecute(time, server, port, command, player, executenow) VALUES(NOW(), :server, :port, :command, :player, 1)");
                $values = array(':server' => $serverInfo[0]['ip'], ':port' => $serverInfo[0]['port'], ':command' => $command, ':player' => $steamid);
                $sql->execute($values);
            } elseif($gameinfo[0]['gamename'] === "Minecraft" OR $gameinfo[0]['gamename'] === "Rust" OR $gameinfo[0]['gamename'] === "Counter-Strike: Global Offensive" OR $gameinfo[0]['gamename'] === "Team Fortress 2" OR $gameinfo[0]['gamename'] === "Left 4 Dead 2"){
                $sql = $dbcon->prepare("INSERT INTO commandstoexecute(time, server, port, command, player, executenow) VALUES(NOW(), :server, :port, :command, :player, 1)");
                $values = array(':server' => $serverInfo[0]['ip'], ':port' => $serverInfo[0]['port'], ':command' => $command, ':player' => $_SESSION['username']);
                $sql->execute($values);
            } else {
                sendRCONCommand($serverInfo[0]['ip'], $serverInfo[0]['port'], $serverInfo[0]['rconpass'], $command);
            }

        } else {
            array_push($pageError, getLangString("invalid-server-error"));
        }

    }

    if(isset($_POST['editserver'])){

        $sql = $dbcon->prepare("SELECT ip, port FROM servers WHERE id=:id");
        $values = array(':id' => $_POST['serverid']);
        $sql->execute($values);
        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
        $oldip = $results[0]['ip'];
        $oldport = $results[0]['port'];

        $sql = $dbcon->prepare("UPDATE servers SET game=:game, name=:name, ip=:ip, port=:port, rconpass=:rconpass, enabled=:enabled WHERE id=:id");

        if(isset($_POST['serverenabled'])){
            $enabled = 1;
        } else {
            $enabled = 0;
        }

        if(strlen($_POST['servername']) > 200){
            array_push($pageError, "Server Name must be 200 characters or less.");
        }

        if(strlen($_POST['serverport']) > 10){
            array_push($pageError, "Server Port must be 10 characters or less.");
        }

        if(strlen($_POST['servername']) < 201 AND strlen($_POST['serverport']) < 11){
            $settings = array(':game' => $_POST['servergame'], ':name' => $_POST['servername'], ':ip' => $_POST['serverip'], ':port' => $_POST['serverport'], ':rconpass' => $_POST['serverrcon'], ':enabled' => $enabled, ':id' => $_POST['serverid']);
            $sql->execute($settings);

            $sql = $dbcon->prepare("UPDATE commandstoexecute SET server=:server, port=:port WHERE server=:oldserver AND port=:oldport");
            $values = array(':server' => $_POST['serverip'], ':port' => $_POST['serverport'], ':oldserver' => $oldip, ':oldport' => $oldport);
            $sql->execute($values);

            if(isset($_FILES['serverimagefile'])){
                if($_FILES["serverimagefile"]["size"] != 0){
                    $target_dir = getcwd() . DIRECTORY_SEPARATOR . "../../img" . DIRECTORY_SEPARATOR . "servers" . DIRECTORY_SEPARATOR . $_POST['serverid'] . DIRECTORY_SEPARATOR;
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $possibleChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                    $randomString = '';

                    for($i = 0; $i < 5; $i++) {
                        $rand = rand(0, strlen($possibleChars) - 1);
                        $randomString .= substr($possibleChars, $rand, 1);
                    }

                    $target_file = $target_dir . basename($_FILES["serverimagefile"]["name"]);
                    $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                    $newImageName = $randomString . "." . $imageFileType;
                    $target_file = $target_dir . $newImageName;
					if(function_exists("exif_imagetype")){
                    	$imageType = exif_imagetype($_FILES["serverimagefile"]["tmp_name"]);
					} else {
						$imageType = IMAGETYPE_JPEG;
					}

                    if($imageType !== false AND ($imageType === IMAGETYPE_JPEG OR $imageType === IMAGETYPE_PNG OR $imageType === IMAGETYPE_GIF) AND ($imageFileType === "png" OR $imageFileType === "jpg" OR $imageFileType === "jpeg" OR $imageFileType === "gif")) {
                        $uploadOk = 1;
                    } else {
                        $uploadOk = 0;
                        array_push($pageError, getLangString("invalid-image-error"));
                    }

                    if($uploadOk === 1){
                        array_map('unlink', glob($target_dir . "*"));
                        if(move_uploaded_file($_FILES["serverimagefile"]["tmp_name"], $target_file)) {
                            $uploadComplete = 1;
                            $sql = $dbcon->prepare("UPDATE servers SET img=:filepath WHERE id=:id");
                            $settings = array(':filepath' => $newImageName, ':id' => $_POST['serverid']);
                            $sql->execute($settings);
                        } else {
                            $uploadComplete = 0;
                            array_push($pageError, getLangString("image-upload-error"));
                        }
                    }
                }
            }
        }
    }

    if(isset($_POST['addserver'])){

        $sql = $dbcon->prepare("INSERT INTO servers (game, name, ip, port, rconpass, enabled) VALUES(:game, :name, :ip, :port, :rconpass, :enabled)");

        if(isset($_POST['serverenabled'])){
            $enabled = 1;
        } else {
            $enabled = 0;
        }

        if(strlen($_POST['servername']) > 200){
            array_push($pageError, "Server Name must be 200 characters or less.");
        }

        if(strlen($_POST['serverport']) > 10){
            array_push($pageError, "Server Port must be 10 characters or less.");
        }

        if(strlen($_POST['servername']) < 201 AND strlen($_POST['serverport']) < 11 AND $uploadComplete = 1){

            $sql1 = $dbcon->prepare("SELECT * FROM servers");
            $sql1->execute();
            $results = $sql1->fetchAll(PDO::FETCH_ASSOC);
            $unique = true;

            foreach ($results as $key => $value) {
                if($value['ip'] === $_POST['serverip'] && $value['port'] === $_POST['serverport']){
                    array_push($pageError, "You already have a server with this IP and port: " . $value['name']);
                    $unique = false;
                    break;
                }
            }

            if($unique === true){
                $settings = array(':game' => $_POST['servergame'], ':name' => $_POST['servername'], ':ip' => $_POST['serverip'], ':port' => $_POST['serverport'], ':rconpass' => $_POST['serverrcon'], ':enabled' => $enabled);
                $sql->execute($settings);
                $insertid = $dbcon->lastInsertId();

                if(isset($_FILES['serverimagefile'])){
                    if($_FILES["serverimagefile"]["size"] != 0){
                        $target_dir = getcwd() . DIRECTORY_SEPARATOR . "../../img" . DIRECTORY_SEPARATOR . "servers" . DIRECTORY_SEPARATOR . $insertid . DIRECTORY_SEPARATOR;
                        if (!file_exists($target_dir)){
                            mkdir($target_dir, 0777, true);
                        }
                        $possibleChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                        $randomString = '';

                        for($i = 0; $i < 5; $i++) {
                            $rand = rand(0, strlen($possibleChars) - 1);
                            $randomString .= substr($possibleChars, $rand, 1);
                        }

                        $target_file = $target_dir . basename($_FILES["serverimagefile"]["name"]);
                        $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                        $newImageName = $randomString . "." . $imageFileType;
                        $target_file = $target_dir . $newImageName;
						if(function_exists("exif_imagetype")){
							$imageType = exif_imagetype($_FILES["serverimagefile"]["tmp_name"]);
						} else {
							$imageType = IMAGETYPE_JPEG;
						}

                        if($imageType !== false AND ($imageType === IMAGETYPE_JPEG OR $imageType === IMAGETYPE_PNG OR $imageType === IMAGETYPE_GIF) AND ($imageFileType === "png" OR $imageFileType === "jpg" OR $imageFileType === "jpeg" OR $imageFileType === "gif")) {
                            $uploadOk = 1;
                        } else {
                            $uploadOk = 0;
                            array_push($pageError, getLangString("invalid-image-error"));
                        }

                        if($uploadOk === 1){
                            array_map('unlink', glob($target_dir . "*"));
                            if(move_uploaded_file($_FILES["serverimagefile"]["tmp_name"], $target_file)) {
                                $uploadComplete = 1;
                                $sql = $dbcon->prepare("UPDATE servers SET img=:filepath WHERE id=:id");
                                $settings = array(':filepath' => $newImageName, ':id' => $insertid);
                                $sql->execute($settings);
                            } else {
                                $uploadComplete = 0;
                                array_push($pageError, getLangString("image-upload-error"));
                                $sql = $dbcon->prepare("DELETE FROM servers WHERE id=:id");
                                $value = array(':id' => $insertid);
                                $sql->execute($value);
                            }
                        } else {
                            $sql = $dbcon->prepare("DELETE FROM packages WHERE id=:id");
                            $value = array(':id' => $insertid);
                            $sql->execute($value);
                        }
                    }
                }
            }
        }
    }

    if(isset($_POST['deleteserver'])){
        $deleteid = $_POST['deleteserver'];
        $sql = $dbcon->prepare("SELECT id, servers FROM packages");
        $sql->execute();
        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $key => $value) {
            $serverString = $results[$key]['servers'];
            $servers = explode(",", $serverString);
            foreach ($servers as $key1 => $value1) {
                if($value1 == $deleteid){
                    unset($servers[$key1]);
                }
            }
            $serverString = implode(",", $servers);
            $sql = $dbcon->prepare("UPDATE packages SET servers=:servers WHERE id=:id");
            $packageid = $results[$key]['id'];
            $settings = array(':servers' => $serverString, ':id' => $packageid);
            $sql->execute($settings);
        }

        $sql = $dbcon->prepare("DELETE FROM servers WHERE id=:id");
        $value = array(':id' => $deleteid);
        $sql->execute($value);

    }
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
