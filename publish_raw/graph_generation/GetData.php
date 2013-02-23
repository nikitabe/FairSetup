<?php

$company_id = -1;

$lc = array_change_key_case($_GET);

if( isset ($lc['c_id'])) $company_id 	= (int)$lc['c_id'];	
if( isset ($lc['date'])) $date_to_show 	= ($lc['date']);
if( isset ($lc['u_id'])) $user_id 		= (int)($lc['u_id']);

if( !is_numeric( $company_id ) || $company_id < 0 ){
	echo "No company with that id";
	exit;
}

if( isset( $user_id ) && (!is_numeric( $user_id ) || $user_id < 0 ) ){
	echo "No user with that id";
	exit;
}

include_once "../lib-fairsetup/GraphHelper.php";
include_once '../lib-fairsetup/DBConnection.php';

$graph_data = new CGraphHelper();

// This is a pie chart
if( isset ( $date_to_show ) ){
// Get company-related information
	// Reset to now...  for now...
	$date_to_show = date( "m/d/Y" );
	
	$sql = "select UserID , FullName, EventLevel from GetCompanyBreakdown( ?, ? ) order by EventLevel DESC";
	$stmt = sqlsrv_query( $db_conn->conn, $sql, Array( $company_id, $date_to_show ) );
	if( $stmt === false )
	{
		 echo "Error in statement preparation/execution.\n";
		 die( print_r( sqlsrv_errors(), true));
	}

	while( $values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH ) ){
		$user = new CUser( $values[0], $company_id, $values[1], $values[2] );
		$graph_data->addUser( $user );	
	}

	sqlsrv_free_stmt( $stmt );

	$string = json_encode(
		$graph_data->getStateForHighcharts()
	);

	echo $string;
}
elseif(isset ( $company_id) && !isset( $user_id ) ){
	$sql = "select UserId, NameInCompany from user_to_company where CompanyID = ?";
	$stmt = sqlsrv_query( $db_conn->conn, $sql, Array( $company_id, "1/1/2013" ) );
	if( $stmt === false )
	{
		 echo "Error in statement preparation/execution.\n";
		 die( print_r( sqlsrv_errors(), true));
	}

	while( $values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH ) ){
		$user = new CUser( $values[0], $company_id, $values[1] );
		$graph_data->addUser( $user );
	}

	sqlsrv_free_stmt( $stmt );
	
	$data = $graph_data->getHistoryForHighcharts();
	
	$string = json_encode( $data );

	echo $string;
}
// Get user-related information
elseif( isset ( $company_id) && isset( $user_id ) ){
	$user = new CUser( $user_id, $company_id );

	$data = array( $user->getHistoryStateHighchart( true ) );
	
	$string = json_encode( $data );

	echo $string;
	
}
else
{
	echo "Request incomplete";
}

?>