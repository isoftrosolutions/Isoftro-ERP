/**
 * Hamro ERP � ia-staff.js
 * Teachers & Front Desk: List, Add, Edit, Delete
 */

window.renderStaffList = async function(role) {
    const title = role==='teacher' ? 'Teachers' : 'Front Desk Staff';
    const icon  = role==='teacher' ? 'fa-user-tie' : 'fa-person-rays';
    const nav   = role==='teacher' ? 'teachers'   : 'frontdesk';
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">${title}</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid ${icon}"></i></div><div><div class="pg-title">${title}</div><div class="pg-sub">Manage ${title.toLowerCase()} profiles</div></div></div>
            <div class="pg-acts"><button class="btn bt" onclick="goNav('${nav}','add')"><i class="fa-solid fa-plus"></i> Add ${role==='teacher'?'Teacher':'Front Desk'}</button></div>
        </div>
        <div class="card mb" style="padding:15px;">
            <div style="display:flex;gap:15px;flex-wrap:wrap;">
                <div style="flex:1;min-width:250px;"><input type="text" id="staffSearch" class="form-control" placeholder="Search by name or email..." oninput="filterStaff(this.value,'${role}')"></div>
                <div style="width:150px;"><select id="staffStatusFilter" class="form-control" onchange="filterStaff(document.getElementById('staffSearch').value,'${role}',this.value)"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            </div>
        </div>
        <div class="card" id="staffTableContainer"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading ${title.toLowerCase()}...</span></div></div>
    </div>`;
    window.currentStaffData = [];
    await loadStaff(role);
};

window.loadStaff = async function(role) {
    const c = document.getElementById('staffTableContainer'); if (!c) return;
    try {
        const res = await fetch(`${APP_URL}/api/admin/staff?role=${role}`, { credentials:'include', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''} });
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        window.currentStaffData = result.data;
        _renderStaffTable(result.data, role);
    } catch(e) { c.innerHTML=`<div style="padding:40px;text-align:center;color:var(--red);">${e.message}</div>`; }
};

window.filterStaff = function(search='', role, status='') {
    const filtered = (window.currentStaffData||[]).filter(s => {
        const n = (s.full_name||s.name||'').toLowerCase();
        const em = (s.email||'').toLowerCase();
        const matchSearch = !search || n.includes(search.toLowerCase()) || em.includes(search.toLowerCase());
        const matchStatus = !status || s.status===status;
        return matchSearch && matchStatus;
    });
    _renderStaffTable(filtered, role);
};

function _renderStaffTable(staff, role) {
    const c = document.getElementById('staffTableContainer'); if (!c) return;
    if (!staff.length) { c.innerHTML=`<div style="padding:60px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-users-slash" style="font-size:3rem;margin-bottom:15px;"></i><p>No staff found.</p></div>`; return; }
    let html = `<div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Contact</th>${role==='teacher'?'<th>Employee ID</th><th>Specialization</th>':'<th>Joined Date</th>'}<th>Salary</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead><tbody>`;
    staff.forEach(s => {
        const sc = s.status==='active'?'bg-t':'bg-r';
        const n  = s.full_name||s.name||'N/A';
        html += `<tr>
            <td><div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;">${n.charAt(0)}</div><div style="font-weight:600">${n}</div></div></td>
            <td><div style="font-size:13px">${s.email||'No email'}</div><div style="font-size:11px;color:var(--tl)">${s.phone||'No phone'}</div></td>
            ${role==='teacher'?`<td><span class="tag bg-b">${s.employee_id||'N/A'}</span></td><td>${s.specialization||'General'}</td>`:`<td>${new Date(s.created_at).toLocaleDateString()}</td>`}
            <td><span class="tag bg-b">NPR ${parseFloat(s.monthly_salary || 0).toLocaleString()}</span></td>
            <td><span class="tag ${sc}">${s.status.toUpperCase()}</span></td>
            <td style="text-align:right;white-space:nowrap">
                <button class="btn-icon" title="Edit" onclick="editStaff('${role}',${s.user_id})"><i class="fa-solid fa-pen"></i></button>
                <button class="btn-icon text-danger" title="Delete" onclick="deleteStaff('${role}',${s.user_id},'${n.replace(/'/g,"\\''")}')"><i class="fa-solid fa-trash"></i></button>
            </td>
        </tr>`;
    });
    html += `</tbody></table></div>`;
    c.innerHTML = html;
}

window.editStaff = async function(role, userId) {
    try {
        const res = await fetch(`${APP_URL}/api/admin/staff?role=${role}`, { credentials:'include', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''} });
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        const staff = result.data.find(s => s.user_id===userId);
        if (!staff) throw new Error('Staff member not found');
        _renderEditStaffForm(role, staff);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
};

function _renderEditStaffForm(role, staff) {
    const title = role==='teacher' ? 'Teacher' : 'Front Desk Operator';
    const nav   = role==='teacher' ? 'teachers' : 'frontdesk';
    const n = staff.full_name||staff.name||'';
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('${nav}','profiles')">${role==='teacher'?'Teachers':'Front Desk'}</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Edit ${title}</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-user-pen"></i></div><div><div class="pg-title">Edit ${title}</div><div class="pg-sub">Update credentials and profile</div></div></div></div>
        <div class="card fu" style="max-width:800px;margin:0 auto;padding:30px;">
            <form id="editStaffForm">
                <input type="hidden" name="role" value="${role}">
                <input type="hidden" name="user_id" value="${staff.user_id}">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required value="${n}"></div>
                    <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" value="${staff.email||''}" disabled><small style="color:var(--tl);">Email cannot be changed</small></div>
                    <div class="form-group"><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-control" value="${staff.phone||''}"></div>
                    <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><option value="active" ${staff.status==='active'?'selected':''}>Active</option><option value="inactive" ${staff.status==='inactive'?'selected':''}>Inactive</option></select></div>
                    <div class="form-group"><label class="form-label">Monthly Salary (NPR) *</label><input type="number" name="monthly_salary" class="form-control" value="${staff.monthly_salary || 0}" step="0.01" required></div>
                    ${role==='teacher'?`
                    <div class="form-group"><label class="form-label">Employee ID</label><input type="text" class="form-control" value="${staff.employee_id||'N/A'}" disabled></div>
                    <div class="form-group"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" value="${staff.specialization||''}"></div>
                    <div class="form-group"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="${staff.qualification||''}"></div>
                    `:''}
                </div>
                <div style="margin-top:30px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('${nav}','profiles')">Cancel</button>
                    <button type="submit" class="btn bt">Update ${title}</button>
                </div>
            </form>
        </div>
    </div>`;
    document.getElementById('editStaffForm').onsubmit = async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        const btn = e.target.querySelector('button[type="submit"]'); const orig=btn.innerHTML;
        btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i> Updating...';
        try {
            const res = await fetch(APP_URL+'/api/admin/staff',{method:'PUT',credentials:'include',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify(data)});
            const result = await res.json();
            if (result.success) Swal.fire('Success',result.message,'success').then(()=>goNav(nav,'profiles'));
            else throw new Error(result.message);
        } catch(err) { Swal.fire('Error',err.message,'error'); }
        finally { btn.disabled=false; btn.innerHTML=orig; }
    };
}

window.renderAddStaffForm = function(role) {
    const title = role==='teacher' ? 'Teacher' : 'Front Desk Operator';
    const nav   = role==='teacher' ? 'teachers' : 'frontdesk';
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('${nav}','profiles')">${role==='teacher'?'Teachers':'Front Desk'}</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Add ${title}</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-user-plus"></i></div><div><div class="pg-title">Add New ${title}</div><div class="pg-sub">Setup credentials for new staff member</div></div></div></div>
        <div class="card fu" style="max-width:800px;margin:0 auto;padding:30px;">
            <form id="addStaffForm">
                <input type="hidden" name="role" value="${role}">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required placeholder="Display Name"></div>
                    <div class="form-group"><label class="form-label">Email Address *</label><input type="email" name="email" class="form-control" required placeholder="login@institute.com"></div>
                    <div class="form-group"><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX"></div>
                    <div class="form-group"><label class="form-label">Temporary Password</label><input type="text" name="password" class="form-control" value="Staff@123"><small style="color:var(--tl);">User will be prompted to change on first login</small></div>
                    <div class="form-group"><label class="form-label">Monthly Salary (NPR) *</label><input type="number" name="monthly_salary" class="form-control" placeholder="0.00" step="0.01" required></div>
                    ${role==='teacher'?`
                    <div class="form-group"><label class="form-label">Employee ID</label><input type="text" name="employee_id" class="form-control" placeholder="TCH-00X"></div>
                    <div class="form-group"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" placeholder="e.g. Mathematics, Nepali"></div>
                    <div class="form-group"><label class="form-label">Employment Type</label><select name="employment_type" class="form-control"><option value="full_time">Full Time</option><option value="part_time">Part Time</option><option value="visiting">Visiting Faculty</option></select></div>
                    `:''}
                </div>
                <div style="margin-top:30px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('${nav}','list')">Cancel</button>
                    <button type="submit" class="btn bt">Create ${title}</button>
                </div>
            </form>
        </div>
    </div>`;
    document.getElementById('addStaffForm').onsubmit = async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        const btn = e.target.querySelector('button[type="submit"]'); const orig=btn.innerHTML;
        btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i> Creating...';
        try {
            const res = await fetch(APP_URL+'/api/admin/staff',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify(data)});
            const result = await res.json();
            if (result.success) {
                const staffName  = data.name  || result.name  || '';
                const staffEmail = data.email || result.email || '';
                const staffPass  = data.password || 'Staff@123';
                const roleLabel  = role === 'teacher' ? 'Teacher' : 'Front Desk Staff';
                Swal.fire({
                    icon: 'success',
                    title: `<span style="color:#1E40AF;">? ${roleLabel} Added!</span>`,
                    html: `
                        <div style="text-align:left;margin:12px 0;">
                            <p style="font-size:13px;color:#374151;margin:0 0 16px;">Account created. Login credentials have been <strong>emailed automatically</strong>.</p>
                            <div style="background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:10px;padding:14px 18px;font-size:13px;">
                                <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #DBEAFE;">
                                    <span style="color:#6B7280;width:85px;font-weight:600;">?? Name</span>
                                    <span style="color:#111827;font-weight:700;">${staffName}</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #DBEAFE;">
                                    <span style="color:#6B7280;width:85px;font-weight:600;">?? Email</span>
                                    <span style="color:#111827;font-weight:700;">${staffEmail}</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;padding:7px 0;">
                                    <span style="color:#6B7280;width:85px;font-weight:600;">?? Password</span>
                                    <span style="color:#111827;font-weight:700;font-family:monospace;">${staffPass}</span>
                                </div>
                            </div>
                            <div style="background:#FEF3C7;border-radius:8px;padding:10px 14px;margin-top:12px;font-size:12px;color:#92400E;">
                                <i class="fa-solid fa-envelope" style="margin-right:5px;"></i>
                                A welcome email with these credentials has been sent to <strong>${staffEmail}</strong>.
                            </div>
                        </div>
                    `,
                    confirmButtonText: `<i class="fa-solid fa-users"></i> Back to ${roleLabel}s`,
                    confirmButtonColor: '#6bd08bff',
                }).then(() => goNav(nav, 'list'));
            } else {
                throw new Error(result.message);
            }
            
        } catch(err) { Swal.fire('Error',err.message,'error'); }
        finally { btn.disabled=false; btn.innerHTML=orig; }
    };
};

window.deleteStaff = async function(role, userId, name) {
    const nav = role==='teacher'?'teachers':'frontdesk';
    const r = await Swal.fire({title:'Delete Staff?',text:`Are you sure you want to delete "${name}"?`,icon:'warning',showCancelButton:true,confirmButtonColor:'#e74c3c',cancelButtonColor:'#6c757d',confirmButtonText:'Yes, delete!'});
    if (!r.isConfirmed) return;
    try {
        const res = await fetch(APP_URL+'/api/admin/staff',{method:'DELETE',credentials:'include',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify({user_id:userId,role})});
        const data = await res.json();
        if (data.success) Swal.fire('Deleted!',data.message,'success').then(()=>loadStaff(role));
        else throw new Error(data.message);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
};
