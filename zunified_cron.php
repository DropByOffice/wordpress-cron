<?php
/**
 * zunified_cron.php — upgraded unified WordPress cron trigger
 * ✅ Single + Multisite detection
 * ✅ Supports CLI (Cloudways cron) and browser trigger
 * ✅ Captures wp-cron output and shows known job triggers (e.g., AutomateWoo)
 * ✅ Logs to day-based zcron-[DAY].log and warnings to zcron-warnings.log
 * ✅ Safe to drop into multiple sites (shared architecture ready)
 * @version 1.0.9
 */

define('ZUNIFIED_VERSION', '1.0.9');

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

// ================= CONFIG ====================
$day         = strtolower(gmdate('D'));
$log_file    = __DIR__ . "/zcron-$day.log";
$warnings_file = __DIR__ . '/zcron-warnings.log';
$expected_key  = 'cr0nx99';  // Shared access key
$known_hooks   = ['automatewoo', 'subscription', 'wc_'];

// ================ INPUT HANDLING =============
$key    = $_GET['key'] ?? ($_SERVER['argv'][1] ?? '');
$sleep  = isset($_GET['sleep']) && is_numeric($_GET['sleep'])
          ? (int)$_GET['sleep']
          : (isset($_SERVER['argv'][2]) && is_numeric($_SERVER['argv'][2]) ? (int)$_SERVER['argv'][2] : 0);
$debug  = isset($_GET['debug']);
$quiet  = isset($_GET['quiet']);
$now_utc    = gmdate('Y-m-d H:i:s') . ' UTC';
$remote_ip  = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

// ================= LOGGING ===================
function log_line($msg, $also_echo = true) {
    global $log_file, $quiet;
    $ts   = gmdate('[Y-m-d H:i:s UTC]');
    $line = "$ts $msg";
    file_put_contents($log_file, $line . "\n", FILE_APPEND);
    if ($also_echo && !$quiet) echo htmlentities($line) . "<br>\n";
    @ob_flush(); flush();
}

// =============== ACCESS CONTROL =============
if ($key !== $expected_key) {
    log_line("❌ Access denied from [$remote_ip] using key [$key]", true);
    http_response_code(403);
    exit("Access Denied");
}

log_line("✅ Cron access granted with key [$key] from [$remote_ip]");
log_line("Cron start at $now_utc with sleep delay: $sleep seconds");

// ============== CAPTURE WARNINGS =============
ob_start();
require_once __DIR__ . '/wp-load.php';
$wp_warnings = trim(ob_get_clean());
global $wpdb;

if (!empty($wp_warnings)) {
    file_put_contents($warnings_file, "[" . gmdate('Y-m-d H:i:s') . "]\n" . $wp_warnings . "\n\n", FILE_APPEND);
    if ($debug) {
        echo "<hr><strong>⚠️ Debug Output:</strong><br>\n";
        echo "<pre>" . htmlentities($wp_warnings) . "</pre>";
    } else {
        log_line("⚠️ Suppressed warnings captured. Re-run with &debug=1 to view.");
    }
}

// =============== CRON LOGIC =====================
function run_cron_and_log($url) {
    global $known_hooks;
    $response = @file_get_contents("$url/wp-cron.php?doing_wp_cron");
    $clean_response = trim($response);

    if ($clean_response) {
        log_line("[$url] ➔ Output: " . str_replace("\n", ' ', $clean_response));
        foreach ($known_hooks as $hook) {
            if (stripos($clean_response, $hook) !== false) {
                log_line("[$url] ✅ Matched hook: $hook");
            }
        }
    } else {
        log_line("[$url ✅ Silent cron run (no output)]");
    }
}

if (defined('MULTISITE') && MULTISITE) {
    log_line("🟢 Detected multisite environment.");
    $sites = $wpdb->get_results("SELECT domain, path FROM {$wpdb->blogs} WHERE archived = '0' AND deleted = '0'");
    foreach ($sites as $site) {
        $url = 'https://' . $site->domain . rtrim($site->path, '/');
        log_line("[$url] ➔ Triggering...");
        run_cron_and_log($url);
        sleep($sleep);
    }
    log_line("Multisite cron completed.");
} else {
    $url = get_option('siteurl');
    log_line("🟢 Detected single-site: [$url]");
    run_cron_and_log($url);
    log_line("[$url] ✅ Single-site cron executed.");
}

log_line("=== Cron run completed at $now_utc ===");

?>
