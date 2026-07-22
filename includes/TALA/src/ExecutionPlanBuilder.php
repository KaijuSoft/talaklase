<?php

declare(strict_types=1);

namespace Tala\Engine;

use Tala\Engine\Enums\OperationType;
use Tala\Engine\Enums\ExecutionStatus;

/**
 * Converts a validated merge plan into an executable execution plan.
 *
 * This class does not execute SQL. It only prepares the execution metadata
 * consumed by SchemaExecutor.
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
        OperationType::CREATE_TABLE->value => 10,
        OperationType::ADD_COLUMN->value => 20,
        OperationType::MODIFY_COLUMN->value => 30,
        OperationType::CREATE_INDEX->value => 40,
        OperationType::CREATE_FOREIGN_KEY->value => 50,
        OperationType::DROP_FOREIGN_KEY->value => 60,
        OperationType::DROP_INDEX->value => 70,
        OperationType::DROP_COLUMN->value => 80,
        OperationType::DROP_TABLE->value => 90,
    ];

    /**
     * Build an execution plan from a validated merge plan.
     *
     * @param array<string, mixed> $validatedPlan
     * @return array<string, mixed>
     */
    public function build(array $validatedPlan): array
    {
        $executionPlan = [
            'status' => true,
            'summary' => [
                'operations' => 0,
                'warnings' => 0,
                'errors' => 0,
            ],
            'operations' => [],
            'warnings' => [],
            'errors' => [],
        ];

        foreach ($validatedPlan['operations'] ?? [] as $index => $operation) {
            $operationType = $operation['operation'] ?? null;

            $executionPlan['operations'][] = [
                'id' => sprintf('OP-%05d', $index + 1),
                'category' => $operation['category'] ?? 'schema',
                'operation' => $operationType,
                'target_type' => $operation['target_type'] ?? null,
                'target' => $operation['target'] ?? null,
                'details' => $operation['details'] ?? [],
                'safe' => $operation['safe'] ?? true,
                'reason' => $operation['reason'] ?? null,
                'sql' => $this->buildSql($operation),
                'priority' => $this->priorityMap[$operationType] ?? 999,
                'dependencies' => $operation['dependencies'] ?? [],
                'status' => ExecutionStatus::PENDING->value,
            ];
        }

        usort(
            $executionPlan['operations'],
            static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']
        );

        foreach ($executionPlan['operations'] as $index => &$operation) {
            $operation['id'] = sprintf('OP-%05d', $index + 1);
        }
        unset($operation);

        $executionPlan['summary']['operations'] = count($executionPlan['operations']);

        return $executionPlan;
    }

    /**
     * Build SQL for supported operations.
     *
     * Only CREATE TABLE currently produces SQL. All other operations return
     * null so execution remains unchanged until their handlers are added.
     *
     * @param array<string, mixed> $operation
     */
    private function buildSql(array $operation): ?string
    {
        $details = $operation['details'] ?? [];

        return match ($operation['operation'] ?? null) {
            OperationType::CREATE_TABLE->value => $details['definition']['create_sql']
                ?? $details['create_sql']
                ?? null,
            default => null,
        };
    }
}
