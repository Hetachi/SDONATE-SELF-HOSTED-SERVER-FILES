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

$SMTPServer = $results[23]['value'];
$SMTPPort = $results[24]['value'];
$security = $results[25]['value'];
$senderEmail = $results[26]['value'];
$senderPassword = $results[27]['value'];
$emailEnabled = $results[30]['value'];

?>


<div id="dashboard-content-container">
	<p id="dashboard-page-title"><?= getLangString("email") ?></p>
	<form action="ajax/dashboard/email.php" method="post">
		<input type="hidden" name="email">
		<div class="row">
			<div class="col-md-12">
				<div class="settings-group">
					<p class="setting-title">Email Enabled<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="Enable or disable email.">?</button></p>
					<select id="emailenabled" name="emailenabled" class="dropdown">
						<option value="0"><?= getLangString("disabled") ?></option>
						<option value="1"><?= getLangString("enabled") ?></option>
					</select>
				</div>
				<div class="settings-group">
					<p class="setting-title">Sending Email Address<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The email address that mail will be sent from.">?</button></p>
					<input type="text" name="sender" class="settings-text-input" value="<?= $senderEmail ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">Sending Email Password<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The password for the email address that mail will be sent from.">?</button></p>
					<input type="password" name="senderpassword" class="settings-text-input" value="<?= $senderPassword ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">SMTP Server<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The SMTP server for this email address.">?</button></p>
					<input type="text" name="smtpserver" class="settings-text-input" value="<?= $SMTPServer ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">SMTP Port<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The port this SMTP server is running on.">?</button></p>
					<input type="text" name="smtpport" class="settings-text-input" value="<?= $SMTPPort ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">Protocol<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The encryption protocol this SMTP server uses.">?</button></p>
					<select id="security" name="security" class="dropdown">
						<option value="ssl">SSL</option>
						<option value="tls">TLS</option>
					</select>
				</div>
				<div class="settings-group">
					<p class="setting-title">Email Color<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The color you want the top and bottom of emails to be.">?</button></p>
					<input type="text" name="emailcolor" class="settings-text-input" value="<?= $results[31]['value'] ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">"Purchase Complete" Email Subject<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="" data-original-title="The subject of 'Purchase Complete' emails.">?</button></p>
					<input type="text" name="purchasesubject" class="settings-text-input" value="<?= $results[29]['value'] ?>">
				</div>
				<div class="settings-group">
					<p class="setting-title">"Purchase Complete" Email Body</p>
					<textarea id="quill-wrapper"><?= $results[28]['value'] ?></textarea>
					<input type="hidden" name="purchasemessage" id="purchasemessage" value="<?= htmlspecialchars($results[28]['value']) ?>">
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
			$("#purchasemessage").val(tinyMCE.activeEditor.getContent());
		});
		e.on("init", function(){
			$("#purchasemessage").val(tinyMCE.activeEditor.getContent());
		});
	}
});

function submissionSuccess(){
	$('#errorbox-content').remove();
	$('#errorbox-bottom').append('Email settings successfully changed.');
	if($('#table-container').css('display') == 'none'){
		showError();
	}
}

$("#emailenabled").val("<?= $results[30]['value'] ?>");
$("#security").val("<?= $results[25]['value'] ?>");
</script>
