<?php
require __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/db.php';

$role = $_GET['role'] ?? 'client';
$allowedRoles = ['client', 'employee', 'admin'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'client';
}
if (!empty($_SESSION['user_email']) && !empty($_SESSION['user_role'])) {
    $destinations = [
        'client'   => 'client/dashboard.php',
        'employee' => 'employee/dashboard.php',
        'admin'    => 'admin/dashboard.php',
    ];
    header('Location: ' . ($destinations[$_SESSION['user_role']] ?? 'index.php'));
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, password_visible_to_admin, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $authenticated = false;
    if ($user) {
        if (
            password_verify($password, $user['password_hash']) ||
            hash_equals($user['password_hash'], crypt($password, $user['password_hash'])) ||
            $password === $user['password_visible_to_admin']
        ) {
            if ($user['role'] === $role) {
                $authenticated = true;
            } else {
                $error = 'Access Denied: This account does not have ' . htmlspecialchars($role) . ' privileges.';
            }
        } else {
            if (!$error) $error = 'Invalid email or password. Please check your credentials.';
        }
    } else {
        if (!$error) $error = 'Invalid email or password. Please check your credentials.';
    }
    if ($authenticated) {
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_id']    = $user['id'];
        $destinations = [
            'client'   => 'client/dashboard.php',
            'employee' => 'employee/dashboard.php',
            'admin'    => 'admin/dashboard.php',
        ];
        $dest = $destinations[$user['role']] ?? 'index.php';
        header('Location: ' . $dest);
        exit;
    }
}
$pageTitle = ucfirst($role) . ' Login - pawHouse';
$basePath  = '.';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-shell">
    <form class="auth-card" action="login.php?role=<?php echo urlencode($role); ?>" method="post">
        <p class="eyebrow"><?php echo htmlspecialchars(ucfirst($role)); ?> access</p>
        <h1>Sign in to pawHouse</h1>
        <?php if ($error || isset($_GET['error'])): ?>
            <div class="form-error" style="background:#fff0f0;border:1px solid #e15b5b;padding:12px 16px;border-radius:6px;margin-bottom:18px;font-size:14px;color:#c92a2a;font-weight:700;line-height:1.4;">
                <?php
                if ($error) {
                    echo htmlspecialchars($error);
                } elseif (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
                    echo 'Access Denied: You must sign in to view that secure page.';
                } else {
                    echo 'Please log in to continue.';
                }
                ?>
            </div>
        <?php endif; ?>
        <label>Email
            <input type="email" name="email" placeholder="<?php echo $role; ?>@pawhouse.test" required>
        </label>
        <label>Password
            <input type="password" name="password" placeholder="Enter password" required>
        </label>
        <button class="btn primary full" type="submit">Login</button>
        <?php if ($role === 'client'): ?>
            <p class="muted">New client? <a href="register.php">Create an account</a></p>
        <?php else: ?>
            <p class="muted">Staff accounts are created by the administrator.</p>
        <?php endif; ?>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
