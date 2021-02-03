<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{
    if(isset($_POST['deleteimage'])){
        $sql = $dbcon->prepare("UPDATE packages SET img='' WHERE id=:id");
        $value = array(':id' => $_POST['id']);
        $sql->execute($value);
    }

    if(isset($_POST['editpackage'])){

        $titleValid = $descriptionValid = $priceValid = $maxpurchasesValid = $expiresValid = $sortValid = false;

        if(isset($_POST['packageenabled'])){
            $enabled = 1;
        } else {
            $enabled = 0;
        }

        if(isset($_POST['packagepaywhatyouwant'])){
            $pwyw = 1;
        } else {
            $pwyw = 0;
        }

        if(isset($_POST['packagegiftable'])){
            $giftable = 1;
        } else {
            $giftable = 0;
        }

        if(strlen($_POST['packagetitle']) > 200){
            array_push($pageError, "Package Title must be 200 characters or less.");
        } else {
            $titleValid = true;
        }

        if(is_numeric($_POST['packageprice'])){
            if($_POST['packageprice'] > 99999999999 || $_POST['packageprice'] < 0.00){
                array_push($pageError, "Price can't be more than " . $currencycode . "99999999999 or less than " . $currencycode . "0.00.");
            } else {
                $priceValid = true;
            }
        } else {
            array_push($pageError, "Price is not a valid number.");
        }


        if(ctype_digit($_POST['packagemaxpurchases'])){
            if($_POST['packagemaxpurchases'] > 99999999999 || $_POST['packagemaxpurchases'] < 0){
                array_push($pageError, "Max Purchases can't be more than 99999999999 or less than 0.");
            } else {
                $maxpurchasesValid = true;
            }
        } else {
            array_push($pageError, "Max purchases must be an integer.");
        }

        if(!is_numeric($_POST['packageexpires'])){
            $packageexpires = 0;
        } else {
            $packageexpires = $_POST['packageexpires'];
        }

        if(is_numeric($_POST['packagesortorder'])){
            if($_POST['packagesortorder'] > 99999999999 || $_POST['packagesortorder'] < 0.01){
                array_push($pageError, "Sort order can't be more than 99999999999 or less than 0.01.");
            } else {
                $sortValid = true;
            }
        } else {
            array_push($pageError, "Sort Order is not a valid number.");
        }

        $commands = json_decode($_POST['packagecommands']);
        $commandsValid = true;

        foreach ($commands as $key => $value) {

            $sql = $dbcon->prepare("SELECT * FROM actions WHERE (game=:game OR game='all') AND name=:action");
            $values = array(':game' => $value->game, ':action' => $value->name);
            $sql->execute($values);
            $count = $sql->rowCount();

            if($count < 1){
                $commandsValid = false;
                array_push($pageError, "\"" . $value->name . "\" is not a valid action.");
            } else {

                $results = $sql->fetchAll(PDO::FETCH_ASSOC);
                $action = $results[0];

                if($value->startcommand === $action['startcommand'] AND $value->endcommand === $action['endcommand']){
                    foreach ($value->paramtypes as $key1 => $value1) {
                        if($value1 === "numeric" || $value1 === "numericmulti"){
                            if(!ctype_digit($value->params[$key1]) && !(substr($value->params[$key1], 0, 1) == "-" && ctype_digit(substr($value->params[$key1], 1, strlen($value->params[$key1]) - 1))) && substr($value->params[$key1], 0, 14) !== "{{USERCHOICE}}"){
                                $commandsValid = false;
                                array_push($pageError, "\"" . $value->paramnames[$key1] . "\" must be a valid integer.");
                            } elseif (substr($value->params[$key1], 0, 14) === "{{USERCHOICE}}" && $value1 === "numericmulti") {
                                $choices = json_decode($value->params[$key1]);
                                foreach ($choices[1] as $key2 => $value2) {
                                    if(!ctype_digit($value2) && !empty($value2) && !(substr($value2, 0, 1) == "-" && ctype_digit(substr($value2, 1, strlen($value2) - 1)))){
                                        array_push($pageError, "\"" . $value->paramnames[$key1] . "\" must be a valid integer.");
                                        break;
                                    }
                                }
                            }
                        } elseif($value1 === "bool" || $value1 == "boolmulti"){
                            if($value->params[$key1] !== "0" && $value->params[$key1] !== "1"){
                                $commandsValid = false;
                                array_push($pageError, "\"" . $value->paramnames[$key1] . "\" must be 0 for false or 1 for true.");
                            }
                        }
                    }
                } else {
                    $commandsValid = false;
                    array_push($pageError, "Oops, something went wrong.");
                }

            }

        }

        if($titleValid == true AND $priceValid == true AND $maxpurchasesValid == true AND $commandsValid == true AND $sortValid){
            $sql = $dbcon->prepare("UPDATE packages SET game=:game, servers=:servers, title=:title, description=:description, paywhatyouwant=:paywhatyouwant, price=:price, maxpurchases=:maxpurchases, commands=:commands, expires=:expires, enabled=:enabled, sortorder=:sortorder, giftable=:giftable, hiddenservers=:hiddenservers WHERE id=:id");
            $settings = array(':id' => $_POST['packageid'], ':game' => $_POST['packagegame'], ':servers' => $_POST['packageservers'], ':title' => $_POST['packagetitle'], ':description' => $_POST['packagedescription'], ':paywhatyouwant' => $pwyw, ':price' => $_POST['packageprice'], ':maxpurchases' => $_POST['packagemaxpurchases'], ':commands' => $_POST['packagecommands'], ':expires' => $packageexpires, ':enabled' => $enabled, ':sortorder' => $_POST['packagesortorder'], ':giftable' => $giftable, ':hiddenservers' => $_POST['packagehiddenservers']);
            $sql->execute($settings);

            if(isset($_FILES['packageimagefile'])){
                if($_FILES["packageimagefile"]["size"] != 0){
                    $target_dir = getcwd() . DIRECTORY_SEPARATOR . "../../img" . DIRECTORY_SEPARATOR . "packages" . DIRECTORY_SEPARATOR . $_POST['packageid'] . DIRECTORY_SEPARATOR;
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $possibleChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                    $randomString = '';

                    for($i = 0; $i < 5; $i++) {
                        $rand = rand(0, strlen($possibleChars) - 1);
                        $randomString .= substr($possibleChars, $rand, 1);
                    }

                    $target_file = $target_dir . basename($_FILES["packageimagefile"]["name"]);
                    $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                    $newImageName = $randomString . "." . $imageFileType;
                    $target_file = $target_dir . $newImageName;
					if(function_exists("exif_imagetype")){
						$imageType = exif_imagetype($_FILES["packageimagefile"]["tmp_name"]);
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
                        if(move_uploaded_file($_FILES["packageimagefile"]["tmp_name"], $target_file)) {
                            $uploadComplete = 1;
                            $sql = $dbcon->prepare("UPDATE packages SET img=:filepath WHERE id=:id");
                            $settings = array(':filepath' => $newImageName, ':id' => $_POST['packageid']);
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

    if(isset($_POST['duplicatepackage'])){

        $sql = $dbcon->prepare("INSERT INTO packages (game, servers, title, description, img, paywhatyouwant, price, maxpurchases, commands, expires, enabled) SELECT game, servers, title, description, img, paywhatyouwant, price, maxpurchases, commands, expires, enabled FROM packages WHERE id=:id");
        $values = array(':id' => $_POST['duplicatepackage']);
        $sql->execute($values);

    }

    if(isset($_POST['addpackage'])){

        $titleValid = $descriptionValid = $priceValid = $maxpurchasesValid = $expiresValid = $sortValid = false;

        if(isset($_POST['packageenabled'])){
            $enabled = 1;
        } else {
            $enabled = 0;
        }

        if(isset($_POST['packagepaywhatyouwant'])){
            $pwyw = 1;
        } else {
            $pwyw = 0;
        }

        if(isset($_POST['packagegiftable'])){
            $giftable = 1;
        } else {
            $giftable = 0;
        }

        if(strlen($_POST['packagetitle']) > 200){
            array_push($pageError, "Package Title must be 200 characters or less.");
        } else {
            $titleValid = true;
        }

        if(is_numeric($_POST['packageprice'])){
            if($_POST['packageprice'] > 99999999999 || $_POST['packageprice'] < 0.00){
                array_push($pageError, "Price can't be more than " . $currencycode . "99999999999 or less than " . $currencycode . "0.00.");
            } else {
                $priceValid = true;
            }
        } else {
            array_push($pageError, "Price is not a valid number.");
        }

        if(ctype_digit($_POST['packagemaxpurchases'])){
            if($_POST['packagemaxpurchases'] > 99999999999 || $_POST['packagemaxpurchases'] < 0){
                array_push($pageError, "Max Purchases can't be more than 99999999999 or less than 0.");
            } else {
                $maxpurchasesValid = true;
            }
        } else {
            array_push($pageError, "Max purchases must be a valid integer.");
        }

        if(!is_numeric($_POST['packageexpires'])){
            $packageexpires = 0;
        } else {
            $packageexpires = $_POST['packageexpires'];
        }

        if(is_numeric($_POST['packagesortorder'])){
            if($_POST['packagesortorder'] > 99999999999 || $_POST['packagesortorder'] < 0.01){
                array_push($pageError, "Sort order can't be more than 99999999999 or less than 0.01.");
            } else {
                $sortValid = true;
            }
        } else {
            array_push($pageError, "Sort Order is not a valid number.");
        }

        $commands = json_decode($_POST['packagecommands']);
        $commandsValid = true;

        foreach ($commands as $key => $value) {

            $sql = $dbcon->prepare("SELECT * FROM actions WHERE (game=:game OR game='all') AND name=:action");
            $values = array(':game' => $value->game, ':action' => $value->name);
            $sql->execute($values);
            $count = $sql->rowCount();

            if($count < 1){
                $commandsValid = false;
                array_push($pageError, "\"" . $value->name . "\" is not a valid action.");
            } else {

                $results = $sql->fetchAll(PDO::FETCH_ASSOC);
                $action = $results[0];

                if($value->startcommand === $action['startcommand'] AND $value->endcommand === $action['endcommand']){
                    foreach ($value->paramtypes as $key1 => $value1) {
                        if($value1 === "numeric" || $value1 === "numericmulti"){
                            if(!ctype_digit($value->params[$key1]) && !(substr($value->params[$key1], 0, 1) == "-" && ctype_digit(substr($value->params[$key1], 1, strlen($value->params[$key1]) - 1)))){
                                $commandsValid = false;
                                array_push($pageError, "\"" . $value->paramnames[$key1] . "\" must be a valid integer.");
                            }
                        } elseif($value1 === "bool" || $value1 === "boolmulti"){
                            if($value->params[$key1] !== "0" && $value->params[$key1] !== "1"){
                                $commandsValid = false;
                                array_push($pageError, "\"" . $value->paramnames[$key1] . "\" must be 0 for false or 1 for true.");
                            }
                        }
                    }
                } else {
                    $commandsValid = false;
                    array_push($pageError, "Oops, something went wrong.");
                }

            }

        }

        if($titleValid == true AND $priceValid == true AND $maxpurchasesValid == true AND $commandsValid == true AND $sortValid){
            $sql = $dbcon->prepare("INSERT INTO packages(game, servers, title, description, paywhatyouwant, price, maxpurchases, commands, expires, enabled, sortorder, giftable, hiddenservers) VALUES(:game, :servers, :title, :description, :paywhatyouwant, :price, :maxpurchases, :commands, :expires, :enabled, :sortorder, :giftable, '[]')");
            $settings = array(':game' => $_POST['packagegame'], ':servers' => $_POST['packageservers'], ':title' => $_POST['packagetitle'], ':description' => $_POST['packagedescription'], ':paywhatyouwant' => $pwyw, ':price' => $_POST['packageprice'], ':maxpurchases' => $_POST['packagemaxpurchases'], ':commands' => $_POST['packagecommands'], ':expires' => $packageexpires, ':enabled' => $enabled, ':sortorder' => $_POST['packagesortorder'], ':giftable' => $giftable);
            $sql->execute($settings);
            $insertid = $dbcon->lastInsertId();

            if(isset($_FILES['packageimagefile'])){
                if($_FILES["packageimagefile"]["size"] != 0){
                    $target_dir = getcwd() . DIRECTORY_SEPARATOR . "../../img" . DIRECTORY_SEPARATOR . "packages" . DIRECTORY_SEPARATOR . $insertid . DIRECTORY_SEPARATOR;
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $possibleChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                    $randomString = '';

                    for($i = 0; $i < 5; $i++) {
                        $rand = rand(0, strlen($possibleChars) - 1);
                        $randomString .= substr($possibleChars, $rand, 1);
                    }

                    $target_file = $target_dir . basename($_FILES["packageimagefile"]["name"]);
                    $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                    $newImageName = $randomString . "." . $imageFileType;
                    $target_file = $target_dir . $newImageName;
					if(function_exists("exif_imagetype")){
						$imageType = exif_imagetype($_FILES["packageimagefile"]["tmp_name"]);
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
                        if(move_uploaded_file($_FILES["packageimagefile"]["tmp_name"], $target_file)) {
                            $uploadComplete = 1;
                            $sql = $dbcon->prepare("UPDATE packages SET img=:filepath WHERE id=:id");
                            $settings = array(':filepath' => $newImageName, ':id' => $insertid);
                            $sql->execute($settings);
                        } else {
                            $uploadComplete = 0;
                            array_push($pageError, getLangString("image-upload-error"));
                            $sql = $dbcon->prepare("DELETE FROM packages WHERE id=:id");
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

    if(isset($_POST['deletepackage'])){
        $deleteid = $_POST['deletepackage'];
        $sql = $dbcon->prepare("DELETE FROM packages WHERE id=:id");
        $value = array(':id' => $deleteid);
        $sql->execute($value);
    }
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
