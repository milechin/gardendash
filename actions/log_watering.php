<?php
csrf_verify();

$loc_id    = (int)($_POST['location_id'] ?? 0);
$watered_at = trim($_POST['watered_at'] ?? '');
$gallons    = (float)($_POST['gallons'] ?? 0);

if (!$loc_id || !$watered_at || $gallons <= 0) {
    flash_set('error', 'Missing or invalid watering data.');
} else {
    // Convert datetime-local value (YYYY-MM-DDTHH:MM) to standard format
    $watered_at = str_replace('T', ' ', $watered_at);
    log_watering($loc_id, $watered_at, $gallons, trim($_POST['notes'] ?? ''));
    flash_set('success', 'Watering logged.');
}

$redirect = $_POST['redirect_to'] ?? 'location_detail&id=' . $loc_id;
redirect('index.php?page=' . $redirect);
