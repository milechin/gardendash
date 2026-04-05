<?php
define('APP_NAME',       'Garden Dashboard');
define('APP_VERSION',    '1.0.0');
define('DB_PATH',        __DIR__ . '/data/garden.db');
define('UPLOADS_DIR',    __DIR__ . '/uploads');
define('UPLOADS_URL',    'uploads');
define('MAX_PHOTO_SIZE', 5 * 1024 * 1024); // 5 MB
define('BASE_URL',       '');

// NOAA User-Agent (required by api.weather.gov)
define('NOAA_USER_AGENT', 'GardenDash/1.0 (gardendash)');

// Default location: Waltham, MA
define('DEFAULT_LAT', '42.3765');
define('DEFAULT_LON', '-71.2356');
