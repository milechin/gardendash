<?php
csrf_verify();

$lat = (float)($_POST['latitude']  ?? 0);
$lon = (float)($_POST['longitude'] ?? 0);

if ($lat < -90 || $lat > 90) {
    flash_set('error', 'Latitude must be between -90 and 90.');
    redirect('index.php?page=settings');
}
if ($lon < -180 || $lon > 180) {
    flash_set('error', 'Longitude must be between -180 and 180.');
    redirect('index.php?page=settings');
}

set_setting('latitude',  (string)$lat);
set_setting('longitude', (string)$lon);

// Clear cached NOAA grid URLs so they re-resolve for the new location
get_db()->exec("DELETE FROM settings WHERE key IN
    ('noaa_forecast_url','noaa_grid_url','noaa_points_lat','noaa_points_lon')");
// Clear weather cache
get_db()->exec("DELETE FROM weather_cache");

flash_set('success', 'Location saved. Weather cache cleared.');
redirect('index.php?page=settings');
