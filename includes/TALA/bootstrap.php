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
require_once __DIR__ . '/src/Enums/OperationType.php';
require_once __DIR__ . '/src/Enums/ExecutionStatus.php';
require_once __DIR__ . '/src/Enums/TargetType.php';
require_once __DIR__ . '/src/Contracts/OperationHandlerInterface.php';
require_once __DIR__ . '/src/Handlers/CreateTableHandler.php';
require_once __DIR__ . '/src/Handlers/AddColumnHandler.php';
require_once __DIR__ . '/src/LoggerInterface.php';
require_once __DIR__ . '/src/SyncSession.php';
require_once __DIR__ . '/src/DataSnapshot.php';
require_once __DIR__ . '/src/DataInspector.php';
require_once __DIR__ . '/src/DataMerger.php';
require_once __DIR__ . '/src/DataExecutor.php';
require_once __DIR__ . '/src/SchemaInspector.php';
require_once __DIR__ . '/src/SchemaMerger.php';
require_once __DIR__ . '/src/MergeValidator.php';
require_once __DIR__ . '/src/PlanValidator.php';
require_once __DIR__ . '/src/ExecutionPlanBuilder.php';
require_once __DIR__ . '/src/SchemaExecutor.php';
require_once __DIR__ . '/src/DatabaseSnapshot.php';
require_once __DIR__ . '/src/EngineSession.php';
require_once __DIR__ . '/src/TalaEngine.php';
