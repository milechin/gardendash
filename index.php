<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/years.php';
require_once __DIR__ . '/includes/locations.php';
require_once __DIR__ . '/includes/plants.php';
require_once __DIR__ . '/includes/weather.php';

session_start();

// ── Bootstrap: ensure a year exists and set session defaults ──────────────────
$current_year_row = ensure_current_year_exists();

// If no active year is set yet, default to the current calendar year
if (empty($_SESSION['active_year_id'])) {
    set_active_year_id($current_year_row['id']);
}

// Allow switching year via GET param
if (isset($_GET['year_id']) && ctype_digit((string)$_GET['year_id'])) {
    $switch_row = get_year_by_id((int)$_GET['year_id']);
    if ($switch_row) {
        set_active_year_id((int)$switch_row['id']);
    }
    $clean = $_GET;
    unset($clean['year_id']);
    $qs = $clean ? '?' . http_build_query($clean) : '?page=' . ($clean['page'] ?? 'dashboard');
    redirect('index.php' . $qs);
}

$year_id  = active_year_id();
$year_row = get_year_by_id($year_id) ?? $current_year_row;
$all_years = get_all_years();

// ── POST action router (PRG pattern) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action_name = preg_replace('/[^a-z_]/', '', $_POST['action']);
    $action_file = __DIR__ . '/actions/' . $action_name . '.php';
    if (file_exists($action_file)) {
        require $action_file;
    }
    // Actions must always redirect; if they don't, fall through gracefully
    redirect('index.php');
}

// ── Page router ───────────────────────────────────────────────────────────────
$allowed_pages = [
    'dashboard', 'locations', 'location_form', 'location_detail',
    'plants', 'plant_form', 'timeline', 'settings', 'import',
];
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowed_pages, true)) {
    $page = 'dashboard';
}
$page_file = __DIR__ . '/pages/' . $page . '.php';

// ── Layout ────────────────────────────────────────────────────────────────────
$flash = flash_get();

// Page titles
$page_titles = [
    'dashboard'       => 'Dashboard',
    'locations'       => 'Locations',
    'location_form'   => 'Location',
    'location_detail' => 'Location Detail',
    'plants'          => 'Plants',
    'plant_form'      => 'Plant',
    'timeline'        => 'Timeline',
    'settings'        => 'Settings',
    'import'          => 'Import Plants',
];
$page_title = ($page_titles[$page] ?? ucfirst($page)) . ' — ' . APP_NAME;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($page_title) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">
      <i class="bi bi-flower1"></i> <?= h(APP_NAME) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link<?= $page==='dashboard' ? ' active' : '' ?>"
             href="index.php?page=dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $page==='locations'||$page==='location_form'||$page==='location_detail' ? ' active' : '' ?>"
             href="index.php?page=locations">
            <i class="bi bi-grid-3x3-gap"></i> Locations
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $page==='plants'||$page==='plant_form' ? ' active' : '' ?>"
             href="index.php?page=plants">
            <i class="bi bi-tree"></i> Plants
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $page==='timeline' ? ' active' : '' ?>"
             href="index.php?page=timeline">
            <i class="bi bi-bar-chart-steps"></i> Timeline
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $page==='import' ? ' active' : '' ?>"
             href="index.php?page=import">
            <i class="bi bi-upload"></i> Import
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= $page==='settings' ? ' active' : '' ?>"
             href="index.php?page=settings">
            <i class="bi bi-gear"></i> Settings
          </a>
        </li>
      </ul>

      <!-- Year selector -->
      <div class="d-flex align-items-center gap-2">
        <span class="text-white-50 small"><i class="bi bi-calendar3"></i> Year:</span>
        <div class="dropdown">
          <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
            <?= h((string)$year_row['year']) ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php foreach ($all_years as $yr): ?>
              <li>
                <a class="dropdown-item<?= $yr['id']==$year_id ? ' active' : '' ?>"
                   href="index.php?year_id=<?= $yr['id'] ?>&page=<?= h($page) ?>">
                  <?= h((string)$yr['year']) ?>
                </a>
              </li>
            <?php endforeach; ?>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="index.php" class="px-3 py-1 d-flex gap-1">
                <input type="hidden" name="action" value="save_year">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="number" name="year" class="form-control form-control-sm" style="width:80px"
                       placeholder="<?= date('Y') + 1 ?>" min="2000" max="2099" required>
                <button class="btn btn-success btn-sm">+</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- Main content -->
<main class="container-fluid py-4">

<?php if ($flash): ?>
  <?php $ft = $flash['type'] === 'error' ? 'danger' : h($flash['type']); ?>
  <div class="alert alert-<?= $ft ?> alert-dismissible fade show" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php
if (file_exists($page_file)) {
    require $page_file;
} else {
    echo '<div class="alert alert-danger">Page not found.</div>';
}
?>
</main>

<footer class="text-center text-muted small py-3 border-top mt-4">
  <?= h(APP_NAME) ?> v<?= h(APP_VERSION) ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/timeline.js"></script>
</body>
</html>
