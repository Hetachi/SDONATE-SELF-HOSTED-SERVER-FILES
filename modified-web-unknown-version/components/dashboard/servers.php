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

?>

<div id="dashboard-content-container">
    <p id="dashboard-page-title"><?= getLangString("servers") ?></p>
    <div class="row">
        <div class="col-md-12">
            <div class="dashboard-stat-large">
                <div class="statistics-title">&nbsp;</div>
                <div class="statistics-content table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Game<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The game the server is for.">?</button></th>
                                <th>Name<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The name of the server.">?</button></th>
                                <th>IP<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The IP address of the server. NOTE THIS MUST BE NUMBERS ONLY, NOT A URL.">?</button></th>
                                <th>Port<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The port the server runs on.">?</button></th>
                                <th><?= getLangString("enabled") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Whether this server is enabled in your store or not.">?</button></th>
                                <th style="text-align: center;">Test<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Send a console command to the server to test that it is working.">?</button></th>
                                <th style="text-align: center;"><?= getLangString("edit") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if($serverCount === 0){
                                print('
                                    <tr>
                                        <td>You don\'t have any servers added!</td>
                                    </tr>
                                    ');
                            } else {
                                foreach($servers as $key => $value){
                                    $gameid = $servers[$key]['game'];
                                    $sql = $dbcon->prepare("SELECT gamename FROM games WHERE id=" . $gameid);
                                    $sql->execute();
                                    $gameRow = $sql->fetchAll(PDO::FETCH_ASSOC);

                                    $game = $gameRow[0]['gamename'];
                                    print('
                                        <tr>
                                            <td>' . $game . '</td>
                                            <td>' . $servers[$key]['name'] . '</td>
                                            <td>' . $servers[$key]['ip'] . '</td>
                                            <td>' . $servers[$key]['port'] . '</td>
                                            <td>' . $serversEnabled[$key] . '</td>
                                            <td style="text-align: center;"><a href="#" onclick="testServer(' . $key . ');"><span class="glyphicon glyphicon-cog"></span></a></td>
                                            <td style="text-align: center;"><a href="#" onclick="editServer(' . $key . ');"><span class="glyphicon glyphicon-pencil"></span></a></td>
                                        </tr>
                                    ');
                                }
                            }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <button class="submit-button" onclick="addServer();" style="margin-left: 0px; margin-bottom: 60px;">Add Server</button>
        </div>
    </div>
</div>
<script>
    var games = <?= $gamesJS ?>;
    var gamesEnabled = <?= $gamesEnabledJS ?>;
    var servers = <?= $serversJS ?>;
    var serversEnabled = <?= $serversEnabledJS ?>;
    var gameOptions = "";
    $.each(games, function(key, value){
        var html = "<option value=\"" + games[key]["id"] + "\">" + games[key]["gamename"] + "</option>";
        gameOptions += html;
    });

    function gameChanged(){
        var gamename = $("#servergame option:selected").text();
        if(gamename !== "Garry\'s Mod" && gamename !== "Minecraft" && gamename !== "Rust"){
            $("#servergame").nextAll("#rconcontainer").show();
        } else {
            $("#servergame").nextAll("#rconcontainer").hide();
        }
    }

    function deleteImage(type, id){

        var formData = "deleteimage=" + "&type=" + type + "&id=" + id;

        $.ajax({
            url : "ajax/dashboard/servers.php",
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

    function testServer(key){
        var html = '' +
            '<form action="ajax/dashboard/servers.php" method="post" enctype="multipart/form-data">\n' +
                '<input type="hidden" name="testserver" value="' + servers[key]["id"] + '">\n' +
                '<p id="errorbox-title">Test Server</p>\n' +
                '<p>Send a console command to the server to test that it is working. Note, the command may take up to 1 minute to execute.</p>\n' +
                '<p class="setting-title">Command<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the console command to run.">?</button></p>\n' +
                '<input style="margin-bottom: 20px;" type="text" name="command" id="command" class="settings-text-input">\n' +
                '<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit" style="display: inline-block; margin-left: 0px;">\n' +
            '</form>';
        showError(html);
        enableToolTips();
        listenForSubmit();
    }

    function editServer(key){
        var serversEnabledChecked = "";
        if(serversEnabled[key] === "Yes"){
            serversEnabledChecked = "checked";
        }
        var html = '' +
            '<form action="ajax/dashboard/servers.php" method="post" enctype="multipart/form-data">\n' +
                '<input type="hidden" name="editserver">\n' +
                '<input type="hidden" name="serverid" value="' + servers[key]["id"] + '">\n' +
                '<p id="errorbox-title">Edit Server</p>\n' +
                '<p class="setting-title"><?= getLangString("game") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the game this server is for.">?</button></p>\n' +
                '<select class="dropdown" style="margin-bottom: 20px;" id="servergame" name="servergame">' + gameOptions + '</select>\n' +
                '<p class="setting-title">Name<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the name of the server.">?</button></p>\n' +
                '<input style="margin-bottom: 20px;" type="text" name="servername" id="servername" class="settings-text-input" value="' + servers[key]["name"].replace('"', '\"') + '">\n' +
                '<p class="setting-title">Image<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select an image to display for this server. Click Delete to reset back to default game image.">?</button></p>\n' +
                '<button type="button" class="submit-button" style="display: inline-block; margin-left: 0px; float: right;" onclick="deleteImage(\'server\', ' + servers[key]["id"] + ');"><?= getLangString("delete") ?></button>\n' +
                '<input style="margin-bottom: 20px;" type="file" name="serverimagefile" id="serverimagefile">\n' +
                '<p class="setting-title">IP Address<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the IP of the server WITHOUT THE PORT.">?</button></p>\n' +
                '<input style="margin-bottom: 20px;" type="text" name="serverip" id="serverip" class="settings-text-input" value="' + servers[key]["ip"].replace('"','\"') + '">\n' +
                '<p class="setting-title">Port<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the port of the server. This is the part after the colon (:). If you don\'t know the port, enter the default port for this game.">?</button></p>\n' +
                '<input style="margin-bottom: 20px;" type="text" name="serverport" id="serverport" class="settings-text-input" value="' + servers[key]["port"].replace('"','\"') + '">\n' +
                '<div id="rconcontainer">\n' +
                    '<p class="setting-title">RCON Password<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the RCON password of this server.">?</button></p>\n' +
                    '<input style="margin-bottom: 20px;" type="text" name="serverrcon" id="serverrcon" class="settings-text-input" value="' + servers[key]["rconpass"].replace('"','\"') + '">\n' +
                '</div>\n' +
                '<p class="setting-title"><?= getLangString("enabled") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable or disable the server in your store.">?</button></p>\n' +
                '<input style="display: block; margin-bottom: 20px;" type="checkbox" name="serverenabled" id="serverenabled" ' + serversEnabledChecked +'>\n' +
                '<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit" style="display: inline-block; margin-left: 0px;">\n' +
                '<button type="button" class="submit-button" style="display: inline-block; margin-left: 0px; float: right;" onclick="deleteServer(' + key + ');"><?= getLangString("delete") ?></button>\n' +
            '</form>';
        showError(html);
        enableToolTips();
        $("#errorbox-bottom select").val(servers[key]["game"]);
        gameChanged();
        $("#servergame").change(function(){gameChanged();});
        listenForSubmit();
    }

    function deleteServer(key){
        var html = '' +
            '<form action="ajax/dashboard/servers.php" method="post">\n' +
                '<p style="text-align: center;">Do you really want to delete ' + servers[key]["name"] + '?</p>\n' +
                '<input type="hidden" value="' + servers[key]["id"] + '" name="deleteserver">\n' +
                '<input class="submit-button" type="submit" value="<?= getLangString("delete") ?>" name="submit" style="margin-left: auto; margin-right: auto; margin-bottom: 0px;">\n' +
            '</form>';
        showError1(html);
        listenForSubmit();
    }

    function addServer(){
        var html = '' +
            '<form action="ajax/dashboard/servers.php" method="post" enctype="multipart/form-data">\n' +
                '<input type="hidden" name="addserver">\n' +
                '<p id="errorbox-title">Add Server</p>\n' +
                '<p class="setting-title"><?= getLangString("game") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select the game this server is for.">?</button></p>\n' +
                '<select class="dropdown" style="margin-bottom: 20px;" id="servergame" name="servergame">' + gameOptions + '</select>\n' +
                '<p class="setting-title">Name<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the name of the server.">?</button></p>\n' +
                '<input style="margin-bottom: 20px;" type="text" name="servername" id="servername" class="settings-text-input">\n' +
                '<p class="setting-title">Image<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select an image to display for this server. Click Delete to reset back to default game image.">?</button></p>\n' +
                '<input style="margin-bottom: 20px;" type="file" name="serverimagefile" id="serverimagefile">\n' +
                '<p class="setting-title">IP Address<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the IP of the server WITHOUT THE PORT.">?</button></p>\n' +
                '<input style="margin-bottom: 20px;" type="text" name="serverip" id="serverip" class="settings-text-input">\n' +
                '<p class="setting-title">Port<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the port of the server. This is the part after the colon (:). If you don\'t know the port, enter the default port for this game.">?</button></p>\n' +
                '<input style="margin-bottom: 20px;" type="text" name="serverport" id="serverport" class="settings-text-input">\n' +
                '<div id="rconcontainer">\n' +
                    '<p class="setting-title">RCON Password<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enter the RCON password of this server.">?</button></p>\n' +
                    '<input style="margin-bottom: 20px;" type="text" name="serverrcon" id="serverrcon" class="settings-text-input">\n' +
                '</div>\n' +
                '<p class="setting-title"><?= getLangString("enabled") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable or disable the server in your store.">?</button></p>\n' +
                '<input style="margin-bottom: 20px; display: block;" type="checkbox" name="serverenabled" id="serverenabled">\n' +
                '<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit" style="display: inline-block; margin-left: 0px;">\n' +
            '</form>';
        showError(html);
        enableToolTips();
        gameChanged();
        $("#servergame").change(function(){gameChanged();});
        listenForSubmit();
    }

    function submissionSuccess(){
        location.reload(true);
    }
</script>
