<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireAdmin();

$db     = getDB();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'toggle' && $id) {
    $db->prepare("UPDATE Programmes SET Published = NOT Published WHERE ProgrammeID = ?")->execute([$id]);
    flashSet('success', 'Programme visibility updated.');
    redirect(BASE_URL . '/admin/programmes.php');
}
if ($action === 'delete' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->prepare("DELETE FROM Programmes WHERE ProgrammeID = ?")->execute([$id]);
    flashSet('success', 'Programme deleted.');
    redirect(BASE_URL . '/admin/programmes.php');
}

$errors   = [];
$formData = ['ProgrammeName'=>'','LevelID'=>'','ProgrammeLeaderID'=>'','Description'=>'','Image'=>'','Published'=>1];

if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM Programmes WHERE ProgrammeID = ?");
    $stmt->execute([$id]);
    $editProgramme = $stmt->fetch();
    if (!$editProgramme) { flashSet('error','Not found.'); redirect(BASE_URL.'/admin/programmes.php'); }
    $formData = $editProgramme;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','edit'])) {
    // Handle file upload
    $imagePath = sanitize($_POST['Image'] ?? ''); // keep existing if no new file
    if (!empty($_FILES['ImageFile']['name'])) {
        $file     = $_FILES['ImageFile'];
        $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
        $maxSize  = 5 * 1024 * 1024; // 5MB
        if (!in_array($file['type'], $allowed)) {
            $errors[] = 'Invalid image format. Use JPG, PNG, GIF or WebP.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Image too large. Maximum size is 5MB.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed. Please try again.';
        } else {
            $uploadDir = __DIR__ . '/../assets/images/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename  = 'prog_' . uniqid() . '.' . strtolower($ext);
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $imagePath = '/student_course_hub/assets/images/' . $filename;
            } else {
                $errors[] = 'Could not save the uploaded file.';
            }
        }
    }

    $formData = [
        'ProgrammeName'     => sanitize($_POST['ProgrammeName'] ?? ''),
        'LevelID'           => (int)($_POST['LevelID'] ?? 0),
        'ProgrammeLeaderID' => (int)($_POST['ProgrammeLeaderID'] ?? 0),
        'Description'       => sanitize($_POST['Description'] ?? ''),
        'Image'             => $imagePath,
        'Published'         => isset($_POST['Published']) ? 1 : 0,
    ];
    if (empty($formData['ProgrammeName'])) $errors[] = 'Programme name is required.';
    if (empty($formData['LevelID']))       $errors[] = 'Please select a level.';
    if (empty($errors)) {
        if ($action === 'create') {
            $db->prepare("INSERT INTO Programmes (ProgrammeName,LevelID,ProgrammeLeaderID,Description,Image,Published) VALUES (?,?,?,?,?,?)")
               ->execute(array_values($formData));
            flashSet('success', 'Programme created.');
        } else {
            $db->prepare("UPDATE Programmes SET ProgrammeName=?,LevelID=?,ProgrammeLeaderID=?,Description=?,Image=?,Published=? WHERE ProgrammeID=?")
               ->execute([...array_values($formData), $id]);
            flashSet('success', 'Programme updated.');
        }
        redirect(BASE_URL . '/admin/programmes.php');
    }
}

// Manage modules
if ($action === 'modules' && $id) {
    $stmt = $db->prepare("SELECT * FROM Programmes WHERE ProgrammeID = ?");
    $stmt->execute([$id]);
    $editProgramme = $stmt->fetch();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_modules'])) {
        $db->prepare("DELETE FROM ProgrammeModules WHERE ProgrammeID = ?")->execute([$id]);
        $ins = $db->prepare("INSERT INTO ProgrammeModules (ProgrammeID,ModuleID,Year) VALUES (?,?,?)");
        foreach ($_POST['module_id'] ?? [] as $k => $mid) {
            if (!empty($mid) && !empty($_POST['module_year'][$k]))
                $ins->execute([$id,(int)$mid,(int)$_POST['module_year'][$k]]);
        }
        flashSet('success', 'Modules saved.');
        redirect(BASE_URL . '/admin/programmes.php');
    }
    $allModules      = $db->query("SELECT m.*,s.Name AS LeaderName FROM Modules m LEFT JOIN Staff s ON m.ModuleLeaderID=s.StaffID ORDER BY m.ModuleName")->fetchAll();
    $assignedStmt    = $db->prepare("SELECT * FROM ProgrammeModules WHERE ProgrammeID=? ORDER BY Year,ModuleID");
    $assignedStmt->execute([$id]);
    $assignedModules = $assignedStmt->fetchAll();
    $pageTitle       = 'Manage Modules';
    include __DIR__ . '/includes/admin_header.php';
    ?>
    <div class="page-header">
        <div class="page-header-left">
            <div class="ku-divider-sm"></div>
            <h1>Manage Modules</h1>
            <p><?= h($editProgramme['ProgrammeName']) ?></p>
        </div>
        <a href="/student_course_hub/admin/programmes.php" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="form-card">
        <form method="POST">
            <input type="hidden" name="save_modules" value="1">
            <table>
                <thead><tr><th>Module</th><th>Year</th><th></th></tr></thead>
                <tbody id="modulesTable">
                <?php foreach ($assignedModules as $am): ?>
                <tr>
                    <td>
                        <select name="module_id[]" style="width:100%;padding:7px 10px;border:1px solid var(--grey-200);border-radius:var(--radius);font-family:var(--font-sans);font-size:0.85rem">
                            <?php foreach ($allModules as $mod): ?>
                            <option value="<?= $mod['ModuleID'] ?>" <?= $mod['ModuleID']==$am['ModuleID']?'selected':'' ?>><?= h($mod['ModuleName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="module_year[]" style="padding:7px 10px;border:1px solid var(--grey-200);border-radius:var(--radius);font-family:var(--font-sans);font-size:0.85rem">
                            <?php for ($y=1;$y<=3;$y++): ?>
                            <option value="<?= $y ?>" <?= $y==$am['Year']?'selected':'' ?>>Year <?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>
                    <td><button type="button" class="btn btn-outline btn-sm" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:16px;display:flex;gap:10px">
                <button type="button" class="btn btn-outline" onclick="addModuleRow()"><i class="bi bi-plus-lg"></i> Add Module</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
            </div>
        </form>
    </div>
    <script>
    const allMods = <?= json_encode(array_map(fn($m)=>['id'=>$m['ModuleID'],'name'=>$m['ModuleName']],$allModules)) ?>;
    function addModuleRow() {
        const tbody = document.getElementById('modulesTable');
        const opts  = allMods.map(m=>`<option value="${m.id}">${m.name}</option>`).join('');
        const yOpts = [1,2,3].map(y=>`<option value="${y}">Year ${y}</option>`).join('');
        const tr    = document.createElement('tr');
        tr.innerHTML=`<td><select name="module_id[]" style="width:100%;padding:7px 10px;border:1px solid var(--grey-200);border-radius:3px;font-size:0.85rem">${opts}</select></td><td><select name="module_year[]" style="padding:7px 10px;border:1px solid var(--grey-200);border-radius:3px;font-size:0.85rem">${yOpts}</select></td><td><button type="button" class="btn btn-outline btn-sm" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>`;
        tbody.appendChild(tr);
    }
    </script>
    <?php include __DIR__ . '/includes/admin_footer.php'; exit;
}

$programmes = $db->query("
    SELECT p.*,l.LevelName,s.Name AS LeaderName,
           (SELECT COUNT(*) FROM InterestedStudents i WHERE i.ProgrammeID=p.ProgrammeID) AS InterestCount
    FROM Programmes p
    JOIN Levels l ON p.LevelID=l.LevelID
    LEFT JOIN Staff s ON p.ProgrammeLeaderID=s.StaffID
    ORDER BY l.LevelID,p.ProgrammeName
")->fetchAll();
$levels = $db->query("SELECT * FROM Levels")->fetchAll();
$staff  = $db->query("SELECT * FROM Staff ORDER BY Name")->fetchAll();

$pageTitle = in_array($action,['create','edit']) ? ($action==='create'?'Create Programme':'Edit Programme') : 'Programmes';
include __DIR__ . '/includes/admin_header.php';

if (in_array($action,['create','edit'])): ?>
<div class="page-header">
    <div class="page-header-left">
        <div class="ku-divider-sm"></div>
        <h1><?= $action==='create'?'Create Programme':'Edit Programme' ?></h1>
        <p><?= $action==='create'?'Add a new programme.':'Update programme details.' ?></p>
    </div>
    <a href="/student_course_hub/admin/programmes.php" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Cancel</a>
</div>
<?php if (!empty($errors)): ?>
<div class="alert alert-error"><?php foreach($errors as $e) echo '<i class="bi bi-exclamation-circle"></i> '.h($e).'<br>'; ?></div>
<?php endif; ?>
<div class="form-card">
    <form method="POST" action="/student_course_hub/admin/programmes.php?action=<?= $action ?><?= $action==='edit'?'&id='.$id:'' ?>" enctype="multipart/form-data">
        <div class="form-section">
            <p class="form-section-title">Basic Information</p>
            <div class="form-group">
                <label for="ProgrammeName">Programme Name *</label>
                <input type="text" id="ProgrammeName" name="ProgrammeName" value="<?= h($formData['ProgrammeName']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="LevelID">Level *</label>
                    <select id="LevelID" name="LevelID" required>
                        <option value="">Select level…</option>
                        <?php foreach($levels as $l): ?>
                        <option value="<?= $l['LevelID'] ?>" <?= $formData['LevelID']==$l['LevelID']?'selected':'' ?>><?= h($l['LevelName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ProgrammeLeaderID">Programme Leader</label>
                    <select id="ProgrammeLeaderID" name="ProgrammeLeaderID">
                        <option value="">Select staff…</option>
                        <?php foreach($staff as $s): ?>
                        <option value="<?= $s['StaffID'] ?>" <?= $formData['ProgrammeLeaderID']==$s['StaffID']?'selected':'' ?>><?= h($s['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="Description">Description</label>
                <textarea id="Description" name="Description" rows="4"><?= h($formData['Description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="ImageFile">Programme Image</label>
                <?php if (!empty($formData['Image'])): ?>
                <div style="margin-bottom:10px">
                    <img src="<?= h($formData['Image']) ?>" alt="Current image"
                         style="height:120px;width:auto;object-fit:cover;border:1px solid var(--grey-200);border-radius:var(--radius)">
                    <p style="font-size:0.75rem;color:var(--grey-400);margin-top:4px">Current image — upload a new file to replace it.</p>
                </div>
                <?php endif; ?>
                <input type="file" id="ImageFile" name="ImageFile" accept="image/*"
                       style="width:100%;padding:8px 10px;border:1px solid var(--grey-200);border-radius:var(--radius);font-family:var(--font-sans);font-size:0.85rem;background:var(--white)">
                <input type="hidden" name="Image" value="<?= h($formData['Image'] ?? '') ?>">
                <span class="form-hint">Accepted formats: JPG, PNG, GIF, WebP. Max 5MB.</span>
            </div>
        </div>
        <div class="form-section">
            <p class="form-section-title">Visibility</p>
            <div style="display:flex;align-items:center;gap:12px">
                <label class="toggle-switch">
                    <input type="checkbox" name="Published" <?= !empty($formData['Published'])?'checked':'' ?>>
                    <span class="toggle-slider"></span>
                </label>
                <span style="font-size:0.88rem">Published — visible to prospective students</span>
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $action==='create'?'Create Programme':'Save Changes' ?></button>
            <a href="/student_course_hub/admin/programmes.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>
<div class="page-header">
    <div class="page-header-left">
        <div class="ku-divider-sm"></div>
        <h1>Programmes</h1>
        <p>Manage all degree programmes.</p>
    </div>
    <a href="/student_course_hub/admin/programmes.php?action=create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Programme</a>
</div>
<div class="table-wrap">
    <div class="table-toolbar">
        <span class="table-toolbar-title">All Programmes (<?= count($programmes) ?>)</span>
        <input type="search" class="table-search" placeholder="Search…" data-table-search="programmesTable">
    </div>
    <table id="programmesTable">
        <thead><tr><th>Programme</th><th>Level</th><th>Leader</th><th>Interest</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($programmes)): ?>
        <tr class="empty-row"><td colspan="6">No programmes found.</td></tr>
        <?php else: foreach($programmes as $prog): ?>
        <tr>
            <td><strong><?= h($prog['ProgrammeName']) ?></strong></td>
            <td><span class="badge <?= $prog['LevelID']==1?'badge-blue':'badge-orange' ?>"><?= h($prog['LevelName']) ?></span></td>
            <td class="text-muted text-small"><?= h($prog['LeaderName']??'—') ?></td>
            <td><span class="badge badge-grey"><?= $prog['InterestCount'] ?></span></td>
            <td><?= $prog['Published'] ? '<span class="badge badge-green"><i class="bi bi-circle-fill" style="font-size:0.5rem"></i> Live</span>' : '<span class="badge badge-grey"><i class="bi bi-circle" style="font-size:0.5rem"></i> Draft</span>' ?></td>
            <td>
                <div class="table-actions">
                    <a href="/student_course_hub/admin/programmes.php?action=edit&id=<?= $prog['ProgrammeID'] ?>" class="btn btn-outline btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="/student_course_hub/admin/programmes.php?action=modules&id=<?= $prog['ProgrammeID'] ?>" class="btn btn-outline btn-sm"><i class="bi bi-journal-text"></i> Modules</a>
                    <a href="/student_course_hub/admin/programmes.php?action=toggle&id=<?= $prog['ProgrammeID'] ?>" class="btn btn-outline btn-sm"><?= $prog['Published']?'<i class="bi bi-eye-slash"></i> Unpublish':'<i class="bi bi-eye"></i> Publish' ?></a>
                    <form method="POST" action="/student_course_hub/admin/programmes.php?action=delete&id=<?= $prog['ProgrammeID'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete '<?= h($prog['ProgrammeName']) ?>'?"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif;
include __DIR__ . '/includes/admin_footer.php'; ?>