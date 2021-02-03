<?php

require(dirname(__FILE__) . '/../../require/classes.php');
$user = new User();
if (!$user->IsAdmin())
{
	die("You must be an admin to see this page.");
}

$id = $_GET['id'];
$sql = $dbcon->prepare("SELECT * FROM users WHERE id=:id");
$settings = array(':id' => $id);
$sql->execute($settings);
$rowCount = $sql->rowCount();

if($rowCount < 1){
	print('
		<div id="dashboard-content-container">
			<p id="dashboard-page-title">' . getLangString("users") . '</p>
				<p class="setting-title" style="display: inline-block">The specified user could not be found.</p>
		</div>
		');
} else {

	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	$username = $results[0]['username'];
	array_walk_recursive($results, "escapeHTML");
	$escapedUsername = $results[0]['username'];

	$avatar = '';
	$steamid = FALSE;

	if(empty($results[0]['avatar'])){
		$avatar = 'img/defavatar.jpg';
	} else {
		$avatar = $results[0]['avatar'];
	}

	if(!empty($results[0]['steamid'])){
		$steamid = $results[0]['steamid'];
		$linkedsteaminfo = '<a class="underlined-link" target="_blank" href=http://steamcommunity.com/profiles/' . $steamid . '>' . $steamid . '</a>';
	} else {
		$linkedsteaminfo = 'There is no Steam account linked to this account.';
	}

	$transactionsQuery = $dbcon->prepare("SELECT * FROM transactions WHERE purchaser=:username ORDER BY time DESC");
	$settings = array(':username' => $username);
	$transactionsQuery->execute($settings);
	$transactions = $transactionsQuery->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($transactions, "escapeHTML");

	$transactionsJS = json_encode($transactions);

	$packagesQuery = $dbcon->prepare("SELECT * FROM packages");
	$packagesQuery->execute($settings);
	$packages = $packagesQuery->fetchAll(PDO::FETCH_ASSOC);

	$packagesJS = json_encode($packages);

	$transactionsValue = 0;
	$transactionsCount = 0;

	foreach ($transactions as $key => $value) {
		if($transactions[$key]['status'] == 'complete' || $transactions[$key]['status'] == 'revoked' || $transactions[$key]['status'] === "assigned"){
			$transactionsCount = $transactionsCount + 1;
			$transactionsValue = $transactionsValue + $transactions[$key]['value'];
		}
	}

	require(dirname(__FILE__) . "/usersusernamefound.php");

}

?>

<button class="submit-button" type="button" name="back" style=" margin-left: auto; margin-right: auto;" onclick="goBack();">Back</button>

<script>
function goBack(){
	location.href = "dashboard.php?action=users";
}
</script>
