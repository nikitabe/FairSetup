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

$every_x = 1;

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
        global $every_x;
        $every_x = 5;
		$out = array();

		foreach( $this->users as $user ){
			array_push( $out, $user->getStateHighchart() );
		}
		return $out;	
	}

	function getHistoryForHighcharts()
	{
        global $every_x;
        $every_x = 5;

		$out = array();

		foreach( $this->users as $user ){
			$v = $user->getHistoryStateHighchart();
			array_push( $out, $v[0] );
		}
		return $out;	
	}
}

class C_HSDisplaySeries{
	function __construct( $name, $start_date = 0, $zIndex = 1, $color = "gray", $type = "line")
	{
        global $every_x;
        
		$this->name = $name;
		$this->color = $color;
		$this->zIndex = $zIndex;
		$this->pointStart = '0'; // so that it shows before data
		$this->pointInterval = 3600 * 1000 * 24 * $every_x;
		$this->data = array();
		$this->type = $type;
		$this->pointStart = $start_date;
	}
}


class CUser{
	public $name;
	public $impact;
	public $user_id;
	public $company_id;
	public $history;
	
	function __construct( $user_id, $company_id, $name = NULL, $impact = NULL )
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

	function getHistoryStateHighchart( $individual_user = false, $net_value = true )
	{
		// NOT READY TO YET
		global $db_conn;
        global $every_x;

		//$sql = "select EventLevel, EventTime from user_level_cache where UserID = ? and CompanyID = ? ORDER BY EventTime ASC";
		//$sql = "select * from (select Impact_Net, EventTime, Impact_Potential, Impact_Actual, Level, Throttle, Performance, ROW_NUMBER() over (order by EventTime ASC) as rownum from user_impact_cache where UserID = ? and CompanyID = ? ORDER BY EventTime ASC) t where t.rownum %10 = 0 order by t.rownum";
		$sql = "select * from (select Impact_Net, EventTime, Impact_Potential, Impact_Actual, Level, Throttle, Performance, ROW_NUMBER() over (order by EventTime ASC) as rownum from user_impact_cache where UserID = ? and CompanyID = ? ) t where t.rownum % " . $every_x . " = 0 order by t.rownum";
		$stmt = sqlsrv_query( $db_conn->conn, $sql, Array( $this->user_id, $this->company_id ) );
		
		if( $stmt === false )
		{
			 echo "Error in statement preparation/execution.\n";
			 die( print_r( sqlsrv_errors(), true));
		}

		$obj_ret = array();
		if( $net_value ){
			$obj = new CObject();
			if( $individual_user )
				$obj->name = "Net Impact";
			else
				$obj->name = $this->name;
			$obj->pointStart = '0'; // so that it shows before data
			$obj->pointInterval = 3600 * 1000 * 24 * $every_x;
			$obj->data = array();
			$obj->zIndex = 1; //$this->user_id;

			$values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH );
			if( $values ){
				// Round the time to the nearest day
				$start_date = strtotime( date( 'y-m-d', $values[1]->getTimestamp() ) ) * 1000;

				$obj->pointStart = $start_date;
				do{
					array_push( $obj->data, round( $values[0], 3 ) );
					// array_push( $obj->data, date( 'y-m-d', $values[1]->getTimestamp()) );
				}
				while( $values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH ) );
			}
			array_push( $obj_ret, $obj );
		}
		else{

			$values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH );

			if( $values ){
				$start_date = round( $values[1]->getTimestamp() / 3600 / 24, 0 ) * 3600 * 24 * 1000;
			
				$obj_potential 	= new C_HSDisplaySeries( "Impact Potential", 					$start_date, 	6, 'rgba(50,50,50,1)');			
				$obj_actual 	= new C_HSDisplaySeries( "Actual Impact", 						$start_date, 	1, 'rgba(0,200,0,1)', "area" );
				$obj_l 			= new C_HSDisplaySeries( "Level", 								$start_date, 	3, 'rgba(100,100,100,0.7)' );		
				$obj_lt 		= new C_HSDisplaySeries( "Throttled Level", 					$start_date, 	4, 'rgba(0,255,000,0.7)' );	
				$obj_lp 		= new C_HSDisplaySeries( "Level with Performance", 				$start_date,	5, 'rgba(255,000,0,0.7)' );	
				$obj_ltp 		= new C_HSDisplaySeries( "Throttled Level with Performance", 	$start_date,	6, 'rgba(200,0,0,0.4)' );	
				
				$obj_actual->fillOpacity = 0.2;
				$obj_l->visible = false;
				$obj_lt->visible = false;
				$obj_lp->visible = false;
				$obj_ltp->visible = false;
			 //$this->user_id;

				// Round the time to the nearest day
				do{
					array_push( $obj_potential->data, round( $values[2], 3 ) );
					array_push( $obj_actual->data, round( $values[3], 3 ) );

					array_push( $obj_l->data, round( $values[4], 3 ) );
					array_push( $obj_lt->data, round( $values[4] * $values[5], 3 ) );
					array_push( $obj_lp->data, round( $values[4] * $values[6], 3 ) );
					
					$v = round( $values[4] * $values[5] * $values[6], 3 );
					array_push( $obj_ltp->data, $v );

				}
				while( $values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH ) );

				array_push( $obj_ret, $obj_l );
				array_push( $obj_ret, $obj_lt );
				array_push( $obj_ret, $obj_lp );
				array_push( $obj_ret, $obj_ltp );
				array_push( $obj_ret, $obj_potential );
				array_push( $obj_ret, $obj_actual );

			}
			
		}
		

		sqlsrv_free_stmt( $stmt );	
		return $obj_ret;
	}
}


?>