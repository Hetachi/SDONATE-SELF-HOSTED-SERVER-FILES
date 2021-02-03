<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{
    if(isset($_POST['email'])){

        $SMTPServer = $_POST['smtpserver'];
        $SMTPPort = $_POST['smtpport'];
        $security = $_POST['security'];
        $senderEmail = $_POST['sender'];
        $senderPassword = $_POST['senderpassword'];

        if(isset($_POST['emailenabled'])){
            $sql = $dbcon->prepare("UPDATE settings SET value=? WHERE setting='emailenabled'");
            $sql->execute([$_POST['emailenabled']]);
        }
        $sql->execute();

        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='smtpserver'");
        $value = array(':value' => $SMTPServer);
        $sql->execute($value);

        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='smtpport'");
        $value = array(':value' => $SMTPPort);
        $sql->execute($value);

        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='smtpsecurity'");
        $value = array(':value' => $security);
        $sql->execute($value);

        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='senderemailaddress'");
        $value = array(':value' => $senderEmail);
        $sql->execute($value);

        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='senderemailpassword'");
        $value = array(':value' => $senderPassword);
        $sql->execute($value);

        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='emailcolor'");
        $value = array(':value' => $_POST['emailcolor']);
        $sql->execute($value);

        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='purchasesubject'");
        $value = array(':value' => $_POST['purchasesubject']);
        $sql->execute($value);

        $sql = $dbcon->prepare("UPDATE settings SET value=:value WHERE setting='purchasemessage'");
        $value = array(':value' => $_POST['purchasemessage']);
        $sql->execute($value);

    }
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
