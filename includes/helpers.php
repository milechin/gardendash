<?php
/**
 * Escape output for safe HTML rendering.
 */
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * PRG redirect — always exits.
 */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/**
 * Format ISO date (YYYY-MM-DD) as human-readable string.
 */
function date_to_display(?string $iso): string {
    if (!$iso) return '—';
    $d = DateTime::createFromFormat('Y-m-d', $iso);
    return $d ? $d->format('M j, Y') : $iso;
}

/**
 * Format temperature in °F.
 */
function format_temp(float $f): string {
    return round($f, 1) . '°F';
}

/**
 * Format precipitation in inches.
 */
function format_rain(float $in): string {
    return number_format($in, 2) . '"';
}

/**
 * Return or generate the session CSRF token.
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify POST CSRF token; redirect with error on failure.
 */
function csrf_verify(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        flash_set('error', 'Invalid security token. Please try again.');
        redirect('index.php');
    }
}

/**
 * Store a flash message in the session.
 */
function flash_set(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message.
 */
function flash_get(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Get the active year_id from GET param or session.
 */
function active_year_id(): int {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_GET['year_id']) && ctype_digit((string)$_GET['year_id'])) {
        $_SESSION['active_year_id'] = (int)$_GET['year_id'];
    }
    return (int)($_SESSION['active_year_id'] ?? 0);
}

/**
 * Set the active year_id in the session.
 */
function set_active_year_id(int $id): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['active_year_id'] = $id;
}

/**
 * Convert a string to a safe filename slug.
 */
function slugify(string $text): string {
    $text = mb_strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '_', $text);
    return trim($text, '_') ?: 'file';
}

/**
 * Days between two YYYY-MM-DD date strings (always positive).
 */
function days_between(string $date1, string $date2): int {
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    return (int)abs((int)$d1->diff($d2)->days);
}

/**
 * Convert square inches to a readable string.
 */
function format_area(?float $sqin): string {
    if ($sqin === null) return '—';
    if ($sqin >= 144) {
        return number_format($sqin / 144, 1) . ' ft²';
    }
    return number_format($sqin, 0) . ' in²';
}

/**
 * Render a Bootstrap badge for location type.
 */
function location_type_badge(string $type): string {
    $color = $type === 'indoor' ? 'primary' : 'success';
    $label = ucfirst($type);
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

/**
 * Severity color for NOAA alerts.
 */
function alert_severity_class(string $severity): string {
    return match (strtolower($severity)) {
        'extreme', 'severe' => 'danger',
        'moderate'          => 'warning',
        default             => 'info',
    };
}
