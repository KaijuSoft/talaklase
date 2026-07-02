<?php

declare(strict_types=1);

/**
 * TALA Engine — Generic Database Synchronization Middleware
 *
 * Safely merges missing records from a source database into a destination
 * database without destroying, overwriting, truncating, or deleting any
 * existing data.
 *
 * This engine is intentionally application-agnostic. It carries no dependency
 * on TalaKlase or any other consuming system. It requires only two PDO
 * database connections as input.
 *
 * Designed for future use by:
 *   - TalaKlase   (school records)
 *   - OJT Portal
 *   - Inventory
 *   - Library
 *   - Payroll
 *
 * @package  KaijuSoft\TALAEngine
 * @version  0.1.0-alpha
 */
class TALAEngine
{
    // =========================================================================
    // Version
    // =========================================================================

    /**
     * Current engine version following Semantic Versioning (semver.org).
     */
    public const VERSION = '0.1.0-alpha';

    // =========================================================================
    // Synchronization Type Constants
    // =========================================================================

    /**
     * Reference data: static or slow-changing lookup tables.
     *
     * Examples: department, course, subject, academic_year
     * v0.1.0 strategy: insert missing records; skip existing ones.
     */
    private const TYPE_REFERENCE = 'reference';

    /**
     * Master records: primary entities managed by the system.
     *
     * Examples: student, employee, instructor
     * v0.1.0 strategy: insert missing records; skip existing ones.
     * (Update support planned for v0.2.0+)
     */
    private const TYPE_MASTER = 'master';

    /**
     * Transactional records: time-sequenced or event-driven entries.
     *
     * Examples: attendance_log, grade_entry, ojt_hours
     * v0.1.0 strategy: insert missing records; skip existing ones.
     * (Journal and rollback support planned for v0.3.0+)
     */
    private const TYPE_TRANSACTION = 'transaction';

    // =========================================================================
    // Synchronization Table Registry
    // =========================================================================

    /**
     * Registry of tables managed by TALA Engine.
     *
     * Each entry configures:
     *   key  (string|string[]) — Column(s) that uniquely identify a row.
     *                            Pass an array for composite primary keys.
     *   type (string)          — One of TYPE_REFERENCE, TYPE_MASTER,
     *                            or TYPE_TRANSACTION.
     *
     * Composite key example:
     * <code>
     * 'attendance_log' => [
     *     'key'  => ['student_id', 'class_id', 'log_date'],
     *     'type' => self::TYPE_TRANSACTION,
     * ]
     * </code>
     *
     * @var array<string, array{key: string|string[], type: string}>
     */
    private static array $SYNC_TABLES = [

        'department' => [
            'key'  => 'dept_id',
            'type' => self::TYPE_REFERENCE,
        ],

        'course' => [
            'key'  => 'course_id',
            'type' => self::TYPE_REFERENCE,
        ],

        'subject' => [
            'key'  => 'sub_id',
            'type' => self::TYPE_REFERENCE,
        ],

        'academic_year' => [
            'key'  => 'ay_id',
            'type' => self::TYPE_REFERENCE,
        ],

    ];

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Synchronize all registered tables from source to destination.
     *
     * Iterates every table defined in $SYNC_TABLES, delegates each table to
     * mergeTable(), collects per-table results, and fires the optional progress
     * callback after each table completes.
     *
     * This method never deletes, truncates, drops, or overwrites any row.
     * Only records absent from the destination are inserted.
     *
     * Usage:
     * <code>
     * $results = TALAEngine::syncDatabase(
     *     $srcPdo,
     *     $dstPdo,
     *     function (array $result): void {
     *         echo "{$result['table']}: "
     *            . "inserted={$result['inserted']} "
     *            . "skipped={$result['skipped']} "
     *            . "failed={$result['failed']}\n";
     *     }
     * );
     * </code>
     *
     * @param PDO           $src      Active PDO connection to the source database.
     * @param PDO           $dst      Active PDO connection to the destination database.
     * @param callable|null $progress Optional callback fired after each table completes.
     *                                Receives one argument: the mergeTable() result array.
     *                                Signature: function(array $result): void
     *
     * @return array<string, array{table: string, inserted: int, skipped: int, failed: int}>
     *         Associative array of results keyed by table name.
     */
    public static function syncDatabase(
        PDO $src,
        PDO $dst,
        ?callable $progress = null
    ): array {
        $results = [];

        foreach (self::$SYNC_TABLES as $table => $config) {
            $keys = is_array($config['key']) ? $config['key'] : [$config['key']];

            $result = self::mergeTable($src, $dst, $table, $keys);

            $results[$table] = $result;

            if ($progress !== null) {
                $progress($result);
            }
        }

        return $results;
    }

    /**
     * Merge missing rows from a single source table into the destination.
     *
     * Algorithm:
     *   1. Fetch every row from the source table via SELECT *.
     *   2. For each row, build a parameterized WHERE clause from the key columns.
     *   3. Query the destination for a matching row.
     *   4. If the row already exists in destination  → skipped++
     *   5. If the row is absent from destination     → INSERT full row → inserted++
     *   6. Any caught PDOException                   → failed++
     *
     * The existence check and INSERT statement are each prepared once outside
     * the loop and reused across all rows for efficiency.
     *
     * This method never deletes, truncates, drops, or updates existing rows.
     *
     * @param PDO      $src   Active PDO connection to the source database.
     * @param PDO      $dst   Active PDO connection to the destination database.
     * @param string   $table Target table name. Must be a trusted, validated value.
     * @param string[] $keys  One or more column names that together identify a unique row.
     *
     * @return array{table: string, inserted: int, skipped: int, failed: int}
     */
    public static function mergeTable(
        PDO $src,
        PDO $dst,
        string $table,
        array $keys
    ): array {
        $result = [
            'table'    => $table,
            'inserted' => 0,
            'skipped'  => 0,
            'failed'   => 0,
        ];

        // -------------------------------------------------------------------------
        // Step 1: Read all rows from source
        // -------------------------------------------------------------------------
        try {
            $sourceRows = $src
                ->query("SELECT * FROM `{$table}`")
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            // Source table unreadable — nothing to merge
            $result['failed']++;
            return $result;
        }

        if (empty($sourceRows)) {
            return $result;
        }

        // -------------------------------------------------------------------------
        // Step 2: Prepare the destination existence check (prepared once, reused)
        //
        //   Builds: SELECT 1 FROM `table`
        //            WHERE `key1` = ? AND `key2` = ?
        //            LIMIT 1
        // -------------------------------------------------------------------------
        $whereFragments = array_map(
            fn(string $col): string => "`{$col}` = ?",
            $keys
        );
        $whereSql = implode(' AND ', $whereFragments);

        // -------------------------------------------------------------------------
        // Step 3: Prepare the INSERT statement using columns derived from the first
        //         row. All rows from SELECT * share identical column structure.
        //
        //   Builds: INSERT INTO `table` (`col1`, `col2`, ...) VALUES (?, ?, ...)
        // -------------------------------------------------------------------------
        $columns      = array_keys($sourceRows[0]);
        $columnList   = implode(', ', array_map(fn(string $c): string => "`{$c}`", $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        try {
            $existsStmt = $dst->prepare(
                "SELECT 1 FROM `{$table}` WHERE {$whereSql} LIMIT 1"
            );
            $insertStmt = $dst->prepare(
                "INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholders})"
            );
        } catch (\PDOException) {
            // Destination table incompatible — cannot proceed with this table
            $result['failed']++;
            return $result;
        }

        // -------------------------------------------------------------------------
        // Step 4: Process each source row
        // -------------------------------------------------------------------------
        foreach ($sourceRows as $row) {
            try {
                $keyValues = array_map(
                    fn(string $col): mixed => $row[$col] ?? null,
                    $keys
                );

                $existsStmt->execute($keyValues);
                $rowExists = (bool) $existsStmt->fetchColumn();

                if ($rowExists) {
                    $result['skipped']++;
                    continue;
                }

                $insertStmt->execute(array_values($row));
                $result['inserted']++;

            } catch (\PDOException) {
                $result['failed']++;
            }
        }

        return $result;
    }

    // =========================================================================
    // ROADMAP — Planned for future versions
    // =========================================================================

    // -------------------------------------------------------------------------
    // v0.2.0 — Record Comparison & Selective Update
    // -------------------------------------------------------------------------

    // compareRecord(array $sourceRow, array $destRow, array $ignoredColumns = []): bool
    //
    //   Compare a source row and a destination row column by column.
    //   Returns true when the rows are logically identical after excluding
    //   ignored columns (e.g. created_at, updated_at, sync_ts).
    //   Acts as the prerequisite gate before calling updateRecord().

    // updateRecord(PDO $dst, string $table, array $row, array $keys): bool
    //
    //   Overwrite an existing destination row with values from the source row
    //   when compareRecord() detects a difference.
    //   Controlled by a per-table 'allow_update' flag in $SYNC_TABLES to
    //   prevent accidental overwrites on tables that must remain immutable.

    // -------------------------------------------------------------------------
    // v0.2.0 — Conflict Resolution
    // -------------------------------------------------------------------------

    // resolveConflict(array $sourceRow, array $destRow, string $strategy): array
    //
    //   Apply a named conflict-resolution strategy when both source and
    //   destination rows have been independently modified.
    //   Planned strategies:
    //     'source_wins'  — Always take the source row.
    //     'dest_wins'    — Always preserve the destination row.
    //     'latest_wins'  — Compare updated_at timestamps; keep the newer row.
    //     'manual'       — Queue the conflict for user resolution.
    //   Returns the row that should be persisted.

    // -------------------------------------------------------------------------
    // v0.3.0 — Transaction Journaling & Rollback
    // -------------------------------------------------------------------------

    // transactionJournal(PDO $dst, string $table, string $action, array $row): void
    //
    //   Write a structured journal entry to a `_tala_sync_log` table for every
    //   INSERT, UPDATE, or conflict encountered during a sync session.
    //   Each entry captures: session_id, table, action, key_values,
    //   row_snapshot, synced_at.
    //   Enables full audit history and selective replay.

    // rollback(PDO $dst, string $sessionId): bool
    //
    //   Revert all inserts and updates recorded under a specific sync session,
    //   identified by sessionId from the transaction journal.
    //   Requires transactionJournal() to have been active during the session.

    // -------------------------------------------------------------------------
    // v0.3.0 — Structured Logging
    // -------------------------------------------------------------------------

    // syncLogger(string $level, string $message, array $context = []): void
    //
    //   PSR-3 compatible internal diagnostic logger.
    //   Supports pluggable drivers: file, database, stdout.
    //   Log levels: DEBUG, INFO, WARNING, ERROR.
    //   Consumed internally by all engine methods for structured output
    //   without coupling to any application framework.

}