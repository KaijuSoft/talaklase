// RC3.5 synchronization dashboard UI only. Synchronization logic remains in the API layer.
(function () {
  const API_ROOT = 'admin/tala/api/';
  let busy = false;
  let restoreModal = null;

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

  function fmtNumber(value, fallback = '0') {
    const number = Number(value);
    return Number.isFinite(number) ? String(number) : fallback;
  }

  function fmtSeconds(value) {
    const number = Number(value);
    return Number.isFinite(number) ? `${number.toFixed(2)}s` : '0.00s';
  }

  function setBusy(value, label) {
    busy = value;
    ['btnAnalyzeSchema', 'btnExecuteSync', 'btnRefreshDashboard', 'btnRestoreOnline', 'btnConfirmRestoreOnline'].forEach((id) => {
      const button = $(id);
      if (button) button.disabled = value;
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

  const stageLabels = {
    analyze_schema: 'Analyze Schema',
    apply_schema: 'Apply Schema',
    analyze_data: 'Analyze Data',
    synchronize_data: 'Synchronize Data',
    verification: 'Verification',
  };

  const stageOrder = Object.keys(stageLabels);

  function stageState(stage) {
    if (!stage) return { label: 'Pending', className: 'text-bg-light', icon: 'bi-dash-circle' };
    if (stage.failed || stage.success === false) return { label: 'Failed', className: 'text-bg-danger', icon: 'bi-x-circle' };
    if (stage.completed) return { label: 'Completed', className: 'text-bg-success', icon: 'bi-check-circle' };
    if (stage.started) return { label: 'Running', className: 'text-bg-primary', icon: 'bi-arrow-repeat' };
    return { label: 'Pending', className: 'text-bg-light', icon: 'bi-dash-circle' };
  }

  function renderTimeline(stages) {
    const list = $('timelineList');
    const empty = $('timelineEmpty');
    if (!list || !empty) return;
    list.replaceChildren();

    const hasStages = stageOrder.some((key) => stages && stages[key]);
    empty.classList.toggle('d-none', hasStages);
    list.classList.toggle('d-none', !hasStages);
    if (!hasStages) return;

    stageOrder.forEach((key, index) => {
      const stage = stages?.[key] || null;
      const status = stageState(stage);
      const item = document.createElement('div');
      item.className = `sync-timeline__item sync-timeline__item--${status.label.toLowerCase()}`;
      item.innerHTML = `
        <div class="sync-timeline__marker">
          <i class="bi ${status.icon}" aria-hidden="true"></i>
        </div>
        <div class="sync-timeline__content">
          <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <div>
              <div class="sync-timeline__title">${stageLabels[key]}</div>
              <div class="sync-timeline__meta">${fmtSeconds(stage?.duration_ms)}</div>
            </div>
            <span class="badge ${status.className}">${status.label}</span>
          </div>
          ${stage?.warnings?.length ? `<div class="sync-timeline__note text-warning mt-2"><i class="bi bi-exclamation-triangle me-1"></i>${stage.warnings.join(' ')}</div>` : ''}
          ${stage?.failures?.length ? `<div class="sync-timeline__note text-danger mt-2"><i class="bi bi-x-circle me-1"></i>${stage.failures.join(' ')}</div>` : ''}
        </div>
      `;
      list.appendChild(item);
    });

    const allDone = stageOrder.every((key) => stages?.[key]?.completed);
    setText('timelineState', allDone ? 'Completed' : 'Active');
    setClass('timelineState', `badge ${allDone ? 'text-bg-success' : 'text-bg-primary'}`);
  }

  function renderSchemaSummary(schema = {}) {
    const created = Array.isArray(schema.tables_created) ? schema.tables_created.length : 0;
    const altered = Array.isArray(schema.tables_altered) ? schema.tables_altered.length : 0;
    const indexes = Array.isArray(schema.indexes) ? schema.indexes.length : Number(schema.indexes || 0);
    const foreignKeys = Array.isArray(schema.foreign_keys) ? schema.foreign_keys.length : Number(schema.foreign_keys || 0);

    setText('schemaTablesCreated', fmtNumber(created));
    setText('schemaTablesAltered', fmtNumber(altered));
    setText('schemaIndexes', fmtNumber(indexes));
    setText('schemaForeignKeys', fmtNumber(foreignKeys));
  }

  function renderDataSummary(data = {}) {
    const students = data.students || {};
    const tableName = students.table || 'Students';
    const remoteCount = students.remote_records ?? students.remote_count ?? students.remote_student_records ?? 0;
    const localCount = students.local_after ?? students.local_count ?? students.local_student_records ?? students.local_before ?? 0;
    const inserted = students.inserted ?? 0;
    const updated = students.updated ?? students.modified ?? 0;
    const skipped = students.skipped ?? 0;
    const failed = students.failed ?? 0;

    setText('dataTableName', tableName);
    setText('dataRemoteCount', fmtNumber(remoteCount));
    setText('dataLocalCount', fmtNumber(localCount));
    setText('dataInsertedUpdated', `${fmtNumber(inserted)} / ${fmtNumber(updated)}`);
    setText('dataSkippedFailed', `${fmtNumber(skipped)} / ${fmtNumber(failed)}`);
  }

  function renderOverall(execution = {}, data = {}, warnings = [], errors = []) {
    setText('overallExecutionTime', fmtSeconds(execution.duration_ms ?? data.duration_ms ?? 0));
    setText('overallWarnings', fmtNumber(warnings.length));
    setText('overallErrors', fmtNumber(errors.length));
  }

  function renderWarnings(warnings = [], data = {}) {
    const container = $('syncWarnings');
    if (!container) return;

    const tableWarnings = [];
    const tables = Array.isArray(data.tables) ? data.tables : [];
    tables.forEach((table) => {
      const online = Number(table.online ?? table.remote ?? table.remote_count ?? 0);
      const local = Number(table.local ?? table.local_count ?? 0);
      if (Number.isFinite(online) && Number.isFinite(local) && online !== local) {
        tableWarnings.push({ name: table.name || table.table || 'Unknown', online, local, difference: Math.abs(online - local) });
      }
    });

    const hasWarnings = warnings.length > 0 || tableWarnings.length > 0;
    container.classList.toggle('d-none', !hasWarnings);
    if (!hasWarnings) {
      container.replaceChildren();
      return;
    }

    container.innerHTML = '';

    if (warnings.length) {
      const block = document.createElement('div');
      block.className = 'alert alert-warning';
      block.innerHTML = `<div class="fw-700 mb-1">Warnings</div><div>${warnings.join('<br>')}</div>`;
      container.appendChild(block);
    }

    tableWarnings.forEach((item) => {
      const card = document.createElement('div');
      card.className = 'card border-warning-subtle mb-2';
      card.innerHTML = `
        <div class="card-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="fw-700">${item.name}</div>
            <span class="badge text-bg-warning text-dark">Difference ${item.difference}</span>
          </div>
          <div class="sync-compare-grid mt-3">
            <div><span>Online</span><strong>${item.online}</strong></div>
            <div class="sync-compare-grid__arrow">↓</div>
            <div><span>Local</span><strong>${item.local}</strong></div>
          </div>
        </div>
      `;
      container.appendChild(card);
    });
  }

  function renderSuccessState(execution = {}, stages = {}) {
    const box = $('executionSuccessState');
    if (!box) return;
    const allComplete = stageOrder.every((key) => stages?.[key]?.completed);
    const success = allComplete && Number(execution.failed || 0) === 0;
    box.classList.toggle('d-none', !success);
    if (!success) {
      box.replaceChildren();
      return;
    }

    const duration = fmtSeconds(execution.duration_ms || 0);
    box.innerHTML = `
      <div class="sync-success-state__icon text-success"><i class="bi bi-check2-circle"></i></div>
      <div>
        <div class="sync-success-state__title">Synchronization Completed Successfully</div>
        <div class="text-muted">Schema verification completed.</div>
        <div class="text-muted">Data synchronization completed.</div>
        <div class="text-muted">Verification completed.</div>
        <div class="text-muted mt-2">Completed in ${duration}.</div>
      </div>
    `;
  }

  function renderSummaryCards(data = {}) {
    const execution = data.execution || {};
    const schema = data.schema || {};
    const student = data.data?.students || {};
    renderSchemaSummary(schema);
    renderDataSummary(data.data || {});
    const warnings = data.warnings || [];
    const errors = data.errors || [];
    renderOverall(execution, data, warnings, errors);
    renderWarnings(warnings, data.data || {});
    renderSuccessState(execution, data.stages || {});
    renderTimeline(data.stages || {});
    toggleClass('executionSummary', 'd-none', false);
    setHtml('executionSummaryBody', [
      ['Execution Time', fmtSeconds(execution.duration_ms || 0)],
      ['Warnings', fmtNumber(warnings.length)],
      ['Errors', fmtNumber(errors.length)],
      ['Direction', (data.data?.direction === 'online_to_local' ? 'ONLINE → LOCAL' : 'ONLINE → LOCAL')],
    ].map(([label, value]) => `<div class="col-6 col-lg-3"><div class="stat-card h-100"><div class="stat-label">${label}</div><div class="stat-value fs-4">${value}</div></div></div>`).join(''));

    if (student.failure_reasons?.length) {
      showAlert(student.failure_reasons.join(' '), 'warning');
    }
  }

  async function refresh() {
    if (busy) return;
    clearAlert();
    setBusy(true, 'Refreshing synchronization status...');
    try {
      const data = await request('status.php');
      setText('sourceStatus', data.source_connection ? 'Connected' : 'Disconnected');
      setClass('sourceStatus', `stat-value fs-5 ${data.source_connection ? 'text-success' : 'text-danger'}`);
      setText('destinationStatus', data.destination_connection ? 'Connected' : 'Disconnected');
      setClass('destinationStatus', `stat-value fs-5 ${data.destination_connection ? 'text-success' : 'text-danger'}`);
      setText('timelineState', 'Idle');
      setClass('timelineState', 'badge text-bg-light');
    } catch (error) {
      showAlert(error.message, 'danger');
    } finally {
      setBusy(false);
    }
  }

  async function analyze() {
    if (busy) return;
    clearAlert();
    setBusy(true, 'Analyzing database schemas...');
    try {
      const data = await request('analyze.php');
      renderTimeline(data.stages || {});
      renderSchemaSummary(data.schema || {});
      renderOverall(data.execution || {}, data, data.warnings || [], data.errors || []);
      renderWarnings(data.warnings || [], data.data || {});
      if (data.errors?.length) {
        showAlert(data.errors.join(' '), 'warning');
      } else if (!data.schema?.tables_created?.length && !data.schema?.tables_altered?.length) {
        showAlert('Database schemas are synchronized.', 'success');
      }
    } catch (error) {
      showAlert(error.message, 'danger');
    } finally {
      setBusy(false);
    }
  }

  async function execute() {
    if (busy) return;
    clearAlert();
    setBusy(true, 'Executing synchronization plan...');
    try {
      const data = await request('execute.php');
      renderSummaryCards(data);
      if (data.errors?.length) {
        showAlert(data.errors.join(' '), 'danger');
      } else if ((data.data?.students?.local_after ?? data.data?.students?.local_count ?? 0) !== (data.data?.students?.remote_records ?? data.data?.students?.remote_count ?? 0)) {
        showAlert('Synchronization completed, but differences remain in one or more tables.', 'warning');
      } else {
        showAlert('Synchronization completed successfully.', 'success');
      }
    } catch (error) {
      showAlert(error.message, 'danger');
    } finally {
      setBusy(false);
    }
  }

  async function restoreOnline() {
    if (busy) return;
    restoreModal?.show();
  }

  async function confirmRestoreOnline() {
    if (busy) return;
    clearAlert();
    setBusy(true, 'Restoring the online database from local data...');
    try {
      const response = await fetch('sync_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'push_to_online' }),
      });
      const text = await response.text();
      if (!response.ok || text.includes('"type":"error"')) {
        throw new Error('The online database could not be restored.');
      }
      showAlert('Online Database restored successfully from the local copy.', 'success');
      restoreModal?.hide();
      setBusy(false);
      await refresh();
    } catch (error) {
      showAlert(error.message, 'danger');
    } finally {
      if (busy) setBusy(false);
    }
  }

  function init() {
    restoreModal = window.bootstrap ? new bootstrap.Modal($('restoreOnlineModal')) : null;
    $('btnAnalyzeSchema')?.addEventListener('click', analyze);
    $('btnExecuteSync')?.addEventListener('click', execute);
    $('btnRefreshDashboard')?.addEventListener('click', refresh);
    $('btnRestoreOnline')?.addEventListener('click', restoreOnline);
    $('btnConfirmRestoreOnline')?.addEventListener('click', confirmRestoreOnline);
    refresh();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
