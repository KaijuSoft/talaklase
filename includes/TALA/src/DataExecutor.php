<?php

namespace Tala\Engine;

use PDO;
use PDOException;

class DataExecutor
{
    private PDO $pdo;
    private array $plan;

    public function __construct(PDO $pdo, array $plan)
    {
        $this->pdo = $pdo;
        $this->plan = $plan;
    }

    public function execute(): array
    {
        $result = [

            'status' => true,

            'executed' => 0,

            'failed' => 0,

            'skipped' => 0,

            'operations' => []

        ];

        foreach ($this->plan['operations'] as $operation) {

            switch ($operation['operation']) {

                case 'insert':

                    $this->insert($operation);

                    $result['executed']++;

                    break;

                case 'update':

                    $result['skipped']++;

                    break;

                case 'delete':

                    $result['skipped']++;

                    break;

            }

        }

        return $result;
    }

    private function insert(array $operation): void
    {

    }
}