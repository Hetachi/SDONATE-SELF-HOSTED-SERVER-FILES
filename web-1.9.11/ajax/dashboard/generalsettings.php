<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{
    if(isset($_POST['maintenancemode']) && $demoMode === false){
        $maintenancemode = $_POST['maintenancemode'];
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='maintenancemode'");
        $value = array(':value' => $maintenancemode);
        $sql->execute($value);
    }

    if(isset($_POST['loginmode']) && $demoMode === false){

        $sql = $dbcon->prepare("SELECT steamid FROM users WHERE usertype='admin' LIMIT 1");
        $sql->execute();
        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
        $adminSteamLinked = false;

        if(!empty($results[0]['steamid'])){
            $adminSteamLinked = true;
        }

        if($_POST['loginmode'] == "0" || $adminSteamLinked === true){
            $loginmode = $_POST['loginmode'];
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='loginmode'");
            $value = array(':value' => $loginmode);
            $sql->execute($value);
        }

        if($_POST['loginmode'] == "1" && $adminSteamLinked === false){
            array_push($pageError, getLangString("steam-admin-account-error"));
        }

    }

    if(isset($_POST['paypalenabled']) && $demoMode === false){
        $paypalenabled = $_POST['paypalenabled'];
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='paypalenabled'");
        $value = array(':value' => $paypalenabled);
        $sql->execute($value);
    }

    if(isset($_POST['paypalbutton']) && $demoMode === false){
        $paypalbutton = $_POST['paypalbutton'];
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='paypalbutton'");
        $value = array(':value' => $paypalbutton);
        $sql->execute($value);
    }

    if(isset($_POST['creditsenabled']) && $demoMode === false){
        $creditsenabled = $_POST['creditsenabled'];
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='creditsenabled'");
        $value = array(':value' => $creditsenabled);
        $sql->execute($value);
    }

    if(isset($_POST['paymentmode']) && $demoMode === false){
        $paymentmode = $_POST['paymentmode'];
        if($paymentmode == "directpurchase" || $paymentmode == "creditpurchase"){
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='paymentmode'");
            $value = array(':value' => $paymentmode);
            $sql->execute($value);
            if($paymentmode == "creditpurchase"){
                $creditsenabled = "1";
                $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='creditsenabled'");
                $value = array(':value' => $creditsenabled);
                $sql->execute($value);
            }
        }
    }

    if(isset($_POST['paypalemail'])){
        $paypalemail = $_POST['paypalemail'];
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='paypalemail'");
        $value = array(':value' => $paypalemail);
        $sql->execute($value);
    }

    if(isset($_POST['paypalsandbox']) && $demoMode === false){
        $paypalsandbox = $_POST['paypalsandbox'];
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='paypalsandbox'");
        $value = array(':value' => $paypalsandbox);
        $sql->execute($value);
    }

    if($currencycode === "EUR"){
        if(isset($_POST['starpassenabled'])){
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='starpassenabled'");
            $value = array(':value' => $_POST['starpassenabled']);
            $sql->execute($value);
        }
        if(isset($_POST['starpasscode'])){
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='starpasscode'");
            $value = array(':value' => $_POST['starpasscode']);
            $sql->execute($value);
        }
    }

    if(isset($_POST['logotype'])){
        if($_POST['logotype'] === 'logotypetext'){
            $sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='hometitle'");
            $sql->execute();
            $result = $sql->fetchAll(PDO::FETCH_ASSOC);
            $logotext = $result[0]['value'];
            if(!empty($logotext)){
                if(strlen($logotext < 10001)){
                    $dbcon->query("UPDATE settings SET value='text' WHERE setting='logoimgtype'");
                    $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='logoimgcontent'");
                    $value = array(':value' => $logotext);
                    $sql->execute($value);
                } else {
                    array_push($pageError,"Top left logo text must be less than 10000 characters.");
                }
            }
        }
        if($_POST['logotype'] === 'logotypeimg'){
            if($_FILES["logoimgfile"]["size"] != 0){
                $target_dir = getcwd() . DIRECTORY_SEPARATOR . "../../img" . DIRECTORY_SEPARATOR . "logo" . DIRECTORY_SEPARATOR;
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $possibleChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                $randomString = '';

                for($i = 0; $i < 5; $i++) {
                    $rand = rand(0, strlen($possibleChars) - 1);
                    $randomString .= substr($possibleChars, $rand, 1);
                }

                $target_file = $target_dir . basename($_FILES["logoimgfile"]["name"]);
                $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                $newImageName = $randomString . "." . $imageFileType;
                $target_file = $target_dir . $newImageName;
				if(function_exists("exif_imagetype")){
                	$imageType = exif_imagetype($_FILES["logoimgfile"]["tmp_name"]);
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
                    if(move_uploaded_file($_FILES["logoimgfile"]["tmp_name"], $target_file)) {
                        $uploadComplete = 1;
                        $filePathArray = array(':filepath' => 'img/logo/' . $newImageName);
                        $sql = $dbcon->prepare("UPDATE settings SET value=:filepath WHERE setting='logoimgcontent'");
                        $sql->execute($filePathArray);
                        $dbcon->query("UPDATE settings SET value='image' WHERE setting='logoimgtype'");
                    } else {
                        $uploadComplete = 0;
                        array_push($pageError, getLangString("image-upload-error"));
                    }
                }
            }
        }
    }

    if(isset($_POST['homepagetitle'])){
        if(strlen($_POST['homepagetitle']) < 10001){
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='hometitle'");
            $value = array(':value' => $_POST['homepagetitle']);
            $sql->execute($value);
        } else {
            array_push($pageError, getLangString("homepage-title-2long-error"));
        }
    }

    if(isset($_POST['homepagetext'])){
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='homeparagraph'");
        $value = array(':value' => $_POST['homepagetext']);
        $sql->execute($value);
    }

    if(isset($_POST['donatorstats'])){
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='showdonators'");
        $value = array(':value' => $_POST['donatorstats']);
        $sql->execute($value);
    }

    if(isset($_POST['donationstotal'])){
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='showtotaldonations'");
        $value = array(':value' => $_POST['donationstotal']);
        $sql->execute($value);
    }

    if(isset($_POST['defaultlanguage'])){
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='defaultlanguage'");
        $value = array(':value' => $_POST['defaultlanguage']);
        $sql->execute($value);
    }
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
