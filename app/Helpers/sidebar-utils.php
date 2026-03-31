<?php
/**
 * Shared Sidebar Utilities
 *
 * Used by all role-specific sidebar components (IA, SA, FD).
 * Centralises logo path resolution and initials generation so the
 * same logic is not duplicated across sidebar.php files.
 */

/**
 * Resolve a tenant logo path to a fully-qualified URL.
 *
 * On production the web root IS the public/ directory, so stored
 * paths like "/uploads/logos/x.jpg" map directly to the domain root.
 * Any legacy "/public/" prefix must be stripped.
 *
 * @param  string|null  $storedPath  Value from session or DB (e.g. "/uploads/logos/x.jpg")
 * @param  string       $fallback    Relative path under APP_URL used when no logo is set
 * @return string  Absolute URL ready for use in <img src="...">
 */
function resolveLogoPath(?string $storedPath, string $fallback = '/assets/images/logo.png'): string
{
    if (empty($storedPath)) {
        return APP_URL . $fallback;
    }

    // Already an absolute URL — use as-is
    if (str_starts_with($storedPath, 'http')) {
        return $storedPath;
    }

    // Strip any legacy /public prefix — production web root IS public/
    if (str_starts_with($storedPath, '/public/')) {
        $storedPath = substr($storedPath, 7);
    }

    return APP_URL . $storedPath;
}

/**
 * Generate two-letter initials from a display name.
 *
 * @param  string  $name      Full name (e.g. "Sita Rai")
 * @param  string  $fallback  Returned when name is empty
 * @return string  Up to two uppercase letters (e.g. "SR")
 */
function generateInitials(string $name, string $fallback = 'AD'): string
{
    $name = trim($name);
    if (empty($name)) {
        return $fallback;
    }
    $parts = preg_split('/\s+/', $name);
    $first = strtoupper(substr($parts[0], 0, 1));
    $second = isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '';
    return $first . $second ?: $fallback;
}
