<?php

$company_id = -1;

$lc = array_change_key_case($_GET);
$group_id = -1;
$pie_contents = "Net";

if( isset ($lc['c_id'])) $company_id 	= (int)$lc['c_id'];	
if( isset ($lc['date'])) $date_to_show 	= ($lc['date']);
if( isset ($lc['u_id'])) $user_id 		= (int)($lc['u_id']);
if( isset ($lc['group_id'])) $group_id 	= (int)($lc['group_id']);
if( isset ($lc['pie_contents'])) $pie_contents 	= $lc['pie_contents'];	

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

$display_type = DISPLAY_NET_VALUE;
if( isset ($lc['display_type']) ) $display_type = (int)($lc['display_type']);

$graph_data = new CGraphHelper();

// GET COMPANY STATE
if( isset ( $date_to_show ) ){
// Get company-related information
	// Reset to now...  for now...
	$date_to_show = date( "m/d/Y" );

	$ind = 2; $sort_order = "";
	if( $pie_contents == "Net" ) 		{	$ind = 2; $sort_order = " ORDER BY Impact_Net DESC";} 	 
	if( $pie_contents == "Capital" )	{	$ind = 3; $sort_order = " ORDER BY Impact_Net_Capital DESC";} 	 
	if( $pie_contents == "Labor" ) 		{	$ind = 4; $sort_order = " ORDER BY Impact_Net_Labor + Impact_Net_Onetime DESC";} 	 
	if( $pie_contents == "LaborNoRisk" ){	$ind = 5; $sort_order = " ORDER BY Impact_Net_Labor_NoRisk + Impact_Net_Onetime_NoRisk DESC";} 	 
	if( $pie_contents == "CapitalNoRisk" ){	$ind = 6; $sort_order = " ORDER BY Impact_Net_Capital_NoRisk DESC";} 	 
	
	$sql = "select UserID , FullName, Impact_Net, Impact_Net_Capital, 
				ISNULL( Impact_Net_Labor, 0 ) + ISNULL( Impact_Net_Onetime, 0), 
				ISNULL( Impact_Net_Labor_NoRisk, 0 ) + ISNULL( Impact_Net_Onetime_NoRisk, 0 ),  
				CASE WHEN Impact_Net_Capital_NoRisk > 0 THEN Impact_Net_Capital_NoRisk ELSE 0 END from GetCompanyBreakdown( ?, ? ) where (exclude = 0 OR exclude IS NULL)";

	if( $group_id > 0 )
		$sql .= " AND GroupID = " . $group_id; 
	$sql .= $sort_order;
	$stmt = sqlsrv_query( $db_conn->conn, $sql, Array( $company_id, $date_to_show ) );
	if( $stmt === false )
	{
		 echo "Error in statement preparation/execution.\n";
		 die( print_r( sqlsrv_errors(), true));
	}


	while( $values = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_BOTH ) ){
		if( $pie_contents == "Capital" AND $values[$ind] < 0 ) $values[$ind] = 0; // Do not take negative values into account
		$user = new CUser( $values[0], $company_id, $values[1], $values[$ind] );
		$graph_data->addUser( $user );	
	}

	sqlsrv_free_stmt( $stmt );

	$string = json_encode(
		$graph_data->getStateForHighcharts()
	);

	echo $string;
}
// COMPLETE COMPANY DATA
elseif(isset ( $company_id) && !isset( $user_id ) ){
	// Note that no_equity is not taken into account here.
	$sql = "select uc.UserId, NameInCompany from user_to_company uc left join user_to_group ug on uc.UserID = ug.UserID and uc.CompanyID = ug.CompanyID where (uc.exclude IS NULL or uc.exclude = 0) and uc.CompanyID = ?";
	$params = Array( $company_id );

	if( $group_id > 0 ){
		$params[1] = $group_id;
		$sql .= " and ug.GroupID = ?";
	}
		
	$stmt = sqlsrv_query( $db_conn->conn, $sql, $params );
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
// SINGLE USER
elseif( isset ( $company_id) && isset( $user_id ) ){
	$user = new CUser( $user_id, $company_id );

	$data = $user->getHistoryStateHighchart( true, $display_type );		
	
	$string = json_encode( $data );

	echo $string;
	
}
else
{
	echo "Request incomplete";
}

?>