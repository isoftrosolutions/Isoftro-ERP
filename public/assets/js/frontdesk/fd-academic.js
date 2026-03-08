/**
 * Hamro ERP — ia-academic.js
 * Courses & Batches: List, Add, Edit, Delete
 */

/* ══════════════ COURSE LIST ═══════════════════════════════ */
window.renderCourseList = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Courses</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-book-bookmark"></i></div><div><div class="pg-title">Course Management</div><div class="pg-sub">Define and manage institute courses</div></div></div>
            <div class="pg-acts"><button class="btn bt" onclick="goNav('academic','courses',{action:'add'})"><i class="fa-solid fa-plus"></i> Create Course</button></div>
        </div>
        <div class="card" id="courseListContainer"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading courses...</span></div></div>
    </div>`;
    await _loadCourses();
};

async function _loadCourses() {
    const c = document.getElementById('courseListContainer'); if (!c) return;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/courses');
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        if (!result.data.length) { c.innerHTML=`<div style="padding:60px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-book-open" style="font-size:3rem;margin-bottom:15px;"></i><p>No courses created yet.</p></div>`; return; }
        let html = `<div class="table-responsive"><table class="table"><thead><tr><th>Code</th><th>Course Name</th><th>Category</th><th>Fee</th><th>Batches</th><th>Students</th><th style="text-align:right">Actions</th></tr></thead><tbody>`;
        result.data.forEach(c2 => {
            html += `<tr>
                <td><span style="font-weight:700">${c2.code}</span></td>
                <td><div style="font-weight:600">${c2.name}</div></td>
                <td><span class="tag bg-b">${c2.category.toUpperCase()}</span></td>
                <td><span style="font-weight:600;color:var(--primary)">RS ${parseFloat(c2.fee||0).toLocaleString()}</span></td>
                <td>${c2.total_batches||0}</td><td>${c2.total_students||0}</td>
                <td style="text-align:right;white-space:nowrap">
                    <button class="btn-icon" title="Edit" onclick="goNav('academic','courses',{id:${c2.id}})"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-icon" title="Batches" onclick="goNav('academic','batches',{course_id:${c2.id}})"><i class="fa-solid fa-layer-group"></i></button>
                    <button class="btn-icon text-danger" title="Delete" onclick="deleteCourse(${c2.id},'${c2.name.replace(/'/g,"\\''")}')"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;
        });
        html += `</tbody></table></div>`;
        c.innerHTML = html;
    } catch(e) { c.innerHTML=`<div style="padding:20px;color:var(--red);text-align:center">${e.message}</div>`; }
}

window.renderAddCourseForm = function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('academic','courses')">Courses</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">New Course</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-folder-plus"></i></div><div><div class="pg-title">Define Course</div><div class="pg-sub">Add a new educational program</div></div></div></div>
        <div class="card fu" style="max-width:800px;margin:0 auto;padding:30px;">
            <form id="courseAddForm">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group"><label class="form-label">Course Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. Civil Engineering License"></div>
                    <div class="form-group"><label class="form-label">Course Code *</label><input type="text" name="code" class="form-control" required placeholder="e.g. CEL-2081"></div>
                    <div class="form-group"><label class="form-label">Category</label><select name="category" class="form-control"><option value="general">General</option><option value="loksewa">Loksewa</option><option value="health">Health</option><option value="banking">Banking</option><option value="tsc">TSC</option><option value="engineering">Engineering</option></select></div>
                    <div class="form-group"><label class="form-label">Course Fee (RS) *</label><input type="number" name="fee" class="form-control" required placeholder="5000"></div>
                    <div class="form-group"><label class="form-label">Duration (Weeks)</label><input type="number" name="duration_weeks" class="form-control" placeholder="12"></div>
                    <div class="form-group"><label class="form-label">Total Seats</label><input type="number" name="seats" class="form-control" placeholder="100"></div>
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" placeholder="Brief course overview..."></textarea></div>
                </div>
                <div style="margin-top:30px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('academic','courses')">Cancel</button>
                    <button type="submit" class="btn bt">Save Course</button>
                </div>
            </form>
        </div>
    </div>`;
    document.getElementById('courseAddForm').onsubmit = e => _submitCourseForm(e,'POST');
};

window.renderEditCourseForm = async function(id) {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('academic','courses')">Courses</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Edit Course</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-pen-to-square"></i></div><div><div class="pg-title">Edit Course</div><div class="pg-sub">Update course details</div></div></div></div>
        <div class="card fu" style="max-width:800px;margin:0 auto;padding:30px;">
            <form id="courseEditForm">
                <input type="hidden" name="id" value="${id}">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group"><label class="form-label">Course Name *</label><input type="text" name="name" id="editCourseName" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Course Code *</label><input type="text" name="code" id="editCourseCode" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Category</label><select name="category" id="editCourseCategory" class="form-control"><option value="general">General</option><option value="loksewa">Loksewa</option><option value="health">Health</option><option value="banking">Banking</option><option value="tsc">TSC</option><option value="engineering">Engineering</option></select></div>
                    <div class="form-group"><label class="form-label">Course Fee (RS) *</label><input type="number" name="fee" id="editCourseFee" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Duration (Weeks)</label><input type="number" name="duration_weeks" id="editCourseDur" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Total Seats</label><input type="number" name="seats" id="editCourseSeats" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Status</label><select name="is_active" id="editCourseStatus" class="form-control"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Description</label><textarea name="description" id="editCourseDesc" class="form-control" rows="4"></textarea></div>
                </div>
                <div style="margin-top:30px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('academic','courses')">Cancel</button>
                    <button type="submit" class="btn bt">Update Course</button>
                </div>
            </form>
        </div>
    </div>`;
    await _loadCourseData(id);
    document.getElementById('courseEditForm').onsubmit = e => _submitCourseForm(e,'PUT');
};

async function _loadCourseData(id) {
    try {
        const res = await fetch(`${APP_URL}/api/frontdesk/courses?id=${id}`);
        const data = await res.json();
        if (data.success && data.data.length) {
            const c = data.data[0];
            document.getElementById('editCourseName').value = c.name;
            document.getElementById('editCourseCode').value = c.code;
            document.getElementById('editCourseCategory').value = c.category;
            document.getElementById('editCourseFee').value = c.fee||0;
            document.getElementById('editCourseDur').value = c.duration_weeks||'';
            document.getElementById('editCourseSeats').value = c.seats||'';
            document.getElementById('editCourseDesc').value = c.description||'';
            document.getElementById('editCourseStatus').value = c.is_active;
        }
    } catch(e) { Swal.fire('Error','Failed to fetch course details','error'); }
}

async function _submitCourseForm(e, method) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const btn = e.target.querySelector('button[type="submit"]'); const orig=btn.innerHTML;
    btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
    try {
        const res = await fetch(APP_URL+'/api/frontdesk/courses', {method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)});
        const result = await res.json();
        if (result.success) Swal.fire('Success',result.message,'success').then(()=>goNav('academic','courses'));
        else throw new Error(result.message);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
    finally { btn.disabled=false; btn.innerHTML=orig; }
}

window.deleteCourse = async function(id, name) {
    const r = await Swal.fire({title:'Delete Course?',text:`Delete "${name}"? Only works if no active batches.`,icon:'warning',showCancelButton:true,confirmButtonColor:'#e74c3c',confirmButtonText:'Yes, delete!'});
    if (!r.isConfirmed) return;
    try {
        const res = await fetch(APP_URL+'/api/frontdesk/courses',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
        const data = await res.json();
        if (data.success) Swal.fire('Deleted!',data.message,'success').then(()=>_loadCourses());
        else throw new Error(data.message);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
};

/* ══════════════ BATCH LIST ════════════════════════════════ */
window.renderBatchList = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Batches</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-layer-group"></i></div><div><div class="pg-title">Batch Management</div><div class="pg-sub">Manage course batches and schedules</div></div></div>
            <div class="pg-acts"><button class="btn bt" onclick="goNav('academic','batches',{action:'add'})"><i class="fa-solid fa-plus"></i> New Batch</button></div>
        </div>
        <div class="card" id="batchListContainer"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading batches...</span></div></div>
    </div>`;
    await _loadBatches();
};

async function _loadBatches() {
    const c = document.getElementById('batchListContainer'); if (!c) return;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/batches');
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        if (!result.data.length) { c.innerHTML=`<div style="padding:60px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-layer-group" style="font-size:3rem;margin-bottom:15px;"></i><p>No batches created yet.</p></div>`; return; }
        let html = `<div class="table-responsive"><table class="table"><thead><tr><th>Batch Name</th><th>Course</th><th>Shift</th><th>Students</th><th>Start Date</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead><tbody>`;
        result.data.forEach(b => {
            const sc = b.status==='active'?'bg-t':'bg-b';
            html += `<tr>
                <td><div style="font-weight:600">${b.name}</div></td>
                <td>${b.course_name}</td>
                <td><span class="tag bg-y">${b.shift.toUpperCase()}</span></td>
                <td>${b.total_students}/${b.max_strength}</td>
                <td>${b.start_date}</td>
                <td><span class="tag ${sc}">${b.status.toUpperCase()}</span></td>
                <td style="text-align:right;white-space:nowrap">
                    <button class="btn-icon" title="Edit" onclick="goNav('academic','batches',{id:${b.id}})"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-icon text-danger" title="Delete" onclick="deleteBatch(${b.id},'${b.name.replace(/'/g,"\\''")}')"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;
        });
        html += `</tbody></table></div>`;
        c.innerHTML = html;
    } catch(e) { c.innerHTML=`<div style="padding:20px;color:var(--red);text-align:center">${e.message}</div>`; }
}

window.renderAddBatchForm = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('academic','batches')">Batches</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">New Batch</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-layer-plus"></i></div><div><div class="pg-title">Create New Batch</div><div class="pg-sub">Setup a new class schedule</div></div></div></div>
        <div class="card fu" style="max-width:800px;margin:0 auto;padding:30px;">
            <form id="batchAddForm">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Course *</label><select name="course_id" id="batchCourseSelect" class="form-control" required><option value="">Select Course</option></select></div>
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Batch Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. Morning Batch 2081"></div>
                    <div class="form-group"><label class="form-label">Shift</label><select name="shift" class="form-control"><option value="morning">Morning</option><option value="day">Day</option><option value="evening">Evening</option></select></div>
                    <div class="form-group"><label class="form-label">Max Strength</label><input type="number" name="max_strength" class="form-control" value="40"></div>
                    <div class="form-group"><label class="form-label">Start Date *</label><input type="date" name="start_date" class="form-control" required value="${new Date().toISOString().split('T')[0]}"></div>
                    <div class="form-group"><label class="form-label">End Date (Optional)</label><input type="date" name="end_date" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Room / Hall</label><input type="text" name="room" class="form-control" placeholder="Room 101"></div>
                    <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="upcoming">Upcoming</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                </div>
                <div style="margin-top:30px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('academic','batches')">Cancel</button>
                    <button type="submit" class="btn bt">Create Batch</button>
                </div>
            </form>
        </div>
    </div>`;
    await _populateCourses('batchCourseSelect');
    document.getElementById('batchAddForm').onsubmit = e => _submitBatchForm(e,'POST');
};

window.renderEditBatchForm = async function(id) {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('academic','batches')">Batches</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Edit Batch</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-layer-group"></i></div><div><div class="pg-title">Edit Batch</div><div class="pg-sub">Update batch details and schedule</div></div></div></div>
        <div class="card fu" style="max-width:800px;margin:0 auto;padding:30px;">
            <form id="batchEditForm">
                <input type="hidden" name="id" value="${id}">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Course *</label><select name="course_id" id="editBatchCourseSelect" class="form-control" required disabled><option value="">Select Course</option></select></div>
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Batch Name *</label><input type="text" name="name" id="editBatchName" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Shift</label><select name="shift" id="editBatchShift" class="form-control"><option value="morning">Morning</option><option value="day">Day</option><option value="evening">Evening</option></select></div>
                    <div class="form-group"><label class="form-label">Max Strength</label><input type="number" name="max_strength" id="editBatchMax" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Start Date *</label><input type="date" name="start_date" id="editBatchStart" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">End Date</label><input type="date" name="end_date" id="editBatchEnd" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Room / Hall</label><input type="text" name="room" id="editBatchRoom" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Status</label><select name="status" id="editBatchStatus" class="form-control"><option value="active">Active</option><option value="upcoming">Upcoming</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                </div>
                <div style="margin-top:30px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('academic','batches')">Cancel</button>
                    <button type="submit" class="btn bt">Update Batch</button>
                </div>
            </form>
        </div>
    </div>`;
    await _populateCourses('editBatchCourseSelect');
    await _loadBatchData(id);
    document.getElementById('batchEditForm').onsubmit = e => _submitBatchForm(e,'PUT');
};

async function _loadBatchData(id) {
    try {
        const res = await fetch(`${APP_URL}/api/frontdesk/batches?id=${id}`);
        const data = await res.json();
        if (data.success && data.data.length) {
            const b = data.data[0];
            document.getElementById('editBatchCourseSelect').value = b.course_id;
            document.getElementById('editBatchName').value = b.name;
            document.getElementById('editBatchShift').value = b.shift;
            document.getElementById('editBatchMax').value = b.max_strength;
            document.getElementById('editBatchStart').value = b.start_date;
            document.getElementById('editBatchEnd').value = b.end_date||'';
            document.getElementById('editBatchRoom').value = b.room||'';
            document.getElementById('editBatchStatus').value = b.status;
        }
    } catch(e) { console.error('Failed to load batch data',e); }
}

async function _submitBatchForm(e, method) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const btn = e.target.querySelector('button[type="submit"]'); const orig=btn.innerHTML;
    btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
    try {
        const res = await fetch(APP_URL+'/api/frontdesk/batches',{method,headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
        const result = await res.json();
        if (result.success) Swal.fire('Success',result.message,'success').then(()=>goNav('academic','batches'));
        else throw new Error(result.message);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
    finally { btn.disabled=false; btn.innerHTML=orig; }
}

window.deleteBatch = async function(id, name) {
    const r = await Swal.fire({title:'Delete Batch?',text:`Delete "${name}"? This cannot be undone.`,icon:'warning',showCancelButton:true,confirmButtonColor:'#e74c3c',confirmButtonText:'Yes, delete!'});
    if (!r.isConfirmed) return;
    try {
        const res = await fetch(APP_URL+'/api/frontdesk/batches',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
        const data = await res.json();
        if (data.success) Swal.fire('Deleted!',data.message,'success').then(()=>_loadBatches());
        else throw new Error(data.message);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
};

/* ══════════════ SHARED HELPERS ════════════════════════════ */
async function _populateCourses(selectId) {
    const sel = document.getElementById(selectId); if (!sel) return;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/courses');
        const data = await res.json();
        if (data.success) data.data.forEach(c => { const o=document.createElement('option'); o.value=c.id; o.textContent=`${c.name} (${c.code})`; sel.appendChild(o); });
    } catch(e) { console.error('Failed to load courses',e); }
}
