<?php

require(dirname(__FILE__) . '/../../require/classes.php');
$user = new User();
if (!$user->IsAdmin())
{
	die("You must be an admin to see this page.");
}

?>

<div id="dashboard-content-container">
	<p id="dashboard-page-title">User Info</p>
	<div class="container-fluid">
		<div id="top-bar">
			<div id="left-buttons">
				<img id="steam-avatar" src="<?= $avatar ?>">
				<div id="steam-username"><?= $escapedUsername ?></div>
			</div>
			<div id="right-buttons">
				<button class="submit-button" type="button" style="display: inline-block; margin-bottom: 0; margin-top: 4px; float: right;" onclick="assignPackage('<?= htmlspecialchars(addslashes($username)) ?>');"><?= getLangString("assign-package") ?></button>
			</div>
		</div>
		<div class="row">
			<div id="account-info" class="col-md-12">
				<div class="statistics-box">
					<div class="statistics-title">Linked Accounts</div>
					<div class="statistics-content">
						Steam ID: <?= $linkedsteaminfo ?>
					</div>
				</div>
				<div id="credit-statistics" class="statistics-box">
					<div class="statistics-title">Credits</div>
					<div class="statistics-content">
						<?= getLangString("credit") . $results[0]['credit'] ?> <?= $currencycode ?><br>
						<button type="button" class="submit-button" onclick="editCredit();">Edit Credit</button>
					</div>
				</div>
				<div id="purchase-statistics" class="statistics-box">
					<div class="statistics-title"><?= getLangString("purchase-statistics") ?></div>
					<div class="statistics-content"><?=
						getLangString("purchases") ?>: <?= $transactionsCount ?> <br>
						Total Purchase Value:  <?= $currencysymbol . number_format((float)$transactionsValue, 2, '.', '') ?> <br>
					</div>
				</div>
				<div id="purchase-list" class="statistics-box">
					<div class="statistics-title"><?= getLangString("purchases") ?></div>
					<div class="statistics-content table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th><?= getLangString("date") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The date and time at which this transaction took place.">?</button></th>
									<th><?= getLangString("game") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The game this package applies to.">?</button></th>
									<th><?= getLangString("package") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The name of the package purchased.">?</button></th>
									<th><?= getLangString("value") ?> (<?= $currencycode ?>)<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The total value of this transaction.">?</button></th>
									<th><?= getLangString("status") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The status of this purchase. Note that 'Complete' does not mean that the commands have executed.">?</button></th>
									<th><?= getLangString("info") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="View more information about this transaction.">?</button></th>
								</tr>
							</thead>
							<tbody>

							<?php
								if($transactionsCount > 0){
									foreach($transactions as $key => $value){
										print(
											'<tr>' .
											'<td>' . $transactions[$key]['time'] . '</td>' .
											'<td>' . $transactions[$key]['game'] . '</td>' .
											'<td>' . $transactions[$key]['package'] . '</td>' .
											'<td>' . $currencysymbol . $transactions[$key]['value'] . '</td>' .
											'<td>' . ucfirst($transactions[$key]['status']) . '</td>' .
											'<td><a href="#" onclick="viewPackageInfo(' . $key . ');"><span class="glyphicon glyphicon-eye-open"></span></a></td>' .
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
			</div>
		</div>
	</div>
</div>
<script>

var results = <?= $transactionsJS ?>;
var packages = <?= $packagesJS ?>;

function viewPackageInfo(key){

	var expires = "";

	if(results[key]["expires"] === "0"){
		expires = '<p>Never</p>';
	} else {
		expires = '<p>' + results[key]["expiretime"] + '</p>';
	}

	var html = '' +
				'<p id="errorbox-title">Transaction Info</p>\n' +
				'<p class="setting-title">Date of Transaction<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Date and time when the transaction took place in the format YY-MM-DD hh:mm:ss.">?</button></p>\n' +
				'<p>' + results[key]["time"] + '</p>\n' +
				'<p class="setting-title"><?= getLangString("value") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Value of the transaction.">?</button></p>\n' +
				'<p>' + '<?= $currencysymbol ?>'  + results[key]["value"] + '</p>\n' +
				'<p class="setting-title">Payment Method<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The payment method used for this transaction.">?</button></p>\n' +
				'<p>' + results[key]["paymentmethod"] + '</p>\n' +
				'<p class="setting-title">Game<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The game this package applied to.">?</button></p>\n' +
				'<p>' + results[key]["game"] + '</p>\n' +
				'<p class="setting-title"><?= getLangString("package") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The name of the package purchased.">?</button></p>\n' +
				'<p>' + results[key]["package"] + '</p>\n' +
				'<p class="setting-title">' + results[key]["usernametype"] + '<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The ' + results[key]["usernametype"] + ' which the package was applied to.">?</button></p>\n' +
				'<p>' + results[key]["username"] + '</p>\n' +
				'<p class="setting-title">Expires<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Date and time this package expires on.">?</button></p>\n' +
				expires +
				'<button type="button" class="submit-button" onclick="revokePackage(' + results[key]["id"] + ', \'' + results[key]["package"] + '\');">Revoke Package</button>\n';

		showError(html);
		enableToolTips();
}

function editCredit(){
	var html = '' +
		'<form id="credit-form" action="ajax/dashboard/users.php" method="post" enctype="multipart/form-data">\n' +
			'<input id="editcredituser" name="editcredituser" type="hidden" value="<?= htmlspecialchars(addslashes($username)) ?>">\n' +
			'<input id="editcredit" name="editcredit" type="text" class="settings-text-input" style="margin-bottom: 10px;" value="<?= $results[0]['credit'] ?>">\n' +
			'<input class="submit-button" type="submit" value="Edit Credit" name="submit">\n' +
		'</form>';

	showError(html);

	$("#credit-form").on("submit", function (e) {
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
					$("#errorbox-bottom-1").append("Credit successfully changed.");
					if($("#table-container-1").css("display") == "none"){
						showError1();
					}
				}
			}
		});
	});
}

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

function assignPackage(user){

	var html = '' +
		'<p style="text-align: center;">Select a package to assign to <?= htmlspecialchars(addslashes($username)) ?>:</p>\n' +
		'<select class="dropdown" id="assignpackage" name="assignpackage" style="width: 100%; margin-bottom: 20px;">\n';

	$.each(packages, function(key, value){
		var option = '<option value="' + key + '">' + value.title + '</option>\n';
		html += option;
	});

	html += '</select>\n' +
		'<button class="submit-button" type="button" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" onclick="assignPackageVars();"><?= getLangString("assign-package") ?></button>\n';

	showError(html);

}

function assignPackageVars(){

	var package = packages[$("#assignpackage").val()];

	var packageCommands = JSON.parse(package.commands);

	var game = packageCommands[0].game

	var html = '<form id="package-params" action="ajax/dashboard/users.php" method="post" enctype="multipart/form-data">\n' +
		'<input type="hidden" name="assignpackageuser" value="<?= htmlspecialchars(addslashes($username)) ?>">\n' +
		'<input type="hidden" name="assignpackagesubmit" value="' + package.id + '">\n' +
		'<p id="errorbox-title">Give "' + package.title + '" to <?= htmlspecialchars(addslashes($username)) ?>?</p>\n';

	if(game === "Minecraft"){
		html += '<p class="setting-title">Minecraft Username</p>\n' +
			'<input class="settings-text-input" type="text" name="Minercraft Username">\n';
	}

	$.each(packageCommands, function(key1, value1){
		$.each(value1.params, function(key2, value2){
			if(value2.substring(0, 14) === "{{USERCHOICE}}"){
				var userchoices = JSON.parse(value2.substring(14));
				if(userchoices[0].length > 0){
					html += '<p class="setting-title">' + value1.paramnames[key2] + '</p>\n' +
						'<div class="checkbox-container" style="display: block;">\n';
					$.each(userchoices[0], function(key3, value3){

						html += '<label for="choice-checkbox-' + key1 + '-' + key2 + '-' + key3 + '" style="display: block; float: left; clear: both;">' + userchoices[0][key3] + labelPrice + '</label>\n' +
							'<input class="param-checkbox" id="choice-checkbox-' + key1 + '-' + key2 + '-' + key3 + '" name="choice-checkbox-' + key1 + '-' + key2 + '-' + key3 + '" style="display: block; float: right;" type="checkbox" data-price="' + dataPrice + '" onclick="updateTotalPrice(' + key + ');">\n';

					});

					html += '</div>\n';

				} else {
					if(value1.paramtypes[key2] === "bool"){
						html += '<p class="setting-title">' + value1.paramnames[key2] + '</p>\n' +
							'<label for="param-' + key1 + '-' + key2 + '" style="display: block; float: left; clear: both;">Yes</label>\n' +
							'<input type="checkbox" id="param-' + key1 + '-' + key2 + '" style="display: block; float: right;" type="checkbox" name="param-' + key1 + '-' + key2 + '">\n';
					} else {
						html += '<p class="setting-title">' + value1.paramnames[key2] + '</p>\n' +
							'<input class="settings-text-input" type="text" name="param-' + key1 + '-' + key2 + '">\n';
					}
				}
			}
		});
	});

	html += '<button class="submit-button" type="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px; margin-top: 20px;"><?= getLangString("assign-package") ?></button>\n' +
		'</form>\n';

	showError1(html);

	$("#package-params").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			type: "post",
			url: "ajax/dashboard/users.php",
			data: new FormData( this ),
			processData: false,
			contentType: false,
			success: function (data) {
				if($.trim(data)){
					$("#errorbox-content-2").remove();
					$("#errorbox-bottom-2").append("<div id=\"errorbox-content\">" + data + "</div>");
					if($("#table-container-2").css("display") == "none"){
						showError2();
					}
				} else {
					$("#errorbox-content-2").remove();
					$("#errorbox-bottom-2").append("Package successfully assigned.");
					if($("#table-container-2").css("display") == "none"){
						showError2();
					}
				}
			}
		});
	});

}

</script>
