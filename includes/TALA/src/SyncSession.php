<?php

declare(strict_types=1);

namespace Tala\Engine;

/**
 * The structured result of one syncDatabase() run.
 *
 * Replaces the raw array previously returned by the sync routine.
 * Immutable identity/timing fields are readonly; running totals are
 * accumulated internally as each table finishes (see addTableResult()).
 */
final class SyncSession
{
    public readonly string $uuid;
    public readonly float $startTime;
    public readonly string $source;
    public readonly string $destination;

    private float $endTime = 0.0;
    private float $duration = 0.0;

    private int $inserted = 0;
    private int $skipped = 0;
    private int $modified = 0;
    private int $failed = 0;

    /** @var array<int, array<string, mixed>> Per-table metrics, see TalaEngine::syncTable(). */
    private array $tableResults = [];

    /** @var array<int, array<string, mixed>> Detected (unresolved) business-key conflicts. */
    private array $conflicts = [];

    public function __construct(string $uuid, float $startTime, string $source, string $destination)
    {
        $this->uuid = $uuid;
        $this->startTime = $startTime;
        $this->source = $source;
        $this->destination = $destination;
    }

    /**
     * Folds one table's metrics into the session totals.
     *
     * @param array<string, mixed> $metrics
     */
    public function addTableResult(array $metrics): void
    {
        $this->tableResults[] = $metrics;

        $this->inserted += (int) ($metrics['inserted'] ?? 0);
        $this->skipped += (int) ($metrics['skipped'] ?? 0);
        $this->modified += (int) ($metrics['modified'] ?? 0);
        $this->failed += (int) ($metrics['failed'] ?? 0);

        foreach ($metrics['conflicts'] ?? [] as $conflict) {
            $this->conflicts[] = $conflict;
        }
    }

    public function finish(float $endTime): void
    {
        $this->endTime = $endTime;
        $this->duration = $endTime - $this->startTime;
    }

    public function endTime(): float
    {
        return $this->endTime;
    }

    public function duration(): float
    {
        return $this->duration;
    }

    public function inserted(): int
    {
        return $this->inserted;
    }

    public function skipped(): int
    {
        return $this->skipped;
    }

    public function modified(): int
    {
        return $this->modified;
    }

    public function failed(): int
    {
        return $this->failed;
    }

    /** @return array<int, array<string, mixed>> */
    public function tableResults(): array
    {
        return $this->tableResults;
    }

    /** @return array<int, array<string, mixed>> */
    public function conflicts(): array
    {
        return $this->conflicts;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'duration' => $this->duration,
            'source' => $this->source,
            'destination' => $this->destination,
            'inserted' => $this->inserted,
            'skipped' => $this->skipped,
            'modified' => $this->modified,
            'failed' => $this->failed,
            'tables' => $this->tableResults,
            'conflicts' => $this->conflicts,
        ];
    }
	
	public function summary(): string
{
    $lines = [];

    $lines[] = str_repeat('=', 60);
    $lines[] = 'TALA ENGINE SYNCHRONIZATION REPORT';
    $lines[] = str_repeat('=', 60);
    $lines[] = '';

    $lines[] = "Session ID : {$this->uuid}";
    $lines[] = "Source     : {$this->source}";
    $lines[] = "Destination: {$this->destination}";
    $lines[] = sprintf("Duration   : %.3f sec", $this->duration);

    $lines[] = '';
    $lines[] = str_repeat('-', 60);

    $lines[] = "Inserted : {$this->inserted}";
    $lines[] = "Skipped  : {$this->skipped}";
    $lines[] = "Modified : {$this->modified}";
    $lines[] = "Failed   : {$this->failed}";

    $lines[] = str_repeat('-', 60);
    $lines[] = '';

    foreach ($this->tableResults as $table) {

        $lines[] = strtoupper($table['table']);

        $lines[] = "  Strategy : {$table['strategy']}";
        $lines[] = "  Read     : {$table['rows_read']}";
        $lines[] = "  Inserted : {$table['inserted']}";
        $lines[] = "  Skipped  : {$table['skipped']}";
        $lines[] = "  Modified : {$table['modified']}";
        $lines[] = "  Failed   : {$table['failed']}";

        if (!empty($table['conflicts'])) {

            $lines[] = "  Conflicts: " . count($table['conflicts']);

            foreach ($table['conflicts'] as $conflict) {

                $key = '';

                foreach ($conflict['business_key'] as $column => $value) {
                    $key .= "{$column}={$value} ";
                }

                $lines[] = "    {$key}";

                foreach ($conflict['changed_fields'] as $field) {
                    $lines[] = "      • {$field}";
                }
            }
        }

        $lines[] = '';
    }

    return implode(PHP_EOL, $lines);
	}
}
