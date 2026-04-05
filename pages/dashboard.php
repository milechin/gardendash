<?php
// Dashboard: NOAA alerts + 7-day forecast + plant warnings
$coords  = get_location_coords();
$lat     = $coords['lat'];
$lon     = $coords['lon'];

$alerts   = get_active_alerts($lat, $lon);
$forecast = get_forecast($lat, $lon);
$active_plants = get_active_plants_for_year($year_id);
$all_plants    = get_all_plants_for_year($year_id);
$warnings = ($forecast && $active_plants)
    ? get_all_warnings($active_plants, $forecast)
    : [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h4 mb-0"><i class="bi bi-speedometer2"></i> Dashboard — <?= h((string)$year_row['year']) ?></h2>
  <a href="index.php?page=locations" class="btn btn-success btn-sm">
    <i class="bi bi-plus-lg"></i> Add Location
  </a>
</div>

<!-- NOAA Alerts -->
<?php foreach ($alerts as $alert): ?>
  <?php $cls = alert_severity_class($alert['severity']); ?>
  <div class="alert alert-<?= $cls ?> alert-dismissible fade show d-flex gap-2 align-items-start" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
    <div class="flex-grow-1">
      <strong><?= h($alert['event']) ?></strong>
      <?php if ($alert['headline']): ?>
        — <?= h($alert['headline']) ?>
      <?php endif; ?>
      <div class="small mt-1 text-body-secondary">
        <i class="bi bi-lightbulb"></i>
        <em><?= h(alert_garden_action($alert['event'])) ?></em>
      </div>
      <?php if ($alert['expires']): ?>
        <div class="small text-body-secondary">
          Expires: <?= h(date('D, M j g:ia T', strtotime($alert['expires']))) ?>
        </div>
      <?php endif; ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>

<?php if (!$lat || !$lon): ?>
  <div class="alert alert-warning">
    <i class="bi bi-geo-alt"></i>
    No location configured.
    <a href="index.php?page=settings">Set your coordinates in Settings</a> to enable weather.
  </div>
<?php endif; ?>

<!-- 7-day forecast -->
<?php if ($forecast): ?>
<div class="card mb-4 shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-cloud-sun"></i> 7-Day Forecast</span>
    <span class="small text-muted">
      <?php if ($forecast['stale'] ?? false): ?>
        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle"></i> Stale data</span>
      <?php else: ?>
        Updated <?= h($forecast['fetched_at']) ?>
      <?php endif; ?>
    </span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle text-center">
        <thead class="table-light">
          <tr>
            <th class="text-start ps-3">Date</th>
            <th>Forecast</th>
            <th>High</th>
            <th>Low</th>
            <th>Rain%</th>
            <th>Rain</th>
            <th>Humidity</th>
            <th>Feels Like</th>
            <th>Wind Gust</th>
            <th>Snow</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($forecast['daily'] as $day): ?>
          <?php
            $is_frost = $day['temp_min_f'] !== null && $day['temp_min_f'] <= 32;
            $is_hot   = ($day['heat_index_f'] ?? $day['temp_max_f'] ?? 0) >= 95;
            $row_class = $is_frost ? 'table-info' : ($is_hot ? 'table-warning' : '');
          ?>
          <tr class="<?= $row_class ?>">
            <td class="text-start ps-3 fw-semibold">
              <?= h(date('D M j', strtotime($day['date']))) ?>
            </td>
            <td class="small"><?= h($day['short_forecast']) ?></td>
            <td class="<?= ($day['temp_max_f'] ?? 0) >= 95 ? 'text-danger fw-bold' : '' ?>">
              <?= $day['temp_max_f'] !== null ? h(format_temp($day['temp_max_f'])) : '—' ?>
            </td>
            <td class="<?= ($day['temp_min_f'] ?? 100) <= 32 ? 'text-primary fw-bold' : '' ?>">
              <?= $day['temp_min_f'] !== null ? h(format_temp($day['temp_min_f'])) : '—' ?>
            </td>
            <td><?= $day['precip_pct'] ?>%</td>
            <td><?= h(format_rain($day['precip_in'])) ?></td>
            <td><?= $day['humidity_pct'] !== null ? $day['humidity_pct'] . '%' : '—' ?></td>
            <td>
              <?php if ($day['heat_index_f'] !== null): ?>
                <span class="text-danger"><?= h(format_temp($day['heat_index_f'])) ?></span>
              <?php elseif ($day['wind_chill_f'] !== null): ?>
                <span class="text-primary"><?= h(format_temp($day['wind_chill_f'])) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <?= $day['wind_gust_mph'] !== null
                ? h(number_format($day['wind_gust_mph'], 0)) . ' mph'
                : '—' ?>
            </td>
            <td>
              <?= ($day['snow_in'] ?? 0) > 0
                ? '<span class="badge bg-info text-dark">' . h(format_rain($day['snow_in'])) . '</span>'
                : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php else: ?>
  <div class="alert alert-secondary">
    <i class="bi bi-cloud-slash"></i> Weather data unavailable. Check your coordinates in
    <a href="index.php?page=settings">Settings</a>.
  </div>
<?php endif; ?>

<!-- Plant weather warnings -->
<?php if ($active_plants): ?>
<div class="card shadow-sm mb-4">
  <div class="card-header">
    <i class="bi bi-exclamation-diamond"></i> Active Plant Warnings
    <span class="badge bg-secondary ms-2"><?= count($active_plants) ?> active</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Plant</th>
            <th>Location</th>
            <th>Frost</th>
            <th>Heat</th>
            <th>Rain</th>
            <th>Wind</th>
            <th>Snow</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($active_plants as $plant): ?>
          <?php $w = $warnings[$plant['id']] ?? []; ?>
          <tr>
            <td class="ps-3">
              <?php if ($plant['photo']): ?>
                <img src="<?= h(UPLOADS_URL . '/' . $plant['photo']) ?>"
                     class="rounded me-2" width="32" height="32"
                     style="object-fit:cover" alt="">
              <?php endif; ?>
              <a href="index.php?page=plant_form&id=<?= $plant['id'] ?>">
                <?= h($plant['name']) ?>
              </a>
              <?php if ($plant['variety']): ?>
                <small class="text-muted"><?= h($plant['variety']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?= h($plant['location_name']) ?>
              <?= location_type_badge($plant['location_type']) ?>
            </td>
            <td>
              <?php if (!empty($w['frost']['triggered'])): ?>
                <span class="badge bg-primary" title="Low <?= h(format_temp(min($w['frost']['days']))) ?>">
                  <i class="bi bi-thermometer-snow"></i> Frost
                </span>
              <?php else: echo '<span class="text-success"><i class="bi bi-check-lg"></i></span>'; ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($w['heat']['triggered'])): ?>
                <span class="badge bg-danger" title="High <?= h(format_temp(max($w['heat']['days']))) ?>">
                  <i class="bi bi-thermometer-sun"></i> Heat
                </span>
              <?php else: echo '<span class="text-success"><i class="bi bi-check-lg"></i></span>'; ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($w['rain']['triggered'])): ?>
                <span class="badge bg-warning text-dark"
                      title="<?= h(format_rain($w['rain']['weekly_total'])) ?> / <?= h(format_rain($w['rain']['threshold'])) ?> needed">
                  <i class="bi bi-droplet"></i> Dry
                </span>
              <?php else: echo '<span class="text-success"><i class="bi bi-check-lg"></i></span>'; ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($w['wind']['triggered'])): ?>
                <span class="badge bg-secondary">
                  <i class="bi bi-wind"></i> Wind
                </span>
              <?php else: echo '<span class="text-success"><i class="bi bi-check-lg"></i></span>'; ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($w['snow']['triggered'])): ?>
                <span class="badge bg-info text-dark">
                  <i class="bi bi-snow"></i> Snow
                </span>
              <?php else: echo '<span class="text-success"><i class="bi bi-check-lg"></i></span>'; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php elseif (!$all_plants): ?>
<div class="alert alert-info">
  <i class="bi bi-seedling"></i>
  No plants added yet.
  <a href="index.php?page=locations">Add a location</a> and then
  <a href="index.php?page=plant_form">add your first plant</a>.
</div>
<?php else: ?>
<div class="alert alert-success">
  <i class="bi bi-check-circle"></i>
  No plants are currently in their active growing window today (<?= date('M j, Y') ?>).
  <a href="index.php?page=plants">View all plants.</a>
</div>
<?php endif; ?>

<!-- Quick stats row -->
<?php
$locations = get_locations_for_year($year_id);
$loc_count  = count($locations);
$plant_count = count($all_plants);
?>
<div class="row g-3 mt-1">
  <div class="col-sm-4">
    <div class="card text-center border-success">
      <div class="card-body py-3">
        <div class="display-6 fw-bold text-success"><?= $loc_count ?></div>
        <div class="small text-muted">Location<?= $loc_count !== 1 ? 's' : '' ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="card text-center border-success">
      <div class="card-body py-3">
        <div class="display-6 fw-bold text-success"><?= $plant_count ?></div>
        <div class="small text-muted">Plant<?= $plant_count !== 1 ? 's' : '' ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="card text-center border-success">
      <div class="card-body py-3">
        <div class="display-6 fw-bold text-success"><?= count($active_plants) ?></div>
        <div class="small text-muted">Active Today</div>
      </div>
    </div>
  </div>
</div>
