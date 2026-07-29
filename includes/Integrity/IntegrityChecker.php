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

    public function summary(): array
    {
        return [
            'duplicate_students' => $this->duplicateStudentCount(),
            'students_without_sections' => $this->studentsWithoutSectionsCount(),
            'orphan_students' => $this->orphanStudentCount(),
            'duplicate_student_numbers' => $this->duplicateStudentNumberCount(),
            'broken_references' => $this->references->brokenReferenceCount(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function duplicateStudents(): array
    {
        return $this->duplicates->duplicateStudents();
    }

    /** @return array<int, array<string, mixed>> */
    public function orphanStudents(): array
    {
        $students = $this->allStudents();
        $ids = array_map(static fn (array $row): int => (int) $row['st_id'], $students);
        $referenceCounts = $this->references->countsForStudents($ids);

        $orphans = [];
        foreach ($students as $row) {
            if (($referenceCounts[(string) $row['st_id']] ?? 0) !== 0) {
                continue;
            }

            $row['reference_count'] = 0;
            $orphans[] = $row;
        }

        return $orphans;
    }

    /** @return array<int, array<string, mixed>> */
    public function studentsWithoutSections(): array
    {
        return $this->pdo->query("\n            SELECT s.st_id, s.student_no, s.st_lastname, s.st_name, s.st_middlename,\n                   c.course_acronym\n            FROM student s\n            LEFT JOIN course c ON c.course_id = s.course_id\n            LEFT JOIN student_section ss ON ss.st_id = s.st_id\n            WHERE ss.st_id IS NULL\n            ORDER BY s.st_lastname, s.st_name, s.st_id\n        ")->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function duplicateStudentNumbers(): array
    {
        return $this->duplicates->duplicateStudentNumbers();
    }

    /** @return array<int, string> */
    public function referencingTables(): array
    {
        return $this->references->referencingTables();
    }

    /** @return array<string, int> */
    public function referencesForStudent(int $studentId): array
    {
        return $this->references->countsForStudentLazy($studentId);
    }

    public function duplicateStudentCount(): int
    {
        return (int) $this->pdo->query("\n            SELECT COUNT(*)\n            FROM (\n                SELECT 1\n                FROM student\n                GROUP BY st_lastname, st_name, st_middlename\n                HAVING COUNT(*) > 1\n            ) duplicates\n        ")->fetchColumn();
    }

    public function studentsWithoutSectionsCount(): int
    {
        return (int) $this->pdo->query("\n            SELECT COUNT(*)\n            FROM student s\n            LEFT JOIN student_section ss ON ss.st_id = s.st_id\n            WHERE ss.st_id IS NULL\n        ")->fetchColumn();
    }

    public function orphanStudentCount(): int
    {
        return count($this->orphanStudents());
    }

    public function duplicateStudentNumberCount(): int
    {
        return (int) $this->pdo->query("\n            SELECT COUNT(*)\n            FROM (\n                SELECT student_no\n                FROM student\n                WHERE student_no IS NOT NULL AND TRIM(student_no) <> ''\n                GROUP BY student_no\n                HAVING COUNT(*) > 1\n            ) duplicates\n        ")->fetchColumn();
    }

    public function deleteOrphan(int $studentId): void
    {
        $counts = $this->references->countsForStudentLazy($studentId);
        if (array_sum($counts) !== 0) {
            throw new RuntimeException('Deletion blocked: the student has table references.');
        }

        $statement = $this->pdo->prepare('DELETE FROM student WHERE st_id = ?');
        $statement->execute([$studentId]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Deletion blocked: student record was not found.');
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function allStudents(): array
    {
        return $this->pdo->query('SELECT st_id, student_no, st_lastname, st_name, st_middlename FROM student')->fetchAll();
    }
}
