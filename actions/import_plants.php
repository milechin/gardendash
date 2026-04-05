<?php
csrf_verify();

$loc_id = (int)($_POST['location_id'] ?? 0);
if (!$loc_id) {
    flash_set('error', 'Please select a target location.');
    redirect('index.php?page=import');
}

$loc = get_location_by_id($loc_id);
if (!$loc) {
    flash_set('error', 'Selected location not found.');
    redirect('index.php?page=import');
}

// ── File validation ───────────────────────────────────────────────────────────
if (empty($_FILES['json_file']['name']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
    flash_set('error', 'No file uploaded or upload error occurred.');
    redirect('index.php?page=import');
}

if ($_FILES['json_file']['size'] > 512 * 1024) {
    flash_set('error', 'File exceeds 512 KB limit.');
    redirect('index.php?page=import');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($_FILES['json_file']['tmp_name']);
if (!in_array($mime, ['application/json', 'text/plain', 'text/json'], true)) {
    // Some browsers send text/plain for .json; also check extension
    $ext = strtolower(pathinfo($_FILES['json_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'json') {
        flash_set('error', 'Please upload a .json file.');
        redirect('index.php?page=import');
    }
}

$raw = file_get_contents($_FILES['json_file']['tmp_name']);
$data = json_decode($raw, true);

if (!is_array($data) || !isset($data['plants']) || !is_array($data['plants'])) {
    flash_set('error', 'Invalid JSON structure. Expected {"plants": [...]}.');
    redirect('index.php?page=import');
}

// ── Row validation ────────────────────────────────────────────────────────────
$errors = [];
foreach ($data['plants'] as $i => $p) {
    $row = $i + 1;
    if (empty($p['name']) || !is_string($p['name'])) {
        $errors[] = "Row {$row}: 'name' is required.";
    }
    if (empty($p['sow_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $p['sow_date'])) {
        $errors[] = "Row {$row}: 'sow_date' must be YYYY-MM-DD.";
    }
    if (empty($p['harvest_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $p['harvest_date'])) {
        $errors[] = "Row {$row}: 'harvest_date' must be YYYY-MM-DD.";
    }
    if (!empty($p['sow_date']) && !empty($p['harvest_date']) && $p['harvest_date'] < $p['sow_date']) {
        $errors[] = "Row {$row}: harvest_date must be after sow_date.";
    }
    if (!empty($p['transplant_date'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $p['transplant_date'])) {
            $errors[] = "Row {$row}: 'transplant_date' must be YYYY-MM-DD.";
        } elseif (!empty($p['sow_date']) && $p['transplant_date'] < $p['sow_date']) {
            $errors[] = "Row {$row}: transplant_date must be after sow_date.";
        }
    }
    foreach (['frost_temp_f', 'max_heat_temp_f', 'min_weekly_rain_in'] as $field) {
        if (isset($p[$field]) && (!is_numeric($p[$field]) || (float)$p[$field] < 0)) {
            $errors[] = "Row {$row}: '{$field}' must be a positive number.";
        }
    }
}

if ($errors) {
    flash_set('error', 'Import failed: ' . implode(' | ', array_slice($errors, 0, 5))
        . (count($errors) > 5 ? ' … and ' . (count($errors) - 5) . ' more.' : ''));
    redirect('index.php?page=import');
}

// ── Insert all plants ─────────────────────────────────────────────────────────
$count = 0;
foreach ($data['plants'] as $p) {
    save_plant([
        'location_id'        => $loc_id,
        'name'               => trim($p['name']),
        'variety'            => trim($p['variety'] ?? ''),
        'sow_date'           => $p['sow_date'],
        'harvest_date'       => $p['harvest_date'],
        'transplant_date'    => $p['transplant_date'] ?? null,
        'frost_temp_f'       => $p['frost_temp_f']       ?? 36.0,
        'max_heat_temp_f'    => $p['max_heat_temp_f']    ?? 95.0,
        'min_weekly_rain_in' => $p['min_weekly_rain_in'] ?? 0.5,
        'notes'              => trim($p['notes'] ?? ''),
    ]);
    $count++;
}

flash_set('success', "Successfully imported {$count} plant" . ($count !== 1 ? 's' : '')
    . " into \"{$loc['name']}\".");
redirect('index.php?page=plants');
