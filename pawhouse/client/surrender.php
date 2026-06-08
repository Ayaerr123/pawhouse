<?php
require __DIR__ . '/../includes/data.php';
check_access('client');
$pageTitle = 'Surrender a Pet - pawHouse';
$basePath = '..';
$clientEmail = $_SESSION['user_email'] ?? 'nadia@example.test';
$currentClient = null;
foreach ($clients as $c) {
    if (strcasecmp($c['email'], $clientEmail) === 0) {
        $currentClient = $c;
        break;
    }
}
if (!$currentClient) {
    $currentClient = $clients[0];
}
$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeSlug = trim($_POST['type_slug'] ?? '');
    $race = trim($_POST['race'] ?? '');
    $petName = trim($_POST['pet_name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $info = trim($_POST['info'] ?? '');
    $dropoffDate = trim($_POST['dropoff_date'] ?? '');
    $imagePath = '';
    if (!isset($animalTypes[$typeSlug])) {
        $error = 'The animal type you selected is not supported by the pawHouse center. Your request cannot be submitted. Please contact us if you believe this is an error.';
    }
    else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['photo']['tmp_name']);
            if (!in_array($mime, $allowedMimes, true)) {
                $error = 'Only JPEG, PNG, GIF, or WebP images are accepted as the pet photo.';
            }
            else {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'surrender_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                $uploadDir = __DIR__ . '/../images/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'images/' . $filename;
                }
                else {
                    $error = 'Failed to save the uploaded photo. Please try again.';
                }
            }
        }
        if (!$error) {
            $pdo = get_pdo();
            $catId = $animalTypes[$typeSlug]['id'];
            $stmt = $pdo->prepare('INSERT INTO surrender_requests (client_id, category_id, race, pet_name, age, sex, image_path, info, dropoff_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'Pending\')');
            $stmt->execute([
                $currentClient['id'],
                $catId,
                $race,
                $petName,
                $age,
                $sex,
                $imagePath,
                $info,
                $dropoffDate
            ]);
            $success = true;
        }
    }
}
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <p class="eyebrow">Client portal</p>
    <h1>Surrender a pet to the pawHouse center.</h1>
    <p>If you can no longer care for a pet, you may request the center to take them in. Fill in all the details below. Invalid animal types are declined immediately. Valid requests are reviewed by our administrators.</p>
</section>
<section class="section">
    <?php if ($success): ?>
        <div class="notice-success" style="margin-bottom: 24px;">
            Your surrender request has been submitted and is now awaiting review by the pawHouse team. You can track its status from your <a href="dashboard.php">client dashboard</a>.
        </div>
    <?php
endif; ?>
    <?php if ($error): ?>
        <div style="margin-bottom: 24px; padding: 16px 20px; background: #fff0f0; border: 2px solid #e15b5b; border-radius: 8px;">
            <p class="eyebrow" style="color: #c92a2a; margin: 0 0 4px;">Request Declined</p>
            <p style="margin: 0; color: #5c5c5c;"><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php
endif; ?>
    <?php if (!$success): ?>
        <form class="panel edit-form" method="post" enctype="multipart/form-data" style="max-width: 680px;">
            <h2 style="margin-top: 0;">Pet details</h2>
            <label>Animal Type <span style="color: var(--clay); font-size: 12px;">(must match a category supported by the center)</span>
                <select name="type_slug" required>
                    <option value="">Select a type</option>
                    <?php foreach ($animalTypes as $slug => $atype): ?>
                        <option value="<?php echo htmlspecialchars($slug); ?>"><?php echo htmlspecialchars($atype['label']); ?></option>
                    <?php
    endforeach; ?>
                </select>
            </label>
            <label>Race / Breed <span style="color: var(--muted); font-size: 12px;">(if race does not exist yet, it will be created)</span>
                <input name="race" placeholder="e.g. Persian, Husky, Canary..." required>
            </label>
            <label>Pet Name
                <input name="pet_name" placeholder="e.g. Fluffy" required>
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <label>Age
                    <input name="age" placeholder="e.g. 3 years, 6 months" required>
                </label>
                <label>Sex
                    <select name="sex" required>
                        <option value="">Select</option>
                        <option>Male</option>
                        <option>Female</option>
                        <option>Unknown</option>
                    </select>
                </label>
            </div>
            <label>Pet Photo <span style="color: var(--muted); font-size: 12px;">(JPEG, PNG, GIF or WebP — optional)</span>
                <div class="custom-file-upload">
                    <input type="file" id="pet-photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="upload-dropzone" onclick="document.getElementById('pet-photo').click()">
                        <svg class="upload-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span class="upload-text">Choose a photo or drag it here</span>
                        <span class="upload-hint">PNG, JPG, GIF or WEBP up to 5MB</span>
                        <span class="selected-filename" id="file-name-display" style="display: none;"></span>
                    </div>
                </div>
            </label>
            <label>Health &amp; Behavioral Information
                <textarea name="info" placeholder="Vaccinated, neutered, allergies, temperament, special care requirements..." required></textarea>
            </label>
            <label>Proposed Drop-off Date <span style="color: var(--muted); font-size: 12px;">(at least 3 days from today)</span>
                <input type="date" name="dropoff_date" min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
            </label>
            <div class="hero-actions" style="margin-top: 8px;">
                <button class="btn primary" type="submit">Submit surrender request</button>
                <a class="btn secondary" href="dashboard.php">Cancel</a>
            </div>
        </form>
    <?php
endif; ?>
</section>
<script>
document.getElementById('pet-photo').addEventListener('change', function(e) {
    const fileName = e.target.files[0] ? e.target.files[0].name : '';
    const display = document.getElementById('file-name-display');
    const hint = document.querySelector('.upload-hint');
    const text = document.querySelector('.upload-text');
    const icon = document.querySelector('.upload-icon');
    if (fileName) {
        display.innerHTML = '<svg style="width:16px;height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Selected: ' + fileName;
        display.style.display = 'inline-flex';
        hint.style.display = 'none';
        text.style.display = 'none';
        if (icon) icon.style.color = 'var(--sage)';
    } else {
        display.style.display = 'none';
        hint.style.display = 'block';
        text.style.display = 'block';
        if (icon) icon.style.color = 'var(--muted)';
    }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
