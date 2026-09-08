<?php

declare(strict_types=1);

function loadStudentProfileData(PDO $pdo, int $studentId, array $input = []): array
{
    if ($studentId < 1) {
        return ['error_status' => 404, 'error_message' => 'Student not specified.'];
    }

    $user = current_user();
    $isInstructorScoped = in_array($user['role'] ?? '', ['instructor', 'instructor_admin'], true);
    $ownedSections = current_user_owned_section_ids($pdo);

    $stmt = $pdo->prepare(
        "SELECT s.*, c.course_acronym, c.course_name, ss.sectionID, sec.section,
                ss.yearlvl, ay.ay_name
         FROM student s
         LEFT JOIN course c ON c.course_id = s.course_id
         LEFT JOIN student_section ss ON ss.st_id = s.st_id
         LEFT JOIN section sec ON sec.sectionID = ss.sectionID
         LEFT JOIN academic_year ay ON ay.ay_id = ss.ay_id
         WHERE s.st_id = :id
         ORDER BY ss.ay_id DESC
         LIMIT 1"
    );
    $stmt->execute([':id' => $studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        return ['error_status' => 404, 'error_message' => 'Student not found.'];
    }

    if ($isInstructorScoped && !in_array((int) $student['sectionID'], $ownedSections, true)) {
        return ['error_status' => 403, 'error_message' => 'You do not have access to this student record.'];
    }

    $terms = ['Prelim', 'Midterm', 'PreFinal', 'Final'];
    $termLabels = ['Prelim' => 'Prelim', 'Midterm' => 'Midterm', 'PreFinal' => 'Pre-Finals', 'Final' => 'Finals'];
    $selectedTerm = (string) ($input['term'] ?? 'All');
    if ($selectedTerm !== 'All' && !in_array($selectedTerm, $terms, true)) $selectedTerm = 'All';

    $ayId = current_ay_id($pdo);
    $termWhere = $selectedTerm === 'All' ? '' : ' AND crp.term = :term';
    $sql = "SELECT subj.sub_id, subj.sub_code, subj.sub_name, i.inst_name, crp.term,
                    p.par_one, p.par_two, p.par_three, p.par_four, p.par_five,
                    w.written_one, w.written_two, w.written_three, w.written_four, w.written_five,
                    pf.perf_one, pf.perf_two, pf.perf_three, pf.perf_four, pf.perf_five, e.score
             FROM student_assignments sa
             INNER JOIN teaching_assignments ta ON ta.assignment_id = sa.assignment_id AND ta.ay_id = :ay
             INNER JOIN subject subj ON subj.sub_id = ta.sub_id
             LEFT JOIN instructor i ON i.inst_id = ta.inst_id
             LEFT JOIN class_record cr ON cr.st_id = sa.st_id AND cr.sectionID = ta.sectionID AND cr.sub_id = ta.sub_id
             LEFT JOIN class_record_participation crp ON crp.rec_id = cr.rec_id {$termWhere}
             LEFT JOIN participation p ON p.par_id = crp.par_id
             LEFT JOIN class_record_written crw ON crw.rec_id = cr.rec_id AND crw.term = crp.term
             LEFT JOIN written w ON w.written_id = crw.written_id
             LEFT JOIN class_record_performance crpf ON crpf.rec_id = cr.rec_id AND crpf.term = crp.term
             LEFT JOIN performance pf ON pf.perf_id = crpf.perf_id
             LEFT JOIN class_record_exam cre ON cre.rec_id = cr.rec_id AND cre.term = crp.term
             LEFT JOIN exam e ON e.exam_id = cre.exam_id
             WHERE sa.st_id = :id AND sa.ay_id = :ay
             ORDER BY subj.sub_name, FIELD(crp.term, 'Prelim', 'Midterm', 'PreFinal', 'Final')";

    $params = [':id' => $studentId, ':ay' => $ayId];
    if ($selectedTerm !== 'All') $params[':term'] = $selectedTerm;
    $gs = $pdo->prepare($sql);
    $gs->execute($params);
    $grades = $gs->fetchAll(PDO::FETCH_ASSOC);

    $scoreSettings = loadStudentProfileScoreSettings($pdo, $terms);
    $gradeGroups = buildStudentProfileGradeGroups($grades, $scoreSettings);

    $as = $pdo->prepare(
        "SELECT a.Att_ID, a._date, a.term, a.status, a.time_in, a.late_minutes,
                ta.start_time, ta.end_time, subj.sub_code, subj.sub_name,
                i.inst_name, sec.section
         FROM attendance a
         INNER JOIN teaching_assignments ta ON ta.assignment_id = a.assignment_id
         INNER JOIN subject subj ON subj.sub_id = ta.sub_id
         LEFT JOIN instructor i ON i.inst_id = ta.inst_id
         INNER JOIN section sec ON sec.sectionID = a.sectionID
         WHERE a.st_id = :id AND ta.ay_id = :ay
         ORDER BY FIELD(a.term, 'Prelim', 'Midterm', 'PreFinal', 'Final'), a._date ASC"
    );
    $as->execute([':id' => $studentId, ':ay' => $ayId]);
    $attendance = $as->fetchAll(PDO::FETCH_ASSOC);

    $attByTerm = [];
    $attTotals = ['Present' => 0, 'Late' => 0, 'Absent' => 0];
    $totalHours = 0.0;
    foreach ($attendance as $record) {
        if ($selectedTerm !== 'All' && $record['term'] !== $selectedTerm) continue;
        $attByTerm[$record['term']][] = $record;
        $attTotals[$record['status']] = ($attTotals[$record['status']] ?? 0) + 1;
        $duration = max(0, (strtotime($record['end_time']) - strtotime($record['start_time'])) / 3600);
        if ($record['status'] === 'Present') $totalHours += $duration;
        elseif ($record['status'] === 'Late') $totalHours += max(0, $duration - ((float) $record['late_minutes'] / 60));
    }

    return compact('student', 'studentId', 'terms', 'termLabels', 'selectedTerm', 'ayId', 'gradeGroups', 'attByTerm', 'attTotals', 'totalHours');
}
function loadStudentProfileScoreSettings(PDO $pdo, array $terms): array
{
    $settings = [];
    foreach ($terms as $term) {
        $stmt = $pdo->prepare('SELECT component, one_max, two_max, three_max, four_max, five_max FROM score_settings WHERE term = ?');
        $stmt->execute([$term]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $settings[$term][$row['component']] = $row;
        $stmt = $pdo->prepare('SELECT score_max FROM exam_settings WHERE term = ? LIMIT 1');
        $stmt->execute([$term]);
        $settings[$term]['exam'] = (int) ($stmt->fetchColumn() ?: 0);
    }
    return $settings;
}

function buildStudentProfileGradeGroups(array $grades, array $settings): array
{
    $groups = [];
    $scoreColumns = [
        'participation' => ['par_one', 'par_two', 'par_three', 'par_four', 'par_five'],
        'written' => ['written_one', 'written_two', 'written_three', 'written_four', 'written_five'],
        'performance' => ['perf_one', 'perf_two', 'perf_three', 'perf_four', 'perf_five'],
    ];
    $maxKeys = ['one_max', 'two_max', 'three_max', 'four_max', 'five_max'];

    foreach ($grades as $grade) {
        $subjectKey = (string) $grade['sub_id'];
        $term = (string) ($grade['term'] ?? '');
        if ($term === '') continue;
        $groups[$subjectKey]['sub_code'] = $grade['sub_code'];
        $groups[$subjectKey]['sub_name'] = $grade['sub_name'];
        $groups[$subjectKey]['inst_name'] = $grade['inst_name'];
        $groups[$subjectKey]['terms'][$term] = $grade;
        foreach ($scoreColumns as $component => $columns) {
            $max = $settings[$term][$component] ?? [];
            foreach ($columns as $index => $column) {
                $groups[$subjectKey]['terms'][$term]['max'][$component][$index] = $max[$maxKeys[$index]] ?? null;
            }
        }
        $groups[$subjectKey]['terms'][$term]['exam_max'] = $settings[$term]['exam'] ?? 0;
    }
    return array_values($groups);
}

function handleStudentProfilePageRequest(int $studentId, array $input = []): array
{
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/auth.php';
    require_permission('view_students');

    $pdo = getConnection();
    return loadStudentProfileData($pdo, $studentId, $input);
}

function loadStudentProfilePage(): array
{
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/auth.php';
    require_permission('view_students');
    $pdo = getConnection();
    $studentId = (int) ($_GET['st_id'] ?? 0);
    return loadStudentProfileData($pdo, $studentId, $_GET);
}
