<?php
require_once __DIR__ . '/db.php';

function get_plants_for_location(int $location_id): array {
    $stmt = get_db()->prepare(
        "SELECT * FROM plants WHERE location_id = ? ORDER BY sow_date ASC"
    );
    $stmt->execute([$location_id]);
    return $stmt->fetchAll();
}

/**
 * Get all plants for a year by joining through locations.
 * Returns plants augmented with location name and type.
 */
function get_all_plants_for_year(int $year_id): array {
    $stmt = get_db()->prepare("
        SELECT p.*, l.name AS location_name, l.type AS location_type
        FROM plants p
        JOIN locations l ON l.id = p.location_id
        WHERE l.year_id = ?
        ORDER BY l.name ASC, p.sow_date ASC
    ");
    $stmt->execute([$year_id]);
    return $stmt->fetchAll();
}

/**
 * Active plants: sow_date <= today <= harvest_date, for a given year.
 */
function get_active_plants_for_year(int $year_id): array {
    $today = date('Y-m-d');
    $stmt  = get_db()->prepare("
        SELECT p.*, l.name AS location_name, l.type AS location_type
        FROM plants p
        JOIN locations l ON l.id = p.location_id
        WHERE l.year_id = ?
          AND p.sow_date    <= ?
          AND p.harvest_date >= ?
        ORDER BY p.sow_date ASC
    ");
    $stmt->execute([$year_id, $today, $today]);
    return $stmt->fetchAll();
}

function get_plant_by_id(int $id): ?array {
    $stmt = get_db()->prepare(
        "SELECT p.*, l.name AS location_name, l.type AS location_type, l.year_id
         FROM plants p JOIN locations l ON l.id = p.location_id
         WHERE p.id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function save_plant(array $data): int {
    $pdo = get_db();
    $fields = [
        ':location_id'        => (int)$data['location_id'],
        ':name'               => $data['name'],
        ':variety'            => $data['variety'] ?? null,
        ':sow_date'           => $data['sow_date'],
        ':harvest_date'       => $data['harvest_date'],
        ':transplant_date'    => $data['transplant_date'] ?: null,
        ':frost_temp_f'       => isset($data['frost_temp_f'])      ? (float)$data['frost_temp_f']      : 36.0,
        ':max_heat_temp_f'    => isset($data['max_heat_temp_f'])   ? (float)$data['max_heat_temp_f']   : 95.0,
        ':min_weekly_rain_in' => isset($data['min_weekly_rain_in'])? (float)$data['min_weekly_rain_in']: 0.5,
        ':notes'              => $data['notes'] ?? null,
    ];

    if (!empty($data['id'])) {
        $stmt = $pdo->prepare("
            UPDATE plants SET
                location_id        = :location_id,
                name               = :name,
                variety            = :variety,
                sow_date           = :sow_date,
                harvest_date       = :harvest_date,
                transplant_date    = :transplant_date,
                frost_temp_f       = :frost_temp_f,
                max_heat_temp_f    = :max_heat_temp_f,
                min_weekly_rain_in = :min_weekly_rain_in,
                notes              = :notes,
                updated_at         = datetime('now')
            WHERE id = :id
        ");
        $fields[':id'] = (int)$data['id'];
        $stmt->execute($fields);
        return (int)$data['id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO plants
            (location_id, name, variety, sow_date, harvest_date, transplant_date,
             frost_temp_f, max_heat_temp_f, min_weekly_rain_in, notes)
        VALUES
            (:location_id, :name, :variety, :sow_date, :harvest_date, :transplant_date,
             :frost_temp_f, :max_heat_temp_f, :min_weekly_rain_in, :notes)
    ");
    $stmt->execute($fields);
    return (int)$pdo->lastInsertId();
}

function delete_plant(int $id): void {
    $stmt = get_db()->prepare("DELETE FROM plants WHERE id = ?");
    $stmt->execute([$id]);
}

function set_plant_photo(int $id, ?string $filename): void {
    $stmt = get_db()->prepare("UPDATE plants SET photo = ? WHERE id = ?");
    $stmt->execute([$filename, $id]);
}
