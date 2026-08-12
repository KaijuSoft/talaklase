<?php
require_once __DIR__ . '/../includes/db_backup_controller.php';
?>

<div class="card">
    <div class="card-header">
        Database Backup
    </div>

    <div class="card-body">
        <form method="post" class="row g-3 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-4">
                <label class="form-label" for="backup_source">Source</label>
                <select id="backup_source" name="backup_source" class="form-select">
                    <option value="online" selected>Online Database</option>
                    <option value="local">Local Database</option>
                    <option value="both">Both Databases</option>
                </select>
            </div>
            <div class="col-md-4">
                <button
                    type="submit"
                    name="create_backup"
                    class="btn btn-primary">

                    <i class="bi bi-database-add"></i>
                    Create Backup
                </button>
            </div>
        </form>

        <hr>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Filename</th>
                    <th>Date</th>
                    <th>Size</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($files as $file): ?>
                <tr>
                    <td><?= htmlspecialchars(backupSourceLabel(basename($file))) ?></td>
                    <td><?= htmlspecialchars(basename($file)) ?></td>
                    <td><?= date('Y-m-d H:i:s', filemtime($file)) ?></td>
                    <td><?= round(filesize($file) / 1024, 2) ?> KB</td>
                                        <td>
                        <a href="download_backup.php?file=<?= urlencode(basename($file)) ?>" class="btn btn-success btn-sm"><i class="bi bi-download"></i> Download</a>
                        <form method="post" action="delete_backup.php" class="d-inline" onsubmit="return confirm('Delete this backup?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="file" value="<?= htmlspecialchars(basename($file), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                        <form method="post" action="restore_backup.php" class="d-inline" onsubmit="return confirm('Restore this backup?\n\nCurrent database will be overwritten.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="file" value="<?= htmlspecialchars(basename($file), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
