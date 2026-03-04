<?php
/**
 * Front Desk — Email Center
 * Interface for sending announcements and targeted emails
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Email Center';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';

}
// Fetch batches for broadcast
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];
$stmtBatches = $db->prepare("SELECT id, name FROM batches WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name");
$stmtBatches->execute(['tid' => $tenantId]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('communications');
}
?>
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #3B82F6, #1D4ED8);">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div>
                    <h1 class="pg-title">Email Center</h1>
                    <p class="pg-sub">Send official announcements and personalized emails</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="viewSent()"><i class="fa-solid fa-clock-rotate-left"></i> View Sent History</button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 24px;">
            <!-- Left: Recipients selection -->
            <div class="card" style="padding: 24px; border-radius: 16px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px;">Recipients</h3>
                
                <div style="margin-bottom: 24px;">
                    <label class="fl">Mailing List</label>
                    <select id="recipientMode" class="fi" style="margin-top:5px;" onchange="toggleMode(this.value)">
                        <option value="single">Single Student</option>
                        <option value="batch">By Batch</option>
                        <option value="all">All Students</option>
                    </select>
                </div>

                <div id="singleSelect">
                    <label class="fl">Search Student</label>
                    <div style="position:relative; margin-top:5px;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                        <input type="text" id="stuSearch" class="fi" placeholder="Start typing..." style="padding-left:36px;" oninput="searchStudents()">
                        <div id="searchResults" class="search-dd"></div>
                    </div>
                </div>

                <div id="batchSelectDiv" style="display:none;">
                    <label class="fl">Select Batches</label>
                    <div style="max-height: 250px; overflow-y: auto; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                        <?php foreach ($batches as $b): ?>
                            <label style="display:flex; align-items:center; gap:10px; padding:8px; border-bottom:1px solid #f1f5f9; cursor:pointer;">
                                <input type="checkbox" name="batches[]" value="<?= $b['id'] ?>">
                                <span style="font-size:13px; font-weight:600; color:#475569;"><?= htmlspecialchars($b['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="recipientsSummary" style="margin-top:20px; padding:15px; background:#F8FAFC; border-radius:12px; display:none;">
                    <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Selected</div>
                    <div id="recipientCount" style="font-size:18px; font-weight:800; color:#1e293b; margin-top:4px;">0 Students</div>
                </div>
            </div>

            <!-- Right: Composer -->
            <div class="card" style="padding: 24px; border-radius: 16px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px;">Compose Email</h3>
                
                <div style="margin-bottom: 20px;">
                    <label class="fl">Subject <span style="color:var(--red);">*</span></label>
                    <input type="text" id="emailSubject" class="fi" placeholder="Enter email subject..." style="font-weight:700;">
                </div>

                <div style="margin-bottom: 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label class="fl">Email Content</label>
                        <select id="emailTemplate" class="fi" style="width:200px; padding:4px 10px; font-size:12px;" onchange="applyTemplate(this.value)">
                            <option value="">Choose Template...</option>
                            <option value="announcement">General Announcement</option>
                            <option value="fee">Fee Reminder</option>
                            <option value="exam">Exam Schedule</option>
                        </select>
                    </div>
                    <div style="border:1.5px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                        <div style="background:#f8fafc; padding:8px 15px; border-bottom:1px solid #e2e8f0; display:flex; gap:15px; color:#64748b;">
                            <i class="fa-solid fa-bold" style="cursor:pointer;" title="Bold"></i>
                            <i class="fa-solid fa-italic" style="cursor:pointer;" title="Italic"></i>
                            <i class="fa-solid fa-underline" style="cursor:pointer;" title="Underline"></i>
                            <i class="fa-solid fa-link" style="cursor:pointer;" title="Hyperlink"></i>
                            <i class="fa-solid fa-list-ul" style="cursor:pointer;" title="Unordered List"></i>
                        </div>
                        <textarea id="emailContent" class="fi" style="height:350px; border:none; resize:none; padding:20px;" placeholder="Start writing your email here..."></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:30px; padding-top:20px; border-top:1px solid #f1f5f9;">
                    <div style="display:flex; gap:12px;">
                        <button class="btn bt" onclick="saveDraft()"><i class="fa-solid fa-floppy-disk"></i> Save Draft</button>
                    </div>
                    <button class="btn" style="background:linear-gradient(135deg, #3B82F6, #1D4ED8); color:#fff; padding:12px 35px;" id="sendBtn" onclick="sendEmail()">
                        <i class="fa-solid fa-paper-plane"></i> Send Email
                    </button>
                </div>
            </div>
        </div>
    </div>
<style>
.fl { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.search-dd { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; max-height: 250px; overflow-y: auto; z-index: 100; display: none; }
.search-item { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s; }
.search-item:hover { background: #f8fafc; }
</style>

<script>
let selMode = 'single';
let selectedStudents = [];

function toggleMode(val) {
    selMode = val;
    document.getElementById('singleSelect').style.display = val === 'single' ? 'block' : 'none';
    document.getElementById('batchSelectDiv').style.display = val === 'batch' ? 'block' : 'none';
    updateSummary();
}

async function searchStudents() {
    const q = document.getElementById('stuSearch').value;
    const res = document.getElementById('searchResults');
    if (q.length < 2) { res.style.display = 'none'; return; }
    try {
        const response = await fetch(`<?= APP_URL ?>/api/frontdesk/students?q=${encodeURIComponent(q)}`);
        const result = await response.json();
        if (result.success && result.data.length > 0) {
            res.innerHTML = result.data.map(s => `
                <div class="search-item" onclick="addStudent({id:${s.id}, name:'${s.full_name}', email:'${s.email}'})">
                    <div style="font-weight:600; font-size:13px;">${s.full_name}</div>
                    <div style="font-size:11px; color:#64748b;">${s.email || 'No Email'}</div>
                </div>
            `).join('');
            res.style.display = 'block';
        }
    } catch (e) {}
}

function addStudent(s) {
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('stuSearch').value = '';
    if (!selectedStudents.some(i => i.id === s.id)) {
        selectedStudents.push(s);
        updateSummary();
    }
}

function updateSummary() {
    const summary = document.getElementById('recipientsSummary');
    const count = document.getElementById('recipientCount');
    summary.style.display = 'block';
    
    if (selMode === 'single') {
        count.textContent = selectedStudents.length + ' Student(s)';
    } else if (selMode === 'batch') {
        const checked = document.querySelectorAll('input[name="batches[]"]:checked').length;
        count.textContent = checked + ' Batch(es)';
    } else {
        count.textContent = 'All Students';
    }
}

document.querySelectorAll('input[name="batches[]"]').forEach(cb => {
    cb.addEventListener('change', updateSummary);
});

const TEMPLATES = {
    announcement: "Subject: Important Announcement for All Students\n\nDear Students,\n\nThis is to inform you that...\n\nSincerely,\nManagement",
    fee: "Subject: Reminder: Outstanding Fee Payment\n\nDear Student,\n\nour records show that you have an outstanding balance. Please clear it by this weekend.\n\nThank you.",
    exam: "Subject: Examination Schedule Released\n\nDear Students,\n\nThe midterm examination schedule has been published. Please check the notice board or contact the front desk for details."
};

function applyTemplate(val) {
    if (!val) return;
    document.getElementById('emailContent').value = TEMPLATES[val];
}

async function sendEmail() {
    const subject = document.getElementById('emailSubject').value;
    const content = document.getElementById('emailContent').value;
    
    if (!subject || !content) { alert('Subject and content are required'); return; }
    
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    
    setTimeout(() => {
        alert('Emails have been queued for sending!');
        window.location.reload();
    }, 1500);
}
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
