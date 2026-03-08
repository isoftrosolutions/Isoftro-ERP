/**
 * Hamro ERP — Institute Admin · ia-attendance.js
 * Handles Attendance module logic and rendering
 */

window.renderAttendanceTake = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    _iaRenderBreadcrumb([
        { label: 'Attendance', link: "javascript:goNav('attendance','take')" },
        { label: 'Mark Attendance' }
    ]);

    // Setup global state for keyboard navigation
    window.attState = {
        activeIndex: -1,
        unsaved: false
    };

    window.addEventListener('beforeunload', (e) => {
        if (window.attState.unsaved) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    mc.innerHTML = `
        <style>
            .att-row.active-kb { background: var(--bg-hover) !important; outline: 2px solid var(--brand); outline-offset: -2px; }
            .skeleton-row { height: 60px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: skeleton-loading 1.5s infinite; margin-bottom: 8px; border-radius: 8px; }
            @keyframes skeleton-loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
            .sticky-th { position: sticky; top: 0; background: var(--card-bg); z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        </style>
        <div class="pg fu">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom: 24px;">
                <div>
                    <h2 style="font-size:clamp(1.2rem, 2.5vw, 1.5rem); color:var(--text-dark); margin:0;">Mark Attendance</h2>
                    <p style="color:var(--text-light); margin:5px 0 0 0; font-size:clamp(12px, 1.2vw, 13px);">Manage daily attendance for courses and batches.</p>
                </div>
            </div>

            <div class="card" style="margin-bottom: 24px;">
                <div class="card-body" style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
                    <div style="flex:1; min-width:180px;">
                        <label class="form-label" style="display:block; margin-bottom:6px; font-weight:600; font-size:clamp(11px, 1.1vw, 13px); color:var(--text-dark);">Batch</label>
                        <select id="attBatchSel" class="form-input" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px; font-size:clamp(13px, 1.2vw, 14px);">
                            <option value="">Select Batch...</option>
                        </select>
                    </div>
                    <div style="flex:1; min-width:180px;">
                        <label class="form-label" style="display:block; margin-bottom:6px; font-weight:600; font-size:clamp(11px, 1.1vw, 13px); color:var(--text-dark);">Date</label>
                        <input type="date" id="attDateSel" class="form-input" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px; font-size:clamp(13px, 1.2vw, 14px);" value="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div style="flex:2; min-width:180px;">
                        <label class="form-label" style="display:block; margin-bottom:6px; font-weight:600; font-size:clamp(11px, 1.1vw, 13px); color:var(--text-dark);">Quick Search</label>
                        <input type="text" id="attSearchInp" placeholder="Search by name or roll..." onkeyup="filterAttTable()" class="form-input" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px; font-size:clamp(13px, 1.2vw, 14px);">
                    </div>
                    <div>
                        <button class="qa-btn green" onclick="loadAttendanceRecords()" style="white-space:nowrap; padding:12px 24px; min-height:44px;">
                            <i class="fa-solid fa-search"></i> Load Data
                        </button>
                    </div>
                </div>
            </div>

            <div id="attArea">
                <div style="text-align:center; padding: 100px 40px; color:var(--text-light);">
                    <i class="fa-solid fa-list-check" style="font-size:3.5rem; margin-bottom:16px; opacity:0.3; color:var(--brand);"></i>
                    <h3 style="margin:0; color:var(--text-dark);">Let's mark attendance</h3>
                    <p style="margin:5px 0 0 0; font-size:14px;">Select a batch and date above to load student records.</p>
                </div>
            </div>
        </div>
    `;

    // Load batches dropdown
    try {
        const res = await fetch((window.APP_URL || '') + '/api/admin/batches');
        const data = await res.json();
        if (data.success && data.data) {
            const sel = document.getElementById('attBatchSel');
            data.data.forEach(b => {
                sel.innerHTML += `<option value="${b.id}">${b.name}</option>`;
            });
        }
    } catch(err) {
        console.error('Error loading batches', err);
    }

    // Add CSS for the new Attendance UI
    const style = document.createElement('style');
    style.innerHTML = `
        .att-stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px; }
        @media(min-width: 768px) { .att-stats-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); } }
        .att-stat-card { background: #fff; border-radius: 14px; padding: 16px; border: 1px solid var(--card-border); display: flex; align-items: center; gap: 14px; }
        .att-stat-ico { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .att-stat-val { font-size: clamp(1.2rem, 3vw, 1.6rem); font-weight: 800; color: var(--text-dark); line-height: 1; }
        .att-stat-lbl { font-size: clamp(9px, 1vw, 11px); color: var(--text-light); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        
        .att-quick-actions { background: #fff; border-radius: 14px; border: 1px solid var(--card-border); padding: 12px 16px; display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .att-search-box { position: relative; flex: 1; min-width: 200px; }
        .att-search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 14px; }
        .att-search-box input { width: 100%; padding: 10px 14px 10px 36px; border: 1.5px solid var(--card-border); border-radius: 10px; font-size: 14px; outline: none; transition: 0.2s; }
        .att-search-box input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.1); }
        
        .att-grid { display: grid; grid-template-columns: 1fr; gap: 14px; margin-bottom: 100px; }
        @media(min-width: 640px) { .att-grid { grid-template-columns: repeat(2, 1fr); } }
        @media(min-width: 1024px) { .att-grid { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); } }
        
        .att-stu-card { background: #fff; border-radius: 14px; border: 1px solid var(--card-border); padding: 16px; display: flex; flex-direction: column; gap: 12px; transition: 0.2s; position: relative; }
        .att-stu-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .att-stu-top { display: flex; align-items: center; gap: 12px; }
        .att-stu-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #f1f5f9; }
        .att-stu-info { flex: 1; min-width: 0; }
        .att-stu-name { font-size: clamp(13px, 1.2vw, 15px); font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .att-stu-roll { font-size: clamp(10px, 1vw, 11px); color: var(--text-light); font-weight: 600; }
        
        .att-status-group { display: flex; gap: 8px; }
        .att-pill { flex: 1; height: 44px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #fff; color: var(--text-light); font-size: clamp(12px, 1.2vw, 14px); font-weight: 800; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; -webkit-tap-highlight-color: transparent; }
        .att-pill.active-p { background: #10b981; color: #fff; border-color: #10b981; box-shadow: 0 3px 8px rgba(16, 185, 129, 0.3); }
        .att-pill.active-a { background: #ef4444; color: #fff; border-color: #ef4444; box-shadow: 0 3px 8px rgba(239, 68, 68, 0.3); }
        .att-pill.active-l { background: #f59e0b; color: #fff; border-color: #f59e0b; box-shadow: 0 3px 8px rgba(245, 158, 11, 0.3); }
        .att-pill.active-v { background: #3b82f6; color: #fff; border-color: #3b82f6; box-shadow: 0 3px 8px rgba(59, 130, 246, 0.3); }
        
        .att-locked-tag { position: absolute; top: 12px; right: 12px; font-size: 12px; color: var(--text-light); }
        .att-leave-badge { background: #eff6ff; color: #1d4ed8; font-size: 9px; padding: 2px 6px; border-radius: 4px; font-weight: 700; border: 1px solid #dbeafe; }
        
        .att-sticky-footer { position: fixed; bottom: 0; left: var(--sb-w); right: 0; background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 12px 24px; border-top: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; z-index: 1002; box-shadow: 0 -4px 12px rgba(0,0,0,0.06); }
        @media(max-width: 1024px) { .att-sticky-footer { left: 0; } }
        
        .att-save-btn { background: var(--green); color: #fff; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 700; font-size: clamp(13px, 1.3vw, 15px); cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); min-height: 44px; }
        .att-save-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35); }
        .att-save-btn:active { transform: translateY(0); }
        .att-save-btn:disabled { opacity: 0.6; cursor: not-allowed; box-shadow: none !important; transform: none !important; }
    `;
    document.head.appendChild(style);
};

window.loadAttendanceRecords = async function() {
    const batchId = document.getElementById('attBatchSel').value;
    const date = document.getElementById('attDateSel').value;
    const area = document.getElementById('attArea');

    if (!batchId || !date) {
        alert("Please select both a batch and a date.");
        return;
    }

    window.attState.unsaved = false;
    window.attState.activeIndex = -1;

    area.innerHTML = `
        <div style="padding: 100px; text-align:center; color:var(--text-light);">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 2rem; margin-bottom: 12px; color:var(--brand);"></i>
            <p>Loading school records...</p>
        </div>
    `;

    try {
        const res = await fetch((window.APP_URL || '') + `/api/admin/attendance?batch_id=${batchId}&date=${date}`);
        const data = await res.json();
        
        if (!data.success) throw new Error(data.message);

        const stus = data.data || [];
        
        if (stus.length === 0) {
             area.innerHTML = `<div class="card" style="text-align:center; padding: 80px; color:var(--text-light);"><i class="fa-solid fa-users-slash" style="font-size:3rem; opacity:0.3; margin-bottom:15px;"></i><p>No students found in this batch.</p></div>`;
             return;
        }

        // Summary counters
        let p = 0, a = 0, l = 0, v = 0;
        stus.forEach(r => {
            const s = r.attendance?.status || (r.on_leave ? 'leave' : 'present');
            if (s === 'present') p++; else if (s === 'absent') a++; else if (s === 'late') l++; else if (s === 'leave') v++;
        });

        // Determine global lock state
        const anyLocked = stus.some(r => r.attendance?.locked);
        
        let html = `
            <div class="att-stats-grid">
                <div class="att-stat-card"><div class="att-stat-ico" style="background:#dcfce7; color:#16a34a;"><i class="fa-solid fa-user-check"></i></div><div><div class="att-stat-val" id="cnt_p">${p}</div><div class="att-stat-lbl">Present</div></div></div>
                <div class="att-stat-card"><div class="att-stat-ico" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-user-xmark"></i></div><div><div class="att-stat-val" id="cnt_a">${a}</div><div class="att-stat-lbl">Absent</div></div></div>
                <div class="att-stat-card"><div class="att-stat-ico" style="background:#fef3c7; color:#d97706;"><i class="fa-solid fa-clock"></i></div><div><div class="att-stat-val" id="cnt_l">${l}</div><div class="att-stat-lbl">Late</div></div></div>
                <div class="att-stat-card"><div class="att-stat-ico" style="background:#dbeafe; color:#2563eb;"><i class="fa-solid fa-umbrella-beach"></i></div><div><div class="att-stat-val" id="cnt_v">${v}</div><div class="att-stat-lbl">Leave</div></div></div>
            </div>

            <div class="att-quick-actions">
                <div class="att-search-box">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="attSearchInp" placeholder="Search by name or roll..." oninput="filterAttTable()">
                </div>
                <button class="ar-btn ar-btn-outline" onclick="bulkMarkAll('present')" ${anyLocked ? 'disabled' : ''}><i class="fa-solid fa-check-double"></i> All Present</button>
                <button class="ar-btn ar-btn-outline" onclick="bulkMarkAll('absent')" ${anyLocked ? 'disabled' : ''}><i class="fa-solid fa-xmark"></i> All Absent</button>
            </div>

            <div class="att-grid">
        `;

        // Render rows
        stus.forEach(r => {
            const status = r.attendance?.status || (r.on_leave ? 'leave' : 'present');
            const locked = r.attendance?.locked || 0;
            const aid = r.attendance?.id || '';
            const defaultAvatar = (window.APP_URL || '') + '/public/assets/images/default-avatar.png';

            html += `
                <div class="att-stu-card" data-sid="${r.student_id}" data-id="${aid}">
                    ${locked ? '<div class="att-locked-tag"><i class="fa-solid fa-lock"></i></div>' : ''}
                    <div class="att-stu-top">
                        <img src="${r.photo_url || defaultAvatar}" onerror="this.src='${defaultAvatar}'" class="att-stu-avatar">
                        <div class="att-stu-info">
                            <div class="att-stu-name">${r.full_name}</div>
                            <div class="att-stu-roll">#${r.roll_no} ${r.on_leave ? '<span class="att-leave-badge">LEAVE</span>' : ''}</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:10px; font-weight:800; color:${(r.percentage || 0) < 75 ? 'var(--red)' : 'var(--green)'};">${r.percentage || 0}%</div>
                            <div style="width:40px; height:4px; background:#eee; border-radius:2px; overflow:hidden; margin-top:2px; margin-left:auto;">
                                <div style="width:${r.percentage || 0}%; height:100%; background:${(r.percentage || 0) < 75 ? 'var(--red)' : 'var(--green)'};"></div>
                            </div>
                        </div>
                    </div>
                    <div class="att-status-group">
                        <div class="att-pill p-pill ${status === 'present' ? 'active-p' : ''}" onclick="updateAttStatus(this, 'present')" aria-label="Mark Present" ${locked ? 'disabled' : ''}>P</div>
                        <div class="att-pill a-pill ${status === 'absent' ? 'active-a' : ''}" onclick="updateAttStatus(this, 'absent')" aria-label="Mark Absent" ${locked ? 'disabled' : ''}>A</div>
                        <div class="att-pill l-pill ${status === 'late' ? 'active-l' : ''}" onclick="updateAttStatus(this, 'late')" aria-label="Mark Late" ${locked ? 'disabled' : ''}>L</div>
                        <div class="att-pill v-pill ${status === 'leave' ? 'active-v' : ''}" onclick="updateAttStatus(this, 'leave')" aria-label="Mark Leave" ${locked ? 'disabled' : ''}>LV</div>
                    </div>
                    <input type="hidden" class="att-status-val" value="${status}">
                </div>
            `;
        });

        html += `
            </div>

            <div class="att-sticky-footer">
                <div style="color:var(--text-light); font-size:13px; font-weight:600;"><i class="fa-solid fa-users"></i> ${stus.length} Students loaded</div>
                <button class="att-save-btn" id="saveAttBtn" onclick="saveAttendance()" ${anyLocked ? 'disabled' : ''}>
                    <i class="fa-solid fa-cloud-arrow-up"></i> Save Records
                </button>
            </div>
        `;

        area.innerHTML = html;

    } catch(err) {
        area.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Error: ${err.message}</div>`;
    }

    // Add keyboard listener
    setTimeout(() => {
        document.removeEventListener('keydown', handleAttKeydown);
        document.addEventListener('keydown', handleAttKeydown);
    }, 100);
};

window.handleAttKeydown = function(e) {
    const rows = document.querySelectorAll('.att-stu-card:not([style*="display: none"])');
    if (rows.length === 0) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        window.attState.activeIndex = Math.min(window.attState.activeIndex + 1, rows.length - 1);
        highlightActiveRow(rows);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        window.attState.activeIndex = Math.max(window.attState.activeIndex - 1, 0);
        highlightActiveRow(rows);
    } else if (window.attState.activeIndex >= 0) {
        const card = rows[window.attState.activeIndex];
        const status = { 'p': 'present', 'a': 'absent', 'l': 'late', 'v': 'leave' }[e.key.toLowerCase()];
        if (status) {
            const btn = card.querySelector(`.att-pill.${status.substring(0,1)}-pill`);
            if (btn) updateAttStatus(btn, status);
        }
    }
};

function highlightActiveRow(rows) {
    rows.forEach(r => r.classList.remove('active-kb'));
    const activeRow = rows[window.attState.activeIndex];
    if (activeRow) {
        activeRow.classList.add('active-kb');
        activeRow.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
}

window.updateAttStatus = function(btn, status) {
    if (btn.disabled) return;
    const group = btn.parentElement;
    
    // Reset all pills in group
    group.querySelectorAll('.att-pill').forEach(b => {
        b.classList.remove('active-p', 'active-a', 'active-l', 'active-v');
    });

    // Set active class
    const cls = { present: 'active-p', absent: 'active-a', late: 'active-l', leave: 'active-v' }[status];
    btn.classList.add(cls);

    // Update hidden input
    const card = group.closest('.att-stu-card');
    card.querySelector('.att-status-val').value = status;
    window.attState.unsaved = true;

    // Haptic effect
    btn.style.transform = 'scale(1.1)';
    setTimeout(() => btn.style.transform = '', 150);

    // Recalculate totals
    window.recalcAttTotals();
};

window.recalcAttTotals = function() {
    let p = 0, a = 0, l = 0, v = 0;
    document.querySelectorAll('.att-status-val').forEach(inp => {
        const s = inp.value;
        if (s === 'present') p++;
        else if (s === 'absent') a++;
        else if (s === 'late') l++;
        else if (s === 'leave') v++;
    });

    const sp = document.getElementById('cnt_p');
    const sa = document.getElementById('cnt_a');
    const sl = document.getElementById('cnt_l');
    const sv = document.getElementById('cnt_v');

    if (sp) sp.textContent = p;
    if (sa) sa.textContent = a;
    if (sl) sl.textContent = l;
    if (sv) sv.textContent = v;
};

window.bulkMarkAll = function(status) {
    const rows = document.querySelectorAll('.att-stu-card:not([style*="display: none"])');
    rows.forEach(r => {
        const btn = r.querySelector(`.att-pill.${status.substring(0,1)}-pill`);
        if (btn && !btn.disabled) {
            updateAttStatus(btn, status);
        }
    });
};

window.bulkMark = function(filter, status) {
    const rows = document.querySelectorAll('.att-stu-card:not([style*="display: none"])');
    rows.forEach(r => {
        const current = r.querySelector('.att-status-val').value;
        const aid = r.getAttribute('data-id');
        
        let shouldMark = false;
        if (filter === 'unmarked' && !aid) shouldMark = true;
        
        if (shouldMark) {
            const btn = r.querySelector(`.att-pill.${status.substring(0,1)}-pill`);
            if (btn && !btn.disabled) {
                updateAttStatus(btn, status);
            }
        }
    });
};

window.filterAttTable = function() {
    const q = document.getElementById('attSearchInp').value.toLowerCase();
    document.querySelectorAll('.att-stu-card').forEach(card => {
        const name = card.querySelector('.att-stu-name').textContent.toLowerCase();
        const roll = card.querySelector('.att-stu-roll').textContent.toLowerCase();
        if (name.includes(q) || roll.includes(q)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
    window.attState.activeIndex = -1;
    document.querySelectorAll('.att-stu-card').forEach(c => c.classList.remove('active-kb'));
};

window.saveAttendance = async function() {
    const btn = document.getElementById('saveAttBtn');
    const batchId = document.getElementById('attBatchSel').value;
    const date = document.getElementById('attDateSel').value;
    
    const attendance = [];
    document.querySelectorAll('.att-stu-card').forEach(card => {
        const sid = card.getAttribute('data-sid');
        const status = card.querySelector('.att-status-val').value;
        if (sid && status) {
            attendance.push({ student_id: sid, status: status });
        }
    });

    if (attendance.length === 0) return;

    try {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        const payload = {
            action: 'take',
            batch_id: batchId,
            attendance_date: date,
            attendance: attendance
        };

        const res = await fetch((window.APP_URL || '') + '/api/admin/attendance', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success) {
            window.attState.unsaved = false;
            _iaShowToast('<i class="fa-solid fa-circle-check"></i> Records saved successfully!', 'success');
            loadAttendanceRecords();
        } else {
            throw new Error(data.message);
        }
    } catch(err) {
        _iaShowToast('<i class="fa-solid fa-circle-xmark"></i> Failed: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Save Records';
    }
};

function _iaShowToast(msg, type = 'success') {
    const toast = document.createElement('div');
    toast.style = `position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(100px); background:${type==='success'?'#059669':'#dc2626'}; color:#fff; padding:12px 24px; border-radius:12px; font-weight:700; font-size:14px; box-shadow:0 8px 24px rgba(0,0,0,0.2); transition:0.4s; z-index:9999; display:flex; align-items:center; gap:8px; opacity:0;`;
    toast.innerHTML = msg;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.transform = 'translateX(-50%) translateY(0)'; toast.style.opacity = '1'; }, 100);
    setTimeout(() => { toast.style.transform = 'translateX(-50%) translateY(100px)'; toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 3000);
}


window.renderAttendanceReport = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    _iaRenderBreadcrumb([
        { label: 'Attendance', link: "javascript:goNav('attendance','report')" },
        { label: 'Analytics' }
    ]);

    // Fetch batches for filter
    let batchOptions = '<option value="">All Batches</option>';
    try {
        const bRes = await fetch((window.APP_URL || '') + '/api/admin/batches');
        const bData = await bRes.json();
        if (bData.success && bData.data) {
            bData.data.forEach(b => { batchOptions += `<option value="${b.id}">${b.name}</option>`; });
        }
    } catch(e) {}

    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
    const todayStr = today.toISOString().split('T')[0];

    mc.innerHTML = `
        <style>
            .ar-container { padding: clamp(16px, 3vw, 24px); min-height: 100%; display: flex; flex-direction: column; gap: 20px; }
            .ar-premium-card { background: #fff; border-radius: 24px; padding: clamp(20px, 4vw, 35px); border: 1px solid var(--card-border); box-shadow: var(--shadow-md); position: relative; overflow: hidden; }
            .ar-premium-card::before { content: ""; position: absolute; top: 0; right: 0; width: 300px; height: 300px; background: radial-gradient(circle at top right, rgba(0, 184, 148, 0.05), transparent 70%); pointer-events: none; }
            
            .ar-card-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: clamp(24px, 5vw, 40px); }
            .ar-title-group { display: flex; align-items: center; gap: 14px; }
            .ar-icon-box { width: 44px; height: 44px; border-radius: 12px; background: var(--bg); color: var(--brand); display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
            .ar-title-group h2 { font-size: clamp(1.3rem, 3.5vw, 1.8rem); font-weight: 900; color: var(--text-dark); margin: 0; letter-spacing: -0.03em; }
            .ar-title-group p { font-size: 13px; color: var(--text-light); margin: 4px 0 0; font-weight: 500; }

            .ar-filters { display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; padding: 18px; background: #f8fafc; border-radius: 18px; border: 1.5px solid #edf2f7; margin-bottom: 30px; }
            .ar-filter { display: flex; flex-direction: column; gap: 6px; flex: 1 1 200px; }
            .ar-filter label { font-size: 10px; font-weight: 800; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.1em; padding-left: 2px; }
            .ar-filter select, .ar-filter input { padding: 10px 14px; border: 1.5px solid var(--card-border); border-radius: 12px; font-size: 14px; font-family: inherit; outline: none; background: #fff; height: 48px; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
            .ar-filter select:focus, .ar-filter input:focus { border-color: var(--brand); box-shadow: 0 0 0 4px rgba(0, 184, 148, 0.08); }
            .ar-acts { display: flex; gap: 10px; flex: 1 1 280px; }
            .ar-btn { flex: 1; padding: 12px 20px; border: none; border-radius: 12px; font-size: 14px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; min-height: 48px; }
            .ar-btn-primary { background: var(--brand); color: #fff; box-shadow: 0 4px 15px rgba(0, 184, 148, 0.25); }
            .ar-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 184, 148, 0.3); }
            .ar-btn-outline { background: #fff; color: var(--text-dark); border: 1.5px solid var(--card-border); }
            .ar-btn-outline:hover { border-color: var(--brand); color: var(--brand); background: rgba(0, 184, 148, 0.05); }

            /* Grid Layouts */
            .ar-stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
            .ar-stat-card { padding: 24px; border-radius: 20px; background: #fff; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 14px; transition: 0.3s; position: relative; }
            .ar-stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-color: rgba(0, 184, 148, 0.1); }
            .ar-stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
            .ar-stat-val { font-size: 2rem; font-weight: 900; color: var(--text-dark); line-height: 1; letter-spacing: -0.04em; }
            .ar-stat-lbl { font-size: 11px; color: var(--text-light); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }

            .ar-visuals { display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 30px; }
            @media(min-width: 1100px) { .ar-visuals { grid-template-columns: 1.8fr 1fr; } }

            .ar-block { background: #fff; border-radius: 20px; border: 1.5px solid #f8fafc; padding: 20px; }
            .ar-block-title { font-size: 14px; font-weight: 800; color: var(--text-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
            
            /* Chart Container */
            .ar-chart-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 15px; }
            .ar-chart { display: flex; align-items: flex-end; gap: 8px; height: 240px; padding-top: 30px; min-width: 600px; }
            .ar-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 10px; min-width: 14px; height: 100%; position: relative; }
            .ar-bar-stack { width: 100%; max-width: 24px; height: 100%; display: flex; flex-direction: column; border-radius: 6px 6px 0 0; overflow: hidden; background: #f8fafc; position: relative; cursor: pointer; border: 1px solid rgba(0,0,0,0.02); }
            .ar-bar-label { font-size: 10px; color: var(--text-light); font-weight: 800; }

            /* Tooltip Premium */
            .ar-bar-tooltip { visibility: hidden; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%) translateY(-10px); background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(8px); color: #fff; padding: 12px; border-radius: 12px; font-size: 11px; white-space: nowrap; font-weight: 600; z-index: 100; box-shadow: 0 10px 25px rgba(0,0,0,0.2); opacity: 0; transition: 0.2s; pointer-events: none; border: 1px solid rgba(255,255,255,0.1); }
            .ar-bar-stack:hover .ar-bar-tooltip { visibility: visible; opacity: 1; transform: translateX(-50%) translateY(-15px); }

            /* Absentees Refinement */
            .ar-abs-list { display: grid; gap: 10px; max-height: 400px; overflow-y: auto; padding-right: 4px; }
            .ar-abs-list::-webkit-scrollbar { width: 5px; }
            .ar-abs-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
            .ar-abs-item { display: flex; align-items: center; gap: 14px; padding: 14px; border-radius: 16px; background: #fff; border: 1px solid #f1f5f9; transition: 0.3s; }
            .ar-abs-item:hover { border-color: rgba(225, 29, 72, 0.2); background: rgba(225, 29, 72, 0.02); }
            .ar-abs-info { flex: 1; min-width: 0; }

            @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        </style>

        <div class="ar-container">
            <div class="ar-premium-card">
                <div class="ar-card-header">
                    <div class="ar-title-group">
                        <div class="ar-icon-box"><i class="fa-solid fa-chart-line"></i></div>
                        <div>
                            <h2>Attendance Intelligence</h2>
                            <p>Real-time analytics & absenteeism metrics</p>
                        </div>
                    </div>
                </div>

                <div class="ar-filters">
                    <div class="ar-filter">
                        <label>Cohort / Batch</label>
                        <select id="arBatchFilter">${batchOptions}</select>
                    </div>
                    <div class="ar-filter">
                        <label>Reporting Start</label>
                        <input type="date" id="arStartDate" value="${firstDay}">
                    </div>
                    <div class="ar-filter">
                        <label>Reporting End</label>
                        <input type="date" id="arEndDate" value="${todayStr}">
                    </div>
                    <div class="ar-acts">
                        <button class="ar-btn ar-btn-primary" onclick="loadAnalytics()"><i class="fa-solid fa-wand-magic"></i> Analyze</button>
                        <button class="ar-btn ar-btn-outline" onclick="exportAttendanceCSV()"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                    </div>
                </div>

                <div class="ar-stats-row" id="arStatsContainer">
                    <div class="ar-stat-card"><div class="ar-stat-icon" style="background:#dcfce7; color:#16a34a;"><i class="fa-solid fa-percent"></i></div><div><div class="ar-stat-val" id="arStatPct">-</div><div class="ar-stat-lbl">Presence Quotient</div></div></div>
                    <div class="ar-stat-card"><div class="ar-stat-icon" style="background:#dbeafe; color:#2563eb;"><i class="fa-solid fa-database"></i></div><div><div class="ar-stat-val" id="arStatTotal">-</div><div class="ar-stat-lbl">Total Data Points</div></div></div>
                    <div class="ar-stat-card"><div class="ar-stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-solid fa-clock-rotate-left"></i></div><div><div class="ar-stat-val" id="arStatLate">-</div><div class="ar-stat-lbl">Late Anomalies</div></div></div>
                    <div class="ar-stat-card"><div class="ar-stat-icon" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-user-xmark"></i></div><div><div class="ar-stat-val" id="arStatAbsToday">-</div><div class="ar-stat-lbl">Absent Today</div></div></div>
                </div>

                <div class="ar-visuals">
                    <div class="ar-block">
                        <div class="ar-block-title"><i class="fa-solid fa-chart-column" style="color:var(--brand);"></i> Attendance Velocity</div>
                        <div class="ar-chart-wrap">
                            <div id="arTrendChart" class="ar-chart"><div class="ar-loading">Processing telemetry data...</div></div>
                        </div>
                    </div>
                    <div class="ar-block">
                        <div class="ar-block-title"><i class="fa-solid fa-user-slash" style="color:#ef4444;"></i> Chronic Absenteeism</div>
                        <div id="arAbsenteeList" class="ar-abs-list">
                            <div class="ar-loading">Scanning records...</div>
                        </div>
                    </div>
                </div>

                <div class="ar-block" style="border-top: 1px solid #f1f5f9; padding-top: 24px;">
                    <div class="ar-block-title"><i class="fa-solid fa-network-wired" style="color:var(--brand);"></i> Batch-wise Metric Distribution</div>
                    <div id="arBatchCompare" class="ar-batch-stats-container">
                        <div class="ar-loading">Mapping cohort analytics...</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Auto-load on render
    loadAnalytics();
};

window.loadAnalytics = async function() {
    const batchId = document.getElementById('arBatchFilter')?.value || '';
    const startDate = document.getElementById('arStartDate')?.value || '';
    const endDate = document.getElementById('arEndDate')?.value || '';

    let url = `${window.APP_URL || ''}/api/admin/attendance?action=report&start_date=${startDate}&end_date=${endDate}`;
    if (batchId) url += `&batch_id=${batchId}`;

    try {
        const res = await fetch(url);
        const result = await res.json();

        if (!result.success) { console.error(result.message); return; }
        const d = result.data;

        // -- Stat cards --
        const totalRec = parseInt(d.summary?.total_records || 0);
        const presentCt = parseInt(d.summary?.present || 0);
        const lateCt = parseInt(d.summary?.late || 0);
        const absentCt = parseInt(d.summary?.absent || 0);
        const pctRate = totalRec > 0 ? (((presentCt + lateCt) / totalRec) * 100).toFixed(1) : 0;

        animateCounter('arStatPct', pctRate, '%');
        animateCounter('arStatTotal', totalRec);
        animateCounter('arStatLate', lateCt);
        animateCounter('arStatAbsToday', d.absent_today || 0);

        // -- Trend chart --
        renderTrendChart(d.trend || []);

        // -- Top absentees --
        renderAbsenteeList(d.top_absentees || []);

        // -- Batch comparison --
        renderBatchComparison(d.batch_stats || []);

    } catch(e) {
        console.error('Analytics load error:', e);
    }
};

function animateCounter(elId, targetVal, suffix = '') {
    const el = document.getElementById(elId);
    if (!el) return;
    const target = parseFloat(targetVal) || 0;
    const duration = 600;
    const start = performance.now();
    
    function tick(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        const current = Math.round(eased * target);
        el.textContent = (suffix === '%') ? current + '%' : current.toLocaleString();
        if (progress < 1) requestAnimationFrame(tick);
        else el.textContent = (suffix === '%') ? parseFloat(targetVal).toFixed(1) + '%' : parseInt(target).toLocaleString();
    }
    requestAnimationFrame(tick);
}

function renderTrendChart(trend) {
    const container = document.getElementById('arTrendChart');
    if (!container) return;

    if (trend.length === 0) {
        container.innerHTML = '<div class="ar-empty-chart"><i class="fa-solid fa-chart-column" style="margin-right:8px; opacity:0.4;"></i>No attendance data for this period</div>';
        return;
    }

    const maxTotal = Math.max(...trend.map(t => parseInt(t.total) || 1));

    container.innerHTML = trend.map((t, i) => {
        const total = parseInt(t.total) || 1;
        const present = parseInt(t.present) || 0;
        const late = parseInt(t.late) || 0;
        const absent = parseInt(t.absent) || 0;
        const pct = ((present + late) / total * 100).toFixed(0);
        const h = (total / maxTotal * 100).toFixed(0);
        const dayNum = t.attendance_date.split('-')[2];
        
        const absentH = (absent / total * 100).toFixed(1);
        const lateH = (late / total * 100).toFixed(1);
        const presentH = (present / total * 100).toFixed(1);

        const barColor = pct >= 80 ? '#10b981' : pct >= 60 ? '#f59e0b' : '#ef4444';

        return `
            <div class="ar-bar-wrap" style="animation: fadeInUp 0.4s ease-out forwards; animation-delay:${i * 20}ms; height: 100%;">
                <div class="ar-bar-stack" style="height:${h}%; background: rgba(0,0,0,0.02);">
                    <div style="height:${absentH}%; background:#fee2e2;"></div>
                    <div style="height:${lateH}%; background:#fef3c7;"></div>
                    <div style="height:${presentH}%; background:${barColor}; min-height: 2px;"></div>
                    <div class="ar-bar-tooltip">
                        <div style="font-weight: 800; margin-bottom: 4px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 2px;">${t.attendance_date}</div>
                        <div style="display:flex; justify-content:space-between; gap: 10px;"><span>Present:</span> <span>${present}</span></div>
                        <div style="display:flex; justify-content:space-between; gap: 10px;"><span>Late:</span> <span>${late}</span></div>
                        <div style="display:flex; justify-content:space-between; gap: 10px;"><span>Absent:</span> <span>${absent}</span></div>
                        <div style="margin-top: 4px; color: ${barColor}; font-weight: 800;">Rate: ${pct}%</div>
                    </div>
                </div>
                <div class="ar-bar-label">${parseInt(dayNum)}</div>
            </div>`;
    }).join('');
}

function renderAbsenteeList(absentees) {
    const container = document.getElementById('arAbsenteeList');
    if (!container) return;

    if (absentees.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:60px 20px; color:var(--text-light);"><i class="fa-solid fa-face-smile" style="font-size:40px; margin-bottom:15px; display:block; color:#10b981; opacity: 0.5;"></i><p style="font-size:14px; font-weight: 600;">No chronic absentees found in this period.</p></div>';
        return;
    }

    const defaultAvatar = (window.APP_URL || '') + '/public/assets/images/default-avatar.png';

    container.innerHTML = absentees.map((a, i) => {
        const totalDays = parseInt(a.total_days) || 1;
        const absentDays = parseInt(a.absent_days) || 0;
        const absPct = ((absentDays / totalDays) * 100).toFixed(0);

        return `<div class="ar-abs-item" style="animation: fadeInLeft 0.4s ease-out forwards; animation-delay: ${i * 40}ms;">
            <div class="ar-abs-rank">${i + 1}</div>
            <img class="ar-abs-avatar" src="${a.photo_url || defaultAvatar}" onerror="this.src='${defaultAvatar}'" alt="">
            <div class="ar-abs-info">
                <div class="ar-abs-name">${a.full_name}</div>
                <div class="ar-abs-batch">${a.batch_name || 'N/A'} · ${a.roll_no || '-'}</div>
            </div>
            <div class="ar-abs-count">
                <div class="ar-abs-num">${absentDays}</div>
                <div class="ar-abs-days">${absPct}% Ratio</div>
            </div>
        </div>`;
    }).join('');
}

function renderBatchComparison(batchStats) {
    const container = document.getElementById('arBatchCompare');
    if (!container) return;

    if (batchStats.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-light);"><i class="fa-solid fa-layer-group" style="font-size:32px; margin-bottom:12px; display:block; opacity:0.2;"></i>No batch telemetry available.</div>';
        return;
    }

    container.innerHTML = batchStats.map(b => {
        const total = parseInt(b.total) || 1;
        const present = parseInt(b.present) || 0;
        const late = parseInt(b.late) || 0;
        const absent = parseInt(b.absent) || 0;
        const pPct = (present / total * 100).toFixed(1);
        const lPct = (late / total * 100).toFixed(1);
        const aPct = (absent / total * 100).toFixed(1);
        const overallPct = ((present + late) / total * 100).toFixed(1);

        return `<div class="ar-batch-row">
            <div class="ar-batch-name">${b.batch_name || 'Unknown Cohort'}</div>
            <div class="ar-batch-bar-wrap">
                <div class="ar-batch-seg" style="width:${pPct}%; background:#10b981;"><div class="ar-bar-tooltip">Present: ${present} (${pPct}%)</div></div>
                <div class="ar-batch-seg" style="width:${lPct}%; background:#f59e0b;"><div class="ar-bar-tooltip">Late: ${late} (${lPct}%)</div></div>
                <div class="ar-batch-seg" style="width:${aPct}%; background:#ef4444;"><div class="ar-bar-tooltip">Absent: ${absent} (${aPct}%)</div></div>
            </div>
            <div class="ar-batch-pct" style="color:${overallPct >= 80 ? '#16a34a' : overallPct >= 60 ? '#d97706' : '#dc2626'};">${overallPct}%</div>
        </div>`;
    }).join('');
}

window.exportAttendanceCSV = async function() {
    const batchId = document.getElementById('arBatchFilter')?.value || '';
    const startDate = document.getElementById('arStartDate')?.value || '';
    const endDate = document.getElementById('arEndDate')?.value || '';

    let url = `${window.APP_URL || ''}/api/admin/attendance?action=export&start_date=${startDate}&end_date=${endDate}`;
    if (batchId) url += `&batch_id=${batchId}`;

    try {
        const res = await fetch(url);
        const result = await res.json();
        if (!result.success || !result.data || result.data.length === 0) {
            alert('No data to export for the selected filters.');
            return;
        }

        // Build CSV
        const headers = ['Date', 'Student Name', 'Roll No', 'Batch', 'Status'];
        const rows = result.data.map(r => [r.attendance_date, `"${r.full_name}"`, r.roll_no, `"${r.batch_name || ''}"`, r.status]);
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');

        // Download
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `attendance_report_${startDate}_to_${endDate}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
    } catch(e) {
        alert('Export failed: ' + e.message);
    }
};

window.renderLeaveRequests = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    _iaRenderBreadcrumb([
        { label: 'Attendance', link: "javascript:goNav('attendance','leave')" },
        { label: 'Leave Requests' }
    ]);

    mc.innerHTML = `
        <style>
            .lv-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 20px; }
            @media(min-width: 900px) { .lv-grid { grid-template-columns: repeat(2, 1fr); } }
            .lv-card { background: #fff; border-radius: 14px; border: 1px solid var(--card-border); padding: 20px; display: flex; flex-direction: column; gap: 12px; position: relative; }
            .lv-card-head { display: flex; align-items: center; gap: 12px; }
            .lv-card-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #f1f5f9; }
            .lv-card-info { flex: 1; }
            .lv-card-name { font-size: 15px; font-weight: 700; color: var(--text-dark); }
            .lv-card-roll { font-size: 11px; color: var(--text-light); font-weight: 600; }
            .lv-card-body { background: #f8fafc; border-radius: 10px; padding: 12px; }
            .lv-card-dates { display: flex; gap: 20px; margin-bottom: 8px; }
            .lv-date-item { display: flex; flex-direction: column; }
            .lv-date-lbl { font-size: 9px; color: var(--text-light); text-transform: uppercase; font-weight: 700; }
            .lv-date-val { font-size: 13px; font-weight: 700; color: var(--text-dark); }
            .lv-card-reason { font-size: 13px; color: var(--text-body); border-top: 1px solid #e2e8f0; padding-top: 8px; font-style: italic; }
            .lv-card-acts { display: flex; gap: 8px; margin-top: 4px; }
            .lv-btn { flex: 1; height: 40px; border-radius: 10px; border: none; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; }
            .lv-btn-approve { background: #dcfce7; color: #16a34a; }
            .lv-btn-approve:hover { background: #16a34a; color: #fff; }
            .lv-btn-reject { background: #fee2e2; color: #dc2626; }
            .lv-btn-reject:hover { background: #dc2626; color: #fff; }
            .lv-empty { padding: 100px 20px; text-align: center; color: var(--text-light); }
        </style>

        <div class="pg fu">
            <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                <div>
                    <h1 style="font-size:1.5rem; font-weight:800; color:var(--text-dark); margin:0;">Leave Applications</h1>
                    <p style="font-size:13px; color:var(--text-light); margin:4px 0 0;">Review and approve pending student leaves</p>
                </div>
            </div>
            
            <div id="leaveReqList"><div class="lv-empty"><i class="fa-solid fa-spinner fa-spin" style="font-size:2rem; margin-bottom:12px; color:var(--brand);"></i><p>Fetching requests...</p></div></div>
        </div>
    `;

    try {
        const res = await fetch((window.APP_URL || '') + '/api/admin/leave-requests?status=pending');
        const data = await res.json();
        const list = document.getElementById('leaveReqList');
        
        if (data.success && data.data.length > 0) {
            const defaultAvatar = (window.APP_URL || '') + '/public/assets/images/default-avatar.png';
            let html = '<div class="lv-grid">';
            data.data.forEach(r => {
                html += `
                    <div class="lv-card">
                        <div class="lv-card-head">
                            <img src="${r.photo_url || defaultAvatar}" onerror="this.src='${defaultAvatar}'" class="lv-card-avatar">
                            <div class="lv-card-info">
                                <div class="lv-card-name">${r.full_name}</div>
                                <div class="lv-card-roll">Batch Student · Roll: ${r.roll_no || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="lv-card-body">
                            <div class="lv-card-dates">
                                <div class="lv-date-item"><span class="lv-date-lbl">From</span><span class="lv-date-val">${r.from_date}</span></div>
                                <div class="lv-date-item"><span class="lv-date-lbl">To</span><span class="lv-date-val">${r.to_date}</span></div>
                            </div>
                            <div class="lv-card-reason">"${r.reason}"</div>
                        </div>
                        <div class="lv-card-acts">
                            <button class="lv-btn lv-btn-approve" onclick="actionLeave(this, ${r.id}, 'approve')"><i class="fa-solid fa-check"></i> Approve</button>
                            <button class="lv-btn lv-btn-reject" onclick="actionLeave(this, ${r.id}, 'reject')"><i class="fa-solid fa-xmark"></i> Reject</button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            list.innerHTML = html;
        } else {
            list.innerHTML = `<div class="lv-empty"><i class="fa-solid fa-hotel" style="font-size:3rem; opacity:0.2; margin-bottom:15px;"></i><p>No pending leave requests found.</p></div>`;
        }
    } catch(err) {
        document.getElementById('leaveReqList').innerHTML = `<div class="lv-empty" style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i><p>Error loading requests: ${err.message}</p></div>`;
    }
};

window.actionLeave = async function(btn, id, action) {
    if (!id) return;
    const card = btn.closest('.lv-card');
    
    try {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        const res = await fetch((window.APP_URL || '') + '/api/admin/leave-requests', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id, action: action})
        });
        const data = await res.json();
        
        if (data.success) {
            _iaShowToast(`<i class="fa-solid fa-circle-check"></i> Request ${action}d successfully`, 'success');
            card.style.transform = 'scale(0.9)';
            card.style.opacity = '0';
            setTimeout(() => {
                card.remove();
                if (document.querySelectorAll('.lv-card').length === 0) renderLeaveRequests();
            }, 300);
        } else {
            throw new Error(data.message);
        }
    } catch(err) {
        _iaShowToast(`<i class="fa-solid fa-circle-xmark"></i> ${err.message}`, 'error');
        btn.disabled = false;
        btn.innerHTML = action === 'approve' ? '<i class="fa-solid fa-check"></i> Approve' : '<i class="fa-solid fa-xmark"></i> Reject';
    }
};


// Auto-register to nav clicks if breadcrumb script is loaded
if (typeof _iaRenderBreadcrumb === 'undefined') {
    window._iaRenderBreadcrumb = function() {};
}
