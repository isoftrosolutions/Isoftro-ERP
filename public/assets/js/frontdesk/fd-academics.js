/**
 * Hamro ERP — ia-academics.js
 * Subjects & Allocations: List, Add, Edit, Allocation
 */

/* ══════════════ SUBJECT LIST ═══════════════════════════════ */
window.renderSubjectList = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">Subjects</span>
        </div>

        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #fff;">
                    <i class="fa-solid fa-book"></i>
                </div>
                <div>
                    <div class="pg-title" style="font-size: clamp(1.2rem, 3vw, 1.5rem);">Subject Blueprint</div>
                    <div class="pg-sub">Define core curriculum and academic subjects</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="goNav('academic','subjects',{action:'add'})">
                    <i class="fa-solid fa-plus"></i> <span class="d-none d-sm-inline">New Subject</span>
                </button>
            </div>
        </div>

        <div class="premium-tw table-responsive" id="subjectListContainer">
            <div class="pg-loading">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <span>Loading curriculum subjects...</span>
            </div>
        </div>
    </div>`;
    await _loadSubjects();
};

async function _loadSubjects() {
    const c = document.getElementById('subjectListContainer'); if (!c) return;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/subjects');
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        if (!result.data.length) { 
            c.innerHTML=`<div class="empty-state-premium" style="margin: 40px 0;">
                <div class="empty-ico"><i class="fa-solid fa-book-open"></i></div>
                <h4>No Subjects Defined</h4>
                <p>Curriculum is empty. Start by adding a core subject.</p>
            </div>`; 
            return; 
        }
        
        let html = `<table class="premium-student-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Subject Code</th>
                    <th style="width: 30%;">Academic Subject</th>
                    <th style="width: 30%;">Description</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 10%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>`;
            
        result.data.forEach(s => {
            const isActive = s.status === 'active';
            html += `<tr>
                <td><span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #b45309; font-weight: 700; font-size: 11px;">${s.code}</span></td>
                <td>
                    <div class="std-card">
                        <div class="std-img initials" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                            <i class="fa-solid fa-scroll" style="font-size: 14px;"></i>
                        </div>
                        <div class="std-info">
                            <div class="name">${s.name}</div>
                            <div class="id">Academic Discipline</div>
                        </div>
                    </div>
                </td>
                <td><div style="font-size: 13px; color: #64748b; line-height: 1.4;">${s.description || 'No detailed description.'}</div></td>
                <td>
                    <span class="badge" style="background: ${isActive?'#ecfdf5':'#fff1f2'}; color: ${isActive?'#059669':'#e11d48'}; font-weight: 700; font-size: 10px;">
                        ${(s.status || 'INACTIVE').toUpperCase()}
                    </span>
                </td>
                <td style="text-align:right">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn-icon-p" title="Edit" onclick="goNav('academic','subjects',{id:${s.id}})">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn-icon-p" style="color: #e11d48; border-color: #fecdd3;" title="Delete" onclick="deleteSubject(${s.id},'${s.name.replace(/'/g,"\\''")}')">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });
        html += `</tbody></table>`;
        c.innerHTML = html;
    } catch(e) { c.innerHTML=`<div style="padding:40px;text-align:center;color:var(--red);"><i class="fa-solid fa-circle-exclamation" style="font-size: 2rem; margin-bottom: 10px;"></i><p>${e.message}</p></div>`; }
}

window.renderAddSubjectForm = function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('academic','subjects')">Subjects</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">New Subject</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-plus-circle"></i></div><div><div class="pg-title">Add Subject</div><div class="pg-sub">Create a new subject</div></div></div></div>
        <div class="card fu" style="max-width:600px;margin:0 auto;padding:30px;">
            <form id="subjectAddForm">
                <div class="form-group"><label class="form-label">Subject Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. Mathematics"></div>
                <div class="form-group"><label class="form-label">Subject Code *</label><input type="text" name="code" class="form-control" required placeholder="e.g. MATH-101"></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Brief subject description..."></textarea></div>
                <div style="margin-top:30px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('academic','subjects')">Cancel</button>
                    <button type="submit" class="btn bt">Save Subject</button>
                </div>
            </form>
        </div>
    </div>`;
    document.getElementById('subjectAddForm').onsubmit = e => _submitSubjectForm(e,'POST');
};

window.renderEditSubjectForm = async function(id) {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('academic','subjects')">Subjects</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Edit Subject</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-pen-to-square"></i></div><div><div class="pg-title">Edit Subject</div><div class="pg-sub">Update subject details</div></div></div></div>
        <div class="card fu" style="max-width:600px;margin:0 auto;padding:30px;">
            <form id="subjectEditForm">
                <input type="hidden" name="id" value="${id}">
                <div class="form-group"><label class="form-label">Subject Name *</label><input type="text" name="name" id="editSubName" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Subject Code *</label><input type="text" name="code" id="editSubCode" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="editSubDesc" class="form-control" rows="3"></textarea></div>
                <div class="form-group"><label class="form-label">Status</label><select name="status" id="editSubStatus" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                <div style="margin-top:30px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="btn bs" onclick="goNav('academic','subjects')">Cancel</button>
                    <button type="submit" class="btn bt">Update Subject</button>
                </div>
            </form>
        </div>
    </div>`;
    await _loadSubjectData(id);
    document.getElementById('subjectEditForm').onsubmit = e => _submitSubjectForm(e,'PUT');
};

async function _loadSubjectData(id) {
    try {
        const res = await fetch(`${APP_URL}/api/frontdesk/subjects?id=${id}`);
        const data = await res.json();
        if (data.success && data.data.length) {
            const s = data.data[0];
            document.getElementById('editSubName').value = s.name;
            document.getElementById('editSubCode').value = s.code;
            document.getElementById('editSubDesc').value = s.description||'';
            document.getElementById('editSubStatus').value = s.status;
        }
    } catch(e) { Swal.fire('Error','Failed to fetch subject details','error'); }
}

async function _submitSubjectForm(e, method) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const btn = e.target.querySelector('button[type="submit"]'); const orig=btn.innerHTML;
    btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
    try {
        const res = await fetch(APP_URL+'/api/frontdesk/subjects', {method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)});
        const result = await res.json();
        if (result.success) Swal.fire('Success',result.message,'success').then(()=>goNav('academic','subjects'));
        else throw new Error(result.message);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
    finally { btn.disabled=false; btn.innerHTML=orig; }
}

window.deleteSubject = async function(id, name) {
    const r = await Swal.fire({title:'Delete Subject?',text:`Delete "${name}"?`,icon:'warning',showCancelButton:true,confirmButtonColor:'#e74c3c',confirmButtonText:'Yes, delete!'});
    if (!r.isConfirmed) return;
    try {
        const res = await fetch(APP_URL+'/api/frontdesk/subjects',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
        const data = await res.json();
        if (data.success) Swal.fire('Deleted!',data.message,'success').then(()=>_loadSubjects());
        else throw new Error(data.message);
    } catch(err) { Swal.fire('Error',err.message,'error'); }
};

/* ══════════════ SUBJECT ALLOCATION ══════════════════════════ */
window.renderSubjectAllocation = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">Faculty Allocation</span>
        </div>

        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: linear-gradient(135deg, #6366f1, #a855f7); color: #fff;">
                    <i class="fa-solid fa-users-rectangle"></i>
                </div>
                <div>
                    <div class="pg-title" style="font-size: clamp(1.2rem, 3vw, 1.5rem);">Faculty Allocation</div>
                    <div class="pg-sub">Assign subject leads and teachers to active batches</div>
                </div>
            </div>
        </div>

        <div class="toolbar" style="padding: clamp(15px, 2vw, 20px); margin-bottom: 25px; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.4); border-radius: 16px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label class="form-label" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Select Target Batch</label>
                <select id="allocBatchSelect" class="form-control" style="border-radius: 12px; height: 48px; font-weight: 600;" onchange="_loadBatchAllocations(this.value)">
                    <option value="">Choose Batch...</option>
                </select>
            </div>
            <button class="btn bt" onclick="_openAllocModal()" style="padding: 14px 24px; border-radius: 12px; font-weight: 700; height: 48px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-plus-circle"></i> <span>Assign Teacher</span>
            </button>
        </div>

        <div class="premium-tw table-responsive" id="allocationContainer">
            <div class="empty-state-premium" style="margin: 40px 0;">
                <div class="empty-ico"><i class="fa-solid fa-layer-group"></i></div>
                <h4>Select a Batch</h4>
                <p>Choose an academic batch above to manage its subject-teacher assignments.</p>
            </div>
        </div>
    </div>`;
    await _populateBatchesForAlloc();
};

async function _populateBatchesForAlloc() {
    const sel = document.getElementById('allocBatchSelect');
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/batches');
        const result = await res.json();
        if (result.success) result.data.forEach(b => {
            const o = document.createElement('option'); o.value=b.id; o.textContent=`${b.name} (${b.course_name})`; sel.appendChild(o);
        });
    } catch(e) { console.error('Failed to load batches',e); }
}

window._loadBatchAllocations = async function(batchId) {
    const c = document.getElementById('allocationContainer'); 
    if (!batchId) { 
        c.innerHTML='<div class="empty-state-premium" style="margin: 40px 0;"><div class="empty-ico"><i class="fa-solid fa-layer-group"></i></div><h4>Select a Batch</h4><p>Choose an academic batch above to manage its subject-teacher assignments.</p></div>'; 
        return; 
    }
    c.innerHTML = '<div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading faculty assignments...</span></div>';
    try {
        const res = await fetch(`${APP_URL}/api/frontdesk/subject_allocation?batch_id=${batchId}`);
        const result = await res.json(); if (!result.success) throw new Error(result.message);
        if (!result.data.length) { 
            c.innerHTML=`<div class="empty-state-premium" style="margin: 40px 0;">
                <div class="empty-ico"><i class="fa-solid fa-user-slash"></i></div>
                <h4>No Allocations</h4>
                <p>No subjects have been allocated to this batch yet. Use the "Assign Teacher" button to begin.</p>
            </div>`; 
            return; 
        }
        
        let html = `<table class="premium-student-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Academic Subject</th>
                    <th style="width: 45%;">Assigned Faculty Member</th>
                    <th style="width: 20%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>`;
            
        result.data.forEach(a => {
            const hasTeacher = !!a.teacher_name;
            const teacherInitials = hasTeacher ? a.teacher_name.charAt(0).toUpperCase() : '?';
            
            html += `<tr>
                <td>
                    <div style="font-weight: 700; color: #1e293b; font-size: 14px;">${a.subject_name}</div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">CODE: ${a.subject_code}</div>
                </td>
                <td>
                    <div class="std-card">
                        <div class="std-img initials" style="background: ${hasTeacher ? 'linear-gradient(135deg, #6366f1, #a855f7)' : '#f1f5f9; color: #94a3b8'};">
                            ${teacherInitials}
                        </div>
                        <div class="std-info">
                            <div class="name" style="${!hasTeacher ? 'color: #e11d48; font-style: italic;' : ''}">${a.teacher_name || 'No Teacher Assigned'}</div>
                            <div class="id">${hasTeacher ? 'Faculty Lead' : 'Awaiting Assignment'}</div>
                        </div>
                    </div>
                </td>
                <td style="text-align:right">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn-icon-p" title="Change Assignment" onclick="_editAllocation(${a.id}, '${a.subject_name.replace(/'/g,"\\'")}', ${a.teacher_id})">
                            <i class="fa-solid fa-user-gear"></i>
                        </button>
                        <button class="btn-icon-p" style="color: #e11d48; border-color: #fecdd3;" title="Remove Allocation" onclick="_removeAllocation(${a.id})">
                            <i class="fa-solid fa-user-minus"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });
        html += `</tbody></table>`;
        c.innerHTML = html;
    } catch(e) { c.innerHTML=`<div style="padding:40px;text-align:center;color:var(--red);"><i class="fa-solid fa-circle-exclamation" style="font-size: 2rem; margin-bottom: 10px;"></i><p>${e.message}</p></div>`; }
};

window._openAllocModal = async function() {
    const batchId = document.getElementById('allocBatchSelect').value;
    if (!batchId) { Swal.fire('Info','Please select a batch first','info'); return; }

    const { value: formValues } = await Swal.fire({
        title: 'New Subject Allocation',
        html: `
            <div style="text-align:left;">
                <div class="form-group"><label class="form-label">Subject</label><select id="swalSubjects" class="form-control"><option value="">Loading...</option></select></div>
                <div class="form-group"><label class="form-label">Teacher</label><select id="swalTeachers" class="form-control"><option value="">Loading...</option></select></div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        didOpen: async () => {
            // Use Swal.getPopup() to scope queries to the modal — avoids null if document.getElementById misses
            const popup = Swal.getPopup();

            // Populate Subjects
            const sRes = await fetch(APP_URL + '/api/frontdesk/subjects');
            const sData = await sRes.json();
            const sSel = popup ? popup.querySelector('#swalSubjects') : document.getElementById('swalSubjects');
            if (sSel) {
                sSel.innerHTML = '<option value="">Select Subject</option>';
                if (sData.success) sData.data.forEach(s => { const o=document.createElement('option'); o.value=s.id; o.textContent=`${s.name} (${s.code})`; sSel.appendChild(o); });
            }

            // Populate Teachers
            const tRes = await fetch(APP_URL + '/api/frontdesk/staff?role=teacher');
            const tData = await tRes.json();
            const tSel = popup ? popup.querySelector('#swalTeachers') : document.getElementById('swalTeachers');
            if (tSel) {
                tSel.innerHTML = '<option value="">Select Teacher</option>';
                if (tData.success) tData.data.forEach(t => { const o=document.createElement('option'); o.value=t.id; o.textContent=t.full_name||t.name; tSel.appendChild(o); });
            }
        },
        preConfirm: () => {
            return {
                subject_id: document.getElementById('swalSubjects').value,
                teacher_id: document.getElementById('swalTeachers').value,
                batch_id: batchId
            };
        }
    });

    if (formValues) {
        if (!formValues.subject_id || !formValues.teacher_id) { Swal.fire('Error','Both subject and teacher are required','error'); return; }
        try {
            const res = await fetch(APP_URL + '/api/frontdesk/subject_allocation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formValues)
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire('Success', result.message, 'success');
                _loadBatchAllocations(batchId);
            } else throw new Error(result.message);
        } catch(e) { Swal.fire('Error', e.message, 'error'); }
    }
};

window._editAllocation = async function(id, subjectName, currentTeacherId) {
    const batchId = document.getElementById('allocBatchSelect').value;
    
    const { value: teacherId } = await Swal.fire({
        title: 'Edit Teacher Assignment',
        html: `
            <div style="text-align:left;">
                <p style="margin-bottom:15px">Reassigning teacher for: <strong>${subjectName}</strong></p>
                <div class="form-group"><label class="form-label">Select Teacher</label><select id="swalEditTeacher" class="form-control"><option value="">Loading...</option></select></div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        didOpen: async () => {
            const popup = Swal.getPopup();
            const tRes = await fetch(APP_URL + '/api/frontdesk/staff?role=teacher');
            const tData = await tRes.json();
            const tSel = popup.querySelector('#swalEditTeacher');
            if (tSel && tData.success) {
                tSel.innerHTML = '<option value="">Select Teacher</option>';
                tData.data.forEach(t => { 
                    const o=document.createElement('option'); 
                    o.value=t.id; 
                    o.textContent=t.full_name||t.name; 
                    if(t.id == currentTeacherId) o.selected = true;
                    tSel.appendChild(o); 
                });
            }
        },
        preConfirm: () => {
            return document.getElementById('swalEditTeacher').value;
        }
    });

    if (teacherId) {
        try {
            const res = await fetch(APP_URL + '/api/frontdesk/subject_allocation', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, teacher_id: teacherId })
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire('Updated', result.message, 'success');
                _loadBatchAllocations(batchId);
            } else throw new Error(result.message);
        } catch(e) { Swal.fire('Error', e.message, 'error'); }
    }
};

window._removeAllocation = async function(id) {
    const r = await Swal.fire({title:'Remove Allocation?',text:'Assigned teacher will be removed from this subject in this batch.',icon:'warning',showCancelButton:true});
    if (!r.isConfirmed) return;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/subject_allocation', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({id})
        });
        const result = await res.json();
        if (result.success) {
            Swal.fire('Removed', result.message, 'success');
            _loadBatchAllocations(document.getElementById('allocBatchSelect').value);
        } else throw new Error(result.message);
    } catch(e) { Swal.fire('Error', e.message, 'error'); }
};
