<?php

declare(strict_types=1);

namespace Tala\Engine\Contracts;

use Tala\Engine\Enums\OperationType;

interface OperationHandlerInterface
{
    /**
     * Executes a single operation.
     */
    public function execute(array $operation): array;

    /**
     * Returns the supported operation type.
     */
    public static function operation(): OperationType;
}