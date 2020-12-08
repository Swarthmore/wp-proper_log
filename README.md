# wp-proper_log
## WordPress System Logger
### Prerequisites
- This plugin requires the WordPress [Activity Log Plugin](https://wordpress.org/plugins/aryo-activity-log/) to capture WordPress events.
### Rsyslog Configuration
(optional) To send WordPress events to their own system log.
On a system running rsyslog, update /etc/rsyslog.conf to contain the following in the **RULES** portion of the config.
```
# Logging for WordPress applications
local0.*                                                /var/log/wp_event_log
```
Restart rsyslog to apply the configuration change.

