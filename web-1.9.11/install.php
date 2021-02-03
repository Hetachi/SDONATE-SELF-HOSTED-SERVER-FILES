<?php

//Enter your Steam ID (i.e. 76561198134262586), leave empty if you do not have a Steam account
$adminsteamid = '76561198134262586';

//This is the username of the admin account. If you have entered your Steam ID above you can leave this empty.
$adminusername = '';

//This is the password to the admin account. Make sure it is secure! If you have entered your Steam ID above you can leave this empty.
$adminpassword = '';


//DO NOT EDIT BELOW HERE

if(empty($adminsteamid)){
	if(strlen($adminusername) < 6 OR strlen($adminusername) > 32){
		print("The admin username must be between 6 and 32 characters long.");
		exit();
	} else {
		if(preg_match('/^[\a-zA-Z0-9 .-_]+$/', $adminusername) !== 1){
			print("The admin username may only contain letters A-Z, numbers, spaces, underscores and hyphens.");
			exit();
		}
	}
	if(strlen($adminpassword) < 8){
		print("The admin password must be at least 8 characters long.");
		exit();
	}

	$hashed = password_hash($adminpassword, PASSWORD_DEFAULT);
	if($hashed === FALSE){
		print("An error occured while hashing the admin password, please try again. If this error is recurring please try another webhost.");
		exit();
	}
}

require_once('config.php');
require 'steamauth/settings.php';

ini_set('session.cookie_domain', substr($_SERVER['SERVER_NAME'],strpos($_SERVER['SERVER_NAME'],"."),100));

try {
	$dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
	$dbcon->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch(PDOException $e){
	echo 'MySQL Error:' . $e->getMessage();
	exit();
}

if(array_key_exists($currencycode, $currencySymbols) === false){
	print("Invalid currency code.");
	exit();
}

if(tableExists($dbcon, 'actions') === FALSE){
	$sql = "CREATE table actions(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		game TEXT,
		name TEXT,
		execute TEXT,
		startcommand TEXT,
		endcommand TEXT,
		type TEXT);";
	$dbcon->exec($sql);

	$actions[0] = array(':game' => 'Garry\'s Mod', ':name' => 'Console Command', ':execute' => 'choice', ':startcommand' => 'game.ConsoleCommand("{{INPUT=Command|TYPE=varchar}}\n")', ':endcommand' => 'game.ConsoleCommand("{{INPUT=Package Expiry Command|TYPE=varchar}}\n")', ':type' => 'premade');
	$actions[1] = array(':game' => 'Garry\'s Mod', ':name' => 'Custom LUA', ':execute' => 'choice', ':startcommand' => "{{INPUT=LUA|TYPE=varchar}}", ':endcommand' => "{{INPUT=Package Expiry LUA|TYPE=varchar}}", ':type' => 'premade');
	$actions[2] = array(':game' => 'Garry\'s Mod', ':name' => 'Add to Group - Assmod', ':execute' => 'onjoin', ':startcommand' => "player.GetBySteamID64(\"{{VAR=STEAMID}}\"):SetLevel({{INPUT=Rank Number|TYPE=varchar}})", ':endcommand' => "player.GetBySteamID64(\"{{VAR=STEAMID}}\"):SetLevel(ASS_LVL_GUEST)", ':type' => 'premade');
	$actions[3] = array(':game' => 'Garry\'s Mod', ':name' => 'Add to Group - Evolve', ':execute' => 'onjoin', ':startcommand' => "player.GetBySteamID64(\"{{VAR=STEAMID}}\"):EV_SetRank(\"{{INPUT=Rank|TYPE=varchar}}\")", ':endcommand' => "player.GetBySteamID64(\"{{VAR=STEAMID}}\"):EV_SetRank(\"guest\")", ':type' => 'premade');
	$actions[4] = array(':game' => 'Garry\'s Mod', ':name' => 'Add to Group - FAdmin', ':execute' => 'onjoin', ':startcommand' => "RunConsoleCommand(\"fadmin\", \"setaccess\", \"{{VAR=STEAMID32}}\", \"{{INPUT=Rank|TYPE=varchar}}\")", ':endcommand' => "RunConsoleCommand(\"fadmin\", \"setaccess\", \"{{VAR=STEAMID32}}\", \"user\"))", ':type' => 'premade');
	$actions[5] = array(':game' => 'Garry\'s Mod', ':name' => 'Add to Group - ULX', ':execute' => 'now', ':startcommand' => "RunConsoleCommand(\"ulx\", \"adduserid\", \"{{VAR=STEAMID32}}\", \"{{INPUT=Rank|TYPE=varchar}}\")", ':endcommand' => "RunConsoleCommand(\"ulx\", \"removeuserid\", \"{{VAR=STEAMID32}}\")", ':type' => 'premade');
	$actions[6] = array(':game' => 'Garry\'s Mod', ':name' => 'DarkRP Add Money', ':execute' => 'onjoin', ':startcommand' => "while true do if player.GetBySteamID64(\"{{VAR=STEAMID}}\"):getDarkRPVar(\"money\") then player.GetBySteamID64(\"{{VAR=STEAMID}}\"):addMoney({{INPUT=Money|TYPE=numeric}}) return false end end", ':endcommand' => "", ':type' => 'premade');
	$actions[7] = array(':game' => 'Garry\'s Mod', ':name' => 'DarkRP Custom Job', ':execute' => 'now', ':startcommand' => "addDarkRPCustomJob(\"{{VAR=STEAMID}}\", \"{{INPUT=Job Name|TYPE=varchar}}\", \"{{INPUT=Models|TYPE=varcharmulti}}\", \"{{INPUT=Description|TYPE=varchar}}\", \"{{INPUT=Weapons|TYPE=varcharmulti}}\", {{INPUT=Salary|TYPE=numeric}}, \"{{INPUT=Has License|TYPE=bool}}\")", ':endcommand' => "removeDarkRPCustomJob(\"{{VAR=STEAMID}}\", \"{{INPUT=Job Name|TYPE=varchar}}\", \"{{INPUT=Models|TYPE=varcharmulti}}\", \"{{INPUT=Description|TYPE=varchar}}\", \"{{INPUT=Weapons|TYPE=varcharmulti}}\", {{INPUT=Salary|TYPE=numeric}}, \"{{INPUT=Has License|TYPE=bool}}\")", ':type' => 'premade');
	$actions[8] = array(':game' => 'Garry\'s Mod', ':name' => 'Pointshop 1 Points', ':execute' => 'onjoin', ':startcommand' => "player.GetBySteamID64(\"{{VAR=STEAMID}}\"):PS_GivePoints({{INPUT=Points|TYPE=numeric}})", ':endcommand' => "", ':type' => 'premade');
	$actions[9] = array(':game' => 'Garry\'s Mod', ':name' => 'Pointshop 2 Standard Points', ':execute' => 'onjoin', ':startcommand' => 'game.ConsoleCommand("ps2_addpoints {{VAR=STEAMID32}} points {{INPUT=Points|TYPE=numeric}}\n")', ':endcommand' => "", ':type' => 'premade');
	$actions[10] = array(':game' => 'Garry\'s Mod', ':name' => 'Pointshop 2 Premium Points', ':execute' => 'onjoin', ':startcommand' => 'game.ConsoleCommand("ps2_addpoints {{VAR=STEAMID32}} premiumPoints {{INPUT=Points|TYPE=numeric}}\n")', ':endcommand' => "", ':type' => 'premade');
	$actions[11] = array(':game' => 'Garry\'s Mod', ':name' => 'Send Chat Message to Buyer', ':execute' => 'onjoin', ':startcommand' => "player.GetBySteamID64(\"{{VAR=STEAMID}}\"):ChatPrint(\"{{INPUT=Message|TYPE=varchar}}\")", ':endcommand' => "player.GetBySteamID64(\"{{VAR=STEAMID}}\"):ChatPrint(\"{{INPUT=Expiration Message|TYPE=varchar}}\")", ':type' => 'premade');
	$actions[12] = array(':game' => 'Garry\'s Mod', ':name' => 'Send Chat Message to All Players', ':execute' => 'onjoin', ':startcommand' => "for k, ply in pairs( player.GetAll() ) do ply:ChatPrint(\"{{INPUT=Message|TYPE=varchar}}\") end", ':endcommand' => "for k, ply in pairs( player.GetAll() ) do ply:ChatPrint(\"{{INPUT=Expiration Message|TYPE=varchar}}\") end", ':type' => 'premade');
	$actions[13] = array(':game' => 'Minecraft', ':name' => 'Console Command', ':execute' => 'choice', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "{{INPUT=Package Expiry Command|TYPE=varchar}}", ':type' => 'premade');
	$actions[14] = array(':game' => 'ARK: Survival Evolved', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');
	$actions[15] = array(':game' => 'Counter-Strike: Global Offensive', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');
	$actions[16] = array(':game' => 'Left 4 Dead 2', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');
	$actions[17] = array(':game' => 'Rust', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');
	$actions[18] = array(':game' => 'Team Fortress 2', ':name' => 'RCON Console Command', ':execute' => 'now', ':startcommand' => "{{INPUT=Command|TYPE=varchar}}", ':endcommand' => "", ':type' => 'premade');

	$sql = $dbcon->prepare("INSERT INTO actions(game, name, execute, startcommand, endcommand, type) VALUES(:game, :name, :execute, :startcommand, :endcommand, :type)");

	foreach ($actions as $key => $value) {
		$sql->execute($value);
	}

}

if(tableExists($dbcon, 'games') === FALSE){
	$sql = "CREATE table games(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		gamename TEXT,
		gameimg TEXT,
		oldimg TEXT,
		usernametype TEXT,
		enabled INT(1));";
	$dbcon->exec($sql);
	$games[0] = array(':id' => 1, ':gamename' => 'Garry\'s Mod', ':gameimg' => 'gmod.png', ':oldimg' => 'gmod.png', ':usernametype' => 'Steam', ':enabled' => 1);
	$games[1] = array(':id' => 2, ':gamename' => 'Minecraft', ':gameimg' => 'mc.png', ':oldimg' => 'mc.png', ':usernametype' => 'Minecraft Username', ':enabled' => 1);
	$games[2] = array(':id' => 3, ':gamename' => 'Rust', ':gameimg' => 'rust.png', ':oldimg' => 'rust.png', ':usernametype' => 'Steam', ':enabled' => 0);
	$games[3] = array(':id' => 4, ':gamename' => 'Counter-Strike: Global Offensive', ':gameimg' => 'csgo.png', ':oldimg' => 'csgo.png', ':usernametype' => 'Steam', ':enabled' => 0);
	$games[4] = array(':id' => 5, ':gamename' => 'Team Fortress 2', ':gameimg' => 'tf2.png', ':oldimg' => 'tf2.png', ':usernametype' => 'Steam', ':enabled' => 0);
	$games[5] = array(':id' => 6, ':gamename' => 'Left 4 Dead 2', ':gameimg' => 'l4d2.png', ':oldimg' => 'l4d2.png', ':usernametype' => 'Steam', ':enabled' => 0);
	$games[6] = array(':id' => 7, ':gamename' => 'ARK: Survival Evolved', ':gameimg' => 'ase.png', ':oldimg' => 'ase.png', ':usernametype' => 'Steam', ':enabled' => 0);
	$sql = $dbcon->prepare("INSERT INTO games(id, gamename, gameimg, oldimg, usernametype, enabled) VALUES(:id, :gamename, :gameimg, :oldimg, :usernametype, :enabled)");

	foreach ($games as $key => $value) {
		$sql->execute($value);
	}

}

if(tableExists($dbcon, 'servers') === FALSE){
	$sql = "CREATE table servers(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		game INT(11) ,
		name TEXT ,
		img TEXT ,
		ip TEXT ,
		port TEXT ,
		rconpass TEXT ,
		enabled INT(1) );";
	$dbcon->exec($sql);
}

if(tableExists($dbcon, 'packages') === FALSE){
	$sql = "CREATE table packages(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		game INT(11) ,
		servers TEXT ,
		title TEXT,
		description TEXT,
		img TEXT,
		paywhatyouwant INT(1),
		price DECIMAL(11,2),
		maxpurchases INT(11),
		commands TEXT,
		expires DECIMAL(20,2),
		enabled INT(1));";
	$dbcon->exec($sql);
}

if(tableExists($dbcon, 'commandstoexecute') === FALSE){
	$sql = "CREATE table commandstoexecute(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		server TEXT,
		port TEXT,
		command TEXT,
		player TEXT,
		executenow TINYINT DEFAULT 0,
		ready TINYINT DEFAULT 1,
		transactionid TEXT);";
	$dbcon->exec($sql);
}

if(tableExists($dbcon, 'startupcommands') === FALSE){
	$sql = "CREATE table startupcommands(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		server TEXT,
		port TEXT,
		command TEXT);";
	$dbcon->exec($sql);
}

if(tableExists($dbcon, 'users') === FALSE){
	$sql = "CREATE table users(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		username TEXT,
		steamid TEXT,
		avatar TEXT,
		email TEXT,
		password TEXT,
		usertype TEXT,
		credit DECIMAL(11,2) DEFAULT 0.00);";
	$dbcon->exec($sql);

	if(!empty($adminsteamid)){
		$url = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $steamapi . "&steamids=" . $adminsteamid);
        $content = json_decode($url, true);
        $adminusername = $content['response']['players'][0]['personaname'];
        $adminavatar = $content['response']['players'][0]['avatarfull'];
		$sql = $dbcon->prepare("INSERT INTO users(username, steamid, avatar, usertype) VALUES(:username, :steamid, :avatar, :usertype)");
		$values = array(':username' => $adminusername, ':steamid' => $adminsteamid, ':avatar' => $adminavatar, ':usertype' => 'admin');
		$sql->execute($values);
	} else {
		$sql = $dbcon->prepare("INSERT INTO users(username, password, usertype) VALUES(:username, :password, :usertype)");
		$values = array(':username' => $adminusername, ':password' => $hashed, ':usertype' => 'admin');
		$sql->execute($values);
	}

}

if(tableExists($dbcon, 'resetpassword') === FALSE){
	$sql = "CREATE table resetpassword(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		username TEXT,
		resetkey TEXT,
		expires TIMESTAMP DEFAULT CURRENT_TIMESTAMP);";
	$dbcon->exec($sql);
}

if(tableExists($dbcon, 'transactions') === FALSE){
	$sql = "CREATE table transactions(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		purchaser TEXT,
		usernametype TEXT,
		username TEXT,
		game TEXT,
		expires INT(1),
		expiretime TIMESTAMP DEFAULT '1971-01-01 00:00:00',
		endcommands TEXT,
		transactionid TEXT,
		package TEXT,
		packageid TEXT,
		paymentmethod TEXT,
		value DECIMAL(11,2),
		status TEXT,
		params TEXT);";
	$dbcon->exec($sql);
}

if(tableExists($dbcon, 'logs') === FALSE){
	$sql = "CREATE table logs(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		errortype TEXT,
		errorcode TEXT,
		error TEXT);";
	$dbcon->exec($sql);
}

if(tableExists($dbcon, 'settings') === FALSE){
	$sql = "CREATE table settings(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		setting TEXT,
		value TEXT);";
	$dbcon->exec($sql);
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('logoimgtype','text')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('logoimgcontent', 'Example')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('hometitle','Example Store')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('homeparagraph','Welcome to our donation store! Please choose a game below to see our packages.')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('thememain','#0BB5FF')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('themesecondary','#242424')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('themespinning','true')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('tos','<div>Enter your Terms of Service.</div>')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('maintenancemode','0')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('loginmode','0')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('paypalsandbox','1')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('currentversion','1.2.7')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('circleimages','1')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('maintheme', '0')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('mainfontcolor', '#000000')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('secondaryfontcolor', '#FFFFFF')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('themefont', 'Raleway')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('starpassenabled', '0')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('starpasscode', '')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('paypalenabled', '1')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('paymentmode', 'directpurchase')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('paypalemail', '')");
	$dbcon->query("INSERT INTO settings(setting, value) VALUES('creditsenabled', '0')");
}

if(tableExists($dbcon, 'steamids') === FALSE){
	$sql = "CREATE table steamids(
		id INT(11) AUTO_INCREMENT PRIMARY KEY,
		steamid TEXT,
		username TEXT);";
	$dbcon->exec($sql);
}

echo "Installation Complete";

?>
