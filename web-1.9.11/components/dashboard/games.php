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

?>

<div id="dashboard-content-container">
    <p id="dashboard-page-title"><?= getLangString("games") ?></p>
    <div class="row">
        <div class="col-md-12">
            <div class="dashboard-stat-large">
                <div class="statistics-title">&nbsp;</div>
                <div class="statistics-content table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?= getLangString("game") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="The title of the game.">?</button></th>
                                <th><?= getLangString("enabled") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Whether the game is enabled in your store or not.">?</button></th>
                                <th style="text-align: center;"><?= getLangString("edit") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            if($gameCount === 0){
                				print('
                					<tr>
                						<td>You don\'t have any games added!</td>
                					</tr>
                					');
                			} else {

                				foreach($games as $key => $value){
                					print('
                						<tr>
                							<td>' . $games[$key]['gamename'] . '</td>
                							<td>' . $gamesEnabled[$value['id']] . '</td>
                							<td style="text-align: center;"><a href="#" onclick="editGame(' . $key . ');"><span class="glyphicon glyphicon-pencil"></span></a></td>
                						</tr>
                						');
                				}

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
    var games = <?= $gamesJS ?>;
    var gamesEnabled = <?= $gamesEnabledJS ?>;

    function deleteImage(type, id){

        var formData = "deleteimage=" + "&type=" + type + "&id=" + id;

        $.ajax({
            url : "ajax/dashboard/games.php",
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

    function editGame(key){
        var gamesEnabledChecked = "";
        if(gamesEnabled[games[key]["id"]] === "Yes"){
            gamesEnabledChecked = "checked";
        }
        var html = '' +
            '<form action="ajax/dashboard/games.php" method="post" enctype="multipart/form-data">\n' +
                '<input type="hidden" name="editgame">\n' +
                '<input type="hidden" name="gameid" value="' + games[key]["id"] + '">\n' +
                '<p id="errorbox-title">Edit Game</p>\n' +
                '<p class="setting-title"><?= getLangString("enabled") ?><button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Enable or disable the game in your store.">?</button></p>\n' +
                '<input style="margin-bottom: 20px;" type="checkbox" name="gameenabled" id="gameenabled" ' + gamesEnabledChecked +'>\n' +
                '<p class="setting-title">Image<button type="button" class="btn btn-default btn-sm tooltip-btn" data-toggle="tooltip" data-placement="top" title="Select an image to display for this game or click on Reset to set back to default.">?</button></p>\n' +
                '<button type="button" class="submit-button" style="display: inline-block; margin-left: 0px; float: right;" onclick="deleteImage(\'game\', ' + games[key]["id"] + ');">Reset</button>\n' +
                '<input style="margin-bottom: 20px;" type="file" name="gameimagefile" id="gameimagefile">\n' +
                '<input class="submit-button" type="submit" value="<?= getLangString("submit") ?>" name="submit" style="display: inline-block; margin-left: 0px;">\n' +
            '</form>';
        showError(html);
        enableToolTips();
        listenForSubmit();

    }

    function submissionSuccess(){
        location.reload(true);
    }
</script>
