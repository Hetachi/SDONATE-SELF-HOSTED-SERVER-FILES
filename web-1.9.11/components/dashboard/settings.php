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
    $imageChecked = '';
    $textChecked = '';

    if($results[0]['value'] === 'text'){
        $textChecked = 'checked="checked"';
    }

    if($results[0]['value'] === 'image'){
        $imageChecked = 'checked="checked"';
    }

    $starpassEnabled = $results[17]["value"];
    $starpassCode = $results[18]["value"];

    if($currencycode === "EUR"){
        $starpass = '
        <div id="starpassenabled" class="settings-group">
            <p class="setting-title">StarPass Enabled<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable StarPass payments.">?</button></p>
            <a target="_blank" class="underlined-link" href="http://sdonate.readthedocs.io/en/latest/configuration/starpass.html"><p>StarPass Setup Instructions</p></a>
            <p>StarPass Access Page URL: ' . $dir . 'starpass.php</p>
            <p>StarPass Monetization Component URL: ' . $dir . 'account.php?starpasssuccess=</p>
            <select class="dropdown" style="margin-bottom: 20px;" name="starpassenabled">
                <option value="1">' . getLangString("enabled") . '</option>
                <option value="0">' . getLangString("disabled") . '</option>
            </select>
        </div>
        <div class="settings-group">
            <p class="setting-title">StarPass Protection Code<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the StarPass Access HTML.">?</button></p>
            <input type="text" name="starpasscode" id="home-page-title" class="settings-text-input" value="' . htmlspecialchars($starpassCode) . '">
        </div>
        <script>
            $("#starpassenabled select").val(' . $starpassEnabled . ');
        </script>';
    } else {
        $starpass = "";
    }

?>

<div id="dashboard-content-container">
    <p id="dashboard-page-title"><?= getLangString("general-settings") ?></p>
    <form action="ajax/dashboard/generalsettings.php" method="post" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-12">
                <div id="defaultlanguage" class="settings-group">
                    <p class="setting-title">Default Language<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Set the default language of your store.">?</button></p>
                    <select class="dropdown" style="margin-bottom: 20px;" name="defaultlanguage">
                        <option value="en">English</option>
						<option value="de">German</option>
                        <option value="es">Spanish</option>
                        <option value="fr">French</option>
                        <option value="no">Norwegian</option>
						<option value="pt">Portugese</option>
                    </select>
                </div>
                <div id="maintenancemode" class="settings-group">
                    <p class="setting-title">Maintenance Mode<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable Maintenance Mode to prevent anyone accessing the site.">?</button></p>
                    <select class="dropdown" style="margin-bottom: 20px;" name="maintenancemode">
                        <option value="1"><?= getLangString("enabled") ?></option>
                        <option value="0"><?= getLangString("disabled") ?></option>
                    </select>
                </div>
                <div id="loginmode" class="settings-group">
                    <p class="setting-title">Login Mode<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable Steam Only login to only allow people to sign in with steam, or enable Steam + Username login to allow people to register with an email and username.">?</button></p>
                    <select class="dropdown" style="margin-bottom: 20px;" name="loginmode">
                        <option value="1">Steam Only</option>
                        <option value="0">Steam + Username</option>
                    </select>
                </div>
                <div id="paymentmode" class="settings-group">
                    <p class="setting-title">Payment Mode<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select whether people need to buy credit to purchase packages or whether they can buy packages directly. See the documentation for more info.">?</button></p>
                    <select class="dropdown" style="margin-bottom: 20px;" name="paymentmode">
                        <option value="directpurchase">Default - Purchase Directly</option>
                        <option value="creditpurchase">Credit Only</option>
                    </select>
                </div>
                <div id="paypalemail" class="settings-group">
                    <p class="setting-title">PayPal Email<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The email address of the PayPal account used to receive payments.">?</button></p>
                    <input type="text" name="paypalemail" id="paypal-email" class="settings-text-input" value="<?= $results[21]['value'] ?>">
                </div>
                <div id="paypalenabled" class="settings-group">
                    <p class="setting-title">PayPal Enabled<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable or disable PayPal.">?</button></p>
                    <a target="_blank" class="underlined-link" href="http://sdonate.readthedocs.io/en/latest/configuration/paypal.html"><p>PayPal Setup Instructions</p></a>
                    <select class="dropdown" style="margin-bottom: 20px;" name="paypalenabled">
                        <option value="1"><?= getLangString("enabled") ?></option>
                        <option value="0"><?= getLangString("disabled") ?></option>
                    </select>
                </div>
                <div id="paypalbutton" class="settings-group">
                    <p class="setting-title">PayPal Button Type<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Choose the type of PayPal payment button you want to use.">?</button></p>
                    <select class="dropdown" style="margin-bottom: 20px;" name="paypalbutton">
                        <option value="_xclick">Buy Now</option>
                        <option value="_donations">Donate</option>
                    </select>
                </div>
                <div id="paypalsandbox" class="settings-group">
                    <p class="setting-title">PayPal Sandbox<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable PayPal sandbox mode to test everything is working without actually spending money. NOTE: while PayPal sandbox is enabled only admins will be able to purchase and transactions will not appear in the transaction history.">?</button></p>
                    <select class="dropdown" style="margin-bottom: 20px;" name="paypalsandbox">
                        <option value="1"><?= getLangString("enabled") ?></option>
                        <option value="0"><?= getLangString("disabled") ?></option>
                    </select>
                </div>
                <?= $starpass ?>
                <div id="creditsenabled" class="settings-group">
                    <p class="setting-title">Credits Enabled<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable or disable credits.">?</button></p>
                    <select class="dropdown" style="margin-bottom: 20px;" name="creditsenabled">
                        <option value="1"><?= getLangString("enabled") ?></option>
                        <option value="0"><?= getLangString("disabled") ?></option>
                    </select>
                </div>
                <div class="settings-group">
                    <p class="setting-title">Store Name<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Choose the title that is displayed on your homepage.">?</button></p>
                    <input type="text" name="homepagetitle" id="home-page-title" class="settings-text-input" value="<?= $results[2]['value'] ?>">
                </div>
                <div class="settings-group">
                    <p class="setting-title">Store Logo<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Choose the logo that is displayed at the top left of the website. If text is chosen the store name will be displayed.">?</button></p>
                    <input type="radio" name="logotype" <?= $textChecked ?> value="logotypetext"><span class="dashboard-radio-lbl">Text</span></input>
                    <input type="radio" name="logotype" <?= $imageChecked ?> value="logotypeimg"><span class="dashboard-radio-lbl">Image</span></input>
                    <div id="logosettings">
                    </div>
                    <script>
                        function changeLogoSettings(){
                            var logotype = $("input[name=logotype]:radio:checked").val();
                            if(logotype == "logotypeimg"){
                                $("#logosettings").html('<input type="file" name="logoimgfile" id="logoimgfile">');
                            } else {
                                $("#logosettings").html('');
                            }
                        }
                        $(document).ready(function(){
                            changeLogoSettings();
                        })
                        $("input[name=logotype]:radio").change(function () {
                            changeLogoSettings();
                        });
                    </script>
                </div>
                <div class="settings-group">
                    <p class="setting-title">Home Page Text<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Choose the text that is displayed on your homepage.">?</button></p>
                    <textarea id="home-page-text-input" class="settings-text-input"><?= $results[3]['value'] ?></textarea>
                    <input type="hidden" name="homepagetext" id="home-page-text">
                    <script>
                        $(document).ready(function(){
                            $("#home-page-text").val($("#home-page-text-input").val());
                        });
                        $("#home-page-text-input").bind("input propertychange", function() {
                            $("#home-page-text").val($("#home-page-text-input").val());
                        });
                    </script>
                </div>
                <div id="donatorstats" class="settings-group">
                    <p class="setting-title">Display Top/Recent Donators on Home Page<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Display Top/Recent Donators on Home Page.">?</button></p>
                    <select class="dropdown" style="margin-bottom: 20px;" name="donatorstats">
                        <option value="0"><?= getLangString("disabled") ?></option>
                        <option value="1"><?= getLangString("enabled") ?></option>
                    </select>
                </div>
                <div id="donationstotal" class="settings-group">
                    <p class="setting-title">Display Total Donations on Home Page<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Display Total Donations on Home Page.">?</button></p>
                    <select class="dropdown" style="margin-bottom: 20px;" name="donationstotal">
                        <option value="0"><?= getLangString("disabled") ?></option>
                        <option value="1"><?= getLangString("enabled") ?></option>
                    </select>
                </div>
            </div>
        </div>
        <input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit">
    </form>
</div>
<script>
    $("#defaultlanguage select").val("<?= $results[34]["value"] ?>");
    $("#maintenancemode select").val(<?= $results[8]["value"] ?>);
    $("#loginmode select").val(<?= $results[9]["value"] ?>);
    $("#paypalsandbox select").val(<?= $results[10]["value"] ?>);
    $("#paypalenabled select").val(<?= $results[19]["value"] ?>);
    $("#paymentmode select").val("<?= $results[20]["value"] ?>");
    $("#creditsenabled select").val(<?= $results[22]["value"] ?>);
    $("#donatorstats select").val(<?= $results[32]["value"] ?>);
    $("#donationstotal select").val(<?= $results[33]["value"] ?>);
    $("#paypalbutton select").val("<?= $results[35]["value"] ?>");
    function submissionSuccess(){
        $('#errorbox-content').remove();
        $('#errorbox-bottom').append('Settings successfully changed.');
        if($('#table-container').css('display') == 'none'){
            showError();
        }
    }
    <?php
        if($demoMode === true){
            print('showError("The following settings cannot be edited in demo mode: PayPal Sandbox, Login Mode, Maintenance Mode.");');
        }
    ?>
</script>
