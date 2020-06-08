<?php

require_once(dirname(__FILE__) . "/../../config.php");
require('../../require/classes.php');
$user = new User();
$pageError = [];

if ($user->IsAdmin())
{
    if(isset($_POST['newnewstitle'])){
        $title = $_POST['newnewstitle'];
        $content = $_POST['newnewscontent'];
        $sql = $dbcon->prepare("INSERT INTO news(author, title, content) VALUES(:author, :title, :content)");
        $values = array(':author' => $_SESSION['username'], ':title' => $title, ':content' => $content);
        $sql->execute($values);
    }

    if(isset($_POST['editnews'])){
        $title = $_POST['newstitle'];
        $content = $_POST['newscontent'];
        $sql = $dbcon->prepare("UPDATE news SET title=:title, content=:content WHERE id=:id");
        $values = array(':title' => $title, ':content' => $content, ':id' => $_POST['editnews']);
        $sql->execute($values);
    }

    if(isset($_POST['removenews'])){
        $sql = $dbcon->prepare("DELETE FROM news WHERE id=:id");
        $values = array(':id' => $_POST['removenews']);
        $sql->execute($values);
    }
}

if(count($pageError) > 0){
	foreach ($pageError as $key => $value) {
		print('<span>' . $value . '</span><br>');
	}
}
