<?php
/*Connect to the local server using Windows Authentication and
specify the AdventureWorks database as the database in use. */

include_once "CDBConnection.php";

global $db_conn;  //"exec CheckSubscription ?", array('test', '@ret_val')
if( !isset( $db_conn ) )
	$db_conn = new CDBConnection();
?>