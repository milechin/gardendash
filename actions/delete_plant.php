<?php
csrf_verify();

$plant_id = (int)($_POST['plant_id'] ?? 0);
if (!$plant_id) {
    redirect('index.php?page=plants');
}

$plant = get_plant_by_id($plant_id);
if (!$plant) {
    flash_set('error', 'Plant not found.');
    redirect('index.php?page=plants');
}

// Delete photo file if present
if ($plant['photo']) {
    $path = UPLOADS_DIR . '/' . basename($plant['photo']);
    if (file_exists($path)) @unlink($path);
}

delete_plant($plant_id);
flash_set('success', "Plant \"{$plant['name']}\" deleted.");

$redirect = $_POST['redirect_to'] ?? 'plants';
redirect('index.php?page=' . $redirect);
