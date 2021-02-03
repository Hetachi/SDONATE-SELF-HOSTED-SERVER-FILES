<?php

	require(dirname(__FILE__) . '/../../require/classes.php');
	$user = new User();
	if (!$user->IsAdmin())
	{
		die("You must be an admin to see this page.");
	}

	$sql = $dbcon->prepare("SELECT * FROM news");
	$sql->execute();
	$news = $sql->fetchAll(PDO::FETCH_ASSOC);
	$rowCount = $sql->rowCount();
	array_walk_recursive($news, "escapeHTML");
	$newsJS = json_encode($news);

?>

<div id="dashboard-content-container">
	<p id="dashboard-page-title"><?= getLangString("news") ?></p>
	<div class="row">
		<div class="col-md-12">
			<div class="dashboard-stat-large">
				<div class="statistics-title"><?= getLangString("news") ?></div>
				<div class="statistics-content table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th>Date</th>
								<th>Author</th>
								<th>Title</th>
								<th style="text-align: center;"><?= getLangString("edit") ?></th>
								<th style="text-align: center;"><?= getLangString("delete") ?></th>
							</tr>
						</thead>
						<tbody>

						<?php
							foreach ($news as $key => $value) {

								print('
															<tr>
																<td>' . $news[$key]['date'] . '</td>
																<td>' . $news[$key]['author'] . '</td>
																<td>' . $news[$key]['title'] . '</td>
																<td style="text-align: center;"><a href="#" onclick="editNews(' . $key . ')"><span class="glyphicon glyphicon-pencil"></span></a></td>
																<td style="text-align: center;"><a href="#" onclick="removeNews(' . $key . ')"><span class="glyphicon glyphicon-remove"></span></a></td>
															</tr>
								');
							}
						?>

						</tbody>
					</table>
				</div>
			</div>
		</div>
		<button type="button" class="submit-button" onclick="addNewsItem();" style="margin-left: auto; margin-right: auto;"><?= getLangString("create-new") ?></button>
	</div>
</div>
<script>
var news = <?= $newsJS ?>;

function addNewsItem(){
	var html = '' +
		'<p id="errorbox-title"><?= getLangString('create-new') ?></p>\n' +
		'<form id="new-form" action="ajax/dashboard/news.php" method="post" enctype="multipart/form-data">\n' +
		'<p class="setting-title">Title</p>\n' +
		'<input class="settings-text-input" type="text" name="newnewstitle">\n' +
		'<p class="setting-title">Content</p>\n' +
		'<textarea name="newnewscontent" style="width: 100%; min-height: 400px;"></textarea>\n' +
		'<button class="submit-button" type="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px; margin-top: 20px;"><?= getLangString("submit") ?></button>\n' +
		'</form>\n';

	showError(html);

	$("#new-form").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			type: "post",
			url: "ajax/dashboard/news.php",
			data: new FormData( this ),
			processData: false,
			contentType: false,
			success: function (data) {
				if($.trim(data)){
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("<div id=\"errorbox-content\">" + data + "</div>");
					if($("#table-container-1").css("display") == "none"){
						showError();
					}
				} else {
					location.reload();
				}
			}
		});
	});
}

function editNews(key){

	var title = news[key]["title"];
	var content = news[key]["content"];
	var id = news[key]["id"];

	var html = '' +
		'<p id="errorbox-title"><?= getLangString('create-new') ?></p>\n' +
		'<form id="new-form" action="ajax/dashboard/news.php" method="post" enctype="multipart/form-data">\n' +
		'<p class="setting-title">Title</p>\n' +
		'<input type="hidden" name="editnews" value="' + id + '">\n' +
		'<input class="settings-text-input" type="text" name="newstitle" value="' + title + '">\n' +
		'<p class="setting-title">Content</p>\n' +
		'<textarea id="newscontent" name="newscontent" style="width: 100%; min-height: 400px;">' + content + '</textarea>\n' +
		'<button class="submit-button" type="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px; margin-top: 20px;"><?= getLangString("submit") ?></button>\n' +
		'</form>\n';

	showError(html);

	$("#new-form").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			type: "post",
			url: "ajax/dashboard/news.php",
			data: new FormData( this ),
			processData: false,
			contentType: false,
			success: function (data) {
				if($.trim(data)){
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("<div id=\"errorbox-content\">" + data + "</div>");
					if($("#table-container-1").css("display") == "none"){
						showError();
					}
				} else {
					location.reload();
				}
			}
		});
	});
}

function removeNews(key){

	var id = news[key]["id"];

	var html = '' +
		'<p id="errorbox-title"><?= getLangString('remove-news') ?> "' + news[key]["title"] + '"?</p>\n' +
		'<form id="new-form" action="ajax/dashboard/news.php" method="post" enctype="multipart/form-data">\n' +
		'<input type="hidden" name="removenews" value="' + id + '">\n' +
		'<button class="submit-button" type="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px; margin-top: 20px;"><?= getLangString("confirm") ?></button>\n' +
		'</form>\n';

	showError(html);

	$("#new-form").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			type: "post",
			url: "ajax/dashboard/news.php",
			data: new FormData( this ),
			processData: false,
			contentType: false,
			success: function (data) {
				if($.trim(data)){
					$("#errorbox-content-1").remove();
					$("#errorbox-bottom-1").append("<div id=\"errorbox-content\">" + data + "</div>");
					if($("#table-container-1").css("display") == "none"){
						showError();
					}
				} else {
					location.reload();
				}
			}
		});
	});
}
</script>
