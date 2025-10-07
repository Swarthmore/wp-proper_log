# wp-proper_log
## WordPress System Logger

### Prerequisites
- This plugin requires the WordPress [Activity Log Plugin](https://wordpress.org/plugins/aryo-activity-log/) to capture WordPress events.

### Features
- Send activity log events to either:
  - **`syslog`** (default), or
  - **`STDOUT`** / `php://stdout` (useful for containerized deployments where container logs are collected).
- Configurable log ident/tag (default: `ProperLog`) used as the `syslog` ident and prepended to `STDOUT` messages.

### Installation
1. Install and activate the **Activity Log** plugin.
2. Copy this plugin into your `wp-content/plugins/` directory and activate it.
3. Configure destination and tag at **Settings → Proper Log**.

### Defaults
- Destination: `syslog`
- Log tag/ident: `ProperLog`

These defaults are created on plugin activation if the options are not present.

### Rsyslog Configuration
(optional) To send WordPress events to their own system log.
On a system running `rsyslog`, update `/etc/rsyslog.conf` to contain the following in the **RULES** portion of the config.
```
# Logging for WordPress applications
local0.*                                                /var/log/wp_event_log
```
Restart `rsyslog` to apply the configuration change.

