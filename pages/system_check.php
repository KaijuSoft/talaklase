<?php

session_start();
require_once '../vendor/autoload.php';
require_once '../includes/db.php';

$checks = [];
$score = 0;
$maxScore = 0;

function checkItem($name, $passed, $message = '')
{
    global $checks, $score, $maxScore;

    $maxScore++;

    if ($passed) {
        $score++;
    }

    $checks[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message
    ];
}

try {

    $pdo = getConnection();

    checkItem(
        'Database Connection',
        true,
        'Connected successfully'
    );

} catch (Exception $e) {

    checkItem(
        'Database Connection',
        false,
        $e->getMessage()
    );
}

$requiredTables = [
    'users',
    'student',
    'course',
    'section',
    'subject',
    'attendance'
];

foreach ($requiredTables as $table) {

    try {

        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");

        checkItem(
            "Table: {$table}",
		$exists = (bool)$stmt->fetchColumn()
);
		checkItem(
			"Table: {$table}",
			$exists,
			$exists ? 'Found' : 'Missing'
);

    } catch (Exception $e) {

        checkItem(
            "Table: {$table}",
            false,
            $e->getMessage()
        );
    }
}

$adminExists = false;

try {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM users
        WHERE role='admin'
    ");

    $stmt->execute();

    $adminExists = $stmt->fetchColumn() > 0;

} catch (Exception $e) {}

checkItem(
    'Admin Account Exists',
    $adminExists,
    $adminExists
        ? 'OK'
        : 'No admin account found'
);

$displayErrors = ini_get('display_errors');

checkItem(
    'display_errors Disabled',
    !$displayErrors,
    $displayErrors
        ? 'display_errors is ON'
        : 'Safe'
);

checkItem(
    'HTTPS Enabled',
    !empty($_SERVER['HTTPS']),
    !empty($_SERVER['HTTPS'])
        ? 'HTTPS Active'
        : 'Not using HTTPS'
);

checkItem(
    'Composer Autoload',
    file_exists('../vendor/autoload.php'),
    file_exists('../vendor/autoload.php')
        ? 'Found'
        : 'Missing'
);

checkItem(
    'PhpSpreadsheet Installed',
    class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet'),
    class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')
        ? 'Installed'
        : 'Not Found'
);

checkItem(
    'Sessions Enabled',
    function_exists('session_start'),
    'PHP Sessions'
);

checkItem(
    'PDO Extension',
    extension_loaded('pdo'),
    extension_loaded('pdo')
        ? 'Loaded'
        : 'Missing'
);

checkItem(
    'MySQL Extension',
    extension_loaded('mysqli') || extension_loaded('pdo_mysql'),
    'Database Driver'
);

checkItem(
    'File Uploads Enabled',
    ini_get('file_uploads'),
    ini_get('file_uploads')
        ? 'Enabled'
        : 'Disabled'
);

checkItem(
    'ZIP Extension',
    extension_loaded('zip'),
    extension_loaded('zip')
        ? 'Installed'
        : 'Missing'
);

checkItem(
    'GD Extension',
    extension_loaded('gd'),
    extension_loaded('gd')
        ? 'Installed'
        : 'Missing'
);

$orphanStudents = 0;

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM student s
        LEFT JOIN student_section ss
            ON s.st_id = ss.st_id
        WHERE ss.st_id IS NULL
    ");

    $orphanStudents = (int)$stmt->fetchColumn();

} catch (Exception $e) {}

checkItem(
    'Orphan Students',
    $orphanStudents === 0,
    $orphanStudents . ' orphan records'
);

$duplicateUsers = 0;

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM (
            SELECT username
            FROM users
            GROUP BY username
            HAVING COUNT(*) > 1
        ) x
    ");

    $duplicateUsers = (int)$stmt->fetchColumn();

} catch (Exception $e) {}

checkItem(
    'Duplicate Usernames',
    $duplicateUsers === 0,
    $duplicateUsers . ' duplicates found'
);

$healthPercent = round(
    ($score / max($maxScore, 1)) * 100
);

?>

<!doctype html>

<html>
<head>
<meta charset="utf-8">
<title>TalaKlase System Check</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f5f7fa;
}

.pass{
    color:green;
    font-weight:bold;
}

.fail{
    color:red;
    font-weight:bold;
}
</style>

</head>
<body>

<div class="container mt-4">

```
<div class="card shadow">

    <div class="card-header">

        <h3>
            TalaKlase System Health Check
        </h3>

    </div>

    <div class="card-body">

        <h5>
            Score:
            <?= $healthPercent ?>%
        </h5>

        <?php if($healthPercent >= 90): ?>

            <div class="alert alert-success">
                READY FOR HOSTING
            </div>

        <?php elseif($healthPercent >= 70): ?>

            <div class="alert alert-warning">
                HOSTABLE WITH WARNINGS
            </div>

        <?php else: ?>

            <div class="alert alert-danger">
                FIX ISSUES BEFORE DEPLOYMENT
            </div>

        <?php endif; ?>

        <table class="table table-bordered">

            <thead>
            <tr>
                <th>Check</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
            </thead>

            <tbody>

            <?php foreach($checks as $check): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($check['name']) ?>
                    </td>

                    <td>

                        <?php if($check['passed']): ?>

                            <span class="pass">
                                PASS
                            </span>

                        <?php else: ?>

                            <span class="fail">
                                FAIL
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>
                        <?= htmlspecialchars($check['message']) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>
```

</div>

</body>
</html>
