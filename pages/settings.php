<?php
$lat = get_setting('latitude',  DEFAULT_LAT);
$lon = get_setting('longitude', DEFAULT_LON);
?>

<div class="row justify-content-center">
<div class="col-lg-6">

<h2 class="h4 mb-4"><i class="bi bi-gear"></i> Settings</h2>

<!-- Location settings -->
<div class="card shadow-sm mb-4">
  <div class="card-header"><i class="bi bi-geo-alt"></i> Weather Location</div>
  <div class="card-body">
    <p class="small text-muted">
      Enter your latitude and longitude to pull NOAA weather forecasts and alerts.
      You can find your coordinates at
      <a href="https://www.latlong.net/" target="_blank" rel="noopener">latlong.net</a>
      or via Google Maps (right-click a location).
    </p>
    <form method="post" action="index.php">
      <input type="hidden" name="action"    value="save_settings">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label fw-semibold">Latitude</label>
          <input type="number" name="latitude" class="form-control"
                 value="<?= h($lat) ?>"
                 step="0.0001" min="-90" max="90"
                 placeholder="e.g. 42.3765" required>
        </div>
        <div class="col-sm-6">
          <label class="form-label fw-semibold">Longitude</label>
          <input type="number" name="longitude" class="form-control"
                 value="<?= h($lon) ?>"
                 step="0.0001" min="-180" max="180"
                 placeholder="e.g. -71.2356" required>
        </div>
      </div>
      <div class="alert alert-light border small mb-3">
        <i class="bi bi-pin-map"></i>
        Current location: <strong><?= h($lat) ?>°N, <?= h($lon) ?>°W</strong>
        (Waltham, MA default)
      </div>
      <button type="submit" class="btn btn-success">
        <i class="bi bi-check-lg"></i> Save Location
      </button>
    </form>
  </div>
</div>

<!-- Weather cache -->
<div class="card shadow-sm mb-4">
  <div class="card-header"><i class="bi bi-cloud-arrow-down"></i> Weather Cache</div>
  <div class="card-body">
    <p class="small text-muted">
      NOAA forecast data is cached for 3 hours, alerts for 30 minutes.
      Use this to force an immediate refresh.
    </p>
    <form method="post" action="index.php"
          onsubmit="return confirm('Clear all weather cache? It will re-fetch on next page load.')">
      <input type="hidden" name="action"    value="refresh_weather">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <button type="submit" class="btn btn-outline-warning">
        <i class="bi bi-arrow-clockwise"></i> Refresh Weather Now
      </button>
    </form>
  </div>
</div>

<!-- About -->
<div class="card shadow-sm">
  <div class="card-header"><i class="bi bi-info-circle"></i> About</div>
  <div class="card-body small text-muted">
    <p>
      <strong><?= h(APP_NAME) ?></strong> v<?= h(APP_VERSION) ?><br>
      Weather data provided by <a href="https://www.weather.gov" target="_blank" rel="noopener">NOAA National Weather Service</a>
      via the free <code>api.weather.gov</code> API (US locations only).
    </p>
    <p class="mb-0">
      Database: SQLite · PHP <?= phpversion() ?>
    </p>
  </div>
</div>

</div>
</div>
