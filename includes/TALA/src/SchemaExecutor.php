<?php

declare(strict_types=1);

namespace Tala\Engine;

use PDO;


final class SchemaExecutor
{
    
    private PDO $source;

	private PDO $destination;

    /**
     * Constructor.
     */
   public function __construct(
		PDO $source,
		PDO $destination
	)
	{
		$this->source = $source;
		$this->destination = $destination;
	}

    /**
     * Execute a validated merge plan.
     *
     * RC2.9.1:
     * No SQL is executed.
     * All operations are returned as skipped.
     */
    public function execute(array $validatedPlan): array
    {
        $results = [];

        $executed = 0;
        $skipped  = 0;
        $failed   = 0;

        $operations = $validatedPlan['operations'] ?? [];

        foreach ($operations as $operation) {

          $result = $this->executeOperation($operation);

$results[] = $result;

switch ($result['status']) {
    case 'success':
        $executed++;
        break;

    case 'failed':
        $failed++;
        break;

    default:
        $skipped++;
        break;
}
		  
		  
        }

        return [

            'status' => true,

            'executed' => $executed,

            'skipped' => $skipped,

            'failed' => $failed,

            'operations' => $results
        ];
    }


private function executeOperation(array $operation): array
{
    return match ($operation['action'] ?? '') {

        'create_table'
            => $this->executeCreateTable($operation),

        'add_column'
            => $this->executeAddColumn($operation),

        'create_index'
            => $this->executeCreateIndex($operation),

        default
            => $this->skipOperation($operation),
    };
}

private function executeCreateTable(array $operation): array
{
    $table = $operation['table'] ?? null;

    if ($table === null) {

        return [

            'action' => 'create_table',

            'table' => null,

            'column' => null,

            'index' => null,

            'sql' => null,

            'status' => 'failed',

            'reason' => 'Missing table name.',

            'error' => null,

            'executed' => false,

            'started_at' => date('Y-m-d H:i:s'),

            'duration_ms' => 0

        ];

    }

    $sql = $this->getCreateTableStatement($table);

    if ($sql === null) {

        return [

            'action' => 'create_table',

            'table' => $table,

            'column' => null,

            'index' => null,

            'sql' => null,

            'status' => 'failed',

            'reason' => 'Unable to retrieve CREATE TABLE statement.',

            'error' => null,

            'executed' => false,

            'started_at' => date('Y-m-d H:i:s'),

            'duration_ms' => 0

        ];

    }

    $sql = $this->normalizeCreateTableSql($sql);

    return $this->executeSql(
        $operation,
        $sql
    );
}

private function executeAddColumn(array $operation): array
{
    return $this->skipOperation(
        $operation,
        'ADD COLUMN execution not implemented (RC2.9.2)'
    );
}

private function executeCreateIndex(array $operation): array
{
    return $this->skipOperation(
        $operation,
        'CREATE INDEX execution not implemented (RC2.9.2)'
    );
}


private function getCreateTableStatement(string $table): ?string
{
    $stmt = $this->source->prepare(
        "SHOW CREATE TABLE `{$table}`"
    );

    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return null;
    }

    return $result['Create Table'] ?? null;
}

private function normalizeCreateTableSql(string $sql): string
{
    // Remove AUTO_INCREMENT value
    $sql = preg_replace(
        '/AUTO_INCREMENT=\d+\s*/i',
        '',
        $sql
    );

    // Normalize line endings
    $sql = str_replace("\r\n", "\n", $sql);

    // Trim whitespace
    return trim($sql);
}

private function executeSql(
    array $operation,
    string $sql
): array
{
    $start = microtime(true);

    try {

        $this->destination->beginTransaction();

		$this->destination->exec($sql);

if ($this->destination->inTransaction()) {
    $this->destination->commit();
}

        return [

            'action' => $operation['action'] ?? 'unknown',

            'table' => $operation['table'] ?? null,

            'column' => $operation['column'] ?? null,

            'index' => $operation['index'] ?? null,

            'sql' => $sql,

            'status' => 'success',

            'reason' => 'Executed successfully.',

            'error' => null,

            'executed' => true,

            'started_at' => date('Y-m-d H:i:s'),

            'duration_ms' => round(
                (microtime(true) - $start) * 1000,
                3
            )
        ];

    } catch (\Throwable $e) {
		
		if ($this->destination->inTransaction()) {
		$this->destination->rollBack();
	}

        return [

            'action' => $operation['action'] ?? 'unknown',

            'table' => $operation['table'] ?? null,

            'column' => $operation['column'] ?? null,

            'index' => $operation['index'] ?? null,

            'sql' => $sql,

            'status' => 'failed',

            'reason' => 'SQL execution failed.',

            'error' => $e->getMessage(),

            'executed' => false,

            'started_at' => date('Y-m-d H:i:s'),

            'duration_ms' => round(
                (microtime(true) - $start) * 1000,
                3
            )
        ];
    }
}

private function skipOperation(
    array $operation,
    string $reason = 'Execution not implemented (RC2.9.2)'
): array {

    $start = microtime(true);

    return [

        'action' => $operation['action'] ?? 'unknown',

        'table' => $operation['table'] ?? null,

        'column' => $operation['column'] ?? null,

        'index' => $operation['index'] ?? null,

        'sql' => $operation['sql'] ?? null,

        'status' => 'skipped',

        'reason' => $reason,

        'error' => null,

        'executed' => false,

        'started_at' => date('Y-m-d H:i:s'),

        'duration_ms' => round((microtime(true) - $start) * 1000, 3)
    ];
}
}