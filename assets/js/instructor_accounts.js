const configEl = document.getElementById('instructorAccountsConfig');
const csrfToken = configEl?.dataset.csrf || '';

function postAccount(params) {
  params.csrf_token = csrfToken;
  return fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(params)
  }).then(response => response.json());
}

function toggleDeptForRole() {
  const role = document.getElementById('add_role')?.value;
  const wrap = document.getElementById('addDeptWrap');
  if (wrap) wrap.classList.toggle('d-none', role === 'admin');
}

function toggleEditDeptForRole() {
  const role = document.getElementById('edit_role')?.value;
  const wrap = document.getElementById('editDeptWrap');
  if (wrap) wrap.classList.toggle('d-none', role === 'admin');
}

function saveRecord() {
  postAccount({
    action: 'add',
    name: document.getElementById('add_name').value.trim(),
    role: document.getElementById('add_role').value,
    dept_id: document.getElementById('add_dept_id').value,
    email: document.getElementById('add_email').value.trim(),
    password: document.getElementById('add_password').value,
  }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
      setTimeout(() => location.reload(), 700);
    }
  });
}

function openEdit(row) {
  document.getElementById('edit_user_id').value = row.id || '';
  document.getElementById('edit_name').value = row.name || '';
  document.getElementById('edit_role').value = row.role || 'instructor';
  document.getElementById('edit_dept_id').value = row.dept_id || '';
  document.getElementById('edit_email').value = row.email || row.username || '';
  document.getElementById('edit_password').value = '';
  const history = document.getElementById('edit_history');
  const historyBody = document.getElementById('edit_history_body');
  const notes = [];
  if ((intVal(row.is_active) === 0)) {
    notes.push(`<div><strong>Archived</strong></div>`);
    if (row.archived_at) notes.push(`<div>Date: ${escapeHtml(row.archived_at)}</div>`);
    if (row.archived_by_name) notes.push(`<div>Archived By: ${escapeHtml(row.archived_by_name)}</div>`);
    if (row.archive_reason) notes.push(`<div>Reason: ${escapeHtml(row.archive_reason)}</div>`);
    if (row.restored_at) notes.push(`<hr class="my-2"><div><strong>Last Restored</strong></div>`);
    if (row.restored_at) notes.push(`<div>Date: ${escapeHtml(row.restored_at)}</div>`);
    if (row.restored_by_name) notes.push(`<div>Restored By: ${escapeHtml(row.restored_by_name)}</div>`);
    if (row.restore_reason) notes.push(`<div>Reason: ${escapeHtml(row.restore_reason)}</div>`);
  }
  if (history && historyBody) {
    history.classList.toggle('d-none', notes.length === 0);
    historyBody.innerHTML = notes.join('');
  }
  toggleEditDeptForRole();
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

function updateRecord() {
  postAccount({
    action: 'update',
    user_id: document.getElementById('edit_user_id').value,
    name: document.getElementById('edit_name').value.trim(),
    role: document.getElementById('edit_role').value,
    dept_id: document.getElementById('edit_dept_id').value,
    email: document.getElementById('edit_email').value.trim(),
    password: document.getElementById('edit_password').value,
  }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
      setTimeout(() => location.reload(), 700);
    }
  });
}

function openArchive(row) {
  document.getElementById('archive_user_id').value = row.id || '';
  document.getElementById('archive_reason').value = '';
  new bootstrap.Modal(document.getElementById('archiveModal')).show();
}

function archiveUser() {
  const userId = document.getElementById('archive_user_id').value;
  const reason = document.getElementById('archive_reason').value.trim();
  if (!reason) {
    showToast('Archive reason is required.', 'danger');
    return;
  }
  postAccount({ action: 'archive', user_id: userId, archive_reason: reason }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('archiveModal')).hide();
      setTimeout(() => location.reload(), 700);
    }
  });
}

function openRestore(row) {
  document.getElementById('restore_user_id').value = row.id || '';
  document.getElementById('restore_reason').value = '';
  new bootstrap.Modal(document.getElementById('restoreModal')).show();
}

function restoreUser() {
  const userId = document.getElementById('restore_user_id').value;
  const reason = document.getElementById('restore_reason').value.trim();
  if (!reason) {
    showToast('Restore reason is required.', 'danger');
    return;
  }
  postAccount({ action: 'restore', user_id: userId, restore_reason: reason }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('restoreModal')).hide();
      setTimeout(() => location.reload(), 700);
    }
  });
}

function resetPassword(userId) {
  const password = prompt('Enter a new password for this account:');
  if (!password) return;
  postAccount({ action: 'reset_password', user_id: userId, password }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
  });
}

toggleDeptForRole();

function intVal(value) {
  return parseInt(value || '0', 10) || 0;
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  }[ch]));
}
