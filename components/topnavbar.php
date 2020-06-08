<?php
    if(!isset($currentPage)){
        $currentPage = "home";
    }
?>

<div id="fade-overlay"></div>
<div id="table-container">
    <div id="errorbox-container">
        <div id="errorbox">
            <div id="errorbox-top"><a href="#" onclick="closeErrorBox();">X</a></div>
            <div id="errorbox-bottom">

            </div>
        </div>
    </div>
</div>
<div id="fade-overlay-1"></div>
<div id="table-container-1">
    <div id="errorbox-container-1">
        <div id="errorbox-1">
            <div id="errorbox-top-1"><a href="#" onclick="closeErrorBox1();">X</a></div>
            <div id="errorbox-bottom-1">

            </div>
        </div>
    </div>
</div>
<div id="fade-overlay-2"></div>
<div id="table-container-2">
    <div id="errorbox-container-2">
        <div id="errorbox-2">
            <div id="errorbox-top-2"><a href="#" onclick="closeErrorBox2();">X</a></div>
            <div id="errorbox-bottom-2">

            </div>
        </div>
    </div>
</div>

<div id="top-navbar-container">
    <div id="top-navbar-left">
        <?php
            $sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='logoimgtype'");
            $sql->execute();
            $result = $sql->fetchAll(PDO::FETCH_ASSOC);

            $logoimgtype = $result[0]['value'];

            $sql = $dbcon->prepare("SELECT value FROM settings WHERE setting='logoimgcontent'");
            $sql->execute();
            $result = $sql->fetchAll(PDO::FETCH_ASSOC);

            $logoimgcontent = $result[0]['value'];
            escapeHTML($logoimgcontent);

            if($logoimgtype === 'text'){
                print('<div id="top-navbar-left-img">' . $logoimgcontent . '</div>');
            } else {
                print('<img id="top-navbar-left-img" src="' . $logoimgcontent . '">');
            }
        ?>
        <ul id="top-navbar-left-list">
            <a href="index.php"><li class="top-navbar-left-button<?php if($currentPage == "home"){echo " active";} ?>">
            <span class="glyphicon glyphicon-home" style="margin-right: 10px;"></span><?= getLangString("home"); ?></li></a><a href="packages.php"><li class="top-navbar-left-button<?php if($currentPage == "store"){echo " active";} ?>">
            <span class="glyphicon glyphicon-shopping-cart" style="margin-right: 10px;"></span><?= getLangString("store"); ?>
            </li></a>
        </ul>
    </div>
    <div id="top-navbar-right">
        <div class="dropdownmenu">
            <a href="#" onclick="langMenu();"><div class="top-navbar-right-button dropdown-button"><?= strtoupper($chosenLang); ?></div></a>
            <div id="lang-dropdown" class="dropdowncontent">
				<a href="?lang=de">DE</a>
                <a href="?lang=en">EN</a>
                <a href="?lang=es">ES</a>
                <a href="?lang=fr">FR</a>
                <a href="?lang=no">NO</a>
				<a href="?lang=pt">PT</a>
            </div>
        </div>
        <?php
            if(isset($_SESSION['admin'])){
                if($_SESSION['admin'] === true){
                    print('<a href="dashboard.php"><div class="top-navbar-right-button' . ($currentPage == "dashboard" ? " active" : "") . '"><span class="glyphicon glyphicon-cog" style="margin-right: 10px;"></span>Admin</div></a>');
                }
            }

            if(!isset($_SESSION['username'])) {
                print('<a href="login.php"><div class="top-navbar-right-button' . ($currentPage == "login" ? " active" : "") . '"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span>' . getLangString("login") . '</div></a>');
            } else {
                print('<a href="account.php"><div class="top-navbar-right-button' . ($currentPage == "account" ? " active" : "") . '"><span class="glyphicon glyphicon-user" style="margin-right: 10px;"></span>' . getLangString("account") . '</div></a>');
            }
        ?>
    </div>
    <div id="menu-button">
        <span class="glyphicon glyphicon-chevron-down" style="vertical-align: middle;"></span>
    </div>
</div>
