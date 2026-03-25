<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireAdmin();

$db=$getDB=getDB(); $action=$_GET['action']??'list'; $id=isset($_GET['id'])?(int)$_GET['id']:0;

if($action==='delete'&&$id&&$_SERVER['REQUEST_METHOD']==='POST'){
    $db->prepare("DELETE FROM Staff WHERE StaffID=?")->execute([$id]);
    flashSet('success','Staff member removed.'); redirect(BASE_URL.'/admin/staff.php');
}
$errors=[]; $formData=['Name'=>'','Title'=>'','Bio'=>'','Department'=>''];
if($action==='edit'&&$id){
    $stmt=$db->prepare("SELECT * FROM Staff WHERE StaffID=?"); $stmt->execute([$id]);
    $editStaff=$stmt->fetch();
    if(!$editStaff){flashSet('error','Not found.');redirect(BASE_URL.'/admin/staff.php');}
    $formData=array_merge($formData,$editStaff);
}
if($_SERVER['REQUEST_METHOD']==='POST'&&in_array($action,['create','edit'])){
    $formData=['Name'=>sanitize($_POST['Name']??''),'Title'=>sanitize($_POST['Title']??''),'Bio'=>sanitize($_POST['Bio']??''),'Department'=>sanitize($_POST['Department']??'')];
    if(empty($formData['Name']))$errors[]='Name is required.';
    if(empty($errors)){
        if($action==='create'){$db->prepare("INSERT INTO Staff (Name,Title,Bio,Department) VALUES (?,?,?,?)")->execute(array_values($formData));flashSet('success','Staff member created.');}
        else{$db->prepare("UPDATE Staff SET Name=?,Title=?,Bio=?,Department=? WHERE StaffID=?")->execute([...array_values($formData),$id]);flashSet('success','Staff updated.');}
        redirect(BASE_URL.'/admin/staff.php');
    }
}

if(in_array($action,['create','edit'])){
    $pageTitle=$action==='create'?'Add Staff':'Edit Staff';
    include __DIR__.'/includes/admin_header.php'; ?>
    <div class="page-header">
        <div class="page-header-left"><div class="ku-divider-sm"></div><h1><?= $pageTitle ?></h1></div>
        <a href="/student_course_hub/admin/staff.php" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Cancel</a>
    </div>
    <?php if(!empty($errors)):?><div class="alert alert-error"><?php foreach($errors as $e) echo h($e).'<br>';?></div><?php endif;?>
    <div class="form-card">
        <form method="POST" action="/student_course_hub/admin/staff.php?action=<?= $action ?><?= $action==='edit'?'&id='.$id:'' ?>">
            <div class="form-row">
                <div class="form-group"><label>Full Name *</label><input type="text" name="Name" value="<?= h($formData['Name']) ?>" required></div>
                <div class="form-group"><label>Job Title</label><input type="text" name="Title" value="<?= h($formData['Title']??'') ?>" placeholder="e.g. Senior Lecturer"></div>
            </div>
            <div class="form-group"><label>Department</label><input type="text" name="Department" value="<?= h($formData['Department']??'') ?>" placeholder="e.g. School of Computing"></div>
            <div class="form-group"><label>Biography</label><textarea name="Bio" rows="4"><?= h($formData['Bio']??'') ?></textarea></div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $action==='create'?'Add Staff Member':'Save Changes' ?></button>
                <a href="/student_course_hub/admin/staff.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    <?php include __DIR__.'/includes/admin_footer.php'; exit;
}

$staff = $db->query("
    SELECT s.*,
           COUNT(DISTINCT m.ModuleID)    AS ModuleCount,
           COUNT(DISTINCT p.ProgrammeID) AS ProgrammeCount
    FROM Staff s
    LEFT JOIN Modules m    ON m.ModuleLeaderID    = s.StaffID
    LEFT JOIN Programmes p ON p.ProgrammeLeaderID = s.StaffID
    GROUP BY s.StaffID
    ORDER BY COALESCE(NULLIF(s.Department,''),'ZZZ'), s.Name
")->fetchAll();

// Fetch modules led by each staff member
$modulesByStaff = [];
$moduleRows = $db->query("SELECT ModuleLeaderID, ModuleID, ModuleName FROM Modules WHERE ModuleLeaderID IS NOT NULL ORDER BY ModuleName")->fetchAll();
foreach ($moduleRows as $row) {
    $modulesByStaff[$row['ModuleLeaderID']][] = [
        'id'   => $row['ModuleID'],
        'name' => $row['ModuleName']
    ];
}

// Fetch programmes each staff member leads directly
$programmesByStaff = [];
$progRows = $db->query("SELECT ProgrammeLeaderID, ProgrammeName FROM Programmes WHERE ProgrammeLeaderID IS NOT NULL ORDER BY ProgrammeName")->fetchAll();
foreach ($progRows as $row) {
    $programmesByStaff[$row['ProgrammeLeaderID']][] = $row['ProgrammeName'];
}

// Fetch programmes connected to each staff member THROUGH their modules
// e.g. Dr. Alice leads Module 1 -> Module 1 is in BSc CS, BSc SE etc -> she teaches those programmes
$teachingInByStaff = [];
$teachingRows = $db->query("
    SELECT DISTINCT m.ModuleLeaderID, p.ProgrammeName, p.ProgrammeID
    FROM Modules m
    JOIN ProgrammeModules pm ON pm.ModuleID = m.ModuleID
    JOIN Programmes p ON p.ProgrammeID = pm.ProgrammeID
    WHERE m.ModuleLeaderID IS NOT NULL
    ORDER BY p.ProgrammeName
")->fetchAll();
foreach ($teachingRows as $row) {
    $teachingInByStaff[$row['ModuleLeaderID']][$row['ProgrammeID']] = $row['ProgrammeName'];
}

$pageTitle='Staff'; include __DIR__.'/includes/admin_header.php'; ?>

<div class="page-header">
    <div class="page-header-left">
        <div class="ku-divider-sm"></div>
        <h1>Staff</h1>
        <p>Manage academic staff grouped by department — <?= count($staff) ?> members total.</p>
    </div>
    <a href="/student_course_hub/admin/staff.php?action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Staff
    </a>
</div>

<?php if (empty($staff)): ?>
<div class="table-wrap">
    <tr class="empty-row"><td colspan="5">No staff found.</td></tr>
</div>

<?php else:
    // Group staff by department
    $grouped = [];
    foreach ($staff as $s) {
        $dept = !empty($s['Department']) ? $s['Department'] : 'Unassigned';
        $grouped[$dept][] = $s;
    }
    ksort($grouped);
?>

<?php foreach ($grouped as $department => $members): ?>
<div style="margin-bottom:32px">

    <!-- Department Header -->
    <div style="display:flex; align-items:center; justify-content:space-between;
                padding:12px 18px; background:var(--ku-red); color:var(--white);
                margin-bottom:1px">
        <div style="display:flex; align-items:center; gap:10px">
            <i class="bi bi-building" style="font-size:1rem; opacity:0.8"></i>
            <strong style="font-family:var(--font-serif); font-size:0.95rem">
                <?= h($department) ?>
            </strong>
        </div>
        <span style="font-size:0.75rem; opacity:0.75; font-weight:500">
            <?= count($members) ?> member<?= count($members) !== 1 ? 's' : '' ?>
        </span>
    </div>

    <!-- Staff Table for this department -->
    <div class="table-wrap" style="border-top:none">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Title</th>
                    <th>Modules Led</th>
                    <th>Connected Programmes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($members as $s): ?>
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:10px">
                        <div style="width:34px; height:34px; border-radius:50%;
                                    background:var(--ku-red-light); color:var(--ku-red);
                                    display:flex; align-items:center; justify-content:center;
                                    font-weight:700; font-size:0.78rem; flex-shrink:0">
                            <?php
                            $initials = '';
                            foreach (explode(' ', $s['Name']) as $part)
                                $initials .= strtoupper(substr($part, 0, 1));
                            echo h(substr($initials, 0, 2));
                            ?>
                        </div>
                        <strong style="font-size:0.88rem"><?= h($s['Name']) ?></strong>
                    </div>
                </td>
                <td class="text-small text-muted">
                    <?= !empty($s['Title']) ? h($s['Title']) : '<span style="color:var(--grey-400)">—</span>' ?>
                </td>
                <td>
                    <?php if (!empty($modulesByStaff[$s['StaffID']])): ?>
                    <div style="display:flex; flex-direction:column; gap:3px">
                        <?php foreach ($modulesByStaff[$s['StaffID']] as $mod): ?>
                        <span style="display:inline-flex; align-items:center; gap:4px;
                                     background:var(--info-bg); color:var(--info);
                                     font-size:0.72rem; font-weight:600;
                                     padding:2px 8px; border-radius:2px;">
                            <i class="bi bi-journal-text"></i> <?= h($mod['name']) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <span style="color:var(--grey-400); font-size:0.78rem; font-style:italic">No modules</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    // Programmes they directly lead
                    $directProgs   = $programmesByStaff[$s['StaffID']] ?? [];
                    // Programmes they teach in via modules
                    $teachingProgs = $teachingInByStaff[$s['StaffID']] ?? [];
                    ?>
                    <?php if (!empty($directProgs) || !empty($teachingProgs)): ?>
                    <div style="display:flex; flex-direction:column; gap:3px">
                        <?php foreach ($directProgs as $progName): ?>
                        <span style="display:inline-flex; align-items:center; gap:4px;
                                     background:var(--ku-red); color:var(--white);
                                     font-size:0.72rem; font-weight:600;
                                     padding:2px 8px; border-radius:2px;"
                              title="Programme Leader">
                            <i class="bi bi-mortarboard-fill"></i> <?= h($progName) ?>
                        </span>
                        <?php endforeach; ?>
                        <?php foreach ($teachingProgs as $pid => $progName):
                            // Skip if already shown as direct leader
                            if (in_array($progName, $directProgs)) continue; ?>
                        <span style="display:inline-flex; align-items:center; gap:4px;
                                     background:var(--ku-red-light); color:var(--ku-red);
                                     font-size:0.72rem; font-weight:600;
                                     padding:2px 8px; border-radius:2px;"
                              title="Teaches module in this programme">
                            <i class="bi bi-mortarboard"></i> <?= h($progName) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <span style="color:var(--grey-400); font-size:0.78rem; font-style:italic">No programmes</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="/student_course_hub/admin/staff.php?action=edit&id=<?= $s['StaffID'] ?>"
                           class="btn btn-outline btn-sm">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form method="POST" action="/student_course_hub/admin/staff.php?action=delete&id=<?= $s['StaffID'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Remove '<?= h($s['Name']) ?>' from <?= h($department) ?>?">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; endif; ?>

<?php include __DIR__.'/includes/admin_footer.php'; ?>