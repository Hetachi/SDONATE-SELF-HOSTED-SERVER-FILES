<?php

if(!class_exists("User"))
{
	class User
	{

	    function __construct()
	    {
	        require(dirname(__FILE__) . "/../sessionname.php");
	    }

	    function IsLoggedIn()
	    {
	        if (isset($_SESSION['username']))
	        {
	            return true;
	        }
	        return false;
	    }

	    function IsAdmin()
	    {
	        if ($this->IsLoggedIn())
	        {
	            if (isset($_SESSION['admin']))
	            {
	                if ($_SESSION['admin'] === true)
	                {
	                    return true;
	                }
	                return false;
	            }
	            return false;
	        }
	        return false;
	    }

	}
}
