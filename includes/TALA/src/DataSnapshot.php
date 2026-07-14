<?php

namespace Tala\Engine;

use PDO;
use PDOException;

class DataSnapshot
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Capture multiple tables.
     */
	 
	 public function snapshotTables(array $tables): array
{
    $snapshots = [];

    foreach ($tables as $table) {
        $snapshots[$table] = $this->snapshotTable($table);
    }

    return $snapshots;
}
   	public function snapshotTable(string $table): array
{
    $primaryKey = $this->getPrimaryKey($table);

    return [

        'table' => $table,

        'primary_key' => $primaryKey,

        'columns' => $this->getColumns($table),

        'row_count' => $this->getRowCount($table),

        'rows' => $this->getRows($table, $primaryKey),

        'captured_at' => date('Y-m-d H:i:s')
		];
	}
	
	    public function getPrimaryKey(string $table): ?string
    {
        $stmt = $this->pdo->query("
            SHOW KEYS
            FROM `{$table}`
            WHERE Key_name = 'PRIMARY'
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['Column_name'] ?? null;
    }
	
	    public function getColumns(string $table): array
    {
        $stmt = $this->pdo->query("
            SHOW COLUMNS
            FROM `{$table}`
        ");

        $columns = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }

        return $columns;
    }
	
	    public function getRowCount(string $table): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM `{$table}`")
            ->fetchColumn();
    }
	
	public function getRows(string $table, string $primaryKey): array
{
    $stmt = $this->pdo->query("
        SELECT *
        FROM `{$table}`
        ORDER BY `{$primaryKey}`
    ");

    $rows = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[$row[$primaryKey]] = $row;
    }

		return $rows;
	}
	

}