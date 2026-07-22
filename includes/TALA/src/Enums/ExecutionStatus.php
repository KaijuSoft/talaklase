<?php

declare(strict_types=1);

namespace Tala\Engine\Enums;

/**
 * Execution lifecycle states used by the planning layer.
 */
enum ExecutionStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';
}
