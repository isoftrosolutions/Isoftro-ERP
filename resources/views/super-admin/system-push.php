<?php
/**
 * ISOFTRO - Push Announcements
 * Variable: $announcements
 */
$announcements = $announcements ?? [];
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>System</span> <span style="color:#94a3b8;"> / Push Announcements</span></div>
        <h1>Push Announcements</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bt" onclick="openPushModal()"><i class="fas fa-megaphone"></i> New Announcement</button>
    </div>
</div>

<div class="g2 mt-20">
    <!-- Send form -->
    <div class="card p-20">
        <div class="ct"><i class="fas fa-paper-plane"></i> Send New Announcement</div>
        <p style="font-size:13px;color:var(--text-light);margin:10px 0 15px;">Broadcasts a platform-wide message to all institute admin dashboards.</p>
        <div class="form-grp">
            <label class="form-lbl">Title</label>
            <input type="text" class="form-inp" id="pushTitle" placeholder="e.g. Scheduled Maintenance on Friday">
        </div>
        <div class="form-grp">
            <label class="form-lbl">Message</label>
            <textarea class="form-inp" id="pushMsg" rows="4" placeholder="Announcement details..."></textarea>
        </div>
        <div class="form-grp">
            <label class="form-lbl">Target Audience</label>
            <select class="form-sel" id="pushAudience">
                <option value="all">All Institutes</option>
                <option value="active">Active Institutes Only</option>
                <option value="trial">Trial Institutes Only</option>
                <option value="admin">Institute Admins Only</option>
            </select>
        </div>
        <div class="form-grp">
            <label class="form-lbl">Type</label>
            <select class="form-sel" id="pushType">
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="success">Success</option>
            </select>
        </div>
        <button class="btn bt mt-10" onclick="sendAnnouncement()"><i class="fas fa-paper-plane"></i> Send Now</button>
    </div>

    <!-- History -->
    <div class="card p-20">
        <div class="ct"><i class="fas fa-history"></i> Announcement History</div>
        <?php if (empty($announcements)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-light);">
            <i class="fas fa-megaphone" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px;"></i>
            <p>No announcements sent yet.</p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;margin-top:15px;max-height:500px;overflow-y:auto;">
            <?php foreach ($announcements as $a): ?>
            <?php $type = $a['type'] ?? 'info'; $colors = ['info'=>['#eff6ff','#1d4ed8'],'warning'=>['#fef9c3','#854d0e'],'success'=>['#f0fdf4','#166534']]; $c=$colors[$type]??$colors['info']; ?>
            <div style="background:<?= $c[0] ?>;border-radius:10px;padding:14px;border-left:4px solid <?= $c[1] ?>;">
                <div style="font-weight:700;color:<?= $c[1] ?>;font-size:14px;"><?= htmlspecialchars($a['title'] ?? '') ?></div>
                <div style="font-size:13px;color:var(--text-dark);margin:5px 0;"><?= htmlspecialchars($a['message'] ?? '') ?></div>
                <div style="font-size:11px;color:var(--text-light);">
                    Sent to: <strong><?= htmlspecialchars($a['target_audience'] ?? 'all') ?></strong>
                    &bull; <?= date('M d, Y H:i', strtotime($a['created_at'] ?? 'now')) ?>
                    &bull; By: <?= htmlspecialchars($a['created_by_email'] ?? 'Admin') ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.form-sel { width:100%;border:1px solid #e2e8f0;padding:10px 14px;border-radius:8px;font-size:14px;margin-top:5px;background:#fff; }
</style>

<script>
async function sendAnnouncement() {
    const title    = document.getElementById('pushTitle').value.trim();
    const message  = document.getElementById('pushMsg').value.trim();
    const audience = document.getElementById('pushAudience').value;
    const type     = document.getElementById('pushType').value;

    if (!title || !message) { SuperAdmin.showNotification('Title and message are required.', 'error'); return; }

    const r = await SuperAdmin.confirmAction('Send this announcement?', '"' + title + '" will be sent to: ' + audience, 'Yes, Send');
    if (!r.isConfirmed) return;

    try {
        const res = await SuperAdmin.fetchAPI('/api/super-admin/tenants/update', {
            method: 'POST',
            body: JSON.stringify({ action: 'announce', title, message, target_audience: audience, type }),
            headers: { 'Content-Type': 'application/json' }
        });
        SuperAdmin.showNotification('Announcement sent!', 'success');
        setTimeout(() => goNav('system-push'), 1000);
    } catch (e) { /* handled */ }
}
</script>
