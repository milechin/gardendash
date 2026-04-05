<?php
csrf_verify();

$year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
if ($year < 2000 || $year > 2099) {
    flash_set('error', 'Invalid year. Must be between 2000 and 2099.');
    redirect('index.php');
}

$id = create_year($year);
set_active_year_id($id);
flash_set('success', "Year {$year} added.");
redirect('index.php?page=dashboard');
