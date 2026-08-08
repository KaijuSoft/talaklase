(() => {
  const $ = (selector, root = document) => root.querySelector(selector);

  async function loadJson(url, options = {}) {
    const response = await fetch(url, { headers: { Accept: 'application/json' }, ...options });
    const text = await response.text();
    let data;
    try { data = JSON.parse(text); } catch { throw new Error(`Integrity endpoint returned invalid JSON (${response.status}).`); }
    if (!response.ok || !data.success) throw new Error(data.message || 'Unable to load integrity details.');
    return data;
  }

  function mount(hostId, html) {
    const host = document.getElementById(hostId);
    if (host) host.innerHTML = html;
  }

  async function loadDuplicates() {
    mount('duplicateDetailsHost', '<div class="text-muted">Loading duplicate details...</div>');
    try { const data = await loadJson('?page=database_integrity&action=duplicate_details'); mount('duplicateDetailsHost', data.html); bindInspectButtons(); bindMergeButtons(); }
    catch (error) { mount('duplicateDetailsHost', `<div class="alert alert-danger mb-0">${escapeHtml(error.message)}</div>`); }
  }

  async function loadOrphans() {
    mount('orphanDetailsHost', '<div class="text-muted">Loading orphan details...</div>');
    try { const data = await loadJson('?page=database_integrity&action=orphan_details'); mount('orphanDetailsHost', data.html); bindInspectButtons(); }
    catch (error) { mount('orphanDetailsHost', `<div class="alert alert-danger mb-0">${escapeHtml(error.message)}</div>`); }
  }

  async function loadInspector(studentId) {
    mount('referenceInspectorHost', '<div class="text-muted">Loading reference inspector...</div>');
    try {
      const data = await loadJson(`?page=database_integrity&action=reference_inspector&student_id=${encodeURIComponent(studentId)}`);
      mount('referenceInspectorHost', data.html);
      const closeButton = $('[data-integrity-close-inspector]');
      if (closeButton) closeButton.addEventListener('click', () => mount('referenceInspectorHost', '<div class="text-muted">Select a student from a detail panel to load the inspector.</div>'));
    } catch (error) { mount('referenceInspectorHost', `<div class="alert alert-danger mb-0">${escapeHtml(error.message)}</div>`); }
  }

  function escapeHtml(value) { return String(value).replace(/[&<>'"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' }[char])); }

  function bindInspectButtons() {
    document.querySelectorAll('[data-integrity-student-id]').forEach(button => {
      if (button.dataset.bound === '1') return;
      button.dataset.bound = '1';
      button.addEventListener('click', () => loadInspector(button.dataset.integrityStudentId));
    });
  }

  function bindMergeButtons() {
    document.querySelectorAll('[data-prepare-merge]').forEach(button => {
      if (button.dataset.bound === '1') return;
      button.dataset.bound = '1';
      button.addEventListener('click', () => openMergeModal(button.closest('[data-integrity-merge-row]')));
    });
  }

  function openMergeModal(row) {
    const modalElement = $('#studentMergeModal');
    if (!modalElement || !row || typeof bootstrap === 'undefined') return;
    const group = row.dataset.duplicateGroup;
    const rows = [...document.querySelectorAll('[data-integrity-merge-row]')].filter(item => item.dataset.duplicateGroup === group);
    const survivor = $('#mergeSurvivor');
    const duplicate = $('#mergeDuplicate');
    const options = rows.map(item => `<option value="${item.dataset.studentId}">${escapeHtml(item.dataset.studentLabel)}</option>`).join('');
    survivor.innerHTML = options;
    duplicate.innerHTML = options;
    duplicate.value = row.dataset.studentId;
    if (survivor.value === duplicate.value && rows.length > 1) survivor.value = rows.find(item => item.dataset.studentId !== duplicate.value)?.dataset.studentId || '';
    $('#mergeReason').value = '';
    $('#executeStudentMerge').disabled = true;
    loadMergePreview();
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
  }

  async function loadMergePreview() {
    const survivorId = $('#mergeSurvivor')?.value;
    const duplicateId = $('#mergeDuplicate')?.value;
    const host = $('#mergePreviewHost');
    const execute = $('#executeStudentMerge');
    if (!survivorId || !duplicateId || survivorId === duplicateId) { if (host) host.innerHTML = '<div class="alert alert-warning">Choose two different records.</div>'; if (execute) execute.disabled = true; return; }
    if (host) host.innerHTML = '<div class="text-muted">Checking references and field differences...</div>';
    try {
      const data = await loadJson(`?page=database_integrity&action=merge_preview&survivor_id=${encodeURIComponent(survivorId)}&duplicate_id=${encodeURIComponent(duplicateId)}`);
      const plan = data.plan;
      const diffs = Object.entries(plan.field_differences || {}).map(([field, values]) => `<tr><td><code>${escapeHtml(field)}</code></td><td>${escapeHtml(values.survivor ?? '')}</td><td>${escapeHtml(values.duplicate ?? '')}</td></tr>`).join('');
      const refs = Object.entries(plan.duplicate_references || {}).filter(([, count]) => Number(count) > 0).map(([table, count]) => `<tr><td><code>${escapeHtml(table)}</code></td><td>${count}</td></tr>`).join('');
      host.innerHTML = `<div class="card border-warning"><div class="card-body"><strong>Preflight result</strong><p class="small text-muted mb-2">Related references will be reassigned to the survivor. Student fields are not automatically combined.</p><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Field</th><th>Survivor</th><th>Duplicate</th></tr></thead><tbody>${diffs || '<tr><td colspan="3" class="text-muted">No student-field differences.</td></tr>'}</tbody></table></div><div class="mt-2"><strong>Duplicate references:</strong> ${refs ? `<table class="table table-sm mt-2"><tbody>${refs}</tbody></table>` : '<span class="text-muted"> none</span>'}</div></div></div>`;
      const hasDifferences = Object.keys(plan.field_differences || {}).length > 0;
      const confirmation = $('#mergeDifferenceConfirmation');
      const checkbox = $('#confirmMergeFieldDifferences');
      if (confirmation) confirmation.classList.toggle('d-none', !hasDifferences);
      if (!hasDifferences && checkbox) checkbox.checked = false;
      updateMergeExecuteState();
    } catch (error) { host.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`; execute.disabled = true; }
  }

  function updateMergeExecuteState() {
    const button = $('#executeStudentMerge');
    const confirmation = $('#mergeDifferenceConfirmation');
    const checkbox = $('#confirmMergeFieldDifferences');
    if (!button) return;
    const requiresConfirmation = confirmation && !confirmation.classList.contains('d-none');
    button.disabled = requiresConfirmation ? !checkbox?.checked : false;
  }

  async function executeMerge() {
    const survivorId = $('#mergeSurvivor')?.value;
    const duplicateId = $('#mergeDuplicate')?.value;
    const reason = $('#mergeReason')?.value.trim();
    if (!reason) { alert('A merge reason is required.'); return; }
    if (!confirm('Create a backup and permanently merge the selected duplicate into the survivor?')) return;
    const button = $('#executeStudentMerge'); button.disabled = true; button.textContent = 'Backing up & merging...';
    const body = new URLSearchParams({ action:'merge_students', survivor_id:survivorId, duplicate_id:duplicateId, reason, confirm_field_differences:$('#confirmMergeFieldDifferences')?.checked ? '1' : '0', csrf_token:$('#integrityCsrf')?.value || '' });
    try {
      const data = await loadJson('index.php?page=database_integrity', { method:'POST', body });
      alert(`${data.message}\nBackup: ${data.result.backup}`);
      window.location.reload();
    } catch (error) { alert(error.message); button.disabled = false; button.textContent = 'Backup & Merge'; }
  }

  function bind() {
    document.querySelectorAll('[data-load-duplicates]').forEach(button => button.addEventListener('click', loadDuplicates));
    document.querySelectorAll('[data-load-orphans]').forEach(button => button.addEventListener('click', loadOrphans));
    bindInspectButtons(); bindMergeButtons();
    $('#mergeSurvivor')?.addEventListener('change', loadMergePreview);
    $('#mergeDuplicate')?.addEventListener('change', loadMergePreview);
    $('#confirmMergeFieldDifferences')?.addEventListener('change', updateMergeExecuteState);
    $('#executeStudentMerge')?.addEventListener('click', executeMerge);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind); else bind();
})();