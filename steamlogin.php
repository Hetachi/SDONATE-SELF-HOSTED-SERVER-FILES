<?php

	require 'steamauth/steamauth.php';

	if(!isset($_SESSION['returnurl'])){
		$_SESSION['returnurl'] = 'index.php';
	}

	require_once('config.php');

	try {
		$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
		$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	} catch(PDOException $e){
		echo 'MySQL Error:' . $e->getMessage();
	exit();
	}

	if(isset($_SESSION['steamid']) && !isset($_SESSION['username'])){
		require 'steamauth/userInfo.php';
		$username = $steamprofile['personaname'];

		$sql = $dbcon->prepare("SELECT username, usertype FROM users WHERE steamid=:steamid");
		$values = array(':steamid' => $_SESSION['steamid']);
		$sql->execute($values);
		$results = $sql->fetchAll(PDO::FETCH_NUM);		$resultscount = count($results);

		if($resultscount === 0){
			$usernametaken = TRUE;
			$counter = 0;
			$avatar = $steamprofile['avatarfull'];

			while($usernametaken === TRUE){
				$sql1 = $dbcon->prepare("SELECT username FROM users WHERE username=:username");
				$values = array(':username' => $username);
				$sql1->execute($values);
				$results1 = $sql1->fetchAll(PDO::FETCH_NUM);				$resultscount1 = count($results1);

				if($resultscount1 > 0){
					$counter++;
					$username = $username . $counter;
				} else {
					$usernametaken = FALSE;
					break;
				}
			}

			$userType = "user";

			if($demoMode === true){
				$userType = "admin";
				$_SESSION['admin'] = TRUE;
			}

			$sql = $dbcon->prepare("INSERT INTO users(username, steamid, avatar, usertype) VALUES(:username, :steamid, :avatar, :usertype)");
			$values = array(':username' => $username, ':steamid' => $_SESSION['steamid'], ':avatar' => $avatar, ':usertype' => $userType);
			$sql->execute($values);
			$_SESSION['username'] = $username;
		} else {
			$_SESSION['username'] = $results[0][0];
			if($results[0][1] === "admin"){
				$_SESSION['admin'] = TRUE;
			}
		}

		unset($_SESSION['steamid']);
	} else {
		$_SESSION['linksteam'] = true;
	}

	header('Location: ' . $_SESSION['returnurl']);

?>
