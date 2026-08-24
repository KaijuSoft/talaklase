<?php
require_once __DIR__ . '/../includes/system_check_controller.php';
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
