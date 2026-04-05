<?php
csrf_verify();

$plant_id   = !empty($_POST['plant_id'])   ? (int)$_POST['plant_id']   : 0;
$loc_id     = !empty($_POST['location_id'])? (int)$_POST['location_id']: 0;
$name       = trim($_POST['name'] ?? '');
$sow_date   = trim($_POST['sow_date'] ?? '');
$harvest    = trim($_POST['harvest_date'] ?? '');
$transplant = trim($_POST['transplant_date'] ?? '');

// Validation
$errors = [];
if (!$name)      $errors[] = 'Plant name is required.';
if (!$loc_id)    $errors[] = 'Please select a location.';
if (!$sow_date)  $errors[] = 'Sow date is required.';
if (!$harvest)   $errors[] = 'Expected harvest date is required.';
if ($sow_date && $harvest && $harvest < $sow_date) {
    $errors[] = 'Harvest date must be after sow date.';
}
if ($transplant && $sow_date && $transplant < $sow_date) {
    $errors[] = 'Transplant date must be after sow date.';
}
if ($transplant && $harvest && $transplant > $harvest) {
    $errors[] = 'Transplant date must be before harvest date.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    $back = $plant_id ? "plant_form&id={$plant_id}" : 'plant_form';
    redirect('index.php?page=' . $back);
}

$data = [
    'location_id'        => $loc_id,
    'name'               => $name,
    'variety'            => trim($_POST['variety'] ?? ''),
    'sow_date'           => $sow_date,
    'harvest_date'       => $harvest,
    'transplant_date'    => $transplant ?: null,
    'frost_temp_f'       => $_POST['frost_temp_f']       ?? 36.0,
    'max_heat_temp_f'    => $_POST['max_heat_temp_f']    ?? 95.0,
    'min_weekly_rain_in' => $_POST['min_weekly_rain_in'] ?? 0.5,
    'notes'              => trim($_POST['notes'] ?? ''),
];
if ($plant_id) $data['id'] = $plant_id;

$saved_id = save_plant($data);

// ── Photo handling ────────────────────────────────────────────────────────────
$existing = get_plant_by_id($saved_id);

// Remove existing photo if checkbox ticked
if (!empty($_POST['remove_photo']) && $existing['photo']) {
    $old = UPLOADS_DIR . '/' . basename($existing['photo']);
    if (file_exists($old)) @unlink($old);
    set_plant_photo($saved_id, null);
}

// New upload
if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['photo']['tmp_name']);

    if (!in_array($mime, $allowed_mime, true)) {
        flash_set('error', 'Invalid photo type. Use JPEG, PNG, or WebP.');
        redirect('index.php?page=plant_form&id=' . $saved_id);
    }
    if ($_FILES['photo']['size'] > MAX_PHOTO_SIZE) {
        flash_set('error', 'Photo exceeds 5 MB limit.');
        redirect('index.php?page=plant_form&id=' . $saved_id);
    }

    $ext      = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        default      => 'png',
    };
    $filename = $saved_id . '_' . slugify($name) . '.' . $ext;
    $dest     = UPLOADS_DIR . '/' . $filename;

    if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);

    // Remove old photo file if replacing
    if ($existing['photo'] && $existing['photo'] !== $filename) {
        $old = UPLOADS_DIR . '/' . basename($existing['photo']);
        if (file_exists($old)) @unlink($old);
    }

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
        set_plant_photo($saved_id, $filename);
    } else {
        flash_set('error', 'Failed to save photo.');
        redirect('index.php?page=plant_form&id=' . $saved_id);
    }
}

$verb = $plant_id ? 'updated' : 'added';
flash_set('success', "Plant \"{$name}\" {$verb}.");
redirect('index.php?page=plants');
