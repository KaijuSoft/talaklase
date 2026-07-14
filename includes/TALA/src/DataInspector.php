<?php

namespace Tala\Engine;

class DataInspector
{
    private array $source;
    private array $destination;

    public function __construct(array $source, array $destination)
    {
		
        $this->source = $source;
        $this->destination = $destination;
    }

    /**
     * Analyze the differences between two table snapshots.
     */
    public function analyze(): array
{
    $sourceRows = $this->source['rows'];
    $destinationRows = $this->destination['rows'];

    $result = [
        'insert' => [],
        'update' => [],
        'delete' => [],
        'unchanged' => []
    ];

    foreach ($sourceRows as $pk => $row) {

        if (!isset($destinationRows[$pk])) {

            $result['insert'][$pk] = $row;

        } else {

            if ($row == $destinationRows[$pk]) {

                $result['unchanged'][$pk] = $row;

            } else {

                $result['update'][$pk] = [
                    'source' => $row,
                    'destination' => $destinationRows[$pk]
                ];

            }
        }
    }

    foreach ($destinationRows as $pk => $row) {

        if (!isset($sourceRows[$pk])) {

            $result['delete'][$pk] = $row;

        }
    }

    return $result;
}
}