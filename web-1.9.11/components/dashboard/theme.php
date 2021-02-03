<?php

require(dirname(__FILE__) . '/../../require/classes.php');
$user = new User();
if (!$user->IsAdmin())
{
	die("You must be an admin to see this page.");
}

$sql = $dbcon->prepare("SELECT * FROM settings");
$sql->execute();
$results = $sql->fetchAll(PDO::FETCH_ASSOC);

$spinningChecked = $results[6]['value'];
if($spinningChecked == 'true'){
	$spinning = 'checked';
} else {
	$spinning = '';
}

$circleChecked = $results[12]['value'];
if($circleChecked == '1'){
	$circleimages = 'checked';
} else {
	$circleimages = '';
}

$css = Settings::Get("customcss");

?>

<style>
	#css-editor * {
		font-family: Monaco, Menlo, 'Ubuntu Mono', Consolas, source-code-pro, monospace !important;
	}
</style>
<div id="dashboard-content-container">
	<p id="dashboard-page-title"><?= getLangString("theme-editor") ?></p>
	<form action="ajax/dashboard/theme.php" method="post">
		<input type="hidden" name="theme">
		<div class="row">
			<div class="col-md-12">
				<div class="settings-group">
					<p class="setting-title">Theme (Changing this will reset your colour settings)<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Select the font you want to use.">?</button></p>
					<select id="maintheme" name="maintheme" class="dropdown">
						<option value="0">Original</option>
						<option value="1">Grey-Neon</option>
					</select>
				</div>
				<div class="settings-group">
					<p class="setting-title">Font<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Select the font you want to use.">?</button></p>
					<select id="themefont" name="themefont" class="dropdown">
						<option value="Raleway">Raleway</option>
						<option value="Bebas Neue">Bebas Neue</option>
						<option value="Lobster">Lobster</option>
					</select>
				</div>
				<div class="settings-group">
					<p class="setting-title">Main Color<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The main colour of the website. Enter this as a hex color code starting with '#'">?</button></p>
					<input type="text" name="thememaincolor" class="settings-text-input" value="<?= $results[4]['value'] ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">Secondary Color<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The secondary colour of the website. Enter this as a hex color code starting with '#'">?</button></p>
					<input type="text" name="themesecondarycolor" class="settings-text-input" value="<?= $results[5]['value'] ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">Main Font Color<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The main colour of the text. Enter this as a hex color code starting with '#'">?</button></p>
					<input type="text" name="fontmaincolor" class="settings-text-input" value="<?= $results[14]['value'] ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">Secondary Font Color<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The secondary colour of the text. Enter this as a hex color code starting with '#'">?</button></p>
					<input type="text" name="fontsecondarycolor" class="settings-text-input" value="<?= $results[15]['value'] ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">Spinning Images<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Enable or disable spinning game/server pictures.">?</button></p>
					<input style="display: block; margin-bottom: 20px;" type="checkbox" name="themespinning"<?= $spinning ?>>
				</div>
				<div class="settings-group">
					<p class="setting-title">Circle Images<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Enable or disable circle game/server pictures.">?</button></p>
					<input style="display: block; margin-bottom: 20px;" type="checkbox" name="themecircle"<?= $circleimages ?>>
				</div>
				<div class="settings-group">
					<p class="setting-title">Custom CSS<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Enter your own custom CSS.">?</button></p>
					<div id="css-editor" style="height: 480px;"></div>
					<input type="hidden" id="customcss" name="customcss">
				</div>
			</div>
		</div>
		<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit">
	</form>
</div>
<script src="js/ace/ace.js" type="text/javascript" charset="utf-8"></script>
<script>

function submissionSuccess(){
	$('#errorbox-content').remove();
	$('#errorbox-bottom').append('Theme settings successfully changed.');
	if($('#table-container').css('display') == 'none'){
		showError();
	}
}

$("#themefont").val("<?= $results[16]['value'] ?>");
$("#maintheme").val("<?= $results[13]['value'] ?>");

var editor = ace.edit("css-editor");
editor.setTheme("ace/theme/monokai");
editor.getSession().setMode("ace/mode/css");
editor.setValue(<?= json_encode($css) ?>);

window.setInterval(function(){
	$("#customcss").val(editor.getValue());
}, 1000);

</script>
