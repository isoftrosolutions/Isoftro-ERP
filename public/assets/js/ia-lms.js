/**
 * iSoftro ERP — ia-lms.js
 * Learning Management System Dashboard
 */

window.renderLMSDashboard = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">LMS Overview</span></div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg,#6366F1,#4F46E5);"><i class="fa-solid fa-graduation-cap"></i></div>
                <div>
                    <div class="pg-title">Learning Management System</div>
                    <div class="pg-sub">Manage study materials, video lectures, and student assignments</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" onclick="goNav('lms','materials')"><i class="fa-solid fa-box-archive"></i> All Materials</button>
                <button class="btn bt" onclick="openAddMaterialModal()"><i class="fa-solid fa-plus"></i> Upload Content</button>
            </div>
        </div>

        <div id="lmsStatsContainer" class="sg mb">
            <div class="sc card skeleton-shimmer" style="height:120px;"></div>
            <div class="sc card skeleton-shimmer" style="height:120px;"></div>
            <div class="sc card skeleton-shimmer" style="height:120px;"></div>
            <div class="sc card skeleton-shimmer" style="height:120px;"></div>
        </div>

        <div class="g65 mb">
            <!-- Left: Most Viewed & Recent -->
            <div style="display:grid;gap:24px;">
                <div class="card">
                    <div class="ct"><i class="fa-solid fa-clock-rotate-left"></i> Recently Uploaded</div>
                    <div id="recentUploadsList" style="padding:10px 0;">
                        <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>
                    </div>
                </div>

                <div class="card">
                    <div class="ct"><i class="fa-solid fa-fire"></i> Most Popular Content</div>
                    <div id="popularContentList" style="padding:10px 0;">
                        <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>
                    </div>
                </div>
            </div>

            <!-- Right: Content Types & Categories -->
            <div style="display:grid;gap:24px;align-content:start;">
                <div class="card">
                    <div class="ct"><i class="fa-solid fa-layer-group"></i> Content Distribution</div>
                    <div id="contentTypeDist" style="padding:20px 10px;text-align:center;">
                         <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>
                    </div>
                </div>

                <div class="card">
                    <div class="ct">Quick Links</div>
                    <div style="padding:10px;display:grid;gap:10px;">
                        <button class="btn bs fu" style="text-align:left;" onclick="goNav('lms','videos')">
                            <i class="fa-solid fa-video" style="width:20px;color:#9b59b6;"></i> Video Lectures
                        </button>
                        <button class="btn bs fu" style="text-align:left;" onclick="goNav('lms','assignments')">
                            <i class="fa-solid fa-pen-to-square" style="width:20px;color:#f39c12;"></i> Student Assignments
                        </button>
                        <button class="btn bs fu" style="text-align:left;" onclick="goNav('lms','categories')">
                            <i class="fa-solid fa-tags" style="width:20px;color:#3498db;"></i> Manage Categories
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>`;

    await _fetchLMSDashboardData();
};

async function _fetchLMSDashboardData() {
    try {
        const res = await fetch(APP_URL + '/api/admin/lms?action=dashboard');
        const r = await res.json();
        if (!r.success) throw new Error(r.message);

        const s = r.data;
        
        // Stats
        document.getElementById('lmsStatsContainer').innerHTML = `
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-b"><i class="fa-solid fa-file-pdf"></i></div></div><div class="sc-val">${s.total_materials}</div><div class="sc-lbl">Total Materials</div></div>
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-t"><i class="fa-solid fa-folder-tree"></i></div></div><div class="sc-val">${s.total_categories}</div><div class="sc-lbl">Categories</div></div>
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-y"><i class="fa-solid fa-eye"></i></div></div><div class="sc-val">${s.most_viewed.reduce((a,b)=>a+(parseInt(b.view_count)||0),0)}</div><div class="sc-lbl">Total Views</div></div>
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-r"><i class="fa-solid fa-download"></i></div></div><div class="sc-val">${s.most_viewed.reduce((a,b)=>a+(parseInt(b.download_count)||0),0)}</div><div class="sc-lbl">Total Downloads</div></div>
        `;

        // Recent Uploads
        const recentBox = document.getElementById('recentUploadsList');
        if (s.recent_uploads && s.recent_uploads.length) {
            recentBox.innerHTML = s.recent_uploads.map(m => `
                <div class="list-item" onclick="viewMaterial(${m.id})">
                    <div class="li-ico">${_getMaterialIcon(m.content_type)}</div>
                    <div class="li-info">
                        <div class="li-title">${m.title}</div>
                        <div class="li-sub">${m.category_name || 'Uncategorized'} &bull; Uploaded ${new Date(m.created_at).toLocaleDateString()}</div>
                    </div>
                    <div class="li-act"><i class="fa-solid fa-chevron-right"></i></div>
                </div>
            `).join('');
        } else {
            recentBox.innerHTML = '<div style="padding:20px;text-align:center;color:#94a3b8;">No content found</div>';
        }

        // Popular Content
        const popularBox = document.getElementById('popularContentList');
        if (s.most_viewed && s.most_viewed.length) {
            popularBox.innerHTML = s.most_viewed.map(m => `
                <div class="list-item" onclick="viewMaterial(${m.id})">
                    <div class="li-ico">${_getMaterialIcon(m.content_type)}</div>
                    <div class="li-info">
                        <div class="li-title">${m.title}</div>
                        <div class="li-sub">${m.view_count} views &bull; ${m.download_count} downloads</div>
                    </div>
                </div>
            `).join('');
        } else {
            popularBox.innerHTML = '<div style="padding:20px;text-align:center;color:#94a3b8;">No popular items yet</div>';
        }

        // Distribution
        const distBox = document.getElementById('contentTypeDist');
        if (s.by_type && s.by_type.length) {
            distBox.innerHTML = s.by_type.map(t => `
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="text-transform:capitalize;">${t.content_type}</span>
                    <strong class="tag bg-b">${t.count}</strong>
                </div>
            `).join('');
        }

    } catch(e) {
        console.error('LMS Dashboard error', e);
        Swal.fire('Error', 'Failed to load LMS data', 'error');
    }
}

function _getMaterialIcon(type) {
    switch(type) {
        case 'video': return '<i class="fa-solid fa-circle-play" style="color:#9b59b6;"></i>';
        case 'link':  return '<i class="fa-solid fa-link" style="color:#e67e22;"></i>';
        case 'image': return '<i class="fa-solid fa-image" style="color:#1abc9c;"></i>';
        default:      return '<i class="fa-solid fa-file-pdf" style="color:#e74c3c;"></i>';
    }
}
