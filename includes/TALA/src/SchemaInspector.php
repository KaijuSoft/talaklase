<?php

declare(strict_types=1);

namespace Tala\Engine;

use PDO;
use Throwable;

final class SchemaInspector
{
    private readonly PDO $source;
    private readonly PDO $destination;

    public function __construct(PDO $source, PDO $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    /**
     * Compares the configured tables and returns a structured diff.
     *
     * @param array<int, string> $tables
     * @return array<string, mixed>
     */
    public function inspect(array $tables): array
    {
        $results = [];
        $overallStatus = true;

        foreach ($tables as $table) {
            $results[] = $this->compareTable((string) $table);
        }

        foreach ($results as $result) {
            if (($result['status'] ?? false) !== true) {
                $overallStatus = false;
                break;
            }
        }

        return [
            'status' => $overallStatus,
            'tables' => $results,
        ];
    }

    /**
     * Compares one table across both connections.
     *
     * @return array<string, mixed>
     */
    public function compareTable(string $table): array
    {
        $sourceExists = $this->tableExists($this->source, $table);
        $destinationExists = $this->tableExists($this->destination, $table);

       if (!$sourceExists || !$destinationExists) {

		$difference = [
			'type' => !$sourceExists
				? 'missing_source_table'
				: 'missing_destination_table',

			'table' => $table,
		];

		if ($sourceExists && !$destinationExists) {

			$difference['definition'] =
				$this->fetchTableDefinition(
					$this->source,
					$table
				);
		}

		return [
			'table' => $table,
			'status' => false,
			'differences' => [
				$difference
			],
		];
	}

        $sourceColumns = $this->fetchColumns($this->source, $table);
        $destinationColumns = $this->fetchColumns($this->destination, $table);
        $sourceIndexes = $this->fetchIndexes($this->source, $table);
        $destinationIndexes = $this->fetchIndexes($this->destination, $table);

        $differences = array_merge(
            $this->compareColumns($sourceColumns, $destinationColumns),
            $this->compareIndexes($sourceIndexes, $destinationIndexes)
        );

        return [
            'table' => $table,
            'status' => $differences === [],
            'differences' => $differences,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function fetchColumns(PDO $pdo, string $table): array
    {
        $statement = $pdo->prepare("
            SELECT
                COLUMN_NAME,
                COLUMN_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ");
        $statement->execute([$table]);

        $columns = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = (string) ($row['COLUMN_NAME'] ?? '');
            if ($name === '') {
                continue;
            }

            $columns[$name] = [
                'type' => strtolower((string) ($row['COLUMN_TYPE'] ?? '')),
                'nullable' => ((string) ($row['IS_NULLABLE'] ?? 'NO')) === 'YES',
                'default' => $this->normalizeDefault($row['COLUMN_DEFAULT'] ?? null),
                'extra' => strtolower((string) ($row['EXTRA'] ?? '')),
            ];
        }

        return $columns;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function fetchIndexes(PDO $pdo, string $table): array
    {
        $statement = $pdo->prepare("
            SELECT
                INDEX_NAME,
                NON_UNIQUE,
                SEQ_IN_INDEX,
                COLUMN_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
        ");
        $statement->execute([$table]);

        $indexes = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = (string) ($row['INDEX_NAME'] ?? '');
            $column = (string) ($row['COLUMN_NAME'] ?? '');
            if ($name === '' || $column === '') {
                continue;
            }

            if (!isset($indexes[$name])) {
                $indexes[$name] = [
                    'unique' => ((string) ($row['NON_UNIQUE'] ?? '1')) === '0',
                    'columns' => [],
                ];
            }

            $indexes[$name]['columns'][] = $column;
        }

        return $indexes;
    }
	
	/**
 * Returns table engine, charset and collation.
 *
 * @return array<string,mixed>
 */
	public function fetchTableOptions(PDO $pdo, string $table): array
	{
		$statement = $pdo->prepare("
			SELECT
				ENGINE,
				TABLE_COLLATION
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = ?
		");

		$statement->execute([$table]);

		$row = $statement->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			return [];
		}

		$collation = (string)($row['TABLE_COLLATION'] ?? '');

		$charset = null;

		if ($collation !== '') {
			$parts = explode('_', $collation, 2);
			$charset = $parts[0];
		}

		return [
			'engine' => $row['ENGINE'] ?? null,
			'charset' => $charset,
			'collation' => $collation,
		];
	}
	
	/**
 * Returns the original CREATE TABLE statement.
 */
	public function fetchCreateTableSQL(PDO $pdo, string $table): string
	{
		$statement = $pdo->query(
			"SHOW CREATE TABLE `{$table}`"
		);

		$row = $statement->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			throw new \RuntimeException(
				"Unable to read CREATE TABLE for {$table}."
			);
		}

		return (string)$row['Create Table'];
	}
	
	/**
 * Returns the complete table definition.
 *
 * @return array<string,mixed>
 */
	public function fetchTableDefinition(PDO $pdo, string $table): array
	{
		return [

			'table' => $table,

			'columns' => $this->fetchColumns(
				$pdo,
				$table
			),

			'indexes' => $this->fetchIndexes(
				$pdo,
				$table
			),

			...$this->fetchTableOptions(
				$pdo,
				$table
			),

			'create_sql' => $this->fetchCreateTableSQL(
				$pdo,
				$table
			),
		];
	}

    /**
     * @param array<string, array<string, mixed>> $sourceColumns
     * @param array<string, array<string, mixed>> $destinationColumns
     * @return array<int, array<string, mixed>>
     */
    public function compareColumns(array $sourceColumns, array $destinationColumns): array
    {
        $differences = [];

        foreach ($sourceColumns as $column => $sourceDefinition) {
            if (!array_key_exists($column, $destinationColumns)) {
                $differences[] = [
                    'type' => 'missing_column',
                    'column' => $column,
                ];
                continue;
            }

            $destinationDefinition = $destinationColumns[$column];

            if ($sourceDefinition['type'] !== $destinationDefinition['type']) {
                $differences[] = [
                    'type' => 'column_type',
                    'column' => $column,
                    'source' => $sourceDefinition['type'],
                    'destination' => $destinationDefinition['type'],
                ];
            }

            if ($sourceDefinition['nullable'] !== $destinationDefinition['nullable']) {
                $differences[] = [
                    'type' => 'column_nullable',
                    'column' => $column,
                    'source' => $sourceDefinition['nullable'],
                    'destination' => $destinationDefinition['nullable'],
                ];
            }

            if ($this->normalizeDefault($sourceDefinition['default']) !== $this->normalizeDefault($destinationDefinition['default'])) {
                $differences[] = [
                    'type' => 'column_default',
                    'column' => $column,
                    'source' => $sourceDefinition['default'],
                    'destination' => $destinationDefinition['default'],
                ];
            }
        }

        foreach ($destinationColumns as $column => $_definition) {
            if (!array_key_exists($column, $sourceColumns)) {
                $differences[] = [
                    'type' => 'missing_column',
                    'column' => $column,
                    'side' => 'source',
                ];
            }
        }

        return $differences;
    }

    /**
     * @param array<string, array<string, mixed>> $sourceIndexes
     * @param array<string, array<string, mixed>> $destinationIndexes
     * @return array<int, array<string, mixed>>
     */
    public function compareIndexes(array $sourceIndexes, array $destinationIndexes): array
    {
        $differences = [];

        foreach ($sourceIndexes as $name => $sourceDefinition) {
            if (!array_key_exists($name, $destinationIndexes)) {
                $differences[] = [
                    'type' => 'missing_index',
                    'index' => $name,
                ];
                continue;
            }

            $destinationDefinition = $destinationIndexes[$name];

            if ($sourceDefinition['unique'] !== $destinationDefinition['unique']) {
                $differences[] = [
                    'type' => 'index_unique',
                    'index' => $name,
                    'source' => $sourceDefinition['unique'],
                    'destination' => $destinationDefinition['unique'],
                ];
            }

            if ($sourceDefinition['columns'] !== $destinationDefinition['columns']) {
                $differences[] = [
                    'type' => 'index_columns',
                    'index' => $name,
                    'source' => $sourceDefinition['columns'],
                    'destination' => $destinationDefinition['columns'],
                ];
            }
        }

        foreach ($destinationIndexes as $name => $_definition) {
            if (!array_key_exists($name, $sourceIndexes)) {
                $differences[] = [
                    'type' => 'missing_index',
                    'index' => $name,
                    'side' => 'source',
                ];
            }
        }

        return $differences;
    }

    public function tableExists(PDO $pdo, string $table): bool
    {
        try {
            $statement = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
            ");
            $statement->execute([$table]);

            return (int) $statement->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function normalizeDefault(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $stringValue = (string) $value;
		
		$stringValue = trim((string)$value);

	if (
		strlen($stringValue) >= 2 &&
		$stringValue[0] === "'" &&
		$stringValue[strlen($stringValue) - 1] === "'"
	) {
		$stringValue = substr($stringValue, 1, -1);
	}

        return $stringValue === '' ? null : $stringValue;
    }
}
