<?php

declare(strict_types=1);

namespace Tala\Engine\Handlers;

use PDO;
use Tala\Engine\Enums\ExecutionStatus;
use Tala\Engine\Enums\OperationType;

/**
 * Handles ADD COLUMN operations in the schema execution pipeline.
 *
 * This handler does not generate SQL. It only executes SQL already present in
 * the operation payload and returns a standardized execution result.
 */
final class AddColumnHandler implements OperationHandlerInterface
{
    private PDO $source;

    private PDO $destination;

    public function __construct(PDO $source, PDO $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    /**
     * Returns the supported operation type.
     */
    public static function operation(): OperationType
    {
        return OperationType::ADD_COLUMN;
    }

    /**
     * Execute an ADD COLUMN operation.
     *
     * Validation is limited to the presence of the target table, target column,
     * and column definition metadata. If SQL is not provided, the operation is
     * skipped and execution is deferred to the SQL generation layer.
     *
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    public function execute(array $operation): array
    {
        $started = microtime(true);

        try {
            $details = $operation['details'] ?? [];
            $table = (string) ($details['table'] ?? '');
            $column = (string) ($details['column'] ?? '');
            $definition = $details['definition'] ?? null;
            $sql = $operation['sql'] ?? null;

            if ($table === '') {
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    'Target table is missing.',
                    null,
                    $started
                );
            }

            if (!$this->tableExists($table)) {
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    "Table '{$table}' does not exist.",
                    null,
                    $started
                );
            }

            if ($column === '') {
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    'Target column is missing.',
                    null,
                    $started
                );
            }

            if ($this->columnExists($table, $column)) {
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    "Column '{$table}.{$column}' already exists.",
                    null,
                    $started
                );
            }

            if ($definition === null || $definition === []) {
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    "Column definition for '{$table}.{$column}' is missing.",
                    null,
                    $started
                );
            }

            if (empty($sql)) {
                // TODO: SQL generation belongs in the planning layer.
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    'No SQL supplied for ADD COLUMN execution.',
                    null,
                    $started
                );
            }

            $this->destination->exec((string) $sql);

            return $this->buildResult(
                ExecutionStatus::COMPLETED->value,
                null,
                null,
                $started
            );
        } catch (\Throwable $e) {
            return $this->buildResult(
                ExecutionStatus::FAILED->value,
                null,
                $e->getMessage(),
                $started
            );
        }
    }

    /**
     * Check whether the target table exists on the destination connection.
     */
    private function tableExists(string $table): bool
    {
        $statement = $this->destination->prepare(
            '
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            '
        );
        $statement->execute([$table]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Check whether the target column already exists on the destination table.
     */
    private function columnExists(string $table, string $column): bool
    {
        $statement = $this->destination->prepare(
            '
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            '
        );
        $statement->execute([$table, $column]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Build a standardized skipped result.
     *
     * @param string $reason
     * @param float $started
     * @return array<string, mixed>
     */
    private function buildResult(
        string $status,
        ?string $reason,
        ?string $error,
        float $started
    ): array
    {
        return [
            'status' => $status,
            'reason' => $reason,
            'error' => $error,
            'started_at' => date('Y-m-d H:i:s', (int) $started),
            'finished_at' => date('Y-m-d H:i:s'),
            'duration_ms' => round((microtime(true) - $started) * 1000, 2),
        ];
    }
}
