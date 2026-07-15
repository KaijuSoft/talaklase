<?php

namespace Tala\Engine;

use PDO;

class DataExecutor
{
    private PDO $pdo;
    private array $plan;
	private bool $dryRun;

   public function __construct(
		PDO $pdo,
		array $plan,
		bool $dryRun = true
		)
	{
		$this->pdo = $pdo;
		$this->plan = $plan;
		$this->dryRun = $dryRun;
	}
    /**
     * Execute the synchronization plan.
     */
   public function execute(): array
	{
    $result = [

        'status' => true,

        'executed' => 0,

        'failed' => 0,

        'skipped' => 0,

        'operations' => []

    ];

    foreach ($this->plan as $operation) {

        switch ($operation['operation']) {

            case 'insert':

                $sql = $this->buildInsertSQL($operation);

if ($this->dryRun) {

    $result['operations'][] = [

        'mode' => 'DRY RUN',

        'sql' => $sql['sql'],

        'values' => $sql['values']

    ];

}
else {

    try {

        $this->executeInsert($sql);

        $result['executed']++;

        $result['operations'][] = [

            'mode' => 'LIVE',

            'status' => 'SUCCESS',

            'sql' => $sql['sql'],

            'values' => $sql['values']

        ];

    }
    catch (\PDOException $e) {

        $result['failed']++;

        $result['status'] = false;

        $result['operations'][] = [

            'mode' => 'LIVE',

            'status' => 'FAILED',

            'error' => $e->getMessage(),

            'sql' => $sql['sql'],

            'values' => $sql['values']

        ];

    }

}

                break;

            case 'update':

                $result['skipped']++;

                break;

            case 'delete':

                $result['skipped']++;

                break;
        }
    }

    return $result;
}


/**
 * Build a parameterized INSERT statement.
 */
	public function buildInsertSQL(array $operation): array
	{
		$table = $operation['table'];
		$data = $operation['data'];

		$columns = array_keys($data);

		$placeholders = array_fill(0, count($columns), '?');

		$sql =
			"INSERT INTO `{$table}` (`" .
			implode('`,`', $columns) .
			"`) VALUES (" .
			implode(',', $placeholders) .
			")";

		return [

			'sql' => $sql,

			'values' => array_values($data)

		];
	}
	
	/**
	* Execute an INSERT operation.
	*/
	private function executeInsert(array $sql): void
	{
		$statement = $this->pdo->prepare($sql['sql']);
		$statement->execute($sql['values']);
	}
}