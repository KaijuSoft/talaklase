const csrfToken = document.querySelector('[data-csrf-token]')?.dataset.csrfToken || '';

function loadSectionData() {

    const sectionID =
        document.getElementById(
            'add_sectionID'
        ).value;

    loadSubjects(sectionID);

    loadInstructors(sectionID);
}

function loadSubjects(sectionID) {

    const params = new URLSearchParams();

    params.append('action', 'get_subjects');
    params.append('sectionID', sectionID);
    params.append('csrf_token', csrfToken);

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(subjects => {

        console.log('SUBJECTS', subjects);

        const select =
            document.getElementById('add_sub_id');

        select.innerHTML = '';
		
		if (subjects.length === 0) {

    const option =
        document.createElement('option');

    option.textContent =
        'No subjects assigned to this section';

    option.disabled = true;
    option.selected = true;

    select.appendChild(option);

    return;
}

        subjects.forEach(subject => {

            const option =
                document.createElement('option');

            option.value = subject.sub_id;

            option.textContent =
                subject.sub_code +
                ' - ' +
                subject.sub_name;

            select.appendChild(option);

        });

    });

}

function loadInstructors(sectionID) {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'get_instructors'
    );

    params.append(
        'sectionID',
        sectionID
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
            'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(instructors => {

        const select =
            document.getElementById(
                'add_inst_id'
            );

        select.innerHTML = '';
		
		if (instructors.length === 0) {

    const option =
        document.createElement('option');

    option.textContent =
        'No instructors assigned to this section';

    option.disabled = true;
    option.selected = true;

    select.appendChild(option);

    return;
}

        instructors.forEach(instructor => {

            const option =
                document.createElement('option');

            option.value =
                instructor.inst_id;

            option.textContent =
                instructor.inst_name;

            select.appendChild(option);

        });

    });

}


function loadEditSubjects(
    sectionID,
    selectedSubId
) {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'get_subjects'
    );

    params.append(
        'sectionID',
        sectionID
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(subjects => {

        const select =
            document.getElementById(
                'edit_sub_id'
            );

        select.innerHTML = '';

        subjects.forEach(subject => {

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                subject.sub_id;

            option.textContent =
                subject.sub_code +
                ' - ' +
                subject.sub_name;

            if (
                parseInt(subject.sub_id)
                ===
                parseInt(selectedSubId)
            ) {
                option.selected = true;
            }

            select.appendChild(
                option
            );

        });

    });

}

function saveLoad() {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'add'
    );

    params.append(
        'sectionID',
        document.getElementById(
            'add_sectionID'
        ).value
    );

    params.append(
        'sub_id',
        document.getElementById(
            'add_sub_id'
        ).value
    );

    params.append(
        'inst_id',
        document.getElementById(
            'add_inst_id'
        ).value
    );

    params.append(
        'start_time',
        document.getElementById(
            'add_start_time'
        ).value
    );

    params.append(
        'end_time',
        document.getElementById(
            'add_end_time'
        ).value
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(result => {

        alert(result.message);

        if (result.success) {

            location.reload();

        }

    });

}

function updateLoad() {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'update'
    );

    params.append(
        'assignment_id',
        document.getElementById(
            'edit_assignment_id'
        ).value
    );

    params.append(
        'sectionID',
        document.getElementById(
            'edit_sectionID'
        ).value
    );

    params.append(
        'sub_id',
        document.getElementById(
            'edit_sub_id'
        ).value
    );

    params.append(
        'inst_id',
        document.getElementById(
            'edit_inst_id'
        ).value
    );

    params.append(
        'start_time',
        document.getElementById(
            'edit_start_time'
        ).value
    );

    params.append(
        'end_time',
        document.getElementById(
            'edit_end_time'
        ).value
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(result => {

        alert(result.message);

        if (result.success) {

            location.reload();

        }

    });

}

document.addEventListener(
    'DOMContentLoaded',
    () => {

        loadSectionData();

    }
	
);

	document
    .getElementById('addModal')
    .addEventListener(
        'shown.bs.modal',
        function () {

            document.getElementById('add_start_time').value = '';
            document.getElementById('add_end_time').value = '';
            loadSectionData();

        }
    );
	
	
function loadEditInstructors(
    sectionID,
    selectedInstId
) {

    const params =
        new URLSearchParams();

    params.append(
        'action',
        'get_instructors'
    );

    params.append(
        'sectionID',
        sectionID
    );

    params.append(
        'csrf_token',
        csrfToken
    );

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(instructors => {

        const select =
            document.getElementById(
                'edit_inst_id'
            );

        select.innerHTML = '';

        instructors.forEach(
            instructor => {

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                instructor.inst_id;

            option.textContent =
                instructor.inst_name;

            if (
                parseInt(
                    instructor.inst_id
                )
                ===
                parseInt(
                    selectedInstId
                )
            ) {
                option.selected = true;
            }

            select.appendChild(
                option
            );

        });

    });

}

function loadEditSchedule(startTime, endTime) {
    document.getElementById('edit_start_time').value = startTime || '';
    document.getElementById('edit_end_time').value = endTime || '';
}
	
function editLoad(
    assignmentId,
    sectionId,
    subId,
    instId,
    startTime,
    endTime
) {

    document.getElementById(
        'edit_assignment_id'
    ).value = assignmentId;

    document.getElementById(
        'edit_sectionID'
    ).value = sectionId;
	
	loadEditSubjects(
    sectionId,
    subId
);

	loadEditInstructors(
    sectionId,
    instId
);

    loadEditSchedule(startTime, endTime);

    const modal =
        new bootstrap.Modal(
            document.getElementById(
                'editModal'
            )
        );

    modal.show();

}

function importSectionStudents(assignmentId) {
    if (!confirm('Import all students from this section into the teaching assignment?')) {
        return;
    }

    const params = new URLSearchParams();
    params.append('action', 'import_section_students');
    params.append('assignment_id', assignmentId);
    params.append('csrf_token', csrfToken);

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(r => r.json())
    .then(result => {
        alert(result.message);
        if (result.success) {
            location.reload();
        }
    });
}