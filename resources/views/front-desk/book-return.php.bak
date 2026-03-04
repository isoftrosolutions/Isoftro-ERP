<?php
/**
 * Front Desk — Library: Book Return
 * Interface for checking in books and managing fines
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Return Book';
require_once VIEWS_PATH . '/layouts/header_1.php';
require_once __DIR__ . '/sidebar.php';
?>

<?php renderFrontDeskHeader(); ?>
<?php renderFrontDeskSidebar('library'); ?>

<main class="main" id="mainContent">
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #059669);">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <div>
                    <h1 class="pg-title">Book Return</h1>
                    <p class="pg-sub">Process library returns and collect fines</p>
                </div>
            </div>
            <div class="pg-acts">
                <a href="<?= APP_URL ?>/dash/front-desk/book-issue" class="btn bt">
                    <i class="fa-solid fa-book-medical"></i> Issue Book
                </a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 24px;">
            <!-- Left: Search/Scan -->
            <div>
                <div class="card" style="padding: 20px; border-radius: 16px;">
                    <h3 style="font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px;">Return Search</h3>
                    
                    <div style="margin-bottom: 20px;">
                        <label class="fl">Search Mode</label>
                        <select id="searchMode" class="fi" style="margin-top:5px;">
                            <option value="book">By Book (Accession No)</option>
                            <option value="student">By Student (Name/Roll No)</option>
                        </select>
                    </div>

                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="searchInput" class="fi" placeholder="Scan or type..." style="padding-left: 36px;" oninput="handleSearch()">
                        <div id="resultsDropdown" class="search-dd"></div>
                    </div>

                    <div id="activeItem" style="display:none; margin-top:24px; padding:15px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;">
                        <div style="font-size:11px; color:#64748b; margin-bottom:5px;">Currently Selected:</div>
                        <div style="font-weight:700; color:#1a1a2e;" id="itemName">-</div>
                        <div style="font-size:12px; color:#64748b;" id="itemMeta">-</div>
                    </div>
                </div>
            </div>

            <!-- Right: Return Table -->
            <div>
                <div class="card" style="border-radius:16px; overflow:hidden;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                                <th style="padding:14px; text-align:left; font-size:12px; color:#64748b;">Book Details</th>
                                <th style="padding:14px; text-align:left; font-size:12px; color:#64748b;">Issued To</th>
                                <th style="padding:14px; text-align:left; font-size:12px; color:#64748b;">Due Date</th>
                                <th style="padding:14px; text-align:right; font-size:12px; color:#64748b;">Fine Due</th>
                                <th style="padding:14px; text-align:center; font-size:12px; color:#64748b;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="issueList">
                            <tr>
                                <td colspan="5" style="padding:60px; text-align:center; color:#94a3b8;">
                                    <i class="fa-solid fa-search" style="font-size:32px; opacity:0.2; margin-bottom:10px; display:block;"></i>
                                    Search for a book or student to see active issues
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="returnSummary" style="display:none; margin-top:20px; padding:20px; background:#F0FDF4; border:1.5px solid #BBF7D0; border-radius:12px; display:none; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#166534;">Total Fine Collected</div>
                        <div style="font-size:24px; font-weight:800; color:#14532D;" id="totalFineLabel">NPR 0.00</div>
                    </div>
                    <button class="btn" style="background:#10B981; color:#fff; padding:12px 24px;" onclick="processReturn()">
                        <i class="fa-solid fa-check-circle"></i> Complete Return
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.fl { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.search-dd { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; max-height: 250px; overflow-y: auto; z-index: 100; display: none; }
.search-item { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s; }
.search-item:hover { background: #f8fafc; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
</style>

<script>
let activeIssues = [];

function handleSearch() {
    const q = document.getElementById('searchInput').value;
    const res = document.getElementById('resultsDropdown');
    const mode = document.getElementById('searchMode').value;
    
    if (q.length < 2) { res.style.display = 'none'; return; }
    
    // Mocking search results
    if (mode === 'book') {
        res.innerHTML = `
            <div class="search-item" onclick="selectItem({id:1, name:'Advanced Mathematics (M-102)', meta:'Issued to: Rajesh Hamal'})">
                <div style="font-weight:600; font-size:13px;">Advanced Mathematics (M-102)</div>
                <div style="font-size:11px; color:#64748b;">Issued to: Rajesh Hamal (Roll 101)</div>
            </div>
        `;
    } else {
        res.innerHTML = `
            <div class="search-item" onclick="selectItem({id:101, name:'Rajesh Hamal', meta:'3 books currently issued'})">
                <div style="font-weight:600; font-size:13px;">Rajesh Hamal (101)</div>
                <div style="font-size:11px; color:#64748b;">Batch: BBA II • 3 books issued</div>
            </div>
        `;
    }
    res.style.display = 'block';
}

function selectItem(item) {
    document.getElementById('resultsDropdown').style.display = 'none';
    document.getElementById('searchInput').value = '';
    
    document.getElementById('itemName').textContent = item.name;
    document.getElementById('itemMeta').textContent = item.meta;
    document.getElementById('activeItem').style.display = 'block';
    
    loadIssues(item.id);
}

function loadIssues(id) {
    const tbody = document.getElementById('issueList');
    tbody.innerHTML = `
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:14px;">
                <div style="font-weight:700; font-size:13px;">Advanced Mathematics</div>
                <div style="font-size:11px; color:#64748b;">Acc: M-102 • Vol 1</div>
            </td>
            <td style="padding:14px; font-size:13px;">Rajesh Hamal</td>
            <td style="padding:14px;">
                <div style="font-size:13px;">2026-03-01</div>
                <span style="background:#FEE2E2; color:#B91C1C; font-size:9px; padding:2px 6px; border-radius:10px; font-weight:700;">OVERDUE</span>
            </td>
            <td style="padding:14px; text-align:right; font-weight:800; color:#EF4444;">NPR 50.00</td>
            <td style="padding:14px; text-align:center;">
                <input type="checkbox" onchange="updateSummary()" style="width:18px; height:18px;">
            </td>
        </tr>
    `;
}

function updateSummary() {
    const checked = document.querySelectorAll('#issueList input[type="checkbox"]:checked').length;
    document.getElementById('returnSummary').style.display = checked > 0 ? 'flex' : 'none';
    document.getElementById('totalFineLabel').textContent = checked > 0 ? 'NPR 50.00' : 'NPR 0.00';
}

function processReturn() {
    alert('Processing return and collecting fine...');
    window.location.reload();
}
</script>

<?php
renderSuperAdminCSS();
echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
?>
</body>
</html>
