<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_permission('sync_settings');
?>

<section class="mb-4" aria-labelledby="sync-dashboard-title">
  <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
    <div>
      <div class="text-muted small">Administration</div>
      <h1 id="sync-dashboard-title" class="h4 mb-1">Database Synchronization</h1>
      <p class="text-muted mb-0">Review schema changes before applying them.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-outline-primary" id="btnAnalyzeSchema" type="button">
        <i class="bi bi-search me-1" aria-hidden="true"></i>Analyze Schema
      </button>
      <button class="btn btn-primary" id="btnExecuteSync" type="button" disabled>
        <i class="bi bi-play-fill me-1" aria-hidden="true"></i>Execute Synchronization
      </button>
      <button class="btn btn-outline-secondary" id="btnRefreshDashboard" type="button">
        <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh
      </button>
    </div>
  </div>

  <div id="syncAlert" class="alert d-none" role="alert"></div>
  <div id="syncLoading" class="d-none text-muted mb-3" aria-live="polite">
    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
    <span id="syncLoadingText">Working...</span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="stat-card h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-database" aria-hidden="true"></i></div>
          <div><div class="stat-label">Source Database</div><div id="sourceStatus" class="stat-value fs-5">Checking</div></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="stat-card h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-cloud" aria-hidden="true"></i></div>
          <div><div class="stat-label">Destination Database</div><div id="destinationStatus" class="stat-value fs-5">Checking</div></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="stat-card h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></div>
          <div><div class="stat-label">Synchronization Status</div><div id="syncStatusValue" class="stat-value fs-5">Checking</div><div id="pendingCount" class="text-muted small">- pending</div></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="stat-card h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-stopwatch" aria-hidden="true"></i></div>
          <div><div class="stat-label">Last Analysis</div><div id="lastAnalysisTime" class="stat-value fs-6">-</div><div id="lastAnalysisDuration" class="text-muted small">-</div></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h2 class="h6 mb-0"><i class="bi bi-list-check me-2 text-primary" aria-hidden="true"></i>Execution Plan</h2>
      <span id="planCount" class="badge text-bg-light">0 operations</span>
    </div>
    <div class="card-body p-0">
      <div id="planEmpty" class="p-4 text-center text-muted">
        <i class="bi bi-check-circle text-success fs-3 d-block mb-2" aria-hidden="true"></i>
        <div>Database schemas are synchronized.</div>
        <small>No pending operations.</small>
      </div>
      <div id="planTableWrap" class="table-responsive d-none">
        <table class="table align-middle mb-0">
          <thead><tr><th>Operation</th><th>Target</th><th>Severity</th><th>Reason</th><th>Status</th><th>SQL</th></tr></thead>
          <tbody id="planTableBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="executionSummary" class="card mt-3 d-none" aria-live="polite">
    <div class="card-header"><h2 class="h6 mb-0">Execution Result</h2></div>
    <div class="card-body"><div class="row g-3" id="executionSummaryBody"></div></div>
  </div>
</section>

<script src="assets/js/sync.js"></script>
