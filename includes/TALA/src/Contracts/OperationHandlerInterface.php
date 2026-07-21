<?php

declare(strict_types=1);

namespace Tala\Engine\Handlers;

interface OperationHandlerInterface
{
    /**
     * Execute a single schema operation.
     *
     * @param array $operation
     * @return array
     */
    public static function operation(): OperationType;
}