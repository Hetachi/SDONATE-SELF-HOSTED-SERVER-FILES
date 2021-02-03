<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{
    if(isset($_POST['deleteimage'])){

        $sql = $dbcon->prepare("SELECT gameimg, oldimg FROM games WHERE id=:id");
        $value = array(':id' => $_POST['id']);
        $sql->execute($value);
        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
        $newfilename = $results[0]['gameimg'];
        $oldfilename = $results[0]['oldimg'];

        $newdir = getcwd() . DIRECTORY_SEPARATOR . "../../img" . DIRECTORY_SEPARATOR . "games" . DIRECTORY_SEPARATOR . $_POST['id'] . DIRECTORY_SEPARATOR;
        $newfile = getcwd() . DIRECTORY_SEPARATOR . "../../img" . DIRECTORY_SEPARATOR . "games" . DIRECTORY_SEPARATOR . $_POST['id'] . DIRECTORY_SEPARATOR . $oldfilename;
        $oldfile = getcwd() . DIRECTORY_SEPARATOR . "../../img" . DIRECTORY_SEPARATOR . "games" . DIRECTORY_SEPARATOR . "old" . DIRECTORY_SEPARATOR . $oldfilename;

        array_map('unlink', glob($newdir . "*"));

        if(!copy($oldfile, $newfile)){
            array_push($pageError, "Error copying old image.");
        }

        $sql = $dbcon->prepare("UPDATE games SET gameimg=:img WHERE id=:id");
        $value = array(':img' => $oldfilename, ':id' => $_POST['id']);
        $sql->execute($value);

    }

    if(isset($_POST['editgame'])){
        $sql = $dbcon->prepare("UPDATE games SET enabled=:enabled WHERE id=:id");

        if(isset($_POST['gameenabled'])){
            $enabled = 1;
        } else {
            $enabled = 0;
        }

        $settings = array(':enabled' => $enabled, ':id' => $_POST['gameid']);
        $sql->execute($settings);

        if(isset($_FILES['gameimagefile'])){
            if($_FILES["gameimagefile"]["size"] != 0){

                $target_dir = getcwd() . DIRECTORY_SEPARATOR . "../../img" . DIRECTORY_SEPARATOR . "games" . DIRECTORY_SEPARATOR . $_POST['gameid'] . DIRECTORY_SEPARATOR;
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $possibleChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                $randomString = '';

                for($i = 0; $i < 5; $i++) {
                    $rand = rand(0, strlen($possibleChars) - 1);
                    $randomString .= substr($possibleChars, $rand, 1);
                }

                $target_file = $target_dir . basename($_FILES["gameimagefile"]["name"]);
                $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                $newImageName = $randomString . "." . $imageFileType;
                $target_file = $target_dir . $newImageName;
				if(function_exists("exif_imagetype")){
					$imageType = exif_imagetype($_FILES["gameimagefile"]["tmp_name"]);
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
                    if(move_uploaded_file($_FILES["gameimagefile"]["tmp_name"], $target_file)) {
                        $uploadComplete = 1;
                        $sql = $dbcon->prepare("UPDATE games SET gameimg=:filepath WHERE id=:id");
                        $settings = array(':filepath' => $newImageName, ':id' => $_POST['gameid']);
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

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
