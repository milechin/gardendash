<?php
$all_plants = get_all_plants_for_year($year_id);
$locations  = get_locations_for_year($year_id);

$year_val   = (int)$year_row['year'];
$year_start = $year_val . '-01-01';
$year_end   = $year_val . '-12-31';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="h4 mb-0">
    <i class="bi bi-bar-chart-steps"></i> Growing Season Timeline — <?= h((string)$year_val) ?>
  </h2>
  <div class="d-flex gap-2 align-items-center">
    <span class="small text-muted d-flex gap-3">
      <span><span class="badge" style="background:#4a90d9">&nbsp;</span> Indoor</span>
      <span><span class="badge" style="background:#4caf50">&nbsp;</span> Outdoor</span>
      <span><span class="badge" style="background:#ff5722">&nbsp;</span> Transplant →</span>
    </span>
  </div>
</div>

<?php if (!$all_plants): ?>
  <div class="alert alert-info">
    <i class="bi bi-bar-chart-steps"></i>
    No plants to display. <a href="index.php?page=plant_form">Add plants</a> to see the timeline.
  </div>
<?php else: ?>

<div class="card shadow-sm">
  <div class="card-body p-3">
    <div id="timeline-container"
         data-year-start="<?= h($year_start) ?>"
         data-year-end="<?= h($year_end) ?>">

      <!-- Month header injected by JS -->
      <div id="timeline-header" class="timeline-header mb-1"></div>

      <?php
        $current_loc = null;
        foreach ($all_plants as $plant):
          $loc_id = $plant['location_id'];
          if ($loc_id !== $current_loc):
            $current_loc = $loc_id;
      ?>
      <!-- Location group label -->
      <div class="timeline-location-label">
        <i class="bi bi-<?= $plant['location_type']==='indoor' ? 'house' : 'tree' ?>"></i>
        <?= h($plant['location_name']) ?>
        <?= location_type_badge($plant['location_type']) ?>
      </div>
      <?php endif; ?>

      <div class="plant-row mb-2"
           data-sow="<?= h($plant['sow_date']) ?>"
           data-harvest="<?= h($plant['harvest_date']) ?>"
           data-transplant="<?= h($plant['transplant_date'] ?? '') ?>"
           data-location-type="<?= h($plant['location_type']) ?>"
           data-name="<?= h($plant['name'] . ($plant['variety'] ? ' — ' . $plant['variety'] : '')) ?>"
           data-sow-display="<?= h(date_to_display($plant['sow_date'])) ?>"
           data-harvest-display="<?= h(date_to_display($plant['harvest_date'])) ?>"
           data-transplant-display="<?= h($plant['transplant_date'] ? date_to_display($plant['transplant_date']) : '') ?>"
           <?php if ($plant['photo']): ?>
           data-photo="<?= h(UPLOADS_URL . '/' . $plant['photo']) ?>"
           <?php endif; ?>
           >
        <div class="plant-label text-truncate" title="<?= h($plant['name']) ?>">
          <?php if ($plant['photo']): ?>
            <img src="<?= h(UPLOADS_URL . '/' . $plant['photo']) ?>"
                 width="20" height="20" class="rounded me-1"
                 style="object-fit:cover;vertical-align:middle" alt="">
          <?php endif; ?>
          <?= h($plant['name']) ?>
          <?php if ($plant['variety']): ?>
            <small class="text-muted"><?= h($plant['variety']) ?></small>
          <?php endif; ?>
        </div>
        <div class="timeline-track">
          <!-- Bar injected by timeline.js -->
        </div>
      </div>

      <?php endforeach; ?>

      <!-- Today marker injected by JS -->
    </div>
  </div>
</div>

<div class="mt-3 text-muted small">
  <i class="bi bi-info-circle"></i>
  Blue = indoor phase · Green = outdoor phase · Diamond ◆ = transplant date · Red line = today
</div>

<?php endif; ?>
