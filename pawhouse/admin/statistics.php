<?php
require __DIR__ . '/../includes/data.php';
$pageTitle = 'Statistics - pawHouse';
$basePath = '..';
$totalAvailable = array_sum(array_column($animalTypes, 'available'));
$totalAdopted = array_sum(array_column($animalTypes, 'adopted'));
$totalReturns = array_sum(array_column($clients, 'returned'));
$totalClientAdoptions = array_sum(array_column($clients, 'adopted'));
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <p class="eyebrow">Administrator statistics</p>
    <h1>Adoption performance and center activity.</h1>
    <p>Use this page to compare categories, client history, return activity, and follow-up workload.</p>
</section>
<section class="section admin-stats">
    <article><span>Animals available</span><strong><?php echo (int) $totalAvailable; ?></strong></article>
    <article><span>Animals adopted</span><strong><?php echo (int) $totalAdopted; ?></strong></article>
    <article><span>Client adoptions</span><strong><?php echo (int) $totalClientAdoptions; ?></strong></article>
    <article><span>Returned pets</span><strong><?php echo (int) $totalReturns; ?></strong></article>
</section>
<section class="section dashboard-grid">
    <article class="panel wide">
        <h2>Category adoption rates</h2>
        <?php foreach ($animalTypes as $animal): ?>
            <?php $rate = adoption_rate($animal['adopted'], $animal['available']); ?>
            <div class="progress-row">
                <span><?php echo htmlspecialchars($animal['label']); ?>: <?php echo $rate; ?>% adopted, <?php echo (int) $animal['available']; ?> available</span>
                <div class="progress"><i style="width: <?php echo $rate; ?>%"></i></div>
            </div>
        <?php endforeach; ?>
    </article>
    <article class="panel">
        <h2>Client summary</h2>
        <?php foreach ($clients as $client): ?>
            <div class="mini-stat">
                <span><?php echo htmlspecialchars($client['name']); ?></span>
                <strong><?php echo (int) $client['adopted']; ?> adopted / <?php echo (int) $client['returned']; ?> returned</strong>
            </div>
        <?php endforeach; ?>
    </article>
    <article class="panel">
        <h2>Follow-up meetings</h2>
        <?php foreach ($meetings as $meeting): ?>
            <div class="meeting-item">
                <strong>Week <?php echo (int) $meeting['week']; ?> / <?php echo htmlspecialchars($meeting['date']); ?></strong>
                <span><?php echo htmlspecialchars($meeting['client']); ?> with <?php echo htmlspecialchars($meeting['pet']); ?></span>
                <p><?php echo htmlspecialchars($meeting['note']); ?></p>
            </div>
        <?php endforeach; ?>
    </article>
    <article class="panel wide">
        <h2>Race availability</h2>
        <div class="breed-grid compact">
            <?php foreach ($breeds as $typeBreeds): ?>
                <?php foreach ($typeBreeds as $breed): ?>
                    <div class="breed-card">
                        <h3><?php echo htmlspecialchars($breed['name']); ?></h3>
                        <p><?php echo (int) $breed['available']; ?> available, <?php echo (int) $breed['adopted']; ?> adopted.</p>
                        <div class="progress"><i style="width: <?php echo adoption_rate($breed['adopted'], $breed['available']); ?>%"></i></div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
