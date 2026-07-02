<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('manage_student_enrollment');

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'enroll') {

        try {

            $ayId = current_ay_id($pdo);

            $stmt = $pdo->prepare("
                INSERT INTO student_assignments
                (
                    st_id,
                    assignment_id,
                    ay_id
                )
                VALUES
                (
                    ?, ?, ?
                )
            ");

            $stmt->execute([
                $_POST['st_id'],
                $_POST['assignment_id'],
                $ayId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Student enrolled.'
            ]);

        } catch (Throwable $e) {

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }

    if ($action === 'remove') {

        $stmt = $pdo->prepare("
            DELETE
            FROM student_assignments
            WHERE enrollment_id = ?
        ");

        $stmt->execute([
            $_POST['enrollment_id']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Enrollment removed.'
        ]);

        exit;
    }
}

$students = $pdo->query("
    SELECT
        st_id,
        CONCAT(
            st_lastname,
            ', ',
            st_name
        ) AS student_name
    FROM student
    ORDER BY st_lastname
")->fetchAll();

$loads = $pdo->query("
    SELECT
        ta.assignment_id,

        CONCAT(
            s.section,
            ' - ',
            sub.sub_code,
            ' (',
            i.inst_name,
            ')'
        ) AS load_name

    FROM teaching_assignments ta

    JOIN section s
        ON s.sectionID = ta.sectionID

    JOIN subject sub
        ON sub.sub_id = ta.sub_id

    JOIN instructor i
        ON i.inst_id = ta.inst_id

    WHERE ta.is_active = 1

    ORDER BY s.section
")->fetchAll();

$enrollments = $pdo->query("
    SELECT

        sa.enrollment_id,

        CONCAT(
            st.st_lastname,
            ', ',
            st.st_name
        ) AS student_name,

        s.section,

        sub.sub_code,

        i.inst_name

    FROM student_assignments sa

    JOIN student st
        ON st.st_id = sa.st_id

    JOIN teaching_assignments ta
        ON ta.assignment_id = sa.assignment_id

    JOIN section s
        ON s.sectionID = ta.sectionID

    JOIN subject sub
        ON sub.sub_id = ta.sub_id

    JOIN instructor i
        ON i.inst_id = ta.inst_id

    ORDER BY student_name
")->fetchAll();

?>

<div class="card">

    <div class="card-header">

        <h6 class="mb-0">
            Student Enrollment
        </h6>

    </div>

    <div class="card-body">

        <div class="row g-2">

            <div class="col-md-5">

                <select
                    id="st_id"
                    class="form-select">

                    <option value="">
                        Select Student
                    </option>

                    <?php foreach ($students as $s): ?>

                    <option
                        value="<?= $s['st_id'] ?>">

                        <?= htmlspecialchars(
                            $s['student_name']
                        ) ?>

                    </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="col-md-5">

                <select
                    id="assignment_id"
                    class="form-select">

                    <option value="">
                        Select Teaching Load
                    </option>

                    <?php foreach ($loads as $l): ?>

                    <option
                        value="<?= $l['assignment_id'] ?>">

                        <?= htmlspecialchars(
                            $l['load_name']
                        ) ?>

                    </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="col-md-2">

                <button
                    class="btn btn-success w-100"
                    onclick="enrollStudent()">

                    Enroll

                </button>

            </div>

        </div>

    </div>

</div>

<div class="card mt-3">

    <div class="card-header">

        Current Enrollments

    </div>

    <div class="table-responsive">

        <table class="table">

            <thead>

                <tr>
                    <th>Student</th>
                    <th>Section</th>
                    <th>Subject</th>
                    <th>Instructor</th>
                    <th></th>
                </tr>

            </thead>

            <tbody>

            <?php foreach ($enrollments as $e): ?>

                <tr>

                    <td><?= htmlspecialchars($e['student_name']) ?></td>

                    <td><?= htmlspecialchars($e['section']) ?></td>

                    <td><?= htmlspecialchars($e['sub_code']) ?></td>

                    <td><?= htmlspecialchars($e['inst_name']) ?></td>

                    <td>

                        <button
                            class="btn btn-sm btn-danger"
                            onclick="removeEnrollment(
                                <?= $e['enrollment_id'] ?>
                            )">

                            Remove

                        </button>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<script>

function enrollStudent() {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'enroll'
    );

    params.append(
        'st_id',
        document.getElementById(
            'st_id'
        ).value
    );

    params.append(
        'assignment_id',
        document.getElementById(
            'assignment_id'
        ).value
    );

    fetch('', {

        method: 'POST',

        headers: {
            'Content-Type':
            'application/x-www-form-urlencoded'
        },

        body: params

    })
    .then(r => r.json())
    .then(result => {

        alert(result.message);

        if (result.success) {

            location.reload();

        }

    });

}

function removeEnrollment(id) {

    if (
        !confirm(
            'Remove enrollment?'
        )
    ) return;

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'remove'
    );

    params.append(
        'enrollment_id',
        id
    );

    fetch('', {

        method: 'POST',

        headers: {
            'Content-Type':
            'application/x-www-form-urlencoded'
        },

        body: params

    })
    .then(r => r.json())
    .then(result => {

        alert(result.message);

        location.reload();

    });

}

</script>