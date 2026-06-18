<?php
require_once __DIR__ . '/../includes/db.php';
<<<<<<< HEAD
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_score_settings');
=======
>>>>>>> dad965eae0886277347cae4c6fc181143c8fa104
$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // ── Load max scores ───────────────────────────────────────────────────────
    if ($action === 'load') {
        $term = $_POST['term'];
        $result = [];

        // Participation, Written, Performance
        foreach (['participation','written','performance'] as $comp) {
            $stmt = $pdo->prepare("SELECT one_max,two_max,three_max,four_max,five_max FROM score_settings WHERE term=? AND component=? LIMIT 1");
            $stmt->execute([$term,$comp]);
            $row = $stmt->fetch();
            $result[$comp] = $row ? array_values($row) : [0,0,0,0,0];
        }

        // Exam
        $stmt = $pdo->prepare("SELECT score_max FROM exam_settings WHERE term=? LIMIT 1");
        $stmt->execute([$term]);
        $row = $stmt->fetchColumn();
        $result['exam'] = $row !== false ? (int)$row : 0;

        echo json_encode($result);
        exit;
    }

    // ── Save max scores ───────────────────────────────────────────────────────
    if ($action === 'save') {
        $term = $_POST['term'];
        try {
            $pdo->beginTransaction();

            // Save Participation, Written, Performance
            foreach (['participation','written','performance'] as $comp) {
                $vals = [
                    $_POST["{$comp}_1"] ?? 0,
                    $_POST["{$comp}_2"] ?? 0,
                    $_POST["{$comp}_3"] ?? 0,
                    $_POST["{$comp}_4"] ?? 0,
                    $_POST["{$comp}_5"] ?? 0,
                ];
                $pdo->prepare("INSERT INTO score_settings (term,component,one_max,two_max,three_max,four_max,five_max)
                    VALUES (?,?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE one_max=VALUES(one_max),two_max=VALUES(two_max),
                    three_max=VALUES(three_max),four_max=VALUES(four_max),five_max=VALUES(five_max)")
                ->execute([$term,$comp,...$vals]);
            }

            // Save Exam
            $examMax = $_POST['exam_max'] ?? 0;
            $pdo->prepare("INSERT INTO exam_settings (term,score_max) VALUES (?,?)
                ON DUPLICATE KEY UPDATE score_max=VALUES(score_max)")
            ->execute([$term,$examMax]);

            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>"$term max scores saved successfully."]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }
}

$terms = [
    'Prelim'   => 'Prelim',
    'Midterm'  => 'Midterm',
    'PreFinal' => 'Pre-Finals',
    'Final'    => 'Finals',
];
?>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h6 class="mb-0"><i class="bi bi-gear-fill me-2 text-primary"></i>Score Settings</h6>
    <a href="?page=grades" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i> Back to Grading
    </a>
  </div>
  <div class="card-body">
    <p class="text-muted mb-4">Set the <strong>maximum scores</strong> for each component per term. These are used to validate grade entries and compute summary percentages.</p>

    <!-- Term Tabs -->
    <ul class="nav nav-tabs mb-0" id="ssTabs">
      <?php foreach ($terms as $key => $label): ?>
        <li class="nav-item">
          <a class="nav-link <?= $key==='Prelim'?'active':'' ?>" href="#"
            data-term="<?= $key ?>"
            onclick="loadTerm('<?= $key ?>',this);return false;">
            <?= $label ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="border border-top-0 p-4 rounded-bottom" id="ssBody">
      <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
    </div>

  </div>
</div>

<script>
let activeTerm = 'Prelim';

function loadTerm(term, el) {
  document.querySelectorAll('#ssTabs .nav-link').forEach(a=>a.classList.remove('active'));
  el.classList.add('active');
  activeTerm = term;

  document.getElementById('ssBody').innerHTML =
    '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'load', term})})
  .then(r=>r.json()).then(data => renderForm(term, data));
}

function renderForm(term, data) {
  const termLabels = {Prelim:'Prelim',Midterm:'Midterm',PreFinal:'Pre-Finals',Final:'Finals'};
  const lbl = termLabels[term] || term;
  const comps = [
    {key:'participation', label:'Participation', cols:5},
    {key:'written',       label:'Written',       cols:5},
    {key:'performance',   label:'Performance',   cols:5},
  ];

  let html = `<h6 class="text-primary mb-3"><i class="bi bi-pencil-square me-2"></i>Max Scores for ${lbl}</h6>`;

  comps.forEach(c => {
    const vals = data[c.key] || [0,0,0,0,0];
    html += `<div class="mb-4">
      <label class="form-label fw-semibold">${c.label} <small class="text-muted">(5 scores)</small></label>
      <div class="row g-2">`;
    for (let i=0;i<5;i++) {
      html += `<div class="col">
        <label class="form-label text-muted" style="font-size:0.75rem">${c.label} ${i+1}</label>
        <input type="number" class="form-control form-control-sm text-center"
          id="${c.key}_${i+1}" value="${vals[i]}" min="0"/>
      </div>`;
    }
    html += `</div></div>`;
  });

  // Exam
  html += `<div class="mb-4">
    <label class="form-label fw-semibold">Exam <small class="text-muted">(1 score)</small></label>
    <div class="row g-2">
      <div class="col-md-2">
        <label class="form-label text-muted" style="font-size:0.75rem">Max Score</label>
        <input type="number" class="form-control form-control-sm text-center" id="exam_max" value="${data.exam||0}" min="0"/>
      </div>
    </div>
  </div>`;

  html += `<hr/>
    <button class="btn btn-success" onclick="saveTerm('${term}')">
      <i class="bi bi-floppy-fill me-1"></i> Save ${lbl} Max Scores
    </button>`;

  document.getElementById('ssBody').innerHTML = html;
}

function saveTerm(term) {
  const termLabels = {Prelim:'Prelim',Midterm:'Midterm',PreFinal:'Pre-Finals',Final:'Finals'};
  if (!confirm(`Save max scores for ${termLabels[term]}?`)) return;

  const params = {action:'save', term};
  ['participation','written','performance'].forEach(comp => {
    for (let i=1;i<=5;i++) {
      params[`${comp}_${i}`] = document.getElementById(`${comp}_${i}`)?.value || 0;
    }
  });
  params['exam_max'] = document.getElementById('exam_max')?.value || 0;

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams(params)})
  .then(r=>r.json()).then(res => {
    showToast(res.message, res.success?'success':'danger');
  });
}

// Auto-load Prelim on page load
document.addEventListener('DOMContentLoaded', () => {
  loadTerm('Prelim', document.querySelector('#ssTabs .nav-link.active'));
});
</script>
