<?php
/*
 * Plugin Name: Proper Log
 * Plugin URI: http://www.swarthmore.edu/its
 * Description: Creates a real file based event log. Requires "Activity Log" @ https://wordpress.org/plugins/aryo-activity-log - Created out of Les Leach's frustration of WordPress' lack of logging capabilities
 */

add_action("aal_insert_log", "proper_log", 5, 1);

// Default plugin settings
register_activation_hook(__FILE__, 'proper_log_activate');
function proper_log_activate() {
    // Ensure a default logging destination exists
    if (false === get_option('proper_log_destination')) {
        add_option('proper_log_destination', 'syslog');
    }
    // Ensure a default logging tag exists
    if (false === get_option('proper_log_tag')) {
        add_option('proper_log_tag', 'ProperLog');
    }
}

// Settings UI
add_action('admin_menu', 'proper_log_admin_menu');
add_action('admin_init', 'proper_log_settings_init');

// Add a "Settings" link on the Dashboard > Plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'proper_log_plugin_action_links');
function proper_log_plugin_action_links($links) {
    $settings_link = '<a href="options-general.php?page=proper-log">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Admin menu for plugin settings
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

// Settings initialization
function proper_log_settings_init() {
    register_setting('proper_log', 'proper_log_destination', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'syslog',
    ));
    register_setting('proper_log', 'proper_log_tag', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'ProperLog',
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
    add_settings_field(
        'proper_log_tag_field',
        'Log tag',
        'proper_log_tag_field_render',
        'proper_log',
        'proper_log_main_section'
    );
}

// Settings field renderers
function proper_log_tag_field_render() {
    $val = get_option('proper_log_tag', 'ProperLog');
    ?>
    <input type="text" name="proper_log_tag" value="<?php echo esc_attr($val); ?>" class="regular-text" />
    <p class="description">Identifier prepended to stdout messages and used as the syslog ident (default: ProperLog).</p>
    <?php
}

function proper_log_destination_field_render() {
    $val = get_option('proper_log_destination', 'syslog');
    ?>
    <label><input type="radio" name="proper_log_destination" value="syslog" <?php checked($val, 'syslog'); ?>> Syslog</label><br>
    <label><input type="radio" name="proper_log_destination" value="stdout" <?php checked($val, 'stdout'); ?>> STDOUT (php://stdout)</label>
    <?php
}

// Options page
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

// Logging function
function proper_log($args){
    // Prefer WP helper to parse the site URL host
    $site_host = wp_parse_url(get_site_url(), PHP_URL_HOST);
    if (empty($site_host)) {
        // fallback to regex if parsing fails
        $site_host = preg_replace('/^https?:\/\/(.+)$/', '$1', get_site_url());
    }
    $site_name = $site_host;

    // Validate presence of expected keys to avoid notices
    $hist_ip     = isset($args['hist_ip'])     ? $args['hist_ip']     : '';
    $user_id     = isset($args['user_id'])     ? intval($args['user_id']) : 0;
    $object_type = isset($args['object_type']) ? $args['object_type'] : '';
    $action      = isset($args['action'])      ? $args['action']      : '';
    $object_name = isset($args['object_name']) ? $args['object_name'] : '';
    $object_id   = isset($args['object_id'])   ? $args['object_id']   : '';

    $log = array();
    $log[] = $site_name;
    $log[] = $hist_ip;

    // Build user info safely
    $user_info = '';
    if ($user_id) {
        $uobj = get_user_by('ID', $user_id);
        $user_info = (string)$user_id;
        if ($uobj) {
            $login = isset($uobj->data->user_login) ? $uobj->data->user_login : '';
            $roles = isset($uobj->roles) ? implode(', ', $uobj->roles) : '';
            $user_info .= ($login || $roles) ? " ({$login}" . ($roles ? " - {$roles}" : '') . ")" : '';
        }
    }
    $log[] = $user_info;
    $log[] = $object_type;
    $log[] = $action;
    $log[] = $object_name . ((string) $object_id === '' ? '' : " (ID: " . $object_id . ")");

    // Remove empty segments so messages are cleaner
    $message = implode(" - ", array_filter($log, function($v){ return $v !== '' && $v !== null; })) . "\n";

    $dest = get_option('proper_log_destination', 'syslog');
    $tag = get_option('proper_log_tag', 'ProperLog');

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