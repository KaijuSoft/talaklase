<?php
require_once __DIR__ . '/../includes/analytics_controller.php';
$displayName = (string)($currentUser['name'] ?? 'Administrator');
function analyticsPct(float $value, float $total): float { return $total > 0 ? max(0, min(100, ($value / $total) * 100)) : 0; }
function analyticsTrendPoints(array $rows, float $max): string {
    if (!$rows) return '';
    $count = count($rows); $points = [];
    foreach ($rows as $i => $row) {
        $x = $count === 1 ? 50 : 24 + ($i * 472 / ($count - 1));
        $y = 168 - ((int)$row['total'] / max(1, $max)) * 132;
        $points[] = round($x, 1) . ',' . round($y, 1);
    }
    return implode(' ', $points);
}
$trendMax = max(1, ...array_map(fn($r) => (int)$r['total'], $trendRows));
$statusTotal = max(1, $totalAttendance);
$statusPresent = analyticsPct($present, $statusTotal);
$statusLate = analyticsPct($late, $statusTotal);
$courseMax = max(1, ...array_map(fn($r) => (int)$r['total'], $courseRows));
?>
<section class="analytics-page">
  <header class="analytics-head">
    <div>
      <span class="analytics-eyebrow">Institutional intelligence</span>
      <h1>Analytics</h1>
      <p>Understand student success, attendance, academics, and daily academic operations at a glance.</p>
    </div>
    <form method="GET" class="analytics-filterbar">
      <input type="hidden" name="page" value="analytics">
      <label>Academic Year<span class="analytics-filter-value"><?= htmlspecialchars($ayName) ?></span></label>
      <label>Term<select name="term"><option value="">All Terms</option><?php foreach ($allowedTerms as $t): ?><option value="<?= htmlspecialchars($t) ?>" <?= $term === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option><?php endforeach; ?></select></label>
      <button class="btn btn-primary" type="submit">Apply</button>
    </form>
  </header>

  <nav class="analytics-tabs" aria-label="Analytics sections">
    <button type="button" class="analytics-tab active" data-tab="overview">Overview</button>
    <button type="button" class="analytics-tab" data-tab="attendance">Attendance</button>
    <button type="button" class="analytics-tab" data-tab="academics">Academics</button>
    <button type="button" class="analytics-tab" data-tab="students">Students &amp; Operations</button>
  </nav>

  <div class="analytics-tab-panel active" data-panel="overview">
    <div class="analytics-kpis">
      <?php foreach ([['Students',$totalStudents,'bi-people'],['Sections',$totalSections,'bi-diagram-3'],['Instructors',$totalInstructors,'bi-person-workspace'],['Teaching Loads',$totalLoads,'bi-journal-text']] as $c): ?>
      <div class="analytics-kpi"><span class="analytics-kpi-icon"><i class="bi <?= $c[2] ?>"></i></span><span><strong><?= number_format((int)$c[1]) ?></strong><small><?= htmlspecialchars($c[0]) ?></small></span></div>
      <?php endforeach; ?>
    </div>

    <div class="analytics-grid analytics-grid-hero">
      <article class="analytics-surface attendance-hero">
        <div class="analytics-surface-head"><div><span>Attendance</span><h2>30-day activity</h2><p>Recorded attendance sessions across the selected scope.</p></div><strong><?= number_format($totalAttendance) ?><small>records</small></strong></div>
        <div class="analytics-chart"><svg viewBox="0 0 520 205" role="img" aria-label="Attendance activity trend">
          <line x1="24" y1="168" x2="496" y2="168" class="chart-axis"/><line x1="24" y1="102" x2="496" y2="102" class="chart-grid"/><line x1="24" y1="36" x2="496" y2="36" class="chart-grid"/>
          <?php if ($trendRows): ?><polyline points="<?= htmlspecialchars(analyticsTrendPoints($trendRows,$trendMax)) ?>" class="chart-line"/><?php foreach ($trendRows as $i => $r): $count=count($trendRows); $x=$count===1?50:24+($i*472/($count-1)); $y=168-((int)$r['total']/max(1,$trendMax))*132; if($i===0||$i===$count-1||$i%5===0): ?><circle cx="<?= round($x,1) ?>" cy="<?= round($y,1) ?>" r="3.5" class="chart-dot"/><text x="<?= round($x,1) ?>" y="193" class="chart-label" text-anchor="middle"><?= htmlspecialchars(date('M d',strtotime($r['label']))) ?></text><?php endif; endforeach; ?><?php else: ?><text x="260" y="105" class="chart-empty" text-anchor="middle">No attendance activity.</text><?php endif; ?></svg></div>
      </article>
      <article class="analytics-surface">
        <div class="analytics-surface-head"><div><span>Attendance mix</span><h2>Status distribution</h2><p>Current filtered records.</p></div></div>
        <div class="analytics-donut-row"><div class="analytics-donut" style="--present:<?= $statusPresent ?>%;--late:<?= $statusPresent+$statusLate ?>%"><div><strong><?= number_format($statusPresent,1) ?>%</strong><small>Present</small></div></div><div class="analytics-legend"><div><i class="present"></i>Present <b><?= number_format($present) ?></b></div><div><i class="late"></i>Late <b><?= number_format($late) ?></b></div><div><i class="absent"></i>Absent <b><?= number_format($absent) ?></b></div></div></div>
      </article>
    </div>

    <div class="analytics-grid analytics-grid-two">
      <article class="analytics-surface"><div class="analytics-surface-head"><div><span>Enrollment</span><h2>Student population</h2><p>Distribution by course.</p></div><a href="?page=students" class="analytics-link">View records →</a></div><div class="analytics-bars">
      <?php foreach ($courseRows as $r): $value=(int)$r['total']; ?><div><div><span><?= htmlspecialchars($r['label']) ?></span><b><?= $value ?></b></div><i><em style="width:<?= analyticsPct($value,$courseMax) ?>%"></em></i></div><?php endforeach; ?><?php if (!$courseRows): ?><p class="analytics-empty">No enrollment data available.</p><?php endif; ?></div></article>
      <article class="analytics-surface"><div class="analytics-surface-head"><div><span>Student success</span><h2>Needs attention</h2><p>Students meeting current attendance-risk thresholds.</p></div><a href="?page=students" class="analytics-link">View all →</a></div><div class="analytics-risk-list">
      <?php foreach ($studentRisk as $r): ?><a href="?page=student_profile&amp;st_id=<?= (int)$r['st_id'] ?>"><i><?= htmlspecialchars(strtoupper(substr((string)$r['name'],0,1))) ?></i><span><b><?= htmlspecialchars($r['name']) ?></b><small><?= htmlspecialchars($r['section']) ?> · <?= (int)$r['absent'] ?> absent · <?= (int)$r['late'] ?> late</small></span><strong><?= htmlspecialchars($r['rate']) ?>%</strong></a><?php endforeach; ?><?php if (!$studentRisk): ?><div class="analytics-empty success"><i class="bi bi-check-circle"></i><b>No current risk flags</b><span>Students are above the configured review thresholds.</span></div><?php endif; ?></div></article>
    </div>
  </div>

  <div class="analytics-tab-panel" data-panel="attendance" hidden>
    <div class="analytics-section-intro"><span>Attendance intelligence</span><h2>Where attendance is changing</h2><p>Use trend, status mix, and section activity together to identify classes that need follow-up.</p></div>
    <div class="analytics-grid analytics-grid-two">
      <article class="analytics-surface"><div class="analytics-surface-head"><div><span>Section comparison</span><h2>Attendance records by section</h2></div><a href="?page=view_attendance_v2" class="analytics-link">Open report →</a></div><div class="analytics-bars">
      <?php $sectionMax=max(1,...array_map(fn($r)=>(int)$r['total'],$sectionRows)); foreach($sectionRows as $r): ?><div><div><span><?= htmlspecialchars($r['label']) ?></span><b><?= (int)$r['total'] ?></b></div><i><em style="width:<?= analyticsPct((int)$r['total'],$sectionMax) ?>%"></em></i></div><?php endforeach; ?><?php if(!$sectionRows): ?><p class="analytics-empty">No section attendance data.</p><?php endif; ?></div></article>
      <article class="analytics-surface"><div class="analytics-surface-head"><div><span>Attendance health</span><h2>Current status</h2></div></div><div class="analytics-health"><div><strong><?= number_format($presentRate,1) ?>%</strong><span>Present</span></div><div><strong><?= number_format($lateRate,1) ?>%</strong><span>Late</span></div><div><strong><?= number_format($absenceRate,1) ?>%</strong><span>Absent</span></div></div><p class="analytics-note">These rates respond to the selected term and academic-year scope.</p></article>
    </div>
  </div>

  <div class="analytics-tab-panel" data-panel="academics" hidden>
    <div class="analytics-section-intro"><span>Academic intelligence</span><h2>How students are performing</h2><p>Compare subject averages and identify where academic support may be useful.</p></div>
    <div class="analytics-grid analytics-grid-two">
      <article class="analytics-surface"><div class="analytics-surface-head"><div><span>Subject performance</span><h2>Average grade by subject</h2></div><a href="?page=grades" class="analytics-link">Open grades →</a></div><div class="analytics-bars grade-bars">
      <?php foreach($gradeRows as $r): ?><div><div><span><?= htmlspecialchars($r['label']) ?></span><b><?= number_format((float)$r['average'],1) ?></b></div><i><em style="width:<?= analyticsPct((float)$r['average'],100) ?>%"></em></i></div><?php endforeach; ?><?php if(!$gradeRows): ?><p class="analytics-empty">No grade data available yet.</p><?php endif; ?></div></article>
      <article class="analytics-surface"><div class="analytics-surface-head"><div><span>Student composition</span><h2>Population profile</h2></div></div><div class="analytics-composition"><div><h3>Year level</h3><?php foreach($yearRows as $r): ?><p><span><?= htmlspecialchars($r['label']) ?></span><b><?= (int)$r['total'] ?></b></p><?php endforeach; ?></div><div><h3>Gender</h3><?php foreach($genderRows as $r): ?><p><span><?= htmlspecialchars($r['label']) ?></span><b><?= (int)$r['total'] ?></b></p><?php endforeach; ?></div></div></article>
    </div>
  </div>

  <div class="analytics-tab-panel" data-panel="students" hidden>
    <div class="analytics-section-intro"><span>Students &amp; operations</span><h2>People and academic operations</h2><p>See where instructional capacity and student activity are concentrated.</p></div>
    <div class="analytics-grid analytics-grid-two">
      <article class="analytics-surface"><div class="analytics-surface-head"><div><span>Instructor activity</span><h2>Teaching activity</h2></div><a href="?page=teaching_loads" class="analytics-link">Teaching loads →</a></div><div class="analytics-table-wrap"><table class="analytics-table"><thead><tr><th>Instructor</th><th>Loads</th><th>Sections</th><th>Sessions</th></tr></thead><tbody><?php foreach($instructorRows as $r): ?><tr><td><?= htmlspecialchars($r['label']) ?></td><td><?= (int)$r['loads'] ?></td><td><?= (int)$r['sections'] ?></td><td><?= (int)$r['submitted_sessions'] ?></td></tr><?php endforeach; ?></tbody></table></div></article>
      <article class="analytics-surface"><div class="analytics-surface-head"><div><span>Class coverage</span><h2>Largest classes</h2></div></div><div class="analytics-table-wrap"><table class="analytics-table"><thead><tr><th>Class</th><th>Instructor</th><th>Students</th><th>Days</th></tr></thead><tbody><?php foreach($loadRows as $r): ?><tr><td><?= htmlspecialchars($r['label']) ?></td><td><?= htmlspecialchars($r['instructor']) ?></td><td><?= (int)$r['students'] ?></td><td><?= (int)$r['attendance_days'] ?></td></tr><?php endforeach; ?></tbody></table></div></article>
    </div>
  </div>

  <footer class="analytics-insight"><i class="bi bi-lightbulb"></i><span><b>Recommended workflow</b><small>Start with Overview, use Attendance to identify changes, then drill into Student Profile or Grades for action.</small></span><a href="?page=view_attendance_v2" class="btn btn-sm btn-primary">Review attendance</a></footer>
</section>
