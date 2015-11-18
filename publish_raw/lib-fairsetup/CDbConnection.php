<?php

class CDBConnection
{
	public $conn;

	// When the object is loaded, make sure it has a connection
	function __construct(){
		//$serverName = "w0.jove.com";		
		// $connectionInfo = array("Database" => "JoVE", "UID" => "JoVEProduction", "PWD" => "WhatAREyou1999222" );  // live
		// $connectionInfo = array("Database" => "JoVETest", "UID" => "chris.macdonald", "PWD" => "Bacon27" );

		// local access on the production server
		if( file_exists( "../production.i_am" ) ){
			$serverName 		= "CUCURBITA";
			$connectionInfo 	= array("Database" => "FairSetup", "UID" => "FairSetup_App", "PWD" => "alk4wfr43w%32fWW" );  // For local connections
		}
		else if( file_exists( "../staging.i_am" ) ){
			$serverName 		= "WIN-CUCURBITA";
			$connectionInfo 	= array("Database" => "FairSetup_Staging", "UID" => "FairSetup_App", "PWD" => "alk4wfr43w%32fWW" );  // For local connections
		}
		else{
			$serverName 		= "X200-NIKITA";
			//$connectionInfo 	= array("Database" => "FairSetup_2014_06_13_dev" );  // For local connections
			$connectionInfo 	= array("Database" => "FairSetup_2015_10_17" );  // For local connections
		}
		$this->conn = sqlsrv_connect( $serverName, $connectionInfo );
		if( $this->conn === false )
		{
			 echo "Could not connect.\n";
			 die( print_r( sqlsrv_errors(), true));
		}	
	}
	
	// When object is unloaded, make sure to clean up the connection
	function __destruct(){
		if( isset( $this->conn ) ){
			if ($this->conn != false )
				sqlsrv_close( $this->conn );
			unset( $this->conn );
		}	
	}

	// Gets a single numeric value from the SQL statement
	function getValueFromDB( $sql, $params = Array() )
	{
		$stmt = sqlsrv_query( $this->conn, $sql, $params );
		$ret = -1;
		if( $stmt === false )
		{
			 echo "Error in statement preparation/execution.\n";
			 die( print_r( sqlsrv_errors(), true));
		}
		if( sqlsrv_fetch( $stmt ) ){
			$ret = sqlsrv_get_field( $stmt, 0 );
		}
		sqlsrv_free_stmt( $stmt);
		return $ret;
	}
	
	// This can be used to execute individual statements where response is not expected
	function runSQL( $sql, $params = Array() )
	{
		$stmt = sqlsrv_query( $this->conn, $sql, $params );
		$ret = 0;
		if( $stmt === false )
		{
			 echo "Error in statement preparation/execution.\n";
			 die( print_r( sqlsrv_errors(), true));
		}
		$ret = 1;
		sqlsrv_free_stmt( $stmt);
		return $ret;
	}
	
}
?>