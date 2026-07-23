<?php

declare(strict_types=1);

namespace Tala\Engine\Handlers;

use PDO;
use Tala\Engine\Contracts\OperationHandlerInterface;
use Tala\Engine\Enums\ExecutionStatus;
use Tala\Engine\Enums\OperationType;

/**
 * Handles DROP TABLE operations in the schema execution pipeline.
 */
final class DropTableHandler implements OperationHandlerInterface
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
        return OperationType::DROP_TABLE;
    }

    /**
     * @param array<string, mixed> $operation
     * @return array<string, mixed>
     */
    public function execute(array $operation): array
    {
        $started = microtime(true);
        $table = null;

        try {
            $details = $operation['details'] ?? [];
            $table = (string) ($details['table'] ?? '');

            if ($table === '') {
                return $this->buildResult(
                    ExecutionStatus::FAILED->value,
                    $started,
                    $table
                );
            }

            if (!$this->tableExists($table)) {
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    $started,
                    $table
                );
            }

            $sql = $this->buildSql($table);
            $this->destination->exec($sql);

            return $this->buildResult(
                ExecutionStatus::COMPLETED->value,
                $started,
                $table,
                null,
                $sql
            );
        } catch (\Throwable $e) {
            return $this->buildResult(
                ExecutionStatus::FAILED->value,
                $started,
                $table
            );
        }
    }

    private function buildSql(string $table): string
    {
        return sprintf('DROP TABLE `%s`;', $table);
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
            'operation' => OperationType::DROP_TABLE->value,
            'table' => $table,
            'column' => $column,
            'sql' => $sql,
            'started_at' => date('Y-m-d H:i:s', (int) $started),
            'finished_at' => date('Y-m-d H:i:s'),
            'duration_ms' => round((microtime(true) - $started) * 1000, 2),
        ];
    }
}
