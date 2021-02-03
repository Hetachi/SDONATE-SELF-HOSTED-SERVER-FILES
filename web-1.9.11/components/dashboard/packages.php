<?php

	require(dirname(__FILE__) . '/../../require/classes.php');
	$user = new User();
	if (!$user->IsAdmin())
	{
		die("You must be an admin to see this page.");
	}

	$sql = $dbcon->prepare("SELECT id, gamename, usernametype, enabled FROM games ORDER BY gamename");
	$sql->execute();
	$gameCount = $sql->rowCount();
	$games = $sql->fetchAll(PDO::FETCH_ASSOC);

	$gamesJS = json_encode($games);

	$gamesEnabled = array();
	foreach($games as $key => $value){
		if($games[$key]['enabled'] == 0){
			$gamesEnabled[$games[$key]['id']] = "No";
		} else {
			$gamesEnabled[$games[$key]['id']] = "Yes";
		}
	}

	$gamesEnabledJS = json_encode($gamesEnabled);

	//Remember to fix c415e0378f1bec692075e216c7d125c95f535cb7ab447ce5678d077603ff40e3 error

	$sql = $dbcon->prepare("SELECT id, game, name, ip, port, rconpass, enabled FROM servers ORDER BY game, name");
	$sql->execute();
	$serverCount = $sql->rowCount();
	$servers = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($servers, "escapeHTML");

	$serversJS = json_encode($servers);

	$serversEnabled = array();
	foreach($servers as $key => $value){
		if($servers[$key]['enabled'] == 0){
			$serversEnabled[$key] = "No";
		} else {
			$serversEnabled[$key] = "Yes";
		}
	}

	$serversEnabledJS = json_encode($serversEnabled);

	$sql = $dbcon->prepare("SELECT * FROM packages ORDER BY game, title");
	$sql->execute();
	$packageCount = $sql->rowCount();
	$packages = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($packages, "escapeHTML");


	$packagesEnabled = "";
	$packagesPWYW = "";
	$packagesPrice = "";
	$packageStartCommands = "";
	$packageEndCommands = "";
	$packagesGifted = [];

	$packagesEnabled = array();
	foreach($packages as $key => $value){

		$packages[$key]['commands'] = htmlspecialchars_decode($packages[$key]['commands'], ENT_QUOTES);
		$packages[$key]['description'] = htmlspecialchars_decode($packages[$key]['description'], ENT_QUOTES);

		if($packages[$key]['enabled'] == 0){
			$packagesEnabled[$key] = "No";
		} else {
			$packagesEnabled[$key] = "Yes";
		}

		if($packages[$key]['paywhatyouwant'] == 0){
			$packagesPWYW[$key] = "No";
			$packagesPrice[$key] = $currencysymbol . $packages[$key]['price'];
		} else {
			$packagesPWYW[$key] = "Yes";
			$packagesPrice[$key] = "Pay What You Want (Min " . $currencysymbol . $packages[$key]['price'] . ")";
		}

		if($packages[$key]['giftable'] == 0){
			$packagesGifted[$key] = "No";
		} else {
			$packagesGifted[$key] = "Yes";
		}
	}

	$packagesJS = json_encode($packages);
	$packageEnabledJS = json_encode($packagesEnabled);
	$packagePWYWJS = json_encode($packagesPWYW);
	$packageGiftedJS = json_encode($packagesGifted);
	$packagePriceJS = json_encode($packagesPrice);
	$packageStartCommandsJS = json_encode($packageStartCommands);
	$packageEndCommandsJS = json_encode($packageEndCommands);

	$sql = $dbcon->prepare("SELECT * FROM actions WHERE type='premade' ORDER BY name");
	$sql->execute();
	$premadeactions = $sql->fetchAll(PDO::FETCH_ASSOC);

	$sql = $dbcon->prepare("SELECT * FROM actions WHERE type='special' ORDER BY name");
	$sql->execute();
	$customactions = $sql->fetchAll(PDO::FETCH_ASSOC);

	$actions = array_merge_recursive($premadeactions, $customactions);

?>

<div id="dashboard-content-container">
	<p id="dashboard-page-title"><?= getLangString("packages") ?></p>
	<div class="row">
		<div class="col-md-12">
			<div class="dashboard-stat-large">
				<div class="statistics-title">&nbsp;</div>
				<div class="statistics-content table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th><?= getLangString("game") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The game the package is for.">?</button></th>
								<th>Name<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The name of the package.">?</button></th>
								<th>Price<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The current price of the package.">?</button></th>
								<th><?= getLangString("enabled") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Whether this package is enabled for purchase or not.">?</button></th>
								<th style="text-align: center;"><?= getLangString("edit") ?></th>
							</tr>
						</thead>
						<tbody>

						<?php
							if($packageCount === 0){
								print('<tr><td>You don\'t have any packages added!</td></tr>');
							} else {
								foreach ($packages as $key => $value) {
									$gameid = $packages[$key]['game'];
									$sql = $dbcon->prepare("SELECT gamename FROM games WHERE id=" . $gameid);
									$sql->execute();
									$gameRow = $sql->fetchAll(PDO::FETCH_ASSOC);

									$game = $gameRow[0]['gamename'];

									print(
										'<tr>
											<td>' . $game . '</td>
											<td>' . $packages[$key]['title'] . '</td>
											<td>' . $packagesPrice[$key] . '</td>
											<td>' . $packagesEnabled[$key] . '</td>
											<td style="text-align: center;"><a href="#" onclick="editPackage(' . $key . ');"><span class="glyphicon glyphicon-pencil"></span></a></td>
										</tr>'
									);

								}
							}
						?>

						</tbody>
					</table>
				</div>
			</div>
			<button class="submit-button" onclick="addPackage();" style="display: inline-block; margin-left: 0px; margin-bottom: 60px;">Add Package</button>
			<button class="submit-button" onclick="duplicatePackage();" style="display: inline-block; margin-left: 0px; margin-bottom: 60px;">Duplicate Package</button>
		</div>
	</div>
</div>
<script>
	var unprocessedActions = <?= json_encode($actions) ?>;
	var actions = [];
	var hiddenServers = [];

	var servers = <?= $serversJS ?>;
	var serversEnabled = <?= $serversEnabledJS ?>;

	$.each(unprocessedActions, function(key, value){
		actions.push({
			game: value["game"],
			name: value["name"],
			execute: value["execute"],
			startcommand: value["startcommand"],
			endcommand: value["endcommand"],
			type: value["type"]
		});
	});

	var games = <?= $gamesJS ?>;
	var gamesEnabled = <?= $gamesEnabledJS ?>;

	function deleteImage(type, id){

		var formData = "deleteimage=" + "&type=" + type + "&id=" + id;

		$.ajax({
			url : "ajax/dashboard/packages.php",
			type: "POST",
			data : formData,
			success: function(data)
			{
				if($.trim(data)){
					$('#errorbox-content-1').remove();
					$('#errorbox-bottom-1').append('<div id="errorbox-content">' + data + '</div>');
					if($('#table-container-1').css('display') == 'none'){
						showError1();
					}
				} else {
					$('#errorbox-content-1').remove();
					$('#errorbox-bottom-1').append('<div>Successfully reset image.</div>');
					if($('#table-container-1').css('display') == 'none'){
						showError1();
					}
				}
			}
		});

	}

	var packages = <?= $packagesJS ?>;
	var packagesEnabled = <?= $packageEnabledJS ?>;
	var packagesPWYW = <?= $packagePWYWJS ?>;
	var packagesGifted = <?= $packageGiftedJS ?>;
	var packageStartCommands = <?= $packageStartCommandsJS ?>;
	var gameOptions = "";
	$.each(games, function(key, value){
		var html = "<option value=\"" + games[key]["id"] + "\">" + games[key]["gamename"] + "</option>";
		gameOptions += html;
	});

	function getServerOptions(game, commandservers){
		var gameServers = [];
		var gameServersNames = [];

		$.each(servers, function(key1, value1){
			var serverGame = servers[key1]["game"];
			var serverName = servers[key1]["name"];
			if(serverGame === game){
				gameServers.push(servers[key1]["id"]);
				gameServersNames.push(serverName);
			}
		});

		if(gameServers.length < 1){
			var serverOptions = '<p style="display: block; float: left; clear: both;">You don\'t have any servers added for this game!</p>';
		} else {
			var serverOptions = "";
		}

		if(commandservers !== -1){
			$.each(gameServers, function(key1, value1){
				if(commandservers.indexOf(gameServers[key1], 0) == -1){
					var html = '<label for="serverCheckbox' + gameServers[key1] + '" style="display: block; float: left; clear: both;">' + gameServersNames[key1] + '</label><input id="serverCheckbox' + gameServers[key1] + '" style="display: block; float: right;" type="checkbox" value="' + gameServers[key1] + '" class="serverCheckbox">\n';
				} else {
					var html = '<label for="serverCheckbox' + gameServers[key1] + '" style="display: block; float: left; clear: both;">' + gameServersNames[key1] + '</label><input id="serverCheckbox' + gameServers[key1] + '" style="display: block; float: right;" type="checkbox" value="' + gameServers[key1] + '" class="serverCheckbox" checked>\n';
				}
				serverOptions += html;
			});
		} else {
			$.each(gameServers, function(key1, value1){
				var html = '<label for="serverCheckbox' + gameServers[key1] + '" style="display: block; float: left; clear: both;">' + gameServersNames[key1] + '</label><input id="serverCheckbox' + gameServers[key1] + '" style="display: block; float: right;" type="checkbox" value="' + gameServers[key1] + '" class="serverCheckbox">\n';
				serverOptions += html;
			});
		}

		$("#serverOptionsContainer").html(serverOptions);
	}

	function editPackage(key){

		var packagesEnabledChecked = "";
		if(packagesEnabled[key] === "Yes"){
			packagesEnabledChecked = "checked";
		}

		var packagesPWYWChecked = "";
		if(packagesPWYW[key] === "Yes"){
			packagesPWYWChecked = "checked";
		}

		var packagesGiftedChecked = "";
		if(packagesGifted[key] === "Yes"){
			packagesGiftedChecked = "checked";
		}

		var html = '' +
			'<form action="ajax/dashboard/packages.php" method="post" enctype="multipart/form-data">\n' +
				'<input type="hidden" name="editpackage">\n' +
				'<input type="hidden" id="packageid" name="packageid" value="' + packages[key]["id"] + '">\n' +
				'<input type="hidden" id="packageservers" name="packageservers">\n' +
				'<p id="errorbox-title">Edit Package</p>\n' +
				'<p class="setting-title"><?= getLangString("game") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the game this server is for.">?</button></p>\n' +
				'<select class="dropdown" style="margin-bottom: 20px;" id="packagegame" name="packagegame">' + gameOptions + '</select>\n' +
				'<p class="setting-title">Title<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the title of the package.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packagetitle" id="packagetitle" class="settings-text-input" value="' + packages[key]["title"].replace('"',"\"") + '">\n' +
				'<p class="setting-title">Description<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter a short description of the package and what it does.">?</button></p>\n' +
				'<textarea id="quill-wrapper">' + packages[key]["description"] + '</textarea>\n' +
				'<input type="hidden" name="packagedescription" id="packagedescription">\n' +
				'<p class="setting-title">Image<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select an image to display for this package. Click Delete to use the default game image.">?</button></p>\n' +
				'<button type="button" class="submit-button" style="display: inline-block; margin-left: 0px; float: right;" onclick="deleteImage(\'package\', ' + packages[key]["id"] + ');"><?= getLangString("delete") ?></button>\n' +
				'<input style="margin-bottom: 20px;" type="file" name="packageimagefile" id="packageimagefile">\n' +
				'<p class="setting-title">Pay What You Want<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Tick this if the user should be able to pay what they want (above the minimum price) for this package.">?</button></p>\n' +
				'<input style="display: block; margin-bottom: 20px;" type="checkbox" name="packagepaywhatyouwant" id="packagepaywhatyouwant" ' + packagesPWYWChecked + '>\n' +
				'<p class="setting-title">Price<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the price of this package. If \'Pay What You Want\' is enabled this will be the minimum price.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packageprice" id="packageprice" class="settings-text-input" value="' + packages[key]["price"] + '">\n' +
				'<p class="setting-title">Max Purchases<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the maximum amount of times a user should be allowed to purchase this package. Set to 0 for unlimited.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packagemaxpurchases" id="packagemaxpurchases" class="settings-text-input" value="' + packages[key]["maxpurchases"] + '">\n' +
				'<p class="setting-title">Sort Order<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="This value is used to sort packages on the store page. Packages with a lower value will come first and higher values will be nearer the end.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packagesortorder" id="packagesortorder" class="settings-text-input" value="' + packages[key]["sortorder"] + '">\n' +
				'<p class="setting-title">Commands<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the commands to be executed when this package is purchased. See the help documents for more information.">?</button></p>\n' +
				'<div id="commandsContainer"></div>\n' +
				'<div style="display: block; margin-bottom: 5px;"><button type="button" style="float: left;" class="submit-button small-button" onclick="addCommand();"><span class="glyphicon glyphicon-plus small-button-glyphicon"></span></button></div>' +
				'<input type="hidden" id="packagecommands" name="packagecommands">\n' +
				'<p class="setting-title">Hidden Servers<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="If you have a server enabled in one of your commands but don\'t want to show this package on that server tick it here to hide it.">?</button></p>\n' +
				'<div id="hidden-servers-container"></div>\n' +
				'<input type="hidden" id="packagehiddenservers" name="packagehiddenservers">\n' +
				'<p class="setting-title">Duration<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the amount of time this package should last in days. Set to 0 for a permanent package.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packageexpires" id="packageexpires" class="settings-text-input" value="' + packages[key]["expires"] + '">\n' +
				'<p class="setting-title">Giftable<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Set whether this package can be gifted or not.">?</button></p>\n' +
				'<input style="display: block; margin-bottom: 20px;" type="checkbox" name="packagegiftable" id="packagegiftable" ' + packagesGiftedChecked + '>\n' +
				'<p class="setting-title"><?= getLangString("enabled") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable or disable the package in your store.">?</button></p>\n' +
				'<input style="display: block; margin-bottom: 20px;" type="checkbox" name="packageenabled" id="packageenabled" ' + packagesEnabledChecked + '>\n' +
				'<button class="submit-button" type="button" style="display: inline-block; margin-left: 0px;" onclick="processForm();"><?= getLangString("submit") ?></button>\n' +
				'<button type="button" class="submit-button" style="display: inline-block; margin-left: 0px; float: right;" onclick="deletePackage(' + key + ');"><?= getLangString("delete") ?></button>\n' +
			'</form>\n' +
			'<script>';

		showError(html);
		var packageGame = packages[key]["game"];
		enableToolTips();
		$("#errorbox-bottom select").val(packages[key]["game"]);
		gameDropdownChange(key);
		getCommands(key);
		listenForSubmit();

		var gameServers = [];
		var gameServersNames = [];
		var hiddenServerOptions = "";
		hiddenServers = [];

		$.each(servers, function(key1, value1){
			var serverGame = servers[key1]["game"];
			var serverName = servers[key1]["name"];
			if(serverGame === packages[key]["game"]){
				gameServers.push(servers[key1]["id"]);
				gameServersNames.push(serverName);
			}
		});

		$.each(gameServers, function(key1, value1){
			if(packages[key]["hiddenservers"].indexOf(gameServers[key1], 0) == -1){
				var html = '<label for="hiddenServerCheckbox' + gameServers[key1] + '" style="display: block; float: left; clear: both;">' + gameServersNames[key1] + '</label><input id="hiddenServerCheckbox' + gameServers[key1] + '" style="display: block; float: right;" type="checkbox" value="' + gameServers[key1] + '" class="hiddenServerCheckbox">\n';
			} else {
				var html = '<label for="hiddenServerCheckbox' + gameServers[key1] + '" style="display: block; float: left; clear: both;">' + gameServersNames[key1] + '</label><input id="hiddenServerCheckbox' + gameServers[key1] + '" style="display: block; float: right;" type="checkbox" value="' + gameServers[key1] + '" class="hiddenServerCheckbox" checked>\n';
			}
			hiddenServerOptions += html;
		});

		$("#hidden-servers-container").html(hiddenServerOptions);

		var configs = {
			theme: 'snow'
		};

		tinymce.init({
			selector: "#quill-wrapper",
			plugins: "code image hr textcolor table",
			setup: function(e){
				e.on("change", function(){
					$("#packagedescription").val(tinyMCE.activeEditor.getContent());
				});
				e.on("init", function(){
					$("#packagedescription").val(tinyMCE.activeEditor.getContent());
				});
			}
		});
	}

	function deletePackage(key){
		var html = '' +
			'<form action="ajax/dashboard/packages.php" method="post">\n' +
				'<p style="text-align: center;">Do you really want to delete ' + packages[key]["title"] + '? Transaction details related to this package will not be deleted.</p>\n' +
				'<input type="hidden" value="' + packages[key]["id"] + '" name="deletepackage">\n' +
				'<input class="submit-button" type="submit" value="<?= getLangString("delete") ?>" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;">\n' +
			'</form>';
		showError1(html);
		listenForSubmit();
	}

	function duplicatePackage(){

		var html = '' +
			'<form action="ajax/dashboard/packages.php" method="post" enctype="multipart/form-data">\n' +
			'<p style="text-align: center;">Select a package to duplicate:</p>\n' +
			'<select class="dropdown" id="duplicatepackage" name="duplicatepackage" style="width: 100%; margin-bottom: 20px;">\n';

		$.each(packages, function(key, value){
			var option = '<option value="' + value.id + '">' + value.title + '</option>\n';
			html += option;
		});

		html += '</select>\n' +
			'<button class="submit-button" type="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;"><?= getLangString("submit") ?></button></form>\n';

		showError(html);

		listenForSubmit();

	}

	function addPackage(){

		packagecommands = [];

		var html = '' +
			'<form action="ajax/dashboard/packages.php" method="post" enctype="multipart/form-data">\n' +
				'<input type="hidden" name="addpackage">\n' +
				'<input type="hidden" id="packageservers" name="packageservers">\n' +
				'<p id="errorbox-title">Add Package</p>\n' +
				'<p class="setting-title"><?= getLangString("game") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the game this server is for.">?</button></p>\n' +
				'<select class="dropdown" style="margin-bottom: 20px;" id="packagegame" name="packagegame">' + gameOptions + '</select>\n' +
				'<p class="setting-title">Title<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the title of the package.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packagetitle" id="packagetitle" class="settings-text-input">\n' +
				'<p class="setting-title">Description<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter a short description of the package and what it does.">?</button></p>\n' +
				'<textarea id="quill-wrapper"></textarea>\n' +
				'<input type="hidden" name="packagedescription" id="packagedescription">\n' +
				'<p class="setting-title">Image<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select an image to display for this package.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="file" name="packageimagefile" id="packageimagefile">\n' +
				'<p class="setting-title">Pay What You Want<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Tick this if the user should be able to pay what they want (above the minimum price) for this package.">?</button></p>\n' +
				'<input style="display: block; margin-bottom: 20px;" type="checkbox" name="packagepaywhatyouwant" id="packagepaywhatyouwant">\n' +
				'<p class="setting-title">Price<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the price of this package. If \'Pay What You Want\' is enabled this will be the minimum price.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packageprice" id="packageprice" class="settings-text-input">\n' +
				'<p class="setting-title">Max Purchases<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the maximum amount of times a user should be allowed to purchase this package. Set to 0 for unlimited.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packagemaxpurchases" id="packagemaxpurchases" class="settings-text-input" value="0">\n' +
				'<p class="setting-title">Sort Order<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="This value is used to sort packages on the store page. Packages with a lower value will come first and higher values will be nearer the end.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packagesortorder" id="packagesortorder" class="settings-text-input" value="1">\n' +
				'<p class="setting-title">Commands<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the commands to be executed when this package is purchased. See the help documents for more information.">?</button></p>\n' +
				'<div id="commandsContainer"></div>\n' +
				'<div style="display: block; margin-bottom: 5px;"><button type="button" style="float: left;" class="submit-button small-button" onclick="addCommand();"><span class="glyphicon glyphicon-plus small-button-glyphicon"></span></button></div>' +
				'<input type="hidden" id="packagecommands" name="packagecommands">\n' +
				'<p class="setting-title">Duration<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the amount of time this package should last in days. Set to 0 for a permanent package.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="packageexpires" id="packageexpires" class="settings-text-input" value="0">\n' +
				'<p class="setting-title">Giftable<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Set whether this package can be gifted or not.">?</button></p>\n' +
				'<input style="display: block; margin-bottom: 20px;" type="checkbox" name="packagegiftable" id="packagegiftable" checked>\n' +
				'<p class="setting-title"><?= getLangString("enabled") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable or disable the package in your store.">?</button></p>\n' +
				'<input style="display: block; margin-bottom: 20px;" type="checkbox" name="packageenabled" id="packageenabled">\n' +
				'<button class="submit-button" type="button" style="display: inline-block; margin-left: 0px;" onclick="processForm();"><?= getLangString("submit") ?></button>\n' +
			'</form>\n' +
			'<script>';

		showError(html);
		enableToolTips();
		gameDropdownChange(-1);
		listenForSubmit();

		$("#packagegame").nextAll("#packageexpires").val("Only permanent packages are allowed for this game.");
		$("#packagegame").nextAll("#packageexpires").attr("disabled","disabled");

		var configs = {
			theme: 'snow'
		};

		tinymce.init({
			selector: "#quill-wrapper",
			plugins: "code image hr textcolor table",
			setup: function(e){
				e.on("change", function(){
					$("#packagedescription").val(tinyMCE.activeEditor.getContent());
				});
				e.on("init", function(){
					$("#packagedescription").val(tinyMCE.activeEditor.getContent());
				});
			}
		});
	}

	function changeGame(newid){
		$(".small-button").each(function(){
			if($(this).attr("onclick").substring(0, 13) === "deleteCommand"){
				var endString = $(this).attr("onclick").indexOf(");");
				var keyToDelete = $(this).attr("onclick").substring(14, endString);
				submitDeleteCommand(parseInt(keyToDelete));
			}
		});
		$("#packagegame").val(newid);
		var gamename = $("#packagegame option:selected").text();
		if(gamename !== "Garry's Mod" && gamename !== "Minecraft" && gamename !== "Rust" && gamename !== "Counter-Strike: Global Offensive" && gamename !== "Team Fortress 2" && gamename !== "Left 4 Dead 2"){
			$("#packagegame").nextAll("#packageexpires").val("Only permanent packages are allowed for this game.");
			$("#packagegame").nextAll("#packageexpires").attr("disabled","disabled");
		} else {
			$("#packagegame").nextAll("#packageexpires").val("0.00");
			$("#packagegame").nextAll("#packageexpires").removeAttr("disabled");
		}
	}

	var previd;

	function gameDropdownChange(key){

		var gameid = $("#packagegame").val();
		var packagekey = key;

		$("#packagegame").on("focus", function(){

			previd = $("#packagegame").val();

		}).change(function(){

			var newid = $("#packagegame").val();
			var numCommands = $(".command").length;

			if(numCommands > 0){
				$("#packagegame").val(previd);
				var html = '' +
					'<p style="text-align: center;">Changing game will delete all actions you have set, are you sure you want to do this?</p>\n' +
					'<button class="submit-button" type="button" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" onclick="changeGame(' + newid + ')">Yes</button>';

				showError1(html);
			} else {
				changeGame(newid);
			}

		})
	}

	var packagecommands = [];

	function getCommands(key){
		var commandsHTML = "";
		var commandsJoined = packages[key]["commands"];
		packagecommands = $.parseJSON(commandsJoined);
		$.each(packagecommands, function(key, value){
			html = '<div class="command"><input style="display: inline-block; width: calc(100% - 80px);" type="text" class="package-commands settings-text-input" value="' + value.name.replace('"','\"') + '" readonly disabled><button type="button" class="submit-button small-button" onclick="deleteCommand(' + key + ');"><span class="glyphicon glyphicon-remove small-button-glyphicon"></span></button><button type="button" class="submit-button small-button" style="margin-right: 5px;" onclick="editCommand(' + key + ');"><span class="glyphicon glyphicon-pencil small-button-glyphicon"></span></button></div>';
			commandsHTML += html;
		});
		$("#commandsContainer").html(commandsHTML);
	}

	function changeChoices(){

		var choices = [];

		$(".userchoice").each(function(){

			if($(this).is(":checked")){

				var choiceNames = [];
				var choiceValues = [];
				var choicePrices = [];
				var allChoicesSerialized = false;
				var nextElement = $(this);

				while(allChoicesSerialized === false){
					var nextElement = nextElement.next();
					if(nextElement.hasClass("choice-title") === true){
						choiceNames.push(nextElement.val());
						choiceValues.push(nextElement.attr("data-value"));
						choicePrices.push(nextElement.attr("data-price"));
					} else if(nextElement.hasClass("add-choices-button") === true) {
						allChoicesSerialized = true;
						break;
					}
				}

				choices[0] = choiceNames;
				choices[1] = choiceValues;
				choices[2] = choicePrices;

				$(this).nextAll(".param-text-input").first().val("{{USERCHOICE}}" + JSON.stringify(choices));

			}

		});

	}

	function deleteChoice(key){
		$(".delete-choice-button").each(function(){
			if($(this).attr("onclick") === "deleteChoice(" + key + ");"){
				$(this).prevAll(".choice-title").first().remove();
				$(this).nextAll(".small-button").first().remove();
				$(this).remove();
				key1 = key1 - 1;
			}
		});

		changeChoices();

	}

	function submitEditChoice(key, inputType){

		var name = $("#choice-name").val();
		var choicevalue = $("#choice-value").val();
		var price = $("#choice-price").val();
		$(".edit-choice-button").each(function(){
			if($(this).attr("onclick") === "editChoice(" + key + ",'" + inputType + "');"){
				$(this).prevAll(".choice-title").first().val(name);
				$(this).prevAll(".choice-title").first().attr("data-value", choicevalue);
				$(this).prevAll(".choice-title").first().attr("data-price", price);
			}
		});

		closeErrorBox2();

		changeChoices();

	}

	function editChoice(key, inputType){

		var name;
		var choicevalue;
		var price;

		$(".edit-choice-button").each(function(){
			if($(this).attr("onclick") === "editChoice(" + key + ",'" + inputType + "');"){
				name = $(this).prevAll(".choice-title").first().val();
				choicevalue = $(this).prevAll(".choice-title").first().attr("data-value");
				price = $(this).prevAll(".choice-title").first().attr("data-price");
			}
		});

		var html = "";

		html += '<p id="errorbox-title">Edit Choice</p>\n' +
			'<p class="setting-title">Choice Name</p>\n' +
			'<input style="margin-bottom: 20px;" type="text" id="choice-name" class="settings-text-input" value="">\n' +
			'<p class="setting-title">Choice Value</p>\n' +
			'<input style="margin-bottom: 20px;" type="text" id="choice-value" class="settings-text-input" value="">\n' +
			'<p class="setting-title">Price</p>\n' +
			'<input style="margin-bottom: 20px;" type="text" id="choice-price" class="settings-text-input" value="">\n' +
			'<button class="submit-button" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" type="button" onclick="submitEditChoice(' + key + ',\'' + inputType + '\');">Edit Choice</button>';


		showError2(html);

		$("#choice-name").val(name);
		$("#choice-value").val(choicevalue);
		$("#choice-price").val(price);

	}

	function submitAddUserChoice(key, inputType){

		key1 = key1 + 1;
		var name = $("#choice-name").val();
		var choicevalue = $("#choice-value").val();
		var price = $("#choice-price").val();
		$(".add-choices-button").each(function(){
			if($(this).attr("onclick") === "addUserChoice(" + key + ",'" + inputType + "');"){
				$(this).before('<input style="display: inline-block; width: calc(100% - 80px);" class="settings-text-input choice-title" value="" data-value="" data-price="" readonly="" disabled="" type="text">' +
					'<button type="button" class="submit-button small-button delete-choice-button" onclick="deleteChoice(' + key1 + ');"><span class="glyphicon glyphicon-remove small-button-glyphicon"></span></button>' +
					'<button type="button" class="submit-button small-button edit-choice-button" style="margin-right: 5px;" onclick="editChoice(' + key1 + ',\'' + inputType + '\');"><span class="glyphicon glyphicon-pencil small-button-glyphicon"></span></button>');
				$(this).prevAll(".choice-title").first().val(name);
				$(this).prevAll(".choice-title").first().attr("data-value", choicevalue);
				$(this).prevAll(".choice-title").first().attr("data-price", price);
			}
		});

		closeErrorBox2();

		changeChoices();

	}

	function addUserChoice(key, inputType){

		var html = "";

		html += '<p id="errorbox-title">Add Choice</p>\n' +
			'<p class="setting-title">Choice Name</p>\n' +
			'<input style="margin-bottom: 20px;" type="text" id="choice-name" class="settings-text-input">\n' +
			'<p class="setting-title">Choice Value</p>\n' +
			'<input style="margin-bottom: 20px;" type="text" id="choice-value" class="settings-text-input">\n' +
			'<p class="setting-title">Price</p>\n' +
			'<input style="margin-bottom: 20px;" type="text" id="choice-price" class="settings-text-input">\n' +
			'<button class="submit-button" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" type="button" onclick="submitAddUserChoice(' + key + ',\'' + inputType + '\');">Add Choice</button>';


		showError2(html);

	}

	var command;
	var startCommand;
	var endCommand;
	var key1;

	function loadCommandParameters(){

		var params = [];
		var paramTypes = [];
		var html = "";
		$("#param-inputs").html(html);

		$.each(actions, function(key, value){
			if((actions[key].game === $("#packagegame").children(":selected").text() || actions[key].game === "all") && actions[key].name === $("#selectcommand").children(":selected").text()){
				command = actions[key];
				startCommand = actions[key].startcommand;
				endCommand = actions[key].endcommand;
				if(actions[key].type === "special"){
					$("#servers").hide();
				} else {
					$("#servers").show();
				}
				return false;
			}
		});

		var allStartParams = false;
		var searchStart = 0;

		while(allStartParams === false){

			var stringStart = startCommand.indexOf("{{INPUT=", searchStart);

			if(stringStart !== -1){
				var stringEnd = startCommand.indexOf("|TYPE=", stringStart);
				params.push(startCommand.substring((stringStart + 8), stringEnd));
				stringStart = stringEnd;
				stringEnd = startCommand.indexOf("}}", stringStart);
				paramTypes.push(startCommand.substring((stringStart + 6), stringEnd));
				searchStart = stringEnd;
			} else {
				allStartParams = true;
				break;
			}

		}

		var allEndParams = false;
		searchStart = 0;

		while(allEndParams === false){

			var stringStart = endCommand.indexOf("{{INPUT=", searchStart);

			if(stringStart !== -1){
				var stringEnd = endCommand.indexOf("|TYPE=", stringStart);

				var param = endCommand.substring((stringStart + 8), stringEnd);
				var paramAlreadyAdded = false;

				$.each(params, function(key, value){
					if(param === value){
						paramAlreadyAdded = true;
					}
				});

				stringStart = stringEnd;
				stringEnd = endCommand.indexOf("}}", stringStart);

				if(paramAlreadyAdded === false){
					params.push(param);
					paramTypes.push(endCommand.substring((stringStart + 6), stringEnd));
				}

				searchStart = stringEnd;
			} else {
				allEndParams = true;
				break;
			}

		}

		if(params.length > 0 || command.execute === "choice"){

			html = '<p id="errorbox-title" style="clear: both;">Action Parameters</p>\n';
			var paramsAdded = [];

			if(command.execute.substring(0, 6) === "choice"){
				html += '<p class="setting-title">Execution Time</p>\n' +
					'<select id="execution-time" name="execution-time" class="dropdown" style="margin-bottom: 20px;">\n' +
						'<option value="choicenow">On Purchase</option>\n' +
						'<option value="choiceonjoin">On Join</option>\n' +
					'</select>';
			} else {
				html += '<input type="hidden" id="execution-time" name="execution-time" value="' + command.execute + '">\n';
			}

			$.each(params, function(key, value){
				if(paramsAdded.indexOf(value) === -1){
					paramsAdded.push(value);
					html += '<p class="setting-title">' + value + '</p>\n' +
						'<input style="display: inline-block; margin-bottom: 20px;" type="checkbox" name="userchoice" class="userchoice"><p class="user-choice-text">User Chooses</p>\n' +
						'<button class="submit-button add-choices-button" name="submit" type="button" style="display: none;" onclick="addUserChoice(' + key + ',\'' + paramTypes[key] + '\');">Add Choice</button>\n' +
						'<input style="margin-bottom: 20px;" type="text" name="' + value.replace('"','\"') + '" class="settings-text-input param-text-input" data-inputtype="' + paramTypes[key] + '">\n';
				}
			});

		} else {
			html = "";
		}

		$("#param-inputs").html(html);

		$(".userchoice").change(function(){
			if($(this).is(":checked")){

				var firstCharPosition = $(this).nextAll(".add-choices-button").first().attr("onclick").indexOf("'");
				var secondCharPosition = $(this).nextAll(".add-choices-button").first().attr("onclick").indexOf("'", firstCharPosition + 1);
				var inputType = $(this).nextAll(".add-choices-button").first().attr("onclick").substring((firstCharPosition + 1), secondCharPosition);

				$(this).nextAll(".param-text-input").first().hide();
				if(inputType === "varcharmulti" || inputType === "numericmulti"){
					$(this).nextAll(".add-choices-button").first().show();
				}
				$(this).nextUntil(".add-choices-button", ".choice-title").show();
				$(this).nextUntil(".add-choices-button", ".edit-choice-button").show();
				$(this).nextUntil(".add-choices-button", ".delete-choice-button").show();
				$(this).nextAll(".param-text-input").first().val("{{USERCHOICE}}[[],[],[]]");
			} else {
				$(this).nextAll(".param-text-input").first().show();
				$(this).nextAll(".add-choices-button").first().hide();
				$(this).nextUntil(".add-choices-button", ".choice-title").hide();
				$(this).nextUntil(".add-choices-button", ".edit-choice-button").hide();
				$(this).nextUntil(".add-choices-button", ".delete-choice-button").hide();
				$(this).nextAll(".param-text-input").first().val("");
			}
		});

	}

	function submitCommand(){

		var paramNames = [];
		var paramTypes = [];
		var paramValues = [];
		var servers = [];
		var execute = "";

		execute = $("#execution-time").val();

		$(".serverCheckbox").each(function(key, value){
			if($(this).prop("checked")){
				servers.push($(this).val());
			}
		});

		$(".param-text-input").each(function(key, value){
			if(paramNames.indexOf($(this).attr("name")) === -1){
				paramNames.push($(this).attr("name"));
				paramTypes.push($(this).attr("data-inputtype"));
				paramValues.push($(this).val());
			}
		});

		var command = {
			game: $("#packagegame").children(":selected").text(),
			name: $("#selectcommand").children(":selected").text(),
			startcommand: startCommand,
			endcommand: endCommand,
			paramnames: paramNames,
			paramtypes: paramTypes,
			params: paramValues,
			execute: execute,
			servers: servers
		}

		packagecommands.push(command);

		$("#commandsContainer").append('<div class="command"><input style="display: inline-block; width: calc(100% - 80px);" type="text" class="package-commands settings-text-input" value="' + command.name.replace('"','\"') + '" readonly disabled><button type="button" class="submit-button small-button" onclick="deleteCommand(' + (packagecommands.length - 1) + ');"><span class="glyphicon glyphicon-remove small-button-glyphicon"></span></button><button type="button" class="submit-button small-button" style="margin-right: 5px;" onclick="editCommand(' + (packagecommands.length - 1) + ');"><span class="glyphicon glyphicon-pencil small-button-glyphicon"></span></button></div>');
		$("#packagecommands").val(JSON.stringify(packagecommands));

		closeErrorBox1();

	}

	function submitEditCommand(number){

		var paramNames = [];
		var paramTypes = [];
		var paramValues = [];
		var servers = [];
		var execute = "";

		execute = $("#execution-time").val();

		$(".serverCheckbox").each(function(key, value){
			if($(this).prop("checked")){
				servers.push($(this).val());
			}
		});

		$(".param-text-input").each(function(key, value){
			if(paramNames.indexOf($(this).attr("name")) === -1){
				paramNames.push($(this).attr("name"));
				paramTypes.push($(this).attr("data-inputtype"));
				paramValues.push($(this).val());
			}
		});

		var command = {
			game: $("#packagegame").children(":selected").text(),
			name: $("#selectcommand").children(":selected").text(),
			startcommand: startCommand,
			endcommand: endCommand,
			paramnames: paramNames,
			paramtypes: paramTypes,
			params: paramValues,
			execute: execute,
			servers: servers
		}

		$(".package-commands:eq(" + number + ")").val($("#selectcommand").children(":selected").text());

		packagecommands[number] = command;

		$("#packagecommands").val(JSON.stringify(packagecommands));

		closeErrorBox1();

	}

	function addCommand(){

		key1 = 0;

		var game = $("#packagegame").children(":selected").text();
		var html = '' +
			'<p id="errorbox-title">Add Action</p>\n' +
			'<p class="setting-title">Select an action</p>\n' +
			'<select class="dropdown" style="margin-bottom: 20px;" id="selectcommand" name="selectcommand">\n';

		$.each(actions, function(key, value){
			if(actions[key].game === game || actions[key].game === "all"){
				html = html + "<option value=\"" + game + "&action=" + actions[key].name + "\">" + actions[key].name + "</option>";
			}
		});

		html = html + '</select>\n' +
			'<div id="servers">\n' +
				'<p class="setting-title">Servers<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the servers this command will run on.">?</button></p>\n' +
				'<div id="serverOptionsContainer"></div>\n' +
			'</div>\n' +
			'<div id="param-inputs"></div>\n' +
			'<button class="submit-button" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" type="button" onclick="submitCommand();">Add Action</button>';

		showError1(html);
		loadCommandParameters();

		getServerOptions($("#packagegame").val(), -1);

		$("#selectcommand").change(function(){
			loadCommandParameters();
		});

	}

	function editCommand(key){

		key1 = 0;

		var command = packagecommands[key];
		var game = $("#packagegame").children(":selected").text();
		var html = '' +
			'<p id="errorbox-title">Edit Action</p>\n' +
			'<p class="setting-title">Select an action</p>\n' +
			'<select class="dropdown" style="margin-bottom: 20px;" id="selectcommand" name="selectcommand">\n';

		$.each(actions, function(key, value){
			if(actions[key].game === game || actions[key].game === "all"){
				html = html + "<option value=\"" + game + "&action=" + actions[key].name + "\">" + actions[key].name + "</option>";
			}
		});

		html = html + '</select>\n' +
			'<div id="servers">\n' +
				'<p class="setting-title">Servers<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the servers this command will run on.">?</button></p>\n' +
				'<div id="serverOptionsContainer"></div>\n' +
			'</div>\n' +
			'<div id="servers">\n' +
			'<div id="param-inputs"></div>\n' +
			'<button class="submit-button" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" type="button" onclick="submitEditCommand(' + key + ');">Edit Action</button>';

		showError1(html);

		getServerOptions($("#packagegame").val(), command.servers);

		$("#selectcommand").change(function(){
			loadCommandParameters();
		});

		$("#selectcommand").val(game + "&action=" + command.name);

		loadCommandParameters();

		if(command.execute.substring(0, 6) === "choice"){
			$("#execution-time").val(command.execute);
		}

		$(".param-text-input").each(function(key, value){
			var name = $(this).attr("name");
			var hasname = false;
			var paramvalue = "";
			$.each(command.paramnames, function(key1, value1){
				if(value1 === name){
					hasname = true;
					paramvalue = command.params[key1];
				}
			});
			if(hasname === true){
				$(this).val(paramvalue);
				if(paramvalue.substring(0, 14) === "{{USERCHOICE}}"){
					var choicesJson = paramvalue.substring(14);
					var choices = $.parseJSON(choicesJson);
					$(this).hide();
					$(this).prevAll(".userchoice").first().prop("checked", true);

					var button = $(this).prevAll(".add-choices-button").first();

					var firstCharPosition = button.attr("onclick").indexOf("'");
					var secondCharPosition = button.attr("onclick").indexOf("'", firstCharPosition + 1);
					var inputType = button.attr("onclick").substring((firstCharPosition + 1), secondCharPosition);

					$(this).prevAll(".param-text-input").first().hide();
					if(inputType === "varcharmulti" || inputType === "numericmulti"){
						button.show();
					}

					$.each(choices[0], function(key2, value2){
						key1 = key1 + 1;
						var name = choices[0][key2];
						var choicevalue = choices[1][key2];
						var price = choices[2][key2];
						var inputType = button.attr("onclick").substring((button.attr("onclick").indexOf("'") + 1), button.attr("onclick").indexOf("');"));

						button.before('<input style="display: inline-block; width: calc(100% - 80px);" class="settings-text-input choice-title" value="" data-value="" data-price="" readonly="" disabled="" type="text">' +
							'<button type="button" class="submit-button small-button delete-choice-button" onclick="deleteChoice(' + key1 + ');"><span class="glyphicon glyphicon-remove small-button-glyphicon"></span></button>' +
							'<button type="button" class="submit-button small-button edit-choice-button" style="margin-right: 5px;" onclick="editChoice(' + key1 + ',\'' + inputType + '\');"><span class="glyphicon glyphicon-pencil small-button-glyphicon"></span></button>');

						button.prevAll(".choice-title").first().val(name);
						button.prevAll(".choice-title").first().attr("data-value", choicevalue);
						button.prevAll(".choice-title").first().attr("data-price", price);

						changeChoices();
					});

				}
			}
		});

	}

	function submitDeleteCommand(key){
		packagecommands.splice(key, 1);

		$(".small-button").each(function(key1, value1){
			if($(this).attr("onclick") === "deleteCommand(" + key + ");"){
				$(this).parent().remove();
			}
		});

		var currentKey = 0;

		$(".small-button").each(function(key1, value1){
			if($(this).attr("onclick").indexOf("deleteCommand") !== -1){
				$(this).attr("onclick", "deleteCommand(" + currentKey + ");");
			} else if($(this).attr("onclick").indexOf("editCommand") !== -1){
				$(this).attr("onclick", "editCommand(" + currentKey + ");");
				currentKey = currentKey + 1;
			}
		});

		$("#packagecommands").val(JSON.stringify(packagecommands));
		closeErrorBox1();
	}

	function deleteCommand(key){
		var html = '' +
			'<p style="text-align: center;">Are you sure that you want to delete this command?</p>\n' +
			'<button class="submit-button" type="button" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;" onclick="submitDeleteCommand(' + key + ')"><?= getLangString("delete") ?></button>';
		showError1(html);
	}

	function processForm(){

		$(".hiddenServerCheckbox").each(function(key, value){
			if($(this).prop("checked")){
				hiddenServers.push($(this).val());
			}
		});

		$("#packagecommands").val(JSON.stringify(packagecommands));
		$("#packagehiddenservers").val(JSON.stringify(hiddenServers));

		var numServers = 0;
		var packageServers = [];

		$.each(packagecommands, function(key, value){
			$.each(value.servers, function(key1, value1){
				if(packageServers.indexOf(value1) === -1){
					packageServers.push(value1);
				}
			});
		});

		$("#packageservers").val(JSON.stringify(packageServers));

		$("form").submit();

	}


	function submissionSuccess(){
		location.reload(true);
	}
</script>
