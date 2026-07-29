<?php

declare(strict_types=1);

final class IntegrityChecker
{
    private ReferenceInspector $references;
    private DuplicateDetector $duplicates;

    public function __construct(private PDO $pdo)
    {
        $this->references = new ReferenceInspector($pdo);
        $this->duplicates = new DuplicateDetector($pdo, $this->references);
    }

    public function inspect(): IntegrityReport
    {
        $duplicateStudents = $this->duplicates->duplicateStudents();
        $allStudents = $this->pdo->query('SELECT st_id, student_no, st_lastname, st_name, st_middlename FROM student')->fetchAll();
        $allIds = array_map(static fn (array $row): int => (int) $row['st_id'], $allStudents);
        $referenceCounts = $this->references->countsForStudents($allIds);
        $orphans = [];
        foreach ($allStudents as $row) {
            if (($referenceCounts[(string) $row['st_id']] ?? 0) === 0) {
                $row['reference_count'] = 0;
                $orphans[] = $row;
            }
        }

        $withoutSections = $this->pdo->query("\n            SELECT s.st_id, s.student_no, s.st_lastname, s.st_name, s.st_middlename,\n                   c.course_acronym\n            FROM student s\n            LEFT JOIN course c ON c.course_id = s.course_id\n            LEFT JOIN student_section ss ON ss.st_id = s.st_id\n            WHERE ss.st_id IS NULL\n            ORDER BY s.st_lastname, s.st_name, s.st_id\n        ")->fetchAll();

        return new IntegrityReport(
            $duplicateStudents,
            $orphans,
            $withoutSections,
            $this->duplicates->duplicateStudentNumbers(),
            $this->references->referencingTables(),
            $this->references->brokenReferenceCount(),
        );
    }

    /** @return array<string, int> */
    public function referencesForStudent(int $studentId): array
    {
        return $this->references->countsForStudent($studentId);
    }

    public function deleteOrphan(int $studentId): void
    {
        $counts = $this->references->countsForStudent($studentId);
        if (array_sum($counts) !== 0) {
            throw new RuntimeException('Deletion blocked: the student has table references.');
        }

        $statement = $this->pdo->prepare('DELETE FROM student WHERE st_id = ?');
        $statement->execute([$studentId]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Deletion blocked: student record was not found.');
        }
    }
}
