<?php

class TALAEngine
{

    /**
     * Merge missing records from source into destination.
     *
     * Returns:
     * inserted
     * skipped
     */

     // =========================================================
	 // Table Types
     // =========================================================

    private const TYPE_REFERENCE   = 'reference';
    private const TYPE_MASTER      = 'master';
    private const TYPE_TRANSACTION = 'transaction';


	// =========================================================
    // Tables managed by TALA Engine
    // =========================================================
	   private static array $SYNC_TABLES = [

		'department' => [
			'keys' => ['dept_id'],
			'type' => self::TYPE_REFERENCE
		],

		'course' => [
			'keys' => ['course_id'],
			'type' => self::TYPE_REFERENCE
		],

		'student' => [
			'keys' => ['student_no'],
			'type' => self::TYPE_MASTER
		],

		'subject' => [
			'keys' => ['sub_id'],
			'type' => self::TYPE_REFERENCE
		],

		'academic_year' => [
			'keys' => ['ay_id'],
			'type' => self::TYPE_REFERENCE
		]

	];

    // ---------------------------------------------------------



public static function syncDatabase(
    PDO $src,
    PDO $dst,
    callable $progress = null
)
{

    $results = [];

    $total = count(self::$SYNC_TABLES);

    $current = 0;

    foreach (self::$SYNC_TABLES as $table => $config) {

        $current++;

			$result = self::mergeTable(
		$src,
		$dst,
		$table,
		$config['keys']
	);

        $result['table'] = $table;

        $results[] = $result;

        if ($progress) {

            $progress(
                $table,
                $result,
                $current,
                $total
            );

        }

    }

    return $results;

}

    public static function mergeTable(
        PDO $src,
        PDO $dst,
        string $table,
        array $keys
    )
    {

        $inserted = 0;
        $skipped  = 0;

        $rows = $src
            ->query("SELECT * FROM `$table`")
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {

            $where = [];
            $params = [];

            foreach ($keys as $key) {

                $where[] = "`$key`=?";

                $params[] = $row[$key];

            }

            $sql =
                "SELECT COUNT(*) FROM `$table`
                 WHERE " .
                implode(
                    ' AND ',
                    $where
                );

            $stmt =
                $dst->prepare($sql);

            $stmt->execute($params);

            if ($stmt->fetchColumn()) {

                $skipped++;

                continue;

            }

            $columns =
                array_keys($row);

            $colSQL =
                '`' .
                implode(
                    '`,`',
                    $columns
                ) .
                '`';

            $placeholders =
                implode(
                    ',',
                    array_fill(
                        0,
                        count($columns),
                        '?'
                    )
                );

            $insert =
                $dst->prepare(

                    "INSERT INTO `$table`
                    ($colSQL)

                    VALUES

                    ($placeholders)"

                );

            $insert->execute(
                array_values($row)
            );

            $inserted++;

        }

        return [

            'inserted' => $inserted,

            'skipped' => $skipped

        ];

    }

}