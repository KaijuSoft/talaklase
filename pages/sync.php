<?php
// sync.php - display only, all logic is in sync_api.php
// This file is included by index.php so no POST handling here
require_once __DIR__ . '/../includes/auth.php';
require_permission('sync_settings');
?>

<div class="card mb-3">
  <div class="card-header">
    <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Smart Sync Status</h6>
  </div>
  <div class="card-body">

    <!-- Connection Status Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-5">
        <div class="stat-card">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-cloud-fill"></i></div>
            <div class="flex-fill">
              <div class="stat-label">Online Database</div>
              <div id="onlineStatus" class="fw-bold text-muted">Checking…</div>
              <div id="onlineTime" class="text-muted" style="font-size:0.75rem"></div>
            </div>
            <div id="onlineDot" class="rounded-circle" style="width:12px;height:12px;background:#ccc"></div>
          </div>
        </div>
      </div>
      <div class="col-md-2 d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-left-right fs-2 text-muted"></i>
      </div>
      <div class="col-md-5">
        <div class="stat-card">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-hdd-fill"></i></div>
            <div class="flex-fill">
              <div class="stat-label">Local Database</div>
              <div id="localStatus" class="fw-bold text-muted">Checking…</div>
              <div id="localTime" class="text-muted" style="font-size:0.75rem"></div>
            </div>
            <div id="localDot" class="rounded-circle" style="width:12px;height:12px;background:#ccc"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Auto-detect banner -->
    <div id="detectBanner" class="d-none alert mb-4"></div>

    <!-- Action buttons -->
    <div class="d-flex flex-wrap gap-3 mb-4">
      <button class="btn btn-outline-primary" id="btnCheckNewer" type="button">
        <i class="bi bi-search me-1"></i> Check Which is Newer
      </button>
      <button class="btn btn-primary" id="btnPushOnline" type="button" disabled>
        <i class="bi bi-cloud-upload-fill me-1"></i> Push Local → Online
      </button>
      <button class="btn btn-success" id="btnPushLocal" type="button" disabled>
        <i class="bi bi-download me-1"></i> Push Online → Local
      </button>
	  <button
    class="btn btn-warning"
    id="btnSmartMerge"
    type="button">

    <i class="bi bi-cpu-fill me-1"></i>

    Smart Merge (Beta)

</button>
      <button class="btn btn-outline-secondary" id="btnRefreshStatus" type="button">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Status
      </button>
    </div>

    <!-- Progress panel (mirrors VB.NET SyncForm) -->
    <div id="syncPanel" class="d-none border rounded p-4 bg-light">
      <h6 id="syncTitle" class="mb-3">
        <i class="bi bi-arrow-repeat me-2 text-primary"></i>Syncing Database…
      </h6>
      <div class="mb-1 d-flex justify-content-between">
        <span class="text-muted" style="font-size:0.82rem">Current Table:</span>
        <span id="syncTable" class="fw-semibold">—</span>
      </div>
      <div class="progress mb-2" style="height:22px">
        <div id="syncProgress" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
          role="progressbar" style="width:0%">0%</div>
      </div>
      <div class="d-flex justify-content-between mb-3">
        <span id="syncCount" class="text-muted" style="font-size:0.82rem">0 / 0 Tables</span>
        <span id="syncStatus" class="text-muted" style="font-size:0.82rem">Initializing…</span>
      </div>
      <!-- Log output — mirrors VB.NET WriteLog -->
      <div id="syncLog" class="border rounded bg-white p-2"
        style="max-height:200px;overflow-y:auto;font-size:0.78rem;font-family:monospace"></div>
		<hr class="my-3">

<div id="summaryCard" class="card border-success d-none">

    <div class="card-header bg-success text-white">

        <i class="bi bi-clipboard-check me-2"></i>

        Synchronization Summary

    </div>

    <div class="card-body">

       <div id="summaryContent" class="row g-3">

    <div class="col-md-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <small class="text-muted">Duration</small>
                <h5 id="sumDuration" class="mb-0">0 sec</h5>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <small class="text-muted">Tables</small>
                <h5 id="sumTables" class="mb-0">0</h5>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card h-100">
            <div class="card-body text-center">
                <small class="text-muted">Inserted</small>
                <h5 id="sumInserted" class="mb-0">0</h5>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card h-100">
            <div class="card-body text-center">
                <small class="text-muted">Skipped</small>
                <h5 id="sumSkipped" class="mb-0">0</h5>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card h-100">
            <div class="card-body text-center">
                <small class="text-muted">Modified</small>
                <h5 id="sumModified" class="mb-0">0</h5>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card h-100">
            <div class="card-body text-center">
                <small class="text-muted">Failed</small>
                <h5 id="sumFailed" class="mb-0">0</h5>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card h-100">
            <div class="card-body text-center">
                <small class="text-muted">Conflicts</small>
                <h5 id="sumConflicts" class="mb-0">0</h5>
            </div>
        </div>
    </div>

</div>

    </div>

</div>
    </div>

  </div>
</div>

<script src="assets/js/sync.js"></script>
