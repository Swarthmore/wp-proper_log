<?php
/*
Plugin Name: Proper Log
Plugin URI: http://www.swarthmore.edu/its
Description: Creates a real file based event log. Requires "Activity Log" @ https://wordpress.org/plugins/aryo-activity-log - Created out of Les Leach's frustration of WordPress' lack of logging capabilities
*/

add_action("aal_insert_log", "proper_log", 5, 1);

// Ensure a default option is created on activation
register_activation_hook(__FILE__, 'proper_log_activate');
function proper_log_activate() {
    if (false === get_option('proper_log_destination')) {
        add_option('proper_log_destination', 'syslog');
    }
}

/* --- Settings UI --- */
add_action('admin_menu', 'proper_log_admin_menu');
add_action('admin_init', 'proper_log_settings_init');

function proper_log_admin_menu() {
    if (current_user_can('manage_options')) {
        add_options_page(
            'Proper Log',
            'Proper Log',
            'manage_options',
            'proper-log',
            'proper_log_options_page'
        );
    }
}

function proper_log_settings_init() {
    register_setting('proper_log', 'proper_log_destination', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'syslog',
    ));

    add_settings_section(
        'proper_log_main_section',
        'Logging Destination',
        function(){ echo '<p>Choose where to send logs.</p>'; },
        'proper_log'
    );

    add_settings_field(
        'proper_log_destination_field',
        'Destination',
        'proper_log_destination_field_render',
        'proper_log',
        'proper_log_main_section'
    );
}

function proper_log_destination_field_render() {
    $val = get_option('proper_log_destination', 'syslog');
    ?>
    <label><input type="radio" name="proper_log_destination" value="syslog" <?php checked($val, 'syslog'); ?>> Syslog</label><br>
    <label><input type="radio" name="proper_log_destination" value="stdout" <?php checked($val, 'stdout'); ?>> STDOUT (php://stdout)</label>
    <?php
}

function proper_log_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Proper Log</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('proper_log');
            do_settings_sections('proper_log');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/* --- Logging --- */
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
    $message = implode(" - ", $log) . "\n";

    $dest = get_option('proper_log_destination', 'syslog');
    $tag = 'ProperLog';

    if ($dest === 'stdout') {
        // Try STDOUT constant first (CLI), otherwise use php://stdout
        $out_msg = $tag . ': ' . $message;
      if (defined('STDOUT')) {
          @fwrite(STDOUT, $out_msg);
      } else {
          $out = @fopen('php://stdout', 'w');
          if ($out) {
              @fwrite($out, $out_msg);
              @fclose($out);
          }
      }
    } else {
        @openlog($tag, LOG_NDELAY, LOG_LOCAL0);
        @syslog(LOG_INFO, $message);
        @closelog();
    }
}