<?php
csrf_verify();

$loc_id = (int)($_POST['location_id'] ?? 0);
if (!$loc_id) {
    redirect('index.php?page=locations');
}

$loc = get_location_by_id($loc_id);
if (!$loc) {
    flash_set('error', 'Location not found.');
    redirect('index.php?page=locations');
}

// Delete all plant photos for plants in this location
$plants = get_plants_for_location($loc_id);
foreach ($plants as $p) {
    if ($p['photo']) {
        $path = UPLOADS_DIR . '/' . basename($p['photo']);
        if (file_exists($path)) @unlink($path);
    }
}

delete_location($loc_id);
flash_set('success', "Location \"{$loc['name']}\" deleted.");
redirect('index.php?page=locations');
