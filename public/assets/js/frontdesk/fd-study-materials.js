/**
 * Hamro ERP — ia-study-materials.js
 * Study Materials Management: CRUD, Categories, Permissions
 */

window.renderStudyMaterials = async function(type = 'all') {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> 
            <a href="#" onclick="goNav('lms','overview')">LMS</a> <span class="bc-sep">&rsaquo;</span>
            <span class="bc-cur">${type === 'all' ? 'Directory' : type.charAt(0).toUpperCase() + type.slice(1)}</span>
        </div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico"><i class="fa-solid fa-box-archive"></i></div>
                <div>
                    <div class="pg-title">Study Materials Directory</div>
                    <div class="pg-sub">Browse, filter and manage all educational resources</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" onclick="goNav('lms','categories')"><i class="fa-solid fa-tags"></i> Categories</button>
                <button class="btn bt" onclick="openAddMaterialModal('${type}')"><i class="fa-solid fa-plus"></i> Add New</button>
            </div>
        </div>

        <div class="card mb" style="padding:15px;">
            <div style="display:flex;gap:15px;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;"><input type="text" id="matSearch" class="form-control" placeholder="Search materials..." onkeyup="debounce(_loadMaterials, 500)"></div>
                <select id="matCategory" class="form-control" style="width:180px;" onchange="_loadMaterials()"><option value="">All Categories</option></select>
                <select id="matType" class="form-control" style="width:140px;" onchange="_loadMaterials()">
                    <option value="">All Types</option>
                    <option value="file" ${type==='file'?'selected':''}>File Uploads</option>
                    <option value="video" ${type==='video'?'selected':''}>Videos</option>
                    <option value="link" ${type==='link'?'selected':''}>Links</option>
                </select>
                <button class="btn bs" onclick="_loadMaterials()"><i class="fa-solid fa-filter"></i> Apply</button>
            </div>
        </div>

        <div id="materialsListContainer" class="card" style="padding:0;overflow:hidden;">
            <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading materials...</span></div>
        </div>
        
        <div id="materialsPagination" style="margin-top:20px;display:flex;justify-content:center;"></div>
    </div>`;

    await _loadCategoriesDropdown();
    if(type !== 'all') document.getElementById('matType').value = type;
    await _loadMaterials();
};

async function _loadCategoriesDropdown() {
    const sel = document.getElementById('matCategory');
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

async function _loadMaterials(page = 1) {
    const c = document.getElementById('materialsListContainer');
    if(!c) return;

    const search = document.getElementById('matSearch').value;
    const cat = document.getElementById('matCategory').value;
    const type = document.getElementById('matType').value;

    try {
        const query = new URLSearchParams({ 
            action: 'materials', 
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
            c.innerHTML = '<div style="padding:100px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-box-open" style="font-size:4rem;margin-bottom:20px;opacity:0.3;"></i><p>No study materials found matching your criteria.</p></div>';
            return;
        }

        let html = `<div class="table-responsive"><table class="table">
            <thead><tr><th>Title & Category</th><th>Type</th><th>Batch/Access</th><th>Stats</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>`;
        
        mats.forEach(m => {
            const batchLbl = m.batch_name ? `<span class="tag bg-b">${m.batch_name}</span>` : `<span class="tag bg-t">Public</span>`;
            html += `<tr>
                <td style="width:40%;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="font-size:1.5rem;">${_getMaterialIcon(m.content_type)}</div>
                        <div>
                            <div style="font-weight:700;color:var(--teal-d);cursor:pointer;" onclick="viewMaterial(${m.id})">${m.title}</div>
                            <div style="font-size:11px;color:#94a3b8;">${m.category_name || 'General'} &bull; Published ${new Date(m.published_at || m.created_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                </td>
                <td><span class="tag" style="background:#f1f5f9;color:#475569;text-transform:uppercase;font-size:10px;">${m.content_type}</span></td>
                <td>${batchLbl}</td>
                <td style="font-size:12px;color:#64748b;">
                    <div title="Views"><i class="fa-solid fa-eye"></i> ${m.view_count || 0}</div>
                    <div title="Downloads"><i class="fa-solid fa-download"></i> ${m.download_count || 0}</div>
                </td>
                <td style="text-align:right;">
                    <button class="btn-icon" title="View" onclick="viewMaterial(${m.id})"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn-icon" title="Edit" onclick="editMaterial(${m.id})"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-icon text-red" title="Delete" onclick="deleteMaterial(${m.id})"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;
        });
        html += `</tbody></table></div>`;
        c.innerHTML = html;

        // Simple Pagination
        _renderPagination(result.meta, '_loadMaterials');

    } catch(e) {
        c.innerHTML = `<div style="padding:40px;text-align:center;color:var(--red);">${e.message}</div>`;
    }
}

window.openAddMaterialModal = async function(initialType = '') {
    const { value: formValues } = await Swal.fire({
        title: 'Add Study Material',
        width: '700px',
        html: `
            <form id="swalAddMaterialForm" class="swal-form" style="text-align:left;">
                <div class="form-group mb-3">
                    <label class="form-label">Material Title *</label>
                    <input type="text" id="swal_mat_title" class="form-control" required placeholder="e.g. Physics Chapter 3 Notes">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="form-group mb-3">
                        <label class="form-label">Category</label>
                        <select id="swal_mat_cat" class="form-control"><option value="">Select Category</option></select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Content Type</label>
                        <select id="swal_mat_type" class="form-control" onchange="toggleContentInputs(this.value)">
                            <option value="file">File Upload (.pdf, .docx, .zip)</option>
                            <option value="video">Video (YouTube/External)</option>
                            <option value="link">Other External Link</option>
                        </select>
                    </div>
                </div>
                
                <div id="fileInputWrap" class="form-group mb-3">
                    <label class="form-label">Select File *</label>
                    <input type="file" id="swal_mat_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.png,.jpg,.jpeg">
                </div>
                
                <div id="urlInputWrap" class="form-group mb-3" style="display:none;">
                    <label class="form-label">External URL *</label>
                    <input type="url" id="swal_mat_url" class="form-control" placeholder="https://...">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Brief Description</label>
                    <textarea id="swal_mat_desc" class="form-control" rows="2"></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="form-group mb-3">
                        <label class="form-label">Associate with Batch (Optional)</label>
                        <select id="swal_mat_batch" class="form-control"><option value="">All Batches</option></select>
                    </div>
                     <div class="form-group mb-3">
                        <label class="form-label">Visibility</label>
                        <select id="swal_mat_status" class="form-control"><option value="active">Active / Published</option><option value="draft">Save as Draft</option></select>
                    </div>
                </div>
            </form>
        `,
        didOpen: () => {
            _populateModalDropdowns(initialType);
        },
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Create Material',
        confirmButtonColor: 'var(--teal)',
        preConfirm: () => {
            return _validateAndGetForm();
        }
    });

    if (formValues) {
        _submitMaterial(formValues);
    }
};

window.toggleContentInputs = function(type) {
    const f = document.getElementById('fileInputWrap');
    const u = document.getElementById('urlInputWrap');
    if (type === 'file') { f.style.display = 'block'; u.style.display = 'none'; }
    else { f.style.display = 'none'; u.style.display = 'block'; }
};

async function _populateModalDropdowns(initialType) {
    if(initialType) {
        const ts = document.getElementById('swal_mat_type');
        if(ts) { ts.value = initialType; toggleContentInputs(initialType); }
    }
    
    // Categories
    const catSel = document.getElementById('swal_mat_cat');
    const res = await fetch(APP_URL + '/api/frontdesk/lms?action=categories');
    const r = await res.json();
    if(r.success) r.data.forEach(c => { const o=document.createElement('option'); o.value=c.id; o.textContent=c.name; catSel.appendChild(o); });

    // Batches
    const batchSel = document.getElementById('swal_mat_batch');
    const resB = await fetch(APP_URL + '/api/frontdesk/batches');
    const rB = await resB.json();
    if(rB.success) rB.data.forEach(b => { const o=document.createElement('option'); o.value=b.id; o.textContent=`${b.name} (${b.course_name})`; batchSel.appendChild(o); });
}

function _validateAndGetForm() {
    const title = document.getElementById('swal_mat_title').value;
    const type = document.getElementById('swal_mat_type').value;
    if (!title) { Swal.showValidationMessage('Title is required'); return false; }
    
    const formData = new FormData();
    formData.append('title', title);
    formData.append('description', document.getElementById('swal_mat_desc').value);
    formData.append('category_id', document.getElementById('swal_mat_cat').value);
    formData.append('content_type', type);
    formData.append('batch_id', document.getElementById('swal_mat_batch').value);
    formData.append('status', document.getElementById('swal_mat_status').value);

    if (type === 'file') {
        const fileInput = document.getElementById('swal_mat_file');
        if (!fileInput.files.length) { Swal.showValidationMessage('Please select a file to upload'); return false; }
        formData.append('file', fileInput.files[0]);
    } else {
        const url = document.getElementById('swal_mat_url').value;
        if (!url) { Swal.showValidationMessage('URL is required'); return false; }
        formData.append('external_url', url);
    }

    return formData;
}

async function _submitMaterial(formData) {
    Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    try {
        formData.append('action', 'create_material');
        const res = await fetch(APP_URL + '/api/frontdesk/lms', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            Swal.fire('Success!', 'Material uploaded successfully', 'success');
            _loadMaterials();
        } else throw new Error(result.message);
    } catch(e) { Swal.fire('Error', e.message, 'error'); }
}

function _renderPagination(meta, funcName) {
    const p = document.getElementById('materialsPagination');
    if (!p || !meta || meta.total_pages <= 1) { p.innerHTML = ''; return; }
    
    let html = '';
    for (let i = 1; i <= meta.total_pages; i++) {
        const active = i === meta.page ? 'bs' : 'bt-sm';
        html += `<button class="btn ${active}" style="margin:2px;" onclick="${funcName}(${i})">${i}</button>`;
    }
    p.innerHTML = html;
}

// Logic for Category Management
window.renderLMSCategories = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('lms','overview')">LMS</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Categories</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-tags"></i></div><div><div class="pg-title">LMS Categories</div><div class="pg-sub">Organize your study materials into logical groups</div></div></div>
            <div class="pg-acts"><button class="btn bt" onclick="openAddCategoryModal()"><i class="fa-solid fa-plus"></i> New Category</button></div>
        </div>
        <div class="card" id="lmsCategoriesContainer"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading categories...</span></div></div>
    </div>`;
    await _loadCategories();
};

async function _loadCategories() {
    const c = document.getElementById('lmsCategoriesContainer');
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/lms?action=categories');
        const r = await res.json();
        if(!r.success) throw new Error(r.message);
        const cats = r.data;
        if(!cats.length) { c.innerHTML = '<div style="padding:40px;text-align:center;color:#94a3b8;">No categories created yet.</div>'; return; }
        
        c.innerHTML = `<div class="table-responsive"><table class="table">
            <thead><tr><th>Icon</th><th>Category Name</th><th>Description</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                ${cats.map(cat => `
                    <tr>
                         <td style="width:50px;"><div style="width:40px;height:40px;border-radius:10px;background:${cat.color}20;color:${cat.color};display:flex;align-items:center;justify-content:center;"><i class="fa-solid ${cat.icon || 'fa-folder'}"></i></div></td>
                         <td><div style="font-weight:700;">${cat.name}</div></td>
                         <td><div style="font-size:12px;color:#64748b;">${cat.description || ''}</div></td>
                         <td style="text-align:right;">
                            <button class="btn-icon" onclick="editCategory(${cat.id})"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn-icon text-red" onclick="deleteCategory(${cat.id})"><i class="fa-solid fa-trash"></i></button>
                         </td>
                    </tr>
                `).join('')}
            </tbody>
        </table></div>`;
    } catch(e) { c.innerHTML = `<div style="padding:20px;color:var(--red);">${e.message}</div>`; }
}

// Standalone Upload Page
window.renderStudyMaterialUploadPage = function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> 
            <a href="#" onclick="goNav('lms','overview')">LMS</a> <span class="bc-sep">&rsaquo;</span>
            <span class="bc-cur">Upload Material</span>
        </div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg,#0D9488,#14B8A6);"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                <div>
                    <div class="pg-title">Upload Study Material</div>
                    <div class="pg-sub">Add new documents, videos, or links to the learning repository</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" onclick="goNav('lms','materials')"><i class="fa-solid fa-list"></i> View All</button>
            </div>
        </div>

        <div class="card" style="max-width:800px;margin:0 auto;padding:30px;">
            <form id="pgAddMaterialForm" onsubmit="_handleStandaloneUpload(event)">
                <div class="form-group mb-4">
                    <label class="form-label" style="font-weight:600;">Material Title *</label>
                    <input type="text" name="title" id="pg_mat_title" class="form-control" required placeholder="e.g. Physics Chapter 3 Notes">
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group mb-4">
                        <label class="form-label" style="font-weight:600;">Category</label>
                        <select name="category_id" id="pg_mat_cat" class="form-control"><option value="">Select Category</option></select>
                    </div>
                    <div class="form-group mb-4">
                        <label class="form-label" style="font-weight:600;">Content Type</label>
                        <select name="content_type" id="pg_mat_type" class="form-control" onchange="toggleContentInputsPg(this.value)">
                            <option value="file">File Upload (.pdf, .docx, .zip)</option>
                            <option value="video">Video (YouTube/External)</option>
                            <option value="link">Other External Link</option>
                        </select>
                    </div>
                </div>
                
                <div id="fileInputWrapPg" class="form-group mb-4" style="background:#f8fafc;padding:20px;border:2px dashed #cbd5e1;border-radius:10px;text-align:center;">
                    <label class="form-label" style="display:block;margin-bottom:10px;font-weight:600;">Select File *</label>
                    <input type="file" name="file" id="pg_mat_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.png,.jpg,.jpeg">
                    <div style="margin-top:10px;font-size:12px;color:#64748b;">PDF, DOCX, ZIP, IMAGES allowed (Max 50MB)</div>
                </div>
                
                <div id="urlInputWrapPg" class="form-group mb-4" style="display:none;">
                    <label class="form-label" style="font-weight:600;">External URL *</label>
                    <input type="url" name="external_url" id="pg_mat_url" class="form-control" placeholder="https://youtube.com/watch?v=...">
                </div>

                <div class="form-group mb-4">
                    <label class="form-label" style="font-weight:600;">Description</label>
                    <textarea name="description" id="pg_mat_desc" class="form-control" rows="3" placeholder="Briefly describe the content..."></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group mb-4">
                        <label class="form-label" style="font-weight:600;">Batch (Selection restricts access)</label>
                        <select name="batch_id" id="pg_mat_batch" class="form-control"><option value="">All Batches (Public)</option></select>
                    </div>
                     <div class="form-group mb-4">
                        <label class="form-label" style="font-weight:600;">Initial Status</label>
                        <select name="status" id="pg_mat_status" class="form-control">
                            <option value="active">Active / Published</option>
                            <option value="draft">Save as Draft</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:15px;border-top:1px solid #e2e8f0;padding-top:20px;">
                    <button type="button" class="btn bs" onclick="goNav('lms','materials')">Cancel</button>
                    <button type="submit" class="btn bt" style="padding:10px 30px;"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Now</button>
                </div>
            </form>
        </div>
    </div>`;

    // Populate dropdowns
    _populatePgDropdowns();
};

window.toggleContentInputsPg = function(type) {
    const f = document.getElementById('fileInputWrapPg');
    const u = document.getElementById('urlInputWrapPg');
    if (type === 'file') { f.style.display = 'block'; u.style.display = 'none'; }
    else { f.style.display = 'none'; u.style.display = 'block'; }
};

async function _populatePgDropdowns() {
    // Categories
    const catSel = document.getElementById('pg_mat_cat');
    const res = await fetch(APP_URL + '/api/frontdesk/lms?action=categories');
    const r = await res.json();
    if(r.success) r.data.forEach(c => { const o=document.createElement('option'); o.value=c.id; o.textContent=c.name; catSel.appendChild(o); });

    // Batches
    const batchSel = document.getElementById('pg_mat_batch');
    const resB = await fetch(APP_URL + '/api/frontdesk/batches');
    const rB = await resB.json();
    if(rB.success) rB.data.forEach(b => { const o=document.createElement('option'); o.value=b.id; o.textContent=`${b.name} (${b.course_name})`; batchSel.appendChild(o); });
}

window._handleStandaloneUpload = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    // Explicitly handle file if not automatically in FormData (safest way)
    const fileInput = document.getElementById('pg_mat_file');
    const type = document.getElementById('pg_mat_type').value;
    
    if (type === 'file' && !fileInput.files.length) {
        Swal.fire('Error', 'Please select a file to upload', 'error');
        return;
    }

    _submitMaterial(formData);
};

window.renderLMSAnalytics = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> 
            <a href="#" onclick="goNav('lms','overview')">LMS</a> <span class="bc-sep">&rsaquo;</span> 
            <span class="bc-cur">Analytics</span>
        </div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <div class="pg-title">LMS Analytics</div>
                    <div class="pg-sub">Insights into material usage, engagement and repository growth</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" onclick="_loadAnalyticsData()"><i class="fa-solid fa-rotate"></i> Refresh</button>
            </div>
        </div>

        <div id="analyticsLoader" class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Fetching latest insights...</span></div>
        <div id="analyticsContent" style="display:none;">
            <!-- KPI Cards -->
            <div class="kpi-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:25px;">
                <div class="card" style="padding:20px;display:flex;align-items:center;gap:15px;border-left:4px solid #3b82f6;">
                    <div style="width:50px;height:50px;border-radius:12px;background:#3b82f615;color:#3b82f6;display:flex;align-items:center;justify-content:center;font-size:1.5rem;"><i class="fa-solid fa-book"></i></div>
                    <div><div id="kpi-total" style="font-size:1.8rem;font-weight:800;line-height:1;">0</div><div style="font-size:12px;color:#64748b;margin-top:5px;">Total Materials</div></div>
                </div>
                <div class="card" style="padding:20px;display:flex;align-items:center;gap:15px;border-left:4px solid #10b981;">
                    <div style="width:50px;height:50px;border-radius:12px;background:#10b98115;color:#10b981;display:flex;align-items:center;justify-content:center;font-size:1.5rem;"><i class="fa-solid fa-eye"></i></div>
                    <div><div id="kpi-views" style="font-size:1.8rem;font-weight:800;line-height:1;">0</div><div style="font-size:12px;color:#64748b;margin-top:5px;">Total Views</div></div>
                </div>
                <div class="card" style="padding:20px;display:flex;align-items:center;gap:15px;border-left:4px solid #f59e0b;">
                    <div style="width:50px;height:50px;border-radius:12px;background:#f59e0b15;color:#f59e0b;display:flex;align-items:center;justify-content:center;font-size:1.5rem;"><i class="fa-solid fa-download"></i></div>
                    <div><div id="kpi-downloads" style="font-size:1.8rem;font-weight:800;line-height:1;">0</div><div style="font-size:12px;color:#64748b;margin-top:5px;">Total Downloads</div></div>
                </div>
                <div class="card" style="padding:20px;display:flex;align-items:center;gap:15px;border-left:4px solid #8b5cf6;">
                    <div style="width:50px;height:50px;border-radius:12px;background:#8b5cf615;color:#8b5cf6;display:flex;align-items:center;justify-content:center;font-size:1.5rem;"><i class="fa-solid fa-file-invoice"></i></div>
                    <div><div id="kpi-active" style="font-size:1.8rem;font-weight:800;line-height:1;">0</div><div style="font-size:12px;color:#64748b;margin-top:5px;">Active / Published</div></div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
                <!-- By Category -->
                <div class="card" style="padding:20px;">
                    <div style="font-weight:700;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;">
                        <span>Materials by Category</span>
                        <i class="fa-solid fa-folder-tree" style="opacity:0.3;"></i>
                    </div>
                    <div id="statsByCategory" style="display:flex;flex-direction:column;gap:15px;"></div>
                </div>
                
                <!-- By Type -->
                <div class="card" style="padding:20px;">
                    <div style="font-weight:700;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;">
                        <span>Distribution by Content Type</span>
                        <i class="fa-solid fa-pie-chart" style="opacity:0.3;"></i>
                    </div>
                    <div id="statsByType" style="display:flex;flex-direction:column;gap:15px;"></div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card" style="padding:0;overflow:hidden;">
                <div style="padding:20px;font-weight:700;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
                    <span>Recent Access Logs</span>
                    <button class="btn bt-sm" onclick="goNav('lms','materials')">View All Materials</button>
                </div>
                <div id="recentActivityTable"></div>
            </div>
        </div>
    </div>`;

    _loadAnalyticsData();
};

window._loadAnalyticsData = async function() {
    const loader = document.getElementById('analyticsLoader');
    const content = document.getElementById('analyticsContent');
    if(!loader || !content) return;

    loader.style.display = 'flex';
    content.style.display = 'none';

    try {
        const res = await fetch(APP_URL + '/api/frontdesk/lms?action=stats');
        const r = await res.json();
        if(!r.success) throw new Error(r.message);

        const d = r.data;
        
        // Update KPIs
        document.getElementById('kpi-total').textContent = d.totals.total || 0;
        document.getElementById('kpi-views').textContent = d.totals.total_views || 0;
        document.getElementById('kpi-downloads').textContent = d.totals.total_downloads || 0;
        document.getElementById('kpi-active').textContent = d.totals.active || 0;

        // Render Categories
        const catBox = document.getElementById('statsByCategory');
        if (d.by_category && d.by_category.length) {
            catBox.innerHTML = d.by_category.map(c => `
                <div style="margin-bottom:5px;">
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;">
                        <span style="font-weight:600;">${c.name}</span>
                        <span style="color:#64748b;">${c.count} items</span>
                    </div>
                    <div style="height:8px;background:#f1f5f9;border-radius:10px;overflow:hidden;">
                        <div style="height:100%;width:${(c.count / d.totals.total * 100) || 0}%;background:${c.color || 'var(--teal)'};border-radius:10px;"></div>
                    </div>
                </div>
            `).join('');
        } else {
            catBox.innerHTML = '<p style="text-align:center;color:#94a3b8;padding:20px;">No category data available</p>';
        }

        // Render Types
        const typeBox = document.getElementById('statsByType');
        const typeIcons = { 'file': 'fa-file-lines', 'video': 'fa-video', 'link': 'fa-link' };
        if (d.by_type && d.by_type.length) {
            typeBox.innerHTML = d.by_type.map(t => `
                <div style="display:flex;align-items:center;gap:15px;padding:10px;background:#f8fafc;border-radius:10px;">
                    <div style="width:35px;height:35px;border-radius:8px;background:white;display:flex;align-items:center;justify-content:center;color:var(--teal);border:1px solid #e2e8f0;">
                         <i class="fa-solid ${typeIcons[t.content_type] || 'fa-file'}"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:13px;font-weight:700;text-transform:capitalize;">${t.content_type}s</div>
                        <div style="font-size:11px;color:#64748b;">${t.count} materials found</div>
                    </div>
                    <div style="font-weight:800;color:var(--teal);">${Math.round(t.count / d.totals.total * 100) || 0}%</div>
                </div>
            `).join('');
        } else {
            typeBox.innerHTML = '<p style="text-align:center;color:#94a3b8;padding:20px;">No distribution data available</p>';
        }

        // Render Recent Activity
        const actBox = document.getElementById('recentActivityTable');
        if (d.recent_activity && d.recent_activity.length) {
            actBox.innerHTML = `
                <div class="table-responsive">
                    <table class="table" style="margin-bottom:0;">
                        <thead><tr><th>User</th><th>Action</th><th>Material</th><th>Time</th><th>IP Address</th></tr></thead>
                        <tbody>
                            ${d.recent_activity.map(a => `
                                <tr>
                                    <td><div style="font-weight:600;">${a.user_name}</div><div style="font-size:10px;color:#94a3b8;text-transform:uppercase;">${a.user_type}</div></td>
                                    <td><span class="tag ${a.action==='download'?'bg-b':'bg-t'}" style="font-size:10px;">${a.action.toUpperCase()}</span></td>
                                    <td><div style="font-size:13px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${a.material_title}">${a.material_title}</div></td>
                                    <td style="font-size:12px;color:#64748b;">${new Date(a.created_at).toLocaleString()}</td>
                                    <td style="font-size:11px;color:#94a3b8;"><code>${a.ip_address || '-'}</code></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            actBox.innerHTML = '<div style="padding:60px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-clock-rotate-left" style="font-size:3rem;opacity:0.2;margin-bottom:15px;"></i><p>No recent activity recorded yet.</p></div>';
        }

        loader.style.display = 'none';
        content.style.display = 'block';

    } catch(e) {
        loader.innerHTML = `<div style="color:var(--red);padding:20px;"><i class="fa-solid fa-triangle-exclamation"></i> Error: ${e.message}</div>`;
    }
};
