<?php
declare(strict_types=1);

/**
 * Backward-compatible loader for older code paths.
 *
 * The real configuration now lives in includes/config.php so the repository
 * can keep a single credential source and a separate safe template.
 */
return require __DIR__ . '/../config.php';
