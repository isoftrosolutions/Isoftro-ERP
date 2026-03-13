<?php
/**
 * Front Desk — Batch List View
 * Read-only view for front desk to check availability and schedule
 */

if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar();
}

$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];

// Get batches with course names and student counts
$stmt = $db->prepare("
    SELECT b.*, c.name as course_name, 
           (SELECT COUNT(*) FROM enrollments e WHERE e.batch_id = b.id AND e.status = 'active') as enrolled_students
    FROM batches b
    JOIN courses c ON b.course_id = c.id
    WHERE b.tenant_id = :tid AND b.deleted_at IS NULL
    ORDER BY b.start_date DESC
");
$stmt->execute(['tid' => $tenantId]);
$batches = $stmt->fetchAll();
?>

<!-- Breadcrumbs -->
<!-- <div class="bc">
    <a href="javascript:goNav('dashboard')">Dashboard</a>
    <span class="bc-sep">/</span>
    <a href="javascript:goNav('academic','batches')">Academic</a>
    <span class="bc-sep">/</span>
    <span class="bc-cur">Batches & Availability</span>
</div> -->

<div class="pg-head" style="display:flex; align-items:center; gap:14px;">
    <div class="sc-ico ic-purple" style="width:44px; height:44px; font-size:20px;">
        <i class="fa-solid fa-users-rectangle"></i>
    </div>
    <div>
        <div class="pg-title">Batches & Availability</div>
        <div class="pg-sub">Check seat availability and schedules for all active courses</div>
    </div>
</div>

<div class="tw">
    <table>
        <thead>
            <tr>
                <th>Batch Name</th>
                <th>Course</th>
                <th>Schedule</th>
                <th>Seats</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($batches as $b): 
                $maxStrength = $b['max_strength'] ?? 0;
                $seatsLeft = $maxStrength - $b['enrolled_students'];
                $statusClass = 'pg';
                if ($b['status'] == 'upcoming') $statusClass = 'py';
                if ($b['status'] == 'completed') $statusClass = 'pn';
                if ($seatsLeft <= 0) $statusClass = 'pr';
            ?>
            <tr>
                <td>
                    <div class="nm"><?php echo htmlspecialchars($b['name']); ?></div>
                    <div class="sub-txt"><?php echo htmlspecialchars($b['batch_code'] ?? 'B-'.str_pad($b['id'],4,'0',STR_PAD_LEFT)); ?></div>
                </td>
                <td><?php echo htmlspecialchars($b['course_name']); ?></td>
                <td>
                    <div class="sub-txt"><i class="fa-regular fa-calendar-check"></i> <?php echo date('M d, Y', strtotime($b['start_date'])); ?></div>
                    <div class="sub-txt"><i class="fa-regular fa-clock"></i> <?php echo $b['start_time'] ?? 'N/A'; ?> - <?php echo $b['end_time'] ?? 'N/A'; ?></div>
                </td>
                <td>
                    <div class="seats-progress">
                        <span class="sub-txt"><?php echo $b['enrolled_students']; ?> / <?php echo $maxStrength; ?></span>
                        <?php if ($seatsLeft > 0): ?>
                            <div class="badge-dot success" title="<?php echo $seatsLeft; ?> seats left"></div>
                        <?php else: ?>
                            <div class="badge-dot danger" title="Full"></div>
                        <?php endif; ?>
                    </div>
                </td>
                <td><span class="pill <?php echo $statusClass; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                <td>
                    <button class="btn bs" onclick="goNav('admissions', 'adm-form')">
                        <i class="fa-solid fa-user-plus"></i> Enroll
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($batches)): ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:40px; color:var(--text-light);">
                    <i class="fa-solid fa-folder-open" style="font-size:32px; margin-bottom:10px; display:block;"></i>
                    No batches found.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.seats-progress { display: flex; align-items: center; gap: 8px; }
.badge-dot { width: 8px; height: 8px; border-radius: 50%; }
.badge-dot.success { background: #10b981; }
.badge-dot.danger { background: #ef4444; }
</style>
