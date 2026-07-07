// TalaKlase sync page controller. All sync work still goes through sync_api.php.
(function () {
  const API = 'sync_api.php';

  function byId(id) {
    return document.getElementById(id);
  }

  function postAction(action) {
    return fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action })
    });
  }

  function setButtonState(disabled) {
    const btnPushOnline = byId('btnPushOnline');
    const btnPushLocal = byId('btnPushLocal');
    if (btnPushOnline) btnPushOnline.disabled = disabled;
    if (btnPushLocal) btnPushLocal.disabled = disabled;
  }

  function setText(id, value) {
    const el = byId(id);
    if (el) el.textContent = value;
  }

  function resetSummary() {
    const summaryCard = byId('summaryCard');
    if (summaryCard) summaryCard.classList.add('d-none');

    setText('sumDuration', '0 sec');
    setText('sumTables', '0');
    setText('sumInserted', '0');
    setText('sumSkipped', '0');
    setText('sumModified', '0');
    setText('sumFailed', '0');
    setText('sumConflicts', '0');
  }

  function resetProgressPanel() {
    byId('syncPanel').classList.remove('d-none');
    byId('syncLog').innerHTML = '';
    byId('syncProgress').style.width = '0%';
    byId('syncProgress').textContent = '0%';
    byId('syncProgress').className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
    byId('syncCount').textContent = '0 / 0 Tables';
    byId('syncStatus').textContent = 'Initializing.';
    byId('syncTable').textContent = '-';
    byId('syncTitle').innerHTML = '<i class="bi bi-arrow-repeat me-2 text-primary"></i>Syncing Database.';
    setButtonState(true);
    resetSummary();
  }

  function appendLog(message) {
    const log = byId('syncLog');
    const entry = document.createElement('div');
    entry.textContent = message;
    log.appendChild(entry);
    log.scrollTop = log.scrollHeight;
  }

  function showSummary(summary) {
    const summaryCard = byId('summaryCard');
    if (summaryCard) summaryCard.classList.remove('d-none');

    const tables = Array.isArray(summary.tables) ? summary.tables.length : (summary.tables || 0);
    const conflicts = Array.isArray(summary.conflicts) ? summary.conflicts.length : (summary.conflicts || 0);
    const duration = Number(summary.duration || 0).toFixed(3) + ' sec';

    setText('sumDuration', duration);
    setText('sumTables', tables);
    setText('sumInserted', summary.inserted || 0);
    setText('sumSkipped', summary.skipped || 0);
    setText('sumModified', summary.modified || 0);
    setText('sumFailed', summary.failed || 0);
    setText('sumConflicts', conflicts);
  }

  function checkStatus() {
    byId('onlineStatus').textContent = 'Checking.';
    byId('localStatus').textContent = 'Checking.';

    postAction('check_status')
      .then(r => r.json())
      .then(data => {
        byId('onlineStatus').textContent = data.online ? 'Connected' : 'Unavailable';
        byId('onlineStatus').className = 'fw-bold ' + (data.online ? 'text-success' : 'text-danger');
        byId('onlineDot').style.background = data.online ? '#198754' : '#dc3545';
        byId('onlineTime').textContent = data.online_time ? 'Last sync: ' + data.online_time : '';

        byId('localStatus').textContent = data.local ? 'Connected' : 'Unavailable';
        byId('localStatus').className = 'fw-bold ' + (data.local ? 'text-success' : 'text-danger');
        byId('localDot').style.background = data.local ? '#198754' : '#dc3545';
        byId('localTime').textContent = data.local_time ? 'Last sync: ' + data.local_time : '';

        setButtonState(!(data.online && data.local));
      })
      .catch(() => {
        byId('onlineStatus').textContent = 'Error';
        byId('localStatus').textContent = 'Error';
      });
  }

  function checkNewer() {
    const banner = byId('detectBanner');
    banner.className = 'alert alert-info mb-4';
    banner.classList.remove('d-none');
    banner.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Checking timestamps.';

    postAction('check_newer')
      .then(r => r.json())
      .then(data => {
        const icons = { in_sync: '?', local_newer: '??', online_newer: '??', error: '?' };
        const classes = {
          in_sync: 'alert-success',
          local_newer: 'alert-warning',
          online_newer: 'alert-info',
          error: 'alert-danger'
        };
        banner.className = `alert ${classes[data.result] || 'alert-secondary'} mb-4`;
        banner.innerHTML = `${icons[data.result] || ''} ${data.message}`;
      });
  }

  function startStream(action) {
    postAction(action)
      .then(response => {
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        function read() {
          reader.read().then(({ done, value }) => {
            if (done) return;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();

            lines.forEach(line => {
              if (line.startsWith('data: ')) {
                try {
                  updateProgress(JSON.parse(line.substring(6)));
                } catch (e) {
                  console.error(e);
                }
              }
            });

            read();
          });
        }

        read();
      })
      .catch(err => {
        showToast('Connection error: ' + err.message, 'danger');
        checkStatus();
      });
  }

  function startSync(direction) {
    const msg = direction === 'push_to_online'
      ? 'Push Local \u2192 Online?\n\nThis will OVERWRITE all online data with local data.'
      : 'Push Online \u2192 Local?\n\nThis will OVERWRITE all local data with online data.';

    if (!confirm(msg)) return;

    resetProgressPanel();
    startStream(direction);
  }

  function updateProgress(d) {
    if (d.type === 'progress') {
      const total = Number(d.total || 0);
      const current = Number(d.current || 0);
      const pct = total > 0 ? Math.round((current / total) * 100) : 0;

      byId('syncTable').textContent = d.table || '-';
      byId('syncCount').textContent = `${current} / ${total} Tables`;
      byId('syncStatus').textContent = d.message || 'Syncing.';
      byId('syncProgress').style.width = pct + '%';
      byId('syncProgress').textContent = pct + '%';
      appendLog(`[${d.table || '-'}] ${d.message || ''}`);
      return;
    }

    if (d.type === 'done') {
      byId('syncProgress').style.width = '100%';
      byId('syncProgress').textContent = '100%';
      byId('syncProgress').className = 'progress-bar bg-success';
      byId('syncTitle').innerHTML = '<i class="bi bi-check-circle-fill me-2 text-success"></i>Sync Complete!';
      byId('syncStatus').textContent = d.message || 'Synchronization completed.';
      appendLog(d.message || 'Synchronization completed.');
      showToast('Synchronization completed successfully!', 'success');
      checkStatus();
      setButtonState(false);
      return;
    }

    if (d.type === 'summary' && d.summary) {
      showSummary(d.summary);
      return;
    }

    if (d.type === 'error') {
      byId('syncProgress').className = 'progress-bar bg-danger';
      byId('syncTitle').innerHTML = '<i class="bi bi-x-circle-fill me-2 text-danger"></i>Sync Failed!';
      byId('syncStatus').textContent = d.message || 'Sync failed.';
      appendLog(d.message || 'Sync failed.');
      showToast(d.message || 'Sync failed.', 'danger');
      setButtonState(false);
    }
  }

  function startSmartMerge() {
    if (!confirm('Run Smart Merge?\n\nNo records will be deleted.')) {
      return;
    }

    resetProgressPanel();
    startStream('smart_merge');
  }

  function bindSyncControls() {
    const btnCheckNewer = byId('btnCheckNewer');
    const btnPushOnline = byId('btnPushOnline');
    const btnPushLocal = byId('btnPushLocal');
    const btnSmartMerge = byId('btnSmartMerge');
    const btnRefreshStatus = byId('btnRefreshStatus');

    if (btnCheckNewer) btnCheckNewer.addEventListener('click', checkNewer);
    if (btnPushOnline) btnPushOnline.addEventListener('click', () => startSync('push_to_online'));
    if (btnPushLocal) btnPushLocal.addEventListener('click', () => startSync('push_to_local'));
    if (btnSmartMerge) btnSmartMerge.addEventListener('click', startSmartMerge);
    if (btnRefreshStatus) btnRefreshStatus.addEventListener('click', checkStatus);
  }

  function initSyncPage() {
    bindSyncControls();
    checkStatus();
  }

  if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', initSyncPage);
  } else {
    initSyncPage();
  }
})();
