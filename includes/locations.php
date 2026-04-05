<?php
require_once __DIR__ . '/db.php';

// ── Locations ────────────────────────────────────────────────────────────────

function get_locations_for_year(int $year_id): array {
    $stmt = get_db()->prepare("SELECT * FROM locations WHERE year_id = ? ORDER BY name ASC");
    $stmt->execute([$year_id]);
    return $stmt->fetchAll();
}

function get_location_by_id(int $id): ?array {
    $stmt = get_db()->prepare("SELECT * FROM locations WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function save_location(array $data): int {
    $pdo = get_db();
    if (!empty($data['id'])) {
        $stmt = $pdo->prepare("
            UPDATE locations
            SET name = :name, type = :type, area_sqin = :area_sqin,
                notes = :notes, updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'     => $data['name'],
            ':type'     => $data['type'],
            ':area_sqin'=> $data['area_sqin'] !== '' ? (float)$data['area_sqin'] : null,
            ':notes'    => $data['notes'] ?? null,
            ':id'       => (int)$data['id'],
        ]);
        return (int)$data['id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO locations (year_id, name, type, area_sqin, notes)
        VALUES (:year_id, :name, :type, :area_sqin, :notes)
    ");
    $stmt->execute([
        ':year_id'  => (int)$data['year_id'],
        ':name'     => $data['name'],
        ':type'     => $data['type'],
        ':area_sqin'=> isset($data['area_sqin']) && $data['area_sqin'] !== ''
                        ? (float)$data['area_sqin'] : null,
        ':notes'    => $data['notes'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

function delete_location(int $id): void {
    $stmt = get_db()->prepare("DELETE FROM locations WHERE id = ?");
    $stmt->execute([$id]);
}

// ── Watering Log ─────────────────────────────────────────────────────────────

function log_watering(int $location_id, string $watered_at, float $gallons, string $notes = ''): void {
    $stmt = get_db()->prepare("
        INSERT INTO watering_log (location_id, watered_at, gallons, notes)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$location_id, $watered_at, $gallons, $notes ?: null]);
}

function get_watering_log(int $location_id): array {
    $stmt = get_db()->prepare(
        "SELECT * FROM watering_log WHERE location_id = ? ORDER BY watered_at DESC"
    );
    $stmt->execute([$location_id]);
    return $stmt->fetchAll();
}

function get_last_watered(int $location_id): ?array {
    $stmt = get_db()->prepare(
        "SELECT * FROM watering_log WHERE location_id = ? ORDER BY watered_at DESC LIMIT 1"
    );
    $stmt->execute([$location_id]);
    return $stmt->fetch() ?: null;
}

function get_total_gallons_watered(int $location_id): float {
    $stmt = get_db()->prepare(
        "SELECT COALESCE(SUM(gallons), 0) FROM watering_log WHERE location_id = ?"
    );
    $stmt->execute([$location_id]);
    return (float)$stmt->fetchColumn();
}

function delete_watering_entry(int $id): void {
    $stmt = get_db()->prepare("DELETE FROM watering_log WHERE id = ?");
    $stmt->execute([$id]);
}

// ── Fertilizer Log ───────────────────────────────────────────────────────────

function log_fertilizer(int $location_id, string $fertilized_at, string $product = '', string $notes = ''): void {
    $stmt = get_db()->prepare("
        INSERT INTO fertilizer_log (location_id, fertilized_at, product, notes)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$location_id, $fertilized_at, $product ?: null, $notes ?: null]);
}

function get_fertilizer_log(int $location_id): array {
    $stmt = get_db()->prepare(
        "SELECT * FROM fertilizer_log WHERE location_id = ? ORDER BY fertilized_at DESC"
    );
    $stmt->execute([$location_id]);
    return $stmt->fetchAll();
}

function get_last_fertilized(int $location_id): ?array {
    $stmt = get_db()->prepare(
        "SELECT * FROM fertilizer_log WHERE location_id = ? ORDER BY fertilized_at DESC LIMIT 1"
    );
    $stmt->execute([$location_id]);
    return $stmt->fetch() ?: null;
}

function delete_fertilizer_entry(int $id): void {
    $stmt = get_db()->prepare("DELETE FROM fertilizer_log WHERE id = ?");
    $stmt->execute([$id]);
}
