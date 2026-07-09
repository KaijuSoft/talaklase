<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('manage_student_enrollment');

$pdo = getConnection();

function formatTeachingLoadTime(?string $value): string {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $time = strtotime($value);
    return $time !== false ? date('h:i A', $time) : '';
}

function buildTeachingLoadLabel(array $load): string {
    $labelParts = [];

    if (!empty($load['sub_code'])) {
        $labelParts[] = 'Subject: ' . $load['sub_code'];
    }

    if (!empty($load['section'])) {
        $labelParts[] = 'Section: ' . $load['section'];
    }

    if (!empty($load['inst_name'])) {
        $labelParts[] = 'Instructor: ' . $load['inst_name'];
    }

    if (!empty($load['day_name'])) {
        $labelParts[] = 'Day: ' . $load['day_name'];
    }

    $start = formatTeachingLoadTime($load['start_time'] ?? null);
    $end = formatTeachingLoadTime($load['end_time'] ?? null);
    if ($start !== '' && $end !== '') {
        $labelParts[] = 'Schedule: ' . $start . ' - ' . $end;
    }

    return implode(' | ', $labelParts);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'enroll') {

        try {

            $stId = (int)($_POST['st_id'] ?? 0);
            $assignmentId = (int)($_POST['assignment_id'] ?? 0);
            $ayId = current_ay_id($pdo);

            $assignmentStmt = $pdo->prepare("
                SELECT
                    is_active
                FROM teaching_assignments
                WHERE assignment_id = ?
                LIMIT 1
            ");
            $assignmentStmt->execute([$assignmentId]);
            $assignment = $assignmentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$assignment) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Selected teaching assignment was not found.'
                ]);
                exit;
            }

            if ((int)$assignment['is_active'] !== 1) {
                echo json_encode([
                    'success' => false,
                    'message' => 'This teaching assignment is inactive and cannot be used for enrollment.'
                ]);
                exit;
            }

            $duplicateStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM student_assignments
                WHERE st_id = ?
                  AND assignment_id = ?
                  AND ay_id = ?
            ");
            $duplicateStmt->execute([
                $stId,
                $assignmentId,
                $ayId
            ]);

            if ((int)$duplicateStmt->fetchColumn() > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'This student is already enrolled in that teaching assignment.'
                ]);
                exit;
            }

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
                $stId,
                $assignmentId,
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
        ta.sectionID,
        ta.sub_id,
        ta.inst_id,
        ta.start_time,
        ta.end_time,
        ta.is_active,

        sec.section,

        sub.sub_code,
        sub.sub_name,

        i.inst_name,

        CONCAT(
            sub.sub_code,
            ' | ',
            sec.section,
            ' | ',
            i.inst_name
        ) AS load_name

    FROM teaching_assignments ta

    JOIN section sec
        ON sec.sectionID = ta.sectionID

    JOIN subject sub
        ON sub.sub_id = ta.sub_id

    JOIN instructor i
        ON i.inst_id = ta.inst_id

    WHERE ta.is_active = 1

    ORDER BY sec.section,
        sub.sub_code
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
                            buildTeachingLoadLabel($l)
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
