<?php

declare(strict_types=1);

namespace Tala\Engine;

use Tala\Engine\Enums\OperationType;
use Tala\Engine\Enums\TargetType;

final class SchemaMerger
{
    /**
     * Converts SchemaInspector output into a dry-run merge plan.
     *
     * @param array<string, mixed> $inspection
     * @return array<string, mixed>
     */
    public function buildPlan(array $inspection): array
    {
        $operations = [];

        foreach ($inspection['tables'] ?? [] as $tableResult) {
            $tableName = (string) ($tableResult['table'] ?? '');
            if ($tableName === '') {
                continue;
            }

            foreach ($tableResult['differences'] ?? [] as $difference) {
                $action = (string) ($difference['type'] ?? '');

                $planned = match ($action) {
                    'missing_destination_table' => $this->planMissingDestinationTable(
                        $tableName,
                        $difference
                    ),
                    'missing_source_table' => $this->planMissingSourceTable($tableName),
                    'missing_column' => $this->planMissingColumn($tableName, $difference),
                    'column_type',
                    'column_nullable',
                    'column_default' => $this->planColumnDifference($tableName, $difference),
                    'missing_index' => $this->planMissingIndex($tableName, $difference),
                    default => null,
                };

                if ($planned !== null) {
                    $operations[] = $planned;
                }
            }
        }

        $status = ($inspection['status'] ?? false) === true;

        return [
            'status' => $status,
            'operations' => $operations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function planMissingDestinationTable(
        string $table,
        array $difference
    ): array {
        return $this->createOperation(
            OperationType::CREATE_TABLE->value,
            TargetType::TABLE->value,
            $table,
            [
                'table' => $table,
                'definition' => $difference['definition'] ?? [],
            ],
            true,
            'Table exists in source but not destination.'
        );
    }

    private function planMissingSourceTable(string $table): ?array
    {
        return null;
    }

    /**
     * @param array<string, mixed> $difference
     * @return array<string, mixed>
     */
    private function planMissingColumn(string $table, array $difference): array
    {
        return $this->createOperation(
            OperationType::ADD_COLUMN->value,
            TargetType::COLUMN->value,
            "{$table}." . (string) ($difference['column'] ?? ''),
            [
                'table' => $table,
                'column' => (string) ($difference['column'] ?? ''),
                'definition' => $difference['definition'] ?? [],
            ]
        );
    }

    /**
     * @param array<string, mixed> $difference
     * @return array<string, mixed>
     */
    private function planColumnDifference(string $table, array $difference): array
    {
        return $this->createOperation(
            OperationType::MODIFY_COLUMN->value,
            TargetType::COLUMN->value,
            "{$table}." . (string) ($difference['column'] ?? ''),
            [
                'table' => $table,
                'column' => (string) ($difference['column'] ?? ''),
                'source_definition' => $difference['source_definition'] ?? [],
                'destination_definition' => $difference['destination_definition'] ?? [],
                'source_type' => $difference['source'] ?? null,
                'destination_type' => $difference['destination'] ?? null,
            ]
        );
    }

    /**
     * @param array<string, mixed> $difference
     * @return array<string, mixed>
     */
    private function planMissingIndex(string $table, array $difference): array
    {
        return $this->createOperation(
            OperationType::CREATE_INDEX->value,
            TargetType::INDEX->value,
            "{$table}." . (string) ($difference['index'] ?? ''),
            [
                'table' => $table,
                'index' => (string) ($difference['index'] ?? ''),
            ]
        );
    }

    /**
     * Creates a standardized merge operation.
     *
     * @param string $operation
     * @param string $targetType
     * @param string $target
     * @param array<string, mixed> $details
     * @param bool $safe
     * @param string|null $reason
     * @return array<string, mixed>
     */
    private function createOperation(
        string $operation,
        string $targetType,
        string $target,
        array $details = [],
        bool $safe = true,
        ?string $reason = null
    ): array {
        return [
            'category' => 'schema',
            'operation' => $operation,
            'target_type' => $targetType,
            'target' => $target,
            'details' => $details,
            'safe' => $safe,
            'reason' => $reason,
        ];
    }
}
