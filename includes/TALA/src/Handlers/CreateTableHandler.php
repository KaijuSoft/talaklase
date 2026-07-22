<?php

declare(strict_types=1);

namespace Tala\Engine\Handlers;

use PDO;
use Tala\Engine\Contracts\OperationHandlerInterface;
use Tala\Engine\Enums\ExecutionStatus;
use Tala\Engine\Enums\OperationType;

/**
 * Handles CREATE TABLE operations in the schema execution pipeline.
 *
 * The handler only executes SQL that already exists in the operation payload.
 * It does not generate SQL.
 */
final class CreateTableHandler implements OperationHandlerInterface
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
        return OperationType::CREATE_TABLE;
    }

    /**
     * Execute a CREATE TABLE operation.
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
            $sql = $operation['sql'] ?? null;

            if ($table !== '' && $this->tableExists($table)) {
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    "Table '{$table}' already exists.",
                    null,
                    $started
                );
            }

            if (empty($sql)) {
                return $this->buildResult(
                    ExecutionStatus::SKIPPED->value,
                    'No SQL supplied for CREATE TABLE execution.',
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
     * Check whether the destination table already exists.
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
     * Build a standardized execution result.
     *
     * @param string $status
     * @param string|null $reason
     * @param string|null $error
     * @param float $started
     * @return array<string, mixed>
     */
    private function buildResult(
        string $status,
        ?string $reason,
        ?string $error,
        float $started
    ): array {
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
