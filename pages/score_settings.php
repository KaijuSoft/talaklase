<?php
require_once __DIR__ . '/../includes/score_settings_controller.php';
?>
<div id="scoreSettingsPageConfig" data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"></div>
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
