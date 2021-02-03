<?php

require_once(dirname(__FILE__) . "/../config.php");
require('../require/classes.php');
$user = new User();
$pageError = [];

$sql = $dbcon->prepare("SELECT value FROM settings");
$sql->execute();
$result = $sql->fetchAll(PDO::FETCH_ASSOC);

if(isset($_POST['login-type']) && $result[9]['value'] != 1){
	if(isset($_SESSION['username'])){
		session_destroy();
		if(!isset($_SESSION)){
		    session_start();
		}
	}

	if(isset($_POST['g-recaptcha-response']) OR (empty($recaptchasecretkey) AND empty($recaptchasitekey))){

		$moveOn = false;

		if(!empty($recaptchasecretkey) AND !empty($recaptchasitekey)){

			$url = 'https://www.google.com/recaptcha/api/siteverify';
			$params = array('secret' => $recaptchasecretkey,
							'response' => $_POST['g-recaptcha-response']
							);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$returned = curl_exec($curl);

			if(curl_errno($curl)){
				array_push($pageError, getLangString("process-request-error"));
				array_push($pageError, curl_errno($curl));
			} else {
				$returnedDecoded = json_decode($returned, true);
				if(isset($returnedDecoded['success'])){
					if($returnedDecoded['success'] === true){
						$moveOn = true;
					} else {
						array_push($pageError, getLangString("retry-captcha"));
					}
				} else {
					array_push($pageError, getLangString("process-request-error"));
				}
			}

		} else {
			$moveOn = true;
		}

		if($moveOn === true) {
			if($_POST['login-type'] == "login"){
				$username = $_POST['username'];
				$password = $_POST['password'];
				$sql = $dbcon->prepare("SELECT password, usertype FROM users WHERE username=:username");
				$value = array(':username' => $username);
				$sql->execute($value);
				$result = $sql->fetchAll(PDO::FETCH_NUM);
				$count = 0;
				foreach ($result as $key => $value) {
					$count = $count + 1;
				}
				if($count < 1){
					array_push($pageError, getLangString("incorrect-login"));
				} else {
					if(password_verify($password, $result[0][0]) == true){
						$_SESSION['username'] = $username;
						if($result[0][1] === 'admin'){
							$_SESSION['admin'] = TRUE;
						}
					} else {
						array_push($pageError, getLangString("incorrect-login"));
					}
				}
			} elseif ($_POST['login-type'] == "register"){
				$username = $_POST['username'];
				$password = $_POST['password'];
				$confirmpassword = $_POST['confirm-password'];
				$email = $_POST['email'];
				$uservalid = FALSE;
				$passvalid = FALSE;
				$emailvalid = FALSE;
				$sql = $dbcon->prepare("SELECT password FROM users WHERE username=:username");
				$value = array(':username' => $username);
				$sql->execute($value);
				$result = $sql->fetchAll(PDO::FETCH_NUM);
				$count = 0;
				foreach ($result as $key => $value) {
					$count = $count + 1;
				}
				if($count > 0){
					array_push($pageError, getLangString("username-taken"));
				} else {
					if($password === $confirmpassword){
						if(strlen($username) < 6 OR strlen($username) > 32){
								array_push($pageError, getLangString("username-length-error"));
						} else {
								$uservalid = TRUE;
						}
						if(strlen($password) < 8){
							array_push($pageError, getLangString("password-length-error"));
						} else {
							$passvalid = TRUE;
						}

						$url = $sdonateapiurl;
						$data = array('action' => 'validateemail', 'apikey' => $sdonateapi, 'email' => $_POST['email']);
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
							$emailvalid = true;
						}

						if($uservalid === TRUE AND $passvalid === TRUE AND $emailvalid === true){
							$hashed = password_hash($password, PASSWORD_DEFAULT);
							if($hashed === FALSE){
								array_push($pageError, getLangString("account-registration-error"));
							} else {
								$sql = $dbcon->prepare("INSERT INTO users(username, email, password, usertype) VALUES(:username, :email, :password, :usertype)");
								$values = array(':username' => $username, ':email' => $email, ':password' => $hashed, ':usertype' => 'user');
								$sql->execute($values);
								$_SESSION['username'] = $username;
							}
						}
					} else {
						array_push($pageError, getLangString("password-mismatch-error"));
					}
				}
			} elseif($_POST['login-type'] == "resetpassword"){

				$key = $_POST['reset-password-key'];
				$username = $_POST['username'];
				$password = $_POST['password'];
				$confirmpassword = $_POST['confirmpassword'];

				$sql = $dbcon->prepare("SELECT * FROM resetpassword WHERE username=:username AND resetkey=:resetkey");
				$values = array(':username' => $username, ':resetkey' => $key);
				$sql->execute($values);
				$results = $sql->fetchAll(PDO::FETCH_ASSOC);
				$resultscount = $sql->rowCount();

				if($resultscount > 0){
					if(strtotime($results[0]['expires']) > time() - 86400){
						if($password === $confirmpassword){
							if(strlen($password) > 7){
								$hashed = password_hash($password, PASSWORD_DEFAULT);
								if($hashed === FALSE){
									array_push($pageError, getLangString("password-change-error"));
								} else {
									$sql = $dbcon->prepare("UPDATE users SET password=:password WHERE username=:username");
									$values = array(':password' => $hashed, ':username' => $username);
									$sql->execute($values);
									$sql = $dbcon->prepare("DELETE FROM resetpassword WHERE username=:username AND resetkey=:resetkey");
									$values = array(':username' => $username, ':resetkey' => $key);
									$sql->execute($values);
								}
							} else {
								array_push($pageError, getLangString("password-length-error"));
							}
						} else {
							array_push($pageError, getLangString("password-mismatch-error"));
						}
					} else {
						array_push($pageError, getLangString("password-link-expired-error"));
					}
				} else {
					array_push($pageError, getLangString("password-link-invalid-error"));
				}
			} else {
				array_push($pageError, getLangString("invalid-request-error"));
			}
		}
	} else {
		array_push($pageError, getLangString("retry-captcha"));
	}
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
