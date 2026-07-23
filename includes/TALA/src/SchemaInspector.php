<?php

declare(strict_types=1);

namespace Tala\Engine;

use PDO;
use Throwable;

final class SchemaInspector
{
    private readonly PDO $source;
    private readonly PDO $destination;
    /** @var array<int, bool> */
    private array $supportsDatetimePrecision = [];

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

        $differenceCount = 0;
        foreach ($results as $result) {
            $differenceCount += is_array($result['differences'] ?? null) ? count($result['differences']) : 0;
        }

        error_log(sprintf(
            'SchemaInspector::inspect differences=%d tables=%d',
            $differenceCount,
            count($results)
        ));

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
            $this->compareColumns($sourceColumns, $destinationColumns, $table),
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
        $datetimePrecisionSupported = $this->supportsDatetimePrecision($pdo);
        error_log(sprintf(
            'SchemaInspector::fetchColumns table=%s supportsDatetimePrecision=%s',
            $table,
            $datetimePrecisionSupported ? 'true' : 'false'
        ));

        $selectColumns = [
            'COLUMN_NAME',
            'COLUMN_TYPE',
            'CHARACTER_MAXIMUM_LENGTH',
            'CHARACTER_OCTET_LENGTH',
            'NUMERIC_PRECISION',
            'NUMERIC_SCALE',
            'IS_NULLABLE',
            'COLUMN_DEFAULT',
            'EXTRA',
            'COLUMN_COMMENT',
            'COLLATION_NAME',
            'CHARACTER_SET_NAME',
            'ORDINAL_POSITION',
        ];

        if ($datetimePrecisionSupported) {
            $selectColumns[] = 'DATETIME_PRECISION';
        }

        $statement = $pdo->prepare("
            SELECT
                " . implode(",\n                ", $selectColumns) . "
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ");
        error_log('SchemaInspector::fetchColumns SQL: ' . str_replace("\n", ' ', trim($statement->queryString)));
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
                'collation' => $this->normalizeStringOrNull($row['COLLATION_NAME'] ?? null),
                'charset' => $this->normalizeStringOrNull($row['CHARACTER_SET_NAME'] ?? null),
                'comment' => $this->normalizeStringOrNull($row['COLUMN_COMMENT'] ?? null),
                'length' => $this->normalizeIntegerOrNull($row['CHARACTER_MAXIMUM_LENGTH'] ?? null),
                'octet_length' => $this->normalizeIntegerOrNull($row['CHARACTER_OCTET_LENGTH'] ?? null),
                'precision' => $this->normalizeIntegerOrNull($row['NUMERIC_PRECISION'] ?? null),
                'scale' => $this->normalizeIntegerOrNull($row['NUMERIC_SCALE'] ?? null),
                'datetime_precision' => $datetimePrecisionSupported
                    ? $this->normalizeIntegerOrNull($row['DATETIME_PRECISION'] ?? null)
                    : null,
                'ordinal_position' => $this->normalizeIntegerOrNull($row['ORDINAL_POSITION'] ?? null),
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
    public function compareColumns(array $sourceColumns, array $destinationColumns, string $table = ''): array
    {
        $differences = [];

        foreach ($sourceColumns as $column => $sourceDefinition) {
            if (!array_key_exists($column, $destinationColumns)) {
                $differences[] = [
                    'type' => 'missing_column',
                    'side' => 'destination',
                    'column' => $column,
                    'definition' => $sourceDefinition,
                    'source_definition' => $sourceDefinition,
                    'destination_definition' => null,
                ];
                continue;
            }

            $destinationDefinition = $destinationColumns[$column];
            $normalizedSource = $this->normalizeColumnDefinition($sourceDefinition);
            $normalizedDestination = $this->normalizeColumnDefinition($destinationDefinition);
            $changedFields = $this->compareNormalizedColumnDefinitions($normalizedSource, $normalizedDestination);

            if ($changedFields === []) {
                error_log(sprintf(
                    "SchemaInspector::compareColumns table=%s column=%s changed_fields=<none>",
                    $table !== '' ? $table : 'unknown',
                    $column
                ));
                continue;
            }

            error_log(sprintf(
                "SchemaInspector::compareColumns table=%s column=%s changed_fields=%s source=%s destination=%s source_default_type=%s destination_default_type=%s source_datetime_precision_type=%s destination_datetime_precision_type=%s",
                $table !== '' ? $table : 'unknown',
                $column,
                implode(',', $changedFields),
                json_encode($normalizedSource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($normalizedDestination, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                get_debug_type($normalizedSource['default'] ?? null),
                get_debug_type($normalizedDestination['default'] ?? null),
                get_debug_type($normalizedSource['datetime_precision'] ?? null),
                get_debug_type($normalizedDestination['datetime_precision'] ?? null)
            ));

            foreach ($changedFields as $field) {
                $differences[] = [
                    'type' => 'column_' . $field,
                    'column' => $column,
                    'source' => $normalizedSource[$field] ?? null,
                    'destination' => $normalizedDestination[$field] ?? null,
                    'source_definition' => $sourceDefinition,
                    'destination_definition' => $destinationDefinition,
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
     * @param array<string, mixed> $source
     * @param array<string, mixed> $destination
     * @return array<int, string>
     */
    private function compareNormalizedColumnDefinitions(array $source, array $destination): array
    {
        $fields = [
            'type',
            'nullable',
            'default',
            'charset',
            'collation',
            'comment',
            'length',
            'octet_length',
            'precision',
            'scale',
            'datetime_precision',
            'extra',
        ];

        $changed = [];

        foreach ($fields as $field) {
            if (($source[$field] ?? null) !== ($destination[$field] ?? null)) {
                $changed[] = $field;
            }
        }

        return $changed;
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

    private function normalizeStringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }

    private function normalizeIntegerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function normalizeColumnDefinition(array $definition): array
    {
        $type = $this->normalizeColumnType((string) ($definition['type'] ?? ''));
        $default = $this->normalizeComparableDefault($definition['default'] ?? null);

        return [
            'type' => $type,
            'nullable' => $this->normalizeBoolean($definition['nullable'] ?? true),
            'default' => $default,
            'extra' => $this->normalizeExtra($definition['extra'] ?? ''),
            'collation' => $this->normalizeStringOrNull($definition['collation'] ?? null),
            'charset' => $this->normalizeStringOrNull($definition['charset'] ?? null),
            'comment' => $this->normalizeStringOrNull($definition['comment'] ?? null),
            'length' => $this->normalizeIntegerOrNull($definition['length'] ?? null),
            'octet_length' => $this->normalizeIntegerOrNull($definition['octet_length'] ?? null),
            'precision' => $this->normalizeIntegerOrNull($definition['precision'] ?? null),
            'scale' => $this->normalizeIntegerOrNull($definition['scale'] ?? null),
            'datetime_precision' => $this->normalizeDatetimePrecision($definition['datetime_precision'] ?? null),
            'ordinal_position' => $this->normalizeIntegerOrNull($definition['ordinal_position'] ?? null),
        ];
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        $stringValue = strtolower(trim((string) $value));

        return in_array($stringValue, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    private function normalizeExtra(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function normalizeColumnType(string $type): string
    {
        $value = strtolower(trim($type));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        $value = preg_replace('/\((\d+)\)/', '($1)', $value) ?? $value;
        $value = preg_replace('/\b(int|integer|bigint|smallint|mediumint|tinyint)\(\d+\)/', '$1', $value) ?? $value;

        if (preg_match('/^([a-z]+)\s*\((.*)\)$/', $value, $matches) === 1) {
            $baseType = $matches[1];
            $args = $matches[2];

            if (in_array($baseType, ['enum', 'set'], true)) {
                return $baseType . '(' . $args . ')';
            }
        }

        return $value;
    }

    private function normalizeComparableDefault(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        $upper = strtoupper($stringValue);
        if ($upper === 'NULL') {
            return null;
        }

        if (in_array($upper, ['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP()'], true)) {
            return 'CURRENT_TIMESTAMP';
        }

        if (strlen($stringValue) >= 2 && $stringValue[0] === "'" && $stringValue[strlen($stringValue) - 1] === "'") {
            $stringValue = substr($stringValue, 1, -1);
        }

        return is_numeric($stringValue) ? $stringValue + 0 : $stringValue;
    }

    private function normalizeDatetimePrecision(mixed $value): ?int
    {
        $precision = $this->normalizeIntegerOrNull($value);

        return $precision === 0 ? null : $precision;
    }

    private function supportsDatetimePrecision(PDO $pdo): bool
    {
        $cacheKey = spl_object_id($pdo);

        if (array_key_exists($cacheKey, $this->supportsDatetimePrecision)) {
            return $this->supportsDatetimePrecision[$cacheKey];
        }

        try {
            $statement = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = 'information_schema'
                  AND TABLE_NAME = 'COLUMNS'
                  AND COLUMN_NAME = 'DATETIME_PRECISION'
            ");
            $statement->execute();

            $this->supportsDatetimePrecision[$cacheKey] = (int) $statement->fetchColumn() > 0;
        } catch (Throwable) {
            $this->supportsDatetimePrecision[$cacheKey] = false;
        }

        return $this->supportsDatetimePrecision[$cacheKey];
    }
}
