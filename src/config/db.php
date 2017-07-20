<?php
/**
* Database Connection
*/
class db
{
	private $dbhost = 'localhost';
	private $dbuser = 'root';
	private $dbpass = '123456';
	private $dbname = 'slimapp';

	public function connect()
	{
		$mysql_connect_str = "mysql:host=$this->dbhost;dbname=$this->dbname";
		$dbconnection = new PDO($mysql_connect_str, $this->dbuser, $this->dbpass);
		$dbconnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$dbconnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		return $dbconnection;
	}
}