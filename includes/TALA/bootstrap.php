<?php

/**
 * Manual bootstrap for environments without a Composer autoloader
 * (e.g. Termux/Apache without `composer install`).
 *
 * Usage:
 *   require __DIR__ . '/TalaEngine/bootstrap.php';
 *
 *   $engine = new Tala\Engine\TalaEngine($source, $dest, $tables);
 *
 * If you do have Composer available, prefer a PSR-4 autoload entry
 * instead and skip this file:
 *   "autoload": { "psr-4": { "Tala\\Engine\\": "TalaEngine/src/" } }
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Exceptions/SyncException.php';
require_once __DIR__ . '/src/Exceptions/DependencyException.php';
require_once __DIR__ . '/src/Exceptions/ConfigurationException.php';
require_once __DIR__ . '/src/LoggerInterface.php';
require_once __DIR__ . '/src/SyncSession.php';
require_once __DIR__ . '/src/TalaEngine.php';
