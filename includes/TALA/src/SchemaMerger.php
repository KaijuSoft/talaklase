<?php

declare(strict_types=1);

namespace Tala\Engine;

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
                    'missing_source_table', 'missing_destination_table' => $this->planMissingTable($tableName),
                    'missing_column' => $this->planMissingColumn($tableName, $difference),
                    'column_type', 'column_nullable', 'column_default' => $this->planColumnDifference($tableName, $difference),
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
    private function planMissingTable(string $table): array
    {
        return [
            'action' => 'create_table',
            'table' => $table,
        ];
    }

    /**
     * @param array<string, mixed> $difference
     * @return array<string, mixed>
     */
    private function planMissingColumn(string $table, array $difference): array
    {
        return [
            'action' => 'add_column',
            'table' => $table,
            'column' => (string) ($difference['column'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $difference
     * @return array<string, mixed>
     */
    private function planColumnDifference(string $table, array $difference): array
    {
        return [
            'action' => 'modify_column',
            'table' => $table,
            'column' => (string) ($difference['column'] ?? ''),
            'source_type' => $difference['source'] ?? null,
            'destination_type' => $difference['destination'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $difference
     * @return array<string, mixed>
     */
    private function planMissingIndex(string $table, array $difference): array
    {
        return [
            'action' => 'create_index',
            'table' => $table,
            'index' => (string) ($difference['index'] ?? ''),
        ];
    }
}
