<?php

namespace Tala\Engine;

class DataMerger
{
	private string $table;
    private array $analysis;

    public function __construct(string $table, array $analysis)
{
    $this->table = $table;
    $this->analysis = $analysis;
}

    /**
     * Build an executable synchronization plan.
     */
    public function buildPlan(): array
{
    $plan = [];

    foreach ($this->analysis['insert'] as $pk => $row) {

        $plan[] = [

            'operation' => 'insert',

            'table' => $this->table,

            'primary_key' => $pk,

            'data' => $row

        ];

    }

    foreach ($this->analysis['update'] as $pk => $row) {

        $plan[] = [

            'operation' => 'update',

            'table' => $this->table,

            'primary_key' => $pk,

            'data' => $row

        ];

    }

    foreach ($this->analysis['delete'] as $pk => $row) {

        $plan[] = [

            'operation' => 'delete',

            'table' => $this->table,

            'primary_key' => $pk,

            'data' => $row

        ];

    }

    return $plan;
}
}