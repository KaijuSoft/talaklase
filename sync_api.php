<?php
// sync_api.php - dedicated endpoint for sync AJAX and SSE calls
// Called by sync.php via fetch('sync_api.php', ...)
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/includes/TALA/bootstrap.php';
use Tala\Engine\Exceptions\SyncException;
require_once __DIR__ . '/includes/auth.php';
require_permission('sync_settings');

define('ONLINE_DSN',  "mysql:host=sql12.freesqldatabase.com;port=3306;dbname=sql12817970;charset=utf8mb4");
define('ONLINE_USER', "sql12817970");
define('ONLINE_PASS', "N9dIfCwPRj");
define('LOCAL_DSN',   "mysql:host=127.0.0.1;port=3306;dbname=talaklasedb;charset=utf8mb4");
define('LOCAL_USER',  "root");
define('LOCAL_PASS',  "");

/* define('ONLINE_DSN',  "mysql:host=gateway01.ap-southeast-1.prod.aws.tidbcloud.com;port=4000;dbname=talaklasedb;charset=utf8mb4");
define('ONLINE_USER', "43PYUCXNx91RQ8v.root");
define('ONLINE_PASS', "kxfO6GgjbTi7fbbH");
define('LOCAL_DSN',   "mysql:host=127.0.0.1;port=3306;dbname=talaklasedb;charset=utf8mb4");
define('LOCAL_USER',  "root");
define('LOCAL_PASS',  ""); */

$pdoOpts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+8:00'",
];

function getOnlineConn() {
    global $pdoOpts;
    $pdo = new PDO(ONLINE_DSN, ONLINE_USER, ONLINE_PASS, $pdoOpts);
    $pdo->exec("SET time_zone = '+8:00'");
    return $pdo;
}
function getLocalConn() {
    global $pdoOpts;
    $pdo = new PDO(LOCAL_DSN, LOCAL_USER, LOCAL_PASS, $pdoOpts);
    $pdo->exec("SET time_zone = '+8:00'");
    return $pdo;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Debug connection test ─────────────────────────────────────────────────────
if ($action === 'debug') {
    header('Content-Type: application/json');
    $result = [];
    try {
        $c = getOnlineConn();
        $c->query("SELECT 1");
        $result['online'] = 'OK';
    } catch (Exception $e) {
        $result['online_error'] = $e->getMessage();
    }
    try {
        $c = getLocalConn();
        $c->query("SELECT 1");
        $result['local'] = 'OK';
    } catch (Exception $e) {
        $result['local_error'] = $e->getMessage();
    }
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// ── Check connection status ───────────────────────────────────────────────────
if ($action === 'check_status') {
    header('Content-Type: application/json');
    $status = ['online'=>false,'local'=>false,'online_time'=>null,'local_time'=>null];
    try {
        $c = getOnlineConn();
        $c->query("SELECT 1");
        $status['online'] = true;
        try {
            $r = $c->query("SELECT last_updated FROM last_sync WHERE sync_id=1 LIMIT 1")->fetchColumn();
            $status['online_time'] = $r ?: null;
        } catch(Exception $e) {}
    } catch (Exception $e) {}
    try {
        $c = getLocalConn();
        $c->query("SELECT 1");
        $status['local'] = true;
        try {
            $r = $c->query("SELECT last_updated FROM last_sync WHERE sync_id=1 LIMIT 1")->fetchColumn();
            $status['local_time'] = $r ?: null;
        } catch(Exception $e) {}
    } catch (Exception $e) {}
    echo json_encode($status);
    exit;
}

// ── Check which DB is newer ───────────────────────────────────────────────────
if ($action === 'check_newer') {
    header('Content-Type: application/json');
    try {
        $onlineCon  = getOnlineConn();
        $localCon   = getLocalConn();
        $localTime  = $localCon->query("SELECT last_updated FROM last_sync WHERE sync_id=1")->fetchColumn();
        $onlineTime = $onlineCon->query("SELECT last_updated FROM last_sync WHERE sync_id=1")->fetchColumn();
        $localDt    = $localTime  ? new DateTime($localTime)  : new DateTime('@0');
        $onlineDt   = $onlineTime ? new DateTime($onlineTime) : new DateTime('@0');
        $diff       = $localDt->getTimestamp() - $onlineDt->getTimestamp();
        $tolerance  = 180;
        if (abs($diff) <= $tolerance) {
            echo json_encode(['result'=>'in_sync',     'message'=>'Databases are already in sync. No sync needed.']);
        } elseif ($diff > 0) {
            echo json_encode(['result'=>'local_newer', 'message'=>'Local is newer. Recommend: Push Local → Online.']);
        } else {
            echo json_encode(['result'=>'online_newer','message'=>'Online is newer. Recommend: Push Online → Local.']);
        }
    } catch (Exception $e) {
        echo json_encode(['result'=>'error','message'=>'Error: '.$e->getMessage()]);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────
// TALA Engine v0.1 - Smart Merge (BETA)
// ─────────────────────────────────────────────────────────────

if ($action === 'smart_merge') {

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    if (ob_get_level()) ob_end_clean();

    function sendEvent($msg, $table='—', $current=0, $total=1, $type='progress') {

        echo "data: " . json_encode([
            'type'=>$type,
            'message'=>$msg,
            'table'=>$table,
            'current'=>$current,
            'total'=>$total
        ]) . "\n\n";

        flush();
    }

    try {

        $srcCon = getLocalConn();
        $dstCon = getOnlineConn();

        sendEvent(
            'Starting Smart Merge...',
            'Initializing',
            0,
            1
        );

     require_once __DIR__ . '/includes/TALA/bootstrap.php';

$config = require __DIR__ . '/includes/TALA/TalaKlaseConfig.php';

$engine = new \Tala\Engine\TalaEngine(
    $srcCon,
    $dstCon,
    $config
);

$engine->onProgress(
    function (
        string $table,
        array $result,
        int $current,
        int $total
    ) {

        sendEvent(
            "Inserted {$result['inserted']} | Skipped {$result['skipped']}",
            $table,
            $current,
            $total,
            'progress'
        );

    }
);

$session = $engine->syncDatabase();

sendEvent(
    'Synchronization completed successfully.',
    'Finished',
    1,
    1,
    'done'
);

echo "data: " . json_encode([
    'type'    => 'summary',
    'message' => 'Synchronization completed.',
    'summary' => $session->toArray()
]) . "\n\n";

flush();

		} catch (SyncException $e) {

		sendEvent(
			$e->getMessage(),
			'Smart Merge',
			0,
			1,
			'error'
		);

	} catch (Throwable $e) {

		error_log($e);

		sendEvent(
			'An unexpected system error occurred. Please contact the administrator.',
			'System',
			0,
			1,
			'error'
		);

	}

    exit;
}

// ── Sync (SSE streaming) ──────────────────────────────────────────────────────
if ($action === 'push_to_online' || $action === 'push_to_local') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    if (ob_get_level()) ob_end_clean();

    function sendEvent($msg, $table='—', $current=0, $total=1, $type='progress') {
        $data = json_encode(['type'=>$type,'message'=>$msg,'table'=>$table,'current'=>$current,'total'=>$total]);
        echo "data: $data\n\n";
        flush();
    }

    try {
        if ($action === 'push_to_online') {
            $srcCon = getLocalConn();
            $dstCon = getOnlineConn();
            sendEvent('Preparing Online Database...', 'Initializing', 0, 1);
        } else {
            $srcCon = getOnlineConn();
            $dstCon = getLocalConn();
            sendEvent('Preparing Local Database...', 'Initializing', 0, 1);
        }

        // Step 1: Disable FK checks on destination
        $dstCon->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Step 2: Drop all tables from destination
        $tables = $dstCon->query("SHOW TABLES;")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $tbl) {
            $dstCon->exec("DROP TABLE IF EXISTS `$tbl`;");
        }

        // Step 3: Get all tables from source
        $srcTables = $srcCon->query("SHOW TABLES;")->fetchAll(PDO::FETCH_COLUMN);
        $total     = count($srcTables);
        $current   = 0;

        // Step 4: Recreate and copy each table
        foreach ($srcTables as $tbl) {
            $current++;
            sendEvent("Syncing...", $tbl, $current, $total);

            // Get CREATE TABLE from source
            $createRow = $srcCon->query("SHOW CREATE TABLE `$tbl`;")->fetch(PDO::FETCH_NUM);
            $createSQL = $createRow[1];
            $dstCon->exec($createSQL);

            // Copy rows
            $rows = $srcCon->query("SELECT * FROM `$tbl`;")->fetchAll();
            foreach ($rows as $row) {
                $cols = implode(',', array_map(fn($c)=>"`$c`", array_keys($row)));
                $vals = implode(',', array_map(fn($v)=> $v===null ? 'NULL' : $dstCon->quote((string)$v), array_values($row)));
                $dstCon->exec("INSERT INTO `$tbl` ($cols) VALUES ($vals);");
            }
        }

        // Step 5: Re-enable FK checks
        $dstCon->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // Step 6: Update sync time on both DBs
        $sql = "INSERT INTO last_sync (sync_id,last_updated) VALUES (1,NOW()) ON DUPLICATE KEY UPDATE last_updated=NOW()";
        try { getOnlineConn()->exec($sql); } catch(Exception $e) {}
        try { getLocalConn()->exec($sql);  } catch(Exception $e) {}

        sendEvent('Sync complete!', 'Done', $total, $total, 'done');

    } catch (Exception $e) {
        sendEvent('Sync failed: '.$e->getMessage(), 'Error', 0, 1, 'error');
    }
    exit;
}

// Unknown action
header('Content-Type: application/json');
echo json_encode(['error'=>'Unknown action']);
