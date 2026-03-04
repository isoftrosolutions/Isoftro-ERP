<?php
/**
 * Front Desk — Student ID Card Generation
 * Bulk or single ID card printing with preview
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Generate ID Cards';
require_once VIEWS_PATH . '/layouts/header_1.php';
require_once __DIR__ . '/sidebar.php';

// Fetch batches for bulk selection
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];
$stmtBatches = $db->prepare("SELECT id, name FROM batches WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name");
$stmtBatches->execute(['tid' => $tenantId]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);
?>

<?php renderFrontDeskHeader(); ?>
<?php renderFrontDeskSidebar('academic'); ?>

<main class="main" id="mainContent">
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #1D4ED8, #3B82F6);">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div>
                    <h1 class="pg-title">ID Card Printing</h1>
                    <p class="pg-sub">Generate and print professional student identification cards</p>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 24px;">
            <!-- Selection Panel -->
            <div>
                <div class="card" style="padding:20px; border-radius:16px;">
                    <h3 style="font-size:14px; font-weight:700; color:#1a1a2e; margin-bottom:15px;">Print Selection</h3>
                    
                    <div style="margin-bottom:20px;">
                        <label class="fl">Selection Mode</label>
                        <div style="display:flex; gap:10px; margin-top:5px;">
                            <label style="flex:1; cursor:pointer;">
                                <input type="radio" name="mode" value="single" checked onchange="switchMode('single')"> 
                                <span style="font-size:13px; font-weight:600; margin-left:4px;">Single Student</span>
                            </label>
                            <label style="flex:1; cursor:pointer;">
                                <input type="radio" name="mode" value="bulk" onchange="switchMode('bulk')"> 
                                <span style="font-size:13px; font-weight:600; margin-left:4px;">Bulk (Batch)</span>
                            </label>
                        </div>
                    </div>

                    <div id="singleSelect">
                        <label class="fl">Search Student</label>
                        <div style="position:relative; margin-top:5px;">
                            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                            <input type="text" id="stuSearch" class="fi" placeholder="Name or Roll No..." style="padding-left:36px;" oninput="searchStudents()">
                            <div id="searchResults" class="search-dd"></div>
                        </div>
                    </div>

                    <div id="bulkSelect" style="display:none;">
                        <label class="fl">Select Batch</label>
                        <select id="batchSelect" class="fi" style="margin-top:5px;" onchange="loadBatchStudents()">
                            <option value="">Choose Batch...</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-top:30px; padding-top:20px; border-top:1px solid #f1f5f9;">
                        <label class="fl">ID Card Template</label>
                        <select class="fi" style="margin-top:5px;">
                            <option value="classic">Classic Professional</option>
                            <option value="modern">Modern Minimalist</option>
                            <option value="landscape">Landscape Badge</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Preview Panel -->
            <div>
                <div class="card" style="background:#f8fafc; border:2px dashed #e2e8f0; height:100%; min-height:500px; display:flex; flex-direction:column; border-radius:16px; overflow:hidden;">
                    <div style="padding:15px 20px; background:#fff; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="font-size:14px; font-weight:700; color:#1a1a2e;"><i class="fa-solid fa-eye" style="margin-right:8px; color:#3B82F6;"></i> Print Preview</h3>
                        <div style="display:flex; gap:10px;">
                            <button class="btn bt" onclick="window.print()"><i class="fa-solid fa-download"></i> PDF</button>
                            <button class="btn" style="background:#1a1a2e; color:#fff;" id="printBtn" disabled onclick="handlePrint()">
                                <i class="fa-solid fa-print"></i> Print Cards
                            </button>
                        </div>
                    </div>
                    
                    <div id="previewContainer" style="flex:1; padding:40px; display:flex; flex-wrap:wrap; gap:20px; justify-content:center; overflow-y:auto;">
                        <div style="text-align:center; color:#94a3b8; margin:auto;">
                            <i class="fa-solid fa-id-card" style="font-size:60px; opacity:0.1; margin-bottom:15px; display:block;"></i>
                            <p>Cards selected for printing will appear here</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ID Card Template Styles -->
<style>
@media print {
    body * { visibility: hidden; }
    #previewContainer, #previewContainer * { visibility: visible; }
    #previewContainer { position: absolute; left: 0; top: 0; width: 100%; height: auto; padding: 0; }
    .pg-head, .pg-acts, .card > div:first-child, .main > div > div:first-child { display: none !important; }
}

.id-card { width: 320px; height: 200px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; position: relative; border: 1px solid #e2e8f0; display: flex; text-align: left; }
.id-card-side { width: 8px; background: linear-gradient(to bottom, #1D4ED8, #3B82F6); }
.id-content { padding: 15px; flex: 1; display: flex; flex-direction: column; }
.id-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
.id-logo { width: 30px; height: 30px; background: #1D4ED8; border-radius: 6px; }
.inst-name { font-size: 11px; font-weight: 800; color: #1e293b; text-transform: uppercase; }
.id-body { display: flex; gap: 15px; }
.id-photo { width: 75px; height: 90px; background: #f1f5f9; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.id-info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.id-row { font-size: 9px; }
.id-lbl { color: #64748b; font-weight: 600; width: 50px; display: inline-block; }
.id-val { color: #1e293b; font-weight: 700; }
.id-name { font-size: 13px; font-weight: 800; color: #1D4ED8; margin-bottom: 5px; }
.id-footer { margin-top: auto; font-size: 8px; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; }

/* Filter styles */
.fl { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.search-dd { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; max-height: 250px; overflow-y: auto; z-index: 100; display: none; }
.search-item { padding: 10px 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s; font-size: 13px; }
.search-item:hover { background: #f8fafc; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
</style>

<script>
let selectedStudents = [];

function switchMode(mode) {
    document.getElementById('singleSelect').style.display = mode === 'single' ? 'block' : 'none';
    document.getElementById('bulkSelect').style.display = mode === 'bulk' ? 'block' : 'none';
    selectedStudents = [];
    updatePreview();
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
                <div class="search-item" onclick="addStudent(${JSON.stringify(s).replace(/"/g, '&quot;')})">
                    <div style="font-weight:600;">${s.full_name}</div>
                    <div style="font-size:11px; color:#64748b;">${s.roll_no} • ${s.batch_name || 'N/A'}</div>
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
        updatePreview();
    }
}

async function loadBatchStudents() {
    const bid = document.getElementById('batchSelect').value;
    if (!bid) return;
    try {
        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/students?batch_id=${bid}`);
        const result = await res.json();
        if (result.success) {
            selectedStudents = result.data || [];
            updatePreview();
        }
    } catch (e) {}
}

function updatePreview() {
    const container = document.getElementById('previewContainer');
    const printBtn = document.getElementById('printBtn');
    
    if (selectedStudents.length === 0) {
        container.innerHTML = `<div style="text-align:center; color:#94a3b8; margin:auto;"><i class="fa-solid fa-id-card" style="font-size:60px; opacity:0.1; margin-bottom:15px; display:block;"></i><p>Cards selected for printing will appear here</p></div>`;
        printBtn.disabled = true;
        return;
    }
    
    printBtn.disabled = false;
    container.innerHTML = selectedStudents.map(s => `
        <div class="id-card">
            <div class="id-card-side"></div>
            <div class="id-content">
                <div class="id-header">
                    <div class="id-logo"></div>
                    <div class="inst-name">Elite Technical Institute</div>
                </div>
                <div class="id-body">
                    <div class="id-photo"><i class="fa-solid fa-user" style="font-size:30px; color:#cbd5e1;"></i></div>
                    <div class="id-info">
                        <div class="id-name">${s.full_name}</div>
                        <div class="id-row"><span class="id-lbl">Roll No:</span> <span class="id-val">${s.roll_no}</span></div>
                        <div class="id-row"><span class="id-lbl">Course:</span> <span class="id-val">${s.course_name || 'N/A'}</span></div>
                        <div class="id-row"><span class="id-lbl">Shift:</span> <span class="id-val">${s.shift || 'N/A'}</span></div>
                        <div class="id-row"><span class="id-lbl">Valid:</span> <span class="id-val">Dec 2026</span></div>
                    </div>
                </div>
                <div class="id-footer">
                    <div>Student Identity Card</div>
                    <div style="font-weight:800; color:#1D4ED8;">PRO</div>
                </div>
            </div>
        </div>
    `).join('');
}

function handlePrint() {
    window.print();
}
</script>

<?php
renderSuperAdminCSS();
echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
?>
</body>
</html>
