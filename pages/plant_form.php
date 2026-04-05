<?php
$edit       = null;
$preselect_location = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;

if (!empty($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    $edit = get_plant_by_id((int)$_GET['id']);
    if (!$edit) {
        flash_set('error', 'Plant not found.');
        redirect('index.php?page=plants');
    }
}
$is_edit   = $edit !== null;
$locations = get_locations_for_year($year_id);

if (!$locations && !$is_edit) {
    flash_set('error', 'Please add a location before adding plants.');
    redirect('index.php?page=location_form');
}
?>

<div class="row justify-content-center">
<div class="col-lg-7">

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php?page=plants" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h2 class="h4 mb-0"><?= $is_edit ? 'Edit Plant' : 'Add Plant' ?></h2>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="index.php" enctype="multipart/form-data">
      <input type="hidden" name="action"    value="save_plant">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <?php if ($is_edit): ?>
        <input type="hidden" name="plant_id" value="<?= $edit['id'] ?>">
      <?php endif; ?>

      <!-- Location -->
      <div class="mb-3">
        <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
        <select name="location_id" class="form-select" required>
          <option value="">— Select location —</option>
          <?php foreach ($locations as $loc): ?>
          <?php
            $sel = $is_edit
              ? ($edit['location_id'] == $loc['id'])
              : ($preselect_location == $loc['id']);
          ?>
          <option value="<?= $loc['id'] ?>" <?= $sel ? 'selected' : '' ?>>
            <?= h($loc['name']) ?> (<?= h($loc['type']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="row g-3 mb-3">
        <!-- Name -->
        <div class="col-sm-7">
          <label class="form-label fw-semibold">Plant Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control"
                 value="<?= h($edit['name'] ?? '') ?>"
                 placeholder="e.g. Tomato, Basil, Cucumber"
                 required maxlength="100">
        </div>
        <!-- Variety -->
        <div class="col-sm-5">
          <label class="form-label fw-semibold">Variety</label>
          <input type="text" name="variety" class="form-control"
                 value="<?= h($edit['variety'] ?? '') ?>"
                 placeholder="e.g. Roma, Sweet Basil"
                 maxlength="100">
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-sm-4">
          <label class="form-label fw-semibold">Sow Date <span class="text-danger">*</span></label>
          <input type="date" name="sow_date" class="form-control"
                 value="<?= h($edit['sow_date'] ?? '') ?>" required>
        </div>
        <div class="col-sm-4">
          <label class="form-label fw-semibold">Transplant Date</label>
          <input type="date" name="transplant_date" class="form-control"
                 value="<?= h($edit['transplant_date'] ?? '') ?>">
          <div class="form-text">Leave blank if direct sow.</div>
        </div>
        <div class="col-sm-4">
          <label class="form-label fw-semibold">Expected Harvest <span class="text-danger">*</span></label>
          <input type="date" name="harvest_date" class="form-control"
                 value="<?= h($edit['harvest_date'] ?? '') ?>" required>
        </div>
      </div>

      <!-- Photo -->
      <div class="mb-3">
        <label class="form-label fw-semibold">Photo</label>
        <?php if ($is_edit && $edit['photo']): ?>
          <div class="mb-2">
            <img src="<?= h(UPLOADS_URL . '/' . $edit['photo']) ?>"
                 width="100" height="100" class="rounded" style="object-fit:cover" alt="">
          </div>
          <div class="form-check mb-2">
            <input type="checkbox" name="remove_photo" id="remove_photo"
                   class="form-check-input" value="1">
            <label for="remove_photo" class="form-check-label small text-danger">
              Remove current photo
            </label>
          </div>
        <?php endif; ?>
        <input type="file" name="photo" class="form-control"
               accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPEG, PNG, or WebP · max 5 MB</div>
      </div>

      <!-- Weather thresholds -->
      <div class="card bg-light border-0 mb-3">
        <div class="card-body">
          <h6 class="card-subtitle mb-3 text-muted">
            <i class="bi bi-thermometer-half"></i> Weather Warning Thresholds
          </h6>
          <div class="row g-3">
            <div class="col-sm-4">
              <label class="form-label small">
                <i class="bi bi-thermometer-snow text-primary"></i>
                Frost below (°F)
              </label>
              <input type="number" name="frost_temp_f" class="form-control form-control-sm"
                     value="<?= h($edit['frost_temp_f'] ?? '36') ?>"
                     step="1" min="-20" max="50">
            </div>
            <div class="col-sm-4">
              <label class="form-label small">
                <i class="bi bi-thermometer-sun text-danger"></i>
                Heat above (°F)
              </label>
              <input type="number" name="max_heat_temp_f" class="form-control form-control-sm"
                     value="<?= h($edit['max_heat_temp_f'] ?? '95') ?>"
                     step="1" min="50" max="130">
            </div>
            <div class="col-sm-4">
              <label class="form-label small">
                <i class="bi bi-droplet text-info"></i>
                Min rain/week (in)
              </label>
              <input type="number" name="min_weekly_rain_in" class="form-control form-control-sm"
                     value="<?= h($edit['min_weekly_rain_in'] ?? '0.5') ?>"
                     step="0.1" min="0" max="10">
            </div>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="mb-4">
        <label class="form-label fw-semibold">Notes</label>
        <textarea name="notes" class="form-control" rows="3"><?= h($edit['notes'] ?? '') ?></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">
          <i class="bi bi-check-lg"></i> <?= $is_edit ? 'Update' : 'Add' ?> Plant
        </button>
        <a href="index.php?page=plants" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

</div>
</div>
