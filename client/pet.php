<?php
require __DIR__ . '/../includes/data.php';
check_access('client');
$type = $_GET['type'] ?? 'cats';
$breedSlug = $_GET['breed'] ?? 'domestic-short-hair';
$name = $_GET['name'] ?? '';
if (!isset($animalTypes[$type]) || !isset($pets[$breedSlug])) {
    $type = 'cats';
    $breedSlug = 'domestic-short-hair';
}
$selectedPet = $pets[$breedSlug][0];
foreach ($pets[$breedSlug] as $pet) {
    if ($pet['name'] === $name) {
        $selectedPet = $pet;
        break;
    }
}
$breedName = $breedSlug;
foreach ($breeds[$type] as $breed) {
    if ($breed['slug'] === $breedSlug) {
        $breedName = $breed['name'];
        break;
    }
}
$pageTitle = $selectedPet['name'] . ' Adoption Notice - pawHouse';
$basePath = '..';
require __DIR__ . '/../includes/header.php';
?>
<section class="notice-layout">
    <img src="<?php echo htmlspecialchars(resolve_image($selectedPet['image'])); ?>" alt="<?php echo htmlspecialchars($selectedPet['name'] . ', ' . $breedName); ?>">
    <article class="notice-panel">
        <p class="eyebrow">Adoption notice</p>
        <h1><?php echo htmlspecialchars($selectedPet['name']); ?> can arrive after approval.</h1>
        <p class="lead">Estimated arrival time is 3 to 5 working days after the home check and payment confirmation. The center may delay delivery if preparation is incomplete.</p>
        <h2>Required before adoption</h2>
        <ul class="check-list">
            <li>Valid identity document and signed adoption contract.</li>
            <li>Proof that housing allows this animal type.</li>
            <li>Prepared food, enclosure, bedding, or tank equipment before arrival.</li>
            <li>Agreement to three weekly welfare meetings after adoption.</li>
            <li>Immediate return if the animal is mistreated or kept in unsafe conditions.</li>
        </ul>
        <a class="btn primary" href="dashboard.php?action=request_adoption&breed=<?php echo urlencode($breedSlug); ?>&pet=<?php echo urlencode($selectedPet['name']); ?>">Send adoption request</a>
    </article>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
