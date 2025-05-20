<?php
/*
Plugin Name: ZUnified
Description: Manage and monitor zunified_cron.php from the admin dashboard.
Version: 1.0.0
*/

add_action('admin_menu', function() {
    add_menu_page('ZUnified Cron', 'ZUnified', 'manage_options', 'zunified', 'zunified_page');
});

function zunified_page() {
    $log_path = ABSPATH . 'zcron-' . date('D') . '.log';
    $cron_path = ABSPATH . 'zunified_cron.php';
    $github_url = 'https://raw.githubusercontent.com/DropByOffice/wordpress-cron/main/zunified_cron.php';

    echo "<div class='wrap'><h1>ZUnified Manager</h1>";

    // Version detection
    $version = 'Unknown';
    if (file_exists($cron_path)) {
        $content = file_get_contents($cron_path);
        if (preg_match('/@version\s+([\d\.]+)/', $content, $m)) $version = $m[1];
    }
    echo "<p><strong>Current Version:</strong> $version</p>";

    // Pull latest
    if (isset($_POST['update_zunified'])) {
        $new = file_get_contents($github_url);
        if ($new && strlen($new) > 500) {
            file_put_contents($cron_path, $new);
            echo "<p style='color:green;'>✅ Updated zunified_cron.php from GitHub.</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to fetch file from GitHub.</p>";
        }
    }

    // Trigger cron
    if (isset($_POST['trigger_zunified'])) {
        $result = file_get_contents(site_url('/zunified_cron.php?key=cr0nx99'));
        echo "<p style='color:blue;'>⏱ Triggered zunified_cron.php<br><pre>" . htmlentities($result) . "</pre></p>";
    }

    // Delete tomorrow's log for rotation
    $next_day = date('D', strtotime('+1 day'));
    $rotate_path = ABSPATH . "zcron-$next_day.log";
    if (file_exists($rotate_path)) unlink($rotate_path);

    // Log tail
    echo "<h2>Today's Log (Last 50 lines)</h2>";
    if (file_exists($log_path)) {
        $lines = explode("\n", trim(file_get_contents($log_path)));
        echo "<pre>" . htmlentities(implode("\n", array_slice($lines, -50))) . "</pre>";
    } else {
        echo "<p>No log found for today.</p>";
    }

    // Scheduled cron events
    echo "<h2>Scheduled WP-Cron Events</h2><ul>";
    $crons = _get_cron_array();
    if ($crons) {
        foreach ($crons as $timestamp => $hooks) {
            foreach ($hooks as $hook => $details) {
                echo "<li><strong>$hook</strong> — " . date('Y-m-d H:i:s', $timestamp) . "</li>";
            }
        }
    } else {
        echo "<li>No cron events scheduled.</li>";
    }
    echo "</ul>";

    // Controls
    echo "<form method='post'>";
    echo "<p><button class='button-primary' name='update_zunified'>Update zunified_cron.php</button></p>";
    echo "<p><button class='button' name='trigger_zunified'>Run zunified now</button></p>";
    echo "</form></div>";
}