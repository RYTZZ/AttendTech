<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/header.php';
requireLogin();

$pageTitle = 'Edit Student';
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin/students.php'); exit; }

$studentStmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$studentStmt->execute([$id]);
$student = $studentStmt->fetch();
if (!$student) { header('Location: /admin/students.php'); exit; }

$grades = $pdo->query('SELECT * FROM grade_levels ORDER BY id')->fetchAll();
$sections = $pdo->query('SELECT s.*, gl.grade_name FROM sections s JOIN grade_levels gl ON s.grade_level_id = gl.id ORDER BY gl.id, s.section_name')->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'lrn' => trim($_POST['lrn'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'grade_level_id' => (int)($_POST['grade_level_id'] ?? 0),
        'section_id' => (int)($_POST['section_id'] ?? 0),
        'contact_number' => trim($_POST['contact_number'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'parent_name' => trim($_POST['parent_name'] ?? ''),
        'parent_contact' => trim($_POST['parent_contact'] ?? ''),
        'status' => $_POST['status'] ?? 'Active',
    ];

    if (!$data['lrn'] || strlen($data['lrn']) !== 12) $errors[] = 'LRN must be exactly 12 digits.';
    if (!$data['full_name']) $errors[] = 'Full name is required.';
    if (!in_array($data['gender'], ['Male', 'Female'])) $errors[] = 'Gender is required.';
    if (!$data['grade_level_id']) $errors[] = 'Grade level is required.';
    if (!$data['section_id']) $errors[] = 'Section is required.';

    $checkStmt = $pdo->prepare('SELECT id FROM students WHERE lrn = ? AND id != ?');
    $checkStmt->execute([$data['lrn'], $id]);
    if ($checkStmt->fetch()) $errors[] = 'LRN already used by another student.';

    $photoPath = $student['photo'];
    if (!empty($_FILES['photo']['name'])) {
        $upload = uploadStudentPhoto($_FILES['photo']);
        if ($upload['success']) {
            $photoPath = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    }

    $qrPath = $student['qr_code'];
    if ($data['lrn'] !== $student['lrn']) {
        $qrPath = generateQRCode($data['lrn']);
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE students SET lrn=?, full_name=?, gender=?, grade_level_id=?, section_id=?,
            contact_number=?, address=?, parent_name=?, parent_contact=?, photo=?, qr_code=?, status=?
            WHERE id=?
        ");
        $stmt->execute([
            $data['lrn'], $data['full_name'], $data['gender'],
            $data['grade_level_id'], $data['section_id'],
            $data['contact_number'], $data['address'],
            $data['parent_name'], $data['parent_contact'],
            $photoPath, $qrPath, $data['status'], $id
        ]);
        header('Location: /admin/students.php?updated=1');
        exit;
    }

    $student = array_merge($student, $data);
}
?>
<?php include BASE_PATH . '/includes/sidebar.php'; ?>
<div class="main-wrapper">
<?php include BASE_PATH . '/includes/navbar.php'; ?>
<div class="page-content">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="font-head fw-700 mb-0">Edit Student</h4>
            <p style="color:var(--text-3);font-size:13px;margin:0;"><?= e($student['full_name']) ?></p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php foreach ($errors as $er): ?><div><?= htmlspecialchars($er) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header-custom">
                        <h5 class="card-title"><i class="bi bi-person-vcard"></i> Personal Information</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">LRN <span class="text-danger">*</span></label>
                                <input type="text" name="lrn" class="form-control" maxlength="12" pattern="\d{12}"
                                    value="<?= e($student['lrn']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control"
                                    value="<?= e($student['full_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                                <select name="grade_level_id" id="gradeSelect" class="form-select" required>
                                    <?php foreach ($grades as $g): ?>
                                        <option value="<?= $g['id'] ?>" <?= $student['grade_level_id'] == $g['id'] ? 'selected' : '' ?>><?= e($g['grade_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Section <span class="text-danger">*</span></label>
                                <select name="section_id" id="sectionSelect" class="form-select" required>
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?= $sec['id'] ?>" data-grade="<?= $sec['grade_level_id'] ?>"
                                            <?= $student['section_id'] == $sec['id'] ? 'selected' : '' ?>>
                                            <?= e($sec['section_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                    value="<?= e($student['contact_number']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Active" <?= $student['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $student['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?= e($student['address']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header-custom">
                        <h5 class="card-title"><i class="bi bi-people"></i> Parent/Guardian Information</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Name</label>
                                <input type="text" name="parent_name" class="form-control" value="<?= e($student['parent_name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Contact</label>
                                <input type="text" name="parent_contact" class="form-control" value="<?= e($student['parent_contact']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header-custom">
                        <h5 class="card-title"><i class="bi bi-image"></i> Student Photo</h5>
                    </div>
                    <div class="card-body-custom text-center">
                        <div style="width:120px;height:120px;border-radius:12px;border:2px dashed var(--border);
                            display:flex;align-items:center;justify-content:center;
                            margin:0 auto 16px;overflow:hidden;background:var(--surface2);" id="photoPreview">
                            <?php if ($student['photo']): ?>
                                <img src="<?= BASE_URL ?>/<?= e($student['photo']) ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <i class="bi bi-person" style="font-size:48px;color:var(--text-3);"></i>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="photo" id="photoInput" accept="image/*" class="form-control form-control-sm">
                        <div class="form-text">Leave empty to keep existing photo.</div>
                    </div>
                </div>

                <?php if ($student['qr_code']): ?>
                <div class="card mt-4">
                    <div class="card-header-custom">
                        <h5 class="card-title"><i class="bi bi-qr-code"></i> QR Code</h5>
                    </div>
                    <div class="card-body-custom text-center">
                        <img src="<?= BASE_URL ?>/<?= e($student['qr_code']) ?>" alt="QR" style="width:120px;height:120px;">
                        <div class="mt-2">
                            <a href="<?= BASE_URL ?>/<?= e($student['qr_code']) ?>" download class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download me-1"></i>Download QR
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-3 d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Update Student
                    </button>
                    <a href="<?= BASE_URL ?>/admin/print_id.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-secondary">
                        <i class="bi bi-printer me-1"></i> Print ID Card
                    </a>
                    <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>

</div>
</div>
<script>
document.getElementById('photoInput').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreview').innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(this.files[0]);
    }
});
document.getElementById('gradeSelect').addEventListener('change', function() {
    const gradeId = this.value;
    const sel = document.getElementById('sectionSelect');
    Array.from(sel.options).forEach(opt => {
        opt.style.display = !opt.value || opt.dataset.grade === gradeId ? '' : 'none';
    });
    sel.value = '';
});
</script>
<?php include BASE_PATH . '/includes/footer.php'; ?>
