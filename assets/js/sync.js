// RC3.3 synchronization dashboard. All synchronization work is performed by TalaEngine APIs.
(function () {
  const API_ROOT = 'admin/tala/api/';
  let busy = false;

  const $ = (id) => document.getElementById(id);

  function setText(id, value) {
    const element = $(id);
    if (element) element.textContent = value;
  }

  function setClass(id, value) {
    const element = $(id);
    if (element) element.className = value;
  }

  function setHtml(id, value) {
    const element = $(id);
    if (element) element.innerHTML = value;
  }

  function toggleClass(id, className, force) {
    const element = $(id);
    if (element) element.classList.toggle(className, force);
  }

  function setBusy(value, label) {
    busy = value;
    ['btnAnalyzeSchema', 'btnExecuteSync', 'btnRefreshDashboard'].forEach((id) => {
      const button = $(id);
      if (button) button.disabled = value || (id === 'btnExecuteSync' && !plan.length);
    });
    const loading = $('syncLoading');
    if (loading) loading.classList.toggle('d-none', !value);
    if (label) setText('syncLoadingText', label);
  }

  function showAlert(message, type) {
    const alert = $('syncAlert');
    if (!alert) return;
    alert.textContent = message;
    alert.className = `alert alert-${type} mb-3`;
  }

  function clearAlert() {
    setClass('syncAlert', 'alert d-none');
    setText('syncAlert', '');
  }

  async function request(endpoint) {
    const response = await fetch(API_ROOT + endpoint, { headers: { Accept: 'application/json' } });
    const payload = await response.json();
    if (!response.ok || !payload.success) throw new Error(payload.message || 'The synchronization request failed.');
    return payload.data || {};
  }

  let plan = [];

  function statusText(connected) { return connected ? 'Connected' : 'Disconnected'; }

  function updateStatus(data) {
    setText('sourceStatus', statusText(data.source_connection));
    setClass('sourceStatus', `stat-value fs-5 ${data.source_connection ? 'text-success' : 'text-danger'}`);
    setText('destinationStatus', statusText(data.destination_connection));
    setClass('destinationStatus', `stat-value fs-5 ${data.destination_connection ? 'text-success' : 'text-danger'}`);
    const count = Number(data.pending_operations || 0);
    setText('syncStatusValue', count ? 'Changes Detected' : 'Up to Date');
    setText('pendingCount', `${count} pending operation${count === 1 ? '' : 's'}`);
    setText('lastAnalysisDuration', data.duration_ms != null ? `${Number(data.duration_ms).toFixed(2)} ms` : '-');
  }

  function severity(operation) {
    const name = String(operation || '').toUpperCase();
    if (name === 'DROP_TABLE') return ['Danger', 'text-bg-danger'];
    if (name === 'DROP_COLUMN') return ['High', 'text-bg-warning'];
    if (name === 'MODIFY_COLUMN') return ['Medium', 'text-bg-info'];
    return [name === 'CREATE_TABLE' ? 'Low' : 'Info', 'text-bg-primary'];
  }

  function renderPlan(operations) {
    plan = Array.isArray(operations) ? operations : [];
    setText('planCount', `${plan.length} operation${plan.length === 1 ? '' : 's'}`);
    toggleClass('planEmpty', 'd-none', plan.length > 0);
    toggleClass('planTableWrap', 'd-none', plan.length === 0);
    const executeButton = $('btnExecuteSync');
    if (executeButton) executeButton.disabled = busy || plan.length === 0;
    const body = $('planTableBody');
    if (!body) return;
    body.replaceChildren();
    plan.forEach((operation, index) => {
      const details = operation.details || {};
      const [severityText, severityClass] = severity(operation.operation);
      const row = document.createElement('tr');
      const sql = operation.sql || 'SQL will be generated during execution.';
      row.innerHTML = `<td><span class="badge text-bg-secondary">${operation.operation || '-'}</span></td>`
        + `<td><code>${details.table || operation.target || '-'}</code>${details.column ? `<div class="small text-muted">${details.column}</div>` : ''}</td>`
        + `<td><span class="badge ${severityClass}">${severityText}</span></td>`
        + `<td>${operation.reason || 'Schema difference detected.'}</td>`
        + `<td><span class="badge text-bg-light">${operation.status || 'Pending'}</span></td>`
        + `<td><details><summary class="text-primary" role="button">Preview</summary><pre class="small mt-2 mb-0"><code>${sql}</code></pre></details></td>`;
      body.appendChild(row);
    });
  }

  async function refresh() {
    if (busy) return;
    clearAlert();
    setBusy(true, 'Refreshing synchronization status...');
    try { updateStatus(await request('status.php')); }
    catch (error) { showAlert(error.message, 'danger'); }
    finally { setBusy(false); }
  }

  async function analyze() {
    if (busy) return;
    clearAlert();
    setBusy(true, 'Analyzing database schemas...');
    try {
      const data = await request('analyze.php');
      plan = data.operations || [];
      renderPlan(plan);
      setText('lastAnalysisTime', new Date().toLocaleString());
      setText('lastAnalysisDuration', `${Number(data.summary?.duration_ms || 0).toFixed(2)} ms`);
      if (data.errors?.length) showAlert(data.errors.join(' '), 'warning');
      else if (!plan.length) showAlert('Database schemas are synchronized.', 'success');
    } catch (error) { showAlert(error.message, 'danger'); }
    finally { setBusy(false); }
  }

  async function execute() {
    if (busy || !plan.length) return;
    clearAlert();
    setBusy(true, 'Executing synchronization plan...');
    try {
      const data = await request('execute.php');
      const remaining = Number(data.remaining_operations || 0);
      toggleClass('executionSummary', 'd-none', false);
      setHtml('executionSummaryBody', [['Executed', data.executed], ['Skipped', data.skipped], ['Failed', data.failed], ['Remaining', remaining]]
        .map(([label, value]) => `<div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">${label}</div><div class="stat-value fs-4">${Number(value || 0)}</div></div></div>`).join(''));
      if (remaining === 0) { renderPlan([]); showAlert('Synchronization completed successfully. No operations remain.', 'success'); }
      else { showAlert(`${remaining} operation${remaining === 1 ? '' : 's'} remain after execution.`, 'warning'); }
    } catch (error) { showAlert(error.message, 'danger'); }
    finally { setBusy(false); }
  }

  function init() {
    $('btnAnalyzeSchema')?.addEventListener('click', analyze);
    $('btnExecuteSync')?.addEventListener('click', execute);
    $('btnRefreshDashboard')?.addEventListener('click', refresh);
    refresh();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
