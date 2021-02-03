<?php

require(dirname(__FILE__) . '/../../require/classes.php');
$user = new User();
if (!$user->IsAdmin())
{
	die("You must be an admin to see this page.");
}

$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='currentversion'");
$sql->execute();
$results = $sql->fetchAll(PDO::FETCH_ASSOC);
$currentVersion = $results[0]['value'];

$dirInfo = "You have set the directory your store is in to <a href='" . $dir . "'>" . $dir . "</a> If this is incorrect change \$dir in config.php.";

if($customDir === false){
	$dirInfo = "SDonate has automatically the directory your store is in as <a href='" . $dir . "'>" . $dir . "</a> If this is incorrect change \$dir in config.php.";
}

?>

<div id="dashboard-content-container">
	<p id="dashboard-page-title"><?= getLangString("info") ?></p>
	<div class="row">
		<div class="col-md-12">
			<div class="settings-group">
				<p class="setting-title"><?= $dirInfo ?></p>
			</div>
			<div class="settings-group">
				<p class="setting-title">Installed Version: <?= $currentVersion ?></p>
			</div>
		</div>
	</div>
</div>
