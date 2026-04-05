<?php
require_once __DIR__ . '/../config.php';

/**
 * Return the singleton PDO connection.
 * Creates the database and schema on first call.
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    init_schema($pdo);
    return $pdo;
}

function init_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS growing_years (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            year       INTEGER NOT NULL UNIQUE,
            notes      TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS locations (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            year_id     INTEGER NOT NULL REFERENCES growing_years(id) ON DELETE CASCADE,
            name        TEXT NOT NULL,
            type        TEXT NOT NULL DEFAULT 'outdoor',
            area_sqin   REAL,
            notes       TEXT,
            created_at  TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS watering_log (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            location_id INTEGER NOT NULL REFERENCES locations(id) ON DELETE CASCADE,
            watered_at  TEXT NOT NULL,
            gallons     REAL NOT NULL,
            notes       TEXT,
            created_at  TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fertilizer_log (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            location_id     INTEGER NOT NULL REFERENCES locations(id) ON DELETE CASCADE,
            fertilized_at   TEXT NOT NULL,
            product         TEXT,
            notes           TEXT,
            created_at      TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plants (
            id                  INTEGER PRIMARY KEY AUTOINCREMENT,
            location_id         INTEGER NOT NULL REFERENCES locations(id) ON DELETE CASCADE,
            name                TEXT NOT NULL,
            variety             TEXT,
            sow_date            TEXT NOT NULL,
            harvest_date        TEXT NOT NULL,
            transplant_date     TEXT,
            photo               TEXT,
            frost_temp_f        REAL NOT NULL DEFAULT 36.0,
            max_heat_temp_f     REAL NOT NULL DEFAULT 95.0,
            min_weekly_rain_in  REAL NOT NULL DEFAULT 0.5,
            notes               TEXT,
            created_at          TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at          TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key        TEXT PRIMARY KEY,
            value      TEXT NOT NULL,
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS weather_cache (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            cache_key  TEXT NOT NULL UNIQUE,
            payload    TEXT NOT NULL,
            fetched_at TEXT NOT NULL DEFAULT (datetime('now')),
            expires_at TEXT NOT NULL
        )
    ");

    // Insert default settings if not present
    $defaults = [
        'latitude'  => DEFAULT_LAT,
        'longitude' => DEFAULT_LON,
    ];
    $stmt = $pdo->prepare(
        "INSERT OR IGNORE INTO settings (key, value) VALUES (:k, :v)"
    );
    foreach ($defaults as $k => $v) {
        $stmt->execute([':k' => $k, ':v' => $v]);
    }
}
