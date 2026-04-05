<?php
$locations = get_locations_for_year($year_id);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="h4 mb-0"><i class="bi bi-grid-3x3-gap"></i> Locations — <?= h((string)$year_row['year']) ?></h2>
  <a href="index.php?page=location_form" class="btn btn-success">
    <i class="bi bi-plus-lg"></i> Add Location
  </a>
</div>

<?php if (!$locations): ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    No locations yet. A "location" is a growing area — a raised bed, window sill, row in the ground, etc.
    <a href="index.php?page=location_form" class="alert-link">Add your first location.</a>
  </div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($locations as $loc): ?>
  <?php
    $plants       = get_plants_for_location((int)$loc['id']);
    $last_water   = get_last_watered((int)$loc['id']);
    $last_fert    = get_last_fertilized((int)$loc['id']);
    $total_gal    = get_total_gallons_watered((int)$loc['id']);
  ?>
  <div class="col-md-6 col-xl-4">
    <div class="card h-100 shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">
          <i class="bi bi-<?= $loc['type']==='indoor' ? 'house' : 'tree' ?>"></i>
          <?= h($loc['name']) ?>
        </span>
        <?= location_type_badge($loc['type']) ?>
      </div>
      <div class="card-body">
        <?php if ($loc['area_sqin']): ?>
          <div class="small text-muted mb-2">
            <i class="bi bi-aspect-ratio"></i> <?= h(format_area((float)$loc['area_sqin'])) ?>
          </div>
        <?php endif; ?>

        <div class="row g-2 mb-3 text-center">
          <div class="col-4">
            <div class="fw-bold fs-5 text-success"><?= count($plants) ?></div>
            <div class="small text-muted">Plants</div>
          </div>
          <div class="col-4">
            <div class="fw-bold fs-5"><?= number_format($total_gal, 1) ?></div>
            <div class="small text-muted">Gal. total</div>
          </div>
          <div class="col-4">
            <div class="fw-bold fs-5">
              <?= $last_fert ? date('M j', strtotime($last_fert['fertilized_at'])) : '—' ?>
            </div>
            <div class="small text-muted">Last fert.</div>
          </div>
        </div>

        <?php if ($last_water): ?>
          <div class="small text-muted mb-1">
            <i class="bi bi-droplet-fill text-primary"></i>
            Watered <?= h(date('M j', strtotime($last_water['watered_at']))) ?>
            — <?= h(number_format((float)$last_water['gallons'], 1)) ?> gal.
          </div>
        <?php endif; ?>

        <?php if ($loc['notes']): ?>
          <p class="small text-muted mt-2 mb-0"><?= h($loc['notes']) ?></p>
        <?php endif; ?>
      </div>
      <div class="card-footer bg-transparent d-flex flex-wrap gap-1">
        <a href="index.php?page=location_detail&id=<?= $loc['id'] ?>"
           class="btn btn-sm btn-outline-success flex-grow-1">
          <i class="bi bi-eye"></i> View
        </a>
        <a href="index.php?page=location_form&id=<?= $loc['id'] ?>"
           class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-pencil"></i>
        </a>
        <!-- Quick log water -->
        <button class="btn btn-sm btn-outline-primary"
                data-bs-toggle="modal"
                data-bs-target="#waterModal<?= $loc['id'] ?>">
          <i class="bi bi-droplet"></i>
        </button>
        <!-- Quick log fertilizer -->
        <button class="btn btn-sm btn-outline-warning"
                data-bs-toggle="modal"
                data-bs-target="#fertModal<?= $loc['id'] ?>">
          <i class="bi bi-flower3"></i>
        </button>
        <!-- Delete -->
        <form method="post" action="index.php" class="d-inline"
              onsubmit="return confirm('Delete <?= h(addslashes($loc['name'])) ?> and all its plants?')">
          <input type="hidden" name="action"       value="delete_location">
          <input type="hidden" name="csrf_token"   value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="location_id"  value="<?= $loc['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash"></i>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Water modal -->
  <div class="modal fade" id="waterModal<?= $loc['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <form method="post" action="index.php">
          <input type="hidden" name="action"      value="log_watering">
          <input type="hidden" name="csrf_token"  value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
          <input type="hidden" name="redirect_to" value="locations">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-droplet"></i> Log Watering</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Date &amp; Time</label>
              <input type="datetime-local" name="watered_at" class="form-control"
                     value="<?= date('Y-m-d\TH:i') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Gallons</label>
              <input type="number" name="gallons" class="form-control"
                     step="0.1" min="0.1" placeholder="e.g. 2.5" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Notes</label>
              <input type="text" name="notes" class="form-control" placeholder="Optional">
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Fertilizer modal -->
  <div class="modal fade" id="fertModal<?= $loc['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <form method="post" action="index.php">
          <input type="hidden" name="action"      value="log_fertilizer">
          <input type="hidden" name="csrf_token"  value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
          <input type="hidden" name="redirect_to" value="locations">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-flower3"></i> Log Fertilizer</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Date</label>
              <input type="date" name="fertilized_at" class="form-control"
                     value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Product</label>
              <input type="text" name="product" class="form-control"
                     placeholder="e.g. 10-10-10, Tomato-tone">
            </div>
            <div class="mb-2">
              <label class="form-label">Notes</label>
              <input type="text" name="notes" class="form-control" placeholder="Optional">
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-warning">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php endforeach; ?>
</div>
<?php endif; ?>
