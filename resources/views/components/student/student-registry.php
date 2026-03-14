<?php
/**
 * Shared Student Registry Component
 * Nexus Design System — Advanced Dashboard Version
 */

$apiEndpoint = $apiEndpoint ?? APP_URL . '/api/admin/students';
$componentId = $componentId ?? 'shared_stu';
?>

<div class="pg-nexus">
    <!-- ── BREADCRUMB ── -->
    <div class="bc">
        <a href="#" onclick="goNav('overview')">Dashboard</a>
        <span class="bc-sep">&rsaquo;</span>
        <span class="bc-cur">Student Registry</span>
    </div>

    <!-- ── PAGE HEADER ── -->
    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background: rgba(59, 130, 246, 0.08); color: #3B82F6;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <h1 class="pg-title">Student Registry</h1>
                <p class="pg-sub">Central database for all enrolled and graduated students</p>
            </div>
        </div>
        <div class="pg-acts">
            <button class="btn bt" onclick="refreshStudents()">
                <i class="fa-solid fa-rotate"></i>
            </button>
            <button class="btn" style="background: #3B82F6; color: #fff;" onclick="window.location.href='<?= APP_URL ?>/dash/front-desk/index?page=admissions-adm-form'">
                <i class="fa-solid fa-user-plus"></i> New Admission
            </button>
        </div>
    </div>

    <!-- ── KPI STATS (Premium Style) ── -->
    <div class="stat-group mb">
        <div class="stat-item">
            <span class="lbl">Total Students</span>
            <span class="val" id="stu_stat_total">-</span>
            <span class="sub">Active & Registered</span>
        </div>
        <div class="stat-item">
            <span class="lbl" style="color: #F59E0B;">Quick Registered</span>
            <span class="val" id="stu_stat_quick">-</span>
            <span class="sub">Incomplete profiles</span>
        </div>
        <div class="stat-item">
            <span class="lbl" style="color: var(--green);">Full Profiles</span>
            <span class="val" id="stu_stat_full">-</span>
            <span class="sub">Verified students</span>
        </div>
        <div class="stat-item">
            <span class="lbl" style="color: #EF4444;">Overdue Fees</span>
            <span class="val" id="stu_stat_overdue">-</span>
            <span class="sub">Payments pending</span>
        </div>
    </div>

    <!-- ── FILTERS & SEARCH ── -->
    <div class="card mb" style="padding: 16px; border-radius: 14px;">
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 280px; position: relative;">
                <i class="fa-solid fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 14px;"></i>
                <input type="text" id="stu_search" class="fi" placeholder="Search by name, roll number, or phone..." style="padding-left: 38px; height: 44px; background: #f8fafc; border-color: transparent;" oninput="filterStu()">
            </div>
            
            <div style="display: flex; gap: 8px;">
                <select id="stu_status_filter" class="fi" style="width: 180px; height: 44px; background: #f8fafc; border-color: transparent;" onchange="filterStu()">
                    <option value="">Enrollment Status</option>
                    <option value="quick_registered">Quick Registered</option>
                    <option value="fully_registered">Full Registration</option>
                </select>
                
                <button class="btn bt" style="height: 44px; border-radius: 12px;" onclick="exportStuCSV()">
                    <i class="fa-solid fa-file-csv"></i> Export
                </button>
            </div>
        </div>
    </div>

    <!-- ── DATA TABLE ── -->
    <div class="card" style="border-radius: 14px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table" id="stu_table">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding-left: 24px;">Student Profile</th>
                        <th>Program & Batch</th>
                        <th>Contact</th>
                        <th>Registration</th>
                        <th>Status</th>
                        <th style="text-align: right; padding-right: 24px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="stu_table_body">
                    <tr><td colspan="6" style="text-align: center; padding: 60px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="stu_pagination" style="padding: 16px 24px; border-top: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
            <!-- Pagination Rendered by JS -->
        </div>
    </div>
</div>

<!-- Profile Modal (Full View) -->
<div id="stu_profile_modal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 500px; height: 100vh; margin: 0 0 0 auto; border-radius: 0;">
        <div class="modal-header">
            <h3>Student Profile</h3>
            <button onclick="closeStuProfile()">&times;</button>
        </div>
        <div class="modal-body" id="stu_profile_body" style="padding: 0; overflow-y: auto;">
            <!-- Profile Content -->
        </div>
    </div>
</div>

<script>
(function() {
    const API_URL = "<?= $apiEndpoint ?>";
    let allStudents = [];
    let filteredStudents = [];
    
    window.refreshStudents = async () => {
        const tbody = document.getElementById('stu_table_body');
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 60px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Refreshing...</td></tr>';
        await loadStudents();
    };

    async function loadStudents() {
        try {
            const res = await fetch(API_URL, typeof getHeaders === 'function' ? getHeaders() : {});
            const r = await res.json();
            if (r.success) {
                allStudents = r.data || [];
                updateStuStats();
                filterStu();
            } else {
                throw new Error(r.message);
            }
        } catch (e) {
            document.getElementById('stu_table_body').innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--red);">${e.message}</td></tr>`;
        }
    }

    function updateStuStats() {
        const total = allStudents.length;
        const quick = allStudents.filter(i => i.registration_status === 'quick_registered').length;
        const full  = total - quick;
        
        document.getElementById('stu_stat_total').textContent = total;
        document.getElementById('stu_stat_quick').textContent = quick;
        document.getElementById('stu_stat_full').textContent = full;
        // document.getElementById('stu_stat_overdue').textContent = '-'; // Need financial API link
    }

    window.filterStu = () => {
        const search = document.getElementById('stu_search').value.toLowerCase();
        const status = document.getElementById('stu_status_filter').value;
        
        filteredStudents = allStudents.filter(s => {
            const matchesSearch = !search || 
                (s.full_name || '').toLowerCase().includes(search) || 
                (s.phone || '').toLowerCase().includes(search) || 
                (s.roll_no || '').toLowerCase().includes(search);
            const matchesStatus = !status || s.registration_status === status;
            return matchesSearch && matchesStatus;
        });
        
        renderStuTable();
    };

    function renderStuTable() {
        const tbody = document.getElementById('stu_table_body');
        if (!filteredStudents.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 60px; color: var(--text-light);">No student matching filters.</td></tr>';
            return;
        }
        
        tbody.innerHTML = filteredStudents.map(s => {
            const isQuick = s.registration_status === 'quick_registered';
            const statusTag = isQuick 
                ? `<span class="tag" style="background:#FFF7ED; color:#C2410C; font-weight:700;">QUICK REG</span>`
                : `<span class="tag" style="background:#ECFDF5; color:#059669; font-weight:700;">FULLY REG</span>`;
            
            const photoUrl = s.photo_url ? (s.photo_url.startsWith('http') ? s.photo_url : APP_URL + s.photo_url) : null;
            const initials = (s.full_name || '?').charAt(0).toUpperCase();

            // Action button for Quick Reg - Complete Admission
            const completeBtn = isQuick 
                ? `<button class="btn sm" style="background:#6366f1; color:#fff;" onclick="window.location.href='${APP_URL}/dash/front-desk/index?page=admissions-adm-form&complete=${s.id}'"><i class="fa-solid fa-user-check"></i> Complete</button>`
                : '';

            return `
                <tr>
                    <td style="padding-left: 24px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:40px; height:40px; border-radius:10px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; overflow:hidden; border:1px solid #e2e8f0; flex-shrink:0;">
                                ${photoUrl ? `<img src="${photoUrl}" style="width:100%; height:100%; object-fit:cover;">` : `<span style="font-weight:700; color:#94a3b8;">${initials}</span>`}
                            </div>
                            <div>
                                <div style="font-weight: 700; color: var(--text-dark);">${s.full_name}</div>
                                <div style="font-size: 11px; color: var(--text-light); margin-top: 2px;">#${s.roll_no || 'N/A'}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 13px; font-weight: 600; color:#334155;">${s.batch_name || '—'}</div>
                        <div style="font-size: 11px; color:#64748b;">${s.course_name || '—'}</div>
                    </td>
                    <td>
                        <div style="font-size: 13px; font-weight: 600;">${s.phone || '—'}</div>
                    </td>
                    <td style="font-size: 12px; color: var(--text-light);">${s.admission_date || '-'}</td>
                    <td>${statusTag}</td>
                    <td style="text-align: right; padding-right: 24px;">
                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                            ${completeBtn}
                            <button class="btn bt sm" onclick="viewStuProfile(${s.id})"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    window.viewStuProfile = async (id) => {
        const s = allStudents.find(s => s.id == id);
        if (!s) return;
        
        const modal = document.getElementById('stu_profile_modal');
        const body  = document.getElementById('stu_profile_body');
        
        const photoUrl = s.photo_url ? (s.photo_url.startsWith('http') ? s.photo_url : APP_URL + s.photo_url) : null;
        const initials = (s.full_name || '?').charAt(0).toUpperCase();

        body.innerHTML = `
            <div style="background: linear-gradient(135deg, #3B82F6, #2563EB); padding: 40px 24px; color: #fff; text-align: center;">
                <div style="width: 100px; height: 100px; border-radius: 20px; background: rgba(255,255,255,0.2); border: 4px solid rgba(255,255,255,0.3); margin: 0 auto 16px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    ${photoUrl ? `<img src="${photoUrl}" style="width: 100%; height: 100%; object-fit: cover;">` : `<span style="font-size: 32px; font-weight: 850;">${initials}</span>`}
                </div>
                <h2 style="margin: 0; font-size: 20px; font-weight: 800;">${s.full_name}</h2>
                <div style="font-size: 13px; opacity: 0.8; margin-top: 4px;">Roll No: ${s.roll_no || 'N/A'}</div>
                <div style="margin-top: 16px; display: flex; justify-content: center; gap: 8px;">
                     <span class="tag" style="background: rgba(255,255,255,0.2); color: #fff; border: none;">${s.batch_name || 'No Batch'}</span>
                </div>
            </div>
            
            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                    <div style="background: #f8fafc; padding: 12px 16px; border-radius: 12px;">
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8; margin-bottom: 4px;">Contact Details</div>
                        <div style="font-weight: 700; color: #1e293b;">${s.phone || '—'}</div>
                        <div style="font-size: 13px; color: #64748b;">${s.email || '—'}</div>
                    </div>
                    <div style="background: #f8fafc; padding: 12px 16px; border-radius: 12px;">
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8; margin-bottom: 4px;">Program Info</div>
                        <div style="font-weight: 700; color: #1e293b;">${s.course_name || '—'}</div>
                        <div style="font-size: 13px; color: #64748b;">Admitted: ${s.admission_date || '-'}</div>
                    </div>
                     <div style="background: #f8fafc; padding: 12px 16px; border-radius: 12px;">
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8; margin-bottom: 4px;">Personal Info</div>
                        <div style="font-size: 13px; color: #334155;">Gender: <strong>${s.gender ? s.gender.toUpperCase() : '—'}</strong></div>
                        <div style="font-size: 13px; color: #334155;">Blood Group: <strong>${s.blood_group || '—'}</strong></div>
                        <div style="font-size: 13px; color: #334155;">DOB: <strong>${s.dob_bs || s.dob_ad || '—'}</strong></div>
                    </div>
                </div>
                
                <div style="margin-top: 32px; display: flex; flex-direction: column; gap: 12px;">
                    <button class="btn" style="background: #1e293b; color: #fff; height: 48px; border-radius: 14px;" onclick="window.location.href='${APP_URL}/dash/front-desk/index?page=fee-collect&student_id=${s.id}'"><i class="fa-solid fa-money-bill-transfer"></i> Collect Fees</button>
                    <button class="btn bt" style="height: 48px; border-radius: 14px;" onclick="closeStuProfile()">Close Profile</button>
                </div>
            </div>
        `;
        modal.style.display = 'flex';
    };

    window.closeStuProfile = () => {
        document.getElementById('stu_profile_modal').style.display = 'none';
    };

    window.exportStuCSV = () => {
        // Simple client-side CSV export
        const headers = ["Roll No", "Name", "Batch", "Course", "Phone", "Status"];
        const rows = filteredStudents.map(s => [
            s.roll_no || '',
            s.full_name || '',
            s.batch_name || '',
            s.course_name || '',
            s.phone || '',
            s.registration_status || ''
        ]);
        
        let csvContent = "data:text/csv;charset=utf-8," 
            + headers.join(",") + "\n"
            + rows.map(r => r.join(",")).join("\n");
            
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "students_registry_" + new Date().toISOString().split('T')[0] + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    loadStudents();
})();
</script>

<style>
.pg-nexus { animation: fadeIn 0.4s ease-out; }
.stat-item { padding: 20px; border-radius: 14px; position: relative; overflow: hidden; background: #fff !important; }
.stat-item .lbl { font-size: 11px; text-transform: uppercase; font-weight: 800; opacity: 0.7; margin-bottom: 8px; display: block; }
.stat-item .val { font-size: 24px; font-weight: 850; color: var(--text-dark); display: block; }
.stat-item .sub { font-size: 11px; color: var(--text-light); margin-top: 4px; display: block; }
.fi { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 16px; font-size: 14px; transition: all 0.2s; font-family: inherit; }
.fi:focus { border-color: #3B82F6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; }
.modal-content { background: #fff; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; margin: 16px; overflow: hidden; animation: slideInRight 0.3s ease-out; }
.modal-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 24px; }
.btn.sm { padding: 6px 10px; font-size: 12px; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideInRight { from { transform: translateX(100%); } to { transform: translateX(0); } }
</style>
