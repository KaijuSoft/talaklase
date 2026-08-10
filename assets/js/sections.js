const configEl = document.getElementById("sectionsPageConfig");
const sectionsConfig = {
  csrfToken: configEl?.dataset.csrf || "",
  canManageAllSections: configEl?.dataset.manageAll === "1"
};
const csrfToken = sectionsConfig.csrfToken;


function postSection(params) {
  params.csrf_token = csrfToken;
  return fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(params)
  }).then(response => response.json());
}

function saveRecord() {

  const params = new URLSearchParams();

  params.append('action', 'add');
  params.append('section', document.getElementById('add_section').value.trim());
  params.append('course_id', document.getElementById('add_course_id').value);

  document
    .querySelectorAll('#addModal input[name="add_instructors[]"]:checked')
    .forEach(cb => {
        params.append('instructors[]', cb.value);
    });
	
	document
  .querySelectorAll('#addModal input[name="subjects[]"]:checked')
  .forEach(cb => {
      params.append('subjects[]', cb.value);
  });




  params.append('csrf_token', csrfToken);

  fetch('', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: params
  })
  .then(r => r.json())

  .then(result => {
      showToast(result.message, result.success ? 'success' : 'danger');

      if (result.success) {
          bootstrap.Modal
              .getInstance(document.getElementById('addModal'))
              .hide();

          setTimeout(() => location.reload(), 700);
      }
  });
}

function openEdit(row) {

    document.getElementById('edit_sectionID').value =
        row.sectionID || '';

    document.getElementById('edit_section').value =
        row.section || '';

    document.getElementById('edit_course_id').value =
        row.course_id || '';

    document.querySelectorAll('.edit-instructor')
        .forEach(cb => cb.checked = false);
		
		document.querySelectorAll('.edit-subject')
    .forEach(cb => cb.checked = false);

    if (row.instructors) {

        row.instructors.forEach(id => {

            document.querySelectorAll('.edit-instructor')
                .forEach(cb => {

                    if (cb.value == id) {
                        cb.checked = true;
                    }

                });

        });

    }
	
	if (row.subjects) {

    row.subjects.forEach(id => {

        document.querySelectorAll('.edit-subject')
            .forEach(cb => {

                if (cb.value == id) {
                    cb.checked = true;
                }

            });

    });

}

    new bootstrap.Modal(
        document.getElementById('editModal')
    ).show();
}


function updateRecord() {

  const params = new URLSearchParams();

  params.append('action', 'update');
  params.append('sectionID',
      document.getElementById('edit_sectionID').value);

  params.append('section',
      document.getElementById('edit_section').value.trim());

  params.append('course_id',
      document.getElementById('edit_course_id').value);

  document
    .querySelectorAll('.edit-instructor:checked')
    .forEach(cb => {
        params.append('instructors[]', cb.value);
    });

document
  .querySelectorAll('.edit-subject:checked')
  .forEach(cb => {
      params.append('subjects[]', cb.value);
  });
   
  params.append('csrf_token', csrfToken);

  fetch('', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: params
  })
  .then(r => r.json())
  .then(result => {

      showToast(result.message,
          result.success ? 'success' : 'danger');

      if (result.success) {

          bootstrap.Modal
              .getInstance(document.getElementById('editModal'))
              .hide();

          setTimeout(() => location.reload(), 700);
      }

  });
}
function deleteRecord(id, name) {
  if (!confirm(`Delete section "${name}"?`)) return;
  postSection({ action: 'delete', sectionID: id }).then(result => {
    showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) setTimeout(() => location.reload(), 700);
  });
}