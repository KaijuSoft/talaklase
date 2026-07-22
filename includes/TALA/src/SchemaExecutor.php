<?php

declare(strict_types=1);

namespace Tala\Engine;

use PDO;
use Tala\Engine\Handlers\CreateTableHandler;
use Tala\Engine\Handlers\ModifyColumnHandler;


final class SchemaExecutor
{
    
    private PDO $source;

	private PDO $destination;
	
	/**
 * Registered operation handlers.
 *
 * @var array<string,string>
 */
private array $handlers = [

    'create_table' => CreateTableHandler::class,
    'modify_column' => ModifyColumnHandler::class,

];

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

    case 'executed':
        $executed++;
        break;

    case 'failed':
        $failed++;
        break;

    default:
        $skipped++;
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
		$operationType = $operation['operation'] ?? '';

		if (!isset($this->handlers[$operationType])) {

			return $this->skipOperation(
				$operation,
				"No handler registered for '{$operationType}'."
			);

		}

		$handlerClass = $this->handlers[$operationType];

		$handler = new $handlerClass(
			$this->source,
			$this->destination
		);

		return $handler->execute($operation);
	}

private function executeAddColumn(array $operation): array
{
    return $this->skipOperation(
        $operation,
        'ADD COLUMN execution not implemented (RC2.9.2)'
    );
}

private function skipOperation(
    array $operation,
    string $reason = 'Execution not implemented (RC2.9.2)'
): array {

    $start = microtime(true);

    return [

        'operation' => $operation['operation'] ?? 'unknown',

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
