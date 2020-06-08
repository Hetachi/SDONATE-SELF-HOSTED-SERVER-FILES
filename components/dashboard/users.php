<?php

require(dirname(__FILE__) . '/../../require/classes.php');
$user = new User();
if (!$user->IsAdmin())
{
	die("You must be an admin to see this page.");
}

$username = '';

if(!isset($_GET["search"])){
	$sql = $dbcon->prepare("SELECT * FROM users");
	$sql->execute();
	$users = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($users, "escapeHTML");
} else {
	$sql = $dbcon->prepare("SELECT * FROM users WHERE (INSTR(`username`, :search) OR INSTR(`steamid`, :search))");
	$values = array(':search' => $_GET['search']);
	$sql->execute($values);
	$users = $sql->fetchAll(PDO::FETCH_ASSOC);
	array_walk_recursive($users, "escapeHTML");
}

$rowCount = 0;
foreach ($users as $key => $value) {
	$rowCount = $rowCount + 1;
}
$sql = $dbcon->prepare("SELECT purchaser, value, status FROM transactions");
$sql->execute();
$transactions = $sql->fetchAll(PDO::FETCH_ASSOC);
array_walk_recursive($transactions, "escapeHTML");

foreach ($users as $key => $value) {
	$users[$key]['transactioncount'] = 0;
	$users[$key]['transactionvalue'] = 0;

	foreach ($transactions as $key1 => $value1) {
		if($transactions[$key1]['purchaser'] === $users[$key]['username'] AND ($transactions[$key1]['status'] === "complete" OR $transactions[$key1]['status'] === "revoked" OR $transactions[$key1]['status'] === "assigned")){
			$users[$key]['transactioncount']++;
			$users[$key]['transactionvalue'] = $users[$key]['transactionvalue'] + $transactions[$key1]['value'];
		}
	}
}

?>

<div id="dashboard-content-container">
	<p id="dashboard-page-title"><?= getLangString("users") ?></p>
	<div style="display: block;">
		<input type="text" id="user-search" style="display: inline-block; margin-bottom: 20px;" class="settings-text-input" placeholder="Search users by username or Steam ID"/>
		<button class="submit-button small-button" style="float: none; width: auto;" onclick="findUser();">Go</button>
	</div>

	<?php
		if(!isset($_GET['search'])){
			print('<p class="setting-title" style="display: inline-block">Registered Users: ' . $rowCount . '</p>');
		} else {
			print('<p class="setting-title" style="display: inline-block">Search results for: ' . htmlspecialchars($_GET["search"], ENT_QUOTES|ENT_SUBSTITUTE) . '</p>');
		}
	?>

	<div class="row">
		<div class="col-md-12">
			<div class="dashboard-stat-large">
				<div class="statistics-title"><?= getLangString("users") ?></div>
				<div class="statistics-content table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th>Username</th>
								<th>Total Purchases</th>
								<th>Total Donated (<?= $currencycode ?>)</th>
								<th><?= getLangString("usertype") ?></th>
								<th style="text-align: center;">View User</th>
							</tr>
						</thead>
						<tbody>

						<?php
							foreach ($users as $key => $value) {
								if($value["usertype"] == "admin"){
									$adminSelected = 'selected="selected"';
									$userSelected = '';
								} else {
									$adminSelected = '';
									$userSelected = 'selected="selected"';
								}
								print('
									<tr>
										<td>' . $users[$key]['username'] . '</td>
										<td>' . $users[$key]['transactioncount'] . '</td>
										<td>' . $currencysymbol . number_format((float)$users[$key]['transactionvalue'], 2, '.', '') . '</td>
										<td>
											<select class="dropdown usertype-dropdown" data-username="' . $users[$key]['username'] . '" style="min-width: 90px;">
												<option value="admin"' . $adminSelected . '>Admin</option>
												<option value="user"' . $userSelected . '>User</option>
											</select>
										</td>
										<td style="text-align: center;"><a href="dashboard.php?action=users&id=' . $users[$key]['id'] . '"><span class="glyphicon glyphicon-eye-open"></span></a></td>
									</tr>
								');
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

	var prev = "";
	var dropdownBox;
	$(".usertype-dropdown").on("focus", function(){
		dropdownBox = $(this);
		prev = dropdownBox.val();
		console.log(prev);
	}).change(function(){
		var username = dropdownBox.attr("data-username");
		var usertype = dropdownBox.val();
		var data1 = new FormData;
		data1.append("changeusertype", usertype);
		data1.append("username", username);
		$.ajax({
			type: "post",
			url: "ajax/dashboard/users.php",
			data: data1,
			processData: false,
			contentType: false,
			success: function (data) {
				if($.trim(data)){
					dropdownBox.val(prev);
					$("#errorbox-content").remove();
					$("#errorbox-bottom").append('<div id="errorbox-content">' + data + '</div>');
					if($("#table-container").css("display") == "none"){
						showError();
					}
				} else {
					showError("<?= getLangString("usertype-changed") ?>");
				}
			}
		});
	});

	function findUser(){
		window.location.href = "dashboard.php?action=users&search=" + encodeURIComponent($("#user-search").val());
	}

</script>
