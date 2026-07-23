<?php

declare(strict_types=1);

namespace Tala\Engine\Handlers;

use PDO;
use Tala\Engine\Contracts\OperationHandlerInterface;
use Tala\Engine\Enums\ExecutionStatus;
use Tala\Engine\Enums\OperationType;
use Tala\Engine\SqlColumnDefinitionBuilder;

/**
 * Handles MODIFY COLUMN operations in the schema execution pipeline.
 *
 * The handler validates the payload, builds the SQL statement, and executes
 * it against the destination connection.
 */
final class ModifyColumnHandler implements OperationHandlerInterface
{
    private PDO $source;

    private PDO $destination;

    private SqlColumnDefinitionBuilder $columnBuilder;

    public function __construct(PDO $source, PDO $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->columnBuilder = new SqlColumnDefinitionBuilder($destination);
    }

    public static function operation(): OperationType
    {
        return OperationType::MODIFY_COLUMN;
    }

    /**
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    public function execute(array $operation): array
    {
        $started = microtime(true);
        $sql = null;
        $table = null;
        $column = null;

        try {
            $details = $operation['details'] ?? [];
            $table = (string) ($details['table'] ?? '');
            $column = (string) ($details['column'] ?? '');
            $sourceDefinition = $details['source_definition'] ?? null;
            $destinationDefinition = $details['destination_definition'] ?? null;

            if ($table === '' || $column === '') {
                return $this->buildResult(
                    ExecutionStatus::FAILED->value,
                    $started,
                    $table,
                    $column,
                    $sql
                );
            }

            if (!is_array($sourceDefinition) || $sourceDefinition === []) {
                return $this->buildResult(
                    ExecutionStatus::FAILED->value,
                    $started,
                    $table,
                    $column,
                    $sql
                );
            }

            if (!is_array($destinationDefinition)) {
                return $this->buildResult(
                    ExecutionStatus::FAILED->value,
                    $started,
                    $table,
                    $column,
                    $sql
                );
            }

            $sql = $this->buildSql($table, $column, $sourceDefinition, $destinationDefinition);

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
                $column,
                $sql
            );
        }
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function buildSql(string $table, string $column, array $definition, array $existingDefinition): string
    {
        $columnDefinition = $this->columnBuilder->buildColumnDefinition($definition, $existingDefinition);
        if ($columnDefinition === '') {
            throw new \InvalidArgumentException("Column type for '{$table}.{$column}' is missing.");
        }

        return sprintf(
            'ALTER TABLE `%s` MODIFY COLUMN `%s` %s;',
            $table,
            $column,
            $columnDefinition
        );
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
            'operation' => OperationType::MODIFY_COLUMN->value,
            'table' => $table,
            'column' => $column,
            'sql' => $sql,
            'started_at' => date('Y-m-d H:i:s', (int) $started),
            'finished_at' => date('Y-m-d H:i:s'),
            'duration_ms' => round((microtime(true) - $started) * 1000, 2),
        ];
    }
}
