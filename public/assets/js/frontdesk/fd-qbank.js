/**
 * Hamro ERP — fd-qbank.js
 * Question Bank Management for Front Desk / Teachers
 * Based on Study Materials infrastructure
 */

window.renderQuestionBank = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('dashboard')"><i class="fa fa-home"></i></a> <span class="bc-sep">/</span> 
            <a href="#" onclick="goNav('exams','schedule')">Assessments</a> <span class="bc-sep">/</span>
            <span class="bc-cur">Question Bank</span>
        </div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: linear-gradient(135deg, #8141a5, #a855f7); color: #fff;">
                    <i class="fa-solid fa-database"></i>
                </div>
                <div>
                    <div class="pg-title" style="font-size: clamp(1.2rem, 3vw, 1.5rem);">Shared Question Bank</div>
                    <div class="pg-sub">Central repository for all exam questions and assessment documents</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="window.openAddQuestionModal()"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Question</button>
            </div>
        </div>

        <div class="toolbar" style="padding: clamp(10px, 2vw, 15px); margin-bottom: 20px; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.4); border-radius: 16px;">
            <div style="display:flex;gap:15px;flex-wrap:wrap;width:100%;">
                <div style="flex:1;min-width:200px;position:relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;"></i>
                    <input type="text" id="qbSearch" style="width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 13px; outline: none;" placeholder="Search questions..." onkeyup="debounce(window._loadQuestions, 500)">
                </div>
                <select id="qbCategory" style="padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 13px; outline: none; background: #fff; min-width: 150px;" onchange="window._loadQuestions()"><option value="">All Categories</option></select>
                <select id="qbType" style="padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 13px; outline: none; background: #fff; min-width: 140px;" onchange="window._loadQuestions()">
                    <option value="">All Types</option>
                    <option value="file">Documents</option>
                    <option value="image">Images</option>
                    <option value="video">Videos</option>
                    <option value="link">Links</option>
                </select>
                <button class="btn bs" onclick="window._loadQuestions()"><i class="fa-solid fa-filter"></i> Apply</button>
            </div>
        </div>

        <div id="questionsListContainer" class="premium-tw table-responsive" style="padding:0;overflow:hidden; border-radius:16px;">
            <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Retrieving questions...</span></div>
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
        const res = await fetch(APP_URL + '/api/frontdesk/lms?action=categories');
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

window._loadQuestions = async function(page = 1) {
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
        const res = await fetch(APP_URL + '/api/frontdesk/lms?' + query.toString());
        const result = await res.json();
        
        if (!result.success) throw new Error(result.message);
        
        const mats = result.data;
        if (!mats.length) {
            c.innerHTML = `
            <div class="empty-state-premium" style="margin: 40px 0;">
                <div class="empty-ico"><i class="fa-solid fa-database"></i></div>
                <h4>No Questions Found</h4>
                <p>The shared question bank is currently empty.</p>
                <button class="btn bt" onclick="window.openAddQuestionModal()()"><i class="fa-solid fa-plus"></i> Upload First Question</button>
            </div>`;
            return;
        }

        let html = `<table class="premium-student-table">
            <thead><tr><th>Question / Document</th><th>Subject / Category</th><th>Details</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>`;
        
        mats.forEach(m => {
            const size = m.file_size ? (m.file_size / 1024 / 1024).toFixed(2) + ' MB' : 'URL';
            html += `<tr>
                <td style="width:45%;">
                    <div class="std-card">
                        <div class="std-img initials" style="background: #f1f5f9; color: #64748b;">
                            ${_getMaterialIcon(m.content_type)}
                        </div>
                        <div class="std-info">
                            <div class="name" style="cursor:pointer;" onclick="viewMaterial(${m.id})">${m.title}</div>
                            <div class="id">${m.file_extension ? m.file_extension.toUpperCase() : 'PORTAL'} &bull; ${size}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-weight:700; color: #1e293b; font-size:13px;">${m.subject_name || 'Generic'}</div>
                    <div style="font-size:11px;color:#94a3b8;">${m.category_name || 'Uncategorized'}</div>
                </td>
                <td>
                    <div style="font-size:12px; font-weight:600;">${m.created_by_name || 'Faculty'}</div>
                    <div style="font-size:10px;color:#94a3b8;">${new Date(m.created_at).toLocaleDateString('en-GB', {day:'2-digit', month:'short'})}</div>
                </td>
                <td style="text-align:right;">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn-icon-p" title="View" onclick="viewMaterial(${m.id})"><i class="fa-solid fa-eye"></i></button>
                        <button class="btn-icon-p" title="Download" onclick="window.location.href='${APP_URL}/api/student/study-materials?action=download&id=${m.id}'"><i class="fa-solid fa-download"></i></button>
                        <button class="btn-icon-p" style="color:#e11d48;" title="Delete" onclick="window.deleteQuestion(${m.id})"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </td>
            </tr>`;
        });
        html += `</tbody></table>`;
        c.innerHTML = html;

        _renderQBPage(result.meta, 'window._loadQuestions', 'questionsPagination');

    } catch(e) {
        c.innerHTML = `<div style="padding:40px;text-align:center;color:var(--red);">${e.message}</div>`;
    }
}

window.openAddQuestionModal = async function() {
    const { value: formValues } = await Swal.fire({
        title: 'Upload to Question Bank',
        width: '700px',
        html: `
            <form id="swalAddQuestionForm" class="swal-form" style="text-align:left; font-family: inherit;">
                <div class="form-group mb-3">
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Question Title / Set Name *</label>
                    <input type="text" id="swal_q_title" class="form-control" style="border-radius:10px; padding:10px 15px;" required placeholder="e.g. Science Final Term 2080">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="form-group mb-3">
                        <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Subject</label>
                        <select id="swal_q_subject" class="form-control" style="border-radius:10px; height:45px;"><option value="">Select Subject</option></select>
                    </div>
                    <div class="form-group mb-3">
                        <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Category</label>
                        <select id="swal_q_cat" class="form-control" style="border-radius:10px; height:45px;"><option value="">Select Category</option></select>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Content Format</label>
                    <select id="swal_q_type" class="form-control" style="border-radius:10px; height:45px;" onchange="toggleContentInputs(this.value)">
                        <option value="file">File Upload (PDF, Word, etc.)</option>
                        <option value="image">Image / Screenshot</option>
                        <option value="video">Reference Video Link</option>
                        <option value="link">External Resource URL</option>
                    </select>
                </div>

                <div id="fileInputWrap" class="form-group mb-3">
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Select File *</label>
                    <input type="file" id="swal_q_file" class="form-control" style="border-radius:10px; padding:8px;">
                </div>
                
                <div id="urlInputWrap" class="form-group mb-3" style="display:none;">
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase;">External URL *</label>
                    <input type="url" id="swal_q_url" class="form-control" style="border-radius:10px; padding:10px 15px;" placeholder="https://...">
                </div>

                <div class="form-group mb-3">
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Remarks / Context</label>
                    <textarea id="swal_q_desc" class="form-control" rows="2" style="border-radius:10px;" placeholder="Teacher instructions or difficulty level..."></textarea>
                </div>
            </form>
        `,
        didOpen: () => {
            _populateQBModalDropdowns();
        },
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Secure in Bank',
        confirmButtonColor: '#8141a5',
        preConfirm: () => {
            return _validateAndGetQBForm();
        }
    });

    if (formValues) {
        _submitQuestion(formValues);
    }
};

async function _populateQBModalDropdowns() {
    try {
        // Subjects
        const subSel = document.getElementById('swal_q_subject');
        const resS = await fetch(APP_URL + '/api/frontdesk/subjects');
        const rS = await resS.json();
        if(rS.success) rS.data.forEach(s => { const o=document.createElement('option'); o.value=s.id; o.textContent=s.name; subSel.appendChild(o); });

        // Categories
        const catSel = document.getElementById('swal_q_cat');
        const resC = await fetch(APP_URL + '/api/frontdesk/lms?action=categories');
        const rC = await resC.json();
        if(rC.success) rC.data.forEach(c => { const o=document.createElement('option'); o.value=c.id; o.textContent=c.name; catSel.appendChild(o); });
    } catch(e) {}
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
    formData.append('access_type', 'public');

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
        formData.append('action', 'create_material'); 
        const res = await fetch(APP_URL + '/api/frontdesk/lms', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            Swal.fire({ icon: 'success', title: 'Asset Secured', text: 'Question added to bank successfully', timer: 1500 });
            window._loadQuestions();
        } else throw new Error(result.message);
    } catch(e) { Swal.fire('Error', e.message, 'error'); }
}

window.deleteQuestion = function(id) {
    Swal.fire({
        title: 'Remove Asset?',
        text: "This removal is permanent from the shared repository.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Yes, Delete Permanently'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await fetch(`${APP_URL}/api/frontdesk/lms?action=delete_material&id=${id}`, { method: 'GET' });
                const r = await res.json();
                if(r.success) {
                    Swal.fire('Deleted!', 'Question removed.', 'success');
                    window._loadQuestions();
                } else throw new Error(r.message);
            } catch(e) { Swal.fire('Error', e.message, 'error'); }
        }
    });
}

function _renderQBPage(meta, funcName, containerId) {
    const p = document.getElementById(containerId);
    if (!p || !meta || meta.total_pages <= 1) { if(p) p.innerHTML = ''; return; }
    
    let html = '';
    for (let i = 1; i <= meta.total_pages; i++) {
        const active = i === meta.page ? 'btn-primary' : 'btn-outline-primary';
        html += `<button class="btn ${active}" style="margin:2px; padding: 5px 12px; font-size: 12px; border-radius: 8px;" onclick="${funcName}(${i})">${i}</button>`;
    }
    p.innerHTML = html;
}

// Utility for content type icons
function _getMaterialIcon(type) {
    switch(type) {
        case 'file': return '<i class="fa-solid fa-file-pdf"></i>';
        case 'image': return '<i class="fa-solid fa-file-image"></i>';
        case 'video': return '<i class="fa-solid fa-video"></i>';
        default: return '<i class="fa-solid fa-link"></i>';
    }
}
