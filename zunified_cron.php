<?php
/**
 * zunified_cron.php
 *
 * ✅ Unified cron trigger for WordPress (single or multisite)
 * ✅ Use in browser:  ?key=cr0nx99&sleep=5
 * ✅ Use in CLI cron: php zunified_cron.php cr0nx99 5
 * ✅ Logs: zcron.log and zcron-warnings.log
 * ✅ Safe for Cloudways, browser, and external pingers
 */

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

// -------------------- CONFIG --------------------
$log_file = __DIR__ . '/zcron.log';
$warnings_file = __DIR__ . '/zcron-warnings.log';
$expected_key = 'cr0nx99';  // Static shared key

// -------------------- INPUT HANDLING --------------------
$key = $_GET['key'] ?? ($_SERVER['argv'][1] ?? '');
$sleep = isset($_GET['sleep']) && is_numeric($_GET['sleep']) 
    ? (int)$_GET['sleep'] 
    : (isset($_SERVER['argv'][2]) && is_numeric($_SERVER['argv'][2]) ? (int)$_SERVER['argv'][2] : 2);
$debug = isset($_GET['debug']);
$now_utc = gmdate('Y-m-d H:i:s') . ' UTC';
$remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

// -------------------- LOGGING --------------------
function log_line($msg, $also_echo = true) {
    global $log_file;
    $ts = gmdate('[Y-m-d H:i:s UTC]');
    $line = "$ts $msg";
    file_put_contents($log_file, $line . "\n", FILE_APPEND);
    if ($also_echo) echo htmlentities($line) . "<br>\n";
}

// -------------------- ACCESS CONTROL --------------------
if ($key !== $expected_key) {
    log_line("❌ BLOCKED: Invalid key ($key) from $remote_ip");
    http_response_code(403);
    echo "Access denied. Check zcron.log for correct key.\n";
    exit;
}

log_line("✅ Cron access granted with key [$key] from [$remote_ip]");
log_line("Cron start at $now_utc with sleep delay: $sleep seconds");

// -------------------- LOAD WORDPRESS + CAPTURE WARNINGS --------------------
ob_start();
require_once __DIR__ . '/wp-load.php';
$wp_warnings = trim(ob_get_clean());
global $wpdb;

if (!empty($wp_warnings)) {
    file_put_contents($warnings_file, "[" . gmdate('Y-m-d H:i:s') . "]\n$wp_warnings\n\n", FILE_APPEND);
    if ($debug) {
        echo "<hr><strong>⚠️ Debug Output:</strong><br>\n";
        echo "<pre>" . htmlentities($wp_warnings) . "</pre>";
    } else {
        log_line("⚠️ Suppressed warnings captured. Re-run with ?debug=1 to view.");
    }
}

// -------------------- CRON LOGIC --------------------
if (defined('MULTISITE') && MULTISITE) {
    log_line("Detected multisite environment.");

    $sites = $wpdb->get_results("SELECT domain, path FROM {$wpdb->blogs} WHERE archived = '0' AND deleted = '0'");
    foreach ($sites as $site) {
        $url = 'https://' . $site->domain . rtrim($site->path, '/');
        log_line("[$url] ➡️ Triggering...");
        @file_get_contents("$url/wp-cron.php?doing_wp_cron");
        log_line("[$url] ✅ Done");
        sleep($sleep);
    }

    log_line("Multisite cron completed.");
} else {
    $url = get_option('siteurl');
    log_line("Detected single-site: [$url]");
    @file_get_contents("$url/wp-cron.php?doing_wp_cron");
    log_line("[$url] ✅ Single-site cron executed.");
}

log_line("=== Cron run completed at $now_utc ===");