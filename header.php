<?php
if (!isset($pageTitle)) {
    $pageTitle = 'pawHouse Adoption Center';
}
$basePath = $basePath ?? '.';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/png" href="<?php echo $basePath; ?>/images/logo.png">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/styles.css">

</head>
<body>
<header class="site-header">
    <a class="brand" href="<?php echo $basePath; ?>/index.php" aria-label="pawHouse home">
        <img class="logo-mark" src="<?php echo $basePath; ?>/images/logo.png" alt="" aria-hidden="true">
        <span>pawHouse</span>
    </a>
    <button class="nav-toggle" type="button" data-nav-toggle aria-label="Open navigation">
        <span></span><span></span><span></span>
    </button>
    <nav class="site-nav" data-nav>
        <a href="<?php echo $basePath; ?>/index.php">Center</a>
        <?php if (!empty($_SESSION['user_email']) && !empty($_SESSION['user_role'])): ?>
            <span style="font-weight: 800; color: var(--forest); background: var(--soft); padding: 4px 10px; border-radius: 999px; font-size: 12px; text-transform: uppercase;">
                <?php echo htmlspecialchars($_SESSION['user_role']); ?>
            </span>
            <a href="<?php echo $basePath; ?>/<?php echo $_SESSION['user_role']; ?>/dashboard.php" style="font-weight: 700;">Dashboard</a>
            <a href="<?php echo $basePath; ?>/logout.php" style="color: var(--clay); font-weight: 700;">Logout</a>
        <?php
else: ?>
            <a href="<?php echo $basePath; ?>/login.php?role=client">Client Login</a>
            <a href="<?php echo $basePath; ?>/login.php?role=employee">Employee</a>
            <a href="<?php echo $basePath; ?>/login.php?role=admin">Administrator</a>
        <?php
endif; ?>
    </nav>
</header>
<main>
