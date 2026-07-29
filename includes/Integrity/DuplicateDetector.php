<?php

declare(strict_types=1);

final class DuplicateDetector
{
    public function __construct(private PDO $pdo, private ReferenceInspector $references)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function duplicateStudents(): array
    {
        $sql = "
            SELECT
                s.st_id,
                s.student_no,
                s.st_lastname,
                s.st_name,
                s.st_middlename,
                s.st_gender,
                s.course_id,
                c.course_acronym,
                duplicates.duplicate_count
            FROM student s
            LEFT JOIN course c ON c.course_id = s.course_id
            INNER JOIN (
                SELECT st_lastname, st_name, st_middlename, COUNT(*) AS duplicate_count
                FROM student
                GROUP BY st_lastname, st_name, st_middlename
                HAVING COUNT(*) > 1
            ) duplicates
                ON duplicates.st_lastname <=> s.st_lastname
               AND duplicates.st_name <=> s.st_name
               AND duplicates.st_middlename <=> s.st_middlename
            ORDER BY s.st_lastname, s.st_name, s.st_middlename, s.st_id
        ";
        $rows = $this->pdo->query($sql)->fetchAll();
        $ids = array_map(static fn (array $row): int => (int) $row['st_id'], $rows);
        $counts = $this->references->countsForStudents($ids);

        foreach ($rows as &$row) {
            $row['reference_count'] = $counts[(string) $row['st_id']] ?? 0;
            $row['status'] = $row['reference_count'] === 0 ? 'Orphan candidate' : 'Referenced';
        }
        unset($row);

        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    public function duplicateStudentNumbers(): array
    {
        return $this->pdo->query("\n            SELECT student_no, COUNT(*) AS record_count\n            FROM student\n            WHERE student_no IS NOT NULL AND TRIM(student_no) <> ''\n            GROUP BY student_no\n            HAVING COUNT(*) > 1\n            ORDER BY student_no\n        ")->fetchAll();
    }
}
