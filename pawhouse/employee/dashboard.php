<?php
require __DIR__ . '/../includes/data.php';
check_access('employee');
$pageTitle = 'Employee Dashboard - pawHouse';
$basePath = '..';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_meeting') {
    $meetingId = (int)$_POST['meeting_id'];
    $note = trim($_POST['note'] ?? 'Welfare checked, pet appears stable.');
    $returnRequired = (isset($_POST['return_required']) && $_POST['return_required'] === '1') ? 1 : 0;
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE follow_up_meetings SET completed_at = NOW(), treatment_notes = ?, return_required = ? WHERE id = ?');
    $stmt->execute([$note, $returnRequired, $meetingId]);
    header('Location: dashboard.php?saved=1');
    exit;
}
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <p class="eyebrow">Employee workspace</p>
    <h1>Track animals, adoption demands, returns, and weekly meetings.</h1>
    <p>Employees can review animal information, delivery status, return requests, and record welfare notes from the three required follow-up meetings.</p>
</section>
<?php if (isset($_GET['saved'])): ?>
    <div class="notice-success" style="margin: 24px clamp(18px, 4vw, 56px);">
        Welfare meeting notes and recommendation saved successfully! The admin has been notified.
    </div>
<?php endif; ?>
<section class="section dashboard-grid">
    <article class="panel wide">
        <div class="panel-head">
            <h2>Welfare Meeting Schedule</h2>
            <span class="muted">Conduct follow-up checks and submit notes to administrator</span>
        </div>
        <div style="margin-top: 16px; display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
            <?php foreach ($meetings as $index => $meeting): ?>
                <div class="meeting-item" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid <?php echo $meeting['completed'] ? 'var(--sage)' : 'var(--clay)'; ?>; padding: 18px; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); background: var(--white);">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <strong>Week <?php echo (int) $meeting['week']; ?> Welfare Visit</strong>
                            <?php if ($meeting['completed']): ?>
                                <span class="status" style="background: #ecfdf5; color: #059669; font-size: 11px;">Completed</span>
                            <?php else: ?>
                                <span class="status" style="background: #fff7ed; color: #c2410c; font-size: 11px;">Pending Action</span>
                            <?php endif; ?>
                        </div>
                        <p style="margin: 4px 0; font-size: 13px;"><strong>Client:</strong> <?php echo htmlspecialchars($meeting['client']); ?></p>
                        <p style="margin: 4px 0; font-size: 13px;"><strong>Companion:</strong> <?php echo htmlspecialchars($meeting['pet']); ?></p>
                        <p style="margin: 4px 0; font-size: 13px;"><strong>Date:</strong> <?php echo htmlspecialchars($meeting['date']); ?></p>          
                        <?php if ($meeting['completed']): ?>
                            <div style="margin-top: 10px; padding: 10px; background: var(--soft); border-radius: 4px; font-size: 13px;">
                                <p style="margin: 0; font-style: italic; color: var(--muted);"><strong>Welfare Notes:</strong> "<?php echo htmlspecialchars($meeting['note']); ?>"</p>
                                <p style="margin: 6px 0 0; font-weight: 700; color: <?php echo $meeting['return_required'] ? '#dc2626' : 'var(--forest)'; ?>;">
                                    <?php echo $meeting['return_required'] ? '⚠ Return demanded due to mistreatment' : '✓ No return required (Welfare stable)'; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>                 
                    <?php if (!$meeting['completed']): ?>
                        <form method="post" style="margin-top: 16px; border-top: 1px solid var(--line); padding-top: 14px;">
                            <input type="hidden" name="action" value="complete_meeting">
                            <input type="hidden" name="meeting_id" value="<?php echo (int)$meeting['id']; ?>">  
                            <label style="font-size: 12px; margin: 4px 0;">Welfare & Treatment Notes
                                <textarea name="note" placeholder="Write details about the animal's behavior, physical health, and habitat..." style="min-height: 70px; font-size: 13px;" required></textarea>
                            </label>
                            <div style="margin: 10px 0;">
                                <span style="display: block; font-weight: 700; font-size: 12px; margin-bottom: 6px;">Demand Return of Pet? (If mistreated / unsafe)</span>
                                <div style="display: flex; gap: 20px; font-size: 13px;">
                                    <label style="display: inline-flex; align-items: center; gap: 6px; font-weight: normal; margin: 0;">
                                        <input type="radio" name="return_required" value="1" style="min-height: auto; width: auto;"> Yes, pet is mistreated
                                    </label>
                                    <label style="display: inline-flex; align-items: center; gap: 6px; font-weight: normal; margin: 0;">
                                        <input type="radio" name="return_required" value="0" checked style="min-height: auto; width: auto;"> No, welfare is fine
                                    </label>
                                </div>
                            </div>
                            <button class="btn primary small full" type="submit" style="margin-top: 8px;">Submit welfare report</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="panel wide">
        <h2>Adoption Demands</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Client</th><th>Pet</th><th>Status</th><th>Delivery</th><th>Return Demand Status</th></tr></thead>
                <tbody>
                <?php foreach ($adoptionDemands as $demand): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($demand['client']); ?></td>
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
                            <?php 
                            if (isset($demand['return']) && $demand['return'] === 'Return Demanded') {
                                echo '<span style="color: #dc2626; font-weight: 800;">⚠ Demand Active</span>';
                            } else {
                                echo '<span class="muted">' . htmlspecialchars($demand['return'] ?? 'No return request') . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <article class="panel">
        <h2>Adoption Statistics & Performance</h2>
        <p class="muted">Check care center stats, including overall adoption rates by animal category.</p>
        
        <div style="margin-top: 16px; display: grid; gap: 12px;">
            <?php foreach ($animalTypes as $animal): ?>
                <?php $rate = adoption_rate($animal['adopted'], $animal['available']); ?>
                <div class="progress-row" style="margin: 0;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; margin-bottom: 2px;">
                        <span><?php echo htmlspecialchars($animal['label']); ?></span>
                        <span><?php echo $rate; ?>% adopted</span>
                    </div>
                    <div class="progress"><i style="width: <?php echo $rate; ?>%"></i></div>
                    <small class="muted" style="font-size: 11px;"><?php echo (int) $animal['available']; ?> shelter pets vs <?php echo (int) $animal['adopted']; ?> adopted</small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="panel">
        <h2>Shelter Animal Inventory</h2>
        <p class="muted">Currently available animal categories in Al Hoceima center.</p>
        <div style="margin-top: 16px; display: grid; gap: 8px;">
            <?php foreach ($animalTypes as $animal): ?>
                <div class="mini-stat" style="margin-top: 0; padding: 12px 16px;">
                    <strong><?php echo htmlspecialchars($animal['label']); ?></strong>
                    <span style="font-weight: 700; color: var(--forest);"><?php echo (int) $animal['available']; ?> available</span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>