<?php
require __DIR__ . '/../includes/data.php';
check_access('client');
$type = $_GET['type'] ?? 'cats';
if (!isset($animalTypes[$type])) {
    $type = 'cats';
}
$animal = $animalTypes[$type];
$pageTitle = $animal['label'] . ' - pawHouse';
$basePath = '..';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro image-intro" style="background-image: linear-gradient(90deg, rgba(17,24,39,.86), rgba(17,24,39,.35)), url('<?php echo htmlspecialchars(resolve_image($animal['image'])); ?>')">
    <p class="eyebrow"><?php echo htmlspecialchars($animal['label']); ?></p>
    <h1>Available races and adoption facts.</h1>
    <p><?php echo htmlspecialchars($animal['description']); ?></p>
</section>

<section class="section">
    <div class="breed-grid">
        <?php foreach ($breeds[$type] as $breed):
            if (count($pets[$breed['slug']] ?? []) === 0) continue;
        ?>
            <article class="breed-card race-card">
                <a class="race-photo-button" href="breed.php?type=<?php echo urlencode($type); ?>&breed=<?php echo urlencode($breed['slug']); ?>" aria-label="View <?php echo htmlspecialchars($breed['name']); ?> animals" style="background-image: url('<?php echo htmlspecialchars(resolve_image($breed['image'])); ?>')">
                    <span><?php echo htmlspecialchars($breed['name']); ?></span>
                </a>
                <div class="race-content">
                    <h2><?php echo htmlspecialchars($breed['name']); ?></h2>
                    <p><?php echo htmlspecialchars($breed['fact']); ?></p>
                    <div class="stats-line">
                        <span><?php echo (int) $breed['available']; ?> available</span>
                        <span><?php echo adoption_rate($breed['adopted'], $breed['available']); ?>% adopted</span>
                    </div>
                    <div class="progress"><i style="width: <?php echo adoption_rate($breed['adopted'], $breed['available']); ?>%"></i></div>
                    <a class="btn small" href="breed.php?type=<?php echo urlencode($type); ?>&breed=<?php echo urlencode($breed['slug']); ?>">Choose this race</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>