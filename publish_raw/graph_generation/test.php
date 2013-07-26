<?php


if( file_exists( "../production.i_am" ) ){
	echo "production";
}
else if( file_exists( "../staging.i_am" ) ){
	echo "staging";
}
else{
	echo "none";
}
?>