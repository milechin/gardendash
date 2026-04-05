<?php
csrf_verify();

$log_id = (int)($_POST['log_id'] ?? 0);
if ($log_id) {
    delete_watering_entry($log_id);
    flash_set('success', 'Watering entry removed.');
}

$redirect = $_POST['redirect_to'] ?? 'locations';
redirect('index.php?page=' . $redirect);
