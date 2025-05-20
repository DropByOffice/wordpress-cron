# WordPress Unified Cron Trigger (`zunified_cron.php`)

This script provides a secure, unified way to run WordPress cron jobs across **both single-site and multisite installs**.

Designed for **Cloudways cron**, **external pinging services**, or **direct browser access**, it detects your WP installation type, triggers due cron events via `wp-cron.php`, and logs the entire process.

---

## ✅ Features

- Detects **single vs multisite** automatically
- Supports **Cloudways cron jobs** or browser triggers
- Accepts **secure static key** (`cr0nx99`)
- Logs to `zcron.log` (activity) and `zcron-warnings.log` (suppressed PHP output)
- Captures and optionally displays WordPress warnings
- Fully Cloudways-safe (no `wp-cli`, `Phar`, or shell dependencies)

---
for a single install site... 
its simple..

--- 
!! make sure permissions are 755 , and the cloudway server number, and directory are updated..
--- 
## 🔐 Security

Access to the script is protected by a shared static key.

- Set in the script as:  
  ```php
  $expected_key = 'cr0nx99';


  sample in advacnced cron

*/3 * * * * cd /home/1465138.cloudwaysapps.com/egqkgjswuk/public_html && /usr/bin/php zunified_cron.php cr0nx99 5

3,33 * * * * cd /home/1465138.cloudwaysapps.com/kudyrguumr/public_html && /usr/bin/php zunified_cron.php cr0nx99 5
  sample URL line
  https://trapsdirect.com/zunified_cron.php?key=cr0nx99&sleep=5

  ctoday - do NYC consolidate 

