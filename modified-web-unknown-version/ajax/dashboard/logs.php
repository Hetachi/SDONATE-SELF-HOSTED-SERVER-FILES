<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{

	if(isset($_POST['editcommand'])){
		$sql = $dbcon->prepare("UPDATE startupcommands SET server=:ip, port=:port, command=:command WHERE id=:id");
		$values = array(':ip' => $_POST['ip'], ':port' => $_POST['port'], ':command' => $_POST['command'], ':id' => $_POST['editcommand']);
		$sql->execute($values);
    }

    if(isset($_POST['deletecommand'])){
        $sql = $dbcon->prepare("DELETE FROM startupcommands WHERE id=:id");
        $value = array(':id' => $_POST['deletecommand']);
        $sql->execute($value);

    }


}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
