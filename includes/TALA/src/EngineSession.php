<?php

declare(strict_types=1);

namespace Tala\Engine;

final class EngineSession
{
    private readonly string $uuid;
    private readonly string $startedAt;
    private ?string $finishedAt = null;
    private float $durationMs = 0.0;

    private ?string $source = null;
    private ?string $destination = null;

    private array $health = [];
    private array $snapshot = [];
    private array $inspection = [];
    private array $mergePlan = [];
    private array $validation = [];
    private array $execution = [];
    private array $verification = [];

    public function __construct()
    {
        $this->uuid = self::generateUuid();
        $this->startedAt = date('Y-m-d H:i:s');
    }

    public function setSourceDatabase(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function setDestinationDatabase(string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    public function setHealth(array $health): self
    {
        $this->health = $health;
        return $this;
    }

    public function setSnapshot(array $snapshot): self
    {
        $this->snapshot = $snapshot;
        return $this;
    }

    public function setInspection(array $inspection): self
    {
        $this->inspection = $inspection;
        return $this;
    }

    public function setMergePlan(array $mergePlan): self
    {
        $this->mergePlan = $mergePlan;
        return $this;
    }

    public function setValidation(array $validation): self
    {
        $this->validation = $validation;
        return $this;
    }

    public function setExecution(array $execution): self
    {
        $this->execution = $execution;
        return $this;
    }

    public function setVerification(array $verification): self
    {
        $this->verification = $verification;
        return $this;
    }

    public function finish(): self
    {
        $this->finishedAt = date('Y-m-d H:i:s');
        $started = strtotime($this->startedAt) ?: time();
        $finished = strtotime($this->finishedAt) ?: $started;
        $this->durationMs = round(max(0, $finished - $started) * 1000, 3);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'duration_ms' => $this->durationMs,
            'source' => $this->source,
            'destination' => $this->destination,
            'health' => $this->health,
            'snapshot' => $this->snapshot,
            'inspection' => $this->inspection,
            'merge_plan' => $this->mergePlan,
            'validation' => $this->validation,
            'execution' => $this->execution,
            'verification' => $this->verification,
        ];
    }

    private static function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
