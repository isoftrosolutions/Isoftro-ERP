<?php
/**
 * Front Desk — Attendance Marking
 * Daily attendance tracking for batches
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Mark Attendance';
require_once VIEWS_PATH . '/layouts/header_1.php';
require_once __DIR__ . '/sidebar.php';

// Fetch courses & batches
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];

$stmtCourses = $db->prepare("SELECT id, name FROM courses WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL ORDER BY name");
$stmtCourses->execute(['tid' => $tenantId]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

$stmtBatches = $db->prepare("SELECT id, course_id, name, shift FROM batches WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name");
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
                <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #3B82F6);">
                    <i class="fa-solid fa-clipboard-user"></i>
                </div>
                <div>
                    <h1 class="pg-title">Mark Attendance</h1>
                    <p class="pg-sub">Select a batch and date to track student presence</p>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card mb" style="padding:20px; border-radius:16px; display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap; margin-bottom:24px;">
            <div style="flex:1; min-width:200px;">
                <label class="fl">Course</label>
                <select id="courseSelect" class="fi" onchange="filterBatches(this.value)">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1; min-width:200px;">
                <label class="fl">Batch <span style="color:var(--red);">*</span></label>
                <select id="batchSelect" class="fi">
                    <option value="">Select Course First</option>
                </select>
            </div>
            <div style="width:180px;">
                <label class="fl">Date</label>
                <input type="date" id="attendanceDate" class="fi" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn" style="background:#1a1a2e; color:#fff;" onclick="loadStudentList()">
                <i class="fa-solid fa-list-check"></i> Load Students
            </button>
        </div>

        <!-- Attendance Sheet -->
        <div id="attendanceSheet" style="display:none;">
            <div class="card" style="border-radius:16px; overflow:hidden;">
                <div style="padding:16px 20px; background:#f8fafc; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h3 style="font-size:15px; font-weight:700; color:#1a1a2e;" id="batchNameLabel">-</h3>
                        <p style="font-size:12px; color:#64748b;" id="dateLabel">-</p>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button class="btn bt" style="padding:6px 12px; font-size:12px;" onclick="markAll('present')">All Present</button>
                        <button class="btn bt" style="padding:6px 12px; font-size:12px;" onclick="markAll('absent')">All Absent</button>
                    </div>
                </div>

                <div id="studentContainer">
                    <!-- Students rows -->
                </div>

                <div style="padding:20px; background:#f8fafc; border-top:1px solid #f1f5f9; text-align:right;">
                    <button class="btn" style="background:linear-gradient(135deg, #10B981, #059669); color:#fff; padding:12px 30px;" id="saveBtn" onclick="saveAttendance()">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Save Attendance
                    </button>
                </div>
            </div>
        </div>
        
        <div id="emptyState" style="text-align:center; padding:80px 40px; color:#94a3b8;">
            <i class="fa-solid fa-users-rectangle" style="font-size:48px; margin-bottom:15px; opacity:0.3;"></i>
            <p>Select a batch and click "Load Students" to start marking attendance.</p>
        </div>
    </div>
</main>

<style>
.fl { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.stu-row { display: flex; align-items: center; padding: 12px 20px; border-bottom: 1px solid #f1f5f9; transition: background 0.1s; }
.stu-row:hover { background: #fcfdfe; }
.status-pill { border: 1.5px solid #e2e8f0; background: #fff; border-radius: 20px; padding: 6px 16px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
.status-pill.present.active { background: #DCFCE7; color: #166534; border-color: #10B981; }
.status-pill.absent.active { background: #FEE2E2; color: #B91C1C; border-color: #EF4444; }
.status-pill.late.active { background: #FEF3C7; color: #92400E; border-color: #F59E0B; }
</style>

<script>
const BATCHES = <?= json_encode($batches) ?>;
let studentList = [];

function filterBatches(courseId) {
    const sel = document.getElementById('batchSelect');
    sel.innerHTML = '<option value="">Select Batch</option>';
    if (!courseId) return;
    const filtered = BATCHES.filter(b => b.course_id == courseId);
    filtered.forEach(b => {
        sel.innerHTML += `<option value="${b.id}">${b.name} (${b.shift})</option>`;
    });
}

async function loadStudentList() {
    const batchId = document.getElementById('batchSelect').value;
    const date = document.getElementById('attendanceDate').value;
    
    if (!batchId) { alert('Please select a batch'); return; }
    
    document.getElementById('emptyState').style.display = 'none';
    const container = document.getElementById('studentContainer');
    container.innerHTML = '<div style="padding:50px; text-align:center; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading students...</div>';
    document.getElementById('attendanceSheet').style.display = 'block';
    
    try {
        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/attendance?action=get_sheet&batch_id=${batchId}&date=${date}`);
        const result = await res.json();
        
        if (result.success) {
            studentList = result.data || [];
            document.getElementById('batchNameLabel').textContent = document.getElementById('batchSelect').options[document.getElementById('batchSelect').selectedIndex].text;
            document.getElementById('dateLabel').textContent = new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            renderSheet();
        }
    } catch (e) {
        container.innerHTML = '<div style="padding:50px; text-align:center; color:#ef4444;">Error loading student list</div>';
    }
}

function renderSheet() {
    const container = document.getElementById('studentContainer');
    if (studentList.length === 0) {
        container.innerHTML = '<div style="padding:50px; text-align:center; color:#94a3b8;">No students found in this batch.</div>';
        return;
    }
    
    container.innerHTML = studentList.map((s, idx) => `
        <div class="stu-row">
            <div style="width:40px; font-weight:700; color:#94a3b8;">${idx + 1}</div>
            <div style="flex:1;">
                <div style="font-weight:700; color:#1a1a2e;">${s.full_name}</div>
                <div style="font-size:11px; color:#64748b;">Roll No: ${s.roll_no}</div>
            </div>
            <div style="display:flex; gap:8px;">
                <div class="status-pill present ${s.status === 'present' ? 'active' : ''}" onclick="toggleStatus(${s.id}, 'present')">P</div>
                <div class="status-pill absent ${s.status === 'absent' ? 'active' : ''}" onclick="toggleStatus(${s.id}, 'absent')">A</div>
                <div class="status-pill late ${s.status === 'late' ? 'active' : ''}" onclick="toggleStatus(${s.id}, 'late')">L</div>
            </div>
        </div>
    `).join('');
}

function toggleStatus(studentId, status) {
    const s = studentList.find(i => i.id == studentId);
    if (s) {
        s.status = status;
        renderSheet();
    }
}

function markAll(status) {
    studentList.forEach(s => s.status = status);
    renderSheet();
}

async function saveAttendance() {
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    
    const batchId = document.getElementById('batchSelect').value;
    const date = document.getElementById('attendanceDate').value;
    
    try {
        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/attendance`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_attendance',
                batch_id: batchId,
                date: date,
                attendance: studentList.map(s => ({ student_id: s.id, status: s.status || 'present' }))
            })
        });
        
        const result = await res.json();
        if (result.success) {
            alert('Attendance saved successfully!');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (e) {
        alert('Server error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Save Attendance';
    }
}
</script>

<?php
renderSuperAdminCSS();
echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
?>
</body>
</html>
