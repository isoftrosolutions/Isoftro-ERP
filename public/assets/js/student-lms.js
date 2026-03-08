/**
 * Hamro ERP — student-lms.js
 * Student Study Materials Library — Enhanced V3.1
 */

// Inject Styles
const lmsStyles = `
<style>
    .mat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; margin-top: 20px; }
    .mat-card { 
        background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #eef2f6; 
        transition: all 0.3s ease; display: flex; flex-direction: column; position: relative;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    .mat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border-color: var(--teal-l); }
    
    .mat-badge { 
        position: absolute; top: 15px; left: 15px; padding: 4px 12px; border-radius: 20px; 
        font-size: 10px; font-weight: 700; color: #fff; z-index: 2; text-transform: uppercase;
    }
    
    .mat-icon-box { 
        height: 140px; background: #f8fafc; display: flex; align-items:center; justify-content: center; 
        font-size: 3.5rem; position: relative; overflow: hidden;
    }
    .mat-icon-box::after {
        content: ''; position: absolute; inset: 0; 
        background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.02) 100%);
    }
    
    .mat-body { padding: 20px; flex: 1; }
    .mat-title { font-weight: 700; color: #1e293b; font-size: 1.1rem; margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .mat-desc { font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    
    .mat-meta { display: flex; gap: 15px; font-size: 11px; color: #94a3b8; font-weight: 600; }
    .mat-meta span { display: flex; align-items: center; gap: 5px; }
    
    .mat-foot { padding: 15px 20px; background: #fcfdfe; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; align-items: center; }
    .btn-fav { 
        width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; 
        background: #fff; color: #94a3b8; cursor: pointer; display: flex; align-items: center; 
        justify-content: center; transition: all 0.2s;
    }
    .btn-fav:hover { background: #fefce8; border-color: #facc15; color: #facc15; }
    .btn-fav.active { background: #fefce8; border-color: #facc15; color: #facc15; }
    
    /* Stats Cards */
    .st-card { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid #eef2f6; display: flex; align-items: center; gap: 15px; }
    .st-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    
    /* Tabs */
    .lms-tabs { display: flex; gap: 10px; margin-bottom: 25px; background: #f1f5f9; padding: 5px; border-radius: 12px; width: fit-content; }
    .lms-tab { padding: 8px 20px; cursor: pointer; border-radius: 8px; font-size: 14px; font-weight: 600; color: #64748b; transition: 0.2s; }
    .lms-tab.active { background: #fff; color: var(--teal); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
</style>
`;

window.renderStudentLMS = async function(tab = 'overview') {
    const mc = document.getElementById('mainContent');
    if (!document.getElementById('lmsStyles')) {
        const div = document.createElement('div');
        div.id = 'lmsStyles';
        div.innerHTML = lmsStyles;
        document.head.appendChild(div);
    }
    
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('dashboard')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">LMS Library</span></div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg,#10B981,#059669);"><i class="fa-solid fa-graduation-cap"></i></div>
                <div>
                    <div class="pg-title">E-Learning Library</div>
                    <div class="pg-sub">Access materials, watch lectures and track your progress</div>
                </div>
            </div>
            <div class="pg-acts">
                <div class="srch-grp">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="lmsSearch" class="inp" placeholder="Search resources..." onkeyup="_filterLMS()">
                </div>
            </div>
        </div>

        <div class="lms-tabs">
            <div class="lms-tab ${tab==='overview'?'active':''}" onclick="renderStudentLMS('overview')"><i class="fa-solid fa-gauge-high"></i> Overview</div>
            <div class="lms-tab ${tab==='materials'?'active':''}" onclick="renderStudentLMS('materials')"><i class="fa-solid fa-folder-open"></i> All Resources</div>
            <div class="lms-tab ${tab==='favorites'?'active':''}" onclick="renderStudentLMS('favorites')"><i class="fa-solid fa-star"></i> Favorites</div>
        </div>

        <div id="lmsViewArea">
            <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Preparing your library...</span></div>
        </div>
    </div>`;

    if (tab === 'overview') _renderLMSOverview();
    else _loadLMSMaterials(tab);
};

async function _renderLMSOverview() {
    const box = document.getElementById('lmsViewArea');
    try {
        const res = await fetch(APP_URL + '/api/student/study_materials?action=stats');
        const r = await res.json();
        const d = r.data || { total_materials: 0, favorites_count: 0, downloads: [] };

        box.innerHTML = `
            <div class="g4 mb">
                <div class="st-card">
                    <div class="st-icon" style="background:#3b82f615;color:#3b82f6;"><i class="fa-solid fa-book"></i></div>
                    <div><div style="font-size:1.5rem;font-weight:800;">${d.total_materials}</div><div style="font-size:12px;color:#64748b;">Available Materials</div></div>
                </div>
                <div class="st-card">
                    <div class="st-icon" style="background:#facc1515;color:#facc15;"><i class="fa-solid fa-star"></i></div>
                    <div><div style="font-size:1.5rem;font-weight:800;">${d.favorites_count}</div><div style="font-size:12px;color:#64748b;">Your Favorites</div></div>
                </div>
                <div class="st-card" style="grid-column: span 2;">
                    <button class="btn bt" style="width:100%;height:100%;justify-content:center;font-size:1rem;" onclick="renderStudentLMS('materials')">
                        <i class="fa-solid fa-arrow-right"></i> Browse Library
                    </button>
                </div>
            </div>
            
            <div class="card" style="padding:25px;">
                <div style="font-weight:700;margin-bottom:20px;">Top Material Categories</div>
                <div id="studentCatList" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:15px;"></div>
            </div>
        `;
        
        _loadStudentCategories();
    } catch(e) { box.innerHTML = `<div class="card p20 text-red">${e.message}</div>`; }
}

async function _loadStudentCategories() {
    const list = document.getElementById('studentCatList');
    if(!list) return;
    const res = await fetch(APP_URL + '/api/student/study_materials?action=categories');
    const r = await res.json();
    if(r.success) {
        list.innerHTML = r.data.map(c => `
            <div class="card" style="padding:15px;cursor:pointer;display:flex;align-items:center;gap:12px;border-color:transparent;background:#f8fafc;" onclick="_filterByCategory(${c.id})">
                <div style="width:36px;height:36px;border-radius:10px;background:${c.color}20;color:${c.color};display:flex;align-items:center;justify-content:center;"><i class="fa-solid ${c.icon || 'fa-folder'}"></i></div>
                <div><div style="font-weight:700;font-size:13px;">${c.name}</div><div style="font-size:11px;color:#94a3b8;">${c.material_count || 0} Materials</div></div>
            </div>
        `).join('');
    }
}

let _lmsFullList = [];
async function _loadLMSMaterials(tab) {
    const box = document.getElementById('lmsViewArea');
    try {
        const action = tab === 'favorites' ? 'favorites' : 'list';
        const res = await fetch(APP_URL + '/api/student/study_materials?action=' + action);
        const r = await res.json();
        if(!r.success) throw new Error(r.message);
        
        _lmsFullList = r.data;
        box.innerHTML = `<div class="mat-grid" id="matGrid"></div>`;
        _renderMatGrid(_lmsFullList);
    } catch(e) { box.innerHTML = `<div class="card p20 text-red">${e.message}</div>`; }
}

function _renderMatGrid(items) {
    const grid = document.getElementById('matGrid');
    if(!grid) return;
    if(!items.length) { grid.innerHTML = '<div style="grid-column:1/-1;padding:100px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-box-open" style="font-size:4rem;opacity:0.2;margin-bottom:20px;"></i><p>No materials found.</p></div>'; return; }
    
    grid.innerHTML = items.map(m => `
        <div class="mat-card">
            <div class="mat-badge" style="background:${m.category_color || 'var(--teal)'}">${m.category_name || 'General'}</div>
            <div class="mat-icon-box" style="color:${m.category_color || 'var(--teal)'}">${_getMatTypeIcon(m.content_type, m.file_extension)}</div>
            <div class="mat-body">
                <div class="mat-title">${m.title}</div>
                <div class="mat-desc">${m.description || 'No description available.'}</div>
                <div class="mat-meta">
                    <span><i class="fa-solid fa-eye"></i> ${m.view_count || 0}</span>
                    <span><i class="fa-solid fa-calendar"></i> ${new Date(m.created_at).toLocaleDateString()}</span>
                </div>
            </div>
            <div class="mat-foot">
                <button class="btn bs" style="flex:1" onclick="viewMaterialDetails(${m.id})"><i class="fa-solid fa-eye"></i> View</button>
                ${m.can_download ? `<button class="btn-icon" style="background:var(--teal);color:#fff;border-radius:10px;" onclick="downloadMaterial(${m.id})"><i class="fa-solid fa-download"></i></button>` : ''}
                <button class="btn-fav ${m.is_favorite?'active':''}" onclick="toggleFavorite(${m.id}, event)"><i class="fa-${m.is_favorite?'solid':'regular'} fa-star"></i></button>
            </div>
        </div>
    `).join('');
}

function _getMatTypeIcon(type, ext) {
    if(type === 'video') return '<i class="fa-solid fa-circle-play"></i>';
    if(type === 'link') return '<i class="fa-solid fa-link"></i>';
    const extIcons = { 'pdf': 'fa-file-pdf', 'doc': 'fa-file-word', 'docx': 'fa-file-word', 'xls': 'fa-file-excel', 'xlsx': 'fa-file-excel', 'ppt': 'fa-file-powerpoint', 'zip': 'fa-file-zipper' };
    return `<i class="fa-solid ${extIcons[ext?.toLowerCase()] || 'fa-file-lines'}"></i>`;
}

window.viewMaterialDetails = async function(id) {
    Swal.fire({ title: 'Loading...', didOpen: () => Swal.showLoading() });
    try {
        const res = await fetch(APP_URL + '/api/student/study_materials?action=get&id=' + id);
        const r = await res.json();
        if(!r.success) throw new Error(r.message);
        const m = r.data;

        Swal.fire({
            title: m.title,
            width: '800px',
            html: `
                <div style="text-align:left;">
                    <div style="display:flex;gap:20px;margin-bottom:20px;background:#f8fafc;padding:15px;border-radius:12px;">
                        <div style="font-size:3rem;color:var(--teal);">${_getMatTypeIcon(m.content_type, m.file_extension)}</div>
                        <div style="flex:1;">
                            <div style="font-size:12px;color:#64748b;text-transform:uppercase;font-weight:700;">${m.category_name} &bull; ${m.content_type}</div>
                            <div style="margin-top:5px;color:#1e293b;font-size:14px;line-height:1.6;">${m.description || 'No detailed description.'}</div>
                        </div>
                    </div>
                    
                    ${m.content_type === 'video' ? `<div style="aspect-ratio:16/9;background:#000;border-radius:12px;overflow:hidden;margin-bottom:20px;"><iframe src="${m.external_url.replace('watch?v=', 'embed/')}" style="width:100%;height:100%;border:0;" allowfullscreen></iframe></div>` : ''}
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                        <div class="card" style="padding:15px;border-color:transparent;background:#f1f5f9;">
                            <div style="font-weight:700;font-size:13px;margin-bottom:10px;">Resource Details</div>
                            <div style="font-size:12px;color:#64748b;display:grid;gap:5px;">
                                <div><i class="fa-solid fa-user"></i> Created by: ${m.created_by_name}</div>
                                <div><i class="fa-solid fa-clock"></i> Size/Duration: ${m.file_size_formatted || 'N/A'}</div>
                                <div><i class="fa-solid fa-eye"></i> Views: ${m.view_count}</div>
                                <div><i class="fa-solid fa-download"></i> Downloads: ${m.download_count}</div>
                            </div>
                        </div>
                        <div class="card" style="padding:15px;border-color:transparent;background:#f1f5f9;">
                            <div style="font-weight:700;font-size:13px;margin-bottom:10px;">Submit Feedback</div>
                            <div style="display:flex;gap:5px;margin-bottom:10px;" id="ratStars">
                                ${[1,2,3,4,5].map(i => `<i class="fa-regular fa-star" style="cursor:pointer;color:#facc15;font-size:1.2rem;" onclick="_setRating(${i})"></i>`).join('')}
                            </div>
                            <textarea id="ratComm" class="form-control" rows="2" placeholder="Your comments..."></textarea>
                            <button class="btn bt-sm mt" style="width:100%;margin-top:10px;" onclick="_submitFeedback(${m.id})">Post Review</button>
                        </div>
                    </div>

                    <div style="font-weight:700;margin-bottom:10px;">Related Materials</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        ${m.related && m.related.length ? m.related.map(r => `
                            <div class="card" style="padding:10px;display:flex;align-items:center;gap:10px;cursor:pointer;background:#fff;" onclick="viewMaterialDetails(${r.id})">
                                <span style="color:var(--teal);">${_getMatTypeIcon(r.content_type, r.file_extension)}</span>
                                <span style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${r.title}</span>
                            </div>
                        `).join('') : '<div style="color:#94a3b8;font-size:12px;">No related items.</div>'}
                    </div>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: m.can_download ? '<i class="fa-solid fa-download"></i> Download Resource' : 'Close',
            confirmButtonColor: 'var(--teal)',
            showCloseButton: true
        }).then((result) => {
            if (result.isConfirmed && m.can_download) {
                downloadMaterial(m.id);
            }
        });
    } catch(e) { Swal.fire('Error', e.message, 'error'); }
};

let _curRating = 0;
window._setRating = function(r) {
    _curRating = r;
    const stars = document.querySelectorAll('#ratStars i');
    stars.forEach((s, i) => {
        s.className = (i < r) ? 'fa-solid fa-star' : 'fa-regular fa-star';
    });
};

window._submitFeedback = async function(mid) {
    const comment = document.getElementById('ratComm').value;
    if(!_curRating) return Swal.fire('Wait', 'Please select a rating star', 'warning');
    try {
        const res = await fetch(APP_URL + '/api/student/study_materials?action=feedback', {
            method: 'POST',
            body: JSON.stringify({ material_id: mid, rating: _curRating, comment })
        });
        const r = await res.json();
        if(r.success) Swal.fire('Thank You!', 'Your feedback has been recorded.', 'success');
    } catch(e) {}
};

window.downloadMaterial = function(id) {
    window.open(APP_URL + '/api/student/study_materials?action=download&id=' + id, '_blank');
};

window.toggleFavorite = async function(id, e) {
    if(e) e.stopPropagation();
    const btn = e?.currentTarget || document.querySelector(`[onclick*="toggleFavorite(${id}"]`);
    const isFav = btn?.classList.contains('active');
    const action = isFav ? 'remove_favorite' : 'add_favorite';
    
    try {
        const res = await fetch(APP_URL + '/api/student/study_materials?action=' + action, {
            method: 'POST',
            body: JSON.stringify({ material_id: id })
        });
        const r = await res.json();
        if(r.success) {
            if(btn) {
                btn.classList.toggle('active');
                btn.querySelector('i').className = isFav ? 'fa-regular fa-star' : 'fa-solid fa-star';
            }
        }
    } catch(e) {}
};

window._filterLMS = function() {
    const q = document.getElementById('lmsSearch').value.toLowerCase();
    const filtered = _lmsFullList.filter(m => 
        m.title.toLowerCase().includes(q) || 
        (m.description && m.description.toLowerCase().includes(q))
    );
    _renderMatGrid(filtered);
};

window._filterByCategory = function(cid) {
    renderStudentLMS('materials');
    setTimeout(() => {
        const filtered = _lmsFullList.filter(m => m.category_id == cid);
        _renderMatGrid(filtered);
    }, 500);
};
