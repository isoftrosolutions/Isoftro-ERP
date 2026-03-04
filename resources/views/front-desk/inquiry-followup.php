<?php
/**
 * Front Desk — Inquiry Follow-ups
 * Focuses on leads that need immediate attention
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Follow-Up Reminders';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('inquiries');
}
?>
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #F59E0B, #D97706);">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div>
                    <h1 class="pg-title">Follow-Up Reminders</h1>
                    <p class="pg-sub">Contact leads who haven't converted yet</p>
                </div>
            </div>
        </div>

        <!-- Priority Cards -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
            <div class="card" style="background: #FFFBEB; border-left: 5px solid #F59E0B;">
                <div style="padding: 20px;">
                    <h3 style="color: #92400E; font-size: 14px; margin-bottom: 5px;">Urgent Follow-ups</h3>
                    <div style="font-size: 28px; font-weight: 800; color: #78350F;" id="urgentCount">0</div>
                    <p style="font-size: 12px; color: #92400E; opacity: 0.8;">Leads pending for more than 3 days</p>
                </div>
            </div>
            <div class="card" style="background: #F0F9FF; border-left: 5px solid #0EA5E9;">
                <div style="padding: 20px;">
                    <h3 style="color: #075985; font-size: 14px; margin-bottom: 5px;">Total Pending</h3>
                    <div style="font-size: 28px; font-weight: 800; color: #0C4A6E;" id="pendingTotalCount">0</div>
                    <p style="font-size: 12px; color: #075985; opacity: 0.8;">Total active inquiries in pipeline</p>
                </div>
            </div>
        </div>

        <!-- Follow-up List -->
        <div class="card" style="border-radius: 16px; overflow: hidden;">
            <div style="padding: 16px 20px; background: #fff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1a1a2e;">Follow-up Queue</h3>
                <button class="btn bt" onclick="loadFollowups()"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
            </div>
            <div id="followupContainer">
                <div style="padding: 60px; text-align: center; color: #94a3b8;">
                    <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 32px; margin-bottom: 15px;"></i>
                    <p>Loading follow-up queue...</p>
                </div>
            </div>
        </div>
    </div>
<style>
.followup-item { display: flex; align-items: center; gap: 16px; padding: 20px; border-bottom: 1px solid #f1f5f9; transition: all 0.2s; }
.followup-item:hover { background: #f8fafc; }
.f-avatar { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 18px; }
.btn { padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; border: none; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
.bt { background: #fff; color: #475569; border: 1.5px solid #e2e8f0; }
.btn-sms { background: #3B82F6; color: #fff; }
.btn-call { background: #10B981; color: #fff; }
</style>

<script>
async function loadFollowups() {
    const container = document.getElementById('followupContainer');
    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/inquiries');
        const result = await res.json();
        
        if (result.success) {
            const inquiries = result.data || [];
            const pending = inquiries.filter(i => i.status === 'pending' || i.status === 'follow_up');
            
            // Sort by oldest first
            pending.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            
            const today = new Date();
            const urgentThreshold = 3; // days
            const urgent = pending.filter(i => {
                const created = new Date(i.created_at);
                const diffTime = Math.abs(today - created);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                return diffDays >= urgentThreshold;
            });
            
            document.getElementById('urgentCount').textContent = urgent.length;
            document.getElementById('pendingTotalCount').textContent = pending.length;
            
            renderFollowups(pending);
        }
    } catch (e) {
        container.innerHTML = `<div style="padding:50px; text-align:center; color:#ef4444;">Error loading data</div>`;
    }
}

function renderFollowups(data) {
    const container = document.getElementById('followupContainer');
    if (data.length === 0) {
        container.innerHTML = `<div style="padding:80px; text-align:center; color:#94a3b8;">
            <i class="fa-solid fa-circle-check" style="font-size:48px; color:#10B981; margin-bottom:15px; display:block;"></i>
            <h3>No pending follow-ups!</h3>
            <p>You've cleared all inquiries for now.</p>
        </div>`;
        return;
    }
    
    container.innerHTML = data.map(i => {
        const initials = i.full_name.charAt(0).toUpperCase();
        const colors = ['#6366F1', '#8B5CF6', '#EC4899', '#F59E0B', '#10B981'];
        const color = colors[i.id % colors.length];
        const days = Math.ceil(Math.abs(new Date() - new Date(i.created_at)) / (1000 * 60 * 60 * 24));
        
        return `
            <div class="followup-item">
                <div class="f-avatar" style="background: ${color}">${initials}</div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #1a1a2e; display: flex; align-items: center; gap:8px;">
                        ${i.full_name}
                        ${days >= 3 ? '<span style="background:#FEE2E2; color:#B91C1C; font-size:10px; padding:2px 6px; border-radius:4px;">Lapsed ${days} days</span>' : ''}
                    </div>
                    <div style="font-size: 13px; color: #64748b; margin-top:2px;">
                        Interested in: <strong style="color: #475569;">${i.course_name || 'N/A'}</strong> • Source: ${i.source || 'Walk-in'}
                    </div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top:4px;">
                        <i class="fa-solid fa-calendar" style="margin-right:4px;"></i> Received ${formatDate(i.created_at)}
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-call" onclick="markFollowup(${i.id}, 'call')">
                        <i class="fa-solid fa-phone"></i> Call
                    </button>
                    <button class="btn btn-sms" onclick="markFollowup(${i.id}, 'sms')">
                        <i class="fa-solid fa-comment-sms"></i> SMS
                    </button>
                    <button class="btn bt" onclick="window.location.href='<?= APP_URL ?>/dash/front-desk/admission-form?inquiry_id=${i.id}'">
                        <i class="fa-solid fa-user-plus"></i> Admit
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function formatDate(dateStr) {
    if(!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' at ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

async function markFollowup(id, type) {
    // In a real app, this might open a modal to log the follow-up or update status
    alert('Log ' + type + ' for inquiry #' + id);
    // Optionally update status to 'follow_up'
}

document.addEventListener('DOMContentLoaded', loadFollowups);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
