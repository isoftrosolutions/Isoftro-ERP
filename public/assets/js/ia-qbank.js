/**
 * Hamro ERP — ia-qbank.js
 * Question Bank Management based on Study Materials infrastructure
 */

window.renderQuestionBank = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> 
            <a href="#" onclick="goNav('exams','schedule')">Exams</a> <span class="bc-sep">&rsaquo;</span>
            <span class="bc-cur">Question Bank</span>
        </div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:var(--purple);"><i class="fa-solid fa-database"></i></div>
                <div>
                    <div class="pg-title">Shared Question Bank</div>
                    <div class="pg-sub">Central repository for all exam questions and assessment documents</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="openAddQuestionModal()"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Question</button>
            </div>
        </div>

        <div class="card mb" style="padding:15px;">
            <div style="display:flex;gap:15px;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;"><input type="text" id="qbSearch" class="form-control" placeholder="Search questions..." onkeyup="debounce(_loadQuestions, 500)"></div>
                <select id="qbCategory" class="form-control" style="width:180px;" onchange="_loadQuestions()"><option value="">All Categories</option></select>
                <select id="qbType" class="form-control" style="width:140px;" onchange="_loadQuestions()">
                    <option value="">All Types</option>
                    <option value="file">Documents</option>
                    <option value="image">Images</option>
                    <option value="video">Videos</option>
                </select>
                <button class="btn bs" onclick="_loadQuestions()"><i class="fa-solid fa-filter"></i> Apply</button>
            </div>
        </div>

        <div id="questionsListContainer" class="card" style="padding:0;overflow:hidden;">
            <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading questions...</span></div>
        </div>
        
        <div id="questionsPagination" style="margin-top:20px;display:flex;justify-content:center;"></div>
    </div>`;

    await _loadQBCategories();
    await _loadQuestions();
};

async function _loadQBCategories() {
    const sel = document.getElementById('qbCategory');
    if(!sel) return;
    try {
        const res = await fetch(APP_URL + '/api/admin/lms?action=categories');
        const r = await res.json();
        if(r.success) {
            r.data.forEach(c => {
                const o = document.createElement('option');
                o.value = c.id; o.textContent = c.name;
                sel.appendChild(o);
            });
        }
    } catch(e) {}
}

async function _loadQuestions(page = 1) {
    const c = document.getElementById('questionsListContainer');
    if(!c) return;

    const search = document.getElementById('qbSearch').value;
    const cat = document.getElementById('qbCategory').value;
    const type = document.getElementById('qbType').value;

    try {
        const query = new URLSearchParams({ 
            action: 'materials', 
            is_qbank: 1,
            page, 
            search, 
            category_id: cat, 
            content_type: type 
        });
        const res = await fetch(APP_URL + '/api/admin/lms?' + query.toString());
        const result = await res.json();
        
        if (!result.success) throw new Error(result.message);
        
        const mats = result.data;
        if (!mats.length) {
            c.innerHTML = '<div style="padding:100px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-database" style="font-size:4rem;margin-bottom:20px;opacity:0.3;"></i><p>No questions found in the bank.</p></div>';
            return;
        }

        let html = `<div class="table-responsive"><table class="table">
            <thead><tr><th>Question / Document</th><th>Subject / Category</th><th>Uploaded By</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>`;
        
        mats.forEach(m => {
            html += `<tr>
                <td style="width:45%;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="font-size:1.5rem;color:var(--purple);">${_getMaterialIcon(m.content_type)}</div>
                        <div>
                            <div style="font-weight:700;color:var(--teal-d);cursor:pointer;" onclick="viewMaterial(${m.id})">${m.title}</div>
                            <div style="font-size:11px;color:#94a3b8;">${m.file_extension ? m.file_extension.toUpperCase() : 'LINK'} &bull; ${(m.file_size / 1024 / 1024).toFixed(2)} MB</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-weight:600;font-size:13px;">${m.subject_name || 'N/A'}</div>
                    <div style="font-size:11px;color:#94a3b8;">${m.category_name || 'General'}</div>
                </td>
                <td>
                    <div style="font-size:12px;">${m.created_by_name || 'System'}</div>
                    <div style="font-size:10px;color:#94a3b8;">${new Date(m.created_at).toLocaleDateString()}</div>
                </td>
                <td style="text-align:right;">
                    <button class="btn-icon" title="View" onclick="viewMaterial(${m.id})"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn-icon" title="Download" onclick="window.open('${APP_URL}/api/student/study-materials?action=download&id=${m.id}')"><i class="fa-solid fa-download"></i></button>
                    <button class="btn-icon text-red" title="Delete" onclick="deleteQuestion(${m.id})"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;
        });
        html += `</tbody></table></div>`;
        c.innerHTML = html;

        _renderPagination(result.meta, '_loadQuestions', 'questionsPagination');

    } catch(e) {
        c.innerHTML = `<div style="padding:40px;text-align:center;color:var(--red);">${e.message}</div>`;
    }
}

window.openAddQuestionModal = async function() {
    const { value: formValues } = await Swal.fire({
        title: 'Upload to Question Bank',
        width: '700px',
        html: `
            <form id="swalAddQuestionForm" class="swal-form" style="text-align:left;">
                <div class="form-group mb-3">
                    <label class="form-label">Question Title / Description *</label>
                    <input type="text" id="swal_q_title" class="form-control" required placeholder="e.g. Science Final Term 2080">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="form-group mb-3">
                        <label class="form-label">Subject</label>
                        <select id="swal_q_subject" class="form-control"><option value="">Select Subject</option></select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Category</label>
                        <select id="swal_q_cat" class="form-control"><option value="">Select Category</option></select>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label class="form-label">Content Type</label>
                    <select id="swal_q_type" class="form-control" onchange="toggleContentInputs(this.value)">
                        <option value="file">File Upload (PDF, Word, etc.)</option>
                        <option value="image">Image (Snatched Photo)</option>
                        <option value="video">Reference Video</option>
                        <option value="link">Online Portal Link</option>
                    </select>
                </div>

                <div id="fileInputWrap" class="form-group mb-3">
                    <label class="form-label">Select File *</label>
                    <input type="file" id="swal_q_file" class="form-control">
                </div>
                
                <div id="urlInputWrap" class="form-group mb-3" style="display:none;">
                    <label class="form-label">External URL *</label>
                    <input type="url" id="swal_q_url" class="form-control" placeholder="https://...">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Admin Notes</label>
                    <textarea id="swal_q_desc" class="form-control" rows="2" placeholder="Teacher instructions or difficulty level..."></textarea>
                </div>
            </form>
        `,
        didOpen: () => {
            _populateQBModalDropdowns();
        },
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Save to Bank',
        confirmButtonColor: 'var(--purple)',
        preConfirm: () => {
            return _validateAndGetQBForm();
        }
    });

    if (formValues) {
        _submitQuestion(formValues);
    }
};

async function _populateQBModalDropdowns() {
    // Subjects
    const subSel = document.getElementById('swal_q_subject');
    const resS = await fetch(APP_URL + '/api/admin/subjects');
    const rS = await resS.json();
    if(rS.success) rS.data.forEach(s => { const o=document.createElement('option'); o.value=s.id; o.textContent=s.name; subSel.appendChild(o); });

    // Categories
    const catSel = document.getElementById('swal_q_cat');
    const resC = await fetch(APP_URL + '/api/admin/lms?action=categories');
    const rC = await resC.json();
    if(rC.success) rC.data.forEach(c => { const o=document.createElement('option'); o.value=c.id; o.textContent=c.name; catSel.appendChild(o); });
}

function _validateAndGetQBForm() {
    const title = document.getElementById('swal_q_title').value;
    const type = document.getElementById('swal_q_type').value;
    if (!title) { Swal.showValidationMessage('Title is required'); return false; }
    
    const formData = new FormData();
    formData.append('title', title);
    formData.append('description', document.getElementById('swal_q_desc').value);
    formData.append('category_id', document.getElementById('swal_q_cat').value);
    formData.append('subject_id', document.getElementById('swal_q_subject').value);
    formData.append('content_type', type);
    formData.append('is_qbank', 1);
    formData.append('status', 'active');
    formData.append('access_type', 'public'); // Shared across teachers/students

    if (type === 'file' || type === 'image') {
        const fileInput = document.getElementById('swal_q_file');
        if (!fileInput.files.length) { Swal.showValidationMessage('Please select a file'); return false; }
        formData.append('file', fileInput.files[0]);
    } else {
        const url = document.getElementById('swal_q_url').value;
        if (!url) { Swal.showValidationMessage('URL is required'); return false; }
        formData.append('external_url', url);
    }

    return formData;
}

async function _submitQuestion(formData) {
    Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    try {
        formData.append('action', 'create'); // Calls the updated create action in study_materials.php
        const res = await fetch(APP_URL + '/api/admin/lms', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            Swal.fire('Success!', 'Question added to bank', 'success');
            _loadQuestions();
        } else throw new Error(result.message);
    } catch(e) { Swal.fire('Error', e.message, 'error'); }
}

window.deleteQuestion = function(id) {
    Swal.fire({
        title: 'Delete Question?',
        text: "This will remove it from the Question Bank.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete it'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await fetch(APP_URL + '/api/admin/lms?action=delete&id=' + id, { method: 'DELETE' });
                const r = await res.json();
                if(r.success) {
                    Swal.fire('Deleted!', 'Question removed.', 'success');
                    _loadQuestions();
                } else throw new Error(r.message);
            } catch(e) { Swal.fire('Error', e.message, 'error'); }
        }
    });
}

// Extra helper for pagination since ia-study-materials.js might not be loaded
function _renderPagination(meta, funcName, containerId) {
    const p = document.getElementById(containerId);
    if (!p || !meta || meta.total_pages <= 1) { if(p) p.innerHTML = ''; return; }
    
    let html = '';
    for (let i = 1; i <= meta.total_pages; i++) {
        const active = i === meta.page ? 'bs' : 'bt-sm';
        html += `<button class="btn ${active}" style="margin:2px;" onclick="${funcName}(${i})">${i}</button>`;
    }
    p.innerHTML = html;
}
