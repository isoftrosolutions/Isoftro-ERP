<?php
/**
 * Front Desk — Batch Status & Seat Allocation
 * Combined view for monitoring seat capacity
 */

if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar();
}

$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];

$stmt = $db->prepare("
    SELECT b.*, c.name as course_name,
           (SELECT COUNT(*) FROM enrollments e WHERE e.batch_id = b.id AND e.status = 'active') as enrolled
    FROM batches b
    JOIN courses c ON b.course_id = c.id
    WHERE b.tenant_id = :tid AND b.deleted_at IS NULL AND b.status != 'completed'
    ORDER BY b.start_date ASC
");
$stmt->execute(['tid' => $tenantId]);
$batches = $stmt->fetchAll();
?>

<div class="pg-head">
    <div class="pg-title">Seats & Allocation</div>
    <div class="pg-sub">Live monitoring of batch capacity and enrollment distribution</div>
</div>

<div class="sg">
    <?php foreach ($batches as $b): 
        $percent = ($b['seat_limit'] > 0) ? ($b['enrolled'] / $b['seat_limit']) * 100 : 0;
        $color = '#10b981';
        if ($percent > 70) $color = '#f59e0b';
        if ($percent >= 100) $color = '#ef4444';
    ?>
    <div class="card" style="padding:15px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
            <div>
                <div style="font-weight:700; color:#1e293b;"><?php echo htmlspecialchars($b['name']); ?></div>
                <div style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($b['course_name']); ?></div>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:800; color:<?php echo $color; ?>;"><?php echo round($percent); ?>%</div>
                <div style="font-size:11px; color:#94a3b8;">Occupied</div>
            </div>
        </div>
        
        <div style="height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden; margin-bottom:12px;">
            <div style="height:100%; width:<?php echo $percent; ?>%; background:<?php echo $color; ?>; transition:width 0.4s;"></div>
        </div>
        
        <div style="display:flex; justify-content:space-between; font-size:12px; color:#64748b;">
            <span><?php echo $b['enrolled']; ?> Enrolled</span>
            <span><?php echo $b['seat_limit'] - $b['enrolled']; ?> Remaining</span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card" style="margin-top:20px;">
    <div class="ct"><i class="fa-solid fa-chart-simple"></i> Quick Allocation Guide</div>
    <div style="padding:15px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;">
        <ul style="margin:0; padding-left:20px; font-size:13px; color:#475569; line-height:1.8;">
            <li>Batches with <span style="color:#ef4444; font-weight:700;">Red</span> status are full; do not promise seats.</li>
            <li>Batches with <span style="color:#f59e0b; font-weight:700;">Orange</span> status (>70%) are filling fast; encourage immediate payment.</li>
            <li>Use the 'Admissions' module to move students into available batches.</li>
        </ul>
    </div>
</div>
