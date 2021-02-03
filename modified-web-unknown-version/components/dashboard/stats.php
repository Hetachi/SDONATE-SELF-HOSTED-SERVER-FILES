<?php

require(dirname(__FILE__) . '/../../require/classes.php');
$user = new User();
if (!$user->IsAdmin())
{
	die("You must be an admin to see this page.");
}

$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='thememain'");
$sql->execute();
$results = $sql->fetchAll(PDO::FETCH_ASSOC);
$mainColour = $results[0]['value'];

$displayGraph = TRUE;

$monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

$sql = $dbcon->prepare("SELECT * FROM transactions ORDER BY time DESC");
$sql->execute();
$monthtoday = intval(date('n'));
$previousmonths = array();
$previousMonthsNames = array();
$previousmonths[0] = $monthtoday;
$previousMonthsNames[0] = $monthNames[$monthtoday - 1];
$previousMonthsPurchases = array_fill(0, 12, 0);
$previousMonthsValue = array_fill(0, 12, 0);

for($i = 1; $i <= 11; $i++)
{
	$nextMonth = $previousmonths[$i-1] + 1;
	if($nextMonth > 12){
		$nextMonth = $nextMonth - 12;
	}
	$previousmonths[$i] = $nextMonth;
	$previousMonthsNames[$i] = $monthNames[$nextMonth - 1];
}

$totalpurchases = 0;
$totalTransactions = 0;
$totalvalue = 0;
$monthpurchases = 0;
$monthvalue = 0;
$daypurchases = 0;
$dayvalue = 0;
$rowcount = $sql->rowCount();
$purchasesbyuser = [];
$valuebyuser = [];
$steamUsernameHTML = [];
if($rowcount > 0){

	$results = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($results, "escapeHTML");

	foreach($results as $key => $value){
		$totalTransactions++;
		if($results[$key]['usernametype'] === 'Steam ID'){

			$usernamecached = FALSE;

			$sql = $dbcon->prepare("SELECT * FROM steamids WHERE steamid=:steamid");
			$values = array(':steamid' => $results[$key]['username']);
			$sql->execute($values);
			$rowcount1 = $sql->rowCount();

			if($rowcount1 > 0){
				$results1 = $sql->fetchAll(PDO::FETCH_ASSOC);

				$steamusername = htmlspecialchars($results1[0]['username'], ENT_QUOTES, 'UTF-8');
				$usernamecached = TRUE;
			}

			if($usernamecached === FALSE){
				$url = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $steamapi . "&steamids=" . $results[$key]['username']);
				if($url !== false){
					$content = json_decode($url, true);
					if(isset($content['response']['players'][0]['personaname'])){
						$steamusername = htmlspecialchars($content['response']['players'][0]['personaname'], ENT_QUOTES, 'UTF-8');
					} else {
						$steamusername = "Steam API failed to retrieve username.";
					}
				} else {
					$steamusername = "Steam API failed to retrieve username.";
				}

				$sql = $dbcon->prepare("SELECT * FROM steamids WHERE steamid=:steamid");
				$values = array(':steamid' => $results[$key]['username']);
				$sql->execute($values);
				$rowcount2 = $sql->rowCount();

				if($rowcount2 <= 0){
					$sql = $dbcon->prepare("INSERT INTO steamids (steamid, username) VALUES(:steamid, :username)");
					$values = array(':steamid' => $results[$key]['username'], ':username' => $steamusername);
					$sql->execute($values);
				}
			}

			$steamUsernameHTML[$key] = '
				<p class="setting-title">Steam Username<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The purchaser\'s Steam Username which the package was applied to.">?</button></p>
				<p><a class="underlined-link" target="_blank" href="http://steamcommunity.com/profiles/' . $results[$key]['username'] . '/">' . $steamusername . '</a></p>';
		} else {
			$steamUsernameHTML[$key] = '';
		}

		if($results[$key]['status'] == 'complete' || $results[$key]['status'] == 'revoked'){
			$totalvalue = $totalvalue + $results[$key]['value'];
			$totalpurchases = $totalpurchases + 1;

			if(strtotime($results[$key]['time']) > (time() - 2678400)){
				$monthvalue = $monthvalue + $results[$key]['value'];
				$monthpurchases = $monthpurchases + 1;
			}

			if(strtotime($results[$key]['time']) > (time() - 86400)){
				$dayvalue = $dayvalue + $results[$key]['value'];
				$daypurchases = $daypurchases + 1;
			}

			foreach ($previousmonths as $key1 => $value1) {
				if(date('n', strtotime($results[$key]['time'])) == $value1 && strtotime($results[$key]['time']) > (time() - (31536000 - 2678400))){
					$previousMonthsPurchases[$key1]++;
					$previousMonthsValue[$key1] = $previousMonthsValue[$key1] + $results[$key]['value'];
				}
			}
		}
	}
}

$graphLabelsJS = array();
$graphPurchasesDataJS = array();
$graphValueDataJS = array();

for($i = 0; $i <= 2; $i++){
	$graphLabelsJS[$i] = "[";
	$graphPurchasesDataJS[$i] = "[";
	$graphValueDataJS[$i] = "[";
}

for ($i = 10; $i <= 12; $i++) {

	$i1 = $i;
	if($i1 > 11){
		$i1 = $i1 - 12;
	}

	$graphLabelsJS[0] = $graphLabelsJS[0] . '"' . $previousMonthsNames[$i1] . '", ';
	$graphPurchasesDataJS[0] = $graphPurchasesDataJS[0] . '"' . $previousMonthsPurchases[$i1] . '", ';
	$graphValueDataJS[0] = $graphValueDataJS[0] . '"' . $previousMonthsValue[$i1] . '", ';

}

for ($i = 6; $i <= 12; $i++) {

	$i1 = $i;
	if($i1 > 11){
		$i1 = $i1 - 12;
	}

	$graphLabelsJS[1] = $graphLabelsJS[1] . '"' . $previousMonthsNames[$i1] . '", ';
	$graphPurchasesDataJS[1] = $graphPurchasesDataJS[1] . '"' . $previousMonthsPurchases[$i1] . '", ';
	$graphValueDataJS[1] = $graphValueDataJS[1] . '"' . $previousMonthsValue[$i1] . '", ';
}

for ($i = 1; $i <= 12; $i++) {

	$i1 = $i;
	if($i1 > 11){
		$i1 = $i1 - 12;
	}

	$graphLabelsJS[2] = $graphLabelsJS[2] . '"' . $previousMonthsNames[$i1] . '", ';
	$graphPurchasesDataJS[2] = $graphPurchasesDataJS[2] . '"' . $previousMonthsPurchases[$i1] . '", ';
	$graphValueDataJS[2] = $graphValueDataJS[2] . '"' . $previousMonthsValue[$i1] . '", ';

}

for($key = 0; $key <= 2; $key++) {
	$graphLabelsJS[$key] = rtrim($graphLabelsJS[$key], ',') . ']';
	$graphPurchasesDataJS[$key] = rtrim($graphPurchasesDataJS[$key], ',') . ']';
	$graphValueDataJS[$key] = rtrim($graphValueDataJS[$key], ',') . ']';
}

$resultsJS = json_encode($results);

$steamUsernameHTMLJS = json_encode($steamUsernameHTML);

$sql = $dbcon->prepare("SELECT id, name FROM servers");
$sql->execute();
$servers = $sql->fetchAll(PDO::FETCH_ASSOC);

$sql = $dbcon->prepare("SELECT id, title, servers FROM packages");
$sql->execute();
$packages = $sql->fetchAll(PDO::FETCH_ASSOC);

$packagesJS = [];

foreach ($packages as $key => $value) {
	$packageServers = json_decode($value["servers"]);
	foreach ($packageServers as $key1 => $value1) {
		foreach ($servers as $key2 => $value2) {
			if ($value2["id"] == $value1)
			{
				$packageServers[$key1] = $value2["name"];
				break;
			}
		}
	}
	$packagesJS[$key]["id"] = $value["id"];
	$packagesJS[$key]["name"] = $value["title"];
	$packagesJS[$key]["servers"] = implode(", ", $packageServers);
}

?>

<div id="dashboard-content-container">
	<p id="dashboard-page-title"><?= getLangString("statistics") ?></p>
	<div class="row">
		<div class="col-md-4">
			<div class="dashboard-stat-small">
				<div class="statistics-title"><?= getLangString("total-donations") ?></div>
				<div class="statistics-content">
					<?= getLangString("purchases") ?>: <?=  $totalpurchases ?><br>
					<?= getLangString("value") ?>: <?= $currencysymbol . number_format((float)$totalvalue, 2, '.', '')?>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="dashboard-stat-small">
				<div class="statistics-title"><?= getLangString("month-donations") ?></div>
				<div class="statistics-content">
					<?= getLangString("purchases") ?>: <?= $monthpurchases ?><br>
					<?= getLangString("value") ?>: <?= $currencysymbol . number_format((float)$monthvalue, 2, '.', '')?>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="dashboard-stat-small">
				<div class="statistics-title"><?= getLangString("24h-donations") ?></div>
				<div class="statistics-content">
					<?= getLangString("purchases") ?>: <?= $daypurchases ?><br>
					<?= getLangString("value") ?>: <?= $currencysymbol . number_format((float)$dayvalue, 2, '.', '')?>
				</div>
			</div>
		</div>
		<div class="col-md-12">
			<div class="dashboard-stat-large">
				<div class="statistics-title"><?= getLangString("graph") ?></div>
				<div class="statistics-content">
					<select class="dropdown graph-dropdown" id="graph-type-select">
						<option value="value">Revenue</option>
						<option value="purchases">Amount of Purchases</option>
					</select>
					<select class="dropdown graph-dropdown" id="graph-time-select">
						<option value="3">Past 3 Months</option>
						<option value="6">Past 6 Months</option>
						<option value="12">Past 12 Months</option>
					</select>
					<canvas id="graph-canvas"></canvas>
				</div>
			</div>
		</div>
		<div class="col-md-12">
			<div class="dashboard-stat-large">
				<div class="statistics-title"><?= getLangString("purchases") ?></div>
				<div class="statistics-content table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th><?= getLangString("date") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The date and time at which this transaction took place.">?</button></th>
								<th>User<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The purchaser's username.">?</button></th>
								<th><?= getLangString("game") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The game this package applies to.">?</button></th>
								<th><?= getLangString("package") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The name of the package purchased.">?</button></th>
								<th><?= getLangString("value") ?> (<?= $currencycode ?>)<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The total value of this transaction.">?</button></th>
								<th><?= getLangString("transaction-id") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The transaction ID associated with this transaction (if PayPal was used).">?</button></th>
								<th><?= getLangString("status") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The status of this purchase. Note that 'Complete' does not mean that the commands have executed.">?</button></th>
								<th style="text-align: center;"><?= getLangString("info") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="View more information about this transaction.">?</button></th>
							</tr>
						</thead>
						<tbody>

						<?php

							if($totalTransactions > 0){
								foreach($results as $key => $value){
									$transactionID = '';
									if($results[$key]['transactionid'] !== "" AND strpos($results[$key]['transactionid'], "Credit") === false){
										$transactionID = '<a class="underlined-link" href="https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=' . $results[$key]['transactionid'] . '">' . $results[$key]['transactionid'] . '</a>';
									} else {
										$transactionID = $results[$key]['transactionid'];
									}
									print(
										'<tr>' .
										'<td>' . $results[$key]['time'] . '</td>' .
										'<td><a class="underlined-link" href="dashboard.php?action=users&username=' . urlencode($results[$key]['purchaser']) . '">' . $results[$key]['purchaser'] . '</a></td>' .
										'<td>' . $results[$key]['game'] . '</td>' .
										'<td>' . $results[$key]['package'] . '</td>' .
										'<td>' . $currencysymbol . $results[$key]['value'] . '</td>' .
										'<td>' . $transactionID . '</td>' .
										'<td>' . ucfirst($results[$key]['status']) . '</td>' .
										'<td style="text-align: center;"><a href="#" onclick="viewPackageInfo(' . $key . ');"><span class="glyphicon glyphicon-eye-open"></span></a></td>' .
										'</tr>'
										);
								}
							} else {
								print('<tr><td>There are no purchases to show!</td></tr>');
							}

						?>

						</tbody>
					</table>
				</div>
			</div>
			<button class="submit-button" onclick="deleteAllTransactions();" style="display: inline-block; margin-left: 0px; margin-bottom: 60px;">Delete All Transactions</button>
			<button class="submit-button" onclick="rerunTransactions();" style="display: inline-block; margin-left: 0px; margin-bottom: 60px;">Re-run Transaction Commands</button>
		</div>
	</div>
</div>
<script>

var results = <?= $resultsJS ?>;
var steamUsernameHTMLarray = <?= $steamUsernameHTMLJS ?>;

function viewPackageInfo(key){

	var steamUsernameHTML = steamUsernameHTMLarray[key];
	var expires = "";

	if(results[key]["expires"] === "0"){
		expires = '<p>Never</p>';
	} else {
		expires = '<p>' + results[key]["expiretime"] + '</p>';
	}

	var html = '' +
				'<p id="errorbox-title">Transaction Info</p>\n' +
				'<p class="setting-title">Purchaser<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The purchaser\'s username.">?</button></p>\n' +
				'<a class="underlined-link" target="_blank" href="dashboard.php?action=users&username=' + results[key]["purchaser"] + '"><p>' + results[key]["purchaser"] + '</p></a>\n' +
				'<p class="setting-title">Date of Transaction<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Date and time when the transaction took place in the format YY-MM-DD hh:mm:ss.">?</button></p>\n' +
				'<p>' + results[key]["time"] + '</p>\n' +
				'<p class="setting-title"><?= getLangString("value") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Value of the transaction.">?</button></p>\n' +
				'<p><?= $currencysymbol ?>' + results[key]["value"] + '</p>\n' +
				'<p class="setting-title">Payment Method<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The payment method used for this transaction.">?</button></p>\n' +
				'<p>' + results[key]["paymentmethod"] + '</p>\n' +
				'<p class="setting-title"><?= getLangString("game") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The game this package applied to.">?</button></p>\n' +
				'<p>' + results[key]["game"] + '</p>\n' +
				'<p class="setting-title"><?= getLangString("package") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The name of the package purchased.">?</button></p>\n' +
				'<p>' + results[key]["package"] + '</p>\n' +
				'<p class="setting-title">' + results[key]["usernametype"] + '<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The purchaser\'s ' + results[key]["usernametype"] + ' which the package was applied to.">?</button></p>\n' +
				'<p>' + results[key]["username"] + '</p>\n' +
				steamUsernameHTMLarray[key] +
				'<p class="setting-title">Expires<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Date and time this package expires on.">?</button></p>\n' +
				expires +
				'<p class="setting-title">Parameters<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The user\'s chosen parameters.">?</button></p>\n';

		$.each(JSON.parse(htmlspecialchars_decode(results[key]["params"], 3)), function(key, value){
			$.each(value, function(key1, value1){
				html += '<p>' + value1 + '</p>\n';
			});
		});

		html += '' +
				'<button type="button" class="submit-button" onclick="revokePackage(' + results[key]["id"] + ', \'' + results[key]["package"] + '\');" style="display: inline-block;">Revoke Package</button>\n' +
				'<button type="button" class="submit-button" onclick="deleteTransaction(' + results[key]["id"] + ');" style="display: inline-block; float: right;">Delete Transaction</button>\n';

		showError(html);
		enableToolTips();
}

var lineChart;

$("#graph-type-select").val("value");
$("#graph-time-select").val("6");

function changeGraph(){

	var type = $("#graph-type-select").val();
	var time = $("#graph-time-select").val();

	if(type === "value"){
		switch(time){
			case "3":

				var data = {
					labels: <?= $graphLabelsJS[0] ?>,
					datasets: [
						{
							label: "First dataset",
							fillColor: "rgba(220,220,220,0.2)",
							strokeColor: "<?= $mainColour ?>",
							pointColor: "rgba(220,220,220,1)",
							pointStrokeColor: "#fff",
							pointHighlightFill: "#fff",
							pointHighlightStroke: "rgba(220,220,220,1)",
							data: <?= $graphValueDataJS[0] ?>
						}
					]
				};

			break;
			case "6":

				var data = {
					labels:  <?= $graphLabelsJS[1] ?>,
					datasets: [
						{
							label: "First dataset",
							fillColor: "rgba(220,220,220,0.2)",
							strokeColor: "<?= $mainColour ?>",
							pointColor: "rgba(220,220,220,1)",
							pointStrokeColor: "#fff",
							pointHighlightFill: "#fff",
							pointHighlightStroke: "rgba(220,220,220,1)",
							data: <?= $graphValueDataJS[1] ?>
						}
					]
				};

			break;
			case "12":

				var data = {
					labels:  <?= $graphLabelsJS[2] ?>,
					datasets: [
						{
							label: "First dataset",
							fillColor: "rgba(220,220,220,0.2)",
							strokeColor: "<?= $mainColour ?>",
							pointColor: "rgba(220,220,220,1)",
							pointStrokeColor: "#fff",
							pointHighlightFill: "#fff",
							pointHighlightStroke: "rgba(220,220,220,1)",
							data: <?= $graphValueDataJS[2] ?>
						}
					]
				};

			break;
		}
	} else {
		switch(time){
			case "3":

				var data = {
					labels: <?= $graphLabelsJS[0] ?>,
					datasets: [
						{
							label: "First dataset",
							fillColor: "rgba(220,220,220,0.2)",
							strokeColor: "<?= $mainColour ?>",
							pointColor: "rgba(220,220,220,1)",
							pointStrokeColor: "#fff",
							pointHighlightFill: "#fff",
							pointHighlightStroke: "rgba(220,220,220,1)",
							data: <?= $graphPurchasesDataJS[0] ?>
						}
					]
				};

			break;
			case "6":

				var data = {
					labels: <?= $graphLabelsJS[1] ?>,
					datasets: [
						{
							label: "First dataset",
							fillColor: "rgba(220,220,220,0.2)",
							strokeColor: "<?= $mainColour ?>",
							pointColor: "rgba(220,220,220,1)",
							pointStrokeColor: "#fff",
							pointHighlightFill: "#fff",
							pointHighlightStroke: "rgba(220,220,220,1)",
							data: <?= $graphPurchasesDataJS[1] ?>
						}
					]
				};

			break;
			case "12":

				var data = {
					labels: <?= $graphLabelsJS[2] ?>,
					datasets: [
						{
							label: "First dataset",
							fillColor: "rgba(220,220,220,0.2)",
							strokeColor: "<?= $mainColour ?>",
							pointColor: "rgba(220,220,220,1)",
							pointStrokeColor: "#fff",
							pointHighlightFill: "#fff",
							pointHighlightStroke: "rgba(220,220,220,1)",
							data: <?= $graphPurchasesDataJS[2] ?>
						}
					]
				};

			break;
		}
	}

	if(typeof lineChart !== "undefined")
	{
		lineChart.destroy();
	}

	var ctx = document.getElementById("graph-canvas").getContext("2d");
	lineChart = new Chart(ctx).Line(data);

}

$("#graph-type-select").change(function(){
	changeGraph();
});

$("#graph-time-select").change(function(){
	changeGraph();
});

changeGraph();

window.addEventListener("resize", function(){
	changeGraph();
});

function revokePackage(id, name){
	var html = '' +
		'<form id="revoke-form" action="ajax/dashboard/users.php" method="post" enctype="multipart/form-data">\n' +
			'<p style="text-align: center;">Are you sure you want to revoke the package "' + name + '"?</p>\n' +
			'<input id="revokepackage" name="revokepackage" type="hidden" value="' + id + '">\n' +
			'<input class="submit-button" type="submit" value="Revoke Package" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" type="submit">\n' +
		'</form>';

	showError(html);

	$("#revoke-form").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			type: "post",
			url: "ajax/dashboard/users.php",
			data: new FormData( this ),
			processData: false,
			contentType: false,
			success: function (data) {
				if($.trim(data)){
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("<div id=\"errorbox-content\">" + data + "</div>");
					if($("#table-container-1").css("display") == "none"){
						showError1();
					}
				} else {
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("Package successfully revoked.");
					if($("#table-container-1").css("display") == "none"){
						showError1();
					}
				}
			}
		});
	});
}

function deleteTransaction(id){
	var html = '' +
		'<form id="revoke-form" action="ajax/dashboard/users.php" method="post" enctype="multipart/form-data">\n' +
			'<p style="text-align: center;">Are you sure you want to delete this transaction? If you want to revoke the package you should do that first as you can\'t do it later. This action is irreversible and will permanently delete everything related to this transaction.</p>\n' +
			'<input id="deletetransaction" name="deletetransaction" type="hidden" value="' + id + '">\n' +
			'<input class="submit-button" type="submit" value="<?= getLangString("delete") ?>" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" type="submit">\n' +
		'</form>';

	showError(html);

	$("#revoke-form").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			type: "post",
			url: "ajax/dashboard/users.php",
			data: new FormData( this ),
			processData: false,
			contentType: false,
			success: function (data) {
				if($.trim(data)){
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("<div id=\"errorbox-content\">" + data + "</div>");
					if($("#table-container-1").css("display") == "none"){
						showError1();
					}
				} else {
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("Transaction deleted.");
					if($("#table-container-1").css("display") == "none"){
						showError1();
					}
				}
			}
		});
	});
}

function deleteAllTransactions(){
	var html = '' +
		'<form id="revoke-form" action="ajax/dashboard/users.php" method="post" enctype="multipart/form-data">\n' +
			'<p style="text-align: center;">Are you sure you want to delete every transaction? This will NOT revoke the packages and this action can NOT be undone, only proceed if you are absolutely sure you want to destroy EVERY transaction.</p>\n' +
			'<input id="deletealltransactions" name="deletealltransactions" type="hidden" value="1">\n' +
			'<input class="submit-button" type="submit" value="<?= getLangString("delete") ?>" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" type="submit">\n' +
		'</form>';

	showError(html);

	$("#revoke-form").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			type: "post",
			url: "ajax/dashboard/users.php",
			data: new FormData( this ),
			processData: false,
			contentType: false,
			success: function (data) {
				if($.trim(data)){
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("<div id=\"errorbox-content\">" + data + "</div>");
					if($("#table-container-1").css("display") == "none"){
						showError1();
					}
				} else {
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("Deleted all transactions.");
					if($("#table-container-1").css("display") == "none"){
						showError1();
					}
				}
			}
		});
	});
}

packages = <?=json_encode($packagesJS);?>;

function rerunTransactions()
{
	var html = '' +
		'<form id="rerun-form" action="ajax/dashboard/users.php" method="post" enctype="multipart/form-data">\n' +
			'<p class="setting-title">Exclude Expired Packages</p>\n' +
			'<input name="excludexpired" type="checkbox">\n' +
			'<p class="setting-title">Re-Run End Commands</p>\n' +
			'<input name="rerunendcommands" type="checkbox">\n' +
			'<p class="setting-title">Select packages to re-run:</p>\n';

	$.each(packages, function(key, value){
		html = html + '<p>' + value["name"] + ' - ' + value["servers"] + '</p>\n' +
			'<input name="rerun-package-' + value["id"] + '" type="checkbox">\n';
	});

	html = html + '<input id="reruntransactions" name="reruntransactions" type="hidden" value="1">\n' +
			'<input class="submit-button" type="submit" value="Go" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" type="submit">\n' +
		'</form>';

	showError(html);

	$("#rerun-form").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			type: "post",
			url: "ajax/dashboard/users.php",
			data: new FormData( this ),
			processData: false,
			contentType: false,
			success: function (data) {
				if($.trim(data)){
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("<div id=\"errorbox-content\">" + data + "</div>");
					if($("#table-container-1").css("display") == "none"){
						showError1();
					}
				} else {
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("Re-ran transaction commands.");
					if($("#table-container-1").css("display") == "none"){
						showError1();
					}
				}
			}
		});
	});
}

</script>
