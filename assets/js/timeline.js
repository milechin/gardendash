/**
 * Garden Dashboard — Timeline Renderer
 * Reads data attributes from #timeline-container and .plant-row elements,
 * then builds the visual timeline with Bootstrap tooltips.
 */
document.addEventListener('DOMContentLoaded', function () {
  const container = document.getElementById('timeline-container');
  if (!container) return;

  const yearStart = new Date(container.dataset.yearStart + 'T00:00:00');
  const yearEnd   = new Date(container.dataset.yearEnd   + 'T00:00:00');
  const totalMs   = yearEnd - yearStart;

  // ── Helpers ──────────────────────────────────────────────────────────────

  function pct(date) {
    const ms = new Date(date + 'T00:00:00') - yearStart;
    return Math.max(0, Math.min(100, (ms / totalMs) * 100));
  }

  function widthPct(from, to) {
    return Math.max(0.5, pct(to) - pct(from));
  }

  // ── Month header ─────────────────────────────────────────────────────────

  const header = document.getElementById('timeline-header');
  if (header) {
    const months = [
      'Jan','Feb','Mar','Apr','May','Jun',
      'Jul','Aug','Sep','Oct','Nov','Dec'
    ];
    const y = yearStart.getFullYear();
    for (let m = 0; m < 12; m++) {
      const d = new Date(y, m, 1);
      const left = pct(d.toISOString().slice(0, 10));
      const label = document.createElement('span');
      label.className = 'timeline-month-label';
      label.style.left = left + '%';
      label.textContent = months[m];
      header.appendChild(label);
    }
  }

  // ── Today marker (inside each .timeline-track) ────────────────────────────

  const todayStr = new Date().toISOString().slice(0, 10);
  const todayPct = pct(todayStr);

  // ── Plant rows ────────────────────────────────────────────────────────────

  const rows = container.querySelectorAll('.plant-row');
  rows.forEach(function (row) {
    const sow        = row.dataset.sow;
    const harvest    = row.dataset.harvest;
    const transplant = row.dataset.transplant || '';
    const locType    = row.dataset.locationType || 'outdoor';
    const name       = row.dataset.name || '';
    const sowDisplay      = row.dataset.sowDisplay      || sow;
    const harvestDisplay  = row.dataset.harvestDisplay  || harvest;
    const transplantDisp  = row.dataset.transplantDisplay || '';

    if (!sow || !harvest) return;

    const track = row.querySelector('.timeline-track');
    if (!track) return;

    // Today marker on this track
    const todayEl = document.createElement('div');
    todayEl.className = 'today-marker';
    todayEl.style.left = todayPct + '%';
    track.appendChild(todayEl);

    if (transplant) {
      // Two-segment bar: indoor (sow→transplant) then outdoor (transplant→harvest)
      const bar1 = buildBar(sow, transplant, 'bar-indoor', name,
        'Sown: ' + sowDisplay + ' → Transplant: ' + transplantDisp);
      const bar2 = buildBar(transplant, harvest, 'bar-outdoor', '',
        'Transplant: ' + transplantDisp + ' → Harvest: ' + harvestDisplay);

      track.appendChild(bar1);
      track.appendChild(bar2);

      // Transplant marker diamond
      const diamond = document.createElement('div');
      diamond.className = 'transplant-marker';
      diamond.style.left = pct(transplant) + '%';
      diamond.title = 'Transplant: ' + transplantDisp;
      track.appendChild(diamond);
    } else {
      // Single bar
      const cls  = locType === 'indoor' ? 'bar-indoor' : 'bar-outdoor';
      const tip  = 'Sown: ' + sowDisplay + ' → Harvest: ' + harvestDisplay;
      const bar  = buildBar(sow, harvest, cls, name, tip);
      track.appendChild(bar);
    }
  });

  // ── Bootstrap tooltip init ────────────────────────────────────────────────

  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    document.querySelectorAll('.plant-bar[title], .transplant-marker[title]')
      .forEach(function (el) {
        new bootstrap.Tooltip(el, { placement: 'top', trigger: 'hover' });
      });
  }

  // ── Helper: build a single bar element ────────────────────────────────────

  function buildBar(fromDate, toDate, cls, labelText, tooltip) {
    const bar = document.createElement('div');
    bar.className = 'plant-bar ' + cls;
    bar.style.left  = pct(fromDate) + '%';
    bar.style.width = widthPct(fromDate, toDate) + '%';
    if (tooltip) bar.title = tooltip;

    if (labelText) {
      const lbl = document.createElement('span');
      lbl.className   = 'bar-label';
      lbl.textContent = labelText;
      bar.appendChild(lbl);
    }
    return bar;
  }
});
