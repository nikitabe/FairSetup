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

define("DISPLAY_NET_VALUE",  "0");
define("DISPLAY_IMPACT",     "1");
define("DISPLAY_BEHAVIOR",   "2");

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
		$this->step = 'center';
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

	function getEventHistory()
	{
	}

	// Returns the first day along with the number of days
	function GetDateInfo()
	{
		global $db_conn;

		$sql = "select MIN( EventDate ), DateDiff( d, MIN( EventDate ), MAX( EventDate ) ) 
					from user_events
						 where ((PLevelID IS NOT NULL AND PLevelID > -1) OR (money_transfer IS NOT NULL) OR (Impact_onetime IS NOT NULL)) and UserID = ? and CompanyID = ?  
					";

		$stmt = sqlsrv_query( $db_conn->conn, $sql, Array( $this->user_id, $this->company_id ) );
		if( $stmt === false )
		{
			 echo "Error in statement preparation/execution.\n";
			 die( print_r( sqlsrv_errors(), true));
		}

		$values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH );
		sqlsrv_free_stmt( $stmt );	
		$ret_obj = new stdClass();
		if( $values[0] ){
			$ret_obj->start_date = date( 'Y-m-d', $values[0]->getTimestamp() );
			$ret_obj->num_days = $values[1];
		}
		return $ret_obj;
	}

	// individual_user - do not place users's name
	function getHistoryStateHighchart( $individual_user = false, $display_type = DISPLAY_NET_VALUE )
	{
		// NOT READY TO YET
		global $db_conn;
        global $every_x;


		//$sql = "select EventLevel, EventTime from user_level_cache where UserID = ? and CompanyID = ? ORDER BY EventTime ASC";
		//$sql = "select * from (select Impact_Net, EventTime, Impact_Potential, Impact_Actual, Level, Throttle, Performance, ROW_NUMBER() over (order by EventTime ASC) as rownum from user_impact_cache where UserID = ? and CompanyID = ? ORDER BY EventTime ASC) t where t.rownum %10 = 0 order by t.rownum" ;

		$obj_ret = array();
		$date_info = $this->GetDateInfo();	

		$fields = array();

		$fields = 
			array(
			 "MAX( ISNULL( Impact_Net_Labor, 0) + ISNULL(Impact_Net_Capital,0) + ISNULL(Impact_Net_Onetime,0))",
			 "MAX( ISNULL( Impact_Net_Labor, 0) )",
			 "MAX( ISNULL( Impact_Net_Capital, 0) )",
			 "MAX( ISNULL( Impact_Net_OneTime, 0) )",
			 "MAX( Level )",
			 "MAX( Level_Potential )",
			 "MAX( Impact_flat )",
			 "MAX( Impact_flat * ISNULL( RiskMultiplier, 1)) as Impact_wRisk" ,
			 "MAX( Impact_Onetime_flat)" ,
			 "MAX( Impact_Onetime_flat * ISNULL( RiskMultiplier, 1)) as Impact_wRisk",
			 "MAX( TimeSpent )" ,
			 "MAX( TimeSpent / 40 )" ,
			 "MAX( PLevel_Backward )");

		$field_names = 
			array(
			// Name 									z-index  color 					   type     	continuous	 visibility
			 array("Net Impact"					        ,4,	"rgba(0,0,0,1)				", "line"	, 1			, 0 ), // 0
			 array("Net Impact (labor only)"	        ,3,	"rgba(0,200,0,1)			", "line"	, 1			, 0 ), // 1
			 array("Net Impact (cash only)"	        	,2,	"rgba(178,147,88,1)			", "line"	, 1			, 0 ), // 2
			 array("Net Impact (non-cash only)"	        ,1,	"rgba(255,127,0,1)			", "line"	, 1			, 0 ), // 3
			 array("Level"						        ,7,	"rgba(100,100,100,0.7)		", "line" 	, 1			, 0 ), // 4
			 array("Potential Level"			        ,4,	"rgba(100,100,100,0.7)		", "area"	, 1			, 0 ), // 5
			 array("Impact (flat)"				        ,5,	"rgba(0,150,0,0.7)			", "area"	, 0			, 0 ), // 6
			 array("Impact (risk-adjusted)"		        ,6,	"rgba(0,200,0,0.7)			", "area"	, 0			, 0 ), // 7
			 array("Onetime Impact (flat)"				,5,	"rgba(200,150,100,0.7)		", "area"	, 0			, 0 ), // 8
			 array("Onetime Impact (risk-adjusted)"		,6,	"rgba(200,200,100,0.7)		", "area"	, 0			, 0 ), // 9
			 array("Time Spent"							,8,	"rgba(150,150,150,0.7)		", "area"	, 0			, 0 ), // 10
			 array("Throttle"							,8,	"rgba(150,150,150,0.7)		", "area"	, 0			, 0 ), // 11
			 array("Performance"						,9,	"rgba(0,0,0,0.7)			", "line"	, 1			, 0 )  // 12
			 );

		if( $display_type == DISPLAY_NET_VALUE ){
			$field_names[0][5] = 1;
			$field_names[1][5] = 1;
			$field_names[2][5] = 1;
			$field_names[3][5] = 1;
		}
		else if( $display_type == DISPLAY_IMPACT ){
			$field_names[4][5] = 1;			
			$field_names[5][5] = 1;			
			$field_names[6][5] = 1;			
			$field_names[7][5] = 1;
			$field_names[8][5] = 1;
			$field_names[9][5] = 1;			
			$field_names[10][5] = 1;
		}
		else if( $display_type == DISPLAY_BEHAVIOR){
			$field_names[11][5] = 1;			
			$field_names[12][5] = 1;
		}

		// Get User Net Value
		if( !property_exists ( $date_info, 'start_date' ) ) return;

		$sql = "select 
			DateDiff( d, '" . $date_info->start_date ."', c.EventDate ),
			c.EventDate,
			[FIELDS]
						from user_events_cache c inner join user_events e on c.EventID = e.EventID
						where (PLevel_Backward IS NOT NULL OR e.money_transfer IS NOT NULL OR e.Impact_onetime IS NOT NULL) and c.UserID = ? and c.CompanyID = ?  
						group by c.EventDate order by c.EventDate ASC";

		$sql = str_replace( "[FIELDS]", implode( ",", $fields ), $sql );

		//$sql = "select Impact_Net, EventTime, Impact_Potential, Impact_Actual, Level, Throttle, Performance, ROW_NUMBER() over (order by EventTime ASC) as rownum from user_impact_cache where UserID = ? and CompanyID = ? ) t where cast( EventTime AS int )";
		$stmt = sqlsrv_query( $db_conn->conn, $sql, Array( $this->user_id, $this->company_id ) );
		if( $stmt === false )
		{
			 echo "Error in statement preparation/execution.\n";
			 die( print_r( sqlsrv_errors(), true));
		}


		$obj = new CObject();
		$obj->name = $this->name;

		$obj->start_date = strtotime( $date_info->start_date ) * 1000; // so that it shows before data

		$obj->num_days = $date_info->num_days;
		$obj->data = array();
		$obj->field_names = $field_names;

		$values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH );

		while( $values ){

			$val_array = array();
			
			for( $i=0; $i < count( $fields ) + 2; $i++ ){
				array_push( $val_array, $values[$i] );
			}
			
			array_push( $obj->data, $val_array );
			$values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH );
		}
		array_push( $obj_ret, $obj );
		sqlsrv_free_stmt( $stmt );	

		return $obj_ret;
	}
}


?>