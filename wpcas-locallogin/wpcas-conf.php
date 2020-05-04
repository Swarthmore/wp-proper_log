<?php
/*
// Optional configuration file for wpCAS plugin
// 
// Settings in this file override any options set in the 
// wpCAS menu in Options and that menu will not be displayed
*/


// the configuration array
$wpcas_options = array(
	'cas_version' => '2.0',
	'include_path' => 'CAS/CAS.php',
	'server_hostname' => 'sid.swarthmore.edu',
	'server_port' => '443',
	'server_path' => '/idp/profile/cas/',
	'allow_local_login' => TRUE
	);

// this function gets executed 
// if the CAS username doesn't match a username in WordPress
function wpcas_nowpuser( $user_name ){
	die('you do not have permission here');
}

?>
