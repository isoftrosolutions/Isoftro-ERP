<?php
/**
 * Shared Attendance Sheet Component
 * Nexus Design System
 */

$apiEndpoint = $apiEndpoint ?? APP_URL . '/api/frontdesk/attendance';
$componentId = $componentId ?? 'shared_att';

// Fetch courses & batches for filters
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];

try {
    $stmtCourses = $db->prepare("SELECT id, name FROM courses WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL ORDER BY name");
    $stmtCourses->execute(['tid' => $tenantId]);
    $courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

    $stmtBatches = $db->prepare("SELECT id, course_id, name, shift FROM batches WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL ORDER BY name");
    $stmtBatches->execute(['tid' => $tenantId]);
    $batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $courses = [];
    $batches = [];
}
?>

<div class="pg-nexus">
    <div class="bc">
        <a href="#" onclick="goNav('overview')">Dashboard</a>
        <span class="bc-sep">&rsaquo;</span>
        <span class="bc-cur">Attendance</span>
    </div>

    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background: rgba(16, 185, 129, 0.08); color: #10B981;">
                <i class="fa-solid fa-clipboard-user"></i>
            </div>
            <div>
                <h1 class="pg-title">Smart Attendance</h1>
                <p class="pg-sub">Track daily student presence with bio-metric consistency</p>
            </div>
        </div>
        <div class="pg-acts">
             <div class="att-today-badge-premium">
                <i class="fa-regular fa-calendar-check"></i>
                <span><?= date('D, M d Y') ?></span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-6" style="padding: 24px; border-radius: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: flex-end;">
            <div class="form-group">
                <label class="lbl">Select Course</label>
                <select id="att_course_sel" class="fi" onchange="syncBatches(this.value)">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="lbl">Batch <span style="color:red">*</span></label>
                <select id="att_batch_sel" class="fi">
                    <option value="">Select Course First</option>
                </select>
            </div>
            <div class="form-group">
                <label class="lbl">Session Date</label>
                <input type="date" id="att_date" class="fi" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn" style="background: #1e293b; color: #fff; height: 44px;" onclick="loadAttendanceSheet()">
                <i class="fa-solid fa-magnifying-glass"></i> Load Students
            </button>
        </div>
    </div>

    <!-- Attendance Sheet Container -->
    <div id="att_sheet_wrapper" style="display: none;">
        <div class="card" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05);">
            <div style="padding: 20px 24px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                   <h3 id="sheet_batch_name" style="margin:0; font-size:16px; font-weight:800;">Batch Name</h3>
                   <div id="sheet_stats" style="margin-top:4px; display:flex; gap:12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                        <span style="color:#10B981" id="stat_p">P: 0</span>
                        <span style="color:#EF4444" id="stat_a">A: 0</span>
                        <span style="color:#F59E0B" id="stat_l">L: 0</span>
                   </div>
                </div>
                <div style="display:flex; gap:10px;">
                    <button class="btn sm bt" onclick="bulkMark('present')">All Present</button>
                    <button class="btn sm bt" onclick="bulkMark('absent')">All Absent</button>
                </div>
            </div>
            <div id="student_list_container" style="max-height: 500px; overflow-y: auto;">
                <!-- Students rendered here -->
            </div>
            <div style="padding: 20px 24px; background: #fff; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
                <button class="btn" style="background: var(--green); color: #fff; padding: 12px 32px;" onclick="commitAttendance()">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Save Attendance
                </button>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div id="att_empty_state" style="padding: 100px 20px; text-align: center;">
         <i class="fa-solid fa-clipboard-list" style="font-size: 64px; color: #e2e8f0; margin-bottom: 20px;"></i>
         <h3 style="font-weight: 800; color: #64748b;">Ready to mark?</h3>
         <p style="color:#94a3b8;">Select a batch and date above to load the attendance sheet.</p>
    </div>
</div>

<script>
(function() {
    const BATCHES = <?= json_encode($batches) ?>;
    const API_URL = "<?= $apiEndpoint ?>";
    let activeStudents = [];

    window.syncBatches = (courseId) => {
        const sel = document.getElementById('att_batch_sel');
        sel.innerHTML = '<option value="">Select Batch</option>';
        const filtered = BATCHES.filter(b => b.course_id == courseId || !courseId);
        filtered.forEach(b => {
             sel.innerHTML += `<option value="${b.id}">${b.name}</option>`;
        });
    }

    window.loadAttendanceSheet = async () => {
        const batchId = document.getElementById('att_batch_sel').value;
        const date = document.getElementById('att_date').value;
        if (!batchId) return alert('Select a batch');

        document.getElementById('att_empty_state').style.display = 'none';
        document.getElementById('att_sheet_wrapper').style.display = 'block';
        const container = document.getElementById('student_list_container');
        container.innerHTML = '<div style="padding:60px; text-align:center;"><i class="fa-solid fa-circle-notch fa-spin"></i> Fetching students...</div>';

        try {
            const res = await fetch(`${API_URL}?action=get_sheet&batch_id=${batchId}&date=${date}`, getHeaders());
            const r = await res.json();
            if (r.success) {
                activeStudents = r.data.map(s => ({
                    student_id: s.student_id,
                    name: s.name || s.full_name || 'N/A',
                    roll_no: s.roll_no,
                    status: s.attendance?.status || 'present'
                }));
                renderStudents();
                document.getElementById('sheet_batch_name').textContent = document.getElementById('att_batch_sel').options[document.getElementById('att_batch_sel').selectedIndex].text;
            }
        } catch(e) { container.innerHTML = 'Error loading sheet.'; }
    }

    function renderStudents() {
        const container = document.getElementById('student_list_container');
        
        container.innerHTML = activeStudents.map((s, i) => `
            <div style="display:flex; align-items:center; padding:16px 24px; border-bottom:1px solid #f8fafc; gap:16px;">
                <div style="width:30px; font-size:11px; font-weight:800; color:#94a3b8;">${i+1}</div>
                <div style="flex:1;">
                    <div style="font-weight:700; color:#1e293b;">${s.name}</div>
                    <div style="font-size:11px; color:#94a3b8;">#${s.roll_no}</div>
                </div>
                <div style="display:flex; gap:6px;">
                    <div class="att-p-btn ${s.status==='present'?'active':''}" onclick="setAtt(${s.student_id}, 'present')">P</div>
                    <div class="att-a-btn ${s.status==='absent'?'active':''}" onclick="setAtt(${s.student_id}, 'absent')">A</div>
                    <div class="att-l-btn ${s.status==='late'?'active':''}" onclick="setAtt(${s.student_id}, 'late')">L</div>
                </div>
            </div>
        `).join('');
        updateAttStats();
    }

    window.setAtt = (id, status) => {
        const s = activeStudents.find(x => x.student_id == id);
        if (s) s.status = status;
        renderStudents();
    }

    window.bulkMark = (status) => {
        activeStudents.forEach(s => s.status = status);
        renderStudents();
    }

    function updateAttStats() {
        const p = activeStudents.filter(s => s.status === 'present').length;
        const a = activeStudents.filter(s => s.status === 'absent').length;
        const l = activeStudents.filter(s => s.status === 'late').length;
        document.getElementById('stat_p').textContent = `P: ${p}`;
        document.getElementById('stat_a').textContent = `A: ${a}`;
        document.getElementById('stat_l').textContent = `L: ${l}`;
    }

    window.commitAttendance = async () => {
        const batchId = document.getElementById('att_batch_sel').value;
        const date = document.getElementById('att_date').value;
        
        try {
            const res = await fetch(`${API_URL}?action=save`, getHeaders({
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ batch_id: batchId, date, attendance_data: activeStudents })
            }));
            const r = await res.json();
            if (r.success) alert('Attendance saved successfully');
        } catch(e) { alert('Failed to save.'); }
    }

    syncBatches('');
})();
</script>

<style>
.att-today-badge-premium { display: flex; align-items: center; gap: 8px; background: #ECFDF5; color: #059669; padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 13px; border: 1px solid #10B981; }
.att-p-btn, .att-a-btn, .att-l-btn { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 800; font-size: 14px; cursor: pointer; border: 2px solid #f1f5f9; color: #94a3b8; transition: all 0.2s; }
.att-p-btn.active { background: #10B981; color: #fff; border-color: #10B981; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2); }
.att-a-btn.active { background: #EF4444; color: #fff; border-color: #EF4444; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2); }
.att-l-btn.active { background: #F59E0B; color: #fff; border-color: #F59E0B; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.2); }
</style>
