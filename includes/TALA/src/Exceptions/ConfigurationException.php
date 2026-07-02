<?php

declare(strict_types=1);

namespace Tala\Engine\Exceptions;

/**
 * Thrown when a table configuration array is missing required fields,
 * declares an invalid type, or otherwise fails validation before any
 * synchronization work begins.
 */
class ConfigurationException extends SyncException
{
}
