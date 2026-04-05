<?php
csrf_verify();

$log_id = (int)($_POST['log_id'] ?? 0);
if ($log_id) {
    delete_fertilizer_entry($log_id);
    flash_set('success', 'Fertilizer entry removed.');
}

$redirect = $_POST['redirect_to'] ?? 'locations';
redirect('index.php?page=' . $redirect);
