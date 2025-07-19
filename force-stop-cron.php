<?php
// Force stop script for Windrose Subscription Cron
// Add this to your theme's functions.php temporarily or run directly

function windrose_force_stop_cron() {
    echo "Force stopping Windrose Subscription Cron...\n";
    
    // Delete the cron running option
    delete_option('windrose_cron_running');
    
    // Clear any scheduled cron events
    wp_clear_scheduled_hook('windrose_subscription_cron');
    wp_clear_scheduled_hook('windrose_subscription_hourly_cron');
    
    echo "Cron processes stopped.\n";
    
    // Check if any cron processes are still running
    $running = get_option('windrose_cron_running', false);
    if ($running) {
        echo "Warning: Cron still appears to be running.\n";
    } else {
        echo "Cron successfully stopped.\n";
    }
}

// Run the function if this file is accessed directly
if (basename(__FILE__) === 'force-stop-cron.php') {
    windrose_force_stop_cron();
}

// Also provide a web interface
function windrose_force_stop_cron_web() {
    if (isset($_GET['force_stop_cron'])) {
        windrose_force_stop_cron();
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0;'>Cron force stopped successfully!</div>";
    }
    
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; margin: 10px 0;'>";
    echo "<h3>Force Stop Cron</h3>";
    echo "<p>If the cron is running infinitely, click the button below to force stop it:</p>";
    echo "<a href='?force_stop_cron=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Force Stop Cron</a>";
    echo "</div>";
}

// Add to admin notices if accessed via web
if (isset($_GET['force_stop_cron'])) {
    add_action('admin_notices', 'windrose_force_stop_cron_web');
}
?> 