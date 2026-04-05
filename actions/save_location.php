<?php
csrf_verify();

$name = trim($_POST['name'] ?? '');
$type = $_POST['type'] ?? 'outdoor';

if ($name === '') {
    flash_set('error', 'Location name is required.');
    redirect('index.php?page=location_form' . (!empty($_POST['location_id']) ? '&id='.(int)$_POST['location_id'] : ''));
}

if (!in_array($type, ['indoor', 'outdoor'], true)) {
    $type = 'outdoor';
}

$data = [
    'year_id'   => (int)($_POST['year_id'] ?? $year_id),
    'name'      => $name,
    'type'      => $type,
    'area_sqin' => $_POST['area_sqin'] ?? '',
    'notes'     => trim($_POST['notes'] ?? ''),
];

if (!empty($_POST['location_id'])) {
    $data['id'] = (int)$_POST['location_id'];
}

$loc_id = save_location($data);
$verb   = isset($data['id']) ? 'updated' : 'created';
flash_set('success', "Location \"{$name}\" {$verb}.");
redirect('index.php?page=location_detail&id=' . $loc_id);
