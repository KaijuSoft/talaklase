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
                        CONCAT(s.st_lastname,', ',s.st_name,' ',s.st_middlename,' ',s.st_suffix) AS FullName,
                        s.st_gender, e.score
                    FROM student s
                    INNER JOIN student_section ss ON ss.st_id=s.st_id
                    LEFT JOIN class_record cr ON cr.st_id=s.st_id AND cr.sectionID=ss.sectionID AND cr.sub_id=:sub
                    LEFT JOIN class_record_exam cre ON cre.rec_id=cr.rec_id AND cre.term=:term
                    LEFT JOIN exam e ON e.exam_id=cre.exam_id
                    WHERE ss.sectionID=:sec
                    ORDER BY s.st_gender DESC, s.st_lastname ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sec'=>$sec,':sub'=>$sub,':term'=>$term]);
        } else {
            $map = [
                'Participation' => ['tbl'=>'participation','cols'=>['par_one','par_two','par_three','par_four','par_five'],'jt'=>'class_record_participation','fk'=>'par_id'],
                'Written'       => ['tbl'=>'written','cols'=>['written_one','written_two','written_three','written_four','written_five'],'jt'=>'class_record_written','fk'=>'written_id'],
                'Performance'   => ['tbl'=>'performance','cols'=>['perf_one','perf_two','perf_three','perf_four','perf_five'],'jt'=>'class_record_performance','fk'=>'perf_id'],
            ];
            $m = $map[$comp];
            $colStr = implode(',', array_map(fn($c)=>"t.$c", $m['cols']));
            $sql = "SELECT s.st_id,
                        CONCAT(s.st_lastname,', ',s.st_name,' ',s.st_middlename,' ',s.st_suffix) AS FullName,
                        s.st_gender, $colStr
                    FROM student s
                    INNER JOIN student_section ss ON ss.st_id=s.st_id
                    LEFT JOIN class_record cr ON cr.st_id=s.st_id AND cr.sectionID=ss.sectionID AND cr.sub_id=:sub
                    LEFT JOIN {$m['jt']} jt ON jt.rec_id=cr.rec_id AND jt.term=:term
                    LEFT JOIN {$m['tbl']} t ON t.{$m['fk']}=jt.{$m['fk']}
                    WHERE ss.sectionID=:sec
                    ORDER BY s.st_gender DESC, s.st_lastname ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sec'=>$sec,':sub'=>$sub,':term'=>$term]);
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

        FROM student s
        INNER JOIN student_section ss_sec
            ON ss_sec.st_id = s.st_id

        LEFT JOIN attendance a
            ON a.st_id = s.st_id
           AND a.sectionID = ss_sec.sectionID
           AND a.term = :term

        LEFT JOIN class_record cr
            ON cr.st_id = s.st_id
           AND cr.sectionID = ss_sec.sectionID
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

        WHERE ss_sec.sectionID = :sec
        GROUP BY s.st_id
        ORDER BY s.st_gender DESC, s.st_lastname ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sec'  => $sec,
            ':sub'  => $sub,
            ':term' => $term
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

<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <h6 class="mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Grading Form</h6>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" onclick="printGrades()">
        <i class="bi bi-printer-fill me-1"></i> Print
      </button>
      <?php if (can('manage_score_settings')): ?>
      <a href="?page=score_settings" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-gear-fill me-1"></i> Score Settings
      </a>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">

    <!-- Section & Subject selectors -->
    <div class="row g-3 mb-4">
      <div class="col-md-5">
        <label class="form-label">Section</label>
        <select class="form-select" id="gr_section" onchange="onFilterChange()">
          <option value="">Select Section…</option>
          <?php foreach ($sections as $s): ?>
            <option value="<?= $s['sectionID'] ?>"><?= htmlspecialchars($s['section']) ?> (<?= $s['course_acronym'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label">Subject</label>
        <select class="form-select" id="gr_subject" onchange="onFilterChange()">
          <option value="">Select Subject…</option>
          <?php foreach ($subjects as $s): ?>
            <option value="<?= $s['sub_id'] ?>"><?= htmlspecialchars($s['sub_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100" onclick="loadGrid()">
          <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Term tabs -->
    <ul class="nav nav-tabs mb-0" id="termTabs" role="tablist">
      <li class="nav-item"><a class="nav-link active" href="#" data-term="Prelim"    onclick="setTerm(this);return false;">Prelim</a></li>
      <li class="nav-item"><a class="nav-link"        href="#" data-term="Midterm"   onclick="setTerm(this);return false;">Midterm</a></li>
      <li class="nav-item"><a class="nav-link"        href="#" data-term="PreFinal"  onclick="setTerm(this);return false;">Pre-Finals</a></li>
      <li class="nav-item"><a class="nav-link"        href="#" data-term="Final"     onclick="setTerm(this);return false;">Finals</a></li>
      <li class="nav-item"><a class="nav-link text-success" href="#" data-term="Summary" onclick="setTerm(this);return false;"><i class="bi bi-bar-chart-fill me-1"></i>Summary</a></li>
    </ul>

    <!-- Component sub-tabs (hidden in Summary) -->
    <div id="compTabsWrapper" class="bg-light border border-top-0 px-3 pt-2 pb-1 mb-3">
      <ul class="nav nav-pills" id="compTabs">
        <li class="nav-item"><a class="nav-link active py-1" href="#" data-comp="Participation" onclick="setComp(this);return false;">Participation</a></li>
        <li class="nav-item"><a class="nav-link py-1"        href="#" data-comp="Written"       onclick="setComp(this);return false;">Written</a></li>
        <li class="nav-item"><a class="nav-link py-1"        href="#" data-comp="Performance"   onclick="setComp(this);return false;">Performance</a></li>
        <li class="nav-item"><a class="nav-link py-1"        href="#" data-comp="Exam"          onclick="setComp(this);return false;">Exam</a></li>
      </ul>
    </div>

    <!-- Grid output -->
    <div id="gradeGrid">
      <div class="text-center text-muted py-5">
        <i class="bi bi-arrow-up-circle fs-1 d-block mb-2"></i>
        Select a section and subject above to load grades.
      </div>
    </div>

    <!-- Save button -->
    <?php if (can('manage_grades')): ?>
    <div id="saveBtnArea" class="d-none mt-3 d-flex gap-2 align-items-center">
      <button class="btn btn-success" onclick="saveGrades()">
        <i class="bi bi-floppy-fill me-1"></i> Save <span id="saveLabel"></span>
      </button>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
let curTerm = 'Prelim';
let curComp = 'Participation';
let gradeRows = [];
let cache = {};
const canManageGrades = <?= can('manage_grades') ? 'true' : 'false' ?>;

const termLabel = {Prelim:'Prelim',Midterm:'Midterm',PreFinal:'Pre-Finals',Final:'Finals'};

function setTerm(el) {
  document.querySelectorAll('#termTabs .nav-link').forEach(a=>a.classList.remove('active'));
  el.classList.add('active');
  curTerm = el.dataset.term;
  const isSummary = curTerm === 'Summary';
  document.getElementById('compTabsWrapper').style.display = isSummary ? 'none' : '';
  document.getElementById('saveBtnArea')?.classList.add('d-none');
  loadGrid();
}

function setComp(el) {
  document.querySelectorAll('#compTabs .nav-link').forEach(a=>a.classList.remove('active'));
  el.classList.add('active');
  curComp = el.dataset.comp;
  loadGrid();
}

function getSecSub() {
  return {
    sec: document.getElementById('gr_section').value,
    sub: document.getElementById('gr_subject').value
  };
}

function onFilterChange() {
  cache = {};
  loadGrid();
}

function loadGrid() {
  const {sec, sub} = getSecSub();
  if (!sec || !sub) {
    document.getElementById('gradeGrid').innerHTML =
      '<div class="alert alert-warning mb-0">Please select both a section and a subject.</div>';
    document.getElementById('saveBtnArea')?.classList.add('d-none');
    return;
  }
  if (curTerm === 'Summary') { loadSummary(); return; }

  const key = `${curTerm}_${curComp}`;
  if (cache[key]) { renderGrid(cache[key]); return; }

  document.getElementById('gradeGrid').innerHTML =
    '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading grades…</p></div>';

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'load_component',section_id:sec,subject_id:sub,term:curTerm,component:curComp})})
  .then(r=>r.json()).then(data => { cache[key]=data; renderGrid(data); });
}

function renderGrid(data) {
  gradeRows = data;
  const isExam = curComp === 'Exam';
  const lbl = `${curComp} - ${termLabel[curTerm]||curTerm}`;
  if (canManageGrades) {
    document.getElementById('saveLabel').textContent = lbl;
    document.getElementById('saveBtnArea')?.classList.remove('d-none');
  }

  if (!data.length) {
    document.getElementById('gradeGrid').innerHTML =
      '<div class="alert alert-info mb-0">No students found in this section.</div>';
    return;
  }

  const colDefs = {
    Participation: ['par_one','par_two','par_three','par_four','par_five'],
    Written:       ['written_one','written_two','written_three','written_four','written_five'],
    Performance:   ['perf_one','perf_two','perf_three','perf_four','perf_five'],
  };

  let html = `<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover mb-0">
    <thead class="table-light"><tr>
      <th style="width:35px">#</th>
      <th>Name</th>
      <th style="width:80px">Gender</th>`;

  if (isExam) {
    html += `<th class="text-center" style="width:90px">Score</th>`;
  } else {
    for (let i=1;i<=5;i++) html += `<th class="text-center" style="width:90px">${curComp} ${i}</th>`;
  }
  html += '</tr></thead><tbody>';

  data.forEach((row, i) => {
    const g = row.st_gender==='Male' ? 'badge-gender-male' : 'badge-gender-female';
    html += `<tr><td class="text-muted">${i+1}</td><td>${row.FullName}</td>
      <td><span class="badge ${g}">${row.st_gender}</span></td>`;

    if (isExam) {
      html += `<td><input type="number" class="form-control form-control-sm grade-input text-center"
        id="score_${row.st_id}" value="${row.score??''}" min="0" step="0.01" placeholder="0" ${canManageGrades ? '' : 'readonly'}/></td>`;
    } else {
      const cols = colDefs[curComp];
      cols.forEach((col, j) => {
        html += `<td><input type="number" class="form-control form-control-sm grade-input text-center"
          id="s${j+1}_${row.st_id}" value="${row[col]??''}" min="0" step="0.01" placeholder="0" ${canManageGrades ? '' : 'readonly'}/></td>`;
      });
    }
    html += '</tr>';
  });
  html += '</tbody></table></div>';
  document.getElementById('gradeGrid').innerHTML = html;
}

function saveGrades() {
  if (!canManageGrades) { showToast('You do not have permission to save grades.','danger'); return; }
  const {sec, sub} = getSecSub();
  if (!sec || !sub) { showToast('Select section and subject first.','warning'); return; }
  const lbl = `${curComp} grades for ${termLabel[curTerm]||curTerm}`;
  if (!confirm(`Save ${lbl}?`)) return;

  const isExam = curComp === 'Exam';
  const rows = gradeRows.map(row => {
    if (isExam) return {st_id:row.st_id, score:document.getElementById(`score_${row.st_id}`)?.value||0};
    return {
      st_id:row.st_id,
      s1:document.getElementById(`s1_${row.st_id}`)?.value||0,
      s2:document.getElementById(`s2_${row.st_id}`)?.value||0,
      s3:document.getElementById(`s3_${row.st_id}`)?.value||0,
      s4:document.getElementById(`s4_${row.st_id}`)?.value||0,
      s5:document.getElementById(`s5_${row.st_id}`)?.value||0,
    };
  });

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'save_component',section_id:sec,subject_id:sub,term:curTerm,component:curComp,rows:JSON.stringify(rows)})})
  .then(r=>r.json()).then(res => {
    showToast(res.message, res.success?'success':'danger');
    if (res.success) delete cache[`${curTerm}_${curComp}`];
  });
}

function equivalentGrade(avg) {
  avg = Number(avg);
  if (isNaN(avg)) return '';
  if (avg >= 96) return '1.00';
  if (avg >= 94) return '1.25';
  if (avg >= 92) return '1.50';
  if (avg >= 89) return '1.75';
  if (avg >= 87) return '2.00';
  if (avg >= 84) return '2.25';
  if (avg >= 82) return '2.50';
  if (avg >= 79) return '2.75';
  if (avg >= 75) return '3.00';
  return '5.00';
}

function summaryRemarks(avg) {
  avg = Number(avg);
  if (isNaN(avg)) return '';
  return avg >= 75 ? 'Passed' : 'Failed';
}

function loadSummary() {
  const {sec,sub} = getSecSub();
  if (!sec || !sub) {
    document.getElementById('gradeGrid').innerHTML =
      '<div class="alert alert-warning mb-0">Please select a section and subject.</div>';
    return;
  }

  document.getElementById('gradeGrid').innerHTML =
    '<div class="text-center py-4"><div class="spinner-border text-success"></div><p class="mt-2 text-muted">Loading summary…</p></div>';

  const postTerm = (term) => fetch('', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({
      action:'load_summary',
      section_id:sec,
      subject_id:sub,
      term:term
    })
  }).then(r=>r.json());

  Promise.all([
    postTerm('Prelim'),
    postTerm('Midterm'),
    postTerm('PreFinal'),
    postTerm('Final')
  ]).then(([prelimRows, midtermRows, prefinalRows, finalRows]) => {
    if (!prelimRows.length && !midtermRows.length && !prefinalRows.length && !finalRows.length) {
      document.getElementById('gradeGrid').innerHTML =
        '<div class="alert alert-info mb-0">No summary data found.</div>';
      return;
    }

    const bucket = {};
    [prelimRows, midtermRows, prefinalRows, finalRows].forEach(group => {
      group.forEach(row => {
        if (!bucket[row.st_id]) {
          bucket[row.st_id] = {
            st_id: row.st_id,
            FullName: row.FullName,
            st_gender: row.st_gender,
            Prelim: '',
            Midterm: '',
            PreFinal: '',
            Final: ''
          };
        }
      });
    });

    prelimRows.forEach(row => { bucket[row.st_id].Prelim = row.TermTotal; });
    midtermRows.forEach(row => { bucket[row.st_id].Midterm = row.TermTotal; });
    prefinalRows.forEach(row => { bucket[row.st_id].PreFinal = row.TermTotal; });
    finalRows.forEach(row => { bucket[row.st_id].Final = row.TermTotal; });

    const rows = Object.values(bucket);

    let html = `<div class="table-responsive">
      <table class="table table-sm table-bordered table-hover mb-0">
      <thead class="table-success">
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Gender</th>
          <th class="text-center">Prelim</th>
          <th class="text-center">Midterm</th>
          <th class="text-center">Quarter 1</th>
          <th class="text-center">Pre-Finals</th>
          <th class="text-center">Finals</th>
          <th class="text-center">Quarter 2</th>
          <th class="text-center">Average Grades</th>
          <th class="text-center">Equivalent Grades</th>
          <th class="text-center">Remarks</th>
        </tr>
      </thead><tbody>`;

    rows.forEach((row, i) => {
      const g = row.st_gender === 'Male' ? 'badge-gender-male' : 'badge-gender-female';

      const prelim = row.Prelim === '' ? null : Number(row.Prelim);
      const midterm = row.Midterm === '' ? null : Number(row.Midterm);
      const prefinal = row.PreFinal === '' ? null : Number(row.PreFinal);
      const final = row.Final === '' ? null : Number(row.Final);

      const q1 = (prelim !== null && midterm !== null) ? Math.round((prelim + midterm) / 2) : '';
      const q2 = (prefinal !== null && final !== null) ? Math.round((prefinal + final) / 2) : '';
      const avg = (q1 !== '' && q2 !== '') ? Math.round((q1 + q2) / 2) : '';
      const eq = avg !== '' ? equivalentGrade(avg) : '';
      const rem = avg !== '' ? summaryRemarks(avg) : '';

      html += `<tr>
        <td class="text-muted">${i+1}</td>
        <td>${row.FullName}</td>
        <td><span class="badge ${g}">${row.st_gender}</span></td>
        <td class="text-center">${prelim ?? ''}</td>
        <td class="text-center">${midterm ?? ''}</td>
        <td class="text-center"><strong>${q1}</strong></td>
        <td class="text-center">${prefinal ?? ''}</td>
        <td class="text-center">${final ?? ''}</td>
        <td class="text-center"><strong>${q2}</strong></td>
        <td class="text-center"><strong>${avg}</strong></td>
        <td class="text-center">${eq}</td>
        <td class="text-center ${rem === 'Passed' ? 'text-success' : (rem === 'Failed' ? 'text-danger' : '')}">${rem}</td>
      </tr>`;
    });

    html += '</tbody></table></div>';
    document.getElementById('gradeGrid').innerHTML = html;
  }).catch(() => {
    document.getElementById('gradeGrid').innerHTML =
      '<div class="alert alert-danger mb-0">Failed to load summary.</div>';
  });
}
function printGrades() {
  const {sec, sub} = getSecSub();
  if (!sec || !sub) { showToast('Select a section and subject first.','warning'); return; }
  if (curTerm === 'Summary') { showToast('Switch to a term tab to print grades.','warning'); return; }
  const url = `print.php?type=grades&sec=${sec}&sub=${sub}&term=${curTerm}&comp=${curComp}`;
  window.open(url, '_blank');
}
</script>
