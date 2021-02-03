<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{
    if(isset($_POST['tos'])){
        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='tos'");
        $value = array(':value' => $_POST['tos']);
        $sql->execute($value);
    }
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
