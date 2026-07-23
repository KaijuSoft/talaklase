<?php

declare(strict_types=1);

namespace Tala\Engine\Handlers;

use PDO;
use Tala\Engine\Contracts\OperationHandlerInterface;
use Tala\Engine\Enums\ExecutionStatus;
use Tala\Engine\Enums\OperationType;

/**
 * Handles DROP COLUMN operations in the schema execution pipeline.
 */
final class DropColumnHandler implements OperationHandlerInterface
{
    private PDO $source;

    private PDO $destination;

    public function __construct(PDO $source, PDO $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    public static function operation(): OperationType
    {
        return OperationType::DROP_COLUMN;
    }

    /**
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    public function execute(array $operation): array
    {
        $started = microtime(true);
        $table = null;
        $column = null;

        try {
            $details = $operation['details'] ?? [];
            $table = (string) ($details['table'] ?? '');
            $column = (string) ($details['column'] ?? '');

            if ($table === '' || $column === '') {
                return $this->buildResult(
                    ExecutionStatus::FAILED->value,
                    $started,
                    $table,
                    $column
                );
            }

            if (!$this->tableExists($table)) {
                return $this->buildResult(
                    ExecutionStatus::FAILED->value,
                    $started,
                    $table,
                    $column
                );
            }

            if (!$this->columnExists($table, $column)) {
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    $started,
                    $table,
                    $column
                );
            }

            $sql = $this->buildSql($table, $column);
            $this->destination->exec($sql);

            return $this->buildResult(
                ExecutionStatus::COMPLETED->value,
                $started,
                $table,
                $column,
                $sql
            );
        } catch (\Throwable $e) {
            return $this->buildResult(
                ExecutionStatus::FAILED->value,
                $started,
                $table,
                $column
            );
        }
    }

    private function buildSql(string $table, string $column): string
    {
        return sprintf(
            'ALTER TABLE `%s` DROP COLUMN `%s`;',
            $table,
            $column
        );
    }

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
     * @return array<string, mixed>
     */
    private function buildResult(
        string $status,
        float $started,
        ?string $table = null,
        ?string $column = null,
        ?string $sql = null
    ): array {
        return [
            'status' => $status,
            'operation' => OperationType::DROP_COLUMN->value,
            'table' => $table,
            'column' => $column,
            'sql' => $sql,
            'started_at' => date('Y-m-d H:i:s', (int) $started),
            'finished_at' => date('Y-m-d H:i:s'),
            'duration_ms' => round((microtime(true) - $started) * 1000, 2),
        ];
    }
}
