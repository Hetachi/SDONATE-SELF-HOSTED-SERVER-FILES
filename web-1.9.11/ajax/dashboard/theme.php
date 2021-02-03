<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{
    if(isset($_POST['theme'])){

        $sql = $dbcon->prepare("SELECT * FROM settings WHERE setting='maintheme'");
        $sql->execute();
        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
        $oldTheme = $results[0]['value'];

        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='maintheme'");
        $value = array(':value' => $_POST['maintheme']);
        $sql->execute($value);

        if(isset($_POST['themefont'])){
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='themefont'");
            $value = array(':value' => $_POST['themefont']);
            $sql->execute($value);
        }

        if(isset($_POST['thememaincolor'])){

            if($oldTheme !== $_POST['maintheme']){
                if($_POST['maintheme'] === "0"){
                    $value = "#0BB5FF";
                } elseif($_POST['maintheme'] === "1"){
                    $value = "#6CFF5E";
                }
            } else {
                $value = $_POST['thememaincolor'];
            }

            if(preg_match('/#([a-f0-9]{3}){1,2}\b/i', $value) === 1){
                $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='thememain'");
                $value = array(':value' => $value);
                $sql->execute($value);
            } else {
                array_push($pageError, getLangString("invalid-color-main-error"));
            }
        }

        if(isset($_POST['themesecondarycolor'])){

            if($oldTheme !== $_POST['maintheme']){
                if($_POST['maintheme'] === "0"){
                    $value = "#242424";
                } elseif($_POST['maintheme'] === "1"){
                    $value = "#333";
                }
            } else {
                $value = $_POST['themesecondarycolor'];
            }

            if(preg_match('/#([a-f0-9]{3}){1,2}\b/i', $value) === 1){
                $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='themesecondary'");
                $value = array(':value' => $value);
                $sql->execute($value);
            } else {
                array_push($pageError, getLangString("invalid-color-secondary-error"));
            }
        }

        if(isset($_POST['fontmaincolor'])){

            if($oldTheme !== $_POST['maintheme']){
                if($_POST['maintheme'] === "0"){
                    $value = "#242424";
                } elseif($_POST['maintheme'] === "1"){
                    $value = "#FFFFFF";
                }
            } else {
                $value = $_POST['fontmaincolor'];
            }

            if(preg_match('/#([a-f0-9]{3}){1,2}\b/i', $value) === 1){
                $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='mainfontcolor'");
                $value = array(':value' => $value);
                $sql->execute($value);
            } else {
                array_push($pageError, getLangString("invalid-color-fontmain-error"));
            }
        }

        if(isset($_POST['fontsecondarycolor'])){

            if($oldTheme !== $_POST['maintheme']){
                if($_POST['maintheme'] === "0"){
                    $value = "#FFFFFF";
                } elseif($_POST['maintheme'] === "1"){
                    $value = "#FFFFFF";
                }
            } else {
                $value = $_POST['fontsecondarycolor'];
            }

            if(preg_match('/#([a-f0-9]{3}){1,2}\b/i', $value) === 1){
                $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='secondaryfontcolor'");
                $value = array(':value' => $value);
                $sql->execute($value);
            } else {
                array_push($pageError, getLangString("invalid-color-fontsecondary-error"));
            }
        }

        if(isset($_POST['themespinning'])){
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='themespinning'");
            $value = array(':value' => 'true');
            $sql->execute($value);
        } else {
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='themespinning'");
            $value = array(':value' => 'false');
            $sql->execute($value);
        }

        if(isset($_POST['themecircle'])){
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='circleimages'");
            $value = array(':value' => '1');
            $sql->execute($value);
        } else {
            $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='circleimages'");
            $value = array(':value' => '0');
            $sql->execute($value);
        }

		if (isset($_POST['customcss'])){
			Settings::Set('customcss', $_POST['customcss']);
		}
    }
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
