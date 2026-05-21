<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/header.php';
requireLogin();

$pageTitle = 'Add Student';
$pdo = getPDO();

$grades = $pdo->query('SELECT * FROM grade_levels ORDER BY id')->fetchAll();
$sections = $pdo->query('SELECT s.*, gl.grade_name FROM sections s JOIN grade_levels gl ON s.grade_level_id = gl.id ORDER BY gl.id, s.section_name')->fetchAll();

$errors = [];
$success = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
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

    if (!$formData['lrn'] || strlen($formData['lrn']) !== 12) $errors[] = 'LRN must be exactly 12 digits.';
    if (!$formData['full_name']) $errors[] = 'Full name is required.';
    if (!in_array($formData['gender'], ['Male', 'Female'])) $errors[] = 'Gender is required.';
    if (!$formData['grade_level_id']) $errors[] = 'Grade level is required.';
    if (!$formData['section_id']) $errors[] = 'Section is required.';

    $checkStmt = $pdo->prepare('SELECT id FROM students WHERE lrn = ?');
    $checkStmt->execute([$formData['lrn']]);
    if ($checkStmt->fetch()) $errors[] = 'LRN already exists.';

    $photoPath = '';
    if (!empty($_FILES['photo']['name'])) {
        $upload = uploadStudentPhoto($_FILES['photo']);
        if ($upload['success']) {
            $photoPath = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    }

    if (empty($errors)) {
        $qrPath = generateQRCode($formData['lrn']);
        $stmt = $pdo->prepare("
            INSERT INTO students (lrn, full_name, gender, grade_level_id, section_id, contact_number, address, parent_name, parent_contact, photo, qr_code, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $formData['lrn'], $formData['full_name'], $formData['gender'],
            $formData['grade_level_id'], $formData['section_id'],
            $formData['contact_number'], $formData['address'],
            $formData['parent_name'], $formData['parent_contact'],
            $photoPath, $qrPath, $formData['status']
        ]);
        $newId = $pdo->lastInsertId();
        header('Location: /admin/students.php?added=1');
        exit;
    }
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
            <h4 class="font-head fw-700 mb-0">Add New Student</h4>
            <p style="color:var(--text-3);font-size:13px;margin:0;">Fill in the student details below</p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
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
                                    placeholder="12-digit LRN" value="<?= e($formData['lrn'] ?? '') ?>" required>
                                <div class="form-text">Learner Reference Number (12 digits)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control"
                                    placeholder="Last Name, First Name MI." value="<?= e($formData['full_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select gender</option>
                                    <option value="Male" <?= ($formData['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($formData['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                                <select name="grade_level_id" id="gradeSelect" class="form-select" required>
                                    <option value="">Select grade</option>
                                    <?php foreach ($grades as $g): ?>
                                        <option value="<?= $g['id'] ?>" <?= ($formData['grade_level_id'] ?? 0) == $g['id'] ? 'selected' : '' ?>><?= e($g['grade_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Section <span class="text-danger">*</span></label>
                                <select name="section_id" id="sectionSelect" class="form-select" required>
                                    <option value="">Select section</option>
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?= $sec['id'] ?>" data-grade="<?= $sec['grade_level_id'] ?>"
                                            <?= ($formData['section_id'] ?? 0) == $sec['id'] ? 'selected' : '' ?>>
                                            <?= e($sec['section_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                    placeholder="09XXXXXXXXX" value="<?= e($formData['contact_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Active" <?= ($formData['status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= ($formData['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Complete address"><?= e($formData['address'] ?? '') ?></textarea>
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
                                <input type="text" name="parent_name" class="form-control"
                                    placeholder="Full name" value="<?= e($formData['parent_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Contact</label>
                                <input type="text" name="parent_contact" class="form-control"
                                    placeholder="09XXXXXXXXX" value="<?= e($formData['parent_contact'] ?? '') ?>">
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
                            <i class="bi bi-person" style="font-size:48px;color:var(--text-3);"></i>
                        </div>
                        <input type="file" name="photo" id="photoInput" accept="image/*" class="form-control form-control-sm">
                        <div class="form-text">JPG, PNG, WebP. Max 5MB.</div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header-custom">
                        <h5 class="card-title"><i class="bi bi-qr-code"></i> QR Code</h5>
                    </div>
                    <div class="card-body-custom text-center">
                        <div style="color:var(--text-3);font-size:13px;">
                            <i class="bi bi-magic" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.4;"></i>
                            QR code will be auto-generated using the student's LRN after saving.
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Student
                    </button>
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
    const sectionSelect = document.getElementById('sectionSelect');
    Array.from(sectionSelect.options).forEach(opt => {
        if (opt.value === '') { opt.style.display = ''; return; }
        opt.style.display = opt.dataset.grade === gradeId ? '' : 'none';
    });
    sectionSelect.value = '';
});
</script>
<?php include BASE_PATH . '/includes/footer.php'; ?>
