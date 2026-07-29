<?php

declare(strict_types=1);

final class ReferenceInspector
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, string> */
    public function referencingTables(): array
    {
        $statement = $this->pdo->query("\n            SELECT DISTINCT table_name\n            FROM information_schema.KEY_COLUMN_USAGE\n            WHERE table_schema = DATABASE()\n              AND referenced_table_name = 'student'\n              AND referenced_column_name = 'st_id'\n            ORDER BY table_name\n        ");

        return array_values(array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN)));
    }

    /** @return array<string, int> */
    public function countsForStudent(int $studentId): array
    {
        $counts = [];
        foreach ($this->referencingTables() as $table) {
            $quotedTable = $this->quoteIdentifier($table);
            $column = $this->referencingColumn($table);
            if ($column === null) {
                continue;
            }

            $quotedColumn = $this->quoteIdentifier($column);
            $statement = $this->pdo->prepare("SELECT COUNT(*) FROM {$quotedTable} WHERE {$quotedColumn} = ?");
            $statement->execute([$studentId]);
            $counts[$table] = (int) $statement->fetchColumn();
        }

        return $counts;
    }

    /** @return array<string, int> */
    public function countsForStudents(array $studentIds): array
    {
        $result = [];
        foreach ($studentIds as $studentId) {
            $result[(string) $studentId] = array_sum($this->countsForStudent((int) $studentId));
        }
        return $result;
    }

    public function brokenReferenceCount(): int
    {
        $broken = 0;
        foreach ($this->referencingTables() as $table) {
            $column = $this->referencingColumn($table);
            if ($column === null) continue;
            $statement = $this->pdo->query(sprintf(
                'SELECT COUNT(*) FROM %s r LEFT JOIN `student` s ON s.`st_id` = r.%s WHERE r.%s IS NOT NULL AND s.`st_id` IS NULL',
                $this->quoteIdentifier($table),
                $this->quoteIdentifier($column),
                $this->quoteIdentifier($column)
            ));
            $broken += (int) $statement->fetchColumn();
        }
        return $broken;
    }

    private function referencingColumn(string $table): ?string
    {
        $statement = $this->pdo->prepare("\n            SELECT column_name\n            FROM information_schema.KEY_COLUMN_USAGE\n            WHERE table_schema = DATABASE()\n              AND table_name = ?\n              AND referenced_table_name = 'student'\n              AND referenced_column_name = 'st_id'\n            ORDER BY ordinal_position\n            LIMIT 1\n        ");
        $statement->execute([$table]);
        $column = $statement->fetchColumn();

        return $column === false ? null : (string) $column;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new RuntimeException('Unsafe database identifier discovered.');
        }
        return '`' . $identifier . '`';
    }
}
