<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission('view_students');
$pdo = getConnection();
$studentId = (int)($_GET['st_id'] ?? 0);
if ($studentId < 1) { http_response_code(404); echo '<div class="alert alert-warning">Student not specified.</div>'; return; }
$user = current_user();
$isInstructorScoped = in_array($user['role'] ?? '', ['instructor','instructor_admin'], true);
$ownedSections = current_user_owned_section_ids($pdo);
$stmt = $pdo->prepare("SELECT s.*,c.course_acronym,c.course_name,ss.sectionID,sec.section,ss.yearlvl,ay.ay_name FROM student s LEFT JOIN course c ON c.course_id=s.course_id LEFT JOIN student_section ss ON ss.st_id=s.st_id LEFT JOIN section sec ON sec.sectionID=ss.sectionID LEFT JOIN academic_year ay ON ay.ay_id=ss.ay_id WHERE s.st_id=:id ORDER BY ss.ay_id DESC LIMIT 1");
$stmt->execute([':id'=>$studentId]);
$student=$stmt->fetch();
if (!$student) { http_response_code(404); echo '<div class="alert alert-warning">Student not found.</div>'; return; }
if ($isInstructorScoped && !in_array((int)$student['sectionID'],$ownedSections,true)) { http_response_code(403); echo '<div class="alert alert-danger">You do not have access to this student record.</div>'; return; }
$terms=['Prelim','Midterm','PreFinal','Final'];
$termLabels=['Prelim'=>'Prelim','Midterm'=>'Midterm','PreFinal'=>'Pre-Finals','Final'=>'Finals'];
$selectedTerm=$_GET['term'] ?? 'All';
if($selectedTerm!=='All'&&!in_array($selectedTerm,$terms,true))$selectedTerm='All';
$ayId=current_ay_id($pdo);
$termWhere=$selectedTerm==='All'?'':' AND crp.term=:term';
$sql="SELECT subj.sub_id,subj.sub_code,subj.sub_name,i.inst_name,crp.term,
 p.par_one,p.par_two,p.par_three,p.par_four,p.par_five,
 w.written_one,w.written_two,w.written_three,w.written_four,w.written_five,
 pf.perf_one,pf.perf_two,pf.perf_three,pf.perf_four,pf.perf_five,e.score
 FROM student_assignments sa
 INNER JOIN teaching_assignments ta ON ta.assignment_id=sa.assignment_id AND ta.ay_id=:ay
 INNER JOIN subject subj ON subj.sub_id=ta.sub_id
 LEFT JOIN instructor i ON i.inst_id=ta.inst_id
 LEFT JOIN class_record cr ON cr.st_id=sa.st_id AND cr.sectionID=ta.sectionID AND cr.sub_id=ta.sub_id
 LEFT JOIN class_record_participation crp ON crp.rec_id=cr.rec_id $termWhere
 LEFT JOIN participation p ON p.par_id=crp.par_id
 LEFT JOIN class_record_written crw ON crw.rec_id=cr.rec_id AND crw.term=crp.term
 LEFT JOIN written w ON w.written_id=crw.written_id
 LEFT JOIN class_record_performance crpf ON crpf.rec_id=cr.rec_id AND crpf.term=crp.term
 LEFT JOIN performance pf ON pf.perf_id=crpf.perf_id
 LEFT JOIN class_record_exam cre ON cre.rec_id=cr.rec_id AND cre.term=crp.term
 LEFT JOIN exam e ON e.exam_id=cre.exam_id
 WHERE sa.st_id=:id AND sa.ay_id=:ay
 ORDER BY subj.sub_name,FIELD(crp.term,'Prelim','Midterm','PreFinal','Final')";
$params=[':id'=>$studentId,':ay'=>$ayId]; if($selectedTerm!=='All')$params[':term']=$selectedTerm;
$gs=$pdo->prepare($sql);$gs->execute($params);$grades=$gs->fetchAll();

$as=$pdo->prepare("SELECT a.Att_ID,a._date,a.term,a.status,a.time_in,a.late_minutes,ta.start_time,ta.end_time,subj.sub_code,subj.sub_name,i.inst_name,sec.section FROM attendance a INNER JOIN teaching_assignments ta ON ta.assignment_id=a.assignment_id INNER JOIN subject subj ON subj.sub_id=ta.sub_id LEFT JOIN instructor i ON i.inst_id=ta.inst_id INNER JOIN section sec ON sec.sectionID=a.sectionID WHERE a.st_id=:id AND ta.ay_id=:ay ORDER BY FIELD(a.term,'Prelim','Midterm','PreFinal','Final'),a._date ASC");
$as->execute([':id'=>$studentId,':ay'=>$ayId]);$attendance=$as->fetchAll();
$attByTerm=[];$attTotals=['Present'=>0,'Late'=>0,'Absent'=>0];$totalHours=0.0;
foreach($attendance as $a){if($selectedTerm!=='All'&&$a['term']!==$selectedTerm)continue;$attByTerm[$a['term']][]=$a;$attTotals[$a['status']] = ($attTotals[$a['status']]??0)+1;$duration=max(0,(strtotime($a['end_time'])-strtotime($a['start_time']))/3600);if($a['status']==='Present')$totalHours+=$duration;elseif($a['status']==='Late')$totalHours+=max(0,$duration-((float)$a['late_minutes']/60));}
?><div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div><a href="?page=students" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Back to Students</a>
    <h2 class="mb-1"><?=htmlspecialchars($student['st_lastname'].', '.$student['st_name'])?></h2>
    <div class="text-muted"><?=htmlspecialchars($student['course_acronym']??'')?> · <?=htmlspecialchars($student['yearlvl']??'')?> · <?=htmlspecialchars($student['section']??'')?></div>
  </div>
  <form method="GET" class="d-flex gap-2"><input type="hidden" name="page" value="student_profile"><input type="hidden" name="st_id" value="<?=$studentId?>"><select name="term" class="form-select form-select-sm" onchange="this.form.submit()"><option value="All">All Terms</option><?php foreach($terms as $t):?><option value="<?=$t?>" <?=$selectedTerm===$t?'selected':''?>><?=$termLabels[$t]?></option><?php endforeach;?></select></form>
</div>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-value"><?=htmlspecialchars($student['student_no']??'—')?></div><div class="stat-label">Student Number</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-value"><?=$attTotals['Present']?></div><div class="stat-label">Present Records</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-value"><?=$attTotals['Late']?></div><div class="stat-label">Late Records</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-value"><?=number_format($totalHours,2)?> hrs</div><div class="stat-label">Hours Rendered</div></div></div>
</div>

<ul class="nav nav-tabs mb-3" id="studentRecordTabs"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profileOverview">Overview</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#profileGrades">Grades</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#profileAttendance">Attendance</button></li></ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="profileOverview"><div class="card"><div class="card-body"><div class="row g-3"><div class="col-md-4"><small class="text-muted">Student Number</small><div class="fw-semibold"><?=htmlspecialchars($student['student_no']??'—')?></div></div><div class="col-md-4"><small class="text-muted">Course</small><div class="fw-semibold"><?=htmlspecialchars($student['course_name']??$student['course_acronym']??'—')?></div></div><div class="col-md-4"><small class="text-muted">Gender</small><div class="fw-semibold"><?=htmlspecialchars($student['st_gender']??'—')?></div></div><div class="col-md-4"><small class="text-muted">Section</small><div class="fw-semibold"><?=htmlspecialchars($student['section']??'—')?></div></div><div class="col-md-4"><small class="text-muted">Year Level</small><div class="fw-semibold"><?=htmlspecialchars($student['yearlvl']??'—')?></div></div><div class="col-md-4"><small class="text-muted">Academic Year</small><div class="fw-semibold"><?=htmlspecialchars($student['ay_name']??'Current')?></div></div></div></div></div></div>
<?php
$scoreSettings=[];
foreach($terms as $t){$q=$pdo->prepare("SELECT component,one_max,two_max,three_max,four_max,five_max FROM score_settings WHERE term=?");$q->execute([$t]);foreach($q->fetchAll() as $r)$scoreSettings[$t][$r['component']]=$r;$q=$pdo->prepare("SELECT score_max FROM exam_settings WHERE term=? LIMIT 1");$q->execute([$t]);$scoreSettings[$t]['exam']=(int)($q->fetchColumn()?:0);}
?>
<div class="tab-pane fade" id="profileGrades">
  <div class="card"><div class="card-body">
    <?php if(empty($grades)):?><div class="text-center text-muted py-5">No grade records found for the selected term.</div><?php else: $lastSubject='';$lastTerm='';foreach($grades as $g):
      if($g['sub_id']!==$lastSubject){if($lastSubject!=='')echo '</div></div>'; $lastSubject=$g['sub_id'];$lastTerm='';?>
      <div class="mb-4"><h5 class="mb-1"><?=htmlspecialchars($g['sub_code'].' — '.$g['sub_name'])?></h5><div class="text-muted small mb-3"><?=htmlspecialchars($g['inst_name']??'')?></div>
    <?php } if($g['term']!==$lastTerm){$lastTerm=$g['term'];if($lastTerm!==$g['term']||false){}?>
      <div class="border rounded p-3 mb-3"><h6 class="text-primary mb-3"><?=htmlspecialchars($termLabels[$g['term']]??$g['term'])?></h6>
      <div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Component</th><th>Score 1</th><th>Score 2</th><th>Score 3</th><th>Score 4</th><th>Score 5</th><th>Exam</th></tr></thead><tbody>
    <?php $ps=$scoreSettings[$g['term']]['participation']??[];$ws=$scoreSettings[$g['term']]['written']??[];$fs=$scoreSettings[$g['term']]['performance']??[]; ?>
      <tr><th>Participation</th><?php foreach(['par_one','par_two','par_three','par_four','par_five'] as $i=>$c):?><td><?=htmlspecialchars((string)($g[$c]??'—'))?> / <?=htmlspecialchars((string)($ps[array_keys(['one_max'=>1,'two_max'=>1,'three_max'=>1,'four_max'=>1,'five_max'=>1])[$i]]??'—'))?></td><?php endforeach;?><td>—</td></tr>
      <tr><th>Written</th><?php foreach(['written_one','written_two','written_three','written_four','written_five'] as $i=>$c):?><td><?=htmlspecialchars((string)($g[$c]??'—'))?> / <?=htmlspecialchars((string)($ws[array_keys(['one_max'=>1,'two_max'=>1,'three_max'=>1,'four_max'=>1,'five_max'=>1])[$i]]??'—'))?></td><?php endforeach;?><td>—</td></tr>
      <tr><th>Performance</th><?php foreach(['perf_one','perf_two','perf_three','perf_four','perf_five'] as $i=>$c):?><td><?=htmlspecialchars((string)($g[$c]??'—'))?> / <?=htmlspecialchars((string)($fs[array_keys(['one_max'=>1,'two_max'=>1,'three_max'=>1,'four_max'=>1,'five_max'=>1])[$i]]??'—'))?></td><?php endforeach;?><td>—</td></tr>
      <tr><th>Major Exam</th><td colspan="5">—</td><td><?=htmlspecialchars((string)($g['score']??'—'))?> / <?=htmlspecialchars((string)($scoreSettings[$g['term']]['exam']??'—'))?></td></tr>
      </tbody></table></div></div>
    <?php } endforeach;if($lastSubject!=='')echo '</div></div>';endif;?>
  </div></div>
</div>
<div class="tab-pane fade" id="profileAttendance">
  <div class="card"><div class="card-body">
  <?php if(empty($attByTerm)):?><div class="text-center text-muted py-5">No attendance records found for the selected term.</div><?php else:foreach($terms as $t):if(empty($attByTerm[$t]))continue;?>
    <div class="mb-4"><div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0"><?=htmlspecialchars($termLabels[$t])?></h5><span class="badge bg-secondary-subtle text-secondary border"><?=count($attByTerm[$t])?> records</span></div>
    <div class="table-responsive"><table class="table table-sm table-hover table-bordered mb-0"><thead class="table-light"><tr><th>Date</th><th>Subject</th><th>Instructor</th><th>Status</th><th>Time In</th><th>Late Minutes</th><th>Hours Rendered</th></tr></thead><tbody>
    <?php foreach($attByTerm[$t] as $a):$duration=max(0,(strtotime($a['end_time'])-strtotime($a['start_time']))/3600);$hours=$a['status']==='Present'?$duration:($a['status']==='Late'?max(0,$duration-((float)$a['late_minutes']/60)):0);?>
      <tr><td><?=htmlspecialchars($a['_date'])?></td><td><?=htmlspecialchars($a['sub_code'].' — '.$a['sub_name'])?></td><td><?=htmlspecialchars($a['inst_name']??'')?></td><td><span class="badge <?= $a['status']==='Present'?'bg-success':($a['status']==='Late'?'bg-warning text-dark':'bg-danger')?>"><?=htmlspecialchars($a['status'])?></span></td><td><?=htmlspecialchars($a['time_in']??'—')?></td><td><?= $a['status']==='Late'?(int)$a['late_minutes']:'—' ?></td><td class="fw-semibold"><?=number_format($hours,2)?> hrs</td></tr>
    <?php endforeach;?></tbody></table></div></div>
  <?php endforeach;endif;?>
  </div></div>
</div>
</div>
<style>.student-profile-table th{white-space:nowrap}.student-profile-table td{vertical-align:middle}</style>