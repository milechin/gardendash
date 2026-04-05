<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

// ── HTTP helper ───────────────────────────────────────────────────────────────

function noaa_http_get(string $url): ?string {
    $opts = ['http' => [
        'method'  => 'GET',
        'header'  => "User-Agent: " . NOAA_USER_AGENT . "\r\nAccept: application/geo+json\r\n",
        'timeout' => 10,
        'ignore_errors' => true,
    ]];
    $result = @file_get_contents($url, false, stream_context_create($opts));
    return ($result === false) ? null : $result;
}

// ── Cache helpers ─────────────────────────────────────────────────────────────

function weather_cache_get(string $key): ?array {
    $stmt = get_db()->prepare(
        "SELECT payload FROM weather_cache WHERE cache_key = ? AND expires_at > datetime('now')"
    );
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? json_decode($row['payload'], true) : null;
}

function weather_cache_set(string $key, array $data, int $ttl): void {
    $pdo  = get_db();
    $json = json_encode($data);
    $stmt = $pdo->prepare("
        INSERT INTO weather_cache (cache_key, payload, fetched_at, expires_at)
        VALUES (:k, :p, datetime('now'), datetime('now', :ttl))
        ON CONFLICT(cache_key) DO UPDATE
            SET payload    = :p,
                fetched_at = datetime('now'),
                expires_at = datetime('now', :ttl)
    ");
    $stmt->execute([':k' => $key, ':p' => $json, ':ttl' => "+{$ttl} seconds"]);
    // Prune stale entries older than 1 day
    $pdo->exec("DELETE FROM weather_cache WHERE expires_at < datetime('now', '-1 day')");
}

function weather_cache_get_stale(string $key): ?array {
    $stmt = get_db()->prepare(
        "SELECT payload FROM weather_cache WHERE cache_key = ? ORDER BY fetched_at DESC LIMIT 1"
    );
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $data = json_decode($row['payload'], true);
    if (is_array($data)) $data['stale'] = true;
    return $data;
}

function clear_weather_cache(): void {
    get_db()->exec("DELETE FROM weather_cache");
    // Force re-fetch of NOAA grid URLs on next load
    get_db()->exec("DELETE FROM settings WHERE key IN
        ('noaa_forecast_url','noaa_grid_url','noaa_alerts_zone',
         'noaa_points_lat','noaa_points_lon')");
}

// ── NOAA grid URL resolution ──────────────────────────────────────────────────

function get_noaa_urls(float $lat, float $lon): ?array {
    // Return cached URLs if coordinates haven't changed
    $cached_lat = (float)get_setting('noaa_points_lat', '0');
    $cached_lon = (float)get_setting('noaa_points_lon', '0');
    $forecast_url = get_setting('noaa_forecast_url');
    $grid_url     = get_setting('noaa_grid_url');

    if ($forecast_url && $grid_url
        && abs($cached_lat - $lat) < 0.001
        && abs($cached_lon - $lon) < 0.001) {
        return ['forecast' => $forecast_url, 'grid' => $grid_url];
    }

    $url  = sprintf('https://api.weather.gov/points/%.4f,%.4f', $lat, $lon);
    $body = noaa_http_get($url);
    if (!$body) return null;

    $json = json_decode($body, true);
    if (empty($json['properties']['forecast'])) return null;

    $forecast_url = $json['properties']['forecast'];
    $grid_url     = $json['properties']['forecastGridData'];

    set_setting('noaa_forecast_url', $forecast_url);
    set_setting('noaa_grid_url',     $grid_url);
    set_setting('noaa_points_lat',   (string)$lat);
    set_setting('noaa_points_lon',   (string)$lon);

    return ['forecast' => $forecast_url, 'grid' => $grid_url];
}

// ── Gridded data helpers ──────────────────────────────────────────────────────

/** Parse ISO 8601 duration string (e.g. PT3H, P1D) to hours */
function iso8601_duration_to_hours(string $d): float {
    preg_match('/P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?)?/', $d, $m);
    return ((float)($m[1] ?? 0)) * 24
         + ((float)($m[2] ?? 0))
         + ((float)($m[3] ?? 0)) / 60;
}

/**
 * Aggregate NOAA time-series values into per-day buckets.
 * Returns ['YYYY-MM-DD' => [float, ...], ...]
 */
function aggregate_to_daily(array $values): array {
    $daily = [];
    foreach ($values as $entry) {
        if ($entry['value'] === null) continue;
        // validTime: "2026-04-05T12:00:00+00:00/PT3H"
        $parts = explode('/', $entry['validTime'] ?? '');
        if (count($parts) < 1) continue;
        // Use the DATE part of the start time as the bucket key
        $date = substr($parts[0], 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;
        $daily[$date][] = (float)$entry['value'];
    }
    return $daily;
}

// ── Main forecast fetch ───────────────────────────────────────────────────────

function get_forecast(float $lat, float $lon): ?array {
    $key = 'forecast_' . round($lat, 4) . '_' . round($lon, 4);

    $cached = weather_cache_get($key);
    if ($cached) return $cached;

    $urls = get_noaa_urls($lat, $lon);
    if (!$urls) return weather_cache_get_stale($key);

    // ── Step 1: 7-day period forecast ────────────────────────────────────────
    $body = noaa_http_get($urls['forecast']);
    if (!$body) return weather_cache_get_stale($key);

    $json    = json_decode($body, true);
    $periods = $json['properties']['periods'] ?? [];
    if (!$periods) return weather_cache_get_stale($key);

    $today = date('Y-m-d');
    $daily = [];

    foreach ($periods as $p) {
        $date = substr($p['startTime'], 0, 10);
        if ($date < $today) continue;

        if (!isset($daily[$date])) {
            $daily[$date] = [
                'date'           => $date,
                'temp_max_f'     => null,
                'temp_min_f'     => null,
                'precip_pct'     => 0,
                'precip_in'      => 0.0,
                'snow_in'        => 0.0,
                'humidity_pct'   => null,
                'heat_index_f'   => null,
                'wind_chill_f'   => null,
                'wind_gust_mph'  => null,
                'sky_cover_pct'  => null,
                'short_forecast' => '',
                'icon'           => $p['icon'] ?? '',
            ];
        }

        $temp = (float)$p['temperature'];
        if (($p['temperatureUnit'] ?? 'F') === 'C') {
            $temp = $temp * 9 / 5 + 32;
        }
        $precip_pct = (int)($p['probabilityOfPrecipitation']['value'] ?? 0);

        if ($p['isDaytime']) {
            $daily[$date]['temp_max_f']     = $temp;
            $daily[$date]['short_forecast'] = $p['shortForecast'] ?? '';
            $daily[$date]['icon']           = $p['icon'] ?? '';
            // Parse wind speed string like "5 to 10 mph" or "10 mph"
            if (!empty($p['windSpeed'])) {
                preg_match('/(\d+)(?:\s+to\s+(\d+))?\s+mph/i', $p['windSpeed'], $wm);
                $daily[$date]['wind_gust_mph'] = isset($wm[2])
                    ? (float)$wm[2] : (float)($wm[1] ?? 0);
            }
        } else {
            $daily[$date]['temp_min_f'] = $temp;
        }
        $daily[$date]['precip_pct'] = max($daily[$date]['precip_pct'], $precip_pct);
    }

    // Keep only 7 days
    $daily = array_slice($daily, 0, 7, true);
    $dates = array_keys($daily);

    // ── Step 2: Gridded data (precip amounts, humidity, wind gust, snow) ──────
    $grid_key  = 'grid_' . round($lat, 4) . '_' . round($lon, 4);
    $grid_data = weather_cache_get($grid_key);

    if (!$grid_data && !empty($urls['grid'])) {
        $grid_body = noaa_http_get($urls['grid']);
        if ($grid_body) {
            $gj   = json_decode($grid_body, true);
            $gp   = $gj['properties'] ?? [];
            $grid_data = [
                'precip'    => $gp['quantitativePrecipitation']['values'] ?? [],
                'humidity'  => $gp['relativeHumidity']['values']          ?? [],
                'wind_gust' => $gp['windGust']['values']                  ?? [],
                'snow'      => $gp['snowfallAmount']['values']             ?? [],
                'sky_cover' => $gp['skyCover']['values']                   ?? [],
            ];
            weather_cache_set($grid_key, $grid_data, WEATHER_CACHE_TTL);
        }
    }

    if ($grid_data) {
        $by_precip   = aggregate_to_daily($grid_data['precip']    ?? []);
        $by_humidity = aggregate_to_daily($grid_data['humidity']  ?? []);
        $by_gust     = aggregate_to_daily($grid_data['wind_gust'] ?? []);
        $by_snow     = aggregate_to_daily($grid_data['snow']      ?? []);
        $by_sky      = aggregate_to_daily($grid_data['sky_cover'] ?? []);

        foreach ($dates as $date) {
            if (!isset($daily[$date])) continue;

            // Precipitation: NOAA returns mm — convert to inches
            $pmm = array_sum($by_precip[$date] ?? []);
            $daily[$date]['precip_in'] = round($pmm / 25.4, 2);

            // Snow: mm → inches
            $smm = array_sum($by_snow[$date] ?? []);
            $daily[$date]['snow_in'] = round($smm / 25.4, 2);

            // Humidity: average (already in %)
            $hvals = array_filter($by_humidity[$date] ?? [], fn($v) => $v !== null);
            if ($hvals) {
                $daily[$date]['humidity_pct'] = (int)round(array_sum($hvals) / count($hvals));
            }

            // Wind gust: NOAA returns m/s — convert to mph, take max
            $gvals = array_filter($by_gust[$date] ?? [], fn($v) => $v !== null);
            if ($gvals) {
                $daily[$date]['wind_gust_mph'] = round(max($gvals) * 2.237, 1);
            }

            // Sky cover: average (%)
            $svals = array_filter($by_sky[$date] ?? [], fn($v) => $v !== null);
            if ($svals) {
                $daily[$date]['sky_cover_pct'] = (int)round(array_sum($svals) / count($svals));
            }
        }
    }

    // ── Step 3: Derive heat index / wind chill ────────────────────────────────
    foreach ($daily as &$day) {
        if ($day['temp_max_f'] !== null && $day['temp_max_f'] >= 80
            && $day['humidity_pct'] !== null) {
            $day['heat_index_f'] = calc_heat_index($day['temp_max_f'], $day['humidity_pct']);
        }
        if ($day['temp_min_f'] !== null && $day['temp_min_f'] <= 50
            && ($day['wind_gust_mph'] ?? 0) >= 3) {
            $day['wind_chill_f'] = calc_wind_chill($day['temp_min_f'], $day['wind_gust_mph']);
        }
    }
    unset($day);

    $result = [
        'daily'      => array_values($daily),
        'stale'      => false,
        'fetched_at' => date('Y-m-d H:i:s'),
    ];

    weather_cache_set($key, $result, WEATHER_CACHE_TTL);
    return $result;
}

// ── Heat index (Rothfusz) ─────────────────────────────────────────────────────

function calc_heat_index(float $T, float $RH): float {
    $HI = -42.379 + 2.04901523*$T + 10.14333127*$RH
        - 0.22475541*$T*$RH - 0.00683783*$T*$T
        - 0.05481717*$RH*$RH + 0.00122874*$T*$T*$RH
        + 0.00085282*$T*$RH*$RH - 0.00000199*$T*$T*$RH*$RH;
    return round($HI, 1);
}

// ── Wind chill ────────────────────────────────────────────────────────────────

function calc_wind_chill(float $T, float $V): float {
    $WC = 35.74 + 0.6215*$T - 35.75*pow($V, 0.16) + 0.4275*$T*pow($V, 0.16);
    return round($WC, 1);
}

// ── Active NOAA alerts ────────────────────────────────────────────────────────

function get_active_alerts(float $lat, float $lon): array {
    $key    = 'alerts_' . round($lat, 4) . '_' . round($lon, 4);
    $cached = weather_cache_get($key);
    if ($cached !== null) return $cached;

    $url  = sprintf('https://api.weather.gov/alerts/active?point=%.4f,%.4f', $lat, $lon);
    $body = noaa_http_get($url);
    if (!$body) return [];

    $json = json_decode($body, true);
    if (empty($json['features'])) {
        weather_cache_set($key, [], ALERT_CACHE_TTL);
        return [];
    }

    $garden_events = [
        'Frost Advisory', 'Freeze Warning', 'Hard Freeze Warning',
        'Freeze Watch', 'Freeze Advisory',
        'Winter Storm Warning', 'Winter Storm Watch', 'Winter Weather Advisory',
        'Wind Advisory', 'High Wind Warning', 'High Wind Watch',
        'Heat Advisory', 'Excessive Heat Warning', 'Excessive Heat Watch',
        'Dense Fog Advisory',
    ];

    $alerts = [];
    foreach ($json['features'] as $f) {
        $props = $f['properties'] ?? [];
        $event = $props['event'] ?? '';
        if (!in_array($event, $garden_events, true)) continue;
        $alerts[] = [
            'event'       => $event,
            'severity'    => $props['severity']    ?? 'Unknown',
            'urgency'     => $props['urgency']     ?? 'Unknown',
            'headline'    => $props['headline']    ?? $event,
            'effective'   => $props['effective']   ?? '',
            'expires'     => $props['expires']     ?? '',
            'description' => $props['description'] ?? '',
            'instruction' => $props['instruction'] ?? '',
        ];
    }

    weather_cache_set($key, $alerts, ALERT_CACHE_TTL);
    return $alerts;
}

// ── Per-plant warning logic ───────────────────────────────────────────────────

function get_plant_warnings(array $plant, array $forecast): array {
    $warnings = [
        'frost' => ['triggered' => false, 'days' => [], 'threshold' => $plant['frost_temp_f']],
        'heat'  => ['triggered' => false, 'days' => [], 'threshold' => $plant['max_heat_temp_f']],
        'rain'  => ['triggered' => false, 'weekly_total' => 0.0, 'threshold' => $plant['min_weekly_rain_in']],
        'wind'  => ['triggered' => false, 'days' => []],
        'snow'  => ['triggered' => false, 'days' => []],
    ];

    $total_precip = 0.0;

    foreach ($forecast['daily'] as $day) {
        $date = $day['date'];

        // Frost
        if ($day['temp_min_f'] !== null && $day['temp_min_f'] < $plant['frost_temp_f']) {
            $warnings['frost']['triggered']    = true;
            $warnings['frost']['days'][$date]  = $day['temp_min_f'];
        }

        // Heat (use heat index when available)
        $feels = $day['heat_index_f'] ?? $day['temp_max_f'];
        if ($feels !== null && $feels > $plant['max_heat_temp_f']) {
            $warnings['heat']['triggered']   = true;
            $warnings['heat']['days'][$date] = $feels;
        }

        // Rain accumulation
        $total_precip += ($day['precip_in'] ?? 0.0);

        // Wind (>30 mph gusts)
        if (($day['wind_gust_mph'] ?? 0) > 30) {
            $warnings['wind']['triggered']   = true;
            $warnings['wind']['days'][$date] = $day['wind_gust_mph'];
        }

        // Snow
        if (($day['snow_in'] ?? 0) > 0) {
            $warnings['snow']['triggered']   = true;
            $warnings['snow']['days'][$date] = $day['snow_in'];
        }
    }

    $warnings['rain']['weekly_total'] = round($total_precip, 2);
    if ($total_precip < $plant['min_weekly_rain_in']) {
        $warnings['rain']['triggered'] = true;
    }

    return $warnings;
}

function get_all_warnings(array $plants, array $forecast): array {
    $out = [];
    foreach ($plants as $plant) {
        $out[$plant['id']] = get_plant_warnings($plant, $forecast);
    }
    return $out;
}

/** Returns true if any warning is triggered for a plant */
function has_any_warning(array $warnings): bool {
    foreach ($warnings as $w) {
        if ($w['triggered']) return true;
    }
    return false;
}

/** Readable action hint for a NOAA alert event */
function alert_garden_action(string $event): string {
    return match (true) {
        str_contains($event, 'Hard Freeze')      => 'Temps below 28°F — bring in all plants now',
        str_contains($event, 'Freeze Warning')   => 'Cover or bring in tender plants',
        str_contains($event, 'Frost')            => 'Protect tender plants tonight',
        str_contains($event, 'Freeze Watch')     => 'Possible freeze in 24–48 hrs — prepare covers',
        str_contains($event, 'Winter Storm')     => 'Stake/cover plants; clear snow from branches',
        str_contains($event, 'High Wind'),
        str_contains($event, 'Wind Advisory')    => 'Stake tall plants and secure row covers',
        str_contains($event, 'Excessive Heat')   => 'Water twice daily and provide shade',
        str_contains($event, 'Heat Advisory')    => 'Water deeply; mulch to retain moisture',
        default                                  => 'Check plants for stress',
    };
}
