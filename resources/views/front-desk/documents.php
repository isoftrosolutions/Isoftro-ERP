<?php
/**
 * Front Desk — Student Document Management
 * Digital storage for enrollment documents (citizenship, transcripts, etc.)
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Student Documents';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('academic');
}
?>
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #64748b, #475569);">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <h1 class="pg-title">Student Documents</h1>
                    <p class="pg-sub">Manage and verify student academic and personal records</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="loadDocuments()"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 24px;">
            <!-- Student Selector -->
            <div>
                <div class="card" style="padding:20px; border-radius:16px;">
                    <h3 style="font-size:14px; font-weight:700; color:#1a1a2e; margin-bottom:15px;">Select Student</h3>
                    <div style="position:relative; margin-bottom:20px;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                        <input type="text" id="stuSearch" class="fi" placeholder="Name or Roll No..." style="padding-left:36px;" oninput="searchStudents()">
                        <div id="searchResults" class="search-dd"></div>
                    </div>

                    <div id="selectedStudentInfo" style="display:none;">
                        <div style="padding:15px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; text-align:center;">
                            <div style="width:50px; height:50px; background:#64748b; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; margin:0 auto 10px;" id="stuAvatar">?</div>
                            <div id="stuName" style="font-weight:700; color:#1a1a2e; font-size:15px;">-</div>
                            <div id="stuMeta" style="font-size:12px; color:#64748b; margin-top:2px;">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents View -->
            <div>
                <div id="noStudentState" class="card" style="text-align:center; padding:100px 40px; border-radius:16px;">
                    <i class="fa-solid fa-folder-open" style="font-size:60px; color:#e2e8f0; margin-bottom:20px;"></i>
                    <h2>No Student Selected</h2>
                    <p style="color:#64748b;">Search for a student to manage their digital documents and verified records.</p>
                </div>

                <div id="docContainer" style="display:none;">
                    <div class="card mb" style="padding:20px; border-radius:16px; margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                            <h3 style="font-size:15px; font-weight:700; color:#1a1a2e;">Uploaded Documents</h3>
                            <button class="btn" style="background:#1a1a2e; color:#fff; font-size:13px;" onclick="openUploadModal()">
                                <i class="fa-solid fa-upload"></i> Upload New
                            </button>
                        </div>
                        
                        <div id="docList">
                            <!-- Doc items -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<style>
.fl { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.search-dd { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; max-height: 250px; overflow-y: auto; z-index: 100; display: none; }
.search-item { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s; display: flex; align-items: center; gap: 12px; }
.search-item:hover { background: #f8fafc; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }

.doc-item { display:flex; align-items:center; gap:16px; padding:16px; border:1px solid #f1f5f9; border-radius:12px; margin-bottom:12px; transition:all 0.2s; }
.doc-item:hover { border-color:#e2e8f0; background:#fcfdfe; }
.doc-ico { width:40px; height:40px; border-radius:10px; background:#f1f5f9; color:#64748b; display:flex; align-items:center; justify-content:center; font-size:20px; }
</style>

<script>
let currentStudent = null;

async function searchStudents() {
    const q = document.getElementById('stuSearch').value;
    const res = document.getElementById('searchResults');
    if (q.length < 2) { res.style.display = 'none'; return; }
    
    try {
        const response = await fetch(`<?= APP_URL ?>/api/frontdesk/students?q=${encodeURIComponent(q)}`);
        const result = await response.json();
        if (result.success && result.data.length > 0) {
            res.innerHTML = result.data.map(s => `
                <div class="search-item" onclick="selectStudent(${JSON.stringify(s).replace(/"/g, '&quot;')})">
                    <div style="width:32px; height:32px; background:#64748b; color:#fff; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px;">${u.name[0]}</div>
                    <div>
                        <div style="font-weight:600; font-size:13px;">${s.name}</div>
                        <div style="font-size:11px; color:#64748b;">${s.roll_no} • ${s.batch_name || 'N/A'}</div>
                    </div>
                </div>
            `).join('');
            res.style.display = 'block';
        }
    } catch (e) {}
}

function selectStudent(s) {
    currentStudent = s;
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('stuSearch').value = '';
    
    document.getElementById('stuAvatar').textContent = u.name[0].toUpperCase();
    document.getElementById('stuName').textContent = u.name;
    document.getElementById('stuMeta').textContent = `${s.roll_no} | ${s.batch_name || 'N/A'}`;
    
    document.getElementById('selectedStudentInfo').style.display = 'block';
    document.getElementById('noStudentState').style.display = 'none';
    document.getElementById('docContainer').style.display = 'block';
    
    loadDocuments();
}

async function loadDocuments() {
    if (!currentStudent) return;
    const list = document.getElementById('docList');
    list.innerHTML = '<div style="padding:40px; text-align:center; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i> Loading documents...</div>';
    
    // In a real app, fetch from API
    // Mocking for demo
    setTimeout(() => {
        const docs = [
            { id: 1, type: 'Citizenship', filename: 'citizen_front.jpg', status: 'verified', date: '2025-05-12' },
            { id: 2, type: 'SEE Transcript', filename: 'transcript_see.pdf', status: 'pending', date: '2025-05-12' },
            { id: 3, type: 'Character Certificate', filename: 'character_cert.pdf', status: 'verified', date: '2025-05-14' }
        ];
        
        list.innerHTML = docs.map(d => `
            <div class="doc-item">
                <div class="doc-ico"><i class="fa-solid ${d.filename.endsWith('pdf') ? 'fa-file-pdf' : 'fa-file-image'}"></i></div>
                <div style="flex:1;">
                    <div style="font-weight:700; color:#1e293b; font-size:14px;">${d.type}</div>
                    <div style="font-size:11px; color:#94a3b8;">${d.filename} • Uploaded on ${d.date}</div>
                </div>
                <div>
                    <span style="font-size:10px; font-weight:700; text-transform:uppercase; padding:4px 10px; border-radius:20px; ${d.status === 'verified' ? 'background:#DCFCE7; color:#166534;' : 'background:#F1F5F9; color:#64748b;'}">
                        ${d.status}
                    </span>
                </div>
                <div style="display:flex; gap:8px;">
                    <button class="btn bt" style="padding:6px 10px;" title="View"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn bt" style="padding:6px 10px; color:#EF4444;" title="Delete"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `).join('');
    }, 500);
}

function openUploadModal() { alert('Opening upload dialog...'); }
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
