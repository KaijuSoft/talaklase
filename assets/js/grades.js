let curTerm = 'Prelim';
let curComp = 'Participation';
let gradeRows = [];
let cache = {};
const canManageGrades = document.getElementById('gradePage')?.dataset.canManageGrades === '1';

const termLabel = {Prelim:'Prelim',Midterm:'Midterm',PreFinal:'Pre-Finals',Final:'Finals'};

function setTerm(el) {
  document.querySelectorAll('#termTabs .nav-link').forEach(a=>a.classList.remove('active'));
  el.classList.add('active');
  curTerm = el.dataset.term;
  const isSummary = curTerm === 'Summary';
  document.getElementById('compTabsWrapper').style.display = isSummary ? 'none' : '';
  document.getElementById('saveBtnArea')?.classList.add('d-none');
  loadGrid();
}

function setComp(el) {
  document.querySelectorAll('#compTabs .nav-link').forEach(a=>a.classList.remove('active'));
  el.classList.add('active');
  curComp = el.dataset.comp;
  loadGrid();
}

function getSecSub() {
  return {
    sec: document.getElementById('gr_section').value,
    sub: document.getElementById('gr_subject').value
  };
}

function onFilterChange() {
  cache = {};
  loadGrid();
}

function loadGrid() {
  const {sec, sub} = getSecSub();
  if (!sec || !sub) {
    document.getElementById('gradeGrid').innerHTML =
      '<div class="alert alert-warning mb-0">Please select both a section and a subject.</div>';
    document.getElementById('saveBtnArea')?.classList.add('d-none');
    return;
  }
  if (curTerm === 'Summary') { loadSummary(); return; }

  const key = `${curTerm}_${curComp}`;
  if (cache[key]) { renderGrid(cache[key]); return; }

  document.getElementById('gradeGrid').innerHTML =
    '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading grades…</p></div>';

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'load_component',section_id:sec,subject_id:sub,term:curTerm,component:curComp})})
  .then(r=>r.json()).then(data => { cache[key]=data; renderGrid(data); });
}

function renderGrid(data) {
  gradeRows = data;
  const isExam = curComp === 'Exam';
  const lbl = `${curComp} - ${termLabel[curTerm]||curTerm}`;
  if (canManageGrades) {
    document.getElementById('saveLabel').textContent = lbl;
    document.getElementById('saveBtnArea')?.classList.remove('d-none');
  }

  if (!data.length) {
    document.getElementById('gradeGrid').innerHTML =
      '<div class="alert alert-info mb-0">No students found in this section.</div>';
    return;
  }

  const colDefs = {
    Participation: ['par_one','par_two','par_three','par_four','par_five'],
    Written:       ['written_one','written_two','written_three','written_four','written_five'],
    Performance:   ['perf_one','perf_two','perf_three','perf_four','perf_five'],
  };

  let html = `<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover mb-0">
    <thead class="table-light"><tr>
      <th style="width:35px">#</th>
      <th>Name</th>
      <th style="width:120px">Student No.</th>
      <th style="width:80px">Gender</th>`;

  if (isExam) {
    html += `<th class="text-center" style="width:90px">Score</th>`;
  } else {
    for (let i=1;i<=5;i++) html += `<th class="text-center" style="width:90px">${curComp} ${i}</th>`;
  }
  html += '</tr></thead><tbody>';

  data.forEach((row, i) => {
    const g = row.st_gender==='Male' ? 'badge-gender-male' : 'badge-gender-female';
    html += `<tr><td class="text-muted">${i+1}</td><td>${row.FullName}</td>
      <td>${row.student_no ?? ''}</td>
      <td><span class="badge ${g}">${row.st_gender}</span></td>`;

    if (isExam) {
      html += `<td><input type="number" class="form-control form-control-sm grade-input text-center"
        id="score_${row.st_id}" value="${row.score??''}" min="0" step="0.01" placeholder="0" ${canManageGrades ? '' : 'readonly'}/></td>`;
    } else {
      const cols = colDefs[curComp];
      cols.forEach((col, j) => {
        html += `<td><input type="number" class="form-control form-control-sm grade-input text-center"
          id="s${j+1}_${row.st_id}" value="${row[col]??''}" min="0" step="0.01" placeholder="0" ${canManageGrades ? '' : 'readonly'}/></td>`;
      });
    }
    html += '</tr>';
  });
  html += '</tbody></table></div>';
  document.getElementById('gradeGrid').innerHTML = html;
}

function saveGrades() {
  if (!canManageGrades) { showToast('You do not have permission to save grades.','danger'); return; }
  const {sec, sub} = getSecSub();
  if (!sec || !sub) { showToast('Select section and subject first.','warning'); return; }
  const lbl = `${curComp} grades for ${termLabel[curTerm]||curTerm}`;
  if (!confirm(`Save ${lbl}?`)) return;

  const isExam = curComp === 'Exam';
  const rows = gradeRows.map(row => {
    if (isExam) return {st_id:row.st_id, score:document.getElementById(`score_${row.st_id}`)?.value||0};
    return {
      st_id:row.st_id,
      s1:document.getElementById(`s1_${row.st_id}`)?.value||0,
      s2:document.getElementById(`s2_${row.st_id}`)?.value||0,
      s3:document.getElementById(`s3_${row.st_id}`)?.value||0,
      s4:document.getElementById(`s4_${row.st_id}`)?.value||0,
      s5:document.getElementById(`s5_${row.st_id}`)?.value||0,
    };
  });

  fetch('', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'save_component',section_id:sec,subject_id:sub,term:curTerm,component:curComp,rows:JSON.stringify(rows)})})
  .then(r=>r.json()).then(res => {
    showToast(res.message, res.success?'success':'danger');
    if (res.success) delete cache[`${curTerm}_${curComp}`];
  });
}

function equivalentGrade(avg) {
  avg = Number(avg);
  if (isNaN(avg)) return '';
  if (avg >= 96) return '1.00';
  if (avg >= 94) return '1.25';
  if (avg >= 92) return '1.50';
  if (avg >= 89) return '1.75';
  if (avg >= 87) return '2.00';
  if (avg >= 84) return '2.25';
  if (avg >= 82) return '2.50';
  if (avg >= 79) return '2.75';
  if (avg >= 75) return '3.00';
  return '5.00';
}

function summaryRemarks(avg) {
  avg = Number(avg);
  if (isNaN(avg)) return '';
  return avg >= 75 ? 'Passed' : 'Failed';
}

function loadSummary() {
  const {sec,sub} = getSecSub();
  if (!sec || !sub) {
    document.getElementById('gradeGrid').innerHTML =
      '<div class="alert alert-warning mb-0">Please select a section and subject.</div>';
    return;
  }

  document.getElementById('gradeGrid').innerHTML =
    '<div class="text-center py-4"><div class="spinner-border text-success"></div><p class="mt-2 text-muted">Loading summary…</p></div>';

  const postTerm = (term) => fetch('', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({
      action:'load_summary',
      section_id:sec,
      subject_id:sub,
      term:term
    })
  }).then(r=>r.json());

  Promise.all([
    postTerm('Prelim'),
    postTerm('Midterm'),
    postTerm('PreFinal'),
    postTerm('Final')
  ]).then(([prelimRows, midtermRows, prefinalRows, finalRows]) => {
    if (!prelimRows.length && !midtermRows.length && !prefinalRows.length && !finalRows.length) {
      document.getElementById('gradeGrid').innerHTML =
        '<div class="alert alert-info mb-0">No summary data found.</div>';
      return;
    }

    const bucket = {};
    [prelimRows, midtermRows, prefinalRows, finalRows].forEach(group => {
      group.forEach(row => {
        if (!bucket[row.st_id]) {
          bucket[row.st_id] = {
            st_id: row.st_id,
            student_no: row.student_no,
            FullName: row.FullName,
            st_gender: row.st_gender,
            Prelim: '',
            Midterm: '',
            PreFinal: '',
            Final: ''
          };
        }
      });
    });

    prelimRows.forEach(row => { bucket[row.st_id].Prelim = row.TermTotal; });
    midtermRows.forEach(row => { bucket[row.st_id].Midterm = row.TermTotal; });
    prefinalRows.forEach(row => { bucket[row.st_id].PreFinal = row.TermTotal; });
    finalRows.forEach(row => { bucket[row.st_id].Final = row.TermTotal; });

    const rows = Object.values(bucket);

    let html = `<div class="table-responsive">
      <table class="table table-sm table-bordered table-hover mb-0">
      <thead class="table-success">
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Gender</th>
          <th class="text-center">Prelim</th>
          <th class="text-center">Midterm</th>
          <th class="text-center">Quarter 1</th>
          <th class="text-center">Pre-Finals</th>
          <th class="text-center">Finals</th>
          <th class="text-center">Quarter 2</th>
          <th class="text-center">Average Grades</th>
          <th class="text-center">Equivalent Grades</th>
          <th class="text-center">Remarks</th>
        </tr>
      </thead><tbody>`;

    rows.forEach((row, i) => {
      const g = row.st_gender === 'Male' ? 'badge-gender-male' : 'badge-gender-female';

      const prelim = row.Prelim === '' ? null : Number(row.Prelim);
      const midterm = row.Midterm === '' ? null : Number(row.Midterm);
      const prefinal = row.PreFinal === '' ? null : Number(row.PreFinal);
      const final = row.Final === '' ? null : Number(row.Final);

      const q1 = (prelim !== null && midterm !== null) ? Math.round((prelim + midterm) / 2) : '';
      const q2 = (prefinal !== null && final !== null) ? Math.round((prefinal + final) / 2) : '';
      const avg = (q1 !== '' && q2 !== '') ? Math.round((q1 + q2) / 2) : '';
      const eq = avg !== '' ? equivalentGrade(avg) : '';
      const rem = avg !== '' ? summaryRemarks(avg) : '';

      html += `<tr>
        <td class="text-muted">${i+1}</td>
        <td>${row.FullName}<div class="text-muted small">${row.student_no ? `Student No.: ${row.student_no}` : ''}</div></td>
        <td><span class="badge ${g}">${row.st_gender}</span></td>
        <td class="text-center">${prelim ?? ''}</td>
        <td class="text-center">${midterm ?? ''}</td>
        <td class="text-center"><strong>${q1}</strong></td>
        <td class="text-center">${prefinal ?? ''}</td>
        <td class="text-center">${final ?? ''}</td>
        <td class="text-center"><strong>${q2}</strong></td>
        <td class="text-center"><strong>${avg}</strong></td>
        <td class="text-center">${eq}</td>
        <td class="text-center ${rem === 'Passed' ? 'text-success' : (rem === 'Failed' ? 'text-danger' : '')}">${rem}</td>
      </tr>`;
    });

    html += '</tbody></table></div>';
    document.getElementById('gradeGrid').innerHTML = html;
  }).catch(() => {
    document.getElementById('gradeGrid').innerHTML =
      '<div class="alert alert-danger mb-0">Failed to load summary.</div>';
  });
}
function printGrades() {
  const {sec, sub} = getSecSub();
  if (!sec || !sub) { showToast('Select a section and subject first.','warning'); return; }
  if (curTerm === 'Summary') { showToast('Switch to a term tab to print grades.','warning'); return; }
  const url = `print.php?type=grades&sec=${sec}&sub=${sub}&term=${curTerm}&comp=${curComp}`;
  window.open(url, '_blank');
}