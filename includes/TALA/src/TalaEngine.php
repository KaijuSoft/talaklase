<?php

declare(strict_types=1);

namespace Tala\Engine;

use PDO;
use Tala\Engine\Exceptions\ConfigurationException;
use Tala\Engine\Exceptions\DependencyException;
use Tala\Engine\Exceptions\SyncException;
use Throwable;

/**
 * TALA Engine — generic, application-agnostic database synchronization
 * middleware for the KaijuSoft ecosystem (TalaKlase, OJT Portal,
 * Inventory, Library, Payroll, and any future system).
 *
 * The engine knows nothing about any specific application's schema.
 * All behaviour is driven entirely by the $tables configuration array
 * passed to the constructor.
 *
 * --------------------------------------------------------------------
 * Architectural note — Synchronization Journal (Goal 8, roadmap v0.3)
 * --------------------------------------------------------------------
 * A future `_tala_sync_log` table (destination database) is intended
 * to record one row per row-level sync operation:
 *
 *   id              BIGINT       PK AUTO_INCREMENT
 *   session_uuid    CHAR(36)     -- SyncSession::$uuid
 *   table_name      VARCHAR(64)
 *   operation       ENUM('insert','modify','skip')
 *   business_key    JSON         -- business key column/value pairs
 *   before_state    JSON NULL    -- destination row before the operation
 *   after_state     JSON NULL    -- incoming row from the source
 *   created_at      DATETIME
 *
 * journal() will write these rows during a sync session. rollback()
 * will use the journal to reverse a specific session's effects.
 * restore() will replay a journal back into the destination. None of
 * this is implemented yet; the methods below exist only to reserve
 * the API surface (see Goal 8 and Goal 13).
 */
final class TalaEngine
{
    public const ENGINE_NAME = 'TALA Engine';
    public const VERSION = '0.2.0-alpha';
    public const AUTHOR = 'KaijuSoft';
    public const BUILD = '20260626';

    public const TYPE_REFERENCE = 'reference';
    public const TYPE_MASTER = 'master';
    public const TYPE_TRANSACTION = 'transaction';

    private const VALID_TYPES = [self::TYPE_REFERENCE, self::TYPE_MASTER, self::TYPE_TRANSACTION];

    private readonly PDO $source;
    private readonly PDO $destination;

    /** @var array<string, array<string, mixed>> */
    private readonly array $tables;

    private readonly ?LoggerInterface $logger;

    private ?EngineSession $engineSession = null;

    private readonly ConflictResolver $conflictResolver;

    /** @var array<int, string> Table sync order, resolved once at construction time. */
    private readonly array $syncOrder;

    /**
     * Typed properties cannot use the `callable` type, so the callback
     * is stored as a Closure (see onProgress()).
     *
     * @var (\Closure(string, array<string, mixed>): void)|null
     */
    private ?\Closure $progressCallback = null;

    /**
     * @param array<string, array<string, mixed>> $tables Table configuration, keyed by table name.
     */
    public function __construct(PDO $source, PDO $destination, array $tables, ?LoggerInterface $logger = null)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->tables = $tables;
        $this->logger = $logger;
        $this->conflictResolver = new ConflictResolver();

        $this->validateConfiguration();
        $this->syncOrder = $this->resolveDependencyOrder($this->tables);
    }

    /**
     * Engine metadata (Goal 3).
     *
     * @return array{name: string, version: string, author: string, build: string}
     */
    public static function about(): array
    {
        return [
            'name' => self::ENGINE_NAME,
            'version' => self::VERSION,
            'author' => self::AUTHOR,
            'build' => self::BUILD,
        ];
    }

    /**
     * Registers a callback invoked after each table finishes syncing.
     *
     * @param callable(string, array<string, mixed>): void $callback Receives (tableName, metrics).
     */
    public function onProgress(callable $callback): void
    {
        $this->progressCallback = $callback instanceof \Closure ? $callback : \Closure::fromCallable($callback);
    }

    /**
     * Synchronizes every configured table from source to destination,
     * in dependency order, and returns a SyncSession describing the
     * outcome (Goal 9).
     */
    public function syncDatabase(): SyncSession
    {
        $session = new SyncSession(
            self::generateUuid(),
            microtime(true),
            $this->describeConnection($this->source),
            $this->describeConnection($this->destination),
        );

	$health = $this->healthCheck();
		
		$failedChecks = [];

	foreach ($health['checks'] as $check) {

    if (!$check['status']) {

        $failedChecks[] =
            "{$check['name']}: {$check['message']}";

		}

	}
		
	if (!$health['status']) {

    $message = "Health Check failed.\n\n";

    if (!empty($failedChecks)) {

        $message .= "Failed Checks:\n";

        foreach ($failedChecks as $failure) {
            $message .= "• {$failure}\n";
        }

        $message .= "\n";
    }

    $message .= "Synchronization cancelled.";

    throw new SyncException($message);

}

        $total = count($this->syncOrder);

$current = 0;

foreach ($this->syncOrder as $tableName) {

    $current++;

    $metrics = $this->syncTable(
        $tableName,
        $this->tables[$tableName]
    );

    $session->addTableResult($metrics);

    $this->reportProgress(
        $tableName,
        $metrics,
        $current,
        $total
    );
}

        $session->finish(microtime(true));

        return $session;
    }

    /**
     * Returns the table sync order resolved at construction time
     * (Goal 5). Useful for diagnostics or pre-flight inspection
     * without running an actual sync.
     *
     * @return array<int, string>
     */
    public function getSyncOrder(): array
    {
        return $this->syncOrder;
    }

    /**
     * Runs the RC2 dry-run analysis pipeline without mutating either database.
     *
     * @return array<string, mixed>
     */
    public function analyzeSchema(): array
    {
        $session = (new EngineSession())
            ->setSourceDatabase($this->describeConnection($this->source))
            ->setDestinationDatabase($this->describeConnection($this->destination));

        $this->engineSession = $session;

        $health = $this->healthCheck();
        $session->setHealth($health);

        /* if (($health['status'] ?? false) !== true) {
            $session->finish();

            return array_merge([
                'status' => false,
                'stage' => 'health_check',
            ], $session->toArray());
        } */

        $sourceSnapshot = (new DatabaseSnapshot($this->source))->capture();
        $destinationSnapshot = (new DatabaseSnapshot($this->destination))->capture();
        $inspector = new SchemaInspector($this->source, $this->destination);
        $merger = new SchemaMerger();
        $validator = new MergeValidator();
		$planValidator = new PlanValidator();
		$planBuilder = new ExecutionPlanBuilder();

		
        $session
            ->setSnapshot([
                'source' => $sourceSnapshot,
                'destination' => $destinationSnapshot,
            ]);

		$inspection = $inspector->inspect(array_keys($this->tables));
		$session->setInspection($inspection);

		$mergePlan = $merger->buildPlan($inspection);
		$session->setMergePlan($mergePlan);

		$validatedPlan = $validator->validate($mergePlan);
		$validatedPlan = $planValidator->validate($validatedPlan);

		$executionPlan = $planBuilder->buildExecutionPlan($validatedPlan);

		$session
			->setValidation($validatedPlan)
			->setExecutionPlan($executionPlan)
			->setVerification([])
			->finish();

				return $session->toArray();
				
			}

    /**
     * Executes the previously built execution plan without rebuilding
     * it or re-running any planning stage.
     *
     * @return array<string, mixed>
     */
    public function executePlan(): array
    {
        if ($this->engineSession === null) {
            throw new SyncException('No execution plan is available. Run analyzeSchema() before executePlan().');
        }

        $executionPlan = $this->engineSession->getExecutionPlan();

        if ($executionPlan === []) {
            throw new SyncException('No execution plan is available in the current EngineSession.');
        }

        $executor = new SchemaExecutor($this->source, $this->destination);
        $executionResult = $executor->execute($executionPlan);

        $this->engineSession->setExecutionResult($executionResult);

        return $executionResult;
    }
	
	public function healthCheck(): array
{
    $checks = [];

    // Source database
    try {

        $this->source->query("SELECT 1");

        $checks[] = [
            'name'   => 'Source Database',
            'status' => true,
            'message'=> 'Connected'
        ];

    } catch (Throwable $e) {

        $checks[] = [
            'name'   => 'Source Database',
            'status' => false,
            'message'=> $e->getMessage()
        ];

    }

    // Destination database
    try {

        $this->destination->query("SELECT 1");

        $checks[] = [
            'name'   => 'Destination Database',
            'status' => true,
            'message'=> 'Connected'
        ];

    } catch (Throwable $e) {

        $checks[] = [
            'name'   => 'Destination Database',
            'status' => false,
            'message'=> $e->getMessage()
        ];

    }

   

// Verify configured tables

foreach (array_keys($this->tables) as $table) {

    try {

        $stmt = $this->source->query(
            "SHOW TABLES LIKE " . $this->source->quote($table)
        );

        $exists = $stmt->fetchColumn() !== false;

        $checks[] = [

            'name'    => "Source Table: {$table}",
            'status'  => $exists,
            'message' => $exists
                ? 'Exists'
                : 'Missing'

        ];

    } catch (Throwable $e) {

        $checks[] = [

            'name'    => "Source Table: {$table}",
            'status'  => false,
            'message' => $e->getMessage()

        ];

    }
	
}

// Verify destination tables

foreach (array_keys($this->tables) as $table) {

    try {

        $stmt = $this->destination->query(
            "SHOW TABLES LIKE " . $this->destination->quote($table)
        );

        $exists = $stmt->fetchColumn() !== false;

        $checks[] = [

            'name'    => "Destination Table: {$table}",
            'status'  => $exists,
            'message' => $exists
                ? 'Exists'
                : 'Missing'

        ];

    } catch (Throwable $e) {

        $checks[] = [

            'name'    => "Destination Table: {$table}",
            'status'  => false,
            'message' => $e->getMessage()

        ];

    }

}

// ======================================================
// Verify Business Keys
// Rule #001:
// Every MASTER and TRANSACTION table must define
// a business_key.
// ======================================================

foreach ($this->tables as $table => $config) {

    $type = $config['type'] ?? null;

    // Business Key is mandatory for MASTER and TRANSACTION tables.
    if (
        in_array(
            $type,
            [
                TalaEngine::TYPE_MASTER,
                TalaEngine::TYPE_TRANSACTION
            ],
            true
        ) &&
        empty($config['business_key'])
    ) {

        $checks[] = [

            'name'    => "Business Key: {$table}",

            'status'  => false,

            'message' => 'Missing business_key configuration'

        ];

        continue;

    }

    // Reference tables do not require business keys.
    if (empty($config['business_key'])) {
        continue;
    }

    foreach ($config['business_key'] as $column) {

        // ---------------------------------------------
        // Verify Source Column
        // ---------------------------------------------

        try {

            $stmt = $this->source->query(
                "SHOW COLUMNS FROM `{$table}` LIKE " .
                $this->source->quote($column)
            );

            $exists = $stmt->fetch() !== false;

            $checks[] = [

                'name'    => "Source Column: {$table}.{$column}",

                'status'  => $exists,

                'message' => $exists
                    ? 'Exists'
                    : 'Missing'

            ];

        } catch (Throwable $e) {

            $checks[] = [

                'name'    => "Source Column: {$table}.{$column}",

                'status'  => false,

                'message' => $e->getMessage()

            ];

        }

        // ---------------------------------------------
        // Verify Destination Column
        // ---------------------------------------------

        try {

            $stmt = $this->destination->query(
                "SHOW COLUMNS FROM `{$table}` LIKE " .
                $this->destination->quote($column)
            );

            $exists = $stmt->fetch() !== false;

            $checks[] = [

                'name'    => "Destination Column: {$table}.{$column}",

                'status'  => $exists,

                'message' => $exists
                    ? 'Exists'
                    : 'Missing'

            ];

        } catch (Throwable $e) {

            $checks[] = [

                'name'    => "Destination Column: {$table}.{$column}",

                'status'  => false,

                'message' => $e->getMessage()

            ];

        }

    }

}

		$passed = !array_filter(
    $checks,
    static fn ($check) => !$check['status']
);
		
		
    return [

        'status' => $passed,

        'checks' => $checks

    ];
	

}



    /**
     * Roadmap (v0.3): persist a synchronization journal entry for the
     * given session to `_tala_sync_log`. Not implemented — see the
     * class-level architectural note for Goal 8.
     */
    public function journal(SyncSession $session): void
    {
        throw new SyncException(
            'journal() is reserved for the v0.3 synchronization journal and is not implemented in v0.2.0-alpha.'
        );
    }

    /**
     * Roadmap (v0.3): reverse a previously committed sync session using
     * the synchronization journal. Not implemented.
     */
    public function rollback(string $sessionUuid): void
    {
        throw new SyncException(
            'rollback() is reserved for the v0.3 synchronization journal and is not implemented in v0.2.0-alpha.'
        );
    }

    /**
     * Roadmap (v0.3): restore destination state from a journaled sync
     * session. Not implemented.
     */
    public function restore(string $sessionUuid): void
    {
        throw new SyncException(
            'restore() is reserved for the v0.3 synchronization journal and is not implemented in v0.2.0-alpha.'
        );
    }

    /**
     * Synchronizes a single table inside its own transaction (Goal 1)
     * and returns its performance metrics (Goal 2). A failure rolls
     * back only this table's transaction; previously committed tables
     * in the same session are unaffected.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function syncTable(string $tableName, array $config): array
    {
        self::assertValidIdentifier($tableName);

        $start = microtime(true);

        $metrics = [
            'table' => $tableName,
            'strategy' => empty($config['business_key']) ? 'primary_key' : 'business_key',
            'rows_read' => 0,
            'inserted' => 0,
            'skipped' => 0,
            'modified' => 0,
            'failed' => 0,
            'duration_ms' => 0.0,
            'conflicts' => [],
        ];

        $sourceRows = $this->fetchSourceRows($tableName);
        $metrics['rows_read'] = count($sourceRows);

        $this->destination->beginTransaction();

        try {
            foreach ($sourceRows as $row) {
                $result = $this->mergeRow($tableName, $config, $row);

                switch ($result['status']) {
                    case 'inserted':
                        $metrics['inserted']++;
                        break;

                    case 'modified':
                        $metrics['modified']++;
                        $metrics['skipped']++;
                        $metrics['conflicts'][] = [
                            'table' => $tableName,
                            'business_key' => $result['business_key'],
                            'changed_fields' => $result['changed_fields'],
                        ];
                        break;

                    default: // 'duplicate'
                        $metrics['skipped']++;
                        break;
                }
            }

            $this->destination->commit();
        } catch (Throwable $e) {
            $this->destination->rollBack();

            // Partial inserts within one table must never occur (Goal 1):
            // the whole table's batch is treated as failed.
            $metrics['inserted'] = 0;
            $metrics['skipped'] = 0;
            $metrics['modified'] = 0;
            $metrics['conflicts'] = [];
            $metrics['failed'] = $metrics['rows_read'];

            $this->logger?->log('error', sprintf(
                'Synchronization failed for table "%s": %s',
                $tableName,
                $e->getMessage()
            ), ['table' => $tableName, 'exception' => $e]);
        }

        $metrics['duration_ms'] = round((microtime(true) - $start) * 1000, 3);

        return $metrics;
    }

    /**
     * Merges a single source row into the destination table using
     * Business Key matching (Goal 4) with Conflict Detection (Goal 6).
     * Existing rows are never updated or overwritten — only flagged.
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mergeRow(string $tableName, array $config, array $row): array
    {
        $existing = $this->findExistingRow($tableName, $config, $row);

        if ($existing === null) {
            $this->insertRow($tableName, $config ,$row);

            return ['status' => 'inserted'];
        }

        $ignore = $config['ignore'] ?? [];
        $primaryKey = $config['primary_key'] ?? null;

        $changedFields = $this->diffRows($existing, $row, $ignore, $primaryKey);

        if ($changedFields === []) {
            return ['status' => 'duplicate'];
        }

        $lookupColumns = $this->lookupColumns($config);

        return [
            'status' => 'modified',
            'business_key' => $this->extractColumnValues($lookupColumns, $row),
            'changed_fields' => $changedFields,
        ];
    }

    /**
     * Finds an existing destination row matching the source row's
     * Business Key (or Primary Key when no business key is configured).
     * Never relies exclusively on auto-increment IDs when a business
     * key is available (Goal 4).
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function findExistingRow(string $tableName, array $config, array $row): ?array
    {
        $columns = $this->lookupColumns($config);

        if ($columns === []) {
            return null;
        }

        foreach ($columns as $column) {
            self::assertValidIdentifier($column);
        }

        $conditions = array_map(static fn (string $c): string => "`{$c}` = :{$c}", $columns);

        $statement = $this->destination->prepare(sprintf(
            'SELECT * FROM `%s` WHERE %s LIMIT 1',
            $tableName,
            implode(' AND ', $conditions)
        ));

        foreach ($columns as $column) {
            $statement->bindValue(':' . $column, $row[$column] ?? null);
        }

        $statement->execute();

        $existing = $statement->fetch(PDO::FETCH_ASSOC);

        return $existing === false ? null : $existing;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function insertRow(string $tableName, array $config, array $row): void
    {
        $primaryKey = $config['primary_key'] ?? null;

	$columns = array_keys($row);

	if ($primaryKey !== null) {
		$columns = array_values(
			array_filter(
				$columns,
				static fn(string $column): bool => $column !== $primaryKey
			)
		);
	}

        foreach ($columns as $column) {
            self::assertValidIdentifier($column);
        }

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $tableName,
            implode(', ', array_map(static fn (string $c): string => "`{$c}`", $columns)),
            implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns))
        );

        $statement = $this->destination->prepare($sql);

			foreach ($columns as $column) {
		$statement->bindValue(
			':' . $column,
			$row[$column]
		);
}

        $statement->execute();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSourceRows(string $tableName): array
    {
        $statement = $this->source->prepare(sprintf('SELECT * FROM `%s`', $tableName));
        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows === false ? [] : $rows;
    }

    /**
     * Compares every non-ignored field (Goal 7) and the primary key
     * column is always excluded, since it may legitimately differ
     * between source and destination. Returns the list of differing
     * field names; an empty array means the rows are equivalent.
     *
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $incoming
     * @param array<int, string> $ignore
     * @return array<int, string>
     */
    private function diffRows(array $existing, array $incoming, array $ignore, ?string $primaryKey): array
    {
        $excluded = $ignore;

        if ($primaryKey !== null) {
            $excluded[] = $primaryKey;
        }

        $source = array_diff_key($incoming, array_flip($excluded));

        return $this->conflictResolver->detectChangedFields($source, $existing);
    }

    /**
     * Computes table synchronization order via topological sort over
     * each table's 'depends' list (Goal 5). Order is never hardcoded.
     *
     * @param array<string, array<string, mixed>> $tables
     * @return array<int, string>
     */
    private function resolveDependencyOrder(array $tables): array
    {
        $order = [];
        $visited = [];
        $visiting = [];

        foreach (array_keys($tables) as $tableName) {
            $this->visitForOrdering($tableName, $tables, $visited, $visiting, $order, []);
        }

        return $order;
    }

    /**
     * @param array<string, array<string, mixed>> $tables
     * @param array<string, true> $visited
     * @param array<string, true> $visiting
     * @param array<int, string> $order
     * @param array<int, string> $path
     */
    private function visitForOrdering(
        string $tableName,
        array $tables,
        array &$visited,
        array &$visiting,
        array &$order,
        array $path
    ): void {
        if (isset($visited[$tableName])) {
            return;
        }

        if (isset($visiting[$tableName])) {
            throw new DependencyException(sprintf(
                'Circular dependency detected: %s -> %s',
                implode(' -> ', $path),
                $tableName
            ));
        }

        if (!isset($tables[$tableName])) {
            throw new DependencyException(sprintf(
                'Table "%s" is referenced as a dependency but has no configuration entry.',
                $tableName
            ));
        }

        $visiting[$tableName] = true;
        $path[] = $tableName;

        foreach ($tables[$tableName]['depends'] ?? [] as $dependency) {
            $this->visitForOrdering($dependency, $tables, $visited, $visiting, $order, $path);
        }

        unset($visiting[$tableName]);
        $visited[$tableName] = true;
        $order[] = $tableName;
    }

    /**
     * Validates every table configuration up front, before any
     * synchronization begins (Goal 4, Goal 5, Goal 7, Goal 10).
     */
    private function validateConfiguration(): void
    {
        if ($this->tables === []) {
            throw new ConfigurationException('No tables configured for synchronization.');
        }

        foreach ($this->tables as $tableName => $config) {
            if (!is_string($tableName) || $tableName === '') {
                throw new ConfigurationException('Table configuration keys must be non-empty strings.');
            }

            self::assertValidIdentifier($tableName);

            $type = $config['type'] ?? null;

            if (!in_array($type, self::VALID_TYPES, true)) {
                throw new ConfigurationException(sprintf(
                    'Table "%s" must declare a valid type (%s).',
                    $tableName,
                    implode(', ', self::VALID_TYPES)
                ));
            }

            $primaryKey = $config['primary_key'] ?? null;
            $businessKey = $config['business_key'] ?? [];

            if (!is_array($businessKey)) {
                throw new ConfigurationException(sprintf('Table "%s" business_key must be an array.', $tableName));
            }

            if ($primaryKey === null && $businessKey === []) {
                throw new ConfigurationException(sprintf(
                    'Table "%s" must define a primary_key, a business_key, or both.',
                    $tableName
                ));
            }

            if (isset($config['depends']) && !is_array($config['depends'])) {
                throw new ConfigurationException(sprintf('Table "%s" depends must be an array.', $tableName));
            }

            if (isset($config['ignore']) && !is_array($config['ignore'])) {
                throw new ConfigurationException(sprintf('Table "%s" ignore must be an array.', $tableName));
            }
        }
    }

    /**
     * Determines which column(s) identify a row for duplicate/conflict
     * detection: the business key when configured, otherwise the
     * primary key (Goal 4).
     *
     * @param array<string, mixed> $config
     * @return array<int, string>
     */
    private function lookupColumns(array $config): array
    {
        $businessKey = $config['business_key'] ?? [];

        if ($businessKey !== []) {
            return $businessKey;
        }

        $primaryKey = $config['primary_key'] ?? null;

        return $primaryKey !== null ? [$primaryKey] : [];
    }

    /**
     * @param array<int, string> $columns
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function extractColumnValues(array $columns, array $row): array
    {
        $values = [];

        foreach ($columns as $column) {
            $values[$column] = $row[$column] ?? null;
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $metrics
     */
		private function reportProgress(
    string $tableName,
    array $metrics,
    int $current,
    int $total
): void {

    error_log("REPORT PROGRESS: {$tableName} ({$current}/{$total})");

    if ($this->progressCallback !== null) {

        error_log("CALLBACK EXISTS");

        ($this->progressCallback)(
            $tableName,
            $metrics,
            $current,
            $total
        );

    } else {

        error_log("NO CALLBACK");

    }
}

    private function describeConnection(PDO $pdo): string
    {
        try {
            $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (Throwable) {
            $driver = 'unknown';
        }

        return $driver;
    }

    /**
     * Rejects anything that is not a safe SQL identifier. Table and
     * column names are never user input in this engine, but they are
     * developer configuration and PDO cannot bind identifiers as
     * parameters, so they are still validated defensively.
     */
    private static function assertValidIdentifier(string $identifier): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new ConfigurationException(sprintf('Invalid identifier "%s".', $identifier));
        }
    }

    private static function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); // version 4
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // RFC 4122 variant

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
