<?php
require_once __DIR__ . '/../includes/student_enrollment_controller.php';
?>

<div class="card" data-enrollment-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">

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
