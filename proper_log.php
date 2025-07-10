<?php
/*
Plugin Name: Proper Log
Plugin URI: http://www.swarthmore.edu/its
Description: Creates a real file based event log. Requires "Activity Log" @ https://wordpress.org/plugins/aryo-activity-log - Created out of Les Leach's frustration of WordPress' lack of logging capabilities
Version: 1.2
*/

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

add_action("aal_insert_log", "proper_log", 5, 1);

define("WP_LOG_PREFIX", "WordPress");

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
	$message = implode(" - ", $log);
	system_log($message);
	monolog_local($message);
}

function system_log($message) {
	@openlog(WP_LOG_PREFIX, LOG_NDELAY, LOG_LOCAL0); 
	@syslog(LOG_INFO, $message . "\n");
	@closelog();
}

function monolog_local($message) {
	$target_dir = WP_CONTENT_DIR . '/wp_event_log';
	if ( ! file_exists( $target_dir ) ) {
    wp_mkdir_p( $target_dir );
	}
	// the default date format is "Y-m-d\TH:i:sP"
	$dateFormat = "M n H:i:s";

	// get the hostname
	$hostname = preg_replace("/^([^\.]+).*$/", "$1", php_uname("n"));

	// the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
	// we now change the default output format according to our needs.
	$output = "%datetime% $hostname " . WP_LOG_PREFIX . ": %message%\n";

	// finally, create a formatter
	$formatter = new LineFormatter($output, $dateFormat);	

	// Create a logger instance
	$log = new Logger('wp_event_log');

	// Create a rotating file handler (daily rotation, keep 3 files)
  $handler = new RotatingFileHandler($target_dir . '/wp_event.log', 3, Logger::DEBUG);
	$handler->setFormatter($formatter);
	
	// Attach the handler
	$log->pushHandler($handler);

	//Log the message
	$log->info($message);
}