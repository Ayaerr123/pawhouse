<?php
require __DIR__ . '/includes/data.php';
$pageTitle = 'pawHouse Adoption Center';
$basePath = '.';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">Adoption center in Al Hoceima</p>
        <h1><?php echo htmlspecialchars($center['name']); ?></h1>
        <p><?php echo htmlspecialchars($center['tagline']); ?> We care for cats, dogs, birds, fish, snakes, spiders, and lizards with a documented adoption process.</p>
        <div class="hero-actions">
            <a class="btn primary" href="login.php?role=client">Adopt an animal</a>
            <a class="btn secondary" href="#animals">View animals</a>
        </div>
    </div>
    <div class="hero-image" role="img" aria-label="pawHouse adoption center"></div>
</section>
<section class="section two-column">
    <div>
        <p class="eyebrow">How the center works</p>
        <h2>Responsible adoption with follow-up after the animal goes home.</h2>
        <p>Every adoption request is reviewed by the center team. Approved clients receive arrival instructions, preparation requirements, and three weekly welfare meetings after adoption.</p>
    </div>
    <div class="info-list">
        <div><strong>Location</strong><span><?php echo htmlspecialchars($center['location']); ?></span></div>
        <div><strong>Hours</strong><span><?php echo htmlspecialchars($center['hours']); ?></span></div>
        <div><strong>Contact</strong><span><?php echo htmlspecialchars($center['phone']); ?> / <?php echo htmlspecialchars($center['email']); ?></span></div>
    </div>
</section>

<section class="section" id="animals">
    <div class="section-heading">
        <p class="eyebrow">Animal families</p>
        <h2>Choose by real animal category.</h2>
    </div>
    <div class="animal-grid">
        <?php foreach ($animalTypes as $slug => $animal): ?>
            <a class="animal-tile" href="client/animal.php?type=<?php echo urlencode($slug); ?>" style="background-image: url('<?php echo htmlspecialchars(resolve_image($animal['image'])); ?>')">
                <span><?php echo htmlspecialchars($animal['label']); ?></span>
                <small><?php echo (int)$animal['available']; ?> available</small>
            </a>
        <?php
endforeach; ?>
    </div>
</section>

<section class="section split-band">
    <div>
        <p class="eyebrow">Employees</p>
        <h2>A small team with clear responsibilities.</h2>
        <div class="staff-list">
            <?php foreach (array_slice($employees, 0, 3) as $employee): ?>
                <article>
                    <strong><?php echo htmlspecialchars($employee['name']); ?></strong>
                    <span><?php echo htmlspecialchars($employee['role']); ?></span>
                </article>
            <?php
endforeach; ?>
        </div>
    </div>
    <div>
        <p class="eyebrow">Access</p>
        <h2>Separate spaces for clients, employees, and administrators.</h2>
        <p>Clients browse animals and start adoption requests. Employees manage deliveries, returns, meetings, and welfare notes. Administrators can edit animals, clients, employees, and statistics.</p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
