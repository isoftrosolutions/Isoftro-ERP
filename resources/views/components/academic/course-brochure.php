<?php
/**
 * Shared Course Brochure Component
 * Nexus Design System
 */

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
$courses = $courses ?? [];

if (empty($courses) && $tenantId) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT * FROM courses 
            WHERE tenant_id = :tid AND deleted_at IS NULL AND status = 'active'
            ORDER BY name ASC
        ");
        $stmt->execute(['tid' => $tenantId]);
        $courses = $stmt->fetchAll();
    } catch(Exception $e) {
        $courses = [];
    }
}
?>

<div class="pg-nexus">
    <div class="bc">
        <a href="#" onclick="goNav('overview')">Dashboard</a>
        <span class="bc-sep">&rsaquo;</span>
        <span class="bc-cur">Courses</span>
    </div>

    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background: rgba(139, 92, 246, 0.08); color: #8B5CF6;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <h1 class="pg-title">Program Brochure</h1>
                <p class="pg-sub">Explore our educational programs and fee structures</p>
            </div>
        </div>
    </div>

    <div class="courses-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; margin-top: 24px;">
        <?php foreach ($courses as $c): ?>
            <div class="course-card-premium">
                <div class="course-visual" style="background: linear-gradient(135deg, #8B5CF6, #6366f1); padding: 24px; color: #fff; position: relative; overflow: hidden;">
                    <i class="fa-solid fa-book-open" style="position: absolute; right: -10px; bottom: -10px; font-size: 80px; opacity: 0.1;"></i>
                    <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; opacity: 0.8; letter-spacing: 1px;"><?= htmlspecialchars($c['course_code'] ?? 'N/A') ?></div>
                    <div style="font-size: 20px; font-weight: 800; margin-top: 6px;"><?= htmlspecialchars($c['name']) ?></div>
                    <div style="margin-top: 12px;">
                         <span style="background: rgba(255,255,255,0.2); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">Active Program</span>
                    </div>
                </div>
                <div class="course-body" style="padding: 24px; background: #fff; border-radius: 0 0 16px 16px;">
                    <p style="font-size: 14px; color: #64748b; line-height: 1.6; min-height: 60px; margin-bottom: 20px;">
                        <?= nl2br(htmlspecialchars($c['description'] ?? 'No description available.')) ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                        <div>
                            <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 800;">Duration</div>
                            <div style="font-weight: 700; color: #1e293b; font-size: 14px;"><?= htmlspecialchars($c['duration'] ?? 'N/A') ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 800;">Base Fee</div>
                            <div style="font-weight: 800; color: #10B981; font-size: 16px;">NPR <?= number_format($c['base_fee'] ?? 0) ?></div>
                        </div>
                    </div>
                    
                    <button class="btn" style="width: 100%; height: 44px; margin-top: 24px; background: #f1f5f9; color: #334155; border-radius: 12px; font-weight: 700;" onclick="goNav('inquiries', 'inq-add', {course_id: <?= $c['id'] ?>})">
                        <i class="fa-solid fa-plus-circle"></i> Interest Inquiry
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($courses)): ?>
            <div style="grid-column: 1 / -1; padding: 80px; text-align: center; background: #fff; border-radius: 20px; border: 2px dashed #e2e8f0;">
                <i class="fa-solid fa-folder-open" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                <div style="font-size: 18px; font-weight: 700; color: #64748b;">No active courses available</div>
                <p style="color: #94a3b8; margin-top: 8px;">Please check with the academic administrator.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.course-card-premium { 
    border-radius: 16px; 
    overflow: hidden; 
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid #f1f5f9;
}
.course-card-premium:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
}
</style>
