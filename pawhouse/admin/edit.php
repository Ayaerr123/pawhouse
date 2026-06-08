<?php
require __DIR__ . '/../includes/data.php';
check_access('admin');

$entity = $_GET['entity'] ?? '';
$id = $_GET['id'] ?? '';
$breedSlug = $_GET['breed'] ?? '';
$pageTitle = 'Edit Record - pawHouse';
$basePath = '..';

function clean_post_value(string $key): string
{
    return trim($_POST[$key] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity = $_POST['entity'] ?? $entity;
    $id = $_POST['id'] ?? $id;
    $breedSlug = $_POST['breed'] ?? $breedSlug;
    if ($entity === 'client' && isset($clients[(int) $id])) {
        $_SESSION['admin_edits']['clients'][(int) $id] = [
            'name' => clean_post_value('name'),
            'email' => clean_post_value('email'),
            'password' => clean_post_value('password'),
            'joined' => clean_post_value('joined'),
            'adopted' => (int) clean_post_value('adopted'),
            'returned' => (int) clean_post_value('returned'),
            'status' => clean_post_value('status'),
        ];
    }
    if ($entity === 'employee' && isset($employees[(int) $id])) {
        $quit = clean_post_value('quit');
        $_SESSION['admin_edits']['employees'][(int) $id] = [
            'name' => clean_post_value('name'),
            'age' => (int) clean_post_value('age'),
            'role' => clean_post_value('role'),
            'started' => clean_post_value('started'),
            'quit' => $quit === '' ? null : $quit,
        ];
    }
    if ($entity === 'category' && isset($animalTypes[$id])) {
        $_SESSION['admin_edits']['animalTypes'][$id] = [
            'label' => clean_post_value('label'),
            'description' => clean_post_value('description'),
            'adopted' => (int) clean_post_value('adopted'),
            'image' => clean_post_value('image'),
        ];
    }

    if ($entity === 'pet' && isset($pets[$breedSlug][(int) $id])) {
        $_SESSION['admin_edits']['pets'][$breedSlug][(int) $id] = [
            'name' => clean_post_value('name'),
            'age' => clean_post_value('age'),
            'sex' => clean_post_value('sex'),
            'image' => clean_post_value('image'),
        ];
    }

    header('Location: dashboard.php?saved=1');
    exit;
}

$record = null;
$formTitle = 'Edit record';

if ($entity === 'client' && isset($clients[(int) $id])) {
    $record = $clients[(int) $id];
    $formTitle = 'Edit client';
}

if ($entity === 'employee' && isset($employees[(int) $id])) {
    $record = $employees[(int) $id];
    $formTitle = 'Edit employee';
}

if ($entity === 'category' && isset($animalTypes[$id])) {
    $record = $animalTypes[$id];
    $formTitle = 'Edit animal category';
}

if ($entity === 'pet' && isset($pets[$breedSlug][(int) $id])) {
    $record = $pets[$breedSlug][(int) $id];
    $formTitle = 'Edit animal';
}

require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <p class="eyebrow">Administrator edit</p>
    <h1><?php echo htmlspecialchars($formTitle); ?></h1>
    <p>Changes are saved in your current PHP session for this prototype.</p>
</section>

<section class="section">
    <?php if (!$record): ?>
        <article class="panel">
            <h2>Record not found</h2>
            <p class="muted">Return to the administrator dashboard and choose a valid record.</p>
            <a class="btn small" href="dashboard.php">Back to dashboard</a>
        </article>
    <?php else: ?>
        <form class="panel edit-form" method="post">
            <input type="hidden" name="entity" value="<?php echo htmlspecialchars($entity); ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $id); ?>">
            <input type="hidden" name="breed" value="<?php echo htmlspecialchars($breedSlug); ?>">

            <?php if ($entity === 'client'): ?>
                <label>Name <input name="name" value="<?php echo htmlspecialchars($record['name']); ?>" required></label>
                <label>Email <input type="email" name="email" value="<?php echo htmlspecialchars($record['email']); ?>" required></label>
                <label>Password <input name="password" value="<?php echo htmlspecialchars($record['password']); ?>" required></label>
                <label>Client since <input type="date" name="joined" value="<?php echo htmlspecialchars($record['joined']); ?>" required></label>
                <label>Pets adopted <input type="number" name="adopted" value="<?php echo (int) $record['adopted']; ?>" min="0" required></label>
                <label>Returns <input type="number" name="returned" value="<?php echo (int) $record['returned']; ?>" min="0" required></label>
                <label>Treatment status <textarea name="status" required><?php echo htmlspecialchars($record['status']); ?></textarea></label>
            <?php endif; ?>

            <?php if ($entity === 'employee'): ?>
                <label>Name <input name="name" value="<?php echo htmlspecialchars($record['name']); ?>" required></label>
                <label>Age <input type="number" name="age" value="<?php echo (int) $record['age']; ?>" min="18" required></label>
                <label>Role <input name="role" value="<?php echo htmlspecialchars($record['role']); ?>" required></label>
                <label>Started working <input type="date" name="started" value="<?php echo htmlspecialchars($record['started']); ?>" required></label>
                <label>Quit date <input type="date" name="quit" value="<?php echo htmlspecialchars($record['quit'] ?? ''); ?>"></label>
            <?php endif; ?>

            <?php if ($entity === 'category'): ?>
                <label>Category name <input name="label" value="<?php echo htmlspecialchars($record['label']); ?>" required></label>
                <label>Description <textarea name="description" required><?php echo htmlspecialchars($record['description']); ?></textarea></label>
                <label>Adopted count <input type="number" name="adopted" value="<?php echo (int) $record['adopted']; ?>" min="0" required></label>
                <label>Image URL <input name="image" value="<?php echo htmlspecialchars($record['image']); ?>" required></label>
            <?php endif; ?>

            <?php if ($entity === 'pet'): ?>
                <label>Name <input name="name" value="<?php echo htmlspecialchars($record['name']); ?>" required></label>
                <label>Age <input name="age" value="<?php echo htmlspecialchars($record['age']); ?>" required></label>
                <label>Sex
                    <select name="sex" required>
                        <?php foreach (['Male', 'Female', 'Unknown'] as $sex): ?>
                            <option <?php echo $record['sex'] === $sex ? 'selected' : ''; ?>><?php echo htmlspecialchars($sex); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Image URL <input name="image" value="<?php echo htmlspecialchars($record['image']); ?>" required></label>
            <?php endif; ?>

            <div class="hero-actions">
                <button class="btn primary" type="submit">Save changes</button>
                <a class="btn secondary" href="dashboard.php">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</section> 
