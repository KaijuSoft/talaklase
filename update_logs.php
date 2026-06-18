<?php
// update_logs.php
// Displays update history log
require_once __DIR__ . '/includes/auth.php';
require_permission('manage_updates');

$log_file = __DIR__ . '/logs/update_log.txt';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TalaKlase – Update Logs</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            color: #333;
            padding: 24px 16px;
        }

        h2 {
            font-size: 1.3rem;
            margin-bottom: 16px;
            color: #1a1a2e;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-family: inherit;
        }

        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
            text-decoration: none;
            display: inline-block;
        }

        .log-box {
            background: #1e1e2e;
            color: #cdd6f4;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.82rem;
            line-height: 1.7;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 70vh;
            overflow-y: auto;
        }

        .log-box .entry-success { color: #a6e3a1; }
        .log-box .entry-failed  { color: #f38ba8; }
        .log-box .entry-meta    { color: #89b4fa; }
        .log-box .entry-divider { color: #45475a; }

        .empty {
            text-align: center;
            color: #888;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #2ecc71;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.85rem;
            display: none;
            z-index: 9999;
        }
    </style>
</head>
<body>

<h2>📋 TalaKlase Update Logs</h2>

<div class="toolbar">
    <a href="index.php" class="btn btn-secondary">← Back</a>
    <?php if (file_exists($log_file) && filesize($log_file) > 0): ?>
        <button class="btn btn-danger" onclick="clearLogs()">🗑 Clear Logs</button>
    <?php endif; ?>
</div>

<?php if (!file_exists($log_file) || filesize($log_file) === 0): ?>
    <div class="empty">No update logs yet.</div>
<?php else: ?>
    <div class="log-box" id="logBox">
<?php
    $lines = file($log_file);
    foreach ($lines as $line) {
        $line = htmlspecialchars($line);
        if (strpos($line, '[SUCCESS]') !== false) {
            echo '<span class="entry-success">' . $line . '</span>';
        } elseif (strpos($line, '[FAILED]') !== false) {
            echo '<span class="entry-failed">' . $line . '</span>';
        } elseif (strpos($line, 'Before') !== false || strpos($line, 'After') !== false || strpos($line, 'Output') !== false) {
            echo '<span class="entry-meta">' . $line . '</span>';
        } elseif (strpos($line, '---') !== false) {
            echo '<span class="entry-divider">' . $line . '</span>';
        } else {
            echo $line;
        }
    }
?>
    </div>
<?php endif; ?>

<div class="toast" id="toast">Logs cleared.</div>

<script>
    // Auto-scroll to bottom
    const logBox = document.getElementById('logBox');
    if (logBox) logBox.scrollTop = logBox.scrollHeight;

    function clearLogs() {
        if (!confirm('Clear all update logs?')) return;
        fetch('clear_logs.php')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ok') {
                    document.getElementById('logBox').innerHTML = '';
                    const toast = document.getElementById('toast');
                    toast.style.display = 'block';
                    setTimeout(() => { toast.style.display = 'none'; location.reload(); }, 1500);
                }
            });
    }
</script>
</body>
</html>
