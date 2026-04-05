<?php
csrf_verify();

$loc_id       = (int)($_POST['location_id'] ?? 0);
$fertilized_at = trim($_POST['fertilized_at'] ?? '');

if (!$loc_id || !$fertilized_at) {
    flash_set('error', 'Missing fertilizer date.');
} else {
    log_fertilizer(
        $loc_id,
        $fertilized_at,
        trim($_POST['product'] ?? ''),
        trim($_POST['notes'] ?? '')
    );
    flash_set('success', 'Fertilizer application logged.');
}

$redirect = $_POST['redirect_to'] ?? 'location_detail&id=' . $loc_id;
redirect('index.php?page=' . $redirect);
