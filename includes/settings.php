<?php
require_once __DIR__ . '/db.php';

function get_setting(string $key, string $default = ''): string {
    $stmt = get_db()->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function set_setting(string $key, string $value): void {
    $pdo = get_db();
    $stmt = $pdo->prepare("
        INSERT INTO settings (key, value, updated_at)
        VALUES (:k, :v, datetime('now'))
        ON CONFLICT(key) DO UPDATE SET value = :v, updated_at = datetime('now')
    ");
    $stmt->execute([':k' => $key, ':v' => $value]);
}

function get_location_coords(): array {
    return [
        'lat' => (float)get_setting('latitude',  DEFAULT_LAT),
        'lon' => (float)get_setting('longitude', DEFAULT_LON),
    ];
}
