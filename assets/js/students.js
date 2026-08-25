/* TalaKlase - Students page frontend */
(function () {
  'use strict';

  const configElement = document.getElementById('studentPageConfig');
  const config = configElement ? JSON.parse(configElement.textContent || '{}') : {};
  const canManageStudents = config.canManageStudents === true;
  const studentCache = window.TalaCache ? window.TalaCache.resource('students', { scope: config.cacheScope || 'default' }) : null;

  function openStudentModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    if (window.bootstrap && bootstrap.Modal) {
      bootstrap.Modal.getOrCreateInstance(modal).show();
    } else {
      console.error('Bootstrap Modal is not available.');
    }
  }

  function initStudentModals() {
    document.querySelectorAll('[data-bs-target="#addModal"]').forEach(button => {
      button.addEventListener('click', event => {
        event.preventDefault();
        openStudentModal('addModal');
      });
    });
    document.querySelectorAll('[data-bs-target="#importStudentsModal"]').forEach(button => {
      button.addEventListener('click', event => {
        event.preventDefault();
        openStudentModal('importStudentsModal');
      });
    });
  }

  function invalidateStudents() { if (studentCache) studentCache.clear(); }
  initStudentModals();

  window.saveStudent = function () {
    if (!canManageStudents) { showToast('You do not have permission to manage students.','danger'); return; }
    const data = {
      action:'add',
      student_no: document.getElementById('add_student_no').value.trim(),
      lastname: document.getElementById('add_lastname').value.trim(),
      firstname: document.getElementById('add_firstname').value.trim(),
      middlename: document.getElementById('add_middlename').value.trim(),
      suffix: document.getElementById('add_suffix').value.trim(),
      gender: document.getElementById('add_gender').value,
      course_id: document.getElementById('add_course').value,
      section_id: document.getElementById('add_section').value,
      year_level: document.getElementById('add_year').value,
    };
    if (!data.lastname||!data.firstname||!data.gender||!data.course_id||!data.section_id||!data.year_level) {
      showToast('Please fill in all required fields.','warning'); return;
    }
    fetch('', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams(data)})
      .then(r=>r.json()).then(res=>{
        if(res.success){ invalidateStudents(); showToast(res.message);bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();setTimeout(()=>location.reload(),800);}
        else showToast(res.message,'danger');
      });
  };

  window.openEdit = function (s) {
    document.getElementById('edit_id').value=s.st_id;
    document.getElementById('edit_student_no').value=s.student_no || '';
    document.getElementById('edit_lastname').value=s.st_lastname;
    document.getElementById('edit_firstname').value=s.st_name;
    document.getElementById('edit_middlename').value=s.st_middlename;
    document.getElementById('edit_suffix').value=s.st_suffix;
    document.getElementById('edit_gender').value=s.st_gender;
    [...document.getElementById('edit_course').options].forEach(o=>{if(o.text===s.course_acronym)o.selected=true;});
    [...document.getElementById('edit_section').options].forEach(o=>{if(o.text===s.section)o.selected=true;});
    document.getElementById('edit_year').value=s.yearlvl;
    new bootstrap.Modal(document.getElementById('editModal')).show();
  };

  window.updateStudent = function () {
    if (!canManageStudents) { showToast('You do not have permission to manage students.','danger'); return; }
    const data = {
      action:'update',
      st_id:document.getElementById('edit_id').value,
      student_no:document.getElementById('edit_student_no').value.trim(),
      lastname:document.getElementById('edit_lastname').value.trim(),
      firstname:document.getElementById('edit_firstname').value.trim(),
      middlename:document.getElementById('edit_middlename').value.trim(),
      suffix:document.getElementById('edit_suffix').value.trim(),
      gender:document.getElementById('edit_gender').value,
      course_id:document.getElementById('edit_course').value,
      section_id:document.getElementById('edit_section').value,
      year_level:document.getElementById('edit_year').value,
    };
    fetch('', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams(data)})
      .then(r=>r.json()).then(res=>{
        if(res.success){ invalidateStudents(); showToast(res.message);bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();setTimeout(()=>location.reload(),800);}
        else showToast(res.message,'danger');
      });
  };

  window.deleteStudent = function (id, name) {
    if (!canManageStudents) { showToast('You do not have permission to manage students.','danger'); return; }
    if (!confirm(`Delete student "${name}"? This cannot be undone.`)) return;
    fetch('', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'delete',st_id:id})})
      .then(r=>r.json()).then(res=>{
        showToast(res.message, res.success?'success':'danger');
        if(res.success) setTimeout(()=>location.reload(),800);
      });
  };

  window.printStudents = function () {
    const q = new URLSearchParams(window.location.search).get('q') || '';
    window.open('print.php?type=students&q=' + encodeURIComponent(q), '_blank');
  };
})();
