<?php
$all_plants = get_all_plants_for_year($year_id);
$locations  = get_locations_for_year($year_id);

// Group by location
$by_location = [];
foreach ($all_plants as $p) {
    $by_location[$p['location_id']][] = $p;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="h4 mb-0"><i class="bi bi-tree"></i> Plants — <?= h((string)$year_row['year']) ?></h2>
  <div class="d-flex gap-2">
    <a href="index.php?page=import" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-upload"></i> Import JSON
    </a>
    <a href="index.php?page=plant_form" class="btn btn-success btn-sm">
      <i class="bi bi-plus-lg"></i> Add Plant
    </a>
  </div>
</div>

<?php if (!$all_plants): ?>
  <div class="alert alert-info">
    <i class="bi bi-seedling"></i>
    No plants yet.
    <?php if (!$locations): ?>
      <a href="index.php?page=location_form" class="alert-link">Add a location first</a>, then add plants to it.
    <?php else: ?>
      <a href="index.php?page=plant_form" class="alert-link">Add your first plant.</a>
    <?php endif; ?>
  </div>
<?php else: ?>

<?php foreach ($locations as $loc): ?>
  <?php
    $plants = $by_location[(int)$loc['id']] ?? [];
    if (!$plants) continue;
  ?>
  <div class="mb-4">
    <h5 class="text-muted mb-2">
      <i class="bi bi-<?= $loc['type']==='indoor' ? 'house' : 'tree' ?>"></i>
      <?= h($loc['name']) ?>
      <?= location_type_badge($loc['type']) ?>
      <a href="index.php?page=location_detail&id=<?= $loc['id'] ?>"
         class="btn btn-sm btn-link py-0">view location</a>
    </h5>
    <div class="row g-3">
      <?php foreach ($plants as $p): ?>
      <?php
        $today = date('Y-m-d');
        $is_active = $p['sow_date'] <= $today && $p['harvest_date'] >= $today;
        $is_future = $p['sow_date'] > $today;
      ?>
      <div class="col-sm-6 col-xl-4">
        <div class="card h-100 shadow-sm <?= $is_active ? 'border-success' : ($is_future ? '' : 'opacity-75') ?>">
          <?php if ($p['photo']): ?>
            <img src="<?= h(UPLOADS_URL . '/' . $p['photo']) ?>"
                 class="card-img-top" style="height:140px;object-fit:cover" alt="">
          <?php endif; ?>
          <div class="card-body">
            <h6 class="card-title mb-1">
              <?= h($p['name']) ?>
              <?php if ($is_active): ?>
                <span class="badge bg-success ms-1">Active</span>
              <?php elseif ($is_future): ?>
                <span class="badge bg-secondary ms-1">Upcoming</span>
              <?php else: ?>
                <span class="badge bg-light text-dark ms-1">Past</span>
              <?php endif; ?>
            </h6>
            <?php if ($p['variety']): ?>
              <div class="small text-muted mb-2"><?= h($p['variety']) ?></div>
            <?php endif; ?>
            <dl class="row small mb-0">
              <dt class="col-5">Sown</dt>
              <dd class="col-7"><?= h(date_to_display($p['sow_date'])) ?></dd>
              <?php if ($p['transplant_date']): ?>
              <dt class="col-5">Transplant</dt>
              <dd class="col-7"><?= h(date_to_display($p['transplant_date'])) ?></dd>
              <?php endif; ?>
              <dt class="col-5">Harvest</dt>
              <dd class="col-7"><?= h(date_to_display($p['harvest_date'])) ?></dd>
              <dt class="col-5 text-muted">Frost warn</dt>
              <dd class="col-7 text-muted">&lt; <?= h(format_temp((float)$p['frost_temp_f'])) ?></dd>
              <dt class="col-5 text-muted">Heat warn</dt>
              <dd class="col-7 text-muted">&gt; <?= h(format_temp((float)$p['max_heat_temp_f'])) ?></dd>
              <dt class="col-5 text-muted">Rain warn</dt>
              <dd class="col-7 text-muted">&lt; <?= h(format_rain((float)$p['min_weekly_rain_in'])) ?>/wk</dd>
            </dl>
          </div>
          <div class="card-footer bg-transparent d-flex gap-1">
            <a href="index.php?page=plant_form&id=<?= $p['id'] ?>"
               class="btn btn-sm btn-outline-secondary flex-grow-1">
              <i class="bi bi-pencil"></i> Edit
            </a>
            <form method="post" action="index.php" class="d-inline"
                  onsubmit="return confirm('Delete <?= h(addslashes($p['name'])) ?>?')">
              <input type="hidden" name="action"     value="delete_plant">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="plant_id"   value="<?= $p['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php endif; ?>
