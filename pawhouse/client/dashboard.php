<?php
require __DIR__ . '/../includes/data.php';
check_access('client');
$pageTitle = 'Client Dashboard - pawHouse';
$basePath = '..';

// Detect current client in session
$clientEmail = $_SESSION['user_email'] ?? 'nadia@example.test';
$currentClient = null;
foreach ($clients as $c) {
    if (strcasecmp($c['email'], $clientEmail) === 0) {
        $currentClient = $c;
        break;
    }
}
if (!$currentClient) {
    $currentClient = $clients[0]; // fallback
}

// Handle adoption request submission
if (isset($_GET['action']) && $_GET['action'] === 'request_adoption') {
    $breedSlug = trim($_GET['breed'] ?? '');
    $petName = trim($_GET['pet'] ?? '');
    if ($breedSlug !== '' && $petName !== '') {
        $pdo = get_pdo();
        // Find animal ID that is available
        $stmt = $pdo->prepare("SELECT a.id FROM animals a JOIN animal_breeds ab ON ab.id = a.breed_id WHERE ab.slug = ? AND a.name = ? AND a.adoption_state = 'available' LIMIT 1");
        $stmt->execute([$breedSlug, $petName]);
        $animal = $stmt->fetch();
        if ($animal) {
            // Check if already requested
            $stmtCheck = $pdo->prepare("SELECT id FROM adoption_requests WHERE client_id = ? AND animal_id = ? AND status = 'pending' LIMIT 1");
            $stmtCheck->execute([$currentClient['id'], $animal['id']]);
            if (!$stmtCheck->fetch()) {
                $stmtInsert = $pdo->prepare("INSERT INTO adoption_requests (client_id, animal_id, status) VALUES (?, ?, 'pending')");
                $stmtInsert->execute([$currentClient['id'], $animal['id']]);
            }
        }
    }
    header('Location: dashboard.php?request_sent=1');
    exit;
}

// Fetch requests
$clientDemands = [];
foreach ($adoptionDemands as $d) {
    if (strcasecmp($d['client'], $currentClient['name']) === 0) {
        $clientDemands[] = $d;
    }
}

// Fetch meetings
$clientMeetings = [];
foreach ($meetings as $m) {
    if (strcasecmp($m['client'], $currentClient['name']) === 0) {
        $clientMeetings[] = $m;
    }
}

// Check for return demand
$hasReturnDemand = false;
$returnPetName = '';
foreach ($clientDemands as $d) {
    if (isset($d['return']) && $d['return'] === 'Return Demanded') {
        $hasReturnDemand = true;
        $returnPetName = $d['pet'];
        break;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<section class="page-intro">
    <p class="eyebrow">Client workspace</p>
    <h1>Welcome back, <?php echo htmlspecialchars($currentClient['name']); ?></h1>
    <p>View the progress of your requests, check upcoming welfare visits, see adoption statistics, and browse animal categories.</p>
    <div class="hero-actions" style="margin-top: 20px;">
        <a class="btn primary" href="#animals">Browse & Adopt</a>
        <a class="btn secondary" href="surrender.php">Surrender a Pet to the Center</a>
    </div>
</section>

<?php
// Load surrender requests for this client
$clientSurrenders = [];
foreach ($surrenderRequests as $sr) {
    if (strcasecmp($sr['client'], $currentClient['name']) === 0) {
        $clientSurrenders[] = $sr;
    }
}
?>

<?php if ($hasReturnDemand): ?>
    <div class="panel" style="background: #fff0f0; border: 2px solid #e15b5b; margin: 24px clamp(18px, 4vw, 56px); padding: 20px; border-radius: 8px;">
        <p class="eyebrow" style="color: #c92a2a; margin: 0 0 5px;">URGENT ACTION REQUIRED</p>
        <h2 style="color: #c92a2a; margin: 0 0 10px;">Return Demand: Pet Mistreatment Warning!</h2>
        <p style="margin: 0; color: #5c5c5c; font-size: 15px;">The administrator has issued a formal demand requesting the immediate return of <strong><?php echo htmlspecialchars($returnPetName); ?></strong> due to reports of welfare concerns or mistreatment during employee post-adoption follow-up. Please contact the pawHouse team immediately to arrange transportation.</p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['request_sent'])): ?>
    <div class="notice-success" style="margin: 24px clamp(18px, 4vw, 56px);">
        Your adoption request has been submitted successfully! It is now under review by our care managers.
    </div>
<?php endif; ?>

<section class="section dashboard-grid">
    <!-- CLIENT INBOX -->
    <article class="panel wide">
        <div class="panel-head">
            <h2>Your Adoption Inbox</h2>
            <span class="muted">Check the real-time state of your pet adoption requests</span>
        </div>
        
        <?php if (empty($clientDemands)): ?>
            <p class="muted" style="margin-top: 16px;">You haven't submitted any adoption requests yet. Browse animals below to get started!</p>
        <?php else: ?>
            <div class="table-wrap" style="margin-top: 16px;">
                <table>
                    <thead>
                        <tr>
                            <th>Pet Name</th>
                            <th>Request Status</th>
                            <th>Arrival Date / Info</th>
                            <th>Return Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientDemands as $demand): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($demand['pet']); ?></strong></td>
                                <td>
                                    <?php 
                                    $state = strtolower($demand['state']);
                                    if ($state === 'under review' || $state === 'pending') {
                                        echo '<span class="status" style="background: #eef2f6; color: #4b5563;">Still in process</span>';
                                    } elseif ($state === 'approved') {
                                        echo '<span class="status" style="background: #ecfdf5; color: #059669;">Accepted</span>';
                                    } elseif ($state === 'rejected' || $state === 'declined') {
                                        echo '<span class="status" style="background: #fef2f2; color: #dc2626;">Declined</span>';
                                    } elseif ($state === 'delivered' || $state === 'completed' || $state === 'adopted') {
                                        echo '<span class="status" style="background: #f0fdf4; color: #15803d;">Already Adopted</span>';
                                    } else {
                                        echo '<span class="status">' . htmlspecialchars($demand['state']) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($state === 'approved') {
                                        echo 'Estimated arrival: <strong>' . htmlspecialchars($demand['delivery']) . '</strong>';
                                    } elseif ($state === 'delivered' || $state === 'completed' || $state === 'adopted') {
                                        echo 'Delivered / Adopted on: <strong>' . htmlspecialchars($demand['delivery']) . '</strong>';
                                    } elseif ($state === 'rejected' || $state === 'declined') {
                                        echo '<span class="muted">Notice of decision sent via email</span>';
                                    } else {
                                        echo '<span class="muted">Awaiting home approval review</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (isset($demand['return']) && $demand['return'] === 'Return Demanded') {
                                        echo '<span style="color: #dc2626; font-weight: 800;">⚠ Return Required</span>';
                                    } else {
                                        echo '<span class="muted">No return requested</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <!-- SCHEDULED MEETINGS -->
    <article class="panel">
        <h2>Your Scheduled Welfare Meetings</h2>
        <p class="muted">To ensure a smooth transition, three follow-up meetings are required during the first three weeks after delivery.</p>
        
        <?php if (empty($clientMeetings)): ?>
            <p class="muted" style="margin-top: 16px;">No meetings currently scheduled. These will appear after your adoption request is delivered.</p>
        <?php else: ?>
            <div style="margin-top: 16px; display: grid; gap: 12px;">
                <?php foreach ($clientMeetings as $meeting): ?>
                    <div class="meeting-item" style="border-left: 4px solid <?php echo $meeting['completed'] ? 'var(--sage)' : 'var(--clay)'; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <strong>Week <?php echo (int) $meeting['week']; ?> welfare review</strong>
                            <?php if ($meeting['completed']): ?>
                                <span class="status" style="background: #ecfdf5; color: #059669; font-size: 11px; padding: 2px 6px;">Completed</span>
                            <?php else: ?>
                                <span class="status" style="background: #fff7ed; color: #c2410c; font-size: 11px; padding: 2px 6px;">Scheduled: <?php echo htmlspecialchars($meeting['date']); ?></span>
                            <?php endif; ?>
                        </div>
                        <p style="margin: 5px 0 0; font-size: 13px;"><strong>Date:</strong> <?php echo htmlspecialchars($meeting['date']); ?></p>
                        <p style="margin: 5px 0 0; font-size: 13px;"><strong>Companion:</strong> <?php echo htmlspecialchars($meeting['pet']); ?></p>
                        <?php if ($meeting['completed']): ?>
                            <p style="margin: 8px 0 0; font-style: italic; font-size: 13px; color: var(--muted); background: var(--soft); padding: 8px; border-radius: 4px;">
                                <strong>Employee Note:</strong> "<?php echo htmlspecialchars($meeting['note']); ?>"
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <!-- SURRENDER REQUEST STATUS -->
    <?php if (!empty($clientSurrenders)): ?>
    <article class="panel">
        <div class="panel-head">
            <h2>Your Surrender Requests</h2>
            <a class="btn small" href="surrender.php">Submit another</a>
        </div>
        <div style="margin-top: 14px; display: grid; gap: 10px;">
            <?php foreach ($clientSurrenders as $sr): ?>
                <div style="padding: 12px 16px; border: 1px solid var(--line); border-radius: 6px; background: var(--white); display: flex; gap: 14px; align-items: start;">
                    <?php if (!empty($sr['image'])): ?>
                        <img src="<?php echo htmlspecialchars(resolve_image($sr['image'])); ?>" alt="" style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px; flex-shrink: 0;">
                    <?php endif; ?>
                    <div style="flex: 1;">
                        <strong><?php echo htmlspecialchars($sr['pet_name']); ?></strong>
                        <span class="muted" style="font-size: 13px; margin-left: 8px;"><?php echo htmlspecialchars($sr['race']); ?> &middot; <?php echo htmlspecialchars($sr['age']); ?> &middot; <?php echo htmlspecialchars($sr['sex']); ?></span>
                        <p style="margin: 4px 0 0; font-size: 12px; color: var(--muted);">Drop-off date: <?php echo htmlspecialchars($sr['dropoff_date']); ?></p>
                    </div>
                    <?php
                    $srSt = $sr['status'] ?? 'Pending';
                    if ($srSt === 'Pending') {
                        echo '<span class="status" style="background:#fff7ed;color:#c2410c;align-self:center;">Pending Review</span>';
                    } elseif ($srSt === 'Approved') {
                        echo '<span class="status" style="background:#ecfdf5;color:#059669;align-self:center;">Approved — Pet added to shelter</span>';
                    } else {
                        echo '<span class="status" style="background:#fef2f2;color:#dc2626;align-self:center;">Declined</span>';
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <?php endif; ?>

    <!-- DYNAMIC ADOPTION STATISTICS -->
    <article class="panel">
        <h2>Center Adoption Statistics</h2>
        <p class="muted">See how we match animals to loving families across all categories.</p>
        
        <div style="margin-top: 20px; display: grid; gap: 14px;">
            <?php foreach ($animalTypes as $animal): ?>
                <?php $rate = adoption_rate($animal['adopted'], $animal['available']); ?>
                <div class="progress-row" style="margin: 0;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; margin-bottom: 4px;">
                        <span><?php echo htmlspecialchars($animal['label']); ?></span>
                        <span><?php echo $rate; ?>% adopted</span>
                    </div>
                    <div class="progress"><i style="width: <?php echo $rate; ?>%"></i></div>
                    <small class="muted" style="font-size: 11px;"><?php echo (int) $animal['available']; ?> available in shelter / <?php echo (int) $animal['adopted']; ?> in homes</small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <!-- ANIMAL BROWSER -->
    <article class="panel wide" id="animals">
        <div class="panel-head">
            <h2>Find your next family member</h2>
            <span class="muted">Select a category below to explore specific breeds and view requirements</span>
        </div>
        <div class="metric-grid" style="margin-top: 16px;">
            <?php foreach ($animalTypes as $slug => $animal): ?>
                <article class="metric-card">
                    <img src="<?php echo htmlspecialchars(resolve_image($animal['image'])); ?>" alt="<?php echo htmlspecialchars($animal['label']); ?>">
                    <div>
                        <h2><?php echo htmlspecialchars($animal['label']); ?></h2>
                        <p style="font-size: 13px;"><?php echo htmlspecialchars($animal['description']); ?></p>
                        <div class="progress-row">
                            <span><?php echo adoption_rate($animal['adopted'], $animal['available']); ?>% adopted</span>
                            <div class="progress"><i style="width: <?php echo adoption_rate($animal['adopted'], $animal['available']); ?>%"></i></div>
                        </div>
                        <a class="btn small" href="animal.php?type=<?php echo urlencode($slug); ?>">View races</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
