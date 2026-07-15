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
	
	if (!$this->dryRun) {
    $this->pdo->beginTransaction();
	}
	
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
		
		if (!$this->dryRun && $this->pdo->inTransaction()) {
    $this->pdo->rollBack();
	}

        $result['failed']++;

        $result['status'] = false;

        $result['operations'][] = [

            'mode' => 'LIVE',

            'status' => 'FAILED',

            'error' => $e->getMessage(),

            'sql' => $sql['sql'],

            'values' => $sql['values']

        ];
			return $result;
    }

}

     break;

           case 'update':

    $sql = $this->buildUpdateSQL($operation);

    if ($this->dryRun) {

        $result['operations'][] = [

            'mode' => 'DRY RUN',

            'sql' => $sql['sql'],

            'values' => $sql['values']

        ];

    } else {

        try {

            $this->executeUpdate($sql);

            $result['executed']++;

            $result['operations'][] = [

                'mode' => 'LIVE',

                'status' => 'SUCCESS',

                'sql' => $sql['sql'],

                'values' => $sql['values']

            ];

        } catch (\PDOException $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $result['failed']++;
            $result['status'] = false;

            $result['operations'][] = [

                'mode' => 'LIVE',

                'status' => 'FAILED',

                'error' => $e->getMessage(),

                'sql' => $sql['sql'],

                'values' => $sql['values']

            ];

            return $result;
        }
    }

    break;
        }
    }
	
	if (!$this->dryRun && $result['status']) {
    $this->pdo->commit();
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
 * Build a parameterized UPDATE statement.
 */
private function buildUpdateSQL(array $operation): array
{
    $table = $operation['table'];
    $data = $operation['data'];
    $primaryKey = $operation['primary_key'];

    $columns = [];

    $values = [];

    foreach ($data as $column => $value) {

        if ($column === $primaryKey) {
            continue;
        }

        $columns[] = "`{$column}` = ?";

        $values[] = $value;
    }

    $values[] = $data[$primaryKey];

    return [

        'sql' =>
            "UPDATE `{$table}` SET " .
            implode(', ', $columns) .
            " WHERE `{$primaryKey}` = ?",

        'values' => $values

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
	
	private function executeUpdate(array $sql): void
{
    $statement = $this->pdo->prepare($sql['sql']);
    $statement->execute($sql['values']);
}
}