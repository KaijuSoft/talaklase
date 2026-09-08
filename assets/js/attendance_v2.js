let attStudents = [];
let isEditMode  = false;
let editOriginalDate = '';
let editOriginalTerm = '';

// On page load - load teaching loads
window.addEventListener(
    'DOMContentLoaded',
    () => {
        loadTeachingLoads();
    }
);


function loadTeachingLoads() {
	
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            action: 'load_teaching_loads'
        })
    })
    .then(r => r.json())
    .then(rows => {
        const ddl =
            document.getElementById(
                'assignment_id'
            );
        ddl.innerHTML =
            '<option value="">Select Teaching Load</option>';
        rows.forEach(row => {
            ddl.innerHTML += `
                <option
                    value="${row.assignment_id}"
                    data-section="${row.sectionID}">
                    ${row.section}
                    - ${row.sub_code}
                    (${row.inst_name})
                </option>
            `;
        });
    });
}

function loadStudentsByAssignment() {

    const assignmentId = document.getElementById('assignment_id').value;
    const date         = document.getElementById('att_date').value;
    const term         = document.getElementById('att_term').value;

    if (!assignmentId) {
        showToast('Select a teaching load.', 'warning');
        return;
    }

        // Step 1 - load the student roster for this assignment
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'load_students_by_assignment',
            assignment_id: assignmentId
        })
    })
    .then(r => r.json())
    .then(students => {

        if (!date || !term) {
            renderGrid(students, false);
            return;
        }

        // Step 2 - check for existing attendance for this load, date, and term
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'load_existing',
                assignment_id: assignmentId,
                date: date,
                term: term
            })
        })
        .then(r => r.json())
        .then(existing => {

            if (existing.length > 0) {
                // Merge saved statuses into the roster so the grid pre-fills correctly
                const statusMap = {};
                existing.forEach(e => {
                    statusMap[e.st_id] = {
                        att_id: e.Att_ID,
                        status: e.status,
                        time_in: e.time_in
                    };
                });
                students.forEach(s => {
                    const saved = statusMap[s.ID];
                    s.att_id = saved?.att_id || null;
                    s.status = saved?.status || 'Absent';
                    s.time_in = saved?.time_in || '';
                });

                editOriginalDate = date;
                editOriginalTerm = term;
                renderGrid(students, true);
                showToast('Existing attendance found. Update mode enabled.', 'info');

            } else {
                renderGrid(students, false);
            }
        });
    });
}

function checkExistingAttendance() {

    const assignmentId = document.getElementById('assignment_id').value;
    const date         = document.getElementById('att_date').value;
    const term         = document.getElementById('att_term').value;

    if (!assignmentId || !date || !term) return;

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'load_existing',
            assignment_id: assignmentId,
            date: date,
            term: term
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.length > 0) {
            showToast('Attendance already recorded for this selection. Click Load Students to update.', 'info');
        }
    });

}

/*function searchStudents() {
  const q  = document.getElementById('att_search').value.trim();
  const by = document.getElementById('att_search_by').value;

  if (!by) { showToast('Please select a search option (Section or Name).','warning'); return; }

  const actionMap = {Section:'search_section', Name:'search_name'};
  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:actionMap[by], q})})
  .then(r=>r.json()).then(data => renderGrid(data, false));
}*/

function attendanceTimeValue(timeIn) {
  const value = String(timeIn || '');
  const match = value.match(/(\d{2}:\d{2})(?::\d{2})?$/);
  return match ? match[1] : '';
}

function renderGrid(data, editData) {
  attStudents = data;
  isEditMode  = !!editData;

  if (!data.length) {
    document.getElementById('attGrid').innerHTML = '<div class="alert alert-info">No students found.</div>';
    document.getElementById('bulkRow').classList.add('d-none');
    document.getElementById('attActions').classList.add('d-none');
    return;
  }

  document.getElementById('bulkRow').classList.remove('d-none');
  document.getElementById('attActions').classList.remove('d-none');

  let html = `<div class="table-responsive">
    <table class="table table-sm table-hover table-bordered mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:35px">#</th>
        <th>Full Name</th>
        <th style="width:100px">Section</th>
        <th style="width:240px">Status</th>
      </tr>
    </thead><tbody>`;

    data.forEach((s, i) => {
    const status = editData ? (s.status || 'Absent') : 'Present';
    const pChk = status==='Present' ? 'checked' : '';
    const aChk = status==='Absent'  ? 'checked' : '';
    const lChk = status==='Late'    ? 'checked' : '';
    const savedTime = attendanceTimeValue(s.time_in);
    const lateTimeClass = status === 'Late' ? '' : 'd-none';

    html += `<tr>
      <td class="text-muted">${i+1}</td>
      <td>
        <div>${s.FullName}</div>
        <div class="text-muted small">${s.student_no ? `Student No.: ${s.student_no}` : ''}</div>
      </td>
      <td><span class="badge bg-secondary-subtle text-secondary border">${s.section}</span></td>
      <td>
        <div class="btn-group btn-group-sm" role="group">
          <input type="radio" class="btn-check" name="st_${s.ID}" id="p_${s.ID}" value="Present" ${pChk} onchange="toggleLateTime(${s.ID})">
          <label class="btn btn-outline-success" for="p_${s.ID}">Present</label>
          <input type="radio" class="btn-check" name="st_${s.ID}" id="a_${s.ID}" value="Absent" ${aChk} onchange="toggleLateTime(${s.ID})">
          <label class="btn btn-outline-danger" for="a_${s.ID}">Absent</label>
          <input type="radio" class="btn-check" name="st_${s.ID}" id="l_${s.ID}" value="Late" ${lChk} onchange="toggleLateTime(${s.ID})">
          <label class="btn btn-outline-warning" for="l_${s.ID}">Late</label>
        </div>
        <input type="time" class="form-control form-control-sm mt-2 late-time ${lateTimeClass}" id="time_${s.ID}" value="${savedTime}">
      </td>
    </tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('attGrid').innerHTML = html;

  // Toggle buttons
  document.getElementById('btnSave').classList.toggle('d-none', isEditMode);
  document.getElementById('btnEdit').classList.toggle('d-none', isEditMode);
  document.getElementById('btnUpdate').classList.toggle('d-none', !isEditMode);
}

function toggleLateTime(studentId) {
  const selected = document.querySelector(`input[name="st_${studentId}"]:checked`)?.value;
  const timeInput = document.getElementById(`time_${studentId}`);

  if (!timeInput) return;

  timeInput.classList.toggle('d-none', selected !== 'Late');
  if (selected !== 'Late') {
    timeInput.value = '';
  }
}

function selectAll(checked) {
  attStudents.forEach(s => {
    const el = document.getElementById(checked ? `p_${s.ID}` : `a_${s.ID}`);
    if (el) {
      el.checked = true;
      toggleLateTime(s.ID);
    }
  });
}

function setAllStatus(status) {
  const prefix = {Present:'p', Absent:'a', Late:'l'}[status];
  attStudents.forEach(s => {
    const el = document.getElementById(`${prefix}_${s.ID}`);
    if (el) {
      el.checked = true;
      toggleLateTime(s.ID);
    }
  });
  document.getElementById('chkSelectAll').checked = (status === 'Present');
}

function getRecords() {

  return attStudents.map(
    s => {
      const status = document.querySelector(
        `input[name="st_${s.ID}"]:checked`
      )?.value || 'Absent';
      const timeInput = document.getElementById(`time_${s.ID}`);

      return ({
      att_id:
        s.att_id || null,
      st_id:
        s.ID,
      sectionID:
        s.SecID,
      assignment_id:
        s.assignment_id,
      status:
        status,
      time_in:
        status === 'Late'
          ? (timeInput?.value || null)
          : null
      });
    }
  );
}

function saveAttendance() {
  const date = document.getElementById('att_date').value;
  const term = document.getElementById('att_term').value;
  if (!attStudents.length) { showToast('No students loaded.','warning'); return; }

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'save', date, term, records:JSON.stringify(getRecords())})})
  .then(r=>r.json()).then(res => {
    showToast(res.message, res.success?'success':'danger');
    if (res.success) resetPage();
  });
}

/* function editMode() {
  const q    = document.getElementById('att_search').value.trim();
  const term = document.getElementById('att_term').value;
  const date = document.getElementById('att_date').value;

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'load_edit', q, term, date})})
  .then(r=>r.json()).then(data => renderGrid(data, true));
}
 */
function updateAttendance() {

  const date =
      document.getElementById('att_date').value;

  const term =
      document.getElementById('att_term').value;

  fetch('', {
      method:'POST',
      headers:{
          'Content-Type':
              'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
          action:'update',
          old_date: editOriginalDate || date,
          old_term: editOriginalTerm || term,
          date,
          term,
          records:JSON.stringify(
              getRecords()
          )
      })
  })
  .then(r=>r.json())
  .then(res => {
      showToast(
          res.message,
          res.success
              ? 'success'
              : 'danger'
      );

      if (res.success)
          resetPage();
  });
}


function resetPage() {

  const assignment =
    document.getElementById('assignment_id');
  if (assignment) {
    assignment.value = '';
  }
  const chk =
    document.getElementById('chkSelectAll');
  if (chk) {
    chk.checked = false;
  }
  document.getElementById('attActions')
    ?.classList.add('d-none');
  document.getElementById('bulkRow')
    ?.classList.add('d-none');
  isEditMode = false;
  editOriginalDate = '';
  editOriginalTerm = '';
  attStudents = [];
  renderGrid([], false);
}

document
.getElementById('assignment_id')
.addEventListener(
    'change',
    checkExistingAttendance
);

document
.getElementById('att_date')
.addEventListener(
    'change',
    checkExistingAttendance
);

document
.getElementById('att_term')
.addEventListener(
    'change',
    checkExistingAttendance
);
