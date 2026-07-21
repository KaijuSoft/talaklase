<?php

namespace Tala\Engine;

/**
 * RC2.10
 * ExecutionPlanBuilder
 *
 * Converts a validated merge plan into an executable plan.
 *
 * NOTE:
 * This class DOES NOT execute SQL.
 * It only prepares an ordered execution plan.
 */
class ExecutionPlanBuilder
{
	
		/**
	 * Execution priority.
	 * Lower numbers execute first.
	 *
	 * @var array<string,int>
	 */
	private array $priorityMap = [

		'create_table' => 10,

		'add_column' => 20,

		'modify_column' => 30,

		'create_index' => 40,

		'create_foreign_key' => 50,

		'drop_foreign_key' => 60,

		'drop_index' => 70,

		'drop_column' => 80,

		'drop_table' => 90,
	];
	
	
   /**
 * Build an execution plan from a validated merge plan.
 *
 * @param array $validatedPlan
 * @return array
 */
public function build(array $validatedPlan): array
{
    $executionPlan = [
        'status' => true,

        'summary' => [
            'operations' => 0,
            'warnings'  => 0,
            'errors'    => 0,
        ],

        'operations' => [],

        'warnings' => [],

        'errors' => [],
    ];

    if (empty($validatedPlan['operations'])) {
        return $executionPlan;
    }

    $counter = 1;

    foreach ($validatedPlan['operations'] as $operation) {

        $details = $operation['details'] ?? [];

		$sql = null;

		if (
			($operation['operation'] ?? '') === 'create_table'
		) {
			$sql = $details['definition']['create_sql'] ?? null;
		}

		$executionPlan['operations'][] = [

			'id' => sprintf(
				'OP-%05d',
				$counter++
			),

			'category' => $operation['category'] ?? 'schema',

			'operation' => $operation['operation'] ?? 'unknown',

			'target' => $operation['target'] ?? null,

			'details' => $details,

			'sql' => $sql,

			'priority' => $this->priorityMap[
				$operation['operation']
			] ?? 999,

			'dependencies' => [],

			'status' => 'pending'
		];
			}
	
	usort(
		$executionPlan['operations'],
		function (array $a, array $b): int {

			return $a['priority'] <=> $b['priority'];

		}
	);
		
		foreach ($executionPlan['operations'] as $index => &$operation) {

    $operation['id'] = sprintf(
        'OP-%05d',
        $index + 1
    );

}

		unset($operation);

		$executionPlan['summary']['operations'] =
			count($executionPlan['operations']);

		return $executionPlan;
	}
	
}