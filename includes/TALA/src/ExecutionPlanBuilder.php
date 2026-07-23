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
    public function buildExecutionPlan(array $validatedPlan): array
    {
        $executionPlan = [
            'status' => true,
            'summary' => [
                'operations' => 0,
                'warnings' => 0,
                'errors' => 0,
            ],
            'queue' => [],
            'operations' => [],
            'warnings' => [],
            'errors' => [],
        ];

        foreach ($validatedPlan['operations'] ?? [] as $index => $operation) {
            $operationType = $operation['operation'] ?? null;
            $dependencies = $this->normalizeDependencies($operation['dependencies'] ?? []);

            $executionPlan['queue'][] = [
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
                'dependencies' => $dependencies,
                'status' => ExecutionStatus::PENDING->value,
            ];
        }

        $executionPlan['operations'] = $this->orderOperations($executionPlan['queue']);

        foreach ($executionPlan['operations'] as $index => &$operation) {
            $operation['id'] = sprintf('OP-%05d', $index + 1);
        }
        unset($operation);

        $executionPlan['summary']['operations'] = count($executionPlan['operations']);

        return $executionPlan;
    }

    /**
     * Backward-compatible alias used by older RC3 callers.
     *
     * @param array<string, mixed> $validatedPlan
     * @return array<string, mixed>
     */
    public function build(array $validatedPlan): array
    {
        return $this->buildExecutionPlan($validatedPlan);
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

    /**
     * @param array<int, mixed> $dependencies
     * @return array<int, string>
     */
    private function normalizeDependencies(array $dependencies): array
    {
        $normalized = [];

        foreach ($dependencies as $dependency) {
            $value = trim((string) $dependency);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Preserve deterministic execution order while keeping dependency
     * information in the queued operations.
     *
     * @param array<int, array<string, mixed>> $queue
     * @return array<int, array<string, mixed>>
     */
    private function orderOperations(array $queue): array
    {
        usort(
            $queue,
            static fn (array $a, array $b): int => [$a['priority'] ?? 999, $a['id'] ?? ''] <=> [$b['priority'] ?? 999, $b['id'] ?? '']
        );

        return $queue;
    }
}
