<?php
if (empty($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    redirect('index.php?page=locations');
}
$loc = get_location_by_id((int)$_GET['id']);
if (!$loc) {
    flash_set('error', 'Location not found.');
    redirect('index.php?page=locations');
}
$loc_id    = (int)$loc['id'];
$plants    = get_plants_for_location($loc_id);
$water_log = get_watering_log($loc_id);
$fert_log  = get_fertilizer_log($loc_id);
$total_gal = get_total_gallons_watered($loc_id);
?>

<!-- Header -->
<div class="d-flex flex-wrap gap-2 align-items-center mb-4">
  <a href="index.php?page=locations" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h2 class="h4 mb-0 me-auto">
    <i class="bi bi-<?= $loc['type']==='indoor' ? 'house' : 'tree' ?>"></i>
    <?= h($loc['name']) ?>
    <?= location_type_badge($loc['type']) ?>
  </h2>
  <a href="index.php?page=location_form&id=<?= $loc_id ?>"
     class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-pencil"></i> Edit
  </a>
</div>

<?php if ($loc['area_sqin'] || $loc['notes']): ?>
<div class="alert alert-light border mb-4">
  <?php if ($loc['area_sqin']): ?>
    <i class="bi bi-aspect-ratio"></i>
    Area: <strong><?= h(format_area((float)$loc['area_sqin'])) ?></strong>
  <?php endif; ?>
  <?php if ($loc['notes']): ?>
    <?php if ($loc['area_sqin']): ?> &mdash; <?php endif; ?>
    <?= h($loc['notes']) ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-4">

<!-- Plants column -->
<div class="col-lg-7">
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-tree"></i> Plants <span class="badge bg-secondary"><?= count($plants) ?></span></span>
      <a href="index.php?page=plant_form&location_id=<?= $loc_id ?>"
         class="btn btn-sm btn-success">
        <i class="bi bi-plus-lg"></i> Add Plant
      </a>
    </div>
    <div class="card-body p-0">
      <?php if (!$plants): ?>
        <div class="p-3 text-muted small">No plants yet.
          <a href="index.php?page=plant_form&location_id=<?= $loc_id ?>">Add one.</a>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th class="ps-3">Plant</th>
              <th>Sow</th>
              <th>Transplant</th>
              <th>Harvest</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($plants as $p): ?>
            <tr>
              <td class="ps-3">
                <?php if ($p['photo']): ?>
                  <img src="<?= h(UPLOADS_URL . '/' . $p['photo']) ?>"
                       width="36" height="36" class="rounded me-2"
                       style="object-fit:cover" alt="">
                <?php endif; ?>
                <a href="index.php?page=plant_form&id=<?= $p['id'] ?>">
                  <?= h($p['name']) ?>
                </a>
                <?php if ($p['variety']): ?>
                  <small class="text-muted d-block"><?= h($p['variety']) ?></small>
                <?php endif; ?>
              </td>
              <td class="small"><?= h(date_to_display($p['sow_date'])) ?></td>
              <td class="small"><?= $p['transplant_date'] ? h(date_to_display($p['transplant_date'])) : '—' ?></td>
              <td class="small"><?= h(date_to_display($p['harvest_date'])) ?></td>
              <td>
                <form method="post" action="index.php" class="d-inline"
                      onsubmit="return confirm('Delete <?= h(addslashes($p['name'])) ?>?')">
                  <input type="hidden" name="action"      value="delete_plant">
                  <input type="hidden" name="csrf_token"  value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="plant_id"    value="<?= $p['id'] ?>">
                  <input type="hidden" name="redirect_to" value="location_detail&id=<?= $loc_id ?>">
                  <button class="btn btn-sm btn-link text-danger p-0">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Logs column -->
<div class="col-lg-5">

  <!-- Watering log -->
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-droplet-fill text-primary"></i> Watering Log</span>
      <span class="badge bg-primary"><?= number_format($total_gal, 1) ?> gal total</span>
    </div>
    <div class="card-body p-0">
      <?php if ($water_log): ?>
      <div class="table-responsive" style="max-height:220px;overflow-y:auto">
        <table class="table table-sm mb-0">
          <tbody>
            <?php foreach ($water_log as $w): ?>
            <tr>
              <td class="ps-3 small"><?= h(date('M j, Y g:ia', strtotime($w['watered_at']))) ?></td>
              <td class="small"><?= h(number_format((float)$w['gallons'], 1)) ?> gal</td>
              <td class="small text-muted"><?= h($w['notes'] ?? '') ?></td>
              <td>
                <form method="post" action="index.php" class="d-inline">
                  <input type="hidden" name="action"      value="delete_watering">
                  <input type="hidden" name="csrf_token"  value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="log_id"      value="<?= $w['id'] ?>">
                  <input type="hidden" name="redirect_to" value="location_detail&id=<?= $loc_id ?>">
                  <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="p-3 text-muted small">No watering events recorded.</div>
      <?php endif; ?>
    </div>
    <div class="card-footer">
      <form method="post" action="index.php" class="row g-2">
        <input type="hidden" name="action"      value="log_watering">
        <input type="hidden" name="csrf_token"  value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="location_id" value="<?= $loc_id ?>">
        <input type="hidden" name="redirect_to" value="location_detail&id=<?= $loc_id ?>">
        <div class="col-6">
          <input type="datetime-local" name="watered_at" class="form-control form-control-sm"
                 value="<?= date('Y-m-d\TH:i') ?>" required>
        </div>
        <div class="col-3">
          <input type="number" name="gallons" class="form-control form-control-sm"
                 step="0.1" min="0.1" placeholder="Gal." required>
        </div>
        <div class="col-3">
          <button class="btn btn-sm btn-primary w-100">Log</button>
        </div>
        <div class="col-12">
          <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes (optional)">
        </div>
      </form>
    </div>
  </div>

  <!-- Fertilizer log -->
  <div class="card shadow-sm">
    <div class="card-header">
      <i class="bi bi-flower3 text-warning"></i> Fertilizer Log
    </div>
    <div class="card-body p-0">
      <?php if ($fert_log): ?>
      <div class="table-responsive" style="max-height:220px;overflow-y:auto">
        <table class="table table-sm mb-0">
          <tbody>
            <?php foreach ($fert_log as $f): ?>
            <tr>
              <td class="ps-3 small"><?= h(date('M j, Y', strtotime($f['fertilized_at']))) ?></td>
              <td class="small"><?= h($f['product'] ?? '—') ?></td>
              <td class="small text-muted"><?= h($f['notes'] ?? '') ?></td>
              <td>
                <form method="post" action="index.php" class="d-inline">
                  <input type="hidden" name="action"      value="delete_fertilizer">
                  <input type="hidden" name="csrf_token"  value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="log_id"      value="<?= $f['id'] ?>">
                  <input type="hidden" name="redirect_to" value="location_detail&id=<?= $loc_id ?>">
                  <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="p-3 text-muted small">No fertilizer events recorded.</div>
      <?php endif; ?>
    </div>
    <div class="card-footer">
      <form method="post" action="index.php" class="row g-2">
        <input type="hidden" name="action"      value="log_fertilizer">
        <input type="hidden" name="csrf_token"  value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="location_id" value="<?= $loc_id ?>">
        <input type="hidden" name="redirect_to" value="location_detail&id=<?= $loc_id ?>">
        <div class="col-5">
          <input type="date" name="fertilized_at" class="form-control form-control-sm"
                 value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-4">
          <input type="text" name="product" class="form-control form-control-sm"
                 placeholder="Product">
        </div>
        <div class="col-3">
          <button class="btn btn-sm btn-warning w-100">Log</button>
        </div>
        <div class="col-12">
          <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes (optional)">
        </div>
      </form>
    </div>
  </div>

</div><!-- /col -->
</div><!-- /row -->
