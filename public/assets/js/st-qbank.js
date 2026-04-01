/**
 * iSoftro ERP — st-qbank.js
 * Student View for Question Bank
 */

window.renderSTQBank = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `
        <div style="padding:24px;">
            <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:16px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">
                <div class="card-body" style="padding:30px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;">
                        <div style="display:flex;align-items:center;gap:20px;">
                            <div style="width:60px;height:60px;background:rgba(255,255,255,0.2);backdrop-filter:blur(10px);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.8rem;">
                                <i class="fa-solid fa-database"></i>
                            </div>
                            <div>
                                <h1 style="margin:0;font-size:1.5rem;font-weight:800;">Question Bank</h1>
                                <p style="margin:5px 0 0;opacity:0.8;font-size:13px;">Practice with past papers and model questions</p>
                            </div>
                        </div>
                        <div style="flex:1;min-width:250px;max-width:400px;position:relative;">
                            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#6366f1;"></i>
                            <input type="text" id="qbSearch" placeholder="Search questions..." 
                                onkeyup="debounce(window._loadSTQuestions, 500)"
                                style="width:100%;padding:12px 15px 12px(45px);border-radius:12px;border:none;background:rgba(255,255,255,1);color:#1e293b;outline:none;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;">
                <select id="qbCategory" class="form-control" style="width:180px;border-radius:10px;" onchange="window._loadSTQuestions()"><option value="">All Categories</option></select>
                <select id="qbType" class="form-control" style="width:150px;border-radius:10px;" onchange="window._loadSTQuestions()">
                    <option value="">All Types</option>
                    <option value="file">Documents</option>
                    <option value="image">Images</option>
                    <option value="video">Videos</option>
                </select>
                <button class="btn btn-primary" style="background:#6366f1;border:none;border-radius:10px;padding:10px 20px;" onclick="window._loadSTQuestions()">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </div>

            <div id="qbListContainer" style="min-height:300px;">
                <div style="text-align:center;padding:50px;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:#6366f1;"></i></div>
            </div>

            <div id="qbPagination" style="margin-top:24px;display:flex;justify-content:center;gap:8px;"></div>
        </div>
    `;

    await _loadQBCategories();
    await _loadSTQuestions();
};

async function _loadQBCategories() {
    const sel = document.getElementById('qbCategory');
    if(!sel) return;
    try {
        const res = await fetch(APP_URL + '/api/student/study-materials?action=categories');
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

window._loadSTQuestions = async function(page = 1) {
    const c = document.getElementById('qbListContainer');
    if(!c) return;

    const search = document.getElementById('qbSearch').value;
    const cat = document.getElementById('qbCategory').value;
    const type = document.getElementById('qbType').value;

    try {
        const query = new URLSearchParams({ 
            action: 'list', 
            is_qbank: 1,
            page, 
            search, 
            category_id: cat, 
            content_type: type 
        });
        const res = await fetch(APP_URL + '/api/student/study-materials?' + query.toString());
        const result = await res.json();
        
        if (!result.success) throw new Error(result.message);
        
        const mats = result.data;
        if (!mats.length) {
            c.innerHTML = `
                <div class="card" style="padding:80px;text-align:center;border-radius:16px;">
                    <i class="fa-solid fa-database" style="font-size:4rem;color:#e2e8f0;margin-bottom:20px;"></i>
                    <h3 style="color:#64748b;">No questions found</h3>
                    <p style="color:#94a3b8;">Try adjusting your filters or search terms.</p>
                </div>`;
            return;
        }

        let html = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">`;
        
        mats.forEach(m => {
            const icon = _getQBIcon(m.content_type);
            const size = m.file_size ? (m.file_size / 1024 / 1024).toFixed(2) + ' MB' : 'External';
            
            html += `
                <div class="card qb-card" style="border-radius:16px;overflow:hidden;transition:0.3s;border:1px solid #f1f5f9;" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 10px 20px -5px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                    <div style="padding:24px;">
                        <div style="display:flex;gap:15px;align-items:flex-start;">
                            <div style="width:50px;height:50px;background:#f1f5f9;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#6366f1;flex-shrink:0;">
                                ${icon}
                            </div>
                            <div style="flex:1;min-width:0;">
                                <h4 style="margin:0 0 8px;font-size:16px;font-weight:700;color:#1e293b;cursor:pointer;" onclick="viewQBItem(${m.id})">${m.title}</h4>
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                                    <span style="font-size:11px;padding:3px 8px;background:#eef2ff;color:#6366f1;border-radius:20px;font-weight:600;">${m.subject_name || 'General'}</span>
                                    <span style="font-size:10px;color:#94a3b8;"><i class="fa-regular fa-clock"></i> ${new Date(m.created_at).toLocaleDateString()}</span>
                                </div>
                                <p style="margin:0;font-size:12px;color:#64748b;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${m.description || 'No additional notes'}</p>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;padding-top:16px;border-top:1px solid #f1f5f9;">
                            <span style="font-size:11px;color:#94a3b8;">${m.file_extension?.toUpperCase() || 'LINK'} &bull; ${size}</span>
                            <div style="display:flex;gap:10px;">
                                <button class="btn btn-sm" style="background:#f1f5f9;color:#64748b;border:none;border-radius:8px;padding:6px 12px;font-size:11px;font-weight:700;" onclick="viewQBItem(${m.id})">VIEW</button>
                                <button class="btn btn-sm" style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-size:11px;font-weight:700;" onclick="window.location.href='${APP_URL}/api/student/study-materials?action=download&id=${m.id}'"><i class="fa-solid fa-download"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += `</div>`;
        c.innerHTML = html;

        _renderQBPage(result.meta, 'window._loadSTQuestions', 'qbPagination');

    } catch(e) {
        c.innerHTML = `<div class="card" style="padding:40px;text-align:center;color:#ef4444;border-radius:16px;">${e.message}</div>`;
    }
}

window.viewQBItem = async function(id) {
    // Redirection or modal
    // Check if viewMaterial is available globally
    if(window.viewMaterial) window.viewMaterial(id);
    else {
        // Fallback or custom modal
        window.location.href = APP_URL + '/api/student/study-materials?action=download&id=' + id;
    }
}

function _renderQBPage(meta, funcName, containerId) {
    const p = document.getElementById(containerId);
    if (!p || !meta || meta.total_pages <= 1) { if(p) p.innerHTML = ''; return; }
    
    let html = '';
    for (let i = 1; i <= meta.total_pages; i++) {
        const active = i === meta.page ? 'background:#6366f1;color:#fff;border:none;' : 'background:#fff;color:#6366f1;border:1px solid #e2e8f0;';
        html += `<button class="btn" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:10px;font-weight:700;font-size:13px;transition:0.2s;${active}" onclick="${funcName}(${i})">${i}</button>`;
    }
    p.innerHTML = html;
}

function _getQBIcon(type) {
    switch(type) {
        case 'file': return '<i class="fa-solid fa-file-pdf"></i>';
        case 'image': return '<i class="fa-solid fa-file-image"></i>';
        case 'video': return '<i class="fa-solid fa-video"></i>';
        default: return '<i class="fa-solid fa-link"></i>';
    }
}
