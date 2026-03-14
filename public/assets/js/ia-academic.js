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
        const res = await fetch(APP_URL + '/api/admin/courses');
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        if (!result.data.length) { c.innerHTML=`<div style="padding:60px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-book-open" style="font-size:3rem;margin-bottom:15px;"></i><p>No courses created yet.</p></div>`; return; }
        let html = `<div class="table-responsive"><table class="table"><thead><tr><th>Code</th><th>Course Name</th><th>Category</th><th>Fee</th><th>Batches</th><th>Students</th><th style="text-align:right">Actions</th></tr></thead><tbody>`;
        result.data.forEach(c2 => {
            html += `<tr>
                <td><span style="font-weight:700">${c2.code}</span></td>
                <td><div style="font-weight:600">${c2.name}</div></td>
                <td><span class="tag bg-b">${(c2.category_name || c2.category || 'General').toUpperCase()}</span></td>
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
                    <div class="form-group"><label class="form-label">Category *</label><select name="course_category_id" id="courseCategorySelect" class="form-control" required><option value="">Loading...</option></select></div>
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
    _populateCategories('courseCategorySelect');
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
                    <div class="form-group"><label class="form-label">Category *</label><select name="course_category_id" id="editCourseCategory" class="form-control" required><option value="">Loading...</option></select></div>
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
        const res = await fetch(`${APP_URL}/api/admin/courses?id=${id}`);
        const data = await res.json();
        if (data.success && data.data.length) {
            const c = data.data[0];
            document.getElementById('editCourseName').value = c.name;
            document.getElementById('editCourseCode').value = c.code;
            _populateCategories('editCourseCategory', c.course_category_id);
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
        const res = await fetch(APP_URL+'/api/admin/courses', {method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)});
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
        const res = await fetch(APP_URL+'/api/admin/courses',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
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
        const res = await fetch(APP_URL + '/api/admin/batches');
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
        const res = await fetch(`${APP_URL}/api/admin/batches?id=${id}`);
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
        const res = await fetch(APP_URL+'/api/admin/batches',{method,headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
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
        const res = await fetch(APP_URL+'/api/admin/batches',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
        const data = await res.json();
        if (data.success) Swal.fire('Deleted!',data.message,'success').then(()=>_loadBatches());
        else throw new Error(data.message);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
};

/* ══════════════ SHARED HELPERS ════════════════════════════ */
async function _populateCourses(selectId) {
    const sel = document.getElementById(selectId); if (!sel) return;
    try {
        const res = await fetch(APP_URL + '/api/admin/courses');
        const data = await res.json();
        if (data.success) data.data.forEach(c => { const o=document.createElement('option'); o.value=c.id; o.textContent=`${c.name} (${c.code})`; sel.appendChild(o); });
    } catch(e) { console.error('Failed to load courses',e); }
}

/* ══════════════ COURSE CATEGORIES ══════════════════════════ */
window.renderCourseCategoryList = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Course Categories</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-folder-tree"></i></div><div><div class="pg-title">Course Categories</div><div class="pg-sub">Manage dynamic categories for your programs</div></div></div>
            <div class="pg-acts"><button class="btn bt" onclick="openCategoryModal()"><i class="fa-solid fa-plus"></i> Add Category</button></div>
        </div>
        <div class="card" id="categoryListContainer"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading categories...</span></div></div>
    </div>
    
    <!-- Modal for Add/Edit Category -->
    <div id="categoryModal" class="modal-root">
        <div class="modal-card" style="max-width:500px;">
            <div class="modal-head">
                <h2 id="catModalTitle">Add New Category</h2>
                <button class="modal-close" onclick="closeCatModal()">&times;</button>
            </div>
            <form id="categoryForm">
                <input type="hidden" name="id" id="cat_id">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" id="cat_name" class="form-control" required placeholder="e.g. Web Development">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="cat_desc" class="form-control" rows="3" placeholder="Optional description..."></textarea>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn bs" onclick="closeCatModal()">Cancel</button>
                    <button type="submit" class="btn bt" id="catSaveBtn">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal-root { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 2000; visibility: hidden; opacity: 0; transition: 0.3s; }
        .modal-root.active { visibility: visible; opacity: 1; }
        .modal-card { background: #fff; width: 100%; border-radius: 20px; overflow: hidden; transform: translateY(20px); transition: 0.3s; }
        .modal-root.active .modal-card { transform: translateY(0); }
    </style>
    `;
    
    document.getElementById('categoryForm').onsubmit = _submitCategoryForm;
    await _loadCourseCategories();
};

async function _loadCourseCategories() {
    const c = document.getElementById('categoryListContainer'); if (!c) return;
    try {
        const res = await fetch(APP_URL + '/api/admin/course-categories');
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        
        let html = `<div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Slug</th><th>Description</th><th style="text-align:right">Actions</th></tr></thead><tbody>`;
        result.data.forEach(cat => {
            html += `<tr>
                <td><div style="font-weight:700">${cat.name}</div></td>
                <td><code>${cat.slug}</code></td>
                <td><span style="font-size:12px;color:var(--tl);">${cat.description || '-'}</span></td>
                <td style="text-align:right;">
                    <button class="btn-icon" onclick='openCategoryModal(${JSON.stringify(cat).replace(/'/g, "&#39;")})'><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-icon text-danger" onclick="deleteCategory(${cat.id}, '${cat.name.replace(/'/g,"\\'")}')"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;
        });
        html += `</tbody></table></div>`;
        c.innerHTML = html;
    } catch(e) { c.innerHTML = `<div class="pg-error">${e.message}</div>`; }
}

window.openCategoryModal = function(cat = null) {
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    form.reset();
    if (cat) {
        document.getElementById('cat_id').value = cat.id;
        document.getElementById('cat_name').value = cat.name;
        document.getElementById('cat_desc').value = cat.description || '';
        document.getElementById('catModalTitle').textContent = 'Edit Category';
    } else {
        document.getElementById('cat_id').value = '';
        document.getElementById('catModalTitle').textContent = 'Add New Category';
    }
    modal.classList.add('active');
};

window.closeCatModal = function() {
    document.getElementById('categoryModal').classList.remove('active');
};

async function _submitCategoryForm(e) {
    e.preventDefault();
    const btn = document.getElementById('catSaveBtn');
    const id = document.getElementById('cat_id').value;
    const formData = Object.fromEntries(new FormData(e.target).entries());
    
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
    const method = id ? 'PUT' : 'POST';
    
    try {
        const res = await fetch(APP_URL + '/api/admin/course-categories', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        const result = await res.json();
        if (result.success) {
            Swal.fire('Success', result.message, 'success');
            closeCatModal();
            _loadCourseCategories();
        } else throw new Error(result.message);
    } catch(err) { Swal.fire('Error', err.message, 'error'); }
    finally { btn.disabled = false; btn.innerHTML = 'Save Category'; }
}

window.deleteCategory = async function(id, name) {
    const r = await Swal.fire({ title: 'Delete Category?', text: `Delete "${name}"? Only categories not linked to courses can be deleted.`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74c3c' });
    if (!r.isConfirmed) return;
    
    try {
        const res = await fetch(APP_URL + '/api/admin/course-categories', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.success) {
            Swal.fire('Deleted!', result.message, 'success');
            _loadCourseCategories();
        } else throw new Error(result.message);
    } catch(err) { Swal.fire('Error', err.message, 'error'); }
};

async function _populateCategories(selectId, selectedId = null) {
    const sel = document.getElementById(selectId); if (!sel) return;
    try {
        const res = await fetch(APP_URL + '/api/admin/course-categories');
        const data = await res.json();
        if (data.success) {
            sel.innerHTML = '<option value="">Select Category</option>';
            data.data.forEach(c => {
                const o = document.createElement('option');
                o.value = c.id;
                o.textContent = c.name;
                if (selectedId && c.id == selectedId) o.selected = true;
                sel.appendChild(o);
            });
        }
    } catch(e) { console.error('Failed to load categories', e); }
}
