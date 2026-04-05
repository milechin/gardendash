<?php
$locations = get_locations_for_year($year_id);
?>

<div class="row justify-content-center">
<div class="col-lg-7">

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php?page=plants" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h2 class="h4 mb-0"><i class="bi bi-upload"></i> Import Plants from JSON</h2>
</div>

<?php if (!$locations): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    You need at least one location before importing plants.
    <a href="index.php?page=location_form" class="alert-link">Add a location.</a>
  </div>
<?php else: ?>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="post" action="index.php" enctype="multipart/form-data">
      <input type="hidden" name="action"    value="import_plants">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

      <div class="mb-3">
        <label class="form-label fw-semibold">
          Target Location <span class="text-danger">*</span>
        </label>
        <select name="location_id" class="form-select" required>
          <option value="">— Select location —</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= $loc['id'] ?>"><?= h($loc['name']) ?> (<?= h($loc['type']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">All imported plants will be assigned to this location.</div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">
          JSON File <span class="text-danger">*</span>
        </label>
        <input type="file" name="json_file" class="form-control"
               accept=".json,application/json" required>
        <div class="form-text">Max 512 KB · must match the format below.</div>
      </div>

      <button type="submit" class="btn btn-success">
        <i class="bi bi-upload"></i> Import Plants
      </button>
      <a href="assets/example_plants.json" class="btn btn-outline-secondary ms-2" download>
        <i class="bi bi-download"></i> Download Example JSON
      </a>
    </form>
  </div>
</div>

<?php endif; ?>

<!-- Format documentation -->
<div class="card shadow-sm">
  <div class="card-header"><i class="bi bi-code-square"></i> Expected JSON Format</div>
  <div class="card-body">
    <p class="small text-muted mb-2">
      <strong>Required fields:</strong> <code>name</code>, <code>sow_date</code>, <code>harvest_date</code><br>
      <strong>Optional:</strong> <code>variety</code>, <code>transplant_date</code>,
      <code>frost_temp_f</code>, <code>max_heat_temp_f</code>, <code>min_weekly_rain_in</code>, <code>notes</code>
    </p>
    <pre class="bg-light border rounded p-3 small mb-0" style="font-size:0.78rem"><?php
echo h(json_encode([
    'plants' => [
        [
            'name'               => 'Tomato - Roma',
            'variety'            => 'Roma VF',
            'sow_date'           => '2026-03-15',
            'transplant_date'    => '2026-05-15',
            'harvest_date'       => '2026-08-20',
            'frost_temp_f'       => 36,
            'max_heat_temp_f'    => 95,
            'min_weekly_rain_in' => 0.75,
            'notes'              => 'Start indoors 6–8 weeks before last frost',
        ],
        [
            'name'               => 'Cucumber',
            'variety'            => 'Straight Eight',
            'sow_date'           => '2026-05-20',
            'harvest_date'       => '2026-08-15',
            'notes'              => 'Direct sow after last frost',
        ],
    ],
], JSON_PRETTY_PRINT));
?></pre>
    <p class="small text-muted mt-2 mb-0">
      Dates must be <code>YYYY-MM-DD</code> format.
      Threshold fields default to frost=36°F, heat=95°F, rain=0.5"/week if omitted.
    </p>
  </div>
</div>

</div>
</div>
