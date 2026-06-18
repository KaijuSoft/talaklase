<?php
// Generic management page builder
// Usage: include this after setting $config array

/*
$config = [
  'title'   => 'Departments',
  'icon'    => 'bi-building',
  'table'   => 'department',
  'pk'      => 'dept_id',
  'fields'  => [
    ['name'=>'dept_name','label'=>'Department Name','type'=>'text','required'=>true],
  ],
  'list_cols' => ['dept_name'=>'Name'],
  'order'   => 'dept_name',
];
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_permission_any(['manage_departments','manage_courses','manage_sections','manage_subjects','manage_instructors']);
$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        try {
            $cols = []; $placeholders = []; $vals = [];
            foreach ($config['fields'] as $f) {
                if (isset($f['fk'])) continue; // handled below
                $cols[] = $f['name'];
                $placeholders[] = '?';
                $vals[] = $_POST[$f['name']] ?? '';
            }
            // include FK fields
            foreach ($config['fields'] as $f) {
                if (isset($f['fk'])) {
                    $cols[] = $f['name'];
                    $placeholders[] = '?';
                    $vals[] = $_POST[$f['name']] ?? '';
                }
            }
            $sql = "INSERT INTO {$config['table']} (".implode(',',$cols).") VALUES (".implode(',',$placeholders).")";
            $pdo->prepare($sql)->execute($vals);
            echo json_encode(['success'=>true,'message'=>ucfirst($config['title']).' added.']);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        try {
            $sets = []; $vals = [];
            foreach ($config['fields'] as $f) {
                $sets[] = "{$f['name']}=?";
                $vals[] = $_POST[$f['name']] ?? '';
            }
            $vals[] = $_POST[$config['pk']];
            $sql = "UPDATE {$config['table']} SET ".implode(',',$sets)." WHERE {$config['pk']}=?";
            $pdo->prepare($sql)->execute($vals);
            echo json_encode(['success'=>true,'message'=>'Updated successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        try {
            $pdo->prepare("DELETE FROM {$config['table']} WHERE {$config['pk']}=?")->execute([$_POST[$config['pk']]]);
            echo json_encode(['success'=>true,'message'=>'Deleted.']);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }
}

// Load FK options
$fkOptions = [];
foreach ($config['fields'] as $f) {
    if (isset($f['fk'])) {
        $fkOptions[$f['name']] = $pdo->query("SELECT {$f['fk']['id']} AS id, {$f['fk']['label']} AS label FROM {$f['fk']['table']} ORDER BY {$f['fk']['label']}")->fetchAll();
    }
}

// Load records
$rows = $pdo->query("SELECT * FROM {$config['table']} ORDER BY {$config['order']}")->fetchAll();
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi <?= $config['icon'] ?> me-2 text-primary"></i><?= htmlspecialchars($config['title']) ?></h6>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-lg me-1"></i> Add <?= rtrim($config['title'],'s') ?>
    </button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <?php foreach ($config['list_cols'] as $col => $label): ?><th><?= $label ?></th><?php endforeach; ?>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="<?= count($config['list_cols'])+2 ?>" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No records found.</td></tr>
        <?php else: foreach ($rows as $i => $row): ?>
          <tr>
            <td class="text-muted"><?= $i+1 ?></td>
            <?php foreach ($config['list_cols'] as $col => $label): ?>
              <td><?= htmlspecialchars($row[$col] ?? '') ?></td>
            <?php endforeach; ?>
            <td>
              <button class="btn btn-sm btn-outline-primary py-0 px-1"
                onclick='openEdit(<?= htmlspecialchars(json_encode($row)) ?>)'>
                <i class="bi bi-pencil-fill"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger py-0 px-1 ms-1"
                onclick="deleteRecord(<?= $row[$config['pk']] ?>, '<?= htmlspecialchars($row[array_key_first($config['list_cols'])]) ?>')">
                <i class="bi bi-trash-fill"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-plus-lg me-2 text-primary"></i>Add <?= rtrim($config['title'],'s') ?></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php foreach ($config['fields'] as $f): ?>
          <div class="mb-3">
            <label class="form-label"><?= $f['label'] ?><?= ($f['required']??false) ? ' *' : '' ?></label>
            <?php if (isset($f['fk'])): ?>
              <select class="form-select" id="add_<?= $f['name'] ?>">
                <option value="">Select…</option>
                <?php foreach ($fkOptions[$f['name']] as $opt): ?>
                  <option value="<?= $opt['id'] ?>"><?= htmlspecialchars($opt['label']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="text" class="form-control" id="add_<?= $f['name'] ?>"/>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveRecord()"><i class="bi bi-check-lg me-1"></i>Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-fill me-2 text-warning"></i>Edit <?= rtrim($config['title'],'s') ?></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_pk"/>
        <?php foreach ($config['fields'] as $f): ?>
          <div class="mb-3">
            <label class="form-label"><?= $f['label'] ?></label>
            <?php if (isset($f['fk'])): ?>
              <select class="form-select" id="edit_<?= $f['name'] ?>">
                <?php foreach ($fkOptions[$f['name']] as $opt): ?>
                  <option value="<?= $opt['id'] ?>"><?= htmlspecialchars($opt['label']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="text" class="form-control" id="edit_<?= $f['name'] ?>"/>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning text-white" onclick="updateRecord()"><i class="bi bi-check-lg me-1"></i>Update</button>
      </div>
    </div>
  </div>
</div>

<script>
const pkField = '<?= $config['pk'] ?>';
const fields  = <?= json_encode(array_column($config['fields'],'name')) ?>;

function saveRecord() {
  const params = { action: 'add' };
  fields.forEach(f => { params[f] = document.getElementById('add_'+f)?.value || ''; });
  fetch('', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(params)})
  .then(r=>r.json()).then(res=>{
    if(res.success){showToast(res.message);bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();setTimeout(()=>location.reload(),800);}
    else showToast(res.message,'danger');
  });
}

function openEdit(row) {
  document.getElementById('edit_pk').value = row[pkField];
  fields.forEach(f => { const el = document.getElementById('edit_'+f); if(el) el.value = row[f] ?? ''; });
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

function updateRecord() {
  const params = { action: 'update', [pkField]: document.getElementById('edit_pk').value };
  fields.forEach(f => { params[f] = document.getElementById('edit_'+f)?.value || ''; });
  fetch('', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(params)})
  .then(r=>r.json()).then(res=>{
    if(res.success){showToast(res.message);bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();setTimeout(()=>location.reload(),800);}
    else showToast(res.message,'danger');
  });
}

function deleteRecord(id, name) {
  if (!confirm(`Delete "${name}"? This may fail if related records exist.`)) return;
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'delete',[pkField]:id})})
  .then(r=>r.json()).then(res=>{
    showToast(res.message,res.success?'success':'danger');
    if(res.success) setTimeout(()=>location.reload(),800);
  });
}
</script>
