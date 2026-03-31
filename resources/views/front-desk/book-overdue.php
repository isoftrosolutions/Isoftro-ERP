<?php
/**
 * Front Desk — Library: Overdue Tracker
 * Monitor books that haven't been returned by their due date
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Overdue Books';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('library');
}
?>
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #EF4444, #B91C1C);">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h1 class="pg-title">Overdue Books</h1>
                    <p class="pg-sub">List of library assets pending return after due date</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="loadOverdue()"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
                <button class="btn" style="background:#EF4444; color:#fff;" onclick="notifyAll()">
                    <i class="fa-solid fa-paper-plane"></i> Notify All Borrowers
                </button>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="sg mb" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:24px;">
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-red"><i class="fa-solid fa-book"></i></div></div>
                <div class="sc-val" id="overdueCount">-</div>
                <div class="sc-lbl">Total Overdue Books</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-sack-dollar"></i></div></div>
                <div class="sc-val" id="totalFines">-</div>
                <div class="sc-lbl">Projected Fines (NPR)</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-blue"><i class="fa-solid fa-users"></i></div></div>
                <div class="sc-val" id="borrowerCount">-</div>
                <div class="sc-lbl">Unique Borrowers</div>
            </div>
        </div>

        <!-- Overdue Table -->
        <div class="card" style="border-radius:16px; overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                            <th style="padding:14px; text-align:left; font-size:12px; color:#64748b;">Borrower</th>
                            <th style="padding:14px; text-align:left; font-size:12px; color:#64748b;">Book Details</th>
                            <th style="padding:14px; text-align:center; font-size:12px; color:#64748b;">Due Date</th>
                            <th style="padding:14px; text-align:center; font-size:12px; color:#64748b;">Lapsed Days</th>
                            <th style="padding:14px; text-align:right; font-size:12px; color:#64748b;">Current Fine</th>
                            <th style="padding:14px; text-align:center; font-size:12px; color:#64748b;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="overdueTableBody">
                        <tr>
                            <td colspan="6" style="padding:60px; text-align:center; color:#94a3b8;">
                                <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                                Fetching records...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<style>
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
</style>

<script>
async function loadOverdue() {
    const tbody = document.getElementById('overdueTableBody');
    try {
        // Mocking data for now
        const data = [
            { id: 1, borrower: 'Rajesh Hamal', roll: '101', book: 'Advanced Mathematics', acc: 'M-102', due: '2026-02-20', fine: 150 },
            { id: 2, borrower: 'Sujata Karki', roll: '205', book: 'Organic Chemistry', acc: 'C-304', due: '2026-02-25', fine: 50 },
            { id: 3, borrower: 'Binesh Magar', roll: '112', book: 'Digital Logic', acc: 'D-201', due: '2026-02-28', fine: 20 }
        ];
        
        document.getElementById('overdueCount').textContent = data.length;
        document.getElementById('totalFines').textContent = 'NPR ' + data.reduce((s,i) => s + i.fine, 0);
        document.getElementById('borrowerCount').textContent = new Set(data.map(i => i.borrower)).size;
        
        tbody.innerHTML = data.map(i => {
            const due = new Date(i.due);
            const today = new Date();
            const lapsed = Math.ceil((today - due) / (1000 * 60 * 60 * 24));
            
            return `
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:14px;">
                        <div style="font-weight:700; color:#1a1a2e; font-size:13px;">${i.borrower}</div>
                        <div style="font-size:11px; color:#64748b;">Roll: ${i.roll}</div>
                    </td>
                    <td style="padding:14px;">
                        <div style="font-weight:600; color:#475569; font-size:13px;">${i.book}</div>
                        <div style="font-size:11px; color:#94a3b8;">Acc: ${i.acc}</div>
                    </td>
                    <td style="padding:14px; text-align:center; font-size:13px; color:#EF4444; font-weight:700;">${i.due}</td>
                    <td style="padding:14px; text-align:center;"><span style="background:#FEE2E2; color:#B91C1C; font-size:11px; font-weight:800; padding:3px 10px; border-radius:20px;">${lapsed} Days</span></td>
                    <td style="padding:14px; text-align:right; font-weight:800; color:#EF4444;">NPR ${i.fine}</td>
                    <td style="padding:14px; text-align:center;">
                        <button class="btn bt" style="padding:6px 12px; font-size:12px; color:#3B82F6;" onclick="notifyOne(${i.id})">
                            <i class="fa-solid fa-bell"></i> Notify
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" style="padding:40px; text-align:center; color:#EF4444;">Error loading records</td></tr>';
    }
}

function notifyOne(id) { alert('Sending reminder to borrower #' + id); }
function notifyAll() { if(confirm('Send overdue reminders to all borrowers?')) alert('Reminders queued for processing.'); }

document.addEventListener('DOMContentLoaded', loadOverdue);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
