<?php
/**
 * Front Desk — ID Card Requests
 * Track and manage student ID cards
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'ID Card Generator';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('admissions');
}
?>

<div class="pg">
    <!-- Page Header -->
    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background:linear-gradient(135deg, #6366F1, #4F46E5);">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <h1 class="pg-title">ID Card Requests</h1>
                <p class="pg-sub">Manage and track student identification cards</p>
            </div>
        </div>
        <div class="pg-acts" style="display:flex; gap:10px;">
            <div style="position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px;"></i>
                <input type="text" id="idSearch" class="fi" placeholder="Search students..." style="width:220px; padding-left:35px;" onkeyup="loadIDCards()">
            </div>
            <select id="statusFilter" class="fi" style="width:160px;" onchange="loadIDCards()">
                <option value="">All Statuses</option>
                <option value="none">Not Requested</option>
                <option value="requested">Requested</option>
                <option value="processing">Processing</option>
                <option value="issued">Issued</option>
            </select>
        </div>
    </div>

    <!-- ID Card Table -->
    <div class="card" style="border-radius:16px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Roll No</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Student Name</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Current Status</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Issued At</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody id="idCardTableBody">
                    <tr>
                        <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            Loading ID card status...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#6366F1; box-shadow:0 0 0 3px rgba(99, 102, 241, 0.1); }
.btn { padding:8px 16px; border-radius:8px; font-weight:600; font-size:12px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:6px; }
.id-tag { font-size:10px; font-weight:800; padding:4px 10px; border-radius:20px; text-transform:uppercase; }
.id-none { background:#F1F5F9; color:#475569; }
.id-requested { background:#DBEAFE; color:#1E40AF; }
.id-processing { background:#FEF3C7; color:#92400E; }
.id-issued { background:#DCFCE7; color:#166534; }
</style>

<script>
async function loadIDCards() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('idSearch').value;
    const tbody = document.getElementById('idCardTableBody');
    
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/id-card-requests?status=${status}&search=${encodeURIComponent(search)}`, getHeaders());
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">No matching student records found.</td></tr>`;
                return;
            }
            
            tbody.innerHTML = data.map(s => {
                const cur = s.id_card_status || 'none';
                let tagClass = 'id-' + cur;
                
                return `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px; font-weight:700; color:#475569;">${esc(s.roll_no)}</td>
                        <td style="padding:14px 16px; font-weight:700; color:#1e293b;">${esc(u.name)}</td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span class="id-tag ${tagClass}">${cur.replace('_', ' ')}</span>
                        </td>
                        <td style="padding:14px 16px; text-align:center; font-size:12px; color:#64748b;">
                            ${s.id_card_issued_at ? new Date(s.id_card_issued_at).toLocaleDateString() : '-'}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                ${cur === 'none' ? `
                                    <button class="btn" style="background:#6366F1; color:#fff;" onclick="updateStatus(${s.id}, 'request')">Request</button>
                                ` : ''}
                                ${cur === 'requested' ? `
                                    <button class="btn" style="background:#F59E0B; color:#fff;" onclick="updateStatus(${s.id}, 'processing')">Process</button>
                                ` : ''}
                                ${cur === 'issued' ? `<div style="display:flex; align-items:center; gap:8px;">
                                    <i class="fa-solid fa-circle-check" title="Issued" style="color:#10B981; font-size:16px;"></i>
                                    <button class="btn" style="background:#0F172A; color:#fff; padding:6px 10px;" onclick="printIDCard(${s.student_id || s.id})" title="Print ID Card">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#ef4444;">Error: ${e.message}</td></tr>`;
    }
}

async function updateStatus(id, action) {
    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/id-card-requests', getHeaders({
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action })
        }));
        const result = await res.json();
        if (result.success) loadIDCards();
    } catch (e) { alert(e.message); }
}

function esc(str) {
    if (!str) return '';
    return str.toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

async function printIDCard(studentId) {
    try {
        if(typeof Swal !== 'undefined') Swal.fire({ title: 'Generating ID Card...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        // Load html2canvas dynamically
        if(typeof html2canvas === 'undefined') {
            await new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                s.onload = resolve;
                s.onerror = () => reject('Failed to load html2canvas');
                document.head.appendChild(s);
            });
        }

        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/students?id=${studentId}&include=details`, getHeaders());
        const result = await res.json();
        if (!result.success || !result.data) {
            if(typeof Swal !== 'undefined') Swal.fire('Error', 'Failed to fetch student details. Note: The frontend uses the unified students API.', 'error');
            else alert('Failed to fetch student details');
            return;
        }

        const s = result.data;
        let photoSrc = '<?= APP_URL ?>/assets/images/user-placeholder.png'; // default
        if (s.photo_url) {
            photoSrc = s.photo_url.startsWith('http') ? s.photo_url : '<?= APP_URL ?>' + (s.photo_url.startsWith('/') ? '' : '/') + s.photo_url;
        }

        let address = 'N/A';
        try {
            let aData = s.permanent_address || s.temporary_address || s.address;
            if (typeof aData === 'string' && aData.trim().startsWith('{')) aData = JSON.parse(aData);
            if (aData && typeof aData === 'object') {
                const muni = aData.municipality || '';
                const ward = aData.ward ? `-${aData.ward}` : '';
                const dist = aData.district ? `, ${aData.district}` : '';
                address = `${muni}${ward}${dist}`.replace(/^[-,\s]+/, '').trim() || 'N/A';
            } else if (aData) {
                address = aData;
            }
        } catch(e) { address = s.permanent_address || 'N/A'; }
        const contact = s.phone || 'N/A';
        const name = s.name || s.full_name || 'N/A';
        const roll = s.roll_no || s.student_id || 'N/A';
        const course = s.course_name || (s.enrollments && s.enrollments.length > 0 ? s.enrollments[0].course_name : 'N/A');
        const batch = s.batch_name || (s.enrollments && s.enrollments.length > 0 ? s.enrollments[0].batch_name : 'N/A');
        
        // --- DYNAMIC INSTITUTE DATA ---
        <?php 
            $iLogo = $_SESSION['tenant_logo'] ?? $_SESSION['institute_logo'] ?? '';
            if ($iLogo && strpos($iLogo, 'http') !== 0) {
                // Strip any legacy /public prefix — production web root IS public/
                if (strpos($iLogo, '/public/') === 0) {
                    $iLogo = substr($iLogo, 7);
                }
                $iLogo = APP_URL . $iLogo;
            }
        ?>
        const instituteName = '<?= addslashes(html_entity_decode($_SESSION['tenant_name'] ?? APP_NAME ?? "Institute Name")) ?>';
        const instituteLogo = '<?= $iLogo ?>';
        // ------------------------------

        const esc = (str) => {
            if (!str) return '';
            return str.toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
        };

        // Hidden wrapping container
        const ghost = document.createElement('div');
        ghost.style.setProperty('--vh', '1vh');
        ghost.style.position = 'fixed';
        ghost.style.top = '-9999px';
        ghost.style.left = '-9999px';
        
        const html = `
        <div id="id-card-capture" style="width: 600px; height: 380px; background: linear-gradient(135deg, #cbeeea 0%, #80b5e2 100%); position: relative; overflow: hidden; border-radius: 12px; font-family: 'Inter', sans-serif; color: #0b114d; box-sizing: border-box;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 80px; background-color: #8cc63f; z-index: 3;"></div>
            
            <svg width="288" height="12" viewBox="0 0 288 12" preserveAspectRatio="none" style="position: absolute; top: 80px; right: 0; z-index: 2; display: block;">
                <polygon points="20,0 288,0 288,12 0,12" fill="#020942" />
            </svg>
            
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 80px; z-index: 4; display: flex; align-items: center; justify-content: center; padding: 0 30px; gap: 15px; text-align: center; box-sizing: border-box;">
                <div style="display: flex; align-items: center; justify-content: center;">
                    ${instituteLogo ? `<img src="${instituteLogo}" style="height: 55px; width: auto; max-width: 100px; object-fit: contain;" crossorigin="anonymous">` : `<i class="fas fa-graduation-cap" style="font-size: 38px; color: #fff;"></i>`}
                </div>
                <div style="color: #fff; display: flex; flex-direction: column; align-items: center;">
                    <h2 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 0.5px;">${esc(instituteName)}</h2>
                </div>
            </div>

            <div style="position: absolute; top: 110px; left: 35px; z-index: 5;">
                <h1 style="font-size: 32px; font-weight: 800; color: #020942; margin: 0 0 15px 0; letter-spacing: 1px;">STUDENT CARD</h1>
                <table style="border-collapse: collapse;">
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173; width: 110px;">Name</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(name)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Roll No</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(roll)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Course</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(course)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Batch</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(batch)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Address</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(address)}</td></tr>
                    <tr><td style="padding: 5px 0; font-size: 17px; font-weight: 600; color: #0f5173;">Contact No</td><td style="width: 20px; text-align: center; color: #0f5173;">:</td><td style="color: #2c3e50; font-size: 17px; font-weight: 600;">${esc(contact)}</td></tr>
                </table>
            </div>

            <div style="position: absolute; top: 100px; right: 40px; width: 160px; height: 180px; background-color: #020942; border-radius: 25px; padding: 5px; z-index: 6; box-sizing: border-box;">
                <div style="width: 100%; height: 100%; border-radius: 20px; border: 4px solid #3cb4cd; padding:0; margin:0; overflow: hidden; background: #fff;">
                    <img id="captureReadyImg" src="${photoSrc}" style="width: 100%; height: 100%; object-fit: cover; display:block;" crossorigin="anonymous">
                </div>
            </div>

            <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 45px; background-color: #9ECCE6; z-index: 1;"></div>
            
            <svg width="220" height="180" viewBox="0 0 220 180" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; z-index: 2; display: block;">
                <polygon points="0,54 220,180 0,180" fill="#020942" />
            </svg>
            <svg width="200" height="120" viewBox="0 0 200 120" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; z-index: 3; display: block;">
                <polygon points="0,24 160,120 0,120" fill="#8cc63f" />
            </svg>
            <svg width="120" height="60" viewBox="0 0 120 60" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; z-index: 4; display: block;">
                <polygon points="0,6 78,60 0,60" fill="#3cb4cd" />
            </svg>
            <svg width="20" height="45" viewBox="0 0 20 45" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 210px; z-index: 2; display: block;">
                <polygon points="6,0 20,45 0,45" fill="#fff" />
            </svg>
        </div>
        `;

        ghost.innerHTML = html;
        document.body.appendChild(ghost);

        const imgEl = ghost.querySelector('#captureReadyImg');
        const targetEl = ghost.querySelector('#id-card-capture');

        const beginCapture = () => {
            html2canvas(targetEl, {
                scale: 3,
                useCORS: true,
                allowTaint: true,
                backgroundColor: null,
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/jpeg', 0.95);
                const a = document.createElement('a');
                a.href = imgData;
                a.download = `ID_Card_${name.replace(/\s+/g, '_')}_${roll}.jpg`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                document.body.removeChild(ghost);
                if(typeof Swal !== 'undefined') Swal.close();
            }).catch(e => {
                console.error('html2canvas err:', e);
                document.body.removeChild(ghost);
                if(typeof Swal !== 'undefined') Swal.fire('Error', 'Failed to generate visual ID.', 'error');
            });
        };

        if(imgEl.complete) {
            beginCapture();
        } else {
            imgEl.onload = beginCapture;
            imgEl.onerror = () => {
                console.warn('Image failed to load via CORS, rendering placeholder instead.');
                imgEl.src = '<?= APP_URL ?>/assets/images/user-placeholder.png'; // Fallback
                setTimeout(beginCapture, 1000);
            };
        }

    } catch (err) {
        console.error(err);
        if(typeof Swal !== 'undefined') Swal.fire('Error', 'Server Error. Check console logs.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', loadIDCards);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
