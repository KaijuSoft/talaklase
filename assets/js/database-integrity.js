(function () {
  const $ = (selector, root = document) => root.querySelector(selector);

  async function loadJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Unable to load integrity details.');
    }
    return data;
  }

  function mount(hostId, html) {
    const host = document.getElementById(hostId);
    if (host) host.innerHTML = html;
  }

  async function loadDuplicates() {
    mount('duplicateDetailsHost', '<div class="text-muted">Loading duplicate details...</div>');
    const data = await loadJson('?page=database_integrity&action=duplicate_details');
    mount('duplicateDetailsHost', data.html);
    bindInspectButtons();
  }

  async function loadOrphans() {
    mount('orphanDetailsHost', '<div class="text-muted">Loading orphan details...</div>');
    const data = await loadJson('?page=database_integrity&action=orphan_details');
    mount('orphanDetailsHost', data.html);
    bindInspectButtons();
  }

  async function loadInspector(studentId) {
    mount('referenceInspectorHost', '<div class="text-muted">Loading reference inspector...</div>');
    const data = await loadJson(`?page=database_integrity&action=reference_inspector&student_id=${encodeURIComponent(studentId)}`);
    mount('referenceInspectorHost', data.html);
    const closeButton = $('[data-integrity-close-inspector]');
    if (closeButton) {
      closeButton.addEventListener('click', () => {
        mount('referenceInspectorHost', '<div class="text-muted">Select a student from a detail panel to load the inspector.</div>');
      });
    }
  }

  function bindInspectButtons() {
    document.querySelectorAll('[data-integrity-student-id]').forEach((button) => {
      if (button.dataset.bound === '1') return;
      button.dataset.bound = '1';
      button.addEventListener('click', () => loadInspector(button.dataset.integrityStudentId));
    });
  }

  function bind() {
    document.querySelectorAll('[data-load-duplicates]').forEach((button) => button.addEventListener('click', loadDuplicates));
    document.querySelectorAll('[data-load-orphans]').forEach((button) => button.addEventListener('click', loadOrphans));
    document.querySelectorAll('[data-load-inspector]').forEach((button) => button.addEventListener('click', () => {
      const host = document.getElementById('referenceInspectorHost');
      if (host) host.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    bindInspectButtons();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
