<?php

declare(strict_types=1);

final class MaintenanceManager
{
    public function __construct(private PDO $pdo, private ReferenceInspector $references)
    {
    }

    public function analyzeStudentMerge(int $survivorId, int $duplicateId): array
    {
        if ($survivorId <= 0 || $duplicateId <= 0 || $survivorId === $duplicateId) {
            throw new InvalidArgumentException('Choose two different student records.');
        }
        $survivor = $this->student($survivorId);
        $duplicate = $this->student($duplicateId);
        if ($survivor === null || $duplicate === null) {
            throw new RuntimeException('Both student records must exist before a merge can be prepared.');
        }
        return [
            'survivor' => $survivor,
            'duplicate' => $duplicate,
            'survivor_references' => $this->references->countsForStudentLazy($survivorId),
            'duplicate_references' => $this->references->countsForStudentLazy($duplicateId),
            'field_differences' => $this->fieldDifferences($survivor, $duplicate),
            'warnings' => [
                'The survivor record is retained unchanged.',
                'Related references are reassigned before the duplicate is deleted.',
                'The operation is blocked if affected tables are not transactional.',
                'A local database backup is created immediately before the destructive operation.',
            ],
        ];
    }

    public function mergeStudents(int $survivorId, int $duplicateId, string $reason, bool $confirmFieldDifferences = false): array
    {
        $reason = trim($reason);
        if ($reason === '') throw new InvalidArgumentException('A merge reason is required.');
        $plan = $this->analyzeStudentMerge($survivorId, $duplicateId);
        if ($plan['field_differences'] !== [] && !$confirmFieldDifferences) {
            throw new RuntimeException('Merge blocked: student fields differ. Confirm that the survivor fields should be kept.');
        }
        $this->assertTransactionalTables();
        $backup = $this->createLocalBackup();
        $updated = [];
        try {
            $this->pdo->beginTransaction();
            foreach ($this->references->referencingTables() as $table) {
                $column = $this->referenceColumn($table);
                if ($column === null) continue;
                $tableId = $this->quoteIdentifier($table);
                $columnId = $this->quoteIdentifier($column);
                $statement = $this->pdo->prepare("UPDATE {$tableId} SET {$columnId} = ? WHERE {$columnId} = ?");
                $statement->execute([$survivorId, $duplicateId]);
                if ($statement->rowCount() > 0) $updated[$table] = $statement->rowCount();
            }
            $delete = $this->pdo->prepare('DELETE FROM student WHERE st_id = ?');
            $delete->execute([$duplicateId]);
            if ($delete->rowCount() !== 1) throw new RuntimeException('Merge aborted: duplicate student could not be removed.');
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw new RuntimeException('Merge aborted and rolled back: ' . $exception->getMessage(), 0, $exception);
        }
        $auditLogged = $this->writeAudit('MERGE_STUDENT', $survivorId, $duplicateId, $reason, $backup, $updated);
        return ['backup'=>$backup,'survivor_id'=>$survivorId,'duplicate_id'=>$duplicateId,'updated'=>$updated,'reason'=>$reason,'audit_logged'=>$auditLogged];
    }

    public function deleteOrphan(int $studentId, string $reason): string
    {
        $reason = trim($reason);
        if ($reason === '') throw new InvalidArgumentException('A deletion reason is required.');
        $counts = $this->references->countsForStudentLazy($studentId);
        if (array_sum($counts) !== 0) throw new RuntimeException('Deletion blocked: the student has table references.');
        if ($this->student($studentId) === null) throw new RuntimeException('Deletion blocked: student record was not found.');
        $this->assertTransactionalTables();
        $backup = $this->createLocalBackup();
        try {
            $this->pdo->beginTransaction();
            $statement = $this->pdo->prepare('DELETE FROM student WHERE st_id = ?');
            $statement->execute([$studentId]);
            if ($statement->rowCount() !== 1) throw new RuntimeException('Deletion blocked: student record was not found.');
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw new RuntimeException('Orphan deletion aborted and rolled back: ' . $exception->getMessage(), 0, $exception);
        }
        $this->writeAudit('DELETE_ORPHAN', $studentId, null, $reason, $backup, []);
        return $backup;
    }

    private function assertTransactionalTables(): void
    {
        $tables = array_merge(['student'], $this->references->referencingTables());
        $placeholders = implode(',', array_fill(0, count($tables), '?'));
        $stmt = $this->pdo->prepare("SELECT table_name, engine FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ({$placeholders})");
        $stmt->execute($tables);
        $nonTransactional = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (strtolower((string) $row['engine']) !== 'innodb') $nonTransactional[] = $row['table_name'] . ' (' . ($row['engine'] ?? 'unknown') . ')';
        }
        if ($nonTransactional !== []) {
            throw new RuntimeException('Maintenance blocked: affected tables are not transactional: ' . implode(', ', $nonTransactional));
        }
    }

    private function student(int $studentId): ?array
    {
        $statement = $this->pdo->prepare('SELECT s.st_id, s.student_no, s.st_lastname, s.st_name, s.st_middlename, s.st_gender, s.course_id, c.course_acronym FROM student s LEFT JOIN course c ON c.course_id = s.course_id WHERE s.st_id = ?');
        $statement->execute([$studentId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function fieldDifferences(array $survivor, array $duplicate): array
    {
        $fields = ['student_no','st_lastname','st_name','st_middlename','st_gender','course_id'];
        $differences = [];
        foreach ($fields as $field) {
            if ((string)($survivor[$field] ?? '') !== (string)($duplicate[$field] ?? '')) {
                $differences[$field] = ['survivor'=>$survivor[$field] ?? null,'duplicate'=>$duplicate[$field] ?? null];
            }
        }
        return $differences;
    }

    private function referenceColumn(string $table): ?string
    {
        $statement=$this->pdo->prepare("SELECT column_name FROM information_schema.KEY_COLUMN_USAGE WHERE table_schema=DATABASE() AND table_name=? AND referenced_table_name='student' AND referenced_column_name='st_id' ORDER BY ordinal_position LIMIT 1");
        $statement->execute([$table]);
        $column=$statement->fetchColumn();
        return $column===false ? null : (string)$column;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$identifier)!==1) throw new RuntimeException('Unsafe database identifier discovered.');
        return '`'.$identifier.'`';
    }

    private function createLocalBackup(): string
    {
        $directory=__DIR__.'/../../storage/backups';
        if(!is_dir($directory) && !mkdir($directory,0775,true) && !is_dir($directory)) throw new RuntimeException('Maintenance aborted: unable to create backup directory.');
        $filename='talaklase_maintenance_local_'.date('Y-m-d_H-i-s').'_'.bin2hex(random_bytes(3)).'.sql';
        $path=$directory.DIRECTORY_SEPARATOR.$filename;
        $handle=fopen($path,'wb');
        if($handle===false) throw new RuntimeException('Maintenance aborted: unable to create database backup.');
        try {
            $tables=$this->pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
            foreach($tables as $table){
                $quoted=$this->quoteIdentifier((string)$table);
                $create=$this->pdo->query("SHOW CREATE TABLE {$quoted}")->fetch(PDO::FETCH_ASSOC);
                fwrite($handle,"DROP TABLE IF EXISTS {$quoted};\n".$create['Create Table'].";\n\n");
                foreach($this->pdo->query("SELECT * FROM {$quoted}")->fetchAll(PDO::FETCH_ASSOC) as $row){
                    $values=array_map(fn($value)=>$value===null?'NULL':$this->pdo->quote((string)$value),array_values($row));
                    fwrite($handle,sprintf("INSERT INTO %s VALUES (%s);\n",$quoted,implode(',',$values)));
                }
                fwrite($handle,"\n");
            }
        } finally { fclose($handle); }
        if(!is_file($path) || filesize($path)===0){@unlink($path); throw new RuntimeException('Maintenance aborted: generated backup is empty.');}
        return $filename;
    }

    private function writeAudit(string $action,int $primaryId,?int $secondaryId,string $reason,string $backup,array $updated): bool
    {
        try {
            $directory=__DIR__.'/../../storage/integrity';
            if(!is_dir($directory) && !mkdir($directory,0775,true) && !is_dir($directory)) return false;
            $entry=['timestamp'=>date('c'),'action'=>$action,'primary_id'=>$primaryId,'secondary_id'=>$secondaryId,'reason'=>$reason,'backup'=>$backup,'updated_references'=>$updated];
            return file_put_contents($directory.'/maintenance.log',json_encode($entry,JSON_UNESCAPED_SLASHES).PHP_EOL,FILE_APPEND|LOCK_EX)!==false;
        } catch(Throwable) { return false; }
    }
}
