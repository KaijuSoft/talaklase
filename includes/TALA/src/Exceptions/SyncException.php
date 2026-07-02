<?php

declare(strict_types=1);

namespace Tala\Engine\Exceptions;

use RuntimeException;

/**
 * Base exception for all TALA Engine synchronization failures.
 *
 * All other engine-specific exceptions extend this class, so callers
 * that only care about "something went wrong during sync" can catch
 * SyncException alone instead of enumerating every subtype.
 */
class SyncException extends RuntimeException
{
}
