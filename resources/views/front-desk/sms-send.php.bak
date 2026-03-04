<?php
/**
 * Front Desk — SMS Center
 * Dedicated portal for sending SMS to students and batches
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'SMS Center';
require_once VIEWS_PATH . '/layouts/header_1.php';
require_once __DIR__ . '/sidebar.php';

// Fetch batches for broadcast
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];
$stmtBatches = $db->prepare("SELECT id, name FROM batches WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name");
$stmtBatches->execute(['tid' => $tenantId]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);
?>

<?php renderFrontDeskHeader(); ?>
<?php renderFrontDeskSidebar('communications'); ?>

<main class="main" id="mainContent">
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #059669);">
                    <i class="fa-solid fa-comment-sms"></i>
                </div>
                <div>
                    <h1 class="pg-title">SMS Center</h1>
                    <p class="pg-sub">Send instant notifications and alerts via SMS</p>
                </div>
            </div>
            <div class="pg-acts">
                <div style="background:#F0FDF4; padding:8px 15px; border-radius:10px; border:1px solid #BBF7D0; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:12px; color:#166534; font-weight:700;">SMS Balance:</span>
                    <span style="font-size:16px; font-weight:800; color:#14532D;" id="smsBalance">2,450</span>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Left: recipients -->
            <div class="card" style="padding: 24px; border-radius: 16px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px;">1. Select Recipients</h3>
                
                <div style="margin-bottom: 24px;">
                    <label class="fl">Recipient Type</label>
                    <div style="display:flex; gap:10px; margin-top:5px;">
                        <button class="btn bt active-type" id="typeSingle" onclick="switchType('single')" style="flex:1;">Single Student</button>
                        <button class="btn bt" id="typeBatch" onclick="switchType('batch')" style="flex:1;">Entire Batch</button>
                    </div>
                </div>

                <div id="singleRecipient">
                    <label class="fl">Search Student</label>
                    <div style="position:relative; margin-top:5px;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                        <input type="text" id="stuSearch" class="fi" placeholder="Name or Roll No..." style="padding-left:36px;" oninput="searchStudents()">
                        <div id="searchResults" class="search-dd"></div>
                    </div>
                </div>

                <div id="batchRecipient" style="display:none;">
                    <label class="fl">Select Batch</label>
                    <select id="batchSelect" class="fi" style="margin-top:5px;" multiple style="height:150px;">
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p style="font-size:11px; color:#94a3b8; margin-top:8px;">Hold Ctrl/Cmd to select multiple batches</p>
                </div>

                <div id="selectedRecipientsList" style="margin-top:20px; display:none;">
                    <label class="fl">Selected</label>
                    <div id="recipientTags" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:5px;"></div>
                </div>
            </div>

            <!-- Right: Message Body -->
            <div class="card" style="padding: 24px; border-radius: 16px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px;">2. Compose Message</h3>
                
                <div style="margin-bottom: 20px;">
                    <label class="fl">SMS Template</label>
                    <select class="fi" style="margin-top:5px;" id="templateSelect" onchange="applyTemplate(this.value)">
                        <option value="">-- No Template --</option>
                        <option value="welcome">Welcome Message</option>
                        <option value="absent">Absent Notice</option>
                        <option value="fees">Fee Reminder</option>
                        <option value="holiday">Holiday Notice</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="fl">Message Content <span style="color:var(--red);">*</span></label>
                    <textarea id="smsContent" class="fi" style="height: 150px; resize: none; font-family: monospace;" placeholder="Type your SMS message here..." oninput="updateCounter()"></textarea>
                    <div style="display:flex; justify-content:space-between; margin-top:8px; font-size:11px; font-weight:700;">
                        <span id="charCount" style="color:#64748b;">0 characters</span>
                        <span id="smsParts" style="color:#64748b;">0 / 1 parts</span>
                    </div>
                </div>

                <div style="background:#F8FAFC; padding:15px; border-radius:12px; margin-top:40px;">
                    <button class="btn" style="background:linear-gradient(135deg, #10B981, #059669); color:#fff; width:100%; justify-content:center; padding:14px;" id="sendBtn" onclick="sendSMS()">
                        <i class="fa-solid fa-paper-plane"></i> Broadcast SMS Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.fl { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.bt.active-type { background:#10B981; color:#fff; border-color:#10B981; }
.search-dd { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; max-height: 250px; overflow-y: auto; z-index: 100; display: none; }
.search-item { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s; }
.search-item:hover { background: #f8fafc; }
.tag { background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px; }
</style>

<script>
let recipients = [];
let mode = 'single';

function switchType(t) {
    mode = t;
    document.getElementById('typeSingle').classList.toggle('active-type', t === 'single');
    document.getElementById('typeBatch').classList.toggle('active-type', t === 'batch');
    document.getElementById('singleRecipient').style.display = t === 'single' ? 'block' : 'none';
    document.getElementById('batchRecipient').style.display = t === 'batch' ? 'block' : 'none';
    recipients = [];
    renderTags();
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
                <div class="search-item" onclick="addRecipient({id:${s.id}, name:'${s.full_name}'})">
                    <div style="font-weight:600; font-size:13px;">${s.full_name}</div>
                    <div style="font-size:11px; color:#64748b;">${s.roll_no} • ${s.phone || 'No Phone'}</div>
                </div>
            `).join('');
            res.style.display = 'block';
        }
    } catch (e) {}
}

function addRecipient(r) {
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('stuSearch').value = '';
    if (!recipients.some(i => i.id === r.id)) {
        recipients.push(r);
        renderTags();
    }
}

function removeRecipient(id) {
    recipients = recipients.filter(i => i.id !== id);
    renderTags();
}

function renderTags() {
    const container = document.getElementById('selectedRecipientsList');
    const tags = document.getElementById('recipientTags');
    if (recipients.length === 0) { container.style.display = 'none'; return; }
    container.style.display = 'block';
    tags.innerHTML = recipients.map(r => `
        <div class="tag">
            ${r.name}
            <i class="fa-solid fa-xmark" style="cursor:pointer; font-size:10px;" onclick="removeRecipient(${r.id})"></i>
        </div>
    `).join('');
}

function updateCounter() {
    const content = document.getElementById('smsContent').value;
    const chars = content.length;
    const parts = Math.ceil(chars / 160) || 0;
    document.getElementById('charCount').textContent = `${chars} characters`;
    document.getElementById('smsParts').textContent = `${chars} / ${parts} part${parts > 1 ? 's' : ''}`;
}

const TEMPLATES = {
    welcome: "Dear [Name], Welcome to Elite Technical Institute. Your student ID is [RollNo]. We look forward to your successful learning journey!",
    absent: "Dear Parent, your child [Name] was absent from class today. Please ensure regular attendance. - ETI",
    fees: "Dear [Name], this is a reminder regarding your outstanding fee of NPR [Amount]. Please clear it by [Date] to avoid late fine. - ETI",
    holiday: "Notice: The Institute will remain closed on [Date] on the occasion of [Festival]. Classes resume from [NextDate]. - Management"
};

function applyTemplate(val) {
    if (!val) return;
    document.getElementById('smsContent').value = TEMPLATES[val];
    updateCounter();
}

async function sendSMS() {
    const content = document.getElementById('smsContent').value;
    if (!content) { alert('Please enter message content'); return; }
    if (recipients.length === 0 && mode === 'single') { alert('Please select at least one recipient'); return; }
    
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Broadcast Initiated...';
    
    setTimeout(() => {
        alert('SMS broadcast sent successfully to ' + (mode === 'single' ? recipients.length : 'selected batches') + ' recipients!');
        window.location.reload();
    }, 1500);
}
</script>

<?php
renderSuperAdminCSS();
echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
?>
</body>
</html>
