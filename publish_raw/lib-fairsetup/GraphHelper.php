<?php
/* how to use

$graph_data = new CGraphHelper();
$user = new CUser( "Nikita", 1.2 );
$graph_data->addUser( $user );

*/
include_once '../lib-fairsetup/DBConnection.php';

class CObject{}
class CNameValue{}
class CCol{}

class CGraphHelper{
	public $users;
	
	function __construct()
	{
		$this->users = array();
	}
	
	function addUser( $user )
	{
		array_push( $this->users, $user );	
	}
	
	function getForGoogle()
	{
		$out = new CObject();

		$col1 = new CCol();
		$col1->id = "";
		$col1->label = "Users";
		$col1->pattern = "";
		$col1->type = "string";

		$col2 = new CCol();
		$col2->id = "";
		$col2->label = "Impact";
		$col2->pattern = "";
		$col2->type = "number";

		$out->cols = array( $col1, $col2 );
		$out->rows = array();
		foreach( $this->users as $user ){
			array_push( $out->rows, $user->getForGoogle() );
		}
		return $out;
	}

	function getStateForHighcharts()
	{
		$out = array();

		foreach( $this->users as $user ){
			array_push( $out, $user->getStateHighchart() );
		}
		return $out;	
	}

	function getHistoryForHighcharts()
	{
		$out = array();

		foreach( $this->users as $user ){
			array_push( $out, $user->getHistoryStateHighchart() );
		}
		return $out;	
	}
}
class CUser{
	public $name;
	public $impact;
	public $user_id;
	public $company_id;
	public $history;
	
	function __construct( $user_id, $company_id, $name, $impact = NULL )
	{
		$this->user_id = $user_id;
		$this->company_id = $company_id;
		$this->name = $name;
		$this->impact = $impact;
	}
	function getForGoogle()
	{
		$user_info = new CNameValue;
		$user_info->v = $this->name;
		$user_info->f = null;

		$impact_info = new CNameValue;
		$impact_info->v = $this->impact;
		$impact_info->f = null;

		$user_obj = new CObject;
		$user_obj->c = array( $user_info, $impact_info );
		return $user_obj;
	}

	function getStateHighchart()
	{
		$obj = new CObject();
		$obj->name = $this->name;
		$obj->y = $this->impact;
		$obj->id = $this->user_id;
//		$obj->color = 'red';
		return $obj;
	}

/*
[{ 	name: 'Nikita', 
															pointStart: Date.UTC(2010, 0, 1),
															pointInterval: 3600 * 1000 * 24, // one hour
															data: [1, 2, 1, 3, 4, 5, 6, 9, 1, 2, 4] },
*/

	function getHistoryStateHighchart()
	{
		// NOT READY TO YET
		global $db_conn;
		$sql = "select EventLevel, EventTime from user_level_cache where UserID = ? and CompanyID = ? ORDER BY EventTime ASC";
		$stmt = sqlsrv_query( $db_conn->conn, $sql, Array( $this->user_id, $this->company_id ) );

		if( $stmt === false )
		{
			 echo "Error in statement preparation/execution.\n";
			 die( print_r( sqlsrv_errors(), true));
		}

		$obj = new CObject();
		$obj->name = $this->name;
		$obj->pointStart = '0'; // so that it shows before data
		$obj->pointInterval = 3600 * 1000 * 24;
		$obj->data = array();
		$obj->zIndex = 1;

		$values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH );
		if( $values ){
			// Round the time to the nearest day
			$start_date = round( $values[1]->getTimestamp() / 3600 / 24, 0 ) * 3600 * 24 * 1000;
			$obj->pointStart = $start_date;
			do{
				array_push( $obj->data, round( $values[0], 3 ) );
			}
			while( $values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH ) );
		}

		

		sqlsrv_free_stmt( $stmt );	
		return $obj;
	}
}


?>