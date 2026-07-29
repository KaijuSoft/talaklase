<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_permission('sync_settings');
?>

<section class="sync-dashboard" aria-labelledby="sync-dashboard-title">
  <div class="sync-hero card mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="sync-hero__copy">
          <div class="text-muted small text-uppercase fw-600 mb-2">Administration</div>
          <h1 id="sync-dashboard-title" class="h3 mb-2">Smart Synchronization</h1>
          <p class="sync-subtitle mb-0">
            Keeps the local TalaKlase installation synchronized with the live production database.
          </p>
        </div>
        <div class="sync-actions d-flex flex-wrap gap-2">
          <button class="btn btn-outline-primary" id="btnAnalyzeSchema" type="button">
            <i class="bi bi-search me-1" aria-hidden="true"></i>Analyze Schema
          </button>
          <button class="btn btn-primary" id="btnExecuteSync" type="button">
            <i class="bi bi-play-fill me-1" aria-hidden="true"></i>Execute Synchronization
          </button>
          <button class="btn btn-outline-warning" id="btnRestoreOnline" type="button">
            <i class="bi bi-cloud-upload me-1" aria-hidden="true"></i>Restore Online Database
          </button>
          <button class="btn btn-outline-secondary" id="btnRefreshDashboard" type="button">
            <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh
          </button>
        </div>
      </div>

      <div class="sync-direction mt-4">
        <div class="sync-direction__label">Synchronization Direction</div>
        <div class="sync-direction__flow">
          <div class="sync-direction__node">
            <i class="bi bi-globe2" aria-hidden="true"></i>
            <span>Online Database</span>
          </div>
          <i class="bi bi-arrow-down-short sync-direction__arrow" aria-hidden="true"></i>
          <div class="sync-direction__node">
            <i class="bi bi-laptop" aria-hidden="true"></i>
            <span>Local Database</span>
          </div>
        </div>
        <div class="sync-direction__badge badge text-bg-success mt-2">ONLINE → LOCAL</div>
      </div>
    </div>
  </div>

  <div id="syncAlert" class="alert d-none" role="alert"></div>
  <div id="syncLoading" class="d-none text-muted mb-3" aria-live="polite">
    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
    <span id="syncLoadingText">Working...</span>
  </div>

  <div class="sync-status-strip row g-3 mb-4">
    <div class="col-md-4">
      <div class="stat-card h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-globe2" aria-hidden="true"></i></div>
          <div>
            <div class="stat-label">Direction</div>
            <div class="stat-value fs-5">Online to Local</div>
            <div class="text-muted small">Keeps production and local data aligned.</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-cloud-download" aria-hidden="true"></i></div>
          <div>
            <div class="stat-label">Source</div>
            <div id="sourceStatus" class="stat-value fs-5">Checking</div>
            <div class="text-muted small">Online database status.</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-laptop" aria-hidden="true"></i></div>
          <div>
            <div class="stat-label">Destination</div>
            <div id="destinationStatus" class="stat-value fs-5">Checking</div>
            <div class="text-muted small">Local database status.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h2 class="h6 mb-0"><i class="bi bi-timeline me-2 text-primary" aria-hidden="true"></i>Execution Timeline</h2>
      <span id="timelineState" class="badge text-bg-light">Idle</span>
    </div>
    <div class="card-body">
      <div id="timelineEmpty" class="sync-empty-state text-center text-muted py-4">
        <i class="bi bi-clock-history text-primary fs-2 d-block mb-2" aria-hidden="true"></i>
        <div>Timeline will appear after execution.</div>
      </div>
      <div id="timelineList" class="sync-timeline d-none"></div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h2 class="h6 mb-0"><i class="bi bi-diagram-3 me-2 text-primary" aria-hidden="true"></i>Schema</h2>
        </div>
        <div class="card-body">
          <div class="sync-metric-grid">
            <div class="sync-metric"><span>Tables Created</span><strong id="schemaTablesCreated">0</strong></div>
            <div class="sync-metric"><span>Tables Altered</span><strong id="schemaTablesAltered">0</strong></div>
            <div class="sync-metric"><span>Indexes</span><strong id="schemaIndexes">0</strong></div>
            <div class="sync-metric"><span>Foreign Keys</span><strong id="schemaForeignKeys">0</strong></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h2 class="h6 mb-0"><i class="bi bi-people me-2 text-success" aria-hidden="true"></i>Data</h2>
        </div>
        <div class="card-body">
          <div class="sync-metric-grid">
            <div class="sync-metric"><span>Students</span><strong id="dataTableName">-</strong></div>
            <div class="sync-metric"><span>Remote Count</span><strong id="dataRemoteCount">0</strong></div>
            <div class="sync-metric"><span>Local Count</span><strong id="dataLocalCount">0</strong></div>
            <div class="sync-metric"><span>Inserted / Updated</span><strong id="dataInsertedUpdated">0 / 0</strong></div>
            <div class="sync-metric"><span>Skipped / Failed</span><strong id="dataSkippedFailed">0 / 0</strong></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h2 class="h6 mb-0"><i class="bi bi-speedometer2 me-2 text-warning" aria-hidden="true"></i>Overall</h2>
        </div>
        <div class="card-body">
          <div class="sync-metric-grid">
            <div class="sync-metric"><span>Execution Time</span><strong id="overallExecutionTime">0.00s</strong></div>
            <div class="sync-metric"><span>Warnings</span><strong id="overallWarnings">0</strong></div>
            <div class="sync-metric"><span>Errors</span><strong id="overallErrors">0</strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="syncWarnings" class="d-none mb-3" aria-live="polite"></div>

  <div id="executionSummary" class="card mt-3 d-none" aria-live="polite">
    <div class="card-header">
      <h2 class="h6 mb-0"><i class="bi bi-check2-circle me-2 text-success" aria-hidden="true"></i>Synchronization Result</h2>
    </div>
    <div class="card-body">
      <div id="executionSummaryBody" class="row g-3"></div>
      <div id="executionSuccessState" class="sync-success-state d-none mt-3"></div>
    </div>
  </div>

  <details class="sync-history card mt-3">
    <summary class="card-header">
      <h2 class="h6 mb-0"><i class="bi bi-journal-text me-2 text-primary" aria-hidden="true"></i>Synchronization History</h2>
    </summary>
    <div class="card-body text-muted">Coming in v1.0.4</div>
  </details>
</section>

<div class="modal fade" id="restoreOnlineModal" tabindex="-1" aria-labelledby="restoreOnlineModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="restoreOnlineModalLabel"><i class="bi bi-exclamation-triangle-fill me-2 text-warning" aria-hidden="true"></i>Restore Online Database</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="sync-restore-flow">
          <div class="sync-restore-flow__node">
            <div class="sync-restore-flow__label">Source</div>
            <div class="sync-restore-flow__value">💻 Local Database</div>
          </div>
          <div class="sync-restore-flow__arrow">↓</div>
          <div class="sync-restore-flow__node">
            <div class="sync-restore-flow__label">Destination</div>
            <div class="sync-restore-flow__value">🌐 Online Database</div>
          </div>
        </div>
        <p class="mt-3 mb-2">This operation will overwrite the online database.</p>
        <p class="text-muted mb-0">A backup is strongly recommended.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-warning text-white" id="btnConfirmRestoreOnline" type="button">
          <i class="bi bi-cloud-upload me-1" aria-hidden="true"></i>Continue Restore
        </button>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/sync.js"></script>
