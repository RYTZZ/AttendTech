<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/header.php';
requireLogin();

$pageTitle = 'Students';
$pdo = getPDO();

$gradeStmt = $pdo->query('SELECT * FROM grade_levels ORDER BY id');
$grades = $gradeStmt->fetchAll();

$sectionStmt = $pdo->query('SELECT s.*, gl.grade_name FROM sections s JOIN grade_levels gl ON s.grade_level_id = gl.id ORDER BY gl.id, s.section_name');
$sections = $sectionStmt->fetchAll();

$filter_grade   = $_GET['grade']   ?? '';
$filter_section = $_GET['section'] ?? '';
$filter_status  = $_GET['status']  ?? '';

$where  = [];
$params = [];
if ($filter_grade)   { $where[] = 's.grade_level_id = ?'; $params[] = $filter_grade; }
if ($filter_section) { $where[] = 's.section_id = ?';     $params[] = $filter_section; }
if ($filter_status)  { $where[] = 's.status = ?';         $params[] = $filter_status; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT s.*, gl.grade_name, sec.section_name
    FROM students s
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN sections sec ON s.section_id = sec.id
    $whereSQL
    ORDER BY s.full_name ASC
");
$stmt->execute($params);
$students = $stmt->fetchAll();

$addLink  = e(BASE_URL . '/admin/add_student.php');
$extraScripts = ['<script>
$(document).ready(function() {
    $("#studentsTable").DataTable({
        pageLength: 25,
        order: [[1, "asc"]],
        columnDefs: [
            { orderable: false, targets: [0, 8] }
        ],
        language: {
            emptyTable: "No students found. <a href=\"' . $addLink . '\">Add one now</a>.",
            zeroRecords: "No matching students found."
        }
    });
});
</script>'];
?>
<?php include BASE_PATH . '/includes/sidebar.php'; ?>
<div class="main-wrapper">
<?php include BASE_PATH . '/includes/navbar.php'; ?>
<div class="page-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="font-head fw-700 mb-1">Student Management</h4>
            <p style="color:var(--text-3);font-size:13px;margin:0;"><?= count($students) ?> student(s) found</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/add_student.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Student
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body-custom">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Grade Level</label>
                    <select name="grade" class="form-select form-select-sm">
                        <option value="">All Grades</option>
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= $filter_grade == $g['id'] ? 'selected' : '' ?>><?= e($g['grade_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Section</label>
                    <select name="section" class="form-select form-select-sm">
                        <option value="">All Sections</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?= $sec['id'] ?>" <?= $filter_section == $sec['id'] ? 'selected' : '' ?>><?= e($sec['grade_name']) ?> — <?= e($sec['section_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="Active"   <?= $filter_status === 'Active'   ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $filter_status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body-custom p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="studentsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>LRN</th>
                            <th>Gender</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $s): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar">
                                            <?php if ($s['photo']): ?>
                                                <img src="<?= BASE_URL ?>/<?= e($s['photo']) ?>" alt="">
                                            <?php else: ?>
                                                <i class="bi bi-person-fill"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="student-name"><?= e($s['full_name']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><code><?= e($s['lrn']) ?></code></td>
                                <td><?= e($s['gender']) ?></td>
                                <td><?= e($s['grade_name']) ?></td>
                                <td><?= e($s['section_name']) ?></td>
                                <td><?= e($s['contact_number'] ?: '—') ?></td>
                                <td>
                                    <span class="badge-status badge-<?= strtolower($s['status']) ?>">
                                        <?= e($s['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="<?= BASE_URL ?>/admin/print_id.php?id=<?= $s['id'] ?>" target="_blank"
                                            class="btn-icon btn btn-sm btn-outline-secondary" title="Print ID">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/edit_student.php?id=<?= $s['id'] ?>"
                                            class="btn-icon btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn-icon btn btn-sm btn-outline-danger" title="Delete"
                                            onclick="confirmDelete(BASE_URL+'/ajax/delete_student.php?id=<?= $s['id'] ?>', '<?= e(addslashes($s['full_name'])) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
<?php include BASE_PATH . '/includes/footer.php'; ?>
