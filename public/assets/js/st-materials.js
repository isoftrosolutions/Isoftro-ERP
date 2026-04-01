/**
 * iSoftro ERP — Student Portal · st-materials.js
 * Student Study Materials Module
 */

window.renderSTMaterials = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div></div>';

    try {
        // Fetch materials and categories
        const [materialsRes, statsRes] = await Promise.all([
            fetch(`${window.APP_URL}/api/student/study-materials?action=list`),
            fetch(`${window.APP_URL}/api/student/study-materials?action=stats`)
        ]);
        
        const materialsResult = await materialsRes.json();
        const statsResult = await statsRes.json();
        
        const materials = materialsResult.success ? (materialsResult.data || []) : [];
        const stats = statsResult.success ? (statsResult.data || {}) : {};
        
        mc.innerHTML = `
            <div style="padding:24px;">
                <!-- Header -->
                <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,var(--sa-primary),var(--sa-primary-h));color:#fff;">
                    <div class="card-body" style="padding:24px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                            <div style="display:flex;align-items:center;gap:16px;">
                                <div style="width:50px;height:50px;background:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--sa-primary);font-size:1.5rem;">
                                    <i class="fa-solid fa-book"></i>
                                </div>
                                <div>
                                    <h2 style="margin:0;font-size:1.3rem;">Study Materials</h2>
                                    <p style="margin:5px 0 0;opacity:0.9;font-size:13px;">Access your learning resources</p>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px;">
                                <input type="text" id="materialSearch" placeholder="Search materials..." 
                                    onkeyup="searchMaterials()"
                                    style="padding:8px 12px;border-radius:8px;border:none;width:200px;background:rgba(255,255,255,0.9);">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;margin-bottom:24px;">
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:var(--sa-primary);">${stats.total_materials || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Total Materials</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#16a34a;">${stats.files_count || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Files</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#9333ea;">${stats.videos_count || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Videos</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#d97706;">${stats.links_count || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">External Links</div>
                        </div>
                    </div>
                </div>
                
                <!-- Materials Grid -->
                <div class="card">
                    <div class="card-hdr">
                        <div class="ct"><i class="fa-solid fa-folder-open" style="margin-right:8px;color:var(--sa-primary);"></i> Available Materials</div>
                    </div>
                    <div class="card-body" id="materialsList">
                        ${renderMaterialsGrid(materials)}
                    </div>
                </div>
            </div>
        `;
        
        // Store materials globally for search
        window._allMaterials = materials;
        
    } catch (e) {
        console.error('Materials load error:', e);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><p>Error loading study materials.</p></div></div></div>';
    }
};

function renderMaterialsGrid(materials) {
    if (!materials || materials.length === 0) {
        return `
            <div style="text-align:center;padding:40px;color:var(--tl);">
                <i class="fa-solid fa-folder-open" style="font-size:3rem;margin-bottom:15px;opacity:0.3;"></i>
                <p>No study materials available</p>
            </div>
        `;
    }
    
    return `
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
            ${materials.map(m => renderMaterialCard(m)).join('')}
        </div>
    `;
}

function renderMaterialCard(material) {
    let icon = 'fa-file';
    let iconColor = 'var(--sa-primary)';
    let iconBg = '#e0e7ff';
    
    switch(material.content_type) {
        case 'video':
        case 'link':
            icon = 'fa-link';
            iconColor = '#9333ea';
            iconBg = '#f3e8ff';
            break;
        case 'document':
            icon = 'fa-file-pdf';
            iconColor = '#dc2626';
            iconBg = '#fee2e2';
            break;
        case 'image':
            icon = 'fa-image';
            iconColor = '#16a34a';
            iconBg = '#dcfce7';
            break;
    }
    
    const title = escapeHtml(material.title || 'Untitled');
    const description = material.description ? escapeHtml(material.description.substring(0, 100)) + (material.description.length > 100 ? '...' : '') : 'No description';
    const category = material.category_name || 'Uncategorized';
    const date = material.created_at ? formatDate(material.created_at) : '';
    const views = material.view_count || 0;
    const downloads = material.download_count || 0;
    
    return `
        <div class="card" style="cursor:pointer;transition:0.2s;" onclick="viewMaterial(${material.id})" 
            onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="padding:16px;">
                <div style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="width:48px;height:48px;border-radius:12px;background:${iconBg};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid ${icon}" style="font-size:1.3rem;color:${iconColor};"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h4 style="margin:0 0 6px;font-size:15px;font-weight:600;color:var(--td);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${title}</h4>
                        <p style="margin:0;font-size:12px;color:var(--tl);line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">${description}</p>
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid var(--cb);">
                    <span style="font-size:11px;color:var(--tl);"><i class="fa-solid fa-tag"></i> ${category}</span>
                    <div style="display:flex;gap:8px;font-size:11px;color:var(--tl);">
                        <span><i class="fa-solid fa-eye"></i> ${views}</span>
                        <span><i class="fa-solid fa-download"></i> ${downloads}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

window.viewMaterial = async function(id) {
    try {
        const res = await fetch(`${window.APP_URL}/api/student/study-materials?action=view&id=${id}`);
        const result = await res.json();
        
        if (result.success && result.data) {
            const m = result.data;
            
            let contentHtml = '';
            if (m.file_path && m.content_type === 'file') {
                contentHtml = `
                    <div style="text-align:center;padding:20px;">
                        <i class="fa-solid fa-file" style="font-size:4rem;color:var(--sa-primary);margin-bottom:16px;"></i>
                        <h4>${escapeHtml(m.title)}</h4>
                        <p style="color:var(--tl);margin-bottom:20px;">${escapeHtml(m.description || '')}</p>
                        <a href="${m.file_path}" target="_blank" class="btn bs" style="background:var(--sa-primary);color:#fff;">
                            <i class="fa-solid fa-download"></i> Download File
                        </a>
                    </div>
                `;
            } else if (m.external_url) {
                contentHtml = `
                    <div style="text-align:center;padding:20px;">
                        <i class="fa-solid fa-link" style="font-size:4rem;color:#9333ea;margin-bottom:16px;"></i>
                        <h4>${escapeHtml(m.title)}</h4>
                        <p style="color:var(--tl);margin-bottom:20px;">${escapeHtml(m.description || '')}</p>
                        <a href="${m.external_url}" target="_blank" class="btn bs" style="background:#9333ea;color:#fff;">
                            <i class="fa-solid fa-external-link-alt"></i> Open Link
                        </a>
                    </div>
                `;
            } else {
                contentHtml = `
                    <div style="padding:20px;">
                        <h4>${escapeHtml(m.title)}</h4>
                        <p style="color:var(--tl);">${escapeHtml(m.description || 'No description')}</p>
                    </div>
                `;
            }
            
            const mc = document.getElementById('mainContent');
            if (mc) {
                mc.innerHTML = `
                    <div style="padding:24px;">
                        <button class="btn" onclick="goST('materials')" style="margin-bottom:16px;background:var(--bg);border:1px solid var(--cb);">
                            <i class="fa-solid fa-arrow-left"></i> Back to Materials
                        </button>
                        <div class="card">
                            <div class="card-body">
                                ${contentHtml}
                                <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--cb);font-size:12px;color:var(--tl);">
                                    <p><strong>Category:</strong> ${m.category_name || 'Uncategorized'}</p>
                                    <p><strong>Views:</strong> ${m.view_count || 0}</p>
                                    <p><strong>Downloads:</strong> ${m.download_count || 0}</p>
                                    <p><strong>Posted:</strong> ${m.created_at ? formatDate(m.created_at) : 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Error viewing material:', e);
        alert('Error loading material details');
    }
};

window.searchMaterials = function() {
    const searchTerm = document.getElementById('materialSearch').value.toLowerCase();
    const materials = window._allMaterials || [];
    
    if (!searchTerm) {
        document.getElementById('materialsList').innerHTML = renderMaterialsGrid(materials);
        return;
    }
    
    const filtered = materials.filter(m => {
        const title = (m.title || '').toLowerCase();
        const description = (m.description || '').toLowerCase();
        const category = (m.category_name || '').toLowerCase();
        return title.includes(searchTerm) || description.includes(searchTerm) || category.includes(searchTerm);
    });
    
    document.getElementById('materialsList').innerHTML = renderMaterialsGrid(filtered);
};

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.renderSTMaterials = window.renderSTMaterials;
