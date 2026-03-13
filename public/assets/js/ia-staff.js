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
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">${title}</span>
        </div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: linear-gradient(135deg, ${role==='teacher'?'#6366f1, #a855f7':'#10b981, #34d399'}); color: #fff;">
                    <i class="fa-solid ${icon}"></i>
                </div>
                <div>
                    <div class="pg-title" style="font-size: clamp(1.2rem, 3vw, 1.5rem);">${title} Directory</div>
                    <div class="pg-sub">Manage academic and administrative staff profiles</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="goNav('${nav}','add')" style="padding: 10px clamp(15px, 2vw, 20px); font-size: clamp(11px, 1.2vw, 13px); border-radius: 12px; box-shadow: none;">
                    <i class="fa-solid fa-plus"></i> <span>Add ${role==='teacher'?'Teacher':'Staff'}</span>
                </button>
            </div>
        </div>
        
        <div class="toolbar" style="padding: clamp(10px, 2vw, 15px); margin-bottom: 25px; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.4); border-radius: 16px;">
            <div class="search-wrap" style="flex: 1; min-width: 250px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search by name, email or phone..." oninput="filterStaff(this.value,'${role}')">
            </div>
            <div style="width: clamp(120px, 20vw, 180px);">
                <select id="staffStatusFilter" class="form-control" style="border-radius: 12px; height: 42px; font-size: 13px;" onchange="filterStaff(document.getElementById('searchInput').value,'${role}',this.value)">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="row-count-badge" id="rowCount">0 Records</div>
        </div>

        <div class="premium-tw table-responsive" id="staffTableContainer">
            <div class="pg-loading">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <span>Loading ${title.toLowerCase()}...</span>
            </div>
        </div>
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
        const n = (u.name||s.name||'').toLowerCase();
        const em = (u.email||'').toLowerCase();
        const ph = (u.phone||'').toLowerCase();
        const matchSearch = !search || n.includes(search.toLowerCase()) || em.includes(search.toLowerCase()) || ph.includes(search.toLowerCase());
        const matchStatus = !status || s.status===status;
        return matchSearch && matchStatus;
    });
    
    if (document.getElementById('rowCount')) {
        const label = role === 'teacher' ? 'Teachers' : 'Staff';
        document.getElementById('rowCount').textContent = `${filtered.length} ${label}`;
    }
    
    _renderStaffTable(filtered, role);
};

function _renderStaffTable(staff, role) {
    const c = document.getElementById('staffTableContainer'); if (!c) return;
    if (!staff.length) { 
        c.innerHTML=`<div class="empty-state-premium" style="margin: 40px 0;">
            <div class="empty-ico"><i class="fa-solid fa-users-slash"></i></div>
            <h4>No Records Found</h4>
            <p>We couldn't find any ${role==='teacher'?'teachers':'staff members'} matching your criteria.</p>
        </div>`; 
        return; 
    }
    
    let html = `<table class="premium-student-table">
        <thead>
            <tr>
                <th style="width: 25%;">Profile</th>
                <th style="width: 20%;">Contact</th>
                ${role==='teacher'?'<th style="width: 15%;">Credentials</th><th style="width: 15%;">Expertise</th>':'<th style="width: 20%;">Joined</th>'}
                <th style="width: 15%;">Salary</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 10%; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>`;
        
    staff.forEach(s => {
        const isActive = s.status==='active';
        const n = u.name||s.name||'N/A';
        const initials = n.charAt(0).toUpperCase();
        
        html += `<tr>
            <td>
                <div class="std-card">
                    <div class="std-img initials" style="background: linear-gradient(135deg, ${role==='teacher'?'#6366f1, #a855f7':'#10b981, #34d399'}); color: #fff;">
                        ${initials}
                    </div>
                    <div class="std-info">
                        <div class="name">${n}</div>
                        <div class="id">${role==='teacher'?'Teacher':'Administrative'}</div>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-size: 13px; font-weight: 600; color: #1e293b;">${u.email||'-'}</div>
                <div style="font-size: 11px; color: #64748b;">${u.phone||'-'}</div>
            </td>
            ${role==='teacher'?`
                <td><span class="badge" style="background: #eff6ff; color: #1d4ed8; font-size: 10px; padding: 4px 10px;">${s.employee_id||'N/A'}</span></td>
                <td><div style="font-size: 13px; color: #334155; font-weight: 500;">${s.specialization||'General'}</div></td>
            ` : `
                <td><div style="font-size: 13px; color: #334155;">${new Date(s.created_at).toLocaleDateString()}</div></td>
            `}
            <td>
                <div style="font-size: 13px; font-weight: 700; color: #0a524a;">NPR ${parseFloat(s.monthly_salary || 0).toLocaleString()}</div>
            </td>
            <td>
                <span class="badge" style="background: ${isActive?'#ecfdf5':'#fff1f2'}; color: ${isActive?'#059669':'#e11d48'}; font-weight: 700; font-size: 10px;">
                    ${s.status.toUpperCase()}
                </span>
            </td>
            <td style="text-align:right">
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn-icon-p" title="Edit Profile" onclick="editStaff('${role}',${s.user_id})">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn-icon-p" style="color: #e11d48; border-color: #fecdd3;" title="Delete Record" onclick="deleteStaff('${role}',${s.user_id},'${n.replace(/'/g,"\\''")}')">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    });
    
    html += `</tbody></table>`;
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
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a> 
            <span class="bc-sep">/</span> 
            <a href="#" onclick="goNav('${nav}','profiles')">${role==='teacher'?'Teachers':'Staff'}</a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">Edit Profile</span>
        </div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: linear-gradient(135deg, #d4d5e6ff, #55f7c6ff); color: #fff;">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <div class="pg-title" style="font-size: clamp(1.2rem, 3vw, 1.5rem);">Edit ${title}</div>
                    <div class="pg-sub">Update credentials and professional profile</div>
                </div>
            </div>
        </div>
        
        <div class="card fu" style="max-width: 900px; margin: 0 auto; padding: clamp(20px, 4vw, 40px); border-radius: 20px;">
            <form id="editStaffForm">
                <input type="hidden" name="role" value="${role}">
                <input type="hidden" name="user_id" value="${staff.user_id}">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(clamp(200px, 100%, 300px), 1fr)); gap: clamp(15px, 3vw, 25px);">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required value="${n}" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="${staff.email||''}" disabled style="border-radius: 12px; padding: 12px 16px; background: #f8fafc;">
                        <small style="color: #64748b; font-size: 11px; margin-top: 5px; display: block;">Email is used for login and cannot be modified.</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="${staff.phone||''}" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" style="border-radius: 12px; padding: 0 16px; height: 50px;">
                            <option value="active" ${staff.status==='active'?'selected':''}>Active</option>
                            <option value="inactive" ${staff.status==='inactive'?'selected':''}>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Monthly Salary (NPR) *</label>
                        <input type="number" name="monthly_salary" class="form-control" value="${staff.monthly_salary || 0}" step="0.01" required style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    
                    ${role==='teacher'?`
                    <div class="form-group">
                        <label class="form-label">Employee ID</label>
                        <input type="text" class="form-control" value="${staff.employee_id||'N/A'}" disabled style="border-radius: 12px; padding: 12px 16px; background: #f8fafc;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" class="form-control" value="${staff.specialization||''}" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Qualification</label>
                        <input type="text" name="qualification" class="form-control" value="${staff.qualification||''}" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    `:''}
                </div>
                
                <div style="margin-top: 40px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('${nav}','profiles')" style="min-width: 120px; border-radius: 12px; padding: 12px 24px;">Cancel</button>
                    <button type="submit" class="btn bt" style="min-width: 180px; border-radius: 12px; padding: 12px 24px; background: linear-gradient(135deg, #6366f1, #a855f7); color: #fff; border: none; font-weight: 700;">
                        Update Profile
                    </button>
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
        const grad = role==='teacher' ? 'linear-gradient(135deg, #6366f1, #8b5cf6)' : 'linear-gradient(135deg, #10b981, #059669)';
        const accent = role==='teacher' ? '#6366f1' : '#10b981';

    mc.innerHTML = `<div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a> 
            <span class="bc-sep">/</span> 
            <a href="#" onclick="goNav('${nav}','profiles')">${role==='teacher'?'Teachers':'Staff'}</a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">Add Member</span>
        </div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: ${grad}; color: #fff;">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <div class="pg-title" style="font-size: clamp(1.2rem, 3vw, 1.5rem);">Register ${title}</div>
                    <div class="pg-sub">Define professional profile and institutional records</div>
                </div>
            </div>
        </div>
        
        <div class="card fu" style="max-width: 900px; margin: 0 auto; padding: clamp(20px, 4vw, 40px); border-radius: 20px;">
            <form id="addStaffForm">
                <input type="hidden" name="role" value="${role}">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(clamp(200px, 100%, 300px), 1fr)); gap: clamp(15px, 3vw, 25px);">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Full Name" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="login@institute.com" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Access Password</label>
                        <input type="text" name="password" class="form-control" value="Staff@123" style="border-radius: 12px; padding: 12px 16px;">
                        <small style="color: #64748b; font-size: 11px; margin-top: 5px; display: block;">Default password. Member can reset after login.</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Monthly Salary (NPR) *</label>
                        <input type="number" name="monthly_salary" class="form-control" placeholder="0.00" step="0.01" required style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    
                    ${role==='teacher'?`
                    <div class="form-group">
                        <label class="form-label">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control" placeholder="TCH-00X" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" class="form-control" placeholder="e.g. Mathematics" style="border-radius: 12px; padding: 12px 16px;">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Employment Type</label>
                        <select name="employment_type" class="form-control" style="border-radius: 12px; padding: 0 16px; height: 50px;">
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="visiting">Visiting Faculty</option>
                        </select>
                    </div>
                    `:''}
                </div>
                
                <div style="margin-top: 40px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('${nav}','profiles')" style="min-width: 120px; border-radius: 12px; padding: 12px 24px;">Cancel</button>
                    <button type="submit" class="btn bt" style="min-width: 180px; border-radius: 12px; padding: 12px 24px; background: ${grad}; color: #fff; border: none; font-weight: 700; box-shadow: 0 4px 15px ${role==='teacher'?'rgba(99, 102, 241, 0.3)':'rgba(16, 185, 129, 0.3)'};">
                        Confirm ${title}
                    </button>
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
