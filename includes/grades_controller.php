<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('view_grades');
$pdo = getConnection();

// ─── AJAX HANDLERS ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // ── Load scores for a component/term ──────────────────────────────────────
    if ($action === 'load_component') {
        $sec  = (int)$_POST['section_id'];
        $sub  = (int)$_POST['subject_id'];
        $term = $_POST['term'];
        $comp = $_POST['component'];

        if ($comp === 'Exam') {
            $sql = "SELECT s.st_id,
                        s.student_no,
                        CONCAT(s.st_lastname,', ',s.st_name,' ',s.st_middlename,' ',s.st_suffix) AS FullName,
                        s.st_gender, e.score
                    FROM teaching_assignments ta
                    INNER JOIN student_assignments sa ON sa.assignment_id=ta.assignment_id
                    INNER JOIN student s ON s.st_id=sa.st_id
                    LEFT JOIN class_record cr ON cr.st_id=s.st_id AND cr.sectionID=ta.sectionID AND cr.sub_id=:sub
                    LEFT JOIN class_record_exam cre ON cre.rec_id=cr.rec_id AND cre.term=:term
                    LEFT JOIN exam e ON e.exam_id=cre.exam_id
                    WHERE ta.sectionID=:sec
                      AND ta.sub_id=:sub
                      AND ta.ay_id=:ay
                      AND sa.ay_id=:ay
                    ORDER BY s.st_gender DESC, s.st_lastname ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sec'=>$sec,':sub'=>$sub,':term'=>$term,':ay'=>current_ay_id($pdo)]);
        } else {
            $map = [
                'Participation' => ['tbl'=>'participation','cols'=>['par_one','par_two','par_three','par_four','par_five'],'jt'=>'class_record_participation','fk'=>'par_id'],
                'Written'       => ['tbl'=>'written','cols'=>['written_one','written_two','written_three','written_four','written_five'],'jt'=>'class_record_written','fk'=>'written_id'],
                'Performance'   => ['tbl'=>'performance','cols'=>['perf_one','perf_two','perf_three','perf_four','perf_five'],'jt'=>'class_record_performance','fk'=>'perf_id'],
            ];
            $m = $map[$comp];
            $colStr = implode(',', array_map(fn($c)=>"t.$c", $m['cols']));
            $sql = "SELECT s.st_id,
                        s.student_no,
                        CONCAT(s.st_lastname,', ',s.st_name,' ',s.st_middlename,' ',s.st_suffix) AS FullName,
                        s.st_gender, $colStr
                    FROM teaching_assignments ta
                    INNER JOIN student_assignments sa ON sa.assignment_id=ta.assignment_id
                    INNER JOIN student s ON s.st_id=sa.st_id
                    LEFT JOIN class_record cr ON cr.st_id=s.st_id AND cr.sectionID=ta.sectionID AND cr.sub_id=:sub
                    LEFT JOIN {$m['jt']} jt ON jt.rec_id=cr.rec_id AND jt.term=:term
                    LEFT JOIN {$m['tbl']} t ON t.{$m['fk']}=jt.{$m['fk']}
                    WHERE ta.sectionID=:sec
                      AND ta.sub_id=:sub
                      AND ta.ay_id=:ay
                      AND sa.ay_id=:ay
                    ORDER BY s.st_gender DESC, s.st_lastname ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sec'=>$sec,':sub'=>$sub,':term'=>$term,':ay'=>current_ay_id($pdo)]);
        }
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // ── Save scores ───────────────────────────────────────────────────────────
    if ($action === 'save_component') {
        require_permission('edit_grades');
        $sec  = (int)$_POST['section_id'];
        $sub  = (int)$_POST['subject_id'];
        $term = $_POST['term'];
        $comp = $_POST['component'];
        $rows = json_decode($_POST['rows'], true);

        // Get max scores for validation
        function getMaxScores($pdo, $comp, $term) {
            if ($comp === 'Exam') {
                $s = $pdo->prepare("SELECT score_max FROM exam_settings WHERE term=? LIMIT 1");
                $s->execute([$term]);
                $r = $s->fetchColumn();
                return $r !== false ? [(int)$r] : [100];
            }
            $s = $pdo->prepare("SELECT one_max,two_max,three_max,four_max,five_max FROM score_settings WHERE term=? AND component=? LIMIT 1");
            $s->execute([$term, strtolower($comp)]);
            $r = $s->fetch();
            return $r ? array_values($r) : [100,100,100,100,100];
        }
        $maxes = getMaxScores($pdo, $comp, $term);

        try {
            $pdo->beginTransaction();
            foreach ($rows as $row) {
                $stId = (int)$row['st_id'];

                // Validate
                if ($comp === 'Exam') {
                    if ((float)($row['score']??0) > $maxes[0])
                        throw new Exception("Exam score for a student exceeds the maximum of {$maxes[0]}.");
                } else {
                    for ($i=0;$i<5;$i++) {
                        if ((float)($row["s".($i+1)]??0) > $maxes[$i])
                            throw new Exception("Score ".($i+1)." exceeds max of {$maxes[$i]} for $comp.");
                    }
                }

                // Ensure class_record
                $cr = $pdo->prepare("SELECT rec_id FROM class_record WHERE st_id=? AND sectionID=? AND sub_id=?");
                $cr->execute([$stId,$sec,$sub]);
                $recId = $cr->fetchColumn();
                if (!$recId) {
                    $pdo->prepare("INSERT INTO class_record (st_id,sectionID,sub_id) VALUES (?,?,?)")->execute([$stId,$sec,$sub]);
                    $recId = $pdo->lastInsertId();
                }

                if ($comp === 'Participation') {
                    $chk = $pdo->prepare("SELECT par_id FROM class_record_participation WHERE rec_id=? AND term=?");
                    $chk->execute([$recId,$term]);
                    $pid = $chk->fetchColumn();
                    $s = [$row['s1']??0,$row['s2']??0,$row['s3']??0,$row['s4']??0,$row['s5']??0];
                    if ($pid) {
                        $pdo->prepare("UPDATE participation SET par_one=?,par_two=?,par_three=?,par_four=?,par_five=? WHERE par_id=?")
                            ->execute([...$s,$pid]);
                    } else {
                        $pdo->prepare("INSERT INTO participation (par_one,par_two,par_three,par_four,par_five) VALUES (?,?,?,?,?)")->execute($s);
                        $pdo->prepare("INSERT INTO class_record_participation (rec_id,par_id,term) VALUES (?,?,?)")->execute([$recId,$pdo->lastInsertId(),$term]);
                    }
                } elseif ($comp === 'Written') {
                    $chk = $pdo->prepare("SELECT written_id FROM class_record_written WHERE rec_id=? AND term=?");
                    $chk->execute([$recId,$term]);
                    $wid = $chk->fetchColumn();
                    $s = [$row['s1']??0,$row['s2']??0,$row['s3']??0,$row['s4']??0,$row['s5']??0];
                    if ($wid) {
                        $pdo->prepare("UPDATE written SET written_one=?,written_two=?,written_three=?,written_four=?,written_five=? WHERE written_id=?")
                            ->execute([...$s,$wid]);
                    } else {
                        $pdo->prepare("INSERT INTO written (written_one,written_two,written_three,written_four,written_five) VALUES (?,?,?,?,?)")->execute($s);
                        $pdo->prepare("INSERT INTO class_record_written (rec_id,written_id,term) VALUES (?,?,?)")->execute([$recId,$pdo->lastInsertId(),$term]);
                    }
                } elseif ($comp === 'Performance') {
                    $chk = $pdo->prepare("SELECT perf_id FROM class_record_performance WHERE rec_id=? AND term=?");
                    $chk->execute([$recId,$term]);
                    $pfid = $chk->fetchColumn();
                    $s = [$row['s1']??0,$row['s2']??0,$row['s3']??0,$row['s4']??0,$row['s5']??0];
                    if ($pfid) {
                        $pdo->prepare("UPDATE performance SET perf_one=?,perf_two=?,perf_three=?,perf_four=?,perf_five=? WHERE perf_id=?")
                            ->execute([...$s,$pfid]);
                    } else {
                        $pdo->prepare("INSERT INTO performance (perf_one,perf_two,perf_three,perf_four,perf_five) VALUES (?,?,?,?,?)")->execute($s);
                        $pdo->prepare("INSERT INTO class_record_performance (rec_id,perf_id,term) VALUES (?,?,?)")->execute([$recId,$pdo->lastInsertId(),$term]);
                    }
                } elseif ($comp === 'Exam') {
                    $chk = $pdo->prepare("SELECT exam_id FROM class_record_exam WHERE rec_id=? AND term=?");
                    $chk->execute([$recId,$term]);
                    $eid = $chk->fetchColumn();
                    $sc = $row['score']??0;
                    if ($eid) {
                        $pdo->prepare("UPDATE exam SET score=? WHERE exam_id=?")->execute([$sc,$eid]);
                    } else {
                        $pdo->prepare("INSERT INTO exam (score) VALUES (?)")->execute([$sc]);
                        $pdo->prepare("INSERT INTO class_record_exam (rec_id,exam_id,term) VALUES (?,?,?)")->execute([$recId,$pdo->lastInsertId(),$term]);
                    }
                }
            }
            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>"$comp grades for $term saved successfully."]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    // ── Summary (Excel-weighted) ─────────────────────────────────────────────
    if ($action === 'load_summary') {
        $sec  = (int)$_POST['section_id'];
        $sub  = (int)$_POST['subject_id'];
        $term = $_POST['term'] ?? '';

        $sql = "SELECT
            s.st_id,
            s.student_no,
            CONCAT(s.st_lastname,', ',s.st_name,' ',IFNULL(s.st_middlename,''),' ',IFNULL(s.st_suffix,'')) AS FullName,
            s.st_gender,

            ROUND(
                (
                    SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END)
                    / NULLIF(COUNT(a.Att_ID), 0)
                ) * 100, 2
            ) AS Attendance,

            ROUND(
                (
                    (IFNULL(p.par_one,0)+IFNULL(p.par_two,0)+IFNULL(p.par_three,0)+IFNULL(p.par_four,0)+IFNULL(p.par_five,0))
                    / NULLIF((IFNULL(ss_p.one_max,0)+IFNULL(ss_p.two_max,0)+IFNULL(ss_p.three_max,0)+IFNULL(ss_p.four_max,0)+IFNULL(ss_p.five_max,0)), 0)
                ) * 100, 2
            ) AS Participation,

            ROUND(
                (
                    (IFNULL(w.written_one,0)+IFNULL(w.written_two,0)+IFNULL(w.written_three,0)+IFNULL(w.written_four,0)+IFNULL(w.written_five,0))
                    / NULLIF((IFNULL(ss_w.one_max,0)+IFNULL(ss_w.two_max,0)+IFNULL(ss_w.three_max,0)+IFNULL(ss_w.four_max,0)+IFNULL(ss_w.five_max,0)), 0)
                ) * 100, 2
            ) AS Written,

            ROUND(
                (
                    (IFNULL(pf.perf_one,0)+IFNULL(pf.perf_two,0)+IFNULL(pf.perf_three,0)+IFNULL(pf.perf_four,0)+IFNULL(pf.perf_five,0))
                    / NULLIF((IFNULL(ss_pf.one_max,0)+IFNULL(ss_pf.two_max,0)+IFNULL(ss_pf.three_max,0)+IFNULL(ss_pf.four_max,0)+IFNULL(ss_pf.five_max,0)), 0)
                ) * 100, 2
            ) AS Performance,

            ROUND(
                (IFNULL(e.score,0) / NULLIF(IFNULL(es.score_max,0), 0)) * 100, 2
            ) AS Exam

        FROM teaching_assignments ta
        INNER JOIN student_assignments sa
            ON sa.assignment_id = ta.assignment_id
        INNER JOIN student s
            ON s.st_id = sa.st_id

        LEFT JOIN attendance a
            ON a.st_id = s.st_id
           AND a.sectionID = ta.sectionID
           AND a.term = :term

        LEFT JOIN class_record cr
            ON cr.st_id = s.st_id
           AND cr.sectionID = ta.sectionID
           AND cr.sub_id = :sub

        LEFT JOIN class_record_participation crp
            ON crp.rec_id = cr.rec_id
           AND crp.term = :term
        LEFT JOIN participation p
            ON p.par_id = crp.par_id
        LEFT JOIN score_settings ss_p
            ON ss_p.term = crp.term
           AND ss_p.component = 'participation'

        LEFT JOIN class_record_written crw
            ON crw.rec_id = cr.rec_id
           AND crw.term = :term
        LEFT JOIN written w
            ON w.written_id = crw.written_id
        LEFT JOIN score_settings ss_w
            ON ss_w.term = crw.term
           AND ss_w.component = 'written'

        LEFT JOIN class_record_performance crpf
            ON crpf.rec_id = cr.rec_id
           AND crpf.term = :term
        LEFT JOIN performance pf
            ON pf.perf_id = crpf.perf_id
        LEFT JOIN score_settings ss_pf
            ON ss_pf.term = crpf.term
           AND ss_pf.component = 'performance'

        LEFT JOIN class_record_exam cre
            ON cre.rec_id = cr.rec_id
           AND cre.term = :term
        LEFT JOIN exam e
            ON e.exam_id = cre.exam_id
        LEFT JOIN exam_settings es
            ON es.term = cre.term

        WHERE ta.sectionID = :sec
          AND ta.sub_id = :sub
          AND ta.ay_id = :ay
          AND sa.ay_id = :ay
        GROUP BY s.st_id
        ORDER BY s.st_gender DESC, s.st_lastname ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sec'  => $sec,
            ':sub'  => $sub,
            ':term' => $term,
            ':ay'   => current_ay_id($pdo)
        ]);

        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $attendance    = is_numeric($row['Attendance'])    ? (float)$row['Attendance']    : 0;
            $participation = is_numeric($row['Participation']) ? (float)$row['Participation'] : 0;
            $written       = is_numeric($row['Written'])       ? (float)$row['Written']       : 0;
            $performance   = is_numeric($row['Performance'])   ? (float)$row['Performance']   : 0;
            $exam          = is_numeric($row['Exam'])          ? (float)$row['Exam']          : 0;

            $row['TermTotal'] = round(
                round($attendance * 0.10, 0) +
                round($participation * 0.15, 0) +
                round($written * 0.20, 0) +
                round($performance * 0.30, 0) +
                round($exam * 0.25, 0),
            0);
        }
        unset($row);

        echo json_encode($rows);
        exit;
    }
}

$sections = $pdo->query("SELECT section.sectionID, section.section, course.course_acronym FROM section INNER JOIN course ON course.course_id=section.course_id ORDER BY section.section")->fetchAll();
$subjects = $pdo->query("SELECT sub_id, sub_name FROM subject ORDER BY sub_name")->fetchAll();
?>