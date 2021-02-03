<?php

require_once(dirname(__FILE__) . "/../config.php");
require('../require/classes.php');
$user = new User();
$pageError = [];

if(isset($_POST['forgotpassword'])){

	$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='hometitle'");
	$sql->execute();
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	$hometitle = $results[0]['value'];

	$sql = $dbcon->prepare("SELECT email FROM users WHERE username=:username");
	$values = array(':username' =>$_POST['username']);
	$sql->execute($values);
	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	$resultscount = $sql->rowCount();

	if($resultscount > 0){

		if($results[0]['email'] !== ""){

			$possibleChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	        $resetkey = '';

	        for($i = 0; $i < 20; $i++) {
	            $rand = rand(0, strlen($possibleChars) - 1);
	            $resetkey .= substr($possibleChars, $rand, 1);
	        }

			$url = $sdonateapiurl;
			$data = array('action' => 'forgotpassword', 'apikey' => $sdonateapi, 'email' => $results[0]['email'], 'storename' => $hometitle, 'link' => $dir, 'username' => $_POST['username'], 'resetkey' => $resetkey);
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
			} elseif($result === "toosoon") {
				array_push($pageError, getLangString("24hr-password-reset-error"));
			} elseif($result === "emailproblem") {
				array_push($pageError, getLangString("password-reset-email-error"));
			} else {
				$sql = $dbcon->prepare("INSERT INTO resetpassword(username, resetkey, expires) VALUES(:username, :resetkey, NOW() +INTERVAL 1 DAY)");
				$values = array(':username' => $_POST['username'], ':resetkey' => $resetkey);
				$sql->execute($values);
			}

		}

	}

}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
