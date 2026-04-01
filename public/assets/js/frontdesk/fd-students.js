/**
 * iSoftro ERP — Institute Admin · ia-students.js
 * Student domain module: list, profile, quick-add, CRUD
 * Loaded BEFORE ia-core.js so window.* render functions are available.
 */

// Current user reference (operator)
var u = window.currentUser || {};

/* ── STUDENT STATE ─────────────────────────────────────────────── */
window._StudentState = {
    students: [],
    currentPage: 1,
    perPage: 15,
    totalPages: 1,
    total: 0,
    searchQuery: '',
    statusFilter: '',
    selectedIds: new Set()
};

/* ── LOAD STUDENT STATS ──────────────────────────────────────────── */
window.loadStudentStats = async () => {
    try {
        const res = await fetch(window.APP_URL + '/api/frontdesk/students?stats=1');
        const result = await res.json();
        
        if (result.success && result.stats) {
            const st = result.stats;
            const elTotal = document.getElementById('stat-total');
            if (elTotal) elTotal.textContent = st.total || 0;
            
            const elMonth = document.getElementById('stat-month');
            if (elMonth) elMonth.textContent = st.this_month || 0;
            
            const elCourses = document.getElementById('stat-courses');
            if(elCourses) elCourses.textContent = st.courses || 0;
            
            const elBatches = document.getElementById('stat-batches');
            if(elBatches) elBatches.textContent = st.batches || 0;

            const elAlumni = document.getElementById('stat-alumni');
            if(elAlumni) elAlumni.textContent = st.alumni || 0;
        }
    } catch (error) {
        console.error('loadStudentStats error:', error);
    }
};

/* ── EXPORT STUDENTS CSV ─────────────────────────────────────────── */
window.exportStudentsCSV = async () => {
    const params = new URLSearchParams();
    if (_StudentState.searchQuery) params.append('search', _StudentState.searchQuery);
    if (_StudentState.statusFilter) params.append('status', _StudentState.statusFilter);
    params.append('export', 'csv');

    window.location.href = window.APP_URL + '/api/frontdesk/students?' + params.toString();
};

/* ── BULK SELECTION ──────────────────────────────────────────────── */
window.toggleSelection = (id) => {
    if (_StudentState.selectedIds.has(id)) {
        _StudentState.selectedIds.delete(id);
    } else {
        _StudentState.selectedIds.add(id);
    }
    updateBulkBar();
};

window.toggleSelectAll = () => {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    let allSelected = true;
    checkboxes.forEach(cb => { if(!cb.checked) allSelected = false; });
    window.toggleMasterCheck(!allSelected);
};

window.clearSelection = () => {
    _StudentState.selectedIds.clear();
    const checkboxes = document.querySelectorAll('.student-checkbox, #masterCheck');
    checkboxes.forEach(cb => cb.checked = false);
    document.querySelectorAll('.row-sel').forEach(row => { row.classList.remove('row-sel'); row.style.background = ''; });
    updateBulkBar();
};

window.updateBulkBar = () => {
    const bulkBar = document.getElementById('bulkBar');
    const selectedCountSpan = document.getElementById('selectedCount');
    if(!bulkBar || !selectedCountSpan) return;
    
    const count = _StudentState.selectedIds.size;
    if (count > 0) {
        bulkBar.classList.add('visible');
        selectedCountSpan.textContent = count;
    } else {
        bulkBar.classList.remove('visible');
    }
};

/* ── BULK EMAIL MODAL ───────────────────────────────────────────── */
window.openBulkEmailModal = () => {
    const count = _StudentState.selectedIds.size;
    if (count === 0) {
        showToast('Please select at least one student', 'warn');
        return;
    }

    const modalHtml = `
        <div class="modal-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;" onclick="if(event.target === this) this.remove()">
            <div class="modal-content" style="background:#fff;border-radius:12px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="padding:20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
                    <h3 style="margin:0;font-size:18px;font-weight:700;color:#1e293b;">
                        <i class="fa-solid fa-envelope" style="margin-right:8px;color:#009E7E;"></i>
                        Send Email to ${count} Students
                    </h3>
                    <button onclick="this.closest('.modal-overlay').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form id="bulkEmailForm" style="padding:20px;">
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px;">Subject *</label>
                        <input type="text" id="bulkEmailSubject" required
                               style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;"
                               placeholder="Enter email subject...">
                    </div>
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px;">Message *</label>
                        <textarea id="bulkEmailMessage" required rows="8"
                                  style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;"
                                  placeholder="Enter your message..."></textarea>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" onclick="this.closest('.modal-overlay').remove()" 
                                class="btn bs">Cancel</button>
                        <button type="submit" class="btn bt">
                            <i class="fa-solid fa-paper-plane"></i> Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    const modal = document.createElement('div');
    modal.innerHTML = modalHtml;
    document.body.appendChild(modal);

    document.getElementById('bulkEmailForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const subject = document.getElementById('bulkEmailSubject').value;
        const message = document.getElementById('bulkEmailMessage').value;
        const studentIds = Array.from(_StudentState.selectedIds);

        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Sending...';
        submitBtn.disabled = true;

        try {
            const res = await fetch(window.APP_URL + '/api/frontdesk/students', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'bulk_send_email',
                    student_ids: studentIds,
                    subject: subject,
                    message: message
                })
            });
            const result = await res.json();

            if (result.success) {
                showToast(result.message, 'success');
                modal.querySelector('.modal-overlay').remove();
                clearSelection();
            } else {
                showToast(result.message || 'Failed to send emails', 'error');
            }
        } catch (error) {
            console.error('bulk email error:', error);
            showToast('Failed to send emails', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
};

/* ── TOAST NOTIFICATION ─────────────────────────────────────────── */
window.showToast = (message, type = 'info') => {
    const container = document.getElementById('toastWrap') || (() => {
        const div = document.createElement('div');
        div.id = 'toastWrap';
        div.className = 'toast-wrap';
        document.body.appendChild(div);
        return div;
    })();

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fa-solid fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'circle-exclamation' : type === 'warn' ? 'triangle-exclamation' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut .3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

/* ── STUDENT LIST PAGE ─────────────────────────────────────────── */
/* ── STUDENT LIST PAGE ─────────────────────────────────────────── */
window.renderStudentList = async () => {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    // Inject Premium CSS if not already present
    if (!document.getElementById('ia-students-premium-css')) {
        const link = document.createElement('link');
        link.id = 'ia-students-premium-css';
        link.rel = 'stylesheet';
        link.href = window.APP_URL + '/assets/css/ia-students-premium.css';
        document.head.appendChild(link);
    }

    mc.innerHTML = `


  <div class="pg-head" style="justify-content: flex-end;">
    <div class="pg-acts">
      <button class="btn bs" onclick="exportStudentsCSV()"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button class="btn bt" onclick="goNav('students', 'add')"><i class="fa-solid fa-user-plus"></i> Add Student</button>
    </div>
  </div>

  <!-- Glassmorphism Stats Header -->
  <div class="student-stats-grid" id="studentStatsContainer">
    <div class="student-stat-card">
        <div class="stat-icon-box blue"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-label">TOTAL STUDENTS</div>
            <div class="stat-value" id="stat-total">0</div>
        </div>
    </div>
    <div class="student-stat-card">
        <div class="stat-icon-box teal"><i class="fa-solid fa-user-check"></i></div>
        <div class="stat-info">
            <div class="stat-label">NEW THIS MONTH</div>
            <div class="stat-value" id="stat-month">0</div>
        </div>
    </div>
    <div class="student-stat-card">
        <div class="stat-icon-box amber"><i class="fa-solid fa-graduation-cap"></i></div>
        <div class="stat-info">
            <div class="stat-label">TOTAL COURSES</div>
            <div class="stat-value" id="stat-courses">0</div>
        </div>
    </div>
    <div class="student-stat-card">
        <div class="stat-icon-box purple"><i class="fa-solid fa-layer-group"></i></div>
        <div class="stat-info">
            <div class="stat-label">ACTIVE BATCHES</div>
            <div class="stat-value" id="stat-batches">0</div>
        </div>
    </div>
    <!-- Passed Out (Alumni) Stat Card -->
    <div class="student-stat-card">
        <div class="stat-icon-box" style="background: #fef2f2; color: #ef4444;"><i class="fa-solid fa-graduation-cap"></i></div>
        <div class="stat-info">
            <div class="stat-label">PASSED OUT</div>
            <div class="stat-value" id="stat-alumni">0</div>
        </div>
    </div>
  </div>

  <!-- Premium Filter Bar -->
  <div class="premium-filter-bar">
    <div class="premium-search-input">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="searchInput" placeholder="Search by name, roll, email or phone…" value="${_StudentState.searchQuery || ''}" oninput="filterStudents()"/>
    </div>
    <div class="filter-group" style="display:flex; gap:10px;">
        <select id="statusFilter" class="form-control" style="width:150px; border-radius:12px;" onchange="filterStudents()">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="alumni">Alumni</option>
            <option value="dropped">Dropped</option>
        </select>
        <button class="btn bs" id="selectAllBtn" style="border-radius:12px;" onclick="toggleSelectAll()">
            <input type="checkbox" id="toolbarMasterCheck" style="margin-right:8px; pointer-events:none;"/> 
            Select All
        </button>
    </div>
  </div>

  <div class="bulk-bar" id="bulkBar">
    <div class="bulk-count"><i class="fa-solid fa-check-circle"></i> <span id="selectedCount">0</span> student(s) selected</div>
    <div class="bulk-actions">
      <button class="btn bs" onclick="openBulkEmailModal()"><i class="fa-solid fa-envelope"></i> Email</button>
      <button class="btn bs" onclick="bulkExport()"><i class="fa-solid fa-download"></i> Export</button>
      <button class="btn bd" onclick="bulkDrop()"><i class="fa-solid fa-times"></i> Drop</button>
      <button class="btn bs" onclick="clearSelection()"><i class="fa-solid fa-times"></i> Clear</button>
      <button class="btn bd" onclick="bulkDelete()"><i class="fa-solid fa-trash"></i> Delete</button>
    </div>
  </div>
  </div>

  <div class="tw premium-tw">
    <div class="table-responsive">
      <table id="studentsTable" class="table premium-student-table">
        <thead>
          <tr>
            <th style="width:48px; text-align:center;">
               <div class="cb-wrap"><input type="checkbox" id="masterCheck" onchange="window.toggleMasterCheck(this.checked)"/></div>
            </th>
            <th class="sortable" onclick="loadStudents()">STUDENT</th>
            <th class="sortable" onclick="loadStudents()">BATCH &amp; COURSE</th>
            <th>PHONE</th>
            <th class="sortable" onclick="loadStudents()">FEE STATUS</th>
            <th style="text-align:right;">ACTIONS</th>
          </tr>
        </thead>
        <tbody id="studentsBody"></tbody>
      </table>
    </div>

    <div class="empty-state" id="emptyState" style="display:none">
      <div class="empty-ico"><i class="fa-solid fa-magnifying-glass"></i></div>
      <p>No students match your search.</p>
    </div>

    <div class="pagination-footer" id="paginationBar">
      <div class="pag-info" id="pagInfo"></div>
      <div class="pag-btns" id="pagBtns"></div>
    </div>
  </div>
</div>
    `;

    await Promise.all([loadStudentStats(), loadStudents()]);
};

/* ── ALUMNI LIST PAGE ───────────────────────────────────────────── */
window.renderAlumniList = async () => {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `
<div class="pg fu">
  <div class="bc">
    <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a>
    <span class="bc-sep">/</span>
    <a href="#" onclick="goNav('students')">Students</a>
    <span class="bc-sep">/</span>
    <span class="bc-cur">Alumni Records</span>
  </div>

  <div class="pg-head">
    <div class="pg-left">
      <div class="pg-ico" style="background: linear-gradient(135deg, #6366f1, #a855f7); color: #fff;">
        <i class="fa-solid fa-user-graduate"></i>
      </div>
      <div>
        <div class="pg-title" style="font-size: clamp(1.2rem, 3vw, 1.5rem);">Alumni Directory</div>
        <div class="pg-sub">Historical records of graduated students</div>
      </div>
    </div>
    <div class="pg-acts">
      <button class="btn bs" onclick="exportAlumniCSV()"><i class="fa-solid fa-file-export"></i> <span>Export</span></button>
      <!-- Removed New Student button as alumni are converted from existing students -->
    </div>
  </div>

  <div class="toolbar" style="padding: clamp(10px, 2vw, 15px);">
    <div class="search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="searchInput" placeholder="Search alumni records…" oninput="filterAlumni()"/>
    </div>
    <div class="row-count-badge" id="rowCount">0 Alumni</div>
  </div>

  <div class="premium-tw table-responsive">
    <table class="premium-student-table" id="alumniTable">
      <thead>
        <tr>
          <th style="width: 30%;">Student Profile</th>
          <th style="width: 25%;">Batch & Course</th>
          <th style="width: 15%;">Contact</th>
          <th style="width: 15%;">Graduation</th>
          <th style="width: 15%; text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody id="alumniBody"></tbody>
    </table>

    <div class="empty-state-premium" id="emptyState" style="display:none">
       <div class="empty-ico"><i class="fa-solid fa-graduation-cap"></i></div>
       <h4>No Alumni Records</h4>
       <p>No students have been marked as alumni yet.</p>
    </div>

    <div class="pagination-premium" id="paginationBar">
      <div id="pagInfo"></div>
      <div class="pag-btns" id="pagBtns"></div>
    </div>
  </div>
</div>
    `;

    // Set status filter to alumni
    _StudentState.statusFilter = 'alumni';
    await loadAlumni();
};

/* ── LOAD ALUMNI (with pagination) ───────────────────────────── */
window.loadAlumni = async (page) => {
    if (page !== undefined) _StudentState.currentPage = page;
    else _StudentState.currentPage = 1;

    const tbody = document.getElementById('alumniBody');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading alumni...</td></tr>`;

    try {
        const params = new URLSearchParams();
        params.append('page', _StudentState.currentPage);
        params.append('per_page', _StudentState.perPage);
        params.append('status', 'alumni');
        if (_StudentState.searchQuery) params.append('search', _StudentState.searchQuery);

        const res = await fetch(APP_URL + '/api/frontdesk/students?' + params);
        const result = await res.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to load');
        }

        const students = result.data || [];
        const meta = result.meta || {};
        _StudentState.alumni = students;
        _StudentState.total = meta.total || 0;
        _StudentState.totalPages = meta.total_pages || 1;
        _StudentState.currentPage = meta.page || 1;

        const start = ((_StudentState.currentPage - 1) * _StudentState.perPage);
        const end = Math.min(_StudentState.currentPage * _StudentState.perPage, _StudentState.total);
        if (document.getElementById('rowCount')) {
            document.getElementById('rowCount').textContent = `Showing ${_StudentState.total ? start+1 : 0} of ${_StudentState.total}`;
        }

        if (students.length === 0) {
            tbody.innerHTML = '';
            document.getElementById('emptyState').style.display = 'block';
            document.getElementById('paginationBar').style.display = 'none';
            return;
        }

        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('paginationBar').style.display = 'flex';

        tbody.innerHTML = students.map(s => {
            const safeName = (s.full_name || '').replace(/'/g, "\\'");
            const photoUrl = s.photo_url || '';
            const initials = (s.full_name || 'S').charAt(0).toUpperCase();
            const photoHtml = photoUrl 
                ? `<img src="${photoUrl}" class="std-img" alt="${safeName}">`
                : `<div class="std-img initials">${initials}</div>`;
            
            return `
                    <td>
                        <div class="std-card">
                            ${photoHtml}
                            <div class="std-info">
                                <div class="name">${s.full_name || '-'}</div>
                                <div class="id">ID: ${s.student_id || s.roll_no || '-'}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="premium-batch-box">
                            <div class="batch-name">${s.batch_name || '-'}</div>
                            <div class="course-tag">${s.course_name || '-'}</div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 13px; font-weight: 600; color: #1e293b;">${s.phone || '-'}</div>
                        <div style="font-size: 11px; color: #64748b;">${s.email || '-'}</div>
                    </td>
                    <td>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a;">
                            ${s.passout_date || (s.updated_at ? new Date(s.updated_at).toLocaleDateString() : '-')}
                        </div>
                        <span class="badge" style="background: #ecfdf5; color: #059669; font-size: 10px; padding: 2px 8px; border-radius: 12px; font-weight: 700;">GRADUATED</span>
                    </td>
                    <td style="text-align:right">
                        <div class="d-flex justify-content-end gap-2">
                             <button class="btn-icon-p" onclick="goNav('students', 'view', {id:${s.id}})" title="View Profile">
                                 <i class="fa-solid fa-eye"></i>
                             </button>
                             <button class="btn-icon-p" style="background: #f1f5f9; color: #475569;" onclick="restoreStudent(${s.id}, '${safeName}')" title="Restore to Active">
                                 <i class="fa-solid fa-rotate-left"></i>
                             </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        // Render pagination
        const info = document.getElementById('pagInfo');
        const btns = document.getElementById('pagBtns');

        info.textContent = `${_StudentState.total ? start : 0} – ${end} of ${_StudentState.total} alumni`;

        const page = _StudentState.currentPage;
        const total = _StudentState.totalPages;

        let html = `<button class="pag-btn" onclick="loadAlumni(${page-1})" ${page<=1?'disabled':''}>
                      <i class="fa-solid fa-chevron-left"></i>
                    </button>`;
        
        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= page - 1 && i <= page + 1)) {
                const activeClass = (i===page) ? 'active' : '';
                html += `<button class="pag-btn ${activeClass}" onclick="loadAlumni(${i})">${i}</button>`;
            } else if (i === page - 2 || i === page + 2) {
                html += `<span style="padding:0 8px;color:var(--tl)">...</span>`;
            }
        }
        
        html += `<button class="pag-btn" onclick="loadAlumni(${page+1})" ${page>=total?'disabled':''}>
                   <i class="fa-solid fa-chevron-right"></i>
                 </button>`;
        btns.innerHTML = html;

    } catch (error) {
        console.error('loadAlumni error:', error);
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--red);"><i class="fa-solid fa-exclamation-triangle"></i> Failed to load alumni.</td></tr>`;
    }
};

/* ── FILTER ALUMNI ─────────────────────────────────────────────── */
window.filterAlumni = () => {
    _StudentState.searchQuery = (document.getElementById('searchInput')?.value || '').trim();
    _StudentState.currentPage = 1;
    loadAlumni();
};

/* ── EXPORT ALUMNI CSV ─────────────────────────────────────────── */
window.exportAlumniCSV = async () => {
    try {
        const params = new URLSearchParams();
        params.append('status', 'alumni');
        if (_StudentState.searchQuery) params.append('search', _StudentState.searchQuery);
        params.append('export', 'csv');

        const res = await fetch(APP_URL + '/api/frontdesk/students?' + params);
        const result = await res.json();

        if (!result.success || !result.data) {
            throw new Error(result.message || 'Export failed');
        }

        const students = result.data;
        const csv = [
            ['Roll No', 'Name', 'Batch', 'Course', 'Phone', 'Email', 'Status'].join(','),
            ...students.map(s => [
                s.roll_no || s.student_id || '',
                '"' + (u.name || '') + '"',
                '"' + (s.batch_name || '') + '"',
                '"' + (s.course_name || '') + '"',
                u.phone || '',
                u.email || '',
                s.status || ''
            ].join(','))
        ].join('\n');

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'alumni_export_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Export error:', error);
        alert('Failed to export alumni: ' + error.message);
    }
};

/* ── RENDER ADD STUDENT FORM (Premium — matches Institute Admin) ── */
window.renderAddStudentForm = async () => {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    // Show premium loading state
    mc.innerHTML = `
        <div style="display:flex; align-items:center; justify-content:center; min-height:300px; flex-direction:column; gap:16px;">
            <i class="fas fa-spinner fa-spin" style="font-size:2.5rem; color:#00b894;"></i>
            <p style="color:#475569; font-weight:600;">Loading Admission Form...</p>
        </div>
    `;

    try {
        // Fetch the shared PHP partial (same component used by Institute Admin)
        const res = await fetch(`${window.APP_URL}/dash/front-desk/admission-form?partial=true`, {
            credentials: 'same-origin'
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const html = await res.text();

        // Inject the fetched PHP-rendered HTML into the SPA main content
        mc.innerHTML = `
            <div class="pg">
            ${html}
        `;

        // Execute inline scripts from the fetched PHP partial (same pattern as institute admin)
        mc.querySelectorAll('script').forEach(s => {
            try { eval(s.innerHTML); } catch(ex) { console.warn('[renderAddStudentForm] Script eval error:', ex); }
        });

        // Patch the "View All Students" button for SPA navigation
        const viewBtn = mc.querySelector('.pg-acts .btn-p');
        if (viewBtn && typeof window.goNav === 'function') {
            viewBtn.href = '#';
            viewBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.goNav('students');
            });
        }

        // Patch the success modal "View Students" button for SPA navigation
        const origSubmitFn = window['handleAdmissionSubmit_fd'];
        if (origSubmitFn) {
            window['handleAdmissionSubmit_fd'] = async function(e) {
                window._fdSpaRedirectPatch = true;
                return origSubmitFn.call(this, e);
            };
        }

    } catch (err) {
        console.error('[renderAddStudentForm] Failed to load admission form:', err);
        mc.innerHTML = `
            <div style="text-align:center; padding:60px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size:3rem; color:#ff7675; margin-bottom:20px;"></i>
                <h3 style="color:#1e293b; font-weight:800;">Failed to Load Form</h3>
                <p style="color:#64748b; margin-bottom:20px;">Could not load the admission form. Please try again.</p>
                <button class="btn bt" onclick="window.renderAddStudentForm()">
                    <i class="fas fa-redo"></i> Retry
                </button>
                <button class="btn bs" onclick="goNav('students')" style="margin-left:10px;">
                    Back to Students
                </button>
            </div>
        `;
    }
};

/* ── RENDER EDIT STUDENT FORM ──────────────────────────────────── */
window.renderEditStudentForm = async (id) => {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `
        <div class="pg fu">
            <div class="bc">
                <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                <a href="#" onclick="goNav('students')">Students</a> <span class="bc-sep">›</span> 
                <span class="bc-cur">Edit Student</span>
            </div>
            <div class="pg-head">
                <div class="pg-left">
                    <div class="pg-ico"><i class="fa-solid fa-user-pen"></i></div>
                    <div>
                        <div class="pg-title">Edit Student Details</div>
                        <div class="pg-sub">Update all student profile information</div>
                    </div>
                </div>
            </div>

            <div class="card fu" style="max-width:1200px; margin:0 auto; padding:clamp(15px, 3vw, 30px);">
                <form id="studentEditForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_std_id" value="${id}">
                    <input type="hidden" name="registration_status" id="edit_reg_status" value="">
                    
                    <!-- Photo & Basic Info Section -->
                    <div style="display:flex; flex-wrap:wrap; gap:clamp(15px, 3vw, 30px); margin-bottom:clamp(20px, 4dvh, 30px); align-items:flex-start; padding-bottom:30px; border-bottom:1px solid #e2e8f0;">
                        <div style="flex-shrink:0; text-align:center; width: clamp(140px, 100%, 160px); margin: 0 auto;">
                            <div id="imagePreviewContainer" style="width:clamp(120px, 100%, 140px); height:clamp(120px, 100%, 140px); border-radius:15px; background:#f1f5f9; border:2px dashed #cbd5e1; display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative; margin-bottom:12px; margin: 0 auto 12px;">
                                <i class="fa-solid fa-user" id="stdImgIcon" style="font-size:3rem; color:#94a3b8;"></i>
                                <img id="stdImgPreview" src="" style="width:100%; height:100%; object-fit:cover; display:none;">
                            </div>
                            <label class="btn bs" style="cursor:pointer; font-size:12px; padding:6px 12px; width:100%;">
                                <i class="fa-solid fa-camera"></i> Change Photo
                                <input type="file" name="profile_image" id="stdImgInput" accept="image/*" style="display:none;" onchange="window._previewStdImage(this)">
                            </label>
                        </div>
                        <div style="flex: 1; min-width: clamp(280px, 100%, 600px);">
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(clamp(200px, 100%, 250px), 1fr)); gap:clamp(10px, 2vw, 20px);">
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="full_name" id="edit_std_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" id="edit_std_email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Number *</label>
                                    <input type="text" name="phone" id="edit_std_phone" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Roll No</label>
                                    <input type="text" name="roll_no" id="edit_std_roll" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" id="edit_std_status" class="form-control">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="on-leave">On Leave</option>
                                        <option value="graduated">Graduated</option>
                                        <option value="dropped">Dropped</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information Section -->
                    <div style="margin-bottom:30px;">
                        <h3 style="font-size:clamp(0.9rem, 2vw, 1.1rem); font-weight:600; margin-bottom:20px; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-user-circle" style="color:#009E7E;"></i> Personal Information
                        </h3>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(clamp(150px, 100%, 250px), 1fr)); gap:clamp(10px, 2vw, 20px);">
                            <div class="form-group">
                                <label class="form-label">Father's Name</label>
                                <input type="text" name="father_name" id="edit_father_name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mother's Name</label>
                                <input type="text" name="mother_name" id="edit_mother_name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gender</label>
                                <select name="gender" id="edit_gender" class="form-control">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" id="edit_blood_group" class="form-control">
                                    <option value="">Select Blood Group</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date of Birth (AD)</label>
                                <input type="date" name="dob_ad" id="edit_dob_ad" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date of Birth (BS)</label>
                                <input type="text" name="dob_bs" id="edit_dob_bs" class="form-control" placeholder="YYYY-MM-DD (B.S.)">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Citizenship No.</label>
                                <input type="text" name="citizenship_no" id="edit_citizenship_no" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">National ID</label>
                                <input type="text" name="national_id" id="edit_national_id" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Guardian Name</label>
                                <input type="text" name="guardian_name" id="edit_guardian_name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Guardian Relation</label>
                                <input type="text" name="guardian_relation" id="edit_guardian_relation" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Husband's Name</label>
                                <input type="text" name="husband_name" id="edit_husband_name" class="form-control">
                            </div>
                        </div>
                    </div>



                    <!-- Permanent Address Section -->
                    <div style="margin-bottom:30px;">
                        <h3 style="font-size:clamp(0.9rem, 2vw, 1.1rem); font-weight:600; margin-bottom:20px; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-home" style="color:#009E7E;"></i> Permanent Address
                        </h3>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(clamp(150px, 100%, 250px), 1fr)); gap:clamp(10px, 2vw, 20px);">
                            <div class="form-group">
                                <label class="form-label">Province</label>
                                <select name="permanent_province" id="edit_permanent_province" class="form-control" onchange="window._updateDistrictSelect(this.value, 'edit_permanent_district')">
                                    <option value="">Select Province</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">District</label>
                                <select name="permanent_district" id="edit_permanent_district" class="form-control">
                                    <option value="">Select District</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Municipality</label>
                                <input type="text" name="permanent_municipality" id="edit_permanent_municipality" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ward No.</label>
                                <input type="text" name="permanent_ward" id="edit_permanent_ward" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Temporary Address Section -->
                    <div style="margin-bottom:30px;">
                        <h3 style="font-size:clamp(0.9rem, 2vw, 1.1rem); font-weight:600; margin-bottom:20px; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-location-arrow" style="color:#009E7E;"></i> Temporary Address
                        </h3>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(clamp(150px, 100%, 250px), 1fr)); gap:clamp(10px, 2vw, 20px);">
                            <div class="form-group">
                                <label class="form-label">Province</label>
                                <select name="temporary_province" id="edit_temporary_province" class="form-control" onchange="window._updateDistrictSelect(this.value, 'edit_temporary_district')">
                                    <option value="">Select Province</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">District</label>
                                <select name="temporary_district" id="edit_temporary_district" class="form-control">
                                    <option value="">Select District</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Municipality</label>
                                <input type="text" name="temporary_municipality" id="edit_temporary_municipality" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ward No.</label>
                                <input type="text" name="temporary_ward" id="edit_temporary_ward" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Academic Selection Section -->
                    <div style="margin-bottom:30px;">
                        <h3 style="font-size:clamp(0.9rem, 2vw, 1.1rem); font-weight:600; margin-bottom:20px; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-graduation-cap" style="color:#009E7E;"></i> Academic Selection
                        </h3>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(clamp(150px, 100%, 250px), 1fr)); gap:clamp(10px, 2vw, 20px);">
                            <div class="form-group">
                                <label class="form-label">Course *</label>
                                <select name="course_id" id="edit_std_course" class="form-control" required onchange="window._loadBatchesForForm(this.value, 'edit_std_batch')">
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Batch *</label>
                                <select name="batch_id" id="edit_std_batch" class="form-control" required disabled>
                                    <option value="">Select Course First</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Admission Date</label>
                                <input type="date" name="admission_date" id="edit_admission_date" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Academic Qualifications Section -->
                    <div style="margin-bottom:30px;">
                        <h3 style="font-size:1.1rem; font-weight:600; margin-bottom:20px; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-graduation-cap" style="color:#009E7E;"></i> Academic Qualifications
                        </h3>
                        <div id="academic_qualifications_container">
                            <!-- Academic qualifications will be loaded here -->
                        </div>
                        <button type="button" class="btn bs" style="margin-top:15px;" onclick="window._addAcademicQualification()">
                            <i class="fa-solid fa-plus"></i> Add Qualification
                        </button>
                    </div>

                    <div style="margin-top:clamp(20px, 4vw, 40px); display:flex; flex-wrap:wrap; gap:10px; justify-content:flex-end;">
                        <button type="button" class="btn bs" style="flex:clamp(1, 1, 1);" onclick="goNav('students')">Cancel</button>
                        <button type="submit" class="btn bt" style="flex:clamp(1, 1, 1);">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // Populate Province Selects immediately after setting innerHTML
    if (window._populateProvinceSelect) {
        console.log("Populating provinces in edit form...");
        window._populateProvinceSelect('edit_permanent_province');
        window._populateProvinceSelect('edit_temporary_province');
    } else {
        console.warn("window._populateProvinceSelect not found! nepal-data.js might not be loaded correctly.");
    }

    await _populateCoursesInForm('edit_std_course');
    
    await _loadStudentDataForEdit(id);
    document.getElementById('studentEditForm').onsubmit = (e) => _submitStudentForm(e, 'PUT');
    
    // Add Date Conversion Listeners
    const adInput = document.getElementById('edit_dob_ad');
    const bsInput = document.getElementById('edit_dob_bs');
    if (adInput && bsInput) {
        const handleConvert = async (e, type) => {
            const date = e.target.value;
            if (!date || date.length < 10) return;
            try {
                const res = await fetch(`${window.APP_URL}/api/frontdesk/date-convert?date=${date}&type=${type}`);
                const result = await res.json();
                if (result.success) {
                    if (type === 'ad') bsInput.value = result.converted;
                    else adInput.value = result.converted;
                }
            } catch (err) { console.error('Date conversion failed', err); }
        };
        adInput.addEventListener('change', (e) => handleConvert(e, 'ad'));
        bsInput.addEventListener('blur', (e) => handleConvert(e, 'bs'));
    }

    // Identity preview helper
    window._previewIdentityDocName = (input) => {
        const status = document.getElementById('identity_doc_status');
        if (status && input.files && input.files[0]) {
            status.textContent = input.files[0].name;
            status.style.color = 'var(--sa-primary,#009E7E)';
        }
    };
};

/* ── FORM HELPERS ──────────────────────────────────────────────── */
async function _populateCoursesInForm(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    try {
        const res = await fetch(window.APP_URL + '/api/frontdesk/courses');
        const data = await res.json();
        if (data.success) {
            data.data.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `${c.name} (${c.code})`;
                sel.appendChild(opt);
            });
        }
    } catch (e) { console.error('Populate courses error', e); }
}

window._loadBatchesForForm = async (courseId, targetSelectId = 'std_batch_select') => {
    const sel = document.getElementById(targetSelectId);
    if (!sel) return;
    if (!courseId) { sel.innerHTML = '<option value="">Select Course First</option>'; sel.disabled = true; return; }
    
    sel.innerHTML = '<option value="">Loading...</option>'; sel.disabled = true;
    try {
        const res = await fetch(`${window.APP_URL}/api/frontdesk/batches?course_id=${courseId}`);
        const data = await res.json();
        if (data.success && data.data.length) {
            let html = '<option value="">Select Batch</option>';
            data.data.forEach(b => {
                html += `<option value="${b.id}">${b.name} (${b.shift})</option>`;
            });
            sel.innerHTML = html;
            sel.disabled = false;
        } else {
            sel.innerHTML = '<option value="">No batches found</option>';
        }
    } catch (e) {
        sel.innerHTML = '<option value="">Error!</option>';
    }
};

async function _loadStudentDataForEdit(id) {
    try {
        const res = await fetch(`${window.APP_URL}/api/frontdesk/students?id=${id}`);
        const result = await res.json();
        if (result.success) {
            const s = result.data;
            
            // Basic Info
            document.getElementById('edit_std_name').value = u.name || '';
            document.getElementById('edit_std_phone').value = u.phone || '';
            document.getElementById('edit_std_email').value = u.email || '';
            document.getElementById('edit_std_roll').value = s.roll_no || '';
            document.getElementById('edit_std_status').value = s.status || 'active';
            
            // Personal Information
            document.getElementById('edit_father_name').value = s.father_name || '';
            document.getElementById('edit_mother_name').value = s.mother_name || '';
            document.getElementById('edit_gender').value = s.gender || '';
            document.getElementById('edit_blood_group').value = s.blood_group || '';
            document.getElementById('edit_dob_ad').value = s.dob_ad || '';
            document.getElementById('edit_dob_bs').value = s.dob_bs || '';
            document.getElementById('edit_citizenship_no').value = s.citizenship_no || '';
            document.getElementById('edit_national_id').value = s.national_id || '';
            document.getElementById('edit_guardian_name').value = s.guardian_name || '';
            document.getElementById('edit_guardian_relation').value = s.guardian_relation || '';
            if (document.getElementById('edit_husband_name')) {
                document.getElementById('edit_husband_name').value = s.husband_name || '';
            }

            // Document display
            if (s.identity_doc_url) {
                const viewBtn = document.getElementById('view_current_identity_doc');
                if (viewBtn) {
                    viewBtn.href = s.identity_doc_url;
                    viewBtn.style.display = 'block';
                }
            }

            // Academic Selection
            if (document.getElementById('edit_admission_date')) {
                document.getElementById('edit_admission_date').value = s.admission_date || '';
            }

            // Sync Course and Batch selection
            if (s.course_id) {
                document.getElementById('edit_std_course').value = s.course_id;
                await _loadBatchesForForm(s.course_id, 'edit_std_batch');
                if (s.batch_id) {
                    document.getElementById('edit_std_batch').value = s.batch_id;
                }
            } else if (s.batch_id) {
                // If only batch_id is present, we might need to find the course_id first
                // But normally the API returns both if joined correctly.
                document.getElementById('edit_std_batch').value = s.batch_id;
            }
            
            // Parse addresses from JSON strings (with fallbacks for legacy keys)
            const parseAddr = (str) => { if (!str) return {}; try { return typeof str === 'string' ? JSON.parse(str) : str; } catch(e) { return {}; } };
            const pAddr = parseAddr(s.permanent_address);
            const tAddr = parseAddr(s.temporary_address);
            
            // Helper to set province value robustly (handles "Koshi" matching "Koshi Province")
            const setProvinceValue = (selectId, value) => {
                const sel = document.getElementById(selectId);
                if (!sel || !value) return;
                
                // Try direct match first
                sel.value = value;
                
                // If not matched, try fuzzy match
                if (!sel.value) {
                    const search = value.toLowerCase().replace(" province", "").trim();
                    for (let opt of sel.options) {
                        const optVal = opt.value.toLowerCase().replace(" province", "").trim();
                        if (optVal === search) {
                            sel.value = opt.value;
                            break;
                        }
                    }
                }
            };

            // Permanent Address
            if (pAddr.province) {
                setProvinceValue('edit_permanent_province', pAddr.province);
                window._updateDistrictSelect(document.getElementById('edit_permanent_province').value, 'edit_permanent_district');
                if (pAddr.district) {
                    document.getElementById('edit_permanent_district').value = pAddr.district;
                }
            }
            document.getElementById('edit_permanent_municipality').value = pAddr.municipality || pAddr.local || pAddr.local_level || '';
            document.getElementById('edit_permanent_ward').value = pAddr.ward || '';
            
            // Temporary Address
            if (tAddr.province) {
                setProvinceValue('edit_temporary_province', tAddr.province);
                window._updateDistrictSelect(document.getElementById('edit_temporary_province').value, 'edit_temporary_district');
                if (tAddr.district) {
                    document.getElementById('edit_temporary_district').value = tAddr.district;
                }
            }
            document.getElementById('edit_temporary_municipality').value = tAddr.municipality || tAddr.local || tAddr.local_level || '';
            document.getElementById('edit_temporary_ward').value = tAddr.ward || '';
            
            // Academic Qualifications (with fallback for institution/grade)
            let qualsRaw = parseAddr(s.academic_qualifications);
            const quals = Array.isArray(qualsRaw) ? qualsRaw.map(q => ({
                level: q.level || '',
                school: q.school || q.institution || '',
                year: q.year || '',
                percentage: q.percentage || q.grade || ''
            })) : [];
            window._renderAcademicQualifications(quals);
            
            if (s.registration_status === 'quick_registered') {
                document.getElementById('edit_reg_status').value = 'fully_registered';
            } else {
                document.getElementById('edit_reg_status').value = s.registration_status || 'fully_registered';
            }

            // Photo
            if (s.photo_url) {
                const img = document.getElementById('stdImgPreview');
                const ico = document.getElementById('stdImgIcon');
                if (img) { img.src = s.photo_url; img.style.display = 'block'; }
                if (ico) { ico.style.display = 'none'; }
            }
        }
    } catch (e) { console.error(e); Swal.fire('Error', 'Failed to load student data', 'error'); }
}

// Render academic qualifications in the edit form
window._renderAcademicQualifications = (quals) => {
    const container = document.getElementById('academic_qualifications_container');
    if (!container) return;
    
    if (!quals || quals.length === 0) {
        container.innerHTML = `
            <div class="academic-qual-row" style="display:grid; grid-template-columns:2fr 2fr 1fr 1fr auto; gap:10px; align-items:end; margin-bottom:10px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Level/Qualification</label>
                    <input type="text" name="qual_level[]" class="form-control" placeholder="e.g., SLC, +2, Bachelor">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">School/College</label>
                    <input type="text" name="qual_school[]" class="form-control" placeholder="Institution name">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Year</label>
                    <input type="text" name="qual_year[]" class="form-control" placeholder="Year">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Grade/Percentage</label>
                    <input type="text" name="qual_grade[]" class="form-control" placeholder="% or GPA">
                </div>
                <button type="button" class="btn bs" style="padding:8px 12px;" onclick="this.closest('.academic-qual-row').remove()">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>`;
        return;
    }
    
    container.innerHTML = quals.map((q, i) => `
        <div class="academic-qual-row" style="display:grid; grid-template-columns:2fr 2fr 1fr 1fr auto; gap:10px; align-items:end; margin-bottom:10px;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Level/Qualification</label>
                <input type="text" name="qual_level[]" class="form-control" value="${q.level || ''}" placeholder="e.g., SLC, +2, Bachelor">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">School/College</label>
                <input type="text" name="qual_school[]" class="form-control" value="${q.school || ''}" placeholder="Institution name">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Year</label>
                <input type="text" name="qual_year[]" class="form-control" value="${q.year || ''}" placeholder="Year">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Grade/Percentage</label>
                <input type="text" name="qual_grade[]" class="form-control" value="${q.percentage || q.grade || ''}" placeholder="% or GPA">
            </div>
            <button type="button" class="btn bs" style="padding:8px 12px;" onclick="this.closest('.academic-qual-row').remove()">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>`).join('');
};

// Add new academic qualification row
window._addAcademicQualification = () => {
    const container = document.getElementById('academic_qualifications_container');
    if (!container) return;
    
    const div = document.createElement('div');
    div.className = 'academic-qual-row';
    div.style = 'display:grid; grid-template-columns:2fr 2fr 1fr 1fr auto; gap:10px; align-items:end; margin-bottom:10px;';
    div.innerHTML = `
        <div class="form-group" style="margin:0;">
            <label class="form-label">Level/Qualification</label>
            <input type="text" name="qual_level[]" class="form-control" placeholder="e.g., SLC, +2, Bachelor">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">School/College</label>
            <input type="text" name="qual_school[]" class="form-control" placeholder="Institution name">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Year</label>
            <input type="text" name="qual_year[]" class="form-control" placeholder="Year">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Grade/Percentage</label>
            <input type="text" name="qual_grade[]" class="form-control" placeholder="% or GPA">
        </div>
        <button type="button" class="btn bs" style="padding:8px 12px;" onclick="this.closest('.academic-qual-row').remove()">
            <i class="fa-solid fa-trash"></i>
        </button>`;
    container.appendChild(div);
};

window._previewStdImage = (input) => {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.getElementById('stdImgPreview');
            const ico = document.getElementById('stdImgIcon') || document.querySelector('#imagePreviewContainer i');
            if (img) { img.src = e.target.result; img.style.display = 'block'; }
            if (ico) { ico.style.display = 'none'; }
        };
        reader.readAsDataURL(input.files[0]);
    }
};

async function _submitStudentForm(e, method) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

    const formData = new FormData(e.target);
    
    // Build JSON objects for address fields
    const permanentAddr = {
        province: formData.get('permanent_province') || '',
        district: formData.get('permanent_district') || '',
        municipality: formData.get('permanent_municipality') || '',
        ward: formData.get('permanent_ward') || ''
    };
    const temporaryAddr = {
        province: formData.get('temporary_province') || '',
        district: formData.get('temporary_district') || '',
        municipality: formData.get('temporary_municipality') || '',
        ward: formData.get('temporary_ward') || ''
    };
    
    // Only add address JSON if any field has value
    if (permanentAddr.province || permanentAddr.district || permanentAddr.municipality || permanentAddr.ward) {
        formData.set('permanent_address', JSON.stringify(permanentAddr));
    }
    if (temporaryAddr.province || temporaryAddr.district || temporaryAddr.municipality || temporaryAddr.ward) {
        formData.set('temporary_address', JSON.stringify(temporaryAddr));
    }
    
    // Build JSON array for academic qualifications
    const qualLevels = formData.getAll('qual_level[]');
    const qualSchools = formData.getAll('qual_school[]');
    const qualYears = formData.getAll('qual_year[]');
    const qualGrades = formData.getAll('qual_grade[]');
    
    const quals = [];
    for (let i = 0; i < qualLevels.length; i++) {
        if (qualLevels[i] || qualSchools[i]) {
            quals.push({
                level: qualLevels[i] || '',
                school: qualSchools[i] || '',
                year: qualYears[i] || '',
                percentage: qualGrades[i] || ''
            });
        }
    }
    if (quals.length > 0) {
        formData.set('academic_qualifications', JSON.stringify(quals));
    }
    
    // For PHP to handle PUT/PATCH with files easily, we use POST and tell the backend the method
    // Or just always use POST for add/update if backend supports it.
    if (method === 'PUT' || method === 'PATCH') {
        formData.append('_method', method);
    }

    try {
        // We always use POST if files might be involved, or ensure headers are correct.
        // PHP only parses multipart/form-data for POST requests natively.
        const res = await fetch(`${window.APP_URL}/api/frontdesk/students`, {
            method: 'POST', 
            body: formData
        });
        const result = await res.json();
        if (result.success) {
            Swal.fire('Success', result.message, 'success').then(() => goNav('students'));
        } else {
            throw new Error(result.message);
        }
    } catch (err) {
        Swal.fire('Error', err.message, 'error');
    } finally {
        btn.disabled = false; btn.innerHTML = orig;
    }
}

/* ── RECORD PAYMENT MODAL ────────────────────────────────────────── */
/* ── RECORD PAYMENT REDIRECTION ────────────────────────────────────────── */
window.openRecordPaymentModal = (studentId, studentName) => {
    // Redirect to the new premium Quick Payment page
    goNav('fee', 'quick', { id: studentId });
};

/* ── LOAD STUDENTS (with pagination) ───────────────────────────── */
window.loadStudents = async (page) => {
    if (page !== undefined) _StudentState.currentPage = page;

    const tbody = document.getElementById('studentsBody');
    if (!tbody) return;

    const emptyState = document.getElementById('emptyState');
    const paginationBar = document.getElementById('paginationBar');
    if (emptyState) emptyState.style.display = 'none';
    if (paginationBar) paginationBar.style.display = 'none';
    tbody.innerHTML = `<tr><td colspan="6" class="empty-state"><i class="fa-solid fa-circle-notch fa-spin"></i> Fetching students...</td></tr>`;

    try {
        const params = new URLSearchParams();
        params.append('page', _StudentState.currentPage);
        params.append('per_page', _StudentState.perPage);
        if (_StudentState.searchQuery) params.append('search', _StudentState.searchQuery);
        if (_StudentState.statusFilter) params.append('status', _StudentState.statusFilter);

        const res = await fetch(window.APP_URL + '/api/frontdesk/students?' + params.toString());
        const result = await res.json();

        if (!result.success) throw new Error(result.message);

        const students = result.data;
        const meta = result.meta || {};
        _StudentState.students = students;
        _StudentState.total = meta.total || 0;
        _StudentState.totalPages = meta.total_pages || 1;
        _StudentState.currentPage = meta.page || 1;

        const start = ((_StudentState.currentPage - 1) * _StudentState.perPage);
        const end = Math.min(_StudentState.currentPage * _StudentState.perPage, _StudentState.total);
        if (document.getElementById('rowCount')) {
            document.getElementById('rowCount').textContent = `Showing ${_StudentState.total ? start+1 : 0} of ${_StudentState.total}`;
        }

        if (students.length === 0) {
            tbody.innerHTML = '';
            if (emptyState) emptyState.style.display = 'block';
            return;
        }

        const getAvatarColor = (id) => {
            const AV_COLORS = ['av-teal','av-blue','av-purple','av-amber','av-red','av-navy'];
            return AV_COLORS[(id || 0) % AV_COLORS.length];
        };
        const initials = (name) => {
            if (!name) return 'S';
            const parts = name.split(' ').slice(0,2).map(w => w && w[0] ? w[0] : '').join('');
            return parts ? parts.toUpperCase() : 'S';
        };

        tbody.innerHTML = students.map(s => {
            const isSelected = _StudentState.selectedIds.has(s.id);
            const safeName = (s.full_name || s.name || '').replace(/'/g, "\\'");
            
            // Premium Fee Pill
            const feeStatus = s.fee_status || 'no_fees';
            let feePill = '';
            const pendingAmount = parseFloat(s.due_amount || 0);
            const formattedPending = pendingAmount > 0 ? ` Rs ${pendingAmount.toLocaleString()}` : '';

            if (feeStatus === 'paid') {
                feePill = `<span class="fee-pill paid"><i class="fa-solid fa-circle-check"></i> Paid</span>`;
            } else if (feeStatus === 'overdue') {
                feePill = `<span class="fee-pill overdue"><i class="fa-solid fa-circle-exclamation"></i> Overdue - ${formattedPending}</span>`;
            } else if (feeStatus === 'partial') {
                feePill = `<span class="fee-pill partial"><i class="fa-solid fa-circle-dot"></i> Partial - ${formattedPending}</span>`;
            } else if (feeStatus === 'unpaid') {
                feePill = `<span class="fee-pill overdue"><i class="fa-solid fa-circle-xmark"></i> Unpaid - ${formattedPending}</span>`;
            } else {
                feePill = `<span class="fee-pill no_fees"><i class="fa-solid fa-info-circle"></i> No Fees</span>`;
            }
            
            const gender = (s.gender === 'male' || s.gender === 'M') ? 'Male' : ((s.gender === 'female' || s.gender === 'F') ? 'Female' : 'Other');

            return `
            <tr id="row-${s.id}" class="${isSelected ? 'row-sel' : ''}">
              <td style="text-align:center; vertical-align: middle;">
                <div class="cb-wrap">
                  <input type="checkbox" class="student-checkbox" value="${s.id}" ${isSelected ? 'checked' : ''} onchange="window.toggleRow(${s.id}, this.checked)"/>
                </div>
              </td>
              <td>
                <div class="premium-s-info">
                  <div class="s-av ${getAvatarColor(s.id)}">${initials(s.full_name)}</div>
                  <div class="s-details">
                    <div class="s-name">${s.full_name || 'N/A'}</div>
                    <div class="s-meta">${s.roll_no || 'No roll'} &bull; ${gender}</div>
                  </div>
                </div>
              </td>
              <td style="vertical-align: middle;">
                <div class="premium-batch-box">
                  <div class="batch-name">${s.batch_name || 'No Batch'}</div>
                  <div class="course-tag">${s.course_name || 'No Course'}</div>
                </div>
              </td>
              <td style="vertical-align: middle;">
                ${s.phone ? `<a href="tel:${s.phone}" class="phone-link" style="color:var(--blue); font-weight:600; text-decoration:none;"><i class="fa-solid fa-phone" style="font-size:0.8rem; margin-right:4px;"></i> ${s.phone}</a>` : '<span style="color:#cbd5e1">No phone</span>'}
              </td>
              <td style="vertical-align: middle;">${feePill}</td>
              <td style="text-align:right; vertical-align: middle;">
                <div class="premium-row-acts d-flex gap-2 justify-content-end">
                  <button class="act-btn act-view btn btn-sm btn-primary" title="View Profile" onclick="goNav('students','view',{id:${s.id}})">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                  <button class="act-btn act-edit btn btn-sm btn-success" title="Edit Student" onclick="goNav('students','edit',{id:${s.id}})">
                    <i class="fa-solid fa-pen"></i>
                  </button>
                  <button class="act-btn act-pay btn btn-sm btn-warning" title="Collect Fee" onclick="goNav('fee', 'quick', {id:${s.id}})">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                  </button>
                  <button class="act-btn btn btn-sm btn-info" title="Send Email" onclick="sendEmailToStudent(${s.id}, '${safeName}', '${s.email || ''}')">
                    <i class="fa-solid fa-envelope"></i>
                  </button>
                  <button class="act-btn act-delete btn btn-sm btn-danger" title="Delete Student" onclick="deleteStudent(${s.id}, '${safeName}')">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>`;
        }).join('');

        const paginationBar = document.getElementById('paginationBar');
        if (paginationBar) paginationBar.style.display = 'flex';
        renderPaginationUI(start + 1, end);
        updateBulkBar();
        updateMasterCheck();

    } catch (error) {
        console.error('loadStudents error:', error);
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--red);padding:40px;"><i class="fa-solid fa-exclamation-triangle"></i> Failed to load students.</td></tr>`;
    }
};

function renderPaginationUI(start, end) {
    const info = document.getElementById('pagInfo');
    const btns = document.getElementById('pagBtns');
    if(!info || !btns) return;
    
    info.textContent = `${_StudentState.total ? start : 0} – ${end} of ${_StudentState.total} students`;
    
    const page = _StudentState.currentPage;
    const total = _StudentState.totalPages;
    
    let html = `<button class="pag-btn" onclick="loadStudents(${page-1})" ${page<=1?'disabled':''}>
                  <i class="fa-solid fa-chevron-left"></i>
                </button>`;
    
    let startBtn = Math.max(1, page - 2);
    let endBtn = Math.min(total, startBtn + 4);
    if (endBtn - startBtn < 4) startBtn = Math.max(1, endBtn - 4);
    
    for(let i=startBtn; i<=endBtn; i++){
        const activeClass = (i===page) ? 'active' : '';
        html += `<button class="pag-btn ${activeClass}" onclick="loadStudents(${i})">${i}</button>`;
    }
    html += `<button class="pag-btn" onclick="loadStudents(${page+1})" ${page>=total?'disabled':''}>
               <i class="fa-solid fa-chevron-right"></i>
             </button>`;
    btns.innerHTML = html;
}

window.toggleRow = (id, checked) => {
    if(checked) _StudentState.selectedIds.add(id);
    else _StudentState.selectedIds.delete(id);
    
    const row = document.getElementById('row-' + id);
    if(row) {
        if(checked) {
            row.classList.add('row-sel');
        } else {
            row.classList.remove('row-sel');
        }
    }
    updateBulkBar();
    updateMasterCheck();
};

window.toggleMasterCheck = (checked) => {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => {
        const id = parseInt(cb.value);
        if (checked) _StudentState.selectedIds.add(id);
        else _StudentState.selectedIds.delete(id);
        cb.checked = checked;
        
        const row = document.getElementById('row-' + id);
        if(row) {
            if(checked) {
                row.classList.add('row-sel');
            } else {
                row.classList.remove('row-sel');
            }
        }
    });
    updateBulkBar();
};

window.updateMasterCheck = () => {
    const master = document.getElementById('masterCheck');
    if(!master) return;
    const checkboxes = document.querySelectorAll('.student-checkbox');
    if(checkboxes.length === 0) { master.checked = false; return; }
    
    let allChecked = true;
    checkboxes.forEach(cb => { if(!cb.checked) allChecked = false; });
    master.checked = allChecked;
};

// Implement missing bulk functions
window.bulkExport = () => {
    const selected = Array.from(_StudentState.selectedIds);
    if(selected.length === 0) return;
    const params = new URLSearchParams();
    params.append('export', 'csv');
    params.append('ids', selected.join(','));
    window.location.href = window.APP_URL + '/api/frontdesk/students?' + params.toString();
};

window.bulkDrop = () => {
    const selected = Array.from(_StudentState.selectedIds);
    if(selected.length === 0) return;
    if(confirm(`Are you sure you want to mark ${selected.length} students as dropped?`)) {
        fetch(window.APP_URL + '/api/frontdesk/students', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action: 'bulk_drop', student_ids: selected})
        }).then(r=>r.json()).then(res=>{
             if(res.success) { 
                 showToast(res.message, 'success'); 
                 clearSelection(); 
                 loadStudents(); 
             } else {
                 showToast(res.message, 'error');
             }
        }).catch(err => {
            showToast('Failed to bulk drop', 'error');
        });
    }
};

window.deleteStudent = (id, name) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `You want to delete student "${name}"? This will soft-delete their account and records.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#E11D48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(window.APP_URL + '/api/frontdesk/students', {
                method: 'DELETE',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({id: id})
            }).then(r=>r.json()).then(res=>{
                 if(res.success) { 
                     Swal.fire('Deleted!', res.message, 'success').then(() => {
                         window.location.reload();
                     });
                 } else {
                     Swal.fire('Error!', res.message, 'error');
                 }
            }).catch(err => {
                Swal.fire('Error!', 'Failed to delete student', 'error');
            });
        }
    });
};

window.bulkDelete = () => {
    const selected = Array.from(_StudentState.selectedIds);
    if(selected.length === 0) return;
    
    Swal.fire({
        title: 'Bulk Delete',
        text: `Are you sure you want to PERMANENTLY delete ${selected.length} students? This will soft-delete their accounts and records.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#E11D48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete selected!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(window.APP_URL + '/api/frontdesk/students', {
                method: 'DELETE',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({id: selected})
            }).then(r=>r.json()).then(res=>{
                 if(res.success) { 
                     Swal.fire('Deleted!', res.message, 'success').then(() => {
                         window.location.reload();
                     });
                 } else {
                     Swal.fire('Error!', res.message, 'error');
                 }
            }).catch(err => {
                Swal.fire('Error!', 'Failed to bulk delete', 'error');
            });
        }
    });
};

/* ── SEND EMAIL TO SINGLE STUDENT ─────────────────────────────── */
window.sendEmailToStudent = (id, name, email) => {
    if (!email) {
        showToast('Student does not have an email address', 'warn');
        return;
    }
    
    const modalHtml = `
        <div class="modal-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;" onclick="if(event.target === this) this.remove()">
            <div class="modal-content" style="background:#fff;border-radius:12px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="padding:20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
                    <h3 style="margin:0;font-size:18px;font-weight:700;color:#1e293b;">
                        <i class="fa-solid fa-envelope" style="margin-right:8px;color:#009E7E;"></i>
                        Send Email
                    </h3>
                    <button onclick="this.closest('.modal-overlay').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div style="padding:20px;border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                    <div style="font-size:13px;color:#64748b;">To:</div>
                    <div style="font-size:14px;font-weight:600;color:#1e293b;">${name} &lt;${email}&gt;</div>
                </div>
                <form id="singleEmailForm" style="padding:20px;">
                    <input type="hidden" id="studentId" value="${id}">
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px;">Subject *</label>
                        <input type="text" id="emailSubject" required
                               style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;"
                               placeholder="Enter email subject...">
                    </div>
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px;">Message *</label>
                        <textarea id="emailMessage" required rows="8"
                                  style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;"
                                  placeholder="Enter your message..."></textarea>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" onclick="this.closest('.modal-overlay').remove()" 
                                class="btn bs">Cancel</button>
                        <button type="submit" class="btn bt">
                            <i class="fa-solid fa-paper-plane"></i> Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    const modal = document.createElement('div');
    modal.innerHTML = modalHtml;
    document.body.appendChild(modal);

    document.getElementById('singleEmailForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const studentId = document.getElementById('studentId').value;
        const subject = document.getElementById('emailSubject').value;
        const message = document.getElementById('emailMessage').value;

        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Sending...';
        submitBtn.disabled = true;

        try {
            const res = await fetch(window.APP_URL + '/api/frontdesk/students', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'send_email',
                    student_id: studentId,
                    subject: subject,
                    message: message
                })
            });
            const result = await res.json();

            if (result.success) {
                showToast('Email sent successfully', 'success');
                modal.querySelector('.modal-overlay').remove();
            } else {
                showToast(result.message || 'Failed to send email', 'error');
            }
        } catch (error) {
            console.error('email error:', error);
            showToast('Failed to send email', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
};

/* ── FILTER / SEARCH ───────────────────────────────────────────── */
window.filterStudents = () => {
    _StudentState.searchQuery = (document.getElementById('searchInput')?.value || '').trim();
    _StudentState.statusFilter = document.getElementById('statusFilter')?.value || '';
    _StudentState.currentPage = 1; // reset to page 1 on new filter
    loadStudents();
};

/* ── DELETE STUDENT ────────────────────────────────────────────── */
window.deleteStudent = (id, name) => {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Student?',
            html: `Are you sure you want to delete <strong>${name}</strong>? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(window.APP_URL + '/api/frontdesk/students', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        Swal.fire('Deleted', data.message || 'Student deleted.', 'success');
                        loadStudents();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Server error.', 'error');
                }
            }
        });
    } else if (confirm(`Delete student "${name}"?`)) {
        fetch(window.APP_URL + '/api/frontdesk/students', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        }).then(r => r.json()).then(d => { alert(d.message); loadStudents(); });
    }
};

/* ── STUDENT PROFILE ───────────────────────────────────────────── */
window.renderStudentProfile = async (id, activeTab = 'personal') => {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading student profile...</span></div></div>`;

    try {
        const res = await fetch(`${window.APP_URL}/api/frontdesk/students?id=${id}&include=details`);
        const result = await res.json();
        if (!result.success) throw new Error(result.message);

        const s = result.data;
        const photoSrc = s.photo_url ? (s.photo_url.startsWith('http') ? s.photo_url : window.APP_URL + s.photo_url) : null;
        const initials = s.name ? s.name.split(' ').filter(n => n).map(n => n[0] || '').join('').toUpperCase().substring(0, 2) : 'ST';

        const statusCls = s.status === 'active' ? 'sp-status-active' : 'sp-status-inactive';
        const statusIcon = s.status === 'active' ? 'fa-circle' : 'fa-circle-xmark';

        const tabs = [
            { id: 'personal',   icon: 'fa-user',            label: 'Personal' },
            { id: 'course',     icon: 'fa-book',             label: 'Course Enrolled' },
            { id: 'payment',    icon: 'fa-credit-card',      label: 'Payment History' },
            { id: 'exam',       icon: 'fa-graduation-cap',   label: 'Exam Performance' },
            { id: 'attendance', icon: 'fa-calendar-check',   label: 'Attendance' },
        ];

        let html = `
            <div class="pg fu">
                <!-- Breadcrumb -->
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <a href="#" onclick="goNav('students')">Students</a> <span class="bc-sep">›</span>
                    <span class="bc-cur">Profile</span>
                </div>

                <!-- Page Header -->
                <div class="sp-pg-hdr">
                    <div class="sp-pg-hdr-left">
                        <button class="sp-back-btn" onclick="goNav('students')"><i class="fas fa-arrow-left"></i></button>
                        <h1>Student Profile</h1>
                    </div>
                    <div class="sp-pg-hdr-right">
                        <button class="btn btn-success" style="background:#0F172A; color:#fff;" onclick="goNav('admissions', 'enroll-existing', { student_id: ${s.id} })">
                            <i class="fas fa-user-graduate"></i> Enroll in New Course
                        </button>
                        <button class="btn bs" onclick="window.print()"><i class="fas fa-print"></i> Print Profile</button>
                        <button class="btn bt" onclick="goNav('students','edit',{id:${s.id}})"><i class="fas fa-edit"></i> Edit Profile</button>
                        ${s.status !== 'alumni' ? `
                        <button class="btn btn-dark" onclick="markAlumniDialog(${s.id}, '${(s.name || '').replace(/'/g, "\\'")}')">
                            <i class="fa-solid fa-graduation-cap"></i> Mark as Alumni
                        </button>` : `
                        <button class="btn btn-secondary" onclick="restoreStudent(${s.id}, '${(s.name || '').replace(/'/g, "\\'")}')">
                            <i class="fa-solid fa-rotate-left"></i> Restore to Active
                        </button>`}
                    </div>
                </div>

                <!-- Profile Header Card -->
                <div class="sp-profile-header">
                    <div class="sp-profile-header-content">
                        <!-- Photo -->
                        <div class="sp-photo-box">
                            <div class="sp-photo">
                                ${photoSrc ? `<img src="${photoSrc}" alt="${s.name}">` : `<span>${initials}</span>`}
                            </div>
                            <span class="sp-status-badge ${statusCls}">
                                <i class="fas ${statusIcon}"></i>
                                ${(s.status || 'Active').toUpperCase()}
                            </span>
                            <span class="sp-status-badge ${s.registration_status === 'fully_registered' ? 'sp-status-active' : 'sp-status-warning'}" style="margin-top:5px; opacity:0.9; font-size:9px;">
                                <i class="fas ${s.registration_status === 'fully_registered' ? 'fa-check-circle' : 'fa-clock'}"></i>
                                ${s.registration_status === 'fully_registered' ? 'FULLY REGISTERED' : 'QUICK REGISTERED'}
                            </span>
                        </div>

                        <!-- Info -->
                        <div class="sp-info">
                            <div class="sp-name-row">
                                <h2 class="sp-name">${s.name}</h2>
                                <div class="sp-roll"><i class="fas fa-id-card"></i> ${s.roll_no || 'N/A'}</div>
                            </div>
                            <div class="sp-meta-grid">
                                <div class="sp-meta-item">
                                    <div class="sp-meta-icon teal"><i class="fas fa-envelope"></i></div>
                                    <div class="sp-meta-content">
                                        <span class="sp-meta-label">Email</span>
                                        <span class="sp-meta-value">${s.email || 'N/A'}</span>
                                    </div>
                                </div>
                                <div class="sp-meta-item">
                                    <div class="sp-meta-icon blue"><i class="fas fa-phone"></i></div>
                                    <div class="sp-meta-content">
                                        <span class="sp-meta-label">Contact</span>
                                        <span class="sp-meta-value">${s.phone || 'N/A'}</span>
                                    </div>
                                </div>
                                <div class="sp-meta-item">
                                    <div class="sp-meta-icon purple"><i class="fas fa-calendar"></i></div>
                                    <div class="sp-meta-content">
                                        <span class="sp-meta-label">DOB (AD)</span>
                                        <span class="sp-meta-value">${s.dob_ad || 'N/A'}</span>
                                    </div>
                                </div>
                                <div class="sp-meta-item">
                                    <div class="sp-meta-icon purple"><i class="fas fa-calendar-alt"></i></div>
                                    <div class="sp-meta-content">
                                        <span class="sp-meta-label">DOB (BS)</span>
                                        <span class="sp-meta-value">${s.dob_bs || 'N/A'}</span>
                                    </div>
                                </div>
                                <div class="sp-meta-item">
                                    <div class="sp-meta-icon amber"><i class="fas fa-tint"></i></div>
                                    <div class="sp-meta-content">
                                        <span class="sp-meta-label">Blood Group</span>
                                        <span class="sp-meta-value">${s.blood_group || 'N/A'}</span>
                                    </div>
                                </div>
                                <div class="sp-meta-item">
                                    <div class="sp-meta-icon blue"><i class="fas fa-venus-mars"></i></div>
                                    <div class="sp-meta-content">
                                        <span class="sp-meta-label">Gender</span>
                                        <span class="sp-meta-value">${s.gender ? (s.gender.charAt(0).toUpperCase() + s.gender.slice(1)) : 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="sp-actions">
                            <button class="btn btn-success" style="background:#009e7e; color:#fff;" onclick="window.renderQuickPayment(${s.id})">
                                <i class="fas fa-hand-holding-dollar"></i> Take Payment
                            </button>
                            <button class="btn bt" onclick="window.sendStudentEmail(${s.id})"><i class="fas fa-comment"></i> Send Message</button>
                            <button class="btn bs" onclick="window.downloadStudentID(${s.id})"><i class="fas fa-file-download"></i> Download ID Card</button>
                        </div>
                    </div>
                </div>

                <!-- Tabs Container -->
                <div class="sp-tabs-container">
                    <div class="sp-tabs-nav">
                        ${tabs.map(t => `
                            <button class="sp-tab-btn ${activeTab === t.id ? 'active' : ''}" data-tab="${t.id}" onclick="window._switchStudentTab(this, '${t.id}')">
                                <i class="fas ${t.icon}"></i> ${t.label}
                            </button>
                        `).join('')}
                    </div>

                    ${tabs.map(t => `
                        <div class="sp-tab-content ${activeTab === t.id ? 'active' : ''}" id="sp-tab-${t.id}">
                            ${window._renderSpTab(s, t.id)}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        mc.innerHTML = html;
    } catch (error) {
        mc.innerHTML = `<div class="card" style="padding:60px; text-align:center; color:var(--red);">
            <i class="fa-solid fa-circle-exclamation" style="font-size:3rem; margin-bottom:10px;"></i>
            <p>${error.message}</p>
            <button class="btn bt" onclick="goNav('students')">Back to Directory</button>
        </div>`;
    }
};

window._switchStudentTab = (el, tabId) => {
    document.querySelectorAll('.sp-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.sp-tab-content').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    const tc = document.getElementById(`sp-tab-${tabId}`);
    if (tc) tc.classList.add('active');
};

window._renderSpTab = (s, tab) => {
    switch (tab) {
        case 'personal':   return _spPersonalTab(s);
        case 'course':     return _spCourseTab(s);
        case 'payment':    return _spPaymentTab(s);
        case 'exam':       return _spExamTab(s);
        case 'attendance': return _spAttendanceTab(s);
        default: return '';
    }
};

/* helpers */
const _spInfoCard = (icon, label, value) => `
    <div class="sp-info-card">
        <div class="sp-info-card-label"><i class="fas ${icon}"></i> ${label}</div>
        <div class="sp-info-card-value">${value || '—'}</div>
    </div>`;

const _spStatCard = (iconClass, icon, value, label) => `
    <div class="sp-stat-card">
        <div class="sp-stat-header"><div class="sp-stat-icon ${iconClass}"><i class="fas ${icon}"></i></div></div>
        <div class="sp-stat-value">${value}</div>
        <div class="sp-stat-label">${label}</div>
    </div>`;

const _spSectionTitle = (icon, text, marginTop = false) => `
    <h3 class="sp-section-title" ${marginTop ? 'style="margin-top:32px;"' : ''}>
        <i class="fas ${icon}"></i> ${text}
    </h3>`;

/* ── TAB: PERSONAL ── */
function _spPersonalTab(s) {
    const parse = (str) => { if (!str) return {}; try { return typeof str === 'string' ? JSON.parse(str) : str; } catch(e) { return {}; } };
    const pAddr = parse(s.permanent_address);
    const tAddr = parse(s.temporary_address);
    const quals = Array.isArray(parse(s.academic_qualifications)) ? parse(s.academic_qualifications) : [];
    const fmtAddr = (a) => {
        const parts = [a.ward ? `Ward-${a.ward}` : null, a.municipality, a.district, a.province].filter(Boolean);
        return parts.join(', ') || '—';
    };

    return `
        ${_spSectionTitle('fa-user-circle', 'Personal Information')}
        <div class="sp-info-grid">
            ${_spInfoCard('fa-user',        'Full Name',      s.name)}
            ${_spInfoCard('fa-male',        "Father's Name",  s.father_name)}
            ${_spInfoCard('fa-id-card',     'Citizenship No.',s.citizenship_no)}
            ${_spInfoCard('fa-fingerprint', 'National ID',    s.national_id)}
        </div>

        ${_spSectionTitle('fa-map-marker-alt', 'Address Information', true)}
        <div class="sp-info-grid">
            ${_spInfoCard('fa-home',           'Permanent Address', fmtAddr(pAddr))}
            ${_spInfoCard('fa-location-arrow', 'Temporary Address', fmtAddr(tAddr))}
        </div>

        ${_spSectionTitle('fa-graduation-cap', 'Academic Qualifications', true)}
        <div class="sp-qual-list">
            ${quals.length > 0 ? quals.map((q, i) => {
                const icons = ['fa-certificate', 'fa-school', 'fa-book'];
                return `
                    <div class="sp-qual-item">
                        <div class="sp-qual-icon"><i class="fas ${icons[i % icons.length]}"></i></div>
                        <div class="sp-qual-content">
                            <div class="sp-qual-degree">${q.level || 'Qualification'}</div>
                            <div class="sp-qual-details">${q.school || 'Institute'} • ${q.year || 'N/A'} • ${q.percentage || q.grade || 'N/A'}</div>
                        </div>
                    </div>`;
            }).join('') : `<div class="sp-empty-state"><i class="fas fa-graduation-cap"></i><p>No academic qualifications recorded.</p></div>`}
        </div>`;
}

/* ── TAB: COURSE ENROLLED ── */
function _spCourseTab(s) {
    const subs = s.batch_subjects || [];
    const enrollments = s.enrollments || [];
    
    return `
        <div style="display:flex; justify-content:space-between; align-items:center;">
            ${_spSectionTitle('fa-book-open', 'Enrollment History')}
            <button class="btn btn-sm bt" style="height:32px; font-size:12px; padding:0 12px; background:#0F172A; color:#fff;" onclick="goNav('admissions', 'enroll-existing', { student_id: ${s.id} })">
                <i class="fas fa-plus"></i> New Enrollment
            </button>
        </div>
        <div class="table-responsive">
            <table class="sp-data-table">
                <thead><tr>
                    <th>Course</th><th>Batch</th><th>Enrolled Date</th><th>Status</th>
                </tr></thead>
                <tbody>
                    ${enrollments.length > 0 ? enrollments.map(e => `
                        <tr>
                            <td><strong>${e.course_name || '—'}</strong> (${e.course_code || '—'})</td>
                            <td>${e.batch_name || '—'}</td>
                            <td>${e.enrollment_date ? new Date(e.enrollment_date).toLocaleDateString() : '—'}</td>
                            <td><span class="sp-ps-paid" style="font-size:10px; background:#f0fdf4; color:#16a34a; padding:2px 8px; border-radius:12px;">${(e.status || 'ACTIVE').toUpperCase()}</span></td>
                        </tr>`).join('') : `<tr><td colspan="4" class="sp-empty-td">No enrollment history found.</td></tr>`}
                </tbody>
            </table>
        </div>

        ${_spSectionTitle('fa-chalkboard-teacher', 'Assigned Teachers & Subjects', true)}
        <div class="table-responsive">
            <table class="sp-data-table">
                <thead><tr>
                    <th>Subject</th><th>Teacher</th><th>Contact</th><th>Schedule</th>
                </tr></thead>
                <tbody>
                    ${subs.length > 0 ? subs.map(sub => `
                        <tr>
                            <td><strong>${sub.subject || '—'}</strong></td>
                            <td>${sub.teacher_name || '—'}</td>
                            <td>${sub.teacher_contact || '—'}</td>
                            <td><span class="sp-ps-partial" style="font-size:10px;">Scheduled</span></td>
                        </tr>`).join('') : `<tr><td colspan="4" class="sp-empty-td">No subject allocations found for this batch.</td></tr>`}
                </tbody>
            </table>
        </div>`;
}

/* ── TAB: PAYMENT HISTORY ── */
function _spPaymentTab(s) {
    const feeSummaries = s.fee_summary || [];
    const payments = s.payments || [];
    
    // In Unified Flow, the total amounts are aggregated in fee summaries.
    const totalDue  = feeSummaries.reduce((sum, f) => sum + parseFloat(f.total_fee || 0), 0);
    const totalPaid = feeSummaries.reduce((sum, f) => sum + parseFloat(f.paid_amount || 0), 0);
    const pending   = feeSummaries.reduce((sum, f) => sum + parseFloat(f.due_amount || 0), 0);
    
    // Determine overdue based on fee_status returning 'overdue' or logic 
    const overdue = feeSummaries.filter(f => f.fee_status === 'overdue').reduce((sum, f) => sum + parseFloat(f.due_amount || 0), 0);

    return `
        <div class="sp-stat-cards">
            ${_spStatCard('success', 'fa-check-circle',        `₹ ${totalPaid.toLocaleString()}`, 'Total Paid')}
            ${_spStatCard('warning', 'fa-clock',               `₹ ${pending.toLocaleString()}`,   'Pending Amount')}
            ${_spStatCard('primary', 'fa-wallet',              `₹ ${totalDue.toLocaleString()}`,  'Total Fee')}
            ${_spStatCard('danger',  'fa-exclamation-triangle',`₹ ${overdue.toLocaleString()}`,   'Overdue Amount')}
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
            ${_spSectionTitle('fa-history', 'Payment Records')}
            <button class="btn bt" onclick="openRecordPaymentModal(${s.id}, '${s.name}')" style="margin-bottom: 20px;">
                <i class="fa-solid fa-money-bill-wave"></i> Record Payment
            </button>
        </div>

        <div class="table-responsive">
            <table class="sp-data-table">
                <thead><tr>
                    <th>Payment ID</th><th>Amount</th><th>Payment Date</th>
                    <th>Mode</th><th>Reference</th><th>Status</th>
                </tr></thead>
                <tbody>
                    ${payments.length > 0 ? payments.map(p => {
                        return `<tr>
                            <td><strong>PAY-${p.id}</strong></td>
                            <td>₹ ${parseFloat(p.amount).toLocaleString()}</td>
                            <td>${p.payment_date || '—'}</td>
                            <td>${p.payment_mode ? (p.payment_mode.replace('_','') || 'CASH').toUpperCase() : '—'}</td>
                            <td>${p.reference || '—'}</td>
                            <td><span class="sp-payment-status sp-ps-paid">Recorded</span></td>
                        </tr>`;
                    }).join('') : `<tr><td colspan="6" class="sp-empty-td">No payment records found.</td></tr>`}
                </tbody>
            </table>
        </div>`;
}

/* ── TAB: EXAM PERFORMANCE ── */
function _spExamTab(s) {
    const exams = s.exams || [];
    const avgScore = exams.length > 0 ? (exams.reduce((sum, e) => sum + parseFloat(e.percentage || 0), 0) / exams.length).toFixed(1) : 0;
    const bestRank = exams.length > 0 ? Math.min(...exams.filter(e => e.rank).map(e => parseInt(e.rank))) : '—';

    return `
        <div class="sp-stat-cards">
            ${_spStatCard('primary', 'fa-file-alt',   exams.length,          'Total Exams')}
            ${_spStatCard('success', 'fa-trophy',      `${avgScore}%`,        'Average Score')}
            ${_spStatCard('warning', 'fa-medal',       bestRank !== Infinity && bestRank !== '—' ? `${bestRank}${bestRank === 1 ? 'st' : bestRank === 2 ? 'nd' : bestRank === 3 ? 'rd' : 'th'}` : '—', 'Best Rank')}
            ${_spStatCard('primary', 'fa-chart-line',  exams.length > 1 ? ((parseFloat(exams[0]?.percentage || 0) - parseFloat(exams[exams.length - 1]?.percentage || 0)).toFixed(1) + '%') : 'N/A', 'Improvement')}
        </div>

        ${_spSectionTitle('fa-clipboard-list', 'Exam Results')}
        <div class="table-responsive">
            <table class="sp-data-table">
                <thead><tr>
                    <th>Exam Title</th><th>Date</th><th>Total Marks</th>
                    <th>Obtained</th><th>Percentage</th><th>Rank</th>
                </tr></thead>
                <tbody>
                    ${exams.length > 0 ? exams.map(e => `
                        <tr>
                            <td><strong>${e.exam_title || 'N/A'}</strong></td>
                            <td>${e.exam_date ? new Date(e.exam_date).toLocaleDateString() : '—'}</td>
                            <td>${e.max_marks || '—'}</td>
                            <td style="font-weight:700; color:#009E7E;">${e.score || '0'}</td>
                            <td><span style="font-weight:700;">${parseFloat(e.percentage || 0).toFixed(1)}%</span></td>
                            <td><span class="sp-rank-badge"># ${e.rank || '—'}</span></td>
                        </tr>`).join('') : `<tr><td colspan="6" class="sp-empty-td">No exam history found.</td></tr>`}
                </tbody>
            </table>
        </div>`;
}

/* ── TAB: ATTENDANCE ── */
function _spAttendanceTab(s) {
    const att = s.attendance_summary || [];
    const present = parseInt(att.find(a => a.status === 'present')?.count || 0);
    const absent  = parseInt(att.find(a => a.status === 'absent')?.count || 0);
    const late    = parseInt(att.find(a => a.status === 'late')?.count || 0);
    const excused = parseInt(att.find(a => a.status === 'excused')?.count || 0);
    const total   = present + absent + late + excused;
    const attPerc = total > 0 ? ((present + late) / total * 100).toFixed(1) : 0;

    return `
        <div class="sp-stat-cards">
            ${_spStatCard('success', 'fa-check-circle',  `${attPerc}%`,        'Overall Attendance')}
            ${_spStatCard('primary', 'fa-calendar-day',  present,              'Present Days')}
            ${_spStatCard('danger',  'fa-times-circle',  absent,               'Absent Days')}
            ${_spStatCard('warning', 'fa-umbrella',       late + excused,       'Leave / Late Days')}
        </div>

        ${_spSectionTitle('fa-chart-pie', 'Attendance Summary (Last 30 Days)')}
        <div class="sp-info-grid">
            <div class="sp-info-card">
                <div class="sp-info-card-label"><i class="fas fa-check"></i> Present</div>
                <div class="sp-info-card-value" style="color:#16a34a;">${present} days</div>
                <div class="sp-progress-bar"><div class="sp-progress-fill" style="width:${total > 0 ? (present/total*100) : 0}%;"></div></div>
            </div>
            <div class="sp-info-card">
                <div class="sp-info-card-label"><i class="fas fa-times"></i> Absent</div>
                <div class="sp-info-card-value" style="color:#dc2626;">${absent} days</div>
                <div class="sp-progress-bar"><div class="sp-progress-fill" style="width:${total > 0 ? (absent/total*100) : 0}%; background:#dc2626;"></div></div>
            </div>
            <div class="sp-info-card">
                <div class="sp-info-card-label"><i class="fas fa-clock"></i> Late</div>
                <div class="sp-info-card-value" style="color:#d97706;">${late} days</div>
                <div class="sp-progress-bar"><div class="sp-progress-fill" style="width:${total > 0 ? (late/total*100) : 0}%; background:#d97706;"></div></div>
            </div>
            <div class="sp-info-card">
                <div class="sp-info-card-label"><i class="fas fa-umbrella"></i> Excused</div>
                <div class="sp-info-card-value">${excused} days</div>
                <div class="sp-progress-bar"><div class="sp-progress-fill" style="width:${total > 0 ? (excused/total*100) : 0}%; background:#94a3b8;"></div></div>
            </div>
        </div>
        ${total === 0 ? `<div class="sp-empty-state"><i class="fas fa-calendar-xmark"></i><p>No attendance records in the last 30 days.</p></div>` : ''}`;
}

/* ── STUDENT ACTIONS ────────────────────────────────────────────── */
window.sendStudentEmail = (id) => {
    Swal.fire({
        title: 'Send Message to Student',
        html: `
            <div style="text-align:left;">
                <div class="form-group">
                    <label class="form-label" style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; display:block;">SUBJECT</label>
                    <input type="text" id="email_subject" class="form-control" value="Important Message from Academy">
                </div>
                <div class="form-group" style="margin-top:15px;">
                    <label class="form-label" style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; display:block;">MESSAGE BODY (HTML Supported)</label>
                    <textarea id="email_message" class="form-control" style="height:140px; font-size:13px;" placeholder="Hello, we wanted to inform you that..."></textarea>
                </div>
                <div style="margin-top:20px; background:#f0f9ff; padding:14px; border-radius:10px; border:1px solid #bae6fd;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; font-weight:700; color:#0369a1;">
                        <input type="checkbox" id="send_creds" style="width:16px; height:16px;" onchange="
                            document.getElementById('email_subject').disabled = this.checked; 
                            document.getElementById('email_message').disabled = this.checked;
                            document.getElementById('email_subject').style.opacity = this.checked ? 0.5 : 1;
                            document.getElementById('email_message').style.opacity = this.checked ? 0.5 : 1;
                        "> 
                        Send Login Credentials Instead?
                    </label>
                    <div style="font-size:11px; color:#0c4a6e; margin-top:5px; margin-left:26px; line-height:1.4;">
                        If checked, the system will send the official welcome email containing the student's email and password.
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Send Now',
        confirmButtonColor: '#2563eb',
        cancelButtonText: 'Cancel',
        width: '500px',
        preConfirm: () => {
            const subject = document.getElementById('email_subject').value;
            const message = document.getElementById('email_message').value;
            const send_creds = document.getElementById('send_creds').checked;
            
            if (!send_creds && (!subject || !message)) {
                Swal.showValidationMessage('Subject and Message are required');
                return false;
            }
            return { subject, message, send_credentials: send_creds };
        }
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Sending Email...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            fetch(window.APP_URL + '/api/frontdesk/students', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'send_email',
                    student_id: id,
                    ...result.value
                })
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.success) Swal.fire('Success!', 'The email has been dispatched successfully.', 'success');
                else Swal.fire('Failed', resp.message || 'Transmission error. Check SMTP logs.', 'error');
            })
            .catch(err => Swal.fire('Error', 'Connection failed: ' + err.message, 'error'));
        }
    });
};

window.downloadStudentID = async (id) => {
    try {
        if(typeof Swal !== 'undefined') Swal.fire({ title: 'Generating ID Card...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        if(typeof html2canvas === 'undefined') {
            await new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                s.onload = resolve;
                s.onerror = () => reject('Failed to load html2canvas');
                document.head.appendChild(s);
            });
        }

        const apiUrl = window.APP_URL + (window.location.pathname.includes('frontdesk') || window.location.pathname.includes('front-desk') ? '/api/frontdesk/students' : '/api/admin/students');
        
        const res = await fetch(`${apiUrl}?id=${id}&include=details`);
        const result = await res.json();
        if (!result.success || !result.data) {
            if(typeof Swal !== 'undefined') Swal.fire('Error', 'Failed to fetch student details for ID Card.', 'error');
            return;
        }

        const s = result.data;
        let photoSrc = window.APP_URL + '/assets/images/user-placeholder.png'; // default
        if (s.photo_url) {
            photoSrc = s.photo_url.startsWith('http') ? s.photo_url : window.APP_URL + (s.photo_url.startsWith('/') ? '' : '/') + s.photo_url;
        }

        let address = 'N/A';
        try {
            let aData = s.permanent_address || s.temporary_address || s.address;
            if (typeof aData === 'string' && aData.trim().startsWith('{')) aData = JSON.parse(aData);
            if (aData && typeof aData === 'object') {
                const muni = aData.municipality || '';
                const ward = aData.ward ? `-${aData.ward}` : '';
                const dist = aData.district ? `, ${aData.district}` : '';
                address = `${muni}${ward}${dist}`.replace(/^[-,\s]+/, '').trim() || 'N/A';
            } else if (aData) {
                address = aData;
            }
        } catch(e) { address = s.permanent_address || 'N/A'; }
        const contact = s.phone || 'N/A';
        const name = s.name || s.full_name || 'N/A';
        const roll = s.roll_no || s.student_id || 'N/A';
        const course = s.course_name || (s.enrollments && s.enrollments.length > 0 ? s.enrollments[0].course_name : 'N/A');
        const batch = s.batch_name || (s.enrollments && s.enrollments.length > 0 ? s.enrollments[0].batch_name : 'N/A');
        const instituteName = document.title.split('|')[0].trim() || 'Ginyard International Co.';
        
        const esc = (str) => {
            if (!str) return '';
            return str.toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
        };

        const ghost = document.createElement('div');
        ghost.style.position = 'fixed';
        ghost.style.top = '-9999px';
        ghost.style.left = '-9999px';

        const html = `
        <div id="id-card-capture" style="width: 600px; height: 380px; background: linear-gradient(135deg, #cbeeea 0%, #80b5e2 100%); position: relative; overflow: hidden; border-radius: 12px; font-family: 'Inter', sans-serif; color: #0b114d; box-sizing: border-box;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 80px; background-color: #8cc63f; z-index: 3;"></div>
            
            <svg width="288" height="12" viewBox="0 0 288 12" preserveAspectRatio="none" style="position: absolute; top: 80px; right: 0; z-index: 2; display: block;">
                <polygon points="20,0 288,0 288,12 0,12" fill="#020942" />
            </svg>
            
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 80px; z-index: 4; display: flex; align-items: center; justify-content: center; padding: 0 30px; gap: 15px; text-align: center; box-sizing: border-box;">
                <div style="display: flex; align-items: center; justify-content: center;"><i class="fas fa-graduation-cap" style="font-size: 38px; color: #fff;"></i></div>
                <div style="color: #fff; display: flex; flex-direction: column; align-items: center;">
                    <h2 style="margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0.5px;">${esc(instituteName)}</h2>
                </div>
            </div>

            <div style="position: absolute; top: 110px; left: 35px; z-index: 5;">
                <h1 style="font-size: 32px; font-weight: 800; color: #020942; margin: 0 0 15px 0; letter-spacing: 1px;">STUDENT CARD</h1>
                <table style="border-collapse: collapse;">
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173; width: 110px;">Name</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(name)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Roll No</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(roll)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Course</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(course)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Batch</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(batch)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Address</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(address)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Contact No</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(contact)}</td></tr>
                </table>
            </div>

            <div style="position: absolute; top: 100px; right: 40px; width: 160px; height: 180px; background-color: #020942; border-radius: 25px; padding: 5px; z-index: 6; box-sizing: border-box;">
                <div style="width: 100%; height: 100%; border-radius: 20px; border: 4px solid #3cb4cd; padding:0; margin:0; overflow: hidden; background: #fff;">
                    <img id="captureReadyImg" src="${photoSrc}" style="width: 100%; height: 100%; object-fit: cover; display:block;" crossorigin="anonymous">
                </div>
            </div>

            <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 45px; background-color: #9ECCE6; z-index: 1;"></div>
            
            <svg width="220" height="180" viewBox="0 0 220 180" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; z-index: 2; display: block;">
                <polygon points="0,54 220,180 0,180" fill="#020942" />
            </svg>
            <svg width="200" height="120" viewBox="0 0 200 120" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; z-index: 3; display: block;">
                <polygon points="0,24 160,120 0,120" fill="#8cc63f" />
            </svg>
            <svg width="120" height="60" viewBox="0 0 120 60" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; z-index: 4; display: block;">
                <polygon points="0,6 78,60 0,60" fill="#3cb4cd" />
            </svg>
            <svg width="20" height="45" viewBox="0 0 20 45" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 210px; z-index: 2; display: block;">
                <polygon points="6,0 20,45 0,45" fill="#fff" />
            </svg>
        </div>
        `;
        
        ghost.innerHTML = html;
        document.body.appendChild(ghost);

        const imgEl = ghost.querySelector('#captureReadyImg');
        const targetEl = ghost.querySelector('#id-card-capture');

        const beginCapture = () => {
            html2canvas(targetEl, {
                scale: 3,
                useCORS: true,
                allowTaint: true,
                backgroundColor: null,
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/jpeg', 0.95);
                const a = document.createElement('a');
                a.href = imgData;
                a.download = `ID_Card_${name.replace(/\s+/g, '_')}_${roll}.jpg`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                document.body.removeChild(ghost);
                if(typeof Swal !== 'undefined') Swal.close();
            }).catch(e => {
                console.error('html2canvas err:', e);
                document.body.removeChild(ghost);
                if(typeof Swal !== 'undefined') Swal.fire('Error', 'Failed to generate visual ID.', 'error');
            });
        };

        if(imgEl.complete) {
            beginCapture();
        } else {
            imgEl.onload = beginCapture;
            imgEl.onerror = () => {
                imgEl.src = window.APP_URL + '/assets/images/user-placeholder.png'; // Fallback
                setTimeout(beginCapture, 1000);
            };
        }

    } catch (err) {
        console.error(err);
        if(typeof Swal !== 'undefined') Swal.fire('Error', 'Failed to generate ID card', 'error');
    }
};



/* ── QUICK ADD STUDENT (Custom Modal - Matching quick-registration.html) ───────────────── */
window.openQuickAddStudent = async () => {
    // Remove existing modal if any
    const existingModal = document.getElementById('quickAddModal');
    if (existingModal) existingModal.remove();

    // Pre-fetch courses for the dropdown
    let coursesHtml = '<option value="">Select Course</option>';
    try {
        const res = await fetch(window.APP_URL + '/api/frontdesk/courses');
        const result = await res.json();
        if (result.success && result.data) {
            result.data.forEach(c => {
                coursesHtml += `<option value="${c.id}" data-code="${c.code || ''}">${c.name}</option>`;
            });
        }
    } catch (e) { console.error('Failed to load courses', e); }

    // Create modal HTML
    const modalHtml = `
    <div id="quickAddModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;padding:clamp(10px, 4vw, 20px);">
        <div style="max-width:600px;width:100%;max-height:90vh;overflow-y:auto;background:#fff;border-radius:20px;box-shadow:0 8px 32px rgba(0,0,0,0.15);">
            <div style="background:linear-gradient(135deg,#009E7E 0%,#00b894 100%);padding:clamp(20px, 5dvh, 32px) clamp(15px, 4vw, 28px);text-align:center;color:#fff;border-radius:20px 20px 0 0;">
                <div style="width:clamp(48px, 12vw, 64px);height:clamp(48px, 12vw, 64px);background:rgba(255,255,255,0.2);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:clamp(20px, 5vw, 28px);"><i class="fas fa-user-plus"></i></div>
                <h1 style="font-size:clamp(1.25rem, 5vw, 1.75rem);font-weight:800;margin-bottom:8px;">Quick Registration</h1>
                <p style="font-size:14px;opacity:0.9;">Get started in minutes, complete details later</p>
                <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.2);padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;margin-top:12px;"><i class="fas fa-bolt"></i>Fast Track Mode</span>
            </div>
           
            <div style="padding:clamp(15px, 4vw, 28px);">
                <form id="quickAddStudentForm" autocomplete="off">
                    <div style="margin-bottom:20px;"><label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1E293B;margin-bottom:8px;"><i class="fas fa-user"></i>Full Name<span style="color:#E11D48;font-size:14px;">*</span></label><div style="position:relative;"><i class="fas fa-user" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:14px;"></i><input type="text" id="qa_name" style="width:100%;padding:12px 16px 12px 44px;border:2px solid #E2E8F0;border-radius:10px;font-size:14px;font-family:'Plus Jakarta Sans',sans-serif;color:#1E293B;background:#fff;" placeholder="Enter student full name" required></div></div>
                    <div style="margin-bottom:20px;"><label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1E293B;margin-bottom:8px;"><i class="fas fa-phone"></i>Contact Number<span style="color:#E11D48;font-size:14px;">*</span></label><div style="position:relative;"><i class="fas fa-phone" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:14px;"></i><input type="tel" id="qa_phone" style="width:100%;padding:12px 16px 12px 44px;border:2px solid #E2E8F0;border-radius:10px;font-size:14px;font-family:'Plus Jakarta Sans',sans-serif;color:#1E293B;background:#fff;" placeholder="98XXXXXXXX" pattern="[0-9]{10}" required></div></div>
                    <div style="margin-bottom:20px;"><label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1E293B;margin-bottom:8px;"><i class="fas fa-envelope"></i>Email Address<span style="color:#E11D48;font-size:14px;">*</span></label><div style="position:relative;"><i class="fas fa-envelope" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:14px;"></i><input type="email" id="qa_email" style="width:100%;padding:12px 16px 12px 44px;border:2px solid #E2E8F0;border-radius:10px;font-size:14px;font-family:'Plus Jakarta Sans',sans-serif;color:#1E293B;background:#fff;" placeholder="your.email@example.com" required></div></div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(clamp(200px, 100%, 250px), 1fr));gap:clamp(12px, 2vw, 16px);margin-bottom:20px;">
                        <div><label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1E293B;margin-bottom:8px;"><i class="fas fa-book"></i>Course<span style="color:#E11D48;font-size:14px;">*</span></label><select id="qa_course" onchange="window._qaLoadBatches(this.value)" style="width:100%;padding:12px 40px 12px 16px;border:2px solid #E2E8F0;border-radius:10px;font-size:14px;color:#1E293B;background:#fff;cursor:pointer;appearance:none;background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 12 12%27%3E%3Cpath fill=%27%2394A3B8%27 d=%27M6 9L1 4h10z/%27%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 16px center;">${coursesHtml}</select></div>
                        <div><label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1E293B;margin-bottom:8px;"><i class="fas fa-users"></i>Batch<span style="color:#E11D48;font-size:14px;">*</span></label><select id="qa_batch" disabled style="width:100%;padding:12px 40px 12px 16px;border:2px solid #E2E8F0;border-radius:10px;font-size:14px;color:#94A3B8;background:#fff;cursor:pointer;appearance:none;background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 12 12%27%3E%3Cpath fill=%27%2394A3B8%27 d=%27M6 9L1 4h10z/%27%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 16px center;"><option value="">Select Course First</option></select></div>
                    </div>
                    <div style="margin-bottom:20px;"><label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1E293B;margin-bottom:8px;"><i class="fas fa-lock"></i>Password<span style="color:#E11D48;font-size:14px;">*</span></label><div style="position:relative;"><i class="fas fa-lock" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:14px;"></i><input type="password" id="qa_password" style="width:100%;padding:12px 48px 12px 44px;border:2px solid #E2E8F0;border-radius:10px;font-size:14px;color:#1E293B;background:#fff;" placeholder="Min 6 characters" minlength="6" value="Student@123" required><span onclick="const p=document.getElementById('qa_password');const icon=document.getElementById('qa_password_icon');if(p.type==='password'){p.type='text';icon.classList.remove('fa-eye');icon.classList.add('fa-eye-slash');}else{p.type='password';icon.classList.remove('fa-eye-slash');icon.classList.add('fa-eye');}" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);color:#94A3B8;cursor:pointer;font-size:14px;"><i class="fas fa-eye" id="qa_password_icon"></i></span></div></div>
                    <div style="margin-top:32px;display:flex;flex-direction:column;gap:12px;">
                        <button type="submit" id="qa_submit_btn" style="width:100%;padding:clamp(12px, 3vw, 14px);border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;border:none;display:flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#009E7E 0%,#00b894 100%);color:#fff;box-shadow:0 4px 16px rgba(0,158,126,0.3);"><i class="fas fa-check-circle"></i>Complete Quick Registration</button>
                        <button type="button" onclick="document.getElementById('quickAddModal').remove()" style="width:100%;padding:clamp(12px, 3vw, 14px);border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;border:2px solid #E2E8F0;display:flex;align-items:center;justify-content:center;gap:8px;background:#fff;color:#475569;"><i class="fas fa-times"></i>Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    document.getElementById('quickAddStudentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const name = document.getElementById('qa_name').value.trim();
        const phone = document.getElementById('qa_phone').value.trim();
        const course_id = document.getElementById('qa_course').value;
        const batch_id = document.getElementById('qa_batch').value;
        const email = document.getElementById('qa_email').value.trim();
        const password = document.getElementById('qa_password').value;
        if (!name) { alert('Full Name is required'); return; }
        if (!phone) { alert('Contact Number is required'); return; }
        if (!course_id) { alert('Please select a Course'); return; }
        if (!batch_id) { alert('Please select a Batch'); return; }
        if (!email) { alert('Email is required for login credentials'); return; }
        if (!password || password.length < 6) { alert('Password must be at least 6 characters'); return; }
        const submitBtn = document.getElementById('qa_submit_btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';
        submitBtn.style.cursor = 'not-allowed';
        try {
            const response = await fetch(window.APP_URL + '/api/frontdesk/students', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ full_name: name, phone: phone, contact_number: phone, email: email, password: password, batch_id: batch_id, registration_mode: 'quick', registration_status: 'quick_registered' }) });
            const resp = await response.json();
            if (resp.success) {
                document.getElementById('quickAddModal').remove();
                Swal.fire({ icon: 'success', title: 'Student Registered!', html: `<div style="text-align:left;background:#f8fafc;border-radius:10px;padding:16px;margin-top:10px;"><div style="font-weight:700;margin-bottom:10px;color:#1e293b;"><i class="fa-solid fa-circle-check" style="color:#10b981;margin-right:6px;"></i>Quick Registration Complete</div><div style="display:grid;grid-template-columns:auto 1fr;gap:6px 12px;font-size:13px;"><span style="color:#64748b;">Name:</span><span style="font-weight:600;">${name}</span><span style="color:#64748b;">Email:</span><span style="font-weight:600;">${email}</span><span style="color:#64748b;">Password:</span><span style="font-weight:600;font-family:monospace;">${password}</span></div><div style="margin-top:12px;padding:8px 12px;background:#ecfdf5;border-radius:8px;font-size:12px;color:#047857;"><i class="fa-solid fa-envelope"></i>Login details and course info have been emailed to the student.</div></div>`, confirmButtonColor: '#009E7E' });
                if (window.renderStudentList) window.renderStudentList();
            } else { Swal.fire('Error', resp.message || 'Failed to register student', 'error'); }
        } catch (err) { Swal.fire('Error', 'Server error: ' + err.message, 'error'); }
        finally { submitBtn.innerHTML = originalText; submitBtn.disabled = false; submitBtn.style.opacity = '1'; submitBtn.style.cursor = 'pointer'; }
    });



    document.getElementById('quickAddModal').addEventListener('click', function(e) { if (e.target === this) this.remove(); });
};

/* ── Helper: Load batches for a course (cascading dropdown) ──── */
window._qaLoadBatches = async (courseId) => {
    const batchSelect = document.getElementById('qa_batch');
    if (!batchSelect) return;

    if (!courseId) {
        batchSelect.innerHTML = '<option value="">-- Select Course First --</option>';
        batchSelect.disabled = true;
        return;
    }

    batchSelect.innerHTML = '<option value="">Loading batches...</option>';
    batchSelect.disabled = true;

    try {
        const res = await fetch(window.APP_URL + '/api/frontdesk/batches?course_id=' + courseId);
        const result = await res.json();

        if (result.success && result.data && result.data.length > 0) {
            let html = '<option value="">-- Select Batch --</option>';
            result.data.forEach(b => {
                html += `<option value="${b.id}">${b.name} (${b.shift || 'Regular'})</option>`;
            });
            batchSelect.innerHTML = html;
            batchSelect.disabled = false;
        } else {
            batchSelect.innerHTML = '<option value="">No batches for this course</option>';
            batchSelect.disabled = true;
        }
    } catch (e) {
        console.error('Failed to load batches', e);
        batchSelect.innerHTML = '<option value="">Error loading batches</option>';
    }
}

/* ── COMPLETE STUDENT REGISTRATION (Multi-Step Form) ─────────── */
window.renderCompleteProfileForm = async (id) => {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Initializing registration form...</span></div></div>`;

    try {
        const res = await fetch(`${window.APP_URL}/api/frontdesk/students?id=${id}`);
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        const s = result.data;

        mc.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('students')">Students</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('students','view',{id:${id}})">Profile</a> <span class="bc-sep">›</span>
                    <span class="bc-cur">Complete Registration</span>
                </div>

                <div class="cr-container">
                    <!-- Header -->
                    <div class="cr-header">
                        <div class="cr-header-content">
                            <div style="display:flex; align-items:center; gap:20px;">
                                <div class="cr-header-icon"><i class="fas fa-user-graduate"></i></div>
                                <div class="cr-header-text">
                                    <h1>Complete Profile</h1>
                                    <p>Finalize student records for ${s.name}</p>
                                </div>
                            </div>
                            <div style="background:rgba(255,255,255,0.2); padding:8px 16px; border-radius:10px; font-size:13px; font-weight:700;">
                                <i class="fas fa-id-card"></i> Roll: ${s.roll_no || 'TEMP-REG'}
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="cr-progress-container" style="height:6px; background:#e2e8f0; position:relative; overflow:hidden;">
                        <div id="cr-progress-bar" style="height:100%; background:linear-gradient(90deg, #009E7E, #10b981); width:20%; transition:width 0.4s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                    </div>

                    <!-- Navigation -->
                    <div class="cr-nav">
                        <button class="cr-nav-tab active" data-idx="0" onclick="window._crSwitchSection(0)">
                            <i class="fas fa-user-check"></i> Basic Info <span class="cr-tab-badge">1</span>
                        </button>
                        <button class="cr-nav-tab" data-idx="1" onclick="window._crSwitchSection(1)">
                            <i class="fas fa-map-marker-alt"></i> Address <span class="cr-tab-badge">2</span>
                        </button>
                        <button class="cr-nav-tab" data-idx="2" onclick="window._crSwitchSection(2)">
                            <i class="fas fa-graduation-cap"></i> Education <span class="cr-tab-badge">3</span>
                        </button>
                        <button class="cr-nav-tab" data-idx="3" onclick="window._crSwitchSection(3)">
                            <i class="fas fa-check-circle"></i> Review <span class="cr-tab-badge">4</span>
                        </button>
                    </div>

                    <div class="cr-body">
                        <form id="crForm">
                            <input type="hidden" name="id" value="${s.id}">
                            <input type="hidden" name="_method" value="PATCH">

                            <!-- Section 1: Basic -->
                            <div class="cr-section active" id="cr-sec-0">
                                <div class="cr-info-card">
                                    <i class="fas fa-info-circle cr-info-icon"></i>
                                    <div class="cr-info-content">
                                        <div class="cr-info-title">Personal Details Completion</div>
                                        <div class="cr-info-text">Basic info is locked. Please provide official dates and identifiers.</div>
                                    </div>
                                </div>

                                <div class="cr-form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Full Name <span class="required">*</span></label>
                                        <input type="text" name="full_name" class="form-control" value="${s.name}" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone <span class="required">*</span></label>
                                        <input type="text" name="phone" class="form-control" value="${u.phone || s.contact_number || ''}" required>
                                    </div>
                                </div>

                                <div class="cr-form-grid-3" style="margin-top:20px;">
                                    <div class="form-group">
                                        <label class="form-label">DOB (AD) <span class="required">*</span></label>
                                        <input type="date" name="dob_ad" class="form-control" required value="${s.dob_ad || ''}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">DOB (BS) <span class="required">*</span></label>
                                        <input type="text" name="dob_bs" class="form-control" placeholder="2055-01-01" required value="${s.dob_bs || ''}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Gender <span class="required">*</span></label>
                                        <select name="gender" class="form-control" required>
                                            <option value="male" ${s.gender==='male'?'selected':''}>Male</option>
                                            <option value="female" ${s.gender==='female'?'selected':''}>Female</option>
                                            <option value="other" ${s.gender==='other'?'selected':''}>Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="cr-form-grid" style="margin-top:20px;">
                                    <div class="form-group">
                                        <label class="form-label">Citizenship / ID No.</label>
                                        <input type="text" name="citizenship_no" class="form-control" placeholder="1234/5678" value="${s.citizenship_no || ''}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Father's Name</label>
                                        <input type="text" name="father_name" class="form-control" placeholder="Full name" value="${s.father_name || ''}">
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Address -->
                            <div class="cr-section" id="cr-sec-1">
                                <h3 class="cr-section-title"><i class="fas fa-home"></i> Permanent Address</h3>
                                <div class="cr-form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Province <span class="required">*</span></label>
                                        <select name="p_province" class="form-control" required>
                                            <option value="Bagmati">Bagmati Province</option>
                                            <option value="Koshi">Koshi Province</option>
                                            <option value="Madhesh">Madhesh Province</option>
                                            <option value="Gandaki">Gandaki Province</option>
                                            <option value="Lumbini">Lumbini Province</option>
                                            <option value="Karnali">Karnali Province</option>
                                            <option value="Sudurpashchim">Sudurpashchim Province</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">District <span class="required">*</span></label>
                                        <input type="text" name="p_district" class="form-control" placeholder="e.g. Kathmandu" required>
                                    </div>
                                </div>
                                <div class="cr-form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Municipality / Local Level <span class="required">*</span></label>
                                        <input type="text" name="p_local" class="form-control" placeholder="e.g. Nagarjun" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Ward <span class="required">*</span></label>
                                        <input type="number" name="p_ward" class="form-control" required min="1">
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Education -->
                            <div class="cr-section" id="cr-sec-2">
                                <h3 class="cr-section-title"><i class="fas fa-graduation-cap"></i> Academic Background</h3>
                                <div id="cr-qual-container">
                                    <div class="cr-qual-item">
                                        <div class="cr-form-grid">
                                            <div class="form-group"><label class="form-label">Level</label><input type="text" name="q_level[]" class="form-control" placeholder="SEE / +2 / Bachelor"></div>
                                            <div class="form-group"><label class="form-label">Institution</label><input type="text" name="q_school[]" class="form-control"></div>
                                        </div>
                                        <div class="cr-form-grid">
                                            <div class="form-group"><label class="form-label">Passed Year</label><input type="number" name="q_year[]" class="form-control"></div>
                                            <div class="form-group"><label class="form-label">Grade</label><input type="text" name="q_grade[]" class="form-control"></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="add-qualification" style="width:100%; border:2px dashed #009E7E; background:#e0f5f0; color:#009E7E; padding:10px; border-radius:10px; font-weight:700; cursor:pointer;" onclick="window._crAddQualification()"><i class="fas fa-plus"></i> Add Another Qualification</button>
                            </div>

                            <!-- Section 4: Review -->
                            <div class="cr-section" id="cr-sec-3">
                                <div class="cr-info-card" style="background:#f0fdf4; border-color:#bbf7d0;">
                                    <i class="fas fa-check-circle cr-info-icon" style="color:#16a34a;"></i>
                                    <div class="cr-info-content">
                                        <div class="cr-info-title" style="color:#15803d;">Ready to Submit</div>
                                        <div class="cr-info-text" style="color:#166534;">By clicking complete, the student profile will be marked as "Fully Registered". This unlocks all student dashboard features.</div>
                                    </div>
                                </div>
                                <div style="padding:20px; background:#fafafa; border-radius:12px; border:1px solid var(--card-border);">
                                    <h4 style="margin:0 0 10px 0; font-size:14px; font-weight:800;">Declaration</h4>
                                    <p style="font-size:12px; color:#64748b; line-height:1.6;">I hereby declare that the information provided above is true to the best of my knowledge.</p>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="cr-actions">
                                <button type="button" id="cr-prev" class="cr-btn cr-btn-prev" onclick="window._crSwitchSection(window._crActiveSec - 1)" style="visibility:hidden;"><i class="fas fa-arrow-left"></i> Back</button>
                                <button type="button" id="cr-next" class="cr-btn cr-btn-next" onclick="window._crSwitchSection(window._crActiveSec + 1)">Next <i class="fas fa-arrow-right"></i></button>
                                <button type="submit" id="cr-submit" class="cr-btn cr-btn-next" style="display:none; background:#2563eb; color:#fff;"><i class="fas fa-check-circle"></i> Complete Registration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        window._crActiveSec = 0;
        const form = document.getElementById('crForm');
        form.onsubmit = (e) => _crSubmitForm(e);

        // Auto Date Conversion Listeners
        const adInput = form.querySelector('input[name="dob_ad"]');
        const bsInput = form.querySelector('input[name="dob_bs"]');

        if (adInput && bsInput) {
            const handleConvert = async (e, type) => {
                const date = e.target.value;
                if (!date || date.length < 10) return;
                
                try {
                    const res = await fetch(`${window.APP_URL}/api/frontdesk/date-convert?date=${date}&type=${type}`);
                    const result = await res.json();
                    if (result.success) {
                        if (type === 'ad') bsInput.value = result.converted;
                        else adInput.value = result.converted;
                    }
                } catch (err) { console.error('Date conversion failed', err); }
            };

            adInput.addEventListener('change', (e) => handleConvert(e, 'ad'));
            bsInput.addEventListener('blur', (e) => handleConvert(e, 'bs'));
        }

    } catch (e) {
        mc.innerHTML = `<div class="pg fu"><div class="pg-error"><i class="fas fa-exclamation-triangle"></i><span>Error: ${e.message}</span></div></div>`;
    }
};

window._crSwitchSection = (idx) => {
    if (idx < 0 || idx > 3) return;
    
    // Validate current section before moving next
    if (idx > window._crActiveSec) {
        const activeSec = document.getElementById('cr-sec-' + window._crActiveSec);
        const requireds = activeSec.querySelectorAll('[required]');
        let valid = true;
        requireds.forEach(r => { if (!r.value) { r.style.borderColor = '#ef4444'; valid = false; } else { r.style.borderColor = ''; } });
        if (!valid) { Swal.fire('Required Fields', 'Please fill in all fields marked with *', 'warning'); return; }
    }

    window._crActiveSec = idx;

    // Update visibility
    document.querySelectorAll('.cr-section').forEach((s, i) => s.classList.toggle('active', i === idx));
    document.querySelectorAll('.cr-nav-tab').forEach((t, i) => t.classList.toggle('active', i === idx));

    // Update buttons
    document.getElementById('cr-prev').style.visibility = idx === 0 ? 'hidden' : 'visible';
    document.getElementById('cr-next').style.display = idx === 3 ? 'none' : 'flex';
    document.getElementById('cr-submit').style.display = idx === 3 ? 'flex' : 'none';

    // Update Progress Bar
    const pb = document.getElementById('cr-progress-bar');
    if (pb) pb.style.width = ((idx + 1) * 25) + '%';

    window.scrollTo({ top: 0, behavior: 'smooth' });
};

window._crAddQualification = () => {
    const container = document.getElementById('cr-qual-container');
    const div = document.createElement('div');
    div.className = 'cr-qual-item';
    div.style.marginTop = '15px';
    div.innerHTML = `
        <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
            <span style="font-size:12px; font-weight:800; color:#64748b;">Previous Qualification</span>
            <button type="button" class="remove-qualification" onclick="this.parentElement.parentElement.remove()" style="color:#ef4444; background:none; border:none; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="cr-form-grid">
            <div class="form-group"><label class="form-label">Level</label><input type="text" name="q_level[]" class="form-control" placeholder="SEE / +2 / Bachelor"></div>
            <div class="form-group"><label class="form-label">Institution</label><input type="text" name="q_school[]" class="form-control"></div>
        </div>
        <div class="cr-form-grid">
            <div class="form-group"><label class="form-label">Passed Year</label><input type="number" name="q_year[]" class="form-control"></div>
            <div class="form-group"><label class="form-label">Grade</label><input type="text" name="q_grade[]" class="form-control"></div>
        </div>
    `;
    container.appendChild(div);
};

window._crPreviewImage = (input) => {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('cr_profile_preview');
            const container = document.getElementById('cr_preview_container');
            preview.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
};

async function _crSubmitForm(e) {
    e.preventDefault();
    const btn = document.getElementById('cr-submit');
    const oldHtml = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    const fd = new FormData(e.target);
    
    // Ensure ID and Method are in FD (even if hidden inputs fail)
    fd.set('id', fd.get('id'));
    fd.set('_method', 'PATCH');
    
    // Structure Address
    const address = {
        province: fd.get('p_province'),
        district: fd.get('p_district'),
        local: fd.get('p_local'),
        ward: fd.get('p_ward')
    };
    fd.set('permanent_address', JSON.stringify(address));

    // Structure Qualifications
    const quals = [];
    const lvls = fd.getAll('q_level[]');
    const schs = fd.getAll('q_school[]');
    const yrs = fd.getAll('q_year[]');
    const grds = fd.getAll('q_grade[]');

    lvls.forEach((l, i) => {
        if (l) quals.push({ level: l, institution: schs[i], year: yrs[i], grade: grds[i] });
    });
    fd.set('academic_qualifications', JSON.stringify(quals));
    fd.set('registration_status', 'fully_registered');

    try {
        const res = await fetch(`${window.APP_URL}/api/frontdesk/students`, {
            method: 'POST',
            body: fd
        });
        const resp = await res.json();
        if (resp.success) {
            Swal.fire({
                icon: 'success',
                title: 'Registration Complete!',
                text: 'The student profile is now fully registered.',
                confirmButtonColor: '#009E7E'
            }).then(() => {
                goNav('students', 'view', { id: fd.get('id') });
            });
        } else {
            Swal.fire('Error', resp.message || 'Update failed', 'error');
            btn.innerHTML = oldHtml; btn.disabled = false;
        }
    } catch (err) {
        Swal.fire('Error', 'Connection failed: ' + err.message, 'error');
        btn.innerHTML = oldHtml; btn.disabled = false;
    }
}

window.renderDocumentVault = async (page = 1, search = '') => {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fas fa-spinner fa-spin"></i><span>Loading Document Vault...</span></div></div>`;

    try {
        const res = await fetch(`${window.APP_URL}/api/frontdesk/students?page=${page}&per_page=12&search=${encodeURIComponent(search)}`);
        const result = await res.json();
        if (!result.success) throw new Error(result.message);

        const students = result.data;
        const meta = result.meta;

        let html = `
            <div class="pg-header">
                <div>
                    <h1 class="pg-title"><i class="fas fa-vault" style="color:var(--primary);"></i> Document Vault</h1>
                    <p class="pg-subtitle">Secure access to student uploaded files and documents</p>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="vaultSearch" placeholder="Search by name or roll no..." value="${search}" onkeypress="if(event.key==='Enter') window.renderDocumentVault(1, this.value)">
                    </div>
                </div>
            </div>
            
            <div class="pg-content">
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
        `;

        if (students.length === 0) {
            html += `<div style="grid-column:1/-1; text-align:center; padding:50px; color:#64748b; background:#fff; border-radius:12px;"><i class="fas fa-folder-open" style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i><h3>No records found</h3></div>`;
        } else {
            students.forEach(s => {
                const photo = s.photo_url || `${window.APP_URL}/assets/images/default-avatar.png`;
                const idDoc = s.identity_doc_url ? `<a href="${s.identity_doc_url}" target="_blank" style="flex:1; background:#f0f9ff; color:#0369a1; border:1px solid #e0f2fe; padding:8px 10px; border-radius:8px; text-decoration:none; text-align:center; font-size:13px; font-weight:600;"><i class="fas fa-id-card"></i> Identity Doc</a>` : `<span style="flex:1; background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; padding:8px 10px; border-radius:8px; text-align:center; font-size:13px; font-weight:600;"><i class="fas fa-ban"></i> No ID</span>`;
                
                const certHtml = s.academic_qualifications ? `<button type="button" onclick="window._showCerts(${s.id})" style="flex:1; background:#f0fdf4; color:#15803d; border:1px solid #dcfce7; padding:8px 10px; border-radius:8px; cursor:pointer; font-size:13px; font-weight:600;"><i class="fas fa-graduation-cap"></i> Academics</button>` : '';

                let hasCerts = false;
                try {
                	let q = s.academic_qualifications;
                	if(typeof q === 'string') q = JSON.parse(q);
                	if(Array.isArray(q) && q.length > 0) hasCerts = true;
                } catch(e) {}
                
                window[`_vaultData_${s.id}`] = s.academic_qualifications;

                html += `
                    <div style="background:#fff; border-radius:12px; border:1px solid var(--card-border); padding:20px; transition:all 0.3s; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display:flex; align-items:center; gap:15px; margin-bottom:15px;">
                            <img src="${photo}" style="width:50px; height:50px; border-radius:10px; object-fit:cover; border:2px solid #e2e8f0;">
                            <div>
                                <h4 style="margin:0; font-size:15px; color:#1e293b; font-weight:700;">${s.name}</h4>
                                <div style="font-size:12px; color:#64748b; margin-top:3px;">
                                    <span><i class="fas fa-hashtag"></i> ${s.roll_no || '-'}</span>
                                    <span style="margin:0 5px;">|</span>
                                    <span>${s.course_name ? s.course_name : 'No Course'}</span>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; gap:10px;">
                            ${idDoc}
                            ${hasCerts ? certHtml : '<span style="flex:1; background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; padding:8px 10px; border-radius:8px; text-align:center; font-size:13px; font-weight:600;"><i class="fas fa-ban"></i> No Academics</span>'}
                        </div>
                    </div>
                `;
            });
        }

        html += `</div>`; // Close grid

        // Pagination
        if (meta && meta.total_pages > 1) {
            html += `<div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding:15px; background:#fff; border-radius:12px; border:1px solid var(--card-border);">
                <div style="font-size:13px; color:#64748b;">Showing page ${meta.page} of ${meta.total_pages} (${meta.total} total)</div>
                <div style="display:flex; gap:5px;">
                    <button ${meta.page === 1 ? 'disabled' : ''} onclick="window.renderDocumentVault(${meta.page - 1}, document.getElementById('vaultSearch')?.value || '')" style="padding:6px 12px; border:1px solid #e2e8f0; background:#fff; border-radius:6px; cursor:pointer;" class="btn-pg">Prev</button>
                    <button ${meta.page === meta.total_pages ? 'disabled' : ''} onclick="window.renderDocumentVault(${meta.page + 1}, document.getElementById('vaultSearch')?.value || '')" style="padding:6px 12px; border:1px solid #e2e8f0; background:#fff; border-radius:6px; cursor:pointer;" class="btn-pg">Next</button>
                </div>
            </div>`;
        }

        html += `</div>`; // Close pg-content

        mc.innerHTML = html;

    } catch (e) {
        mc.innerHTML = `<div class="pg fu"><div class="pg-error"><i class="fas fa-exclamation-triangle"></i><span>Error: ${e.message}</span></div></div>`;
    }
};

window._showCerts = (id) => {
    let qual = window[`_vaultData_${id}`];
    if (!qual) { Swal.fire('Info', 'No academic records found.', 'info'); return; }
    try {
        if (typeof qual === 'string') qual = JSON.parse(qual);
        if (!Array.isArray(qual) || qual.length === 0) { Swal.fire('Info', 'No academic records found.', 'info'); return; }
        
        let table = `<table style="width:100%; text-align:left; border-collapse:collapse;">
            <tr style="border-bottom:2px solid #e2e8f0;">
                <th style="padding:10px 5px; font-size:13px;">Level</th>
                <th style="padding:10px 5px; font-size:13px;">Institution</th>
                <th style="padding:10px 5px; font-size:13px;">Year</th>
                <th style="padding:10px 5px; font-size:13px;">Grade</th>
            </tr>
        `;
        qual.forEach(q => {
            table += `
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px 5px; font-size:13px; font-weight:600;">${q.level || '-'}</td>
                <td style="padding:10px 5px; font-size:13px; color:#64748b;">${q.institution || '-'}</td>
                <td style="padding:10px 5px; font-size:13px;">${q.year || '-'}</td>
                <td style="padding:10px 5px; font-size:13px;"><span style="background:#f0fdf4; color:#15803d; padding:2px 6px; border-radius:6px; font-size:12px;">${q.grade || '-'}</span></td>
            </tr>`;
        });
        table += `</table>`;
        Swal.fire({
            title: 'Academic Details',
            html: table,
            width: '600px',
            confirmButtonColor: '#009E7E',
            confirmButtonText: 'Close'
        });
    } catch (e) {
        Swal.fire('Error', 'Could not parse records.', 'error');
    }
};

/* ── ALUMNI LIFECYCLE ────────────────────────────────────────── */
window.markAlumniDialog = (id, name) => {
    const modalHtml = `
        <div class="modal-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;" onclick="if(event.target === this) this.remove()">
            <div class="modal-content" style="background:#fff;border-radius:12px;width:90%;max-width:500px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="padding:20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:between;">
                    <h3 style="margin:0;font-size:18px;font-weight:700;">Mark as Alumni</h3>
                    <button onclick="this.closest('.modal-overlay').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
                </div>
                <form id="alumniForm" style="padding:20px;">
                    <div style="margin-bottom:15px;">
                        <label>Completion Year *</label>
                        <input type="number" id="alumniYear" class="form-control" value="${new Date().getFullYear()}" required>
                    </div>
                    <div style="margin-bottom:15px;">
                        <label>Status *</label>
                        <select id="completionStatus" class="form-control" required>
                            <option value="completed">Completed</option>
                            <option value="dropped">Dropped</option>
                        </select>
                    </div>
                    <div style="margin-bottom:15px;">
                        <label>Remarks</label>
                        <textarea id="alumniRemarks" class="form-control"></textarea>
                    </div>
                    <div style="text-align:right;">
                        <button type="button" onclick="this.closest('.modal-overlay').remove()" class="btn bs">Cancel</button>
                        <button type="submit" class="btn bt">Convert to Alumni</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    const div = document.createElement('div');
    div.innerHTML = modalHtml;
    document.body.appendChild(div);

    document.getElementById('alumniForm').onsubmit = async (e) => {
        e.preventDefault();
        const year = document.getElementById('alumniYear').value;
        const status = document.getElementById('completionStatus').value;
        const remarks = document.getElementById('alumniRemarks').value;

        try {
            const res = await fetch(`${window.APP_URL}/api/frontdesk/students`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'mark_alumni',
                    student_id: id,
                    alumni_year: year,
                    completion_status: status,
                    remarks: remarks
                })
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('Success', data.message, 'success').then(() => {
                    div.querySelector('.modal-overlay').remove();
                    window.location.reload();
                });
            } else throw new Error(data.message);
        } catch (err) { Swal.fire('Error', err.message, 'error'); }
    }
};

window.restoreStudent = (id, name) => {
    Swal.fire({
        title: 'Restore Student?',
        text: `Restore ${name} to active status?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Restore'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await fetch(`${window.APP_URL}/api/frontdesk/students`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'restore_student', student_id: id })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Restored', data.message, 'success').then(() => window.location.reload());
                } else throw new Error(data.message);
            } catch (err) { Swal.fire('Error', err.message, 'error'); }
        }
    });
};
