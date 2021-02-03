<?php

if (!class_exists("DataBase"))
{
	class DataBase
	{

	    public $dbcon;

	    function __construct()
	    {
	        require(dirname(__FILE__) . "/../config.php");

	        try
	        {
	    		$this->dbcon = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
	    		$this->dbcon->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    	}
	        catch(PDOException $e)
	        {
	    		echo 'MySQL Error:' . $e->getMessage();
	    	       exit();
	    	}
	    }

	    function Select($query, $values = [])
	    {
	        $sql = $this->dbcon->prepare($query);
	        $sql->execute($values);
	        $results = $sql->fetchAll(PDO::FETCH_ASSOC);
	        return $results;
	    }

		function SelectRow($query, $values = [])
	    {
	        $sql = $this->dbcon->prepare($query);
	        $sql->execute($values);
	        $result = $sql->fetch(PDO::FETCH_ASSOC);
	        return $result;
	    }

	    function Count($query, $values)
	    {
	        $sql = $this->dbcon->prepare($query);
	        $sql->execute($values);
	        $count = $sql->rowCount();
	        return $count;
	    }

	    function Query($query, $values = [])
	    {
	        $sql = $this->dbcon->prepare($query);
	        $sql->execute($values);
	    }

	}
}
