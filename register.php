<?php
require __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regName            = trim($_POST['name'] ?? '');
    $regEmail           = trim($_POST['email'] ?? '');
    $regPhone           = trim($_POST['phone'] ?? '');
    $regPassword        = trim($_POST['password'] ?? '');
    $regPasswordConfirm = trim($_POST['password_confirm'] ?? '');
    $regHousing         = trim($_POST['housing'] ?? '');

    if ($regPassword !== $regPasswordConfirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($regPassword) < 4) {
        $error = 'Password must be at least 4 characters.';
    } else {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$regEmail]);
        if ($stmt->fetch()) {
            $error = 'An account with this email address already exists.';
        } else {
            $hash = password_hash($regPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, password_visible_to_admin, role)
                 VALUES (?, ?, ?, ?, \'client\')'
            );
            $stmt->execute([$regName, $regEmail, $hash, $regPassword]);
            $userId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare(
                'INSERT INTO clients (user_id, phone, housing_type, became_client_at, notes)
                 VALUES (?, ?, ?, CURDATE(), \'New client\')'
            );
            $stmt->execute([$userId, $regPhone, $regHousing]);
            $_SESSION['user_email'] = $regEmail;
            $_SESSION['user_role']  = 'client';
            $_SESSION['user_name']  = $regName;
            $_SESSION['user_id']    = $userId;
            header('Location: client/dashboard.php');
            exit;
        }
    }
}
$pageTitle = 'Create Client Account - pawHouse';
$basePath  = '.';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-shell">
    <form class="auth-card" action="register.php" method="post" data-register-form>
        <p class="eyebrow">Client registration</p>
        <h1>Create your account</h1>
        <?php if ($error): ?>
            <div class="form-error" style="background:#fff0f0;border:1px solid #e15b5b;padding:12px 16px;border-radius:6px;margin-bottom:18px;font-size:14px;color:#c92a2a;font-weight:700;line-height:1.4;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <label>Full name
            <input type="text" name="name" placeholder="Your full name" required>
        </label>
        <label>Email
            <input type="email" name="email" placeholder="you@example.com" required>
        </label>
        <label>Phone
            <input type="tel" name="phone" placeholder="+212 ..." required>
        </label>
        <label>Password
            <input type="password" name="password" placeholder="Choose your password" minlength="4" required>
        </label>
        <label>Confirm password
            <input type="password" name="password_confirm" placeholder="Repeat your password" minlength="4" required>
        </label>
        <label>Housing type
            <select name="housing" required>
                <option value="">Choose one</option>
                <option>Apartment</option>
                <option>House with garden</option>
                <option>Shared family home</option>
            </select>
        </label>
        <button class="btn primary full" type="submit">Create account</button>
        <p class="form-error" data-password-error hidden>Passwords must match.</p>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>