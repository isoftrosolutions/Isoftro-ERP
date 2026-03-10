/**
 * Hamro ERP — ia-inquiries.js
 * Inquiries & Admissions: List, Add, Analytics, Admission Form
 */

/* ══════════════ INQUIRY LIST ══════════════════════════════ */
window.renderInquiryList = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Inquiry List</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-clipboard-list"></i></div><div><div class="pg-title">Inquiry Management</div><div class="pg-sub">Track and manage admission inquiries</div></div></div>
            <div class="pg-acts"><button class="btn bt" onclick="goNav('inq-add')"><i class="fa-solid fa-plus"></i> New Inquiry</button></div>
        </div>
        <div class="card" id="inquiryListContainer"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading inquiries...</span></div></div>
    </div>`;
    await _loadInquiries();
};

async function _loadInquiries() {
    const c = document.getElementById('inquiryListContainer'); if (!c) return;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/inquiries', getHeaders());
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        const inqs = result.data;
        if (!inqs.length) { c.innerHTML=`<div style="padding:60px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-clipboard-list" style="font-size:3rem;margin-bottom:15px;"></i><p>No inquiries found.</p></div>`; return; }
        let html = `<div class="table-responsive"><table class="table"><thead><tr><th>Inquirer</th><th>Phone</th><th>Course</th><th>Status</th><th>Source</th><th>Date</th><th style="text-align:right">Actions</th></tr></thead><tbody>`;
        inqs.forEach(i => {
            const sc = i.status==='pending'?'bg-y':(i.status==='contacted'?'bg-b':'bg-t');
            html += `<tr>
                <td><div style="font-weight:600">${i.full_name}</div></td>
                <td>${i.phone}</td>
                <td>${i.course_name||'N/A'}</td>
                <td><span class="tag ${sc}">${(i.status || 'PENDING').toUpperCase()}</span></td>
                <td><span class="tag bg-b">${(i.source || 'WALK_IN').toUpperCase()}</span></td>
                <td>${new Date(i.created_at).toLocaleDateString()}</td>
                <td style="text-align:right;white-space:nowrap">
                    <button class="btn-icon" title="Follow up"><i class="fa-solid fa-phone"></i></button>
                    <button class="btn-icon" title="Convert to admission"><i class="fa-solid fa-user-check"></i></button>
                </td>
            </tr>`;
        });
        html += `</tbody></table></div>`;
        c.innerHTML = html;
    } catch(e) { c.innerHTML=`<div style="padding:20px;color:var(--red);text-align:center">${e.message}</div>`; }
}

/* ══════════════ ADD INQUIRY ════════════════════════════════ */
window.renderAddInquiryForm = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('inquiries')">Inquiries</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">New Inquiry</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-user-plus"></i></div><div><div class="pg-title">Add New Inquiry</div><div class="pg-sub">Capture a new admission inquiry or lead</div></div></div></div>
        <div class="card" style="max-width:800px;">
            <form id="addInquiryForm" onsubmit="submitInquiry(event)">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group"><label class="form-label required">Full Name</label><input type="text" name="full_name" class="form-control" required placeholder="Enter full name"></div>
                    <div class="form-group"><label class="form-label required">Phone Number</label><input type="tel" name="phone" class="form-control" required placeholder="98XXXXXXXX"></div>
                    <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" placeholder="email@example.com"></div>
                    <div class="form-group"><label class="form-label">Alternative Phone</label><input type="tel" name="alt_phone" class="form-control" placeholder="Optional"></div>
                    <div class="form-group"><label class="form-label required">Interested Course</label><select name="course_id" class="form-control" required id="inqCourseSelect"><option value="">Select Course</option></select></div>
                    <div class="form-group"><label class="form-label required">Source</label><select name="source" class="form-control" required><option value="">Select Source</option><option value="website">Website</option><option value="facebook">Facebook</option><option value="google">Google Ads</option><option value="referral">Referral</option><option value="walkin">Walk-in</option><option value="phone">Phone Call</option><option value="other">Other</option></select></div>
                    <div class="form-group"><label class="form-label">Date of Inquiry</label><input type="date" name="inquiry_date" class="form-control" value="${new Date().toISOString().split('T')[0]}"></div>
                    <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><option value="pending" selected>Pending</option><option value="contacted">Contacted</option><option value="interested">Interested</option><option value="not_interested">Not Interested</option></select></div>
                </div>
                <div class="form-group"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2" placeholder="Full address (optional)"></textarea></div>
                <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Additional notes about the inquiry..."></textarea></div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" class="btn bs" onclick="goNav('inquiries')">Cancel</button>
                    <button type="submit" class="btn bt" id="inqSubmitBtn"><i class="fa-solid fa-save"></i> Save Inquiry</button>
                </div>
            </form>
        </div>
    </div>`;
    await _loadInqCourses();
};

async function _loadInqCourses() {
    const sel = document.getElementById('inqCourseSelect'); if (!sel) return;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/courses', getHeaders());
        const result = await res.json();
        if (result.success) result.data.forEach(c => { const o=document.createElement('option'); o.value=c.id; o.textContent=c.name; sel.appendChild(o); });
    } catch(e) { console.error('Failed to load courses for inquiry',e); }
}

window.submitInquiry = async function(e) {
    e.preventDefault();
    const form = document.getElementById('addInquiryForm');
    const btn  = document.getElementById('inqSubmitBtn');
    btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
    try {
        const res = await fetch(APP_URL+'/api/frontdesk/inquiries',getHeaders({method:'POST',body:new FormData(form)}));
        const result = await res.json();
        if (result.success) { Swal.fire('Saved!','Inquiry recorded successfully.','success').then(()=>goNav('inquiries')); }
        else throw new Error(result.message);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
    finally { btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-save"></i> Save Inquiry'; }
};

/* ══════════════ ANALYTICS ══════════════════════════════════ */
window.renderInquiryAnalytics = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('inquiries')">Inquiries</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Conversion Analytics</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-chart-pie"></i></div><div><div class="pg-title">Inquiry Analytics</div><div class="pg-sub">Track conversion rates and inquiry performance</div></div></div></div>
        <div class="sg mb">
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-t"><i class="fa-solid fa-users"></i></div></div><div class="sc-val" id="totalInquiries">-</div><div class="sc-lbl">Total Inquiries</div></div>
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-b"><i class="fa-solid fa-user-check"></i></div></div><div class="sc-val" id="convertedCount">-</div><div class="sc-lbl">Converted to Students</div></div>
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-r"><i class="fa-solid fa-percent"></i></div></div><div class="sc-val" id="conversionRate">-</div><div class="sc-lbl">Conversion Rate</div></div>
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-y"><i class="fa-solid fa-clock"></i></div></div><div class="sc-val" id="pendingCount">-</div><div class="sc-lbl">Pending Follow-ups</div></div>
        </div>
        <div class="g65 mb">
            <div class="card"><div class="ct"><i class="fa-solid fa-chart-bar"></i> Inquiries by Source</div><div id="sourceChart" style="height:200px;display:flex;align-items:flex-end;gap:15px;padding:20px;justify-content:center;"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div></div></div>
            <div class="card"><div class="ct"><i class="fa-solid fa-chart-line"></i> Status Breakdown</div><div id="statusTable"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div></div></div>
        </div>
    </div>`;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/inquiries', getHeaders());
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        const inqs = result.data;
        const total = inqs.length, converted = inqs.filter(i=>i.status==='converted').length, pending = inqs.filter(i=>i.status==='pending').length;
        document.getElementById('totalInquiries').textContent = total;
        document.getElementById('convertedCount').textContent = converted;
        document.getElementById('conversionRate').textContent = (total>0?Math.round((converted/total)*100):0)+'%';
        document.getElementById('pendingCount').textContent = pending;
        const sources = {}; inqs.forEach(i=>{ sources[i.source]=(sources[i.source]||0)+1; });
        document.getElementById('sourceChart').innerHTML = Object.entries(sources).map(([src,cnt])=>`<div style="text-align:center"><div style="height:${Math.max(40,cnt*15)}px;width:50px;background:var(--teal);border-radius:4px 4px 0 0;margin:0 auto"></div><div style="margin-top:8px;font-size:12px;color:#64748b">${src}</div><div style="font-weight:600">${cnt}</div></div>`).join('') || '<div style="color:#94a3b8">No data</div>';
        const statuses = {}; inqs.forEach(i=>{ statuses[i.status]=(statuses[i.status]||0)+1; });
        document.getElementById('statusTable').innerHTML = `<table class="table"><thead><tr><th>Status</th><th>Count</th><th>%</th></tr></thead><tbody>${Object.entries(statuses).map(([st,cnt])=>`<tr><td><span class="tag bg-${st==='pending'?'y':st==='contacted'?'b':st==='interested'?'t':'r'}">${st.toUpperCase()}</span></td><td>${cnt}</td><td>${Math.round((cnt/total)*100)}%</td></tr>`).join('')}</tbody></table>`;
    } catch(e) { console.error('Analytics error',e); }
};

/* ══════════════ ADMISSION FORM ════════════════════════════ */
window.renderAdmissionForm = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('inquiries')">Inquiries</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Admission Form</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-id-card"></i></div><div><div class="pg-title">Admission Form</div><div class="pg-sub">Generate admission forms from inquiry records</div></div></div></div>
        <div class="card mb">
            <div class="ct"><i class="fa-solid fa-filter"></i> Filter Inquiries for Admission</div>
            <div style="display:flex;gap:15px;flex-wrap:wrap;margin-top:15px;">
                <div style="flex:1;min-width:250px;"><input type="text" id="admissionSearch" class="form-control" placeholder="Search by name or phone..."></div>
                <select id="admissionStatus" class="form-control" style="width:180px;"><option value="">All Statuses</option><option value="interested">Interested</option><option value="contacted">Contacted</option><option value="pending">Pending</option></select>
                <button class="btn bs" onclick="filterForAdmission()">Filter</button>
            </div>
        </div>
        <div class="card" id="admissionListContainer"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading inquiries...</span></div></div>
    </div>`;
    await _loadInquiriesForAdmission();
};

async function _loadInquiriesForAdmission(search='', status='') {
    const c = document.getElementById('admissionListContainer'); if (!c) return;
    try {
        let url = APP_URL + '/api/frontdesk/inquiries';
        const p = new URLSearchParams();
        if (search) p.append('search',search); if (status) p.append('status',status);
        if (p.toString()) url+='?'+p.toString();
        const res = await fetch(url, getHeaders());
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        const inqs = result.data;
        if (!inqs.length) { c.innerHTML=`<div style="padding:60px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-user-plus" style="font-size:3rem;margin-bottom:15px;"></i><p>No inquiries found.</p></div>`; return; }
        let html = `<table class="table"><thead><tr><th>Inquirer</th><th>Contact</th><th>Course</th><th>Status</th><th>Date</th><th style="text-align:right">Actions</th></tr></thead><tbody>`;
        inqs.forEach(i => {
            const sc = i.status==='pending'?'bg-y':(i.status==='contacted'?'bg-b':i.status==='interested'?'bg-t':'bg-r');
            html += `<tr>
                <td><div style="font-weight:600">${i.full_name}</div></td>
                <td><div style="font-size:12px">${i.phone}</div><div style="font-size:11px;color:#64748b">${i.email||''}</div></td>
                <td>${i.course_name||'N/A'}</td>
                <td><span class="tag ${sc}">${i.status.toUpperCase()}</span></td>
                <td>${new Date(i.created_at).toLocaleDateString()}</td>
                <td style="text-align:right"><button class="btn bt btn-sm" onclick="generateAdmissionForm(${i.id},'${i.full_name.replace(/'/g,"\\'")}')"><i class="fa-solid fa-file-contract"></i> Generate Form</button></td>
            </tr>`;
        });
        html += `</tbody></table>`;
        c.innerHTML = html;
    } catch(e) { c.innerHTML=`<div style="padding:20px;color:var(--red);text-align:center">${e.message}</div>`; }
}

window.filterForAdmission = async function() {
    const search = document.getElementById('admissionSearch')?.value||'';
    const status = document.getElementById('admissionStatus')?.value||'';
    await _loadInquiriesForAdmission(search, status);
};

window.generateAdmissionForm = function(id, name) {
    Swal.fire('Coming Soon', `Admission form generation for ${name} will be available in the next update.`, 'info');
};
