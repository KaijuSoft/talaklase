<?php

namespace Tala\Engine\Handlers;

use PDO;

final class CreateTableHandler implements OperationHandlerInterface
{
    private PDO $source;
    private PDO $destination;

    public function __construct(PDO $source, PDO $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    public function execute(array $operation): array
{
    $started = microtime(true);

    try {

        $sql = $operation['sql'] ?? null;

        if (empty($sql)) {
            return [
                'status' => 'skipped',
                'reason' => 'No SQL supplied.'
            ];
        }

        $this->destination->exec($sql);

        return [
            'status' => 'executed',
            'duration_ms' =>
                round((microtime(true) - $started) * 1000, 2)
        ];

    } catch (\Throwable $e) {

        return [
            'status' => 'failed',
            'reason' => $e->getMessage(),
            'duration_ms' =>
                round((microtime(true) - $started) * 1000, 2)
        ];
    }
}
}