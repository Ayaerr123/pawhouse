<?php
require __DIR__ . '/../includes/data.php';
check_access('client');
$type = $_GET['type'] ?? 'cats';
$breedSlug = $_GET['breed'] ?? 'domestic-short-hair';
if (!isset($animalTypes[$type]) || !isset($pets[$breedSlug])) {
    $type = 'cats';
    $breedSlug = 'domestic-short-hair';
}
$breedName = $breedSlug;
foreach ($breeds[$type] as $breed) {
    if ($breed['slug'] === $breedSlug) {
        $breedName = $breed['name'];
        break;
    }
}
$pageTitle = $breedName . ' Animals - pawHouse';
$basePath = '..';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <p class="eyebrow"><?php echo htmlspecialchars($animalTypes[$type]['label']); ?></p>
    <h1><?php echo htmlspecialchars($breedName); ?> animals available.</h1>
    <p>Choose a specific animal to see arrival timing and adoption requirements.</p>
</section>

<section class="section">
    <div class="pet-grid">
        <?php foreach ($pets[$breedSlug] as $pet): ?>
            <article class="pet-card">
                <img src="<?php echo htmlspecialchars(resolve_image($pet['image'])); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>">
                <div>
                    <h2><?php echo htmlspecialchars($pet['name']); ?></h2>
                    <p><?php echo htmlspecialchars($pet['age']); ?> / <?php echo htmlspecialchars($pet['sex']); ?></p>
                    <a class="btn small" href="pet.php?type=<?php echo urlencode($type); ?>&breed=<?php echo urlencode($breedSlug); ?>&name=<?php echo urlencode($pet['name']); ?>">View adoption notice</a>
                </div>
            </article>
        <?php
endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
