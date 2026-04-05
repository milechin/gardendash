<?php
csrf_verify();
clear_weather_cache();
flash_set('success', 'Weather cache cleared. Data will refresh on next page load.');
redirect('index.php?page=settings');
