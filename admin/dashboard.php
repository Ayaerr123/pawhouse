<?php
require __DIR__ . '/../includes/data.php';
check_access('admin');
$pageTitle = 'Administrator Dashboard - pawHouse';
$basePath = '..';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $pdo    = get_pdo();
    if ($action === 'handle_request') {
        $requestId = (int)$_POST['request_index'];
        $decision  = $_POST['decision'];
        if ($decision === 'approve') {
            $deliveryDate = trim($_POST['delivery_date']);
            if (empty($deliveryDate)) {
                $deliveryDate = date('Y-m-d', strtotime('+3 days'));
            }
            $note = 'Delivery scheduled for ' . $deliveryDate . '.';
            $pdo->prepare(
                "UPDATE adoption_requests
                 SET status = 'approved', approved_at = NOW(), employee_note = ?
                 WHERE id = ?"
            )->execute([$note, $requestId]);
            $pdo->prepare(
                "UPDATE animals a
                 JOIN adoption_requests ar ON ar.animal_id = a.id
                 SET a.adoption_state = 'reserved'
                 WHERE ar.id = ?"
            )->execute([$requestId]);
        } else {
            $pdo->prepare(
                "UPDATE adoption_requests SET status = 'rejected' WHERE id = ?"
            )->execute([$requestId]);
        }
        header('Location: dashboard.php?action_done=1');
        exit;
    }
    if ($action === 'schedule_meeting') {
        $clientName = trim($_POST['client']);
        $petName    = trim($_POST['pet']);
        $date       = trim($_POST['date']);
        $week       = (int)$_POST['week'];
        $stmtAR = $pdo->prepare(
            "SELECT ar.id, e.id AS emp_id
             FROM adoption_requests ar
             JOIN clients c ON c.id = ar.client_id
             JOIN users u ON u.id = c.user_id
             JOIN animals a ON a.id = ar.animal_id
             LEFT JOIN employees e ON e.user_id = (SELECT id FROM users WHERE role='employee' LIMIT 1)
             WHERE u.full_name = ? AND a.name = ?
             AND ar.status IN ('approved','delivered')
             LIMIT 1"
        );
        $stmtAR->execute([$clientName, $petName]);
        $arRow = $stmtAR->fetch();
        if ($arRow) {
            $empId = $arRow['emp_id'] ?? 1;
            $pdo->prepare(
                "INSERT INTO follow_up_meetings
                    (adoption_request_id, employee_id, meeting_number, scheduled_for, treatment_notes, return_required)
                 VALUES (?, ?, ?, ?, 'Meeting scheduled. Welfare check pending.', 0)"
            )->execute([$arRow['id'], $empId, $week, $date]);
        }
        header('Location: dashboard.php?action_done=2');
        exit;
    }
    if ($action === 'send_return_demand') {
        $clientName = trim($_POST['client']);
        $petName    = trim($_POST['pet']);
        $stmtAR = $pdo->prepare(
            "SELECT ar.id FROM adoption_requests ar
             JOIN clients c ON c.id = ar.client_id
             JOIN users u ON u.id = c.user_id
             JOIN animals a ON a.id = ar.animal_id
             WHERE u.full_name = ? AND a.name = ?
             LIMIT 1"
        );
        $stmtAR->execute([$clientName, $petName]);
        $arRow = $stmtAR->fetch();
        if ($arRow) {
            $exists = $pdo->prepare(
                "SELECT id FROM return_requests WHERE adoption_request_id = ? AND status = 'open' LIMIT 1"
            );
            $exists->execute([$arRow['id']]);
            if (!$exists->fetch()) {
                $pdo->prepare(
                    "INSERT INTO return_requests (adoption_request_id, reason, status)
                     VALUES (?, 'Mistreatment reported by employee during welfare check.', 'open')"
                )->execute([$arRow['id']]);
            }
        }
        header('Location: dashboard.php?action_done=3');
        exit;
    }
    if ($action === 'handle_surrender') {
        $surrenderId = (int)$_POST['surrender_index'];
        $decision    = $_POST['decision'];
        if ($decision === 'approve') {
            $stmtSurr = $pdo->prepare(
                "SELECT sr.*, ac.id AS cat_id FROM surrender_requests sr
                 JOIN animal_categories ac ON ac.id = sr.category_id
                 WHERE sr.id = ?"
            );
            $stmtSurr->execute([$surrenderId]);
            $sr = $stmtSurr->fetch();
            if ($sr) {
                $raceSlug = strtolower(preg_replace('/\s+/', '-', trim($sr['race'])));
                $stmtBreed = $pdo->prepare("SELECT id FROM animal_breeds WHERE slug = ? LIMIT 1");
                $stmtBreed->execute([$raceSlug]);
                $breedRow = $stmtBreed->fetch();
                if (!$breedRow) {
                    $pdo->prepare(
                        "INSERT INTO animal_breeds (category_id, slug, name, image_url, fact)
                         VALUES (?, ?, ?, ?, 'New breed added via client surrender.')"
                    )->execute([$sr['cat_id'], $raceSlug, trim($sr['race']), $sr['image_path']]);
                    $breedId = (int)$pdo->lastInsertId();
                } else {
                    $breedId = (int)$breedRow['id'];
                }
                preg_match('/(\d+)\s*(year|month)/i', $sr['age'], $ageMatch);
                $ageMonths = isset($ageMatch[2]) && strtolower($ageMatch[2]) === 'year'
                    ? (int)$ageMatch[1] * 12
                    : (int)($ageMatch[1] ?? 12);

                $sexMap = ['male' => 'male', 'female' => 'female', 'unknown' => 'unknown'];
                $sexVal = $sexMap[strtolower($sr['sex'])] ?? 'unknown';
                $pdo->prepare(
                    "INSERT INTO animals
                        (category_id, breed_id, name, age_months, sex, image_url,
                         former_state, health_state, arrival_date, adoption_state)
                     VALUES (?, ?, ?, ?, ?, ?, 'home', ?, CURDATE(), 'available')"
                )->execute([
                    $sr['cat_id'], $breedId, $sr['pet_name'],
                    $ageMonths, $sexVal, $sr['image_path'], $sr['info']
                ]);
                $pdo->prepare(
                    "UPDATE surrender_requests SET status='Approved', resolved_at=NOW() WHERE id=?"
                )->execute([$surrenderId]);
            }
        } else {
            $pdo->prepare(
                "UPDATE surrender_requests SET status='Rejected', resolved_at=NOW() WHERE id=?"
            )->execute([$surrenderId]);
        }
        header('Location: dashboard.php?action_done=4');
        exit;
    }
}
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <p class="eyebrow">Administrator control</p>
    <h1>Full center records and operations.</h1>
    <p>Administrators can export database lists to CSV files, accept or decline adoption requests, schedule follow-up welfare meetings, and demand return of pets if mistreatment is reported by employees.</p>
</section>
<?php if (isset($_GET['action_done'])): ?>
    <div class="notice-success" style="margin: 24px clamp(18px, 4vw, 56px);">
        <?php 
        $done = (int)$_GET['action_done'];
        if ($done === 1) {
            echo "Adoption demand status updated successfully. The animal has been removed from available listings.";
        } elseif ($done === 2) {
            echo "New post-adoption follow-up meeting scheduled successfully.";
        } elseif ($done === 3) {
            echo "Pet return request successfully sent to the client's inbox.";
        } elseif ($done === 4) {
            echo "Surrender request decision recorded. If approved, the animal has been added to the shelter inventory.";
        }
        ?>
    </div>
<?php endif; ?>
<section class="section admin-stats" style="padding-bottom: 0;">
    <article><span>Total available</span><strong><?php echo array_sum(array_column($animalTypes, 'available')); ?></strong></article>
    <article><span>Total adopted</span><strong><?php echo array_sum(array_column($animalTypes, 'adopted')); ?></strong></article>
    <article><span>Active employees</span><strong><?php echo count(array_filter($employees, fn($e) => $e['quit'] === null)); ?></strong></article>
    <article><span>Registered clients</span><strong><?php echo count($clients); ?></strong></article>
</section>
<?php
$pendingSurrenders = array_filter($surrenderRequests, fn($s) => ($s['status'] ?? 'Pending') === 'Pending');
if (!empty($surrenderRequests)):
?>
<section class="section" style="padding-top: 24px; padding-bottom: 0;">
    <article class="panel wide" style="border-color: var(--clay);">
        <div class="panel-head">
            <h2>Client Surrender Requests</h2>
            <span class="muted"><?php echo count($pendingSurrenders); ?> pending review</span>
        </div>
        <div class="table-wrap" style="margin-top: 16px;">
            <table>
                <thead>
                    <tr><th>Client</th><th>Pet Name</th><th>Type</th><th>Race</th><th>Age</th><th>Sex</th><th>Drop-off Date</th><th>Info</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($surrenderRequests as $si => $sr): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sr['client']); ?></td>
                        <td>
                            <?php if (!empty($sr['image'])): ?>
                                <img src="<?php echo htmlspecialchars(resolve_image($sr['image'])); ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:4px;display:block;margin-bottom:4px;">
                            <?php endif; ?>
                            <strong><?php echo htmlspecialchars($sr['pet_name']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($animalTypes[$sr['type_slug']]['label'] ?? $sr['type_slug']); ?></td>
                        <td><?php echo htmlspecialchars($sr['race']); ?></td>
                        <td><?php echo htmlspecialchars($sr['age']); ?></td>
                        <td><?php echo htmlspecialchars($sr['sex']); ?></td>
                        <td><?php echo htmlspecialchars($sr['dropoff_date']); ?></td>
                        <td style="max-width:200px;font-size:12px;color:var(--muted);"><?php echo htmlspecialchars($sr['info']); ?></td>
                        <td>
                            <?php
                            $srStatus = $sr['status'] ?? 'Pending';
                            if ($srStatus === 'Pending') {
                                echo '<span class="status" style="background:#fff7ed;color:#c2410c;">Pending</span>';
                            } elseif ($srStatus === 'Approved') {
                                echo '<span class="status" style="background:#ecfdf5;color:#059669;">Approved</span>';
                            } else {
                                echo '<span class="status" style="background:#fef2f2;color:#dc2626;">Rejected</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (($sr['status'] ?? 'Pending') === 'Pending'): ?>
                                <form method="post" style="display:inline-flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="action" value="handle_surrender">
                                    <input type="hidden" name="surrender_index" value="<?php echo (int)$sr['id']; ?>">
                                    <button class="btn small" name="decision" value="approve" style="background:#059669;color:white;border:0;">Accept</button>
                                    <button class="btn small" name="decision" value="reject" style="background:#dc2626;color:white;border:0;">Decline</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">Finalized</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php endif; ?>
<section class="section" style="padding-top: 24px; padding-bottom: 0;">
    <article class="panel wide" style="background: var(--soft); border-color: var(--sage);">
        <p class="eyebrow">Data Operations</p>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="margin: 0;">Export Records to CSV</h2>
                <p class="muted" style="margin: 4px 0 0;">Download complete spreadsheets of center employees, registered clients, and animals.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a class="btn primary small" href="export.php?entity=employee">Export Employees CSV</a>
                <a class="btn primary small" href="export.php?entity=client">Export Clients CSV</a>
                <a class="btn primary small" href="export.php?entity=animal">Export Animals CSV</a>
            </div>
        </div>
    </article>
</section>
<section class="section dashboard-grid">
    <article class="panel wide">
        <div class="panel-head">
            <h2>Manage Adoption Demands</h2>
            <span class="muted">Review client requests and accept or decline them</span>
        </div>
        <div class="table-wrap" style="margin-top: 16px;">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Pet Name</th>
                        <th>Status</th>
                        <th>Delivery Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($adoptionDemands as $index => $demand): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($demand['client']); ?></strong></td>
                            <td><?php echo htmlspecialchars($demand['pet']); ?></td>
                            <td>
                                <?php 
                                $state = strtolower($demand['state']);
                                if ($state === 'under review' || $state === 'pending') {
                                    echo '<span class="status" style="background: #eef2f6; color: #4b5563;">Under review</span>';
                                } elseif ($state === 'approved') {
                                    echo '<span class="status" style="background: #ecfdf5; color: #059669;">Approved</span>';
                                } elseif ($state === 'rejected' || $state === 'declined') {
                                    echo '<span class="status" style="background: #fef2f2; color: #dc2626;">Declined</span>';
                                } elseif ($state === 'delivered' || $state === 'completed' || $state === 'adopted') {
                                    echo '<span class="status" style="background: #f0fdf4; color: #15803d;">Delivered</span>';
                                } else {
                                    echo '<span class="status">' . htmlspecialchars($demand['state']) . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($demand['delivery']); ?></td>
                            <td>
                                <?php if ($state === 'under review' || $state === 'pending'): ?>
                                    <form method="post" style="display: inline-flex; gap: 8px; align-items: center;">
                                        <input type="hidden" name="action" value="handle_request">
                                        <input type="hidden" name="request_index" value="<?php echo (int)$demand['id']; ?>">
                                        
                                        <label style="margin: 0; font-size: 12px; font-weight: normal;">Arrival Date:
                                            <input type="date" name="delivery_date" style="min-height: 32px; padding: 2px 6px; font-size: 12px; width: 130px;" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
                                        </label>
                                        
                                        <button class="btn small" type="submit" name="decision" value="approve" style="background: #059669; color: white; border: 0;">Accept</button>
                                        <button class="btn small" type="submit" name="decision" value="reject" style="background: #dc2626; color: white; border: 0;">Decline</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">Decision finalized</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <article class="panel">
        <h2>Schedule Post-Adoption Meeting</h2>
        <p class="muted">Schedule follow-up visits for clients who have approved or delivered adoptions.</p>
        <form method="post" style="margin-top: 16px; display: grid; gap: 12px;">
            <input type="hidden" name="action" value="schedule_meeting">
            
            <label>Select Adoption Partner
                <select name="adoption_pair" onchange="var p=this.value.split('|'); document.getElementById('meeting_client').value=p[0]; document.getElementById('meeting_pet').value=p[1];" required>
                    <option value="">Choose active adoption</option>
                    <?php foreach ($adoptionDemands as $demand): ?>
                        <?php if (strtolower($demand['state']) === 'approved' || strtolower($demand['state']) === 'delivered'): ?>
                            <option value="<?php echo htmlspecialchars($demand['client'] . '|' . $demand['pet']); ?>">
                                <?php echo htmlspecialchars($demand['client'] . ' (' . $demand['pet'] . ')'); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <input type="hidden" name="client" id="meeting_client">
            <input type="hidden" name="pet" id="meeting_pet">
            
            <label>Meeting Number
                <select name="week" required>
                    <option value="1">Meeting 1 (Week 1 follow-up)</option>
                    <option value="2">Meeting 2 (Week 2 follow-up)</option>
                    <option value="3">Meeting 3 (Week 3 follow-up)</option>
                </select>
            </label>
            
            <label>Scheduled For Date
                <input type="date" name="date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
            </label>
            
            <button class="btn primary small" type="submit" style="margin-top: 8px;">Schedule Meeting</button>
        </form>
    </article>
    <article class="panel">
        <h2>Welfare Review & Return Demands</h2>
        <p class="muted">Welfare reviews submitted by employees. Demanding the return of pets is triggered here.</p>
        <div style="margin-top: 16px; display: grid; gap: 12px;">
            <?php 
            $welfareIssuesCount = 0;
            foreach ($meetings as $meeting): 
                if ($meeting['completed']):
            ?>
                <div class="meeting-item" style="border-left: 4px solid <?php echo $meeting['return_required'] ? '#dc2626' : 'var(--sage)'; ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <strong>Week <?php echo (int) $meeting['week']; ?> welfare review completed</strong>
                        <?php if ($meeting['return_required']): ?>
                            <span class="status" style="background: #fef2f2; color: #dc2626; font-size: 11px;">Mistreatment Reported</span>
                        <?php else: ?>
                            <span class="status" style="background: #ecfdf5; color: #059669; font-size: 11px;">✓ Normal Welfare</span>
                        <?php endif; ?>
                    </div>
                    <p style="margin: 4px 0 0; font-size: 13px;"><strong>Client:</strong> <?php echo htmlspecialchars($meeting['client']); ?> | <strong>Pet:</strong> <?php echo htmlspecialchars($meeting['pet']); ?></p>
                    <p style="margin: 4px 0 0; font-style: italic; font-size: 13px; color: var(--muted);">"<?php echo htmlspecialchars($meeting['note']); ?>"</p>
                    
                    <?php if ($meeting['return_required']): ?>
                        <?php 
                        $welfareIssuesCount++;
                        $alreadySent = false;
                        foreach ($adoptionDemands as $d) {
                            if ($d['client'] === $meeting['client'] && $d['pet'] === $meeting['pet'] && isset($d['return']) && $d['return'] === 'Return Demanded') {
                                $alreadySent = true;
                                break;
                            }
                        }
                        ?>
                        <div style="margin-top: 10px;">
                            <?php if ($alreadySent): ?>
                                <span style="color: #c2410c; font-weight: 700; font-size: 13px;">✓ Return demand is active in Client's Inbox</span>
                            <?php else: ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="send_return_demand">
                                    <input type="hidden" name="client" value="<?php echo htmlspecialchars($meeting['client']); ?>">
                                    <input type="hidden" name="pet" value="<?php echo htmlspecialchars($meeting['pet']); ?>">
                                    <button class="btn small" type="submit" style="background: #dc2626; color: white; border: 0; padding: 4px 10px; font-size: 12px;">Demand Pet Return Now</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php 
                endif;
            endforeach; 
            if ($welfareIssuesCount === 0):
            ?>
                <p class="muted">No completed reviews indicating welfare issues or mistreatment at this time.</p>
            <?php endif; ?>
        </div>
    </article>
    <article class="panel wide">
        <div class="panel-head">
            <h2>Employees</h2>
            <a class="btn small" href="statistics.php">View Statistics Dashboard</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Age</th><th>Role</th><th>Started</th><th>Quit date</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($employees as $index => $employee): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($employee['name']); ?></strong></td>
                        <td><?php echo (int) $employee['age']; ?></td>
                        <td><?php echo htmlspecialchars($employee['role']); ?></td>
                        <td><?php echo htmlspecialchars($employee['started']); ?></td>
                        <td><?php echo htmlspecialchars($employee['quit'] ?? 'Still working'); ?></td>
                        <td><a class="link-button" href="edit.php?entity=employee&id=<?php echo (int) $employee['id']; ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <article class="panel wide">
        <div class="panel-head">
            <h2>Clients</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Password</th><th>Client since</th><th>Pets adopted</th><th>Returns</th><th>Treatment status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($clients as $index => $client): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($client['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($client['email']); ?></td>
                        <td><code><?php echo htmlspecialchars($client['password']); ?></code></td>
                        <td><?php echo htmlspecialchars($client['joined']); ?></td>
                        <td><?php echo (int) $client['adopted']; ?></td>
                        <td><?php echo (int) $client['returned']; ?></td>
                        <td><?php echo htmlspecialchars($client['status']); ?></td>
                        <td><a class="link-button" href="edit.php?entity=client&id=<?php echo (int) $client['id']; ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <article class="panel wide">
        <h2>Animals by category</h2>
        <div class="breed-grid compact">
            <?php foreach ($animalTypes as $slug => $animal): ?>
                <div class="breed-card">
                    <h3><?php echo htmlspecialchars($animal['label']); ?></h3>
                    <p><?php echo (int) $animal['available']; ?> available, <?php echo (int) $animal['adopted']; ?> adopted.</p>
                    <a class="btn small" href="edit.php?entity=category&id=<?php echo urlencode($slug); ?>">Edit category</a>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="panel wide">
        <h2>Individual animals</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Race</th><th>Age</th><th>Sex</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($breeds as $typeBreeds): ?>
                    <?php foreach ($typeBreeds as $breed): ?>
                        <?php foreach (($pets[$breed['slug']] ?? []) as $index => $pet): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($pet['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($breed['name']); ?></td>
                                <td><?php echo htmlspecialchars($pet['age']); ?></td>
                                <td><?php echo htmlspecialchars($pet['sex']); ?></td>
                                <td><a class="link-button" href="edit.php?entity=pet&breed=<?php echo urlencode($breed['slug']); ?>&id=<?php echo (int) $pet['id']; ?>">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
