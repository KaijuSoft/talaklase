<?php
// sync.php — display only, all logic is in sync_api.php
// This file is included by index.php so no POST handling here
?>

<div class="card mb-3">
  <div class="card-header">
    <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Database Sync</h6>
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
      <button class="btn btn-outline-primary" onclick="checkNewer()">
        <i class="bi bi-search me-1"></i> Check Which is Newer
      </button>
      <button class="btn btn-primary" onclick="startSync('push_to_online')" id="btnPushOnline" disabled>
        <i class="bi bi-cloud-upload-fill me-1"></i> Push Local → Online
      </button>
      <button class="btn btn-success" onclick="startSync('push_to_local')" id="btnPushLocal" disabled>
        <i class="bi bi-download me-1"></i> Push Online → Local
      </button>
      <button class="btn btn-outline-secondary" onclick="checkStatus()">
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
    </div>

  </div>
</div>

<script>
const API = 'sync_api.php';

window.addEventListener('DOMContentLoaded', checkStatus);

function checkStatus() {
  document.getElementById('onlineStatus').textContent = 'Checking…';
  document.getElementById('localStatus').textContent  = 'Checking…';

  fetch(API, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'check_status'})
  })
  .then(r => r.json())
  .then(data => {
    // Online
    document.getElementById('onlineStatus').textContent = data.online ? 'Connected' : 'Unavailable';
    document.getElementById('onlineStatus').className   = 'fw-bold ' + (data.online ? 'text-success' : 'text-danger');
    document.getElementById('onlineDot').style.background = data.online ? '#198754' : '#dc3545';
    document.getElementById('onlineTime').textContent   = data.online_time ? 'Last sync: ' + data.online_time : '';

    // Local
    document.getElementById('localStatus').textContent  = data.local ? 'Connected' : 'Unavailable';
    document.getElementById('localStatus').className    = 'fw-bold ' + (data.local ? 'text-success' : 'text-danger');
    document.getElementById('localDot').style.background = data.local ? '#198754' : '#dc3545';
    document.getElementById('localTime').textContent    = data.local_time ? 'Last sync: ' + data.local_time : '';

    // Only enable sync buttons if BOTH DBs are available
    const bothAvail = data.online && data.local;
    document.getElementById('btnPushOnline').disabled = !bothAvail;
    document.getElementById('btnPushLocal').disabled  = !bothAvail;
  })
  .catch(() => {
    document.getElementById('onlineStatus').textContent = 'Error';
    document.getElementById('localStatus').textContent  = 'Error';
  });
}

function checkNewer() {
  const banner = document.getElementById('detectBanner');
  banner.className = 'alert alert-info mb-4';
  banner.classList.remove('d-none');
  banner.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Checking timestamps…';

  fetch(API, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'check_newer'})
  })
  .then(r => r.json())
  .then(data => {
    const icons   = {in_sync:'✅', local_newer:'💻', online_newer:'☁️', error:'❌'};
    const classes = {in_sync:'alert-success', local_newer:'alert-warning', online_newer:'alert-info', error:'alert-danger'};
    banner.className = `alert ${classes[data.result] || 'alert-secondary'} mb-4`;
    banner.innerHTML = `${icons[data.result] || ''} ${data.message}`;
  });
}

function startSync(direction) {
  const msg = direction === 'push_to_online'
    ? 'Push Local → Online?\n\nThis will OVERWRITE all online data with local data.'
    : 'Push Online → Local?\n\nThis will OVERWRITE all local data with online data.';

  if (!confirm(msg)) return;

  // Show progress panel
  const panel = document.getElementById('syncPanel');
  panel.classList.remove('d-none');
  document.getElementById('syncLog').innerHTML = '';
  document.getElementById('syncProgress').style.width = '0%';
  document.getElementById('syncProgress').textContent = '0%';
  document.getElementById('syncProgress').className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
  document.getElementById('syncCount').textContent  = '0 / 0 Tables';
  document.getElementById('syncStatus').textContent = 'Initializing…';
  document.getElementById('syncTable').textContent  = '—';
  document.getElementById('syncTitle').innerHTML    = '<i class="bi bi-arrow-repeat me-2 text-primary"></i>Syncing Database…';
  document.getElementById('btnPushOnline').disabled = true;
  document.getElementById('btnPushLocal').disabled  = true;

  // Fetch SSE stream from sync_api.php
  fetch(API, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action: direction})
  })
  .then(response => {
    const reader  = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    function read() {
      reader.read().then(({done, value}) => {
        if (done) return;
        buffer += decoder.decode(value, {stream: true});
        const lines = buffer.split('\n');
        buffer = lines.pop();
        lines.forEach(line => {
          if (line.startsWith('data: ')) {
            try {
              updateProgress(JSON.parse(line.slice(6)));
            } catch(e) {}
          }
        });
        read();
      });
    }
    read();
  })
  .catch(err => {
    showToast('Connection error: ' + err.message, 'danger');
    document.getElementById('btnPushOnline').disabled = false;
    document.getElementById('btnPushLocal').disabled  = false;
  });
}

function updateProgress(d) {
  const pct = d.total > 0 ? Math.round((d.current / d.total) * 100) : 0;

  document.getElementById('syncTable').textContent    = d.table    || '—';
  document.getElementById('syncStatus').textContent   = d.message  || '';
  document.getElementById('syncCount').textContent    = `${d.current} / ${d.total} Tables`;
  document.getElementById('syncProgress').style.width = pct + '%';
  document.getElementById('syncProgress').textContent = pct + '%';

  // Append to log
  const log = document.getElementById('syncLog');
  const ts  = new Date().toLocaleTimeString();
  log.innerHTML += `<div>[${ts}] ${d.message} — <em>${d.table}</em></div>`;
  log.scrollTop  = log.scrollHeight;

  if (d.type === 'done') {
    document.getElementById('syncProgress').className = 'progress-bar bg-success';
    document.getElementById('syncTitle').innerHTML = '<i class="bi bi-check-circle-fill me-2 text-success"></i>Sync Complete!';
    showToast('Sync completed successfully!', 'success');
    checkStatus(); // refresh status cards
    document.getElementById('btnPushOnline').disabled = false;
    document.getElementById('btnPushLocal').disabled  = false;
  }

  if (d.type === 'error') {
    document.getElementById('syncProgress').className = 'progress-bar bg-danger';
    document.getElementById('syncTitle').innerHTML = '<i class="bi bi-x-circle-fill me-2 text-danger"></i>Sync Failed!';
    showToast(d.message, 'danger');
    document.getElementById('btnPushOnline').disabled = false;
    document.getElementById('btnPushLocal').disabled  = false;
  }
}
</script>
