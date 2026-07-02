<?php

declare(strict_types=1);

namespace Tala\Engine\Exceptions;

/**
 * Thrown by the dependency resolver when table configuration declares
 * a circular dependency (e.g. A depends on B, B depends on A) or a
 * dependency on a table that has no configuration entry.
 */
class DependencyException extends SyncException
{
}
