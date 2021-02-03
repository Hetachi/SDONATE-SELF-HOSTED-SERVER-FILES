<?php

if (!class_exists("Settings"))
{
	class Settings
	{

		static function Get($settingName)
		{
			require(dirname(__FILE__) . "/db.class.php");
			$db = new DataBase();
			$row = $db->SelectRow("SELECT value FROM settings WHERE setting=?", [$settingName]);
			return $row["value"];
		}

		static function Set($settingName, $value)
		{
			require(dirname(__FILE__) . "/db.class.php");
			$db = new DataBase();
			$values = [$value, $settingName];
			$db->Query("UPDATE settings SET value=? WHERE setting=?", $values);
		}

		static function Create($settingName, $value)
		{
			require(dirname(__FILE__) . "/db.class.php");
			$db = new DataBase();
			$values = [$settingName, $value];
			$db->Query("INSERT INTO settings(setting, value) VALUES(?, ?)", $values);
		}

		static function UpdateVersion($versionNo)
		{
			require(dirname(__FILE__) . "/db.class.php");
			$db = new DataBase();
			$values = [$versionNo];
			$db->Query("UPDATE settings SET value=? WHERE setting='currentversion'", $values);
			return $versionNo;
		}

	}
}
