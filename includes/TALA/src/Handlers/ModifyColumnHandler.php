<?php

declare(strict_types=1);

namespace Tala\Engine\Handlers;

use PDO;
use Tala\Engine\Contracts\OperationHandlerInterface;
use Tala\Engine\Enums\OperationType;
use Tala\Engine\SqlColumnDefinitionBuilder;

/**
 * Handles MODIFY COLUMN operations in the schema execution pipeline.
 *
 * The handler executes SQL built from the execution payload. It does not
 * query schema metadata again.
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

        try {
            $details = $operation['details'] ?? [];
            $table = (string) ($details['table'] ?? '');
            $column = (string) ($details['column'] ?? '');
            $sourceDefinition = $details['source_definition'] ?? null;
            $destinationDefinition = $details['destination_definition'] ?? null;

            if ($table === '' || $column === '') {
                return $this->buildResult(
                    'failed',
                    null,
                    'Target table or column is missing.',
                    $sql,
                    $started
                );
            }

            if (!is_array($sourceDefinition) || $sourceDefinition === []) {
                return $this->buildResult(
                    'failed',
                    null,
                    "Source definition for '{$table}.{$column}' is missing.",
                    $sql,
                    $started
                );
            }

            if (!is_array($destinationDefinition)) {
                return $this->buildResult(
                    'failed',
                    null,
                    "Destination definition for '{$table}.{$column}' is missing.",
                    $sql,
                    $started
                );
            }

            $sql = $this->buildSql($table, $column, $sourceDefinition, $destinationDefinition);

            $this->destination->exec($sql);

            return $this->buildResult(
                'executed',
                $sql,
                null,
                $started
            );
        } catch (\Throwable $e) {
            return $this->buildResult(
                'failed',
                $sql,
                $e->getMessage(),
                $started
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
     * @param string|null $reason
     * @param string|null $sql
     * @return array<string, mixed>
     */
    private function buildResult(
        string $status,
        ?string $sql,
        ?string $reason,
        float $started
    ): array {
        return [
            'status' => $status,
            'sql' => $sql,
            'error' => $reason,
            'duration_ms' => round((microtime(true) - $started) * 1000, 2),
        ];
    }
}
