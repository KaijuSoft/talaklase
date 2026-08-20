<?php
require_once __DIR__ . '/includes/auth.php';
require_permission('print_reports');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TalaKlase — Print</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #000; background: #fff; padding: 0; }
    .print-page { width: 100%; max-width: 960px; margin: 0 auto; padding: 20px 30px; }
    .doc-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 14px; }
    .doc-header .school-name { font-size: 16pt; font-weight: 700; letter-spacing: -0.3px; }
    .doc-header .doc-title { font-size: 13pt; font-weight: 600; margin-top: 3px; }
    .doc-header .doc-meta { font-size: 9pt; color: #444; margin-top: 5px; }
    .info-bar { display: flex; flex-wrap: wrap; gap: 6px 24px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; padding: 7px 12px; margin-bottom: 14px; font-size: 9.5pt; }
    .info-bar span { color: #333; }
    .info-bar strong { color: #000; }
    table { width: 100%; border-collapse: collapse; font-size: 10pt; margin-bottom: 10px; }
    thead th { background: #1a1a2e; color: #fff; font-weight: 700; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5px; padding: 7px 10px; text-align: center; border: 1px solid #000; }
    thead th.left { text-align: left; }
    tbody tr:nth-child(even) { background: #f9f9f9; }
    tbody td { padding: 6px 10px; border: 1px solid #ccc; text-align: center; vertical-align: middle; }
    tbody td.left { text-align: left; }
    .doc-footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 8.5pt; color: #666; display: flex; justify-content: space-between; }
    .signature-row { display: flex; justify-content: space-around; margin-top: 40px; }
    .signature-box { text-align: center; min-width: 160px; }
    .signature-box .sig-line { border-top: 1px solid #000; margin-bottom: 4px; margin-top: 40px; }
    .signature-box .sig-label { font-size: 9pt; color: #333; }
    .no-print { position: fixed; top: 16px; right: 16px; display: flex; gap: 8px; z-index: 999; }
    .no-print button { padding: 8px 18px; font-size: 10pt; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; }
    .btn-print { background: #0dc8a8; color: #fff; }
    .btn-close { background: #e2e8f0; color: #333; }
    .btn-print:hover { background: #09a98d; }
    @media print {
      .no-print { display: none !important; }
      body { padding: 0; }
      .print-page { padding: 10px 15px; }
      thead th { background: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      tbody tr:nth-child(even) { background: #f5f5f5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      table { page-break-inside: auto; }
      tr { page-break-inside: avoid; }
    }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/includes/db.php';
$pdo   = getConnection();
$type  = $_GET['type'] ?? '';
$today = date('F j, Y');
$now   = date('Y-m-d H:i:s');

function docHeader($title, $subtitle='') {
    global $today;
    echo '<div class="doc-header">';
    echo '<div class="school-name">TalaKlase</div>';
    echo '<div class="doc-title">'.htmlspecialchars($title).'</div>';
    if ($subtitle) echo '<div class="doc-meta">'.htmlspecialchars($subtitle).'</div>';
    echo '<div class="doc-meta">Date Printed: '.$today.'</div>';
    echo '</div>';
}

function docFooter() {
    global $now;
    echo '<div class="doc-footer">';
    echo '<span>TalaKlase — School Record System</span>';
    echo '<span>Generated: '.$now.'</span>';
    echo '</div>';
}
?>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">🖨 Print</button>
  <button class="btn-close" onclick="window.close()">✕ Close</button>
</div>

<div class="print-page">
<?php

// ── STUDENTS ──────────────────────────────────────────────────────────────────
if ($type === 'students') {
    $search = trim($_GET['q'] ?? '');
    $where  = $search ? "WHERE (student.st_lastname LIKE :q OR student.st_name LIKE :q)" : "";
    $params = $search ? [':q'=>"%$search%"] : [];

    $stmt = $pdo->prepare("SELECT student.st_lastname, student.st_name, student.st_middlename,
        student.st_suffix, student.st_gender, course.course_acronym,
        student_section.yearlvl, section.section
        FROM student
        INNER JOIN course ON student.course_id=course.course_id
        INNER JOIN student_section ON student.st_id=student_section.st_id
        INNER JOIN section ON section.sectionID=student_section.sectionID
        $where ORDER BY student.st_lastname ASC");
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    docHeader('Student Records', $search ? "Search: \"$search\"" : 'All Students');

    echo '<div class="info-bar">';
    echo '<span>Total Students: <strong>'.count($students).'</strong></span>';
    if ($search) echo '<span>Filter: <strong>'.htmlspecialchars($search).'</strong></span>';
    echo '</div>';

    echo '<table><thead><tr>';
    foreach (['#','Last Name','First Name','Middle Name','Suf.','Gender','Course','Year','Section'] as $h)
        echo '<th>'.htmlspecialchars($h).'</th>';
    echo '</tr></thead><tbody>';

    foreach ($students as $i => $s) {
        echo '<tr>';
        echo '<td>'.($i+1).'</td>';
        echo '<td class="left">'.htmlspecialchars($s['st_lastname']).'</td>';
        echo '<td class="left">'.htmlspecialchars($s['st_name']).'</td>';
        echo '<td class="left">'.htmlspecialchars($s['st_middlename']).'</td>';
        echo '<td>'.htmlspecialchars($s['st_suffix']).'</td>';
        echo '<td>'.htmlspecialchars($s['st_gender']).'</td>';
        echo '<td>'.htmlspecialchars($s['course_acronym']).'</td>';
        echo '<td>'.htmlspecialchars($s['yearlvl']).'</td>';
        echo '<td>'.htmlspecialchars($s['section']).'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    echo '<div class="signature-row">
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Prepared by</div></div>
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Noted by</div></div>
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Approved by</div></div>
    </div>';
    docFooter();
}

// ── VIEW ATTENDANCE ───────────────────────────────────────────────────────────
elseif ($type === 'view_attendance') {
    $sec       = $_GET['sec']       ?? '';
    $term      = $_GET['term']      ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to   = $_GET['date_to']   ?? '';
    $name      = trim($_GET['name'] ?? '');

    $where  = ['1=1'];
    $params = [];
    if ($sec)       { $where[] = 'section.sectionID=:sec';   $params[':sec']   = $sec; }
    if ($term)      { $where[] = 'attendance.term=:term';    $params[':term']  = $term; }
    if ($date_from) { $where[] = 'attendance._date>=:dfrom'; $params[':dfrom'] = $date_from; }
    if ($date_to)   { $where[] = 'attendance._date<=:dto';   $params[':dto']   = $date_to; }
    if ($name) {
        $where[] = "(CONCAT(student.st_lastname,' ',student.st_name,' ',student.st_middlename,' ',student.st_suffix) LIKE :name
                 OR CONCAT(student.st_name,' ',student.st_middlename,' ',student.st_lastname) LIKE :name2
                 OR CONCAT(student.st_lastname,', ',student.st_name) LIKE :name3)";
        $params[':name']  = "%$name%";
        $params[':name2'] = "%$name%";
        $params[':name3'] = "%$name%";
    }
    $whereStr = implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT
        CONCAT(student.st_lastname,', ',student.st_name,' ',student.st_middlename,' ',IF(student.st_suffix='','',student.st_suffix)) AS NAME,
        section.section AS Section,
        attendance.term AS Term,
        COUNT(CASE WHEN attendance.status='Present' THEN 1 END) AS Present,
        COUNT(CASE WHEN attendance.status='Absent'  THEN 1 END) AS Absent,
        COUNT(CASE WHEN attendance.status='Late'    THEN 1 END) AS Late,
        COUNT(CASE WHEN attendance.status='Present' THEN 1 END) * 3 AS TotalHours,
        MIN(attendance._date) AS DateFrom,
        MAX(attendance._date) AS DateTo
        FROM student
        INNER JOIN attendance ON student.st_id=attendance.st_id
        INNER JOIN section ON section.sectionID=attendance.sectionID
        WHERE $whereStr
        GROUP BY student.st_id, section.sectionID, attendance.term
        ORDER BY student.st_lastname ASC, attendance.term ASC");
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    $secName = '';
    if ($sec) {
        $r = $pdo->prepare("SELECT section FROM section WHERE sectionID=?");
        $r->execute([$sec]);
        $secName = $r->fetchColumn();
    }

    docHeader('Attendance Record',
        implode('  |  ', array_filter([
            $secName   ? "Section: $secName"   : '',
            $term      ? "Term: $term"          : '',
            $date_from ? "From: $date_from"     : '',
            $date_to   ? "To: $date_to"         : '',
            $name      ? "Student: $name"       : '',
        ])));

    echo '<div class="info-bar">';
    if ($secName)   echo '<span>Section: <strong>'.htmlspecialchars($secName).'</strong></span>';
    if ($term)      echo '<span>Term: <strong>'.htmlspecialchars($term).'</strong></span>';
    if ($date_from) echo '<span>From: <strong>'.htmlspecialchars($date_from).'</strong></span>';
    if ($date_to)   echo '<span>To: <strong>'.htmlspecialchars($date_to).'</strong></span>';
    if ($name)      echo '<span>Student: <strong>'.htmlspecialchars($name).'</strong></span>';
    echo '<span>Total Records: <strong>'.count($records).'</strong></span>';
    echo '</div>';

    echo '<table><thead><tr>
        <th>#</th><th class="left">Name</th><th>Section</th><th>Term</th>
        <th>Present</th><th>Absent</th><th>Late</th><th>Hours Rendered</th><th>Date Range</th>
    </tr></thead><tbody>';

    foreach ($records as $i => $r) {
        echo '<tr>';
        echo '<td>'.($i+1).'</td>';
        echo '<td class="left">'.htmlspecialchars($r['NAME']).'</td>';
        echo '<td>'.htmlspecialchars($r['Section']).'</td>';
        echo '<td>'.htmlspecialchars($r['Term']).'</td>';
        echo '<td>'.(int)$r['Present'].'</td>';
        echo '<td>'.(int)$r['Absent'].'</td>';
        echo '<td>'.(int)$r['Late'].'</td>';
        echo '<td>'.(int)$r['TotalHours'].' hrs</td>';
        echo '<td>'.htmlspecialchars($r['DateFrom']).' → '.htmlspecialchars($r['DateTo']).'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    echo '<div class="signature-row">
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Prepared by</div></div>
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Noted by</div></div>
    </div>';
    docFooter();
}

// ── PRINT ATTENDANCE ──────────────────────────────────────────────────────────
elseif ($type === 'print_attendance') {
    $sec  = $_GET['sec']  ?? '';
    $term = $_GET['term'] ?? '';
    $name = trim($_GET['name'] ?? '');

    $where  = ['1=1'];
    $params = [];
    if ($sec)  { $where[] = 'section.sectionID=:sec';        $params[':sec']  = $sec; }
    if ($term) { $where[] = 'attendance.term=:term';         $params[':term'] = $term; }
    if ($name) {
        $where[] = "(CONCAT(student.st_lastname,' ',student.st_name,' ',student.st_middlename,' ',student.st_suffix) LIKE :name
                 OR CONCAT(student.st_lastname,', ',student.st_name) LIKE :name2)";
        $params[':name']  = "%$name%";
        $params[':name2'] = "%$name%";
    }
    $whereStr = implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT
        CONCAT(student.st_lastname,', ',student.st_name,' ',student.st_middlename,' ',IF(student.st_suffix='','',student.st_suffix)) AS NAME,
        section.section AS Section,
        attendance.term AS Term,
        COUNT(CASE WHEN attendance.status='Present' THEN 1 END) AS Present,
        COUNT(CASE WHEN attendance.status='Absent'  THEN 1 END) AS Absent
        FROM student
        INNER JOIN attendance ON student.st_id=attendance.st_id
        INNER JOIN section    ON section.sectionID=attendance.sectionID
        WHERE $whereStr
        GROUP BY student.st_id, section.sectionID, attendance.term
        ORDER BY student.st_lastname");
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    $secName = '';
    if ($sec) {
        $r = $pdo->prepare("SELECT section FROM section WHERE sectionID=?");
        $r->execute([$sec]);
        $secName = $r->fetchColumn();
    }

    docHeader('Attendance Summary',
        implode('  |  ', array_filter([
            $secName ? "Section: $secName" : 'All Sections',
            $term    ? "Term: $term"       : 'All Terms',
            $name    ? "Student: $name"    : '',
        ])));

    echo '<div class="info-bar">';
    if ($secName) echo '<span>Section: <strong>'.htmlspecialchars($secName).'</strong></span>';
    if ($term)    echo '<span>Term: <strong>'.htmlspecialchars($term).'</strong></span>';
    if ($name)    echo '<span>Student: <strong>'.htmlspecialchars($name).'</strong></span>';
    echo '<span>Total Students: <strong>'.count($records).'</strong></span>';
    echo '</div>';

    echo '<table><thead><tr>
        <th>#</th><th class="left">Name</th><th>Section</th><th>Term</th>
        <th>Present</th><th>Absent</th><th>Total Hours</th><th>Attendance %</th>
    </tr></thead><tbody>';

    foreach ($records as $i => $r) {
        $total      = (int)$r['Present'] + (int)$r['Absent'];
        $totalHours = (int)$r['Present'] * 3;
        $pct        = $total > 0 ? round(($r['Present'] / $total) * 100, 1) : 0;
        echo '<tr>';
        echo '<td>'.($i+1).'</td>';
        echo '<td class="left">'.htmlspecialchars($r['NAME']).'</td>';
        echo '<td>'.htmlspecialchars($r['Section']).'</td>';
        echo '<td>'.htmlspecialchars($r['Term']).'</td>';
        echo '<td>'.(int)$r['Present'].'</td>';
        echo '<td>'.(int)$r['Absent'].'</td>';
        echo '<td>'.$totalHours.' hrs</td>';
        echo '<td>'.$pct.'%</td>';
        echo '</tr>';
    }

    $totPresent = array_sum(array_column($records,'Present'));
    $totAbsent  = array_sum(array_column($records,'Absent'));
    $totHours   = $totPresent * 3;
    echo '<tr style="font-weight:700;background:#f0f0f0;">
        <td colspan="4" class="left">TOTAL</td>
        <td>'.$totPresent.'</td>
        <td>'.$totAbsent.'</td>
        <td>'.$totHours.' hrs</td>
        <td>—</td>
    </tr>';

    echo '</tbody></table>';

    echo '<div class="signature-row">
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Prepared by</div></div>
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Checked by</div></div>
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Approved by</div></div>
    </div>';
    docFooter();
}

// ── GRADES ────────────────────────────────────────────────────────────────────
elseif ($type === 'grades') {
    $assignmentId = (int)($_GET['assignment_id'] ?? 0);
    $term = $_GET['term'] ?? 'Prelim';
    $comp = $_GET['comp'] ?? 'Participation';

    if ($assignmentId <= 0) { http_response_code(400); exit('Teaching assignment is required.'); }
    $ast = $pdo->prepare("SELECT ta.assignment_id,ta.inst_id,ta.sectionID,ta.sub_id,se.section,su.sub_name
                          FROM teaching_assignments ta
                          INNER JOIN section se ON se.sectionID=ta.sectionID
                          INNER JOIN subject su ON su.sub_id=ta.sub_id
                          WHERE ta.assignment_id=? AND ta.is_active=1 AND ta.ay_id=? LIMIT 1");
    $ast->execute([$assignmentId,current_ay_id($pdo)]); $assignment=$ast->fetch();
    if (!$assignment) { http_response_code(404); exit('Teaching assignment not found.'); }
    $user=current_user(); $role=$user['role']??''; $instId=(int)($user['inst_id']??0);
    if (!in_array($role,['admin','instructor_admin'],true) && (int)$assignment['inst_id'] !== $instId) { http_response_code(403); exit('You are not authorized to print this teaching assignment.'); }
    $sec=(int)$assignment['sectionID']; $sub=(int)$assignment['sub_id'];
    $secName=$assignment['section']; $subName=$assignment['sub_name'];

    $isExam = $comp === 'Exam';
    $map = [
        'Participation' => ['tbl'=>'participation','cols'=>['par_one','par_two','par_three','par_four','par_five'],'jt'=>'class_record_participation','fk'=>'par_id'],
        'Written'       => ['tbl'=>'written','cols'=>['written_one','written_two','written_three','written_four','written_five'],'jt'=>'class_record_written','fk'=>'written_id'],
        'Performance'   => ['tbl'=>'performance','cols'=>['perf_one','perf_two','perf_three','perf_four','perf_five'],'jt'=>'class_record_performance','fk'=>'perf_id'],
    ];

    if ($isExam) {
        $sql = "SELECT s.st_id,
            CONCAT(s.st_lastname,', ',s.st_name,' ',s.st_middlename) AS FullName,
            s.st_gender, e.score
            FROM student s
            INNER JOIN student_assignments sa ON sa.st_id=s.st_id AND sa.assignment_id=:assignment
            LEFT JOIN class_record cr ON cr.st_id=s.st_id AND cr.sectionID=ss.sectionID AND cr.sub_id=:sub
            LEFT JOIN class_record_exam cre ON cre.rec_id=cr.rec_id AND cre.term=:term
            LEFT JOIN exam e ON e.exam_id=cre.exam_id
            WHERE sa.assignment_id=:assignment ORDER BY s.st_gender DESC, s.st_lastname";
    } else {
        $m = $map[$comp];
        $colStr = implode(',', array_map(fn($c)=>"t.$c", $m['cols']));
        $sql = "SELECT s.st_id,
            CONCAT(s.st_lastname,', ',s.st_name,' ',s.st_middlename) AS FullName,
            s.st_gender, $colStr
            FROM student s
            INNER JOIN student_assignments sa ON sa.st_id=s.st_id AND sa.assignment_id=:assignment
            LEFT JOIN class_record cr ON cr.st_id=s.st_id AND cr.sectionID=ss.sectionID AND cr.sub_id=:sub
            LEFT JOIN {$m['jt']} jt ON jt.rec_id=cr.rec_id AND jt.term=:term
            LEFT JOIN {$m['tbl']} t ON t.{$m['fk']}=jt.{$m['fk']}
            WHERE sa.assignment_id=:assignment ORDER BY s.st_gender DESC, s.st_lastname";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':assignment'=>$assignmentId,':sec'=>$sec,':sub'=>$sub,':term'=>$term]);
    $rows = $stmt->fetchAll();

    $ms = $pdo->prepare("SELECT one_max,two_max,three_max,four_max,five_max FROM grade_max_scores WHERE assignment_id=? AND term=? AND component=? LIMIT 1");
    $ms->execute([$assignmentId,$term,strtolower($comp)]); $savedMax=$ms->fetch(PDO::FETCH_NUM);
    if ($savedMax) {
        $maxScores=array_map('intval',$savedMax);
    } elseif ($isExam) {
        $ms=$pdo->prepare("SELECT score_max FROM exam_settings WHERE term=? LIMIT 1"); $ms->execute([$term]); $maxScores=[$ms->fetchColumn() ?: '?'];
    } else {
        $ms=$pdo->prepare("SELECT one_max,two_max,three_max,four_max,five_max FROM score_settings WHERE term=? AND component=? LIMIT 1"); $ms->execute([$term,strtolower($comp)]); $maxScores=array_values($ms->fetch() ?: [0,0,0,0,0]);
    }

    $termLabel = ['Prelim'=>'Prelim','Midterm'=>'Midterm','PreFinal'=>'Pre-Finals','Final'=>'Finals'];

    docHeader("Grade Sheet — $comp",
        "Section: $secName  |  Subject: $subName  |  Term: ".($termLabel[$term]??$term));

    echo '<div class="info-bar">';
    echo '<span>Section: <strong>'.htmlspecialchars($secName).'</strong></span>';
    echo '<span>Subject: <strong>'.htmlspecialchars($subName).'</strong></span>';
    echo '<span>Term: <strong>'.htmlspecialchars($termLabel[$term]??$term).'</strong></span>';
    echo '<span>Component: <strong>'.htmlspecialchars($comp).'</strong></span>';
    echo '<span>Students: <strong>'.count($rows).'</strong></span>';
    echo '</div>';

    if ($isExam) {
        echo '<p style="font-size:9pt;color:#555;margin-bottom:8px;">Max Score: <strong>'.$maxScores[0].'</strong></p>';
    } else {
        echo '<p style="font-size:9pt;color:#555;margin-bottom:8px;">Max Scores: ';
        for ($i=0;$i<5;$i++) echo htmlspecialchars($comp).' '.($i+1).': <strong>'.($maxScores[$i]??0).'</strong>'.($i<4?' &nbsp;|&nbsp; ':'');
        echo '</p>';
    }

    echo '<table><thead><tr><th>#</th><th class="left">Name</th>';
    if ($isExam) {
        echo '<th>Score / '.$maxScores[0].'</th>';
    } else {
        for ($i=0;$i<5;$i++) echo '<th>'.$comp.' '.($i+1).' / '.($maxScores[$i]??0).'</th>';
        echo '<th>Total</th>';
    }
    echo '</tr></thead><tbody>';

    $colDefs = [
        'Participation' => ['par_one','par_two','par_three','par_four','par_five'],
        'Written'       => ['written_one','written_two','written_three','written_four','written_five'],
        'Performance'   => ['perf_one','perf_two','perf_three','perf_four','perf_five'],
    ];

    foreach ($rows as $i => $row) {
        echo '<tr><td>'.($i+1).'</td><td class="left">'.htmlspecialchars($row['FullName']).'</td>';
        if ($isExam) {
            echo '<td>'.($row['score'] ?? '—').'</td>';
        } else {
            $cols = $colDefs[$comp]; $total = 0;
            foreach ($cols as $col) {
                $val = $row[$col] ?? '';
                echo '<td>'.($val !== '' ? $val : '—').'</td>';
                $total += (float)($val ?? 0);
            }
            echo '<td><strong>'.$total.'</strong></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';

    echo '<div class="signature-row">
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Instructor</div></div>
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Noted by</div></div>
        <div class="signature-box"><div class="sig-line"></div><div class="sig-label">Approved by</div></div>
    </div>';
    docFooter();
}

else {
    echo '<div style="text-align:center;padding:60px;color:#999;">No print type specified.</div>';
}
?>
</div>

<script>
window.addEventListener('load', function() {
  setTimeout(function() { window.print(); }, 600);
});
</script>
</body>
</html>
