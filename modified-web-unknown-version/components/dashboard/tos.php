<?php

require(dirname(__FILE__) . '/../../require/classes.php');
$user = new User();
if (!$user->IsAdmin())
{
	die("You must be an admin to see this page.");
}

$sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='tos'");
$sql->execute();
$tosArray = $sql->fetchAll(PDO::FETCH_ASSOC);
$tos = $tosArray[0]['value'];

?>

<div id="dashboard-content-container">
	<p id="dashboard-page-title"><?= getLangString("tos") ?></p>
	<form action="ajax/dashboard/tos.php" method="post">
		<div class="row">
			<div class="col-md-12">
				<div class="settings-group">
					<p class="setting-title">Enter your Terms of Service Below.</p>
					<textarea id="quill-wrapper"><?= $tos ?></textarea>
					<input type="hidden" name="tos" id="tos" value="<?= htmlspecialchars($tos) ?>">
				</div>
			</div>
		</div>
		<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit">
	</form>
</div>
<script>

var configs = {
	theme: 'snow'
};

tinymce.init({
	selector: "#quill-wrapper",
	plugins: "code image hr textcolor table",
	setup: function(e){
		e.on("change", function(){
			$("#tos").val(tinyMCE.activeEditor.getContent());
		});
		e.on("init", function(){
			$("#tos").val(tinyMCE.activeEditor.getContent());
		});
	}
});

function submissionSuccess(){
	$('#errorbox-content').remove();
	$('#errorbox-bottom').append('Terms of Service successfully changed.');
	if($('#table-container').css('display') == 'none'){
		showError();
	}
}

</script>
