<?php

declare(strict_types=1);

namespace Tala\Engine;

use PDO;
use Throwable;

final class DatabaseSnapshot
{
    private readonly PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Captures a read-only schema snapshot of the connected database.
     *
     * @return array<string, mixed>
     */
    public function capture(): array
    {
        $database = (string) $this->connection->query('SELECT DATABASE()')->fetchColumn();
        $capturedAt = date('Y-m-d H:i:s');
        $tables = [];

        try {
            $statement = $this->connection->query("
                SELECT TABLE_NAME
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY TABLE_NAME
            ");

            foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $tableName) {
                $table = (string) $tableName;
                if ($table === '') {
                    continue;
                }

                $tables[] = $this->captureTable($table);
            }
        } catch (Throwable) {
            // Read-only snapshot: return whatever was captured so far.
        }

        return [
            'database' => $database,
            'captured_at' => $capturedAt,
            'tables' => $tables,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function captureTable(string $table): array
    {
        return [
            'table' => $table,
            'rows' => $this->countRows($table),
            'columns' => $this->captureColumns($table),
            'indexes' => $this->captureIndexes($table),
            'primary_key' => $this->capturePrimaryKey($table),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function captureColumns(string $table): array
    {
        $statement = $this->connection->prepare("
            SELECT
                COLUMN_NAME,
                COLUMN_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ");
        $statement->execute([$table]);

        $columns = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = [
                'name' => (string) ($row['COLUMN_NAME'] ?? ''),
                'type' => (string) ($row['COLUMN_TYPE'] ?? ''),
                'nullable' => ((string) ($row['IS_NULLABLE'] ?? 'NO')) === 'YES',
                'default' => $this->normalizeDefault($row['COLUMN_DEFAULT'] ?? null),
                'extra' => (string) ($row['EXTRA'] ?? ''),
            ];
        }

        return $columns;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function captureIndexes(string $table): array
    {
        $statement = $this->connection->prepare("
            SELECT
                INDEX_NAME,
                NON_UNIQUE,
                COLUMN_NAME,
                SEQ_IN_INDEX
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
        ");
        $statement->execute([$table]);

        $indexes = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = (string) ($row['INDEX_NAME'] ?? '');
            $column = (string) ($row['COLUMN_NAME'] ?? '');
            if ($name === '' || $column === '') {
                continue;
            }

            if (!isset($indexes[$name])) {
                $indexes[$name] = [
                    'name' => $name,
                    'unique' => ((string) ($row['NON_UNIQUE'] ?? '1')) === '0',
                    'columns' => [],
                ];
            }

            $indexes[$name]['columns'][] = $column;
        }

        return array_values($indexes);
    }

    /**
     * @return array<int, string>
     */
    public function capturePrimaryKey(string $table): array
    {
        $statement = $this->connection->prepare("
            SELECT COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = 'PRIMARY'
            ORDER BY ORDINAL_POSITION
        ");
        $statement->execute([$table]);

        $columns = [];
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $column) {
            $value = (string) $column;
            if ($value !== '') {
                $columns[] = $value;
            }
        }

        return $columns;
    }

    public function countRows(string $table): int
    {
        try {
            $statement = $this->connection->query(sprintf('SELECT COUNT(*) FROM `%s`', $table));
            return (int) $statement->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function normalizeDefault(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $stringValue = (string) $value;
        return $stringValue === '' ? null : $stringValue;
    }
}
