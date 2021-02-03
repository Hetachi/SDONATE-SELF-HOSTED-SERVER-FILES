<?php

require(dirname(__FILE__) . '/../../require/classes.php');
$user = new User();
if (!$user->IsAdmin())
{
	die("You must be an admin to see this page.");
}

$sql = $dbcon->prepare("SELECT * FROM logs ORDER BY time DESC");
$sql->execute();
$results = $sql->fetchAll(PDO::FETCH_ASSOC);
array_walk_recursive($results, "escapeHTML");

$sql = $dbcon->prepare("SELECT * FROM startupcommands");
$sql->execute();
$startupCommands = $sql->fetchAll(PDO::FETCH_ASSOC);
array_walk_recursive($results, "escapeHTML");
$startupCommandsJS = json_encode($startupCommands)

?>

<div id="dashboard-content-container">
	<p id="dashboard-page-title">Logs</p>
	<div class="row">
		<div class="col-md-12">
			<div class="dashboard-stat-large">
				<div class="statistics-title">&nbsp;</div>
				<div class="statistics-content table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th><?= getLangString("date") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The date and time at which this event took place.">?</button></th>
								<th>Error Type<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The type of error that occured.">?</button></th>
								<th>Error Code<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The asssociated error code (if applicable).">?</button></th>
								<th>Error Details<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The details of the error.">?</button></th>
							</tr>
						</thead>
						<tbody>

						<?php
							if(count($results) > 0){
								foreach($results as $key => $value){
									print('
										<tr>
											<td>' . $value['time'] . '</td>
											<td>' . $value['errortype'] . '</td>
											<td>' . $value['errorcode'] . '</td>
											<td>' . $value['error'] . '</td>
										</tr>
									');
								}
							} else {
								print('<tr><td>There are no errors to show right now.</td></tr>');
							}
						?>

						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<p id="dashboard-page-title">Startup Commands</p>
	<div class="row">
		<div class="col-md-12">
			<div class="dashboard-stat-large">
				<div class="statistics-title">&nbsp;</div>
				<div class="statistics-content table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th>Server IP<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The IP of the server this command runs on.">?</button></th>
								<th>Server Port<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The port of the server this command runs on.">?</button></th>
								<th>Command<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The command which runs at server startup.">?</button></th>
								<th style="text-align: center;">Edit</th>
							</tr>
						</thead>
						<tbody>

						<?php
							if(count($startupCommands) > 0){
								foreach($startupCommands as $key => $value){
									print('
										<tr>
											<td>' . $value['server'] . '</td>
											<td>' . $value['port'] . '</td>
											<td>' . $value['command'] . '</td>
											<td style="text-align: center;"><a href="#" onclick="editCommand(' . $key . ');"><span class="glyphicon glyphicon-pencil"></span></a></td>
										</tr>
									');
								}
							} else {
								print('<tr><td>There are no startup commands to show right now.</td></tr>');
							}
						?>

						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<script>

	var startupCommands = <?= $startupCommandsJS ?>;

	function editCommand(key){
		var html = '' +
			'<form action="ajax/dashboard/logs.php" method="post" enctype="multipart/form-data">\n' +
				'<input type="hidden" name="editcommand" value="' + startupCommands[key]["id"] + '">\n' +
				'<p id="errorbox-title">Edit Startup Command</p>\n' +
				'<p class="setting-title">Server IP<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The IP of the server this command runs on.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="ip" class="settings-text-input" value="' + startupCommands[key]["server"].replace('"', '\"') + '">\n' +
				'<p class="setting-title">Server Port<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The port of the server this command runs on.">?</button></p>\n' +
				'<input style="margin-bottom: 20px;" type="text" name="port" class="settings-text-input" value="' + startupCommands[key]["port"].replace('"', '\"') + '">\n' +
				'<p class="setting-title">Command<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The command which runs at server startup.">?</button></p>\n' +
				'<input id="command" style="margin-bottom: 20px;" type="text" name="command" class="settings-text-input">\n' +
				'<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit" style="display: inline-block; margin-left: 0px;">\n' +
				'<button type="button" class="submit-button" style="display: inline-block; margin-left: 0px; float: right;" onclick="deleteCommand(' + key + ');"><?= getLangString("delete") ?></button>\n' +
			'</form>';
		showError(html);
		enableToolTips();
		$("#command").val(startupCommands[key]["command"]);
		listenForSubmit();
	}

	function deleteCommand(key){
        var html = '' +
            '<form action="ajax/dashboard/logs.php" method="post">\n' +
                '<p style="text-align: center;">Do you really want to delete this startup command?</p>\n' +
                '<input type="hidden" value="' + startupCommands[key]["id"] + '" name="deletecommand">\n' +
                '<input class="submit-button" type="submit" value="<?= getLangString("delete") ?>" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;">\n' +
            '</form>';
        showError1(html);
        listenForSubmit();
    }

	function submissionSuccess(){
        location.reload(true);
    }

</script>
