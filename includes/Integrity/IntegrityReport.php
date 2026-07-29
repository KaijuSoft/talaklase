<?php

declare(strict_types=1);

final class IntegrityReport
{
    public function __construct(
        public readonly array $duplicates,
        public readonly array $orphans,
        public readonly array $withoutSections,
        public readonly array $duplicateNumbers,
        public readonly array $referenceTables,
        public readonly int $brokenReferences = 0,
    ) {
    }

    public function summary(): array
    {
        return [
            'duplicate_students' => count($this->duplicates),
            'students_without_sections' => count($this->withoutSections),
            'orphan_students' => count($this->orphans),
            'duplicate_student_numbers' => count($this->duplicateNumbers),
            'broken_references' => $this->brokenReferences,
        ];
    }
}
