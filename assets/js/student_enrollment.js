const csrfToken = document.querySelector('[data-enrollment-csrf]')?.dataset.enrollmentCsrf || '';

async function postEnrollment(params) {
    params.append('csrf_token', csrfToken);
    const response = await fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params
    });
    return response.json();
}

async function enrollStudent() {
    const stId = document.getElementById('st_id').value;
    const assignmentId = document.getElementById('assignment_id').value;
    if (!stId || !assignmentId) {
        alert('Please select a student and teaching load.');
        return;
    }
    const result = await postEnrollment(new URLSearchParams({
        action: 'enroll', st_id: stId, assignment_id: assignmentId
    }));
    alert(result.message);
    if (result.success) location.reload();
}

async function removeEnrollment(id) {
    if (!confirm('Remove enrollment?')) return;
    const result = await postEnrollment(new URLSearchParams({
        action: 'remove', enrollment_id: String(id)
    }));
    alert(result.message);
    if (result.success) location.reload();
}
