<?php

declare(strict_types=1);

namespace Tala\Engine;

/**
 * Logging contract for the TALA Engine.
 *
 * Goal 11 calls for an interface only — no concrete drivers. The engine
 * accepts any LoggerInterface implementation and will call log() for
 * warnings (e.g. a table sync that failed and rolled back). Concrete
 * drivers (file, stdout, database) are expected to be supplied by the
 * consuming application (TalaKlase, OJT Portal, etc.) in a later
 * version; none are implemented here.
 *
 * Suggested levels (kept as plain strings rather than an enum so any
 * future driver — including PSR-3 loggers — can adapt to it):
 * 'debug', 'info', 'warning', 'error', 'critical'.
 */
interface LoggerInterface
{
    /**
     * @param string               $level   Severity level, e.g. 'error', 'warning', 'info'.
     * @param string               $message Human-readable log message.
     * @param array<string, mixed> $context Structured context (table name, exception, etc.).
     */
    public function log(string $level, string $message, array $context = []): void;
}
