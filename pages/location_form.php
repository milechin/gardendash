<?php
$edit = null;
if (!empty($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    $edit = get_location_by_id((int)$_GET['id']);
    if (!$edit) {
        flash_set('error', 'Location not found.');
        redirect('index.php?page=locations');
    }
}
$is_edit = $edit !== null;
$title   = $is_edit ? 'Edit Location' : 'Add Location';
?>

<div class="row justify-content-center">
<div class="col-lg-6">

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php?page=locations" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h2 class="h4 mb-0"><?= h($title) ?></h2>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="index.php">
      <input type="hidden" name="action"    value="save_location">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="year_id"   value="<?= $year_id ?>">
      <?php if ($is_edit): ?>
        <input type="hidden" name="location_id" value="<?= $edit['id'] ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control"
               value="<?= h($edit['name'] ?? '') ?>"
               placeholder="e.g. Raised Bed 1, South Window Sill"
               required maxlength="100">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
        <div class="d-flex gap-3">
          <?php foreach (['outdoor', 'indoor'] as $t): ?>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="type"
                   id="type_<?= $t ?>" value="<?= $t ?>"
                   <?= ($edit['type'] ?? 'outdoor') === $t ? 'checked' : '' ?>>
            <label class="form-check-label" for="type_<?= $t ?>">
              <i class="bi bi-<?= $t==='indoor' ? 'house' : 'tree' ?>"></i>
              <?= ucfirst($t) ?>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Area (square inches)</label>
        <input type="number" name="area_sqin" class="form-control"
               value="<?= h($edit['area_sqin'] ?? '') ?>"
               step="0.1" min="0" placeholder="e.g. 864 (for a 2×3 ft bed)">
        <div class="form-text">
          Conversion: 144 in² = 1 ft². Leave blank if unknown.
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">Notes</label>
        <textarea name="notes" class="form-control" rows="3"
                  placeholder="Soil type, sun exposure, irrigation method…"><?= h($edit['notes'] ?? '') ?></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">
          <i class="bi bi-check-lg"></i> <?= $is_edit ? 'Update' : 'Create' ?> Location
        </button>
        <a href="index.php?page=locations" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

</div>
</div>
