<?php
/**
 * Front Desk — Library: Book Issue
 * Interface for checking out books to students
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Issue Book';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('library');
}
?>
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #6366F1, #4F46E5);">
                    <i class="fa-solid fa-book-medical"></i>
                </div>
                <div>
                    <h1 class="pg-title">Issue Book</h1>
                    <p class="pg-sub">Check out library assets to students</p>
                </div>
            </div>
            <div class="pg-acts">
                <a href="<?= APP_URL ?>/dash/front-desk/book-return" class="btn bt">
                    <i class="fa-solid fa-rotate-left"></i> Book Return
                </a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 24px;">
            <!-- Left: Student & Book Selection -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Student Selection -->
                <div class="card" style="padding: 20px; border-radius: 16px;">
                    <h3 style="font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-user-graduate" style="color: #6366F1;"></i> 1. Select Student
                    </h3>
                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="stuSearch" class="fi" placeholder="Student Name or Roll No..." style="padding-left: 36px;" oninput="searchStudents()">
                        <div id="stuResults" class="search-dd"></div>
                    </div>
                    <div id="selectedStu" style="display: none; margin-top: 15px; padding: 12px; background: #EEF2FF; border-radius: 10px; border: 1px solid #C7D2FE; display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; background: #6366F1; color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700;" id="sAvatar">?</div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 13px;" id="sName">-</div>
                            <div style="font-size: 11px; color: #4F46E5;" id="sMeta">-</div>
                        </div>
                        <i class="fa-solid fa-circle-check" style="color: #10B981;"></i>
                    </div>
                </div>

                <!-- Book Selection -->
                <div class="card" style="padding: 20px; border-radius: 16px;">
                    <h3 style="font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-book" style="color: #6366F1;"></i> 2. Select Book
                    </h3>
                    <div style="position: relative;">
                        <i class="fa-solid fa-barcode" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="bookSearch" class="fi" placeholder="Book Title or Accession No..." style="padding-left: 36px;" oninput="searchBooks()">
                        <div id="bookResults" class="search-dd"></div>
                    </div>
                    <div id="selectedBook" style="display: none; margin-top: 15px; padding: 12px; background: #F8FAFC; border-radius: 10px; border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 40px; background: #94A3B8; color: #fff; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 16px;"><i class="fa-solid fa-book"></i></div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 13px;" id="bTitle">-</div>
                            <div style="font-size: 11px; color: #64748B;" id="bMeta">-</div>
                        </div>
                        <i class="fa-solid fa-check" style="color: #10B981;"></i>
                    </div>
                </div>
            </div>

            <!-- Right: Action Panel -->
            <div>
                <div class="card" style="padding: 24px; border-radius: 16px; height: 100%;">
                    <h3 style="font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">Issue Details</h3>
                    
                    <form id="issueForm" onsubmit="handleIssue(event)">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                            <div class="form-group">
                                <label class="fl">Issue Date</label>
                                <input type="date" name="issue_date" class="fi" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label class="fl">Expiry / Due Date</label>
                                <input type="date" name="due_date" class="fi" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 24px;">
                            <label class="fl">Remarks (Optional)</label>
                            <textarea name="remarks" class="fi" style="height: 100px; resize: none;" placeholder="Condition of the book or other notes..."></textarea>
                        </div>

                        <div style="padding: 20px; background: #EEF2FF; border-radius: 12px; margin-top: 40px; text-align: center;">
                            <p style="font-size: 12px; color: #4F46E5; margin-bottom: 15px;">Confirm details before issuing. Library policies apply.</p>
                            <button type="submit" class="btn" style="background: linear-gradient(135deg, #6366F1, #4F46E5); color: #fff; width: 100%; justify-content: center; padding: 14px;" id="issueBtn" disabled>
                                <i class="fa-solid fa-check-double"></i> Confirm & Issue Book
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<style>
.fl { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
.fi { width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#6366F1; box-shadow:0 0 0 3px rgba(99, 102, 241, 0.1); }
.search-dd { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; max-height: 250px; overflow-y: auto; z-index: 100; display: none; }
.search-item { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s; display: flex; align-items: center; gap: 12px; }
.search-item:hover { background: #f8fafc; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
</style>

<script>
let selStu = null;
let selBook = null;

async function searchStudents() {
    const q = document.getElementById('stuSearch').value;
    const res = document.getElementById('stuResults');
    if (q.length < 2) { res.style.display = 'none'; return; }
    try {
        const response = await fetch(`<?= APP_URL ?>/api/frontdesk/students?q=${encodeURIComponent(q)}`);
        const result = await response.json();
        if (result.success && result.data.length > 0) {
            res.innerHTML = result.data.map(s => `
                <div class="search-item" onclick="selectStudent(${JSON.stringify(s).replace(/"/g, '&quot;')})">
                    <div style="width:32px; height:32px; background:#6366F1; color:#fff; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px;">${(s.name || 'S')[0]}</div>
                    <div style="flex:1;">
                        <div style="font-weight:600; font-size:13px;">${s.name || 'N/A'}</div>
                        <div style="font-size:11px; color:#64748b;">${s.roll_no} • ${s.batch_name || 'N/A'}</div>
                    </div>
                </div>
            `).join('');
            res.style.display = 'block';
        }
    } catch (e) {}
}

async function searchBooks() {
    const q = document.getElementById('bookSearch').value;
    const res = document.getElementById('bookResults');
    if (q.length < 2) { res.style.display = 'none'; return; }
    // Mocking book search for now
    res.innerHTML = `
        <div class="search-item" onclick="selectBook({id:1, title:'Advanced Mathematics', author:'R.D. Sharma', accession:'M-102'})">
            <div style="flex:1;">
                <div style="font-weight:600; font-size:13px;">Advanced Mathematics</div>
                <div style="font-size:11px; color:#64748b;">Acc No: M-102 • Author: R.D. Sharma</div>
            </div>
            <span style="background:#DCFCE7; color:#166534; font-size:9px; padding:2px 6px; border-radius:10px; font-weight:700;">AVAIL</span>
        </div>
        <div class="search-item" onclick="selectBook({id:2, title:'Digital Logic Design', author:'Morris Mano', accession:'D-201'})">
            <div style="flex:1;">
                <div style="font-weight:600; font-size:13px;">Digital Logic Design</div>
                <div style="font-size:11px; color:#64748b;">Acc No: D-201 • Author: Morris Mano</div>
            </div>
            <span style="background:#DCFCE7; color:#166534; font-size:9px; padding:2px 6px; border-radius:10px; font-weight:700;">AVAIL</span>
        </div>
    `;
    res.style.display = 'block';
}

function selectStudent(s) {
    selStu = s;
    document.getElementById('stuResults').style.display = 'none';
    document.getElementById('stuSearch').value = '';
    document.getElementById('sAvatar').textContent = (s.name || 'S')[0].toUpperCase();
    document.getElementById('sName').textContent = s.name || 'N/A';
    document.getElementById('sMeta').textContent = `${s.roll_no} | ${s.batch_name || 'N/A'}`;
    document.getElementById('selectedStu').style.display = 'flex';
    checkReady();
}

function selectBook(b) {
    selBook = b;
    document.getElementById('bookResults').style.display = 'none';
    document.getElementById('bookSearch').value = '';
    document.getElementById('bTitle').textContent = b.title;
    document.getElementById('bMeta').textContent = `Acc: ${b.accession} | Author: ${b.author}`;
    document.getElementById('selectedBook').style.display = 'flex';
    checkReady();
}

function checkReady() {
    document.getElementById('issueBtn').disabled = !(selStu && selBook);
}

function handleIssue(e) {
    e.preventDefault();
    alert('Issuing "' + selBook.title + '" to ' + (selStu.name || 'Student'));
    // In real app, call API
    window.location.reload();
}
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
