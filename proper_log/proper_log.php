<?php
/*
Plugin Name: Proper Log
Plugin URI: http://www.swarthmore.edu/its
Description: Creates a real file based event log. Requires "Activity Log" @ https://wordpress.org/plugins/aryo-activity-log - Created out of Les Leach's frustration of WordPress' lack of logging capabilities
*/

add_action("aal_insert_log", "proper_log", 5, 1);

function proper_log($args){
	 $site_name = preg_replace('/^https?:\/\/(.+)$/', "$1", get_site_url());
	 $log = array();
	 $log[] = $site_name;
	 $log[] = $args['hist_ip'];
	 $uobj = get_user_by ('ID', $args['user_id']);
	 $log[] =  $args['user_id'] . (($uobj) ? " (" . $uobj->data->user_login . " - " . implode(", ", $uobj->roles) . ")" : "");
	 $log[] = $args['object_type'];
	 $log[] = $args['action'];
	 $log[] = $args['object_name'] .((empty($args['object_id'])) ? "" : " (ID: " . $args['object_id'] . ")");
	 @openlog("WordPress", LOG_NDELAY, LOG_LOCAL0); 
	 @syslog(LOG_INFO, implode(" - ", $log) . "\n");
	 @closelog();
}