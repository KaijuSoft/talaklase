<?php

declare(strict_types=1);

final class ReferenceInspector
{
    /** @var array<int, string>|null */
    private ?array $referencingTables = null;

    /** @var array<string, string|null> */
    private array $referencingColumns = [];

    /** @var array<string, array<int, int>> */
    private array $studentReferenceCounts = [];

    /** @var array<string, int> */
    private array $brokenReferenceCounts = [];

    /** @var array<int, array{sql:string,duration_ms:float}> */
    private array $queries = [];

    private int $queryCount = 0;

    private float $queryTimeMs = 0.0;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, string> */
    public function referencingTables(): array
    {
        if ($this->referencingTables !== null) {
            return $this->referencingTables;
        }

        $statement = $this->runQuery(
            "\n            SELECT DISTINCT table_name\n            FROM information_schema.KEY_COLUMN_USAGE\n            WHERE table_schema = DATABASE()\n              AND referenced_table_name = 'student'\n              AND referenced_column_name = 'st_id'\n            ORDER BY table_name\n        "
        );

        return $this->referencingTables = array_values(array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN)));
    }

    /** @return array<string, int> */
    public function countsForStudent(int $studentId): array
    {
        $counts = [];
        foreach ($this->referencingTables() as $table) {
            $column = $this->referencingColumn($table);
            if ($column === null) {
                continue;
            }

            $quotedTable = $this->quoteIdentifier($table);
            $quotedColumn = $this->quoteIdentifier($column);
            $statement = $this->runQuery("SELECT COUNT(*) FROM {$quotedTable} WHERE {$quotedColumn} = ?", [$studentId]);
            $counts[$table] = (int) $statement->fetchColumn();
        }

        return $counts;
    }

    /** @return array<string, int> */
    public function countsForStudents(array $studentIds): array
    {
        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
        if ($studentIds === []) {
            return [];
        }

        $results = [];
        foreach ($this->referencingTables() as $table) {
            $column = $this->referencingColumn($table);
            if ($column === null) {
                continue;
            }

            $quotedTable = $this->quoteIdentifier($table);
            $quotedColumn = $this->quoteIdentifier($column);
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            $statement = $this->runQuery(
                "SELECT {$quotedColumn} AS student_id, COUNT(*) AS reference_count FROM {$quotedTable} WHERE {$quotedColumn} IN ({$placeholders}) GROUP BY {$quotedColumn}",
                $studentIds
            );

            foreach ($statement->fetchAll(PDO::FETCH_KEY_PAIR) as $studentId => $count) {
                $results[(string) $studentId] = ($results[(string) $studentId] ?? 0) + (int) $count;
            }
        }

        foreach ($studentIds as $studentId) {
            $results[(string) $studentId] = $results[(string) $studentId] ?? 0;
        }

        return $results;
    }

    /** @return array<string, int> */
    public function countsForStudentLazy(int $studentId): array
    {
        return $this->studentReferenceCounts[(string) $studentId] ??= $this->countsForStudent($studentId);
    }

    /** @return array<string, int> */
    public function brokenReferenceCounts(): array
    {
        if ($this->brokenReferenceCounts !== []) {
            return $this->brokenReferenceCounts;
        }

        foreach ($this->referencingTables() as $table) {
            $column = $this->referencingColumn($table);
            if ($column === null) {
                continue;
            }

            $this->brokenReferenceCounts[$table] = (int) $this->runQuery(sprintf(
                'SELECT COUNT(*) FROM %s r LEFT JOIN `student` s ON s.`st_id` = r.%s WHERE r.%s IS NOT NULL AND s.`st_id` IS NULL',
                $this->quoteIdentifier($table),
                $this->quoteIdentifier($column),
                $this->quoteIdentifier($column)
            ))->fetchColumn();
        }

        return $this->brokenReferenceCounts;
    }

    public function brokenReferenceCount(): int
    {
        return array_sum($this->brokenReferenceCounts());
    }

    /** @return array{count:int,time_ms:float,slowest:array<int, array{sql:string,duration_ms:float}>} */
    public function metrics(): array
    {
        $slowest = $this->queries;
        usort($slowest, static fn (array $left, array $right): int => $right['duration_ms'] <=> $left['duration_ms']);

        return [
            'count' => $this->queryCount,
            'time_ms' => round($this->queryTimeMs, 3),
            'slowest' => array_slice($slowest, 0, 5),
        ];
    }

    private function referencingColumn(string $table): ?string
    {
        if (array_key_exists($table, $this->referencingColumns)) {
            return $this->referencingColumns[$table];
        }

        $statement = $this->runQuery(
            "\n            SELECT column_name\n            FROM information_schema.KEY_COLUMN_USAGE\n            WHERE table_schema = DATABASE()\n              AND table_name = ?\n              AND referenced_table_name = 'student'\n              AND referenced_column_name = 'st_id'\n            ORDER BY ordinal_position\n            LIMIT 1\n        ",
            [$table]
        );
        $column = $statement->fetchColumn();

        return $this->referencingColumns[$table] = $column === false ? null : (string) $column;
    }

    private function runQuery(string $sql, array $params = []): PDOStatement
    {
        $started = microtime(true);
        $statement = $params === [] ? $this->pdo->query($sql) : $this->pdo->prepare($sql);
        if ($params !== []) {
            $statement->execute($params);
        }

        $duration = round((microtime(true) - $started) * 1000, 3);
        $this->queryCount++;
        $this->queryTimeMs += $duration;
        $this->queries[] = ['sql' => trim(preg_replace('/\s+/', ' ', $sql) ?? $sql), 'duration_ms' => $duration];

        return $statement;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new RuntimeException('Unsafe database identifier discovered.');
        }

        return '`' . $identifier . '`';
    }
}
