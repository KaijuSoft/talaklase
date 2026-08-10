<?php
require_once __DIR__ . '/../includes/attendance_controller.php';
?>


<div class="card">
  <div class="card-header">
    <h6 class="mb-0"><i class="bi bi-calendar-check-fill me-2 text-primary"></i>Attendance</h6>
  </div>
  <div class="card-body">

    <!-- Attendance controls -->
    <div class="row g-3 mb-3">

    <div class="col-md-6">

        <label class="form-label">
            Teaching Load
        </label>

        <select
            id="assignment_id"
            class="form-select">

            <option value="">
                Loading...
            </option>

        </select>

    </div>

    <div class="col-md-2">

        <label class="form-label">
            Term
        </label>
        <select
            class="form-select"
            id="att_term">
            <option>Prelim</option>
            <option>Midterm</option>
            <option>Pre-Finals</option>
            <option>Finals</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">
            Date
        </label>
        <input
            type="date"
            class="form-control"
            id="att_date"
            value="<?= date('Y-m-d') ?>">
    </div>
    <div class="col-md-2 d-grid">
        <label class="form-label">
            &nbsp;
        </label>
        <button
            class="btn btn-primary"
            onclick="loadStudentsByAssignment()">
            Load Students
        </button>
    </div>
</div>

    <!-- Select All checkbox + Edit/Update button row -->
    <div id="bulkRow" class="d-none d-flex flex-wrap gap-2 align-items-center mb-2">
      <div class="form-check me-3">
        <input class="form-check-input" type="checkbox" id="chkSelectAll" onchange="selectAll(this.checked)"/>
        <label class="form-check-label" for="chkSelectAll">Select All Present</label>
      </div>
      <button class="btn btn-sm btn-outline-success" onclick="setAllStatus('Present')"><i class="bi bi-check-all me-1"></i>All Present</button>
      <button class="btn btn-sm btn-outline-danger"  onclick="setAllStatus('Absent')"><i class="bi bi-x-circle me-1"></i>All Absent</button>
      <button class="btn btn-sm btn-outline-warning" onclick="setAllStatus('Late')"><i class="bi bi-clock me-1"></i>All Late</button>
    </div>

    <!-- Student grid -->
    <div id="attGrid">
      <!-- loaded via JS -->
    </div>

    <!-- Save / Edit / Update buttons -->
    <div id="attActions" class="d-none mt-3 d-flex gap-2 flex-wrap">
      <button id="btnSave" class="btn btn-success" onclick="saveAttendance()">
        <i class="bi bi-floppy-fill me-1"></i> Save Attendance
      </button>
      <button id="btnEdit" class="btn btn-warning text-white" onclick="editMode()">
        <i class="bi bi-pencil-fill me-1"></i> Edit
      </button>
      <button id="btnUpdate" class="btn btn-primary d-none" onclick="updateAttendance()">
        <i class="bi bi-check-lg me-1"></i> Update
      </button>
    </div>

  </div>
</div>
