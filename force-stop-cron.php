<?php
// Force stop stuck cron jobs
require_once('../../../wp-load.php');

// Clear cron running status
delete_option('windrose_cron_running');

// Clear scheduled cron events
wp_clear_scheduled_hook('windrose_subscription_cron');

echo "Cron jobs have been forcefully stopped and cleared.";
?> 