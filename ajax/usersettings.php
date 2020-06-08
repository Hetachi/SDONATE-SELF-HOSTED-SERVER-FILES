<?php

require_once(dirname(__FILE__) . "/../config.php");
require('../require/classes.php');
$user = new User();
$pageError = [];

if(isset($_POST['linksteamaccount']) && isset($_SESSION['username']) && isset($_SESSION['steamid'])){

	require dirname(__FILE__) . '/../steamauth/userInfo.php';

	$sql = $dbcon->prepare("SELECT steamid FROM users WHERE username=:username");
	$values = array(':username' => $_SESSION['username']);
	$sql->execute($values);
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);

	if(empty($results[0]['steamid'])){
		$sql = $dbcon->prepare("SELECT username FROM users WHERE steamid=:steamid");
		$values = array(':steamid' => $_SESSION['steamid']);
		$sql->execute($values);
		$results = $sql->fetchAll(PDO::FETCH_ASSOC);
		$resultscount = count($results);
		$avatar = $steamprofile['avatarfull'];

		if($resultscount !== 0){
			$sql = $dbcon->prepare("UPDATE transactions SET purchaser=:username WHERE purchaser=:steamusername");
			$values = array(':username' => $_SESSION['username'], ':steamusername' => $results[0]['username']);
			$sql->execute($values);

			$sql = $dbcon->prepare("DELETE FROM users WHERE steamid=:steamid");
			$values = array(':steamid' => $_SESSION['steamid']);
			$sql->execute($values);
		}

		$sql = $dbcon->prepare("UPDATE users SET steamid=:steamid, avatar=:avatar WHERE username=:username");
		$values = array(':steamid' => $_SESSION['steamid'], ':avatar' => $avatar, ':username' => $_SESSION['username']);
		$sql->execute($values);
	}

	unset($_SESSION['steamid']);
	unset($_SESSION['linksteam']);

}

if(isset($_POST['changeemail']) && isset($_SESSION['username']) && $_SESSION['csrftoken'] == $_POST['csrftoken']){

	$url = $sdonateapiurl;
	$data = array('action' => 'validateemail', 'apikey' => $sdonateapi, 'email' => $_POST['changeemail']);
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
		array_push($pageError, getLangString("process-request-error"));
	} elseif($result === "apiproblem") {
		array_push($pageError, getLangString("api-key-problem"));
	} elseif($result === "EMAILINVALID") {
		array_push($pageError, getLangString("invalid-email-error"));
	} elseif($result === "EMAILVALID") {
		$sql = $dbcon->prepare("UPDATE users SET email=:email WHERE username=:username");
		$values = array(':email' => $_POST['changeemail'], ':username' => $_SESSION['username']);
		$sql->execute($values);
	}

}

if(isset($_POST['changepassword']) && isset($_SESSION['username']) && $_SESSION['csrftoken'] == $_POST['csrftoken']){

	$currentPassword = $_POST['changepasswordcurrent'];
	$password = $_POST['changepassword'];
	$confirmpassword = $_POST['changepasswordconfirm'];

	$sql = $dbcon->prepare("SELECT password FROM users WHERE username=:user");
	$values = array(':user' => $_SESSION['username']);
	$sql->execute($values);
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);

	if(password_verify($currentPassword, $results[0]['password']) === true){
		if(strlen($password) > 7){
			if($password === $confirmpassword){
				$hashed = password_hash($password, PASSWORD_DEFAULT);
				if($hashed === FALSE){
					array_push($pageError, getLangString("password-change-error"));
				} else {
					$sql = $dbcon->prepare("UPDATE users SET password=:password WHERE username=:username");
					$values = array(':password' => $hashed, ':username' => $_SESSION['username']);
					$sql->execute($values);
				}
			} else {
				array_push($pageError, getLangString("password-mismatch-error"));
			}
		} else {
			array_push($pageError, getLangString("password-length-error"));
		}
	} else {
		array_push($pageError, getLangString("incorrect-password-error"));
	}

}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
