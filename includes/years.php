<?php
require_once __DIR__ . '/db.php';

function get_all_years(): array {
    $stmt = get_db()->query("SELECT * FROM growing_years ORDER BY year DESC");
    return $stmt->fetchAll();
}

function get_year_by_id(int $id): ?array {
    $stmt = get_db()->prepare("SELECT * FROM growing_years WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_year_by_year(int $year): ?array {
    $stmt = get_db()->prepare("SELECT * FROM growing_years WHERE year = ?");
    $stmt->execute([$year]);
    return $stmt->fetch() ?: null;
}

function create_year(int $year, string $notes = ''): int {
    $pdo  = get_db();
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO growing_years (year, notes) VALUES (?, ?)");
    $stmt->execute([$year, $notes]);
    $existing = get_year_by_year($year);
    return $existing['id'];
}

/**
 * Ensure the current calendar year exists; return its row.
 * Also sets it as the active year if none is set in session.
 */
function ensure_current_year_exists(): array {
    $year = (int)date('Y');
    $id   = create_year($year);
    $row  = get_year_by_id($id);

    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['active_year_id'])) {
        $_SESSION['active_year_id'] = $id;
    }
    return $row;
}
