<?php
/**
 * Front Desk — Fee Collection
 * Streamlined interface for recording student payments
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Collect Fee';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';

}
$studentId = $_GET['student_id'] ?? null;
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('fees');
}
?>
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div>
                <h1 class="pg-title">Fee Collection</h1>
                <p class="pg-sub">Record payments and generate instant receipts</p>
            </div>
            <div class="pg-acts">
                <a href="<?= APP_URL ?>/dash/front-desk/index?page=fee-outstanding" class="btn bt">
                    <i class="fa-solid fa-clock"></i> Outstanding Fees
                </a>
                <a href="<?= APP_URL ?>/dash/front-desk/index?page=fee-receipts" class="btn bt">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Recent Receipts
                </a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 24px;">
            <!-- Left: Student Selection -->
            <div>
                <div class="card" style="padding: 20px; border-radius: 16px; position: sticky; top: 20px;">
                    <h3 style="font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-user-graduate" style="color: #F59E0B;"></i> Select Student
                    </h3>
                    
                    <div style="position: relative; margin-bottom: 20px;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="studentSearch" class="fi" placeholder="Name or Roll No..." style="padding-left: 36px;" oninput="searchStudents()">
                        <div id="searchResults" class="search-dd"></div>
                    </div>

                    <div id="selectedStudentCard" style="display: none;">
                        <div style="padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div id="stuAvatar" style="width: 44px; height: 44px; border-radius: 10px; background: #6C5CE7; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">?</div>
                                <div>
                                    <div id="stuName" style="font-weight: 700; color: #1a1a2e; font-size: 14px;">-</div>
                                    <div id="stuDetails" style="font-size: 12px; color: #64748b;">-</div>
                                </div>
                            </div>
                            <button class="btn bt" style="width: 100%; justify-content: center; margin-top: 15px; padding: 8px;" onclick="clearStudent()">
                                <i class="fa-solid fa-xmark"></i> Change Student
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Fee Calculation & Payment -->
            <div>
                <div id="feeModuleContainer">
                    <!-- Default State -->
                    <div class="card" id="noStudentState" style="text-align: center; padding: 100px 40px; border-radius: 16px;">
                        <div style="width: 80px; height: 80px; background: #FFF7ED; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <i class="fa-solid fa-user-plus" style="font-size: 32px; color: #F59E0B;"></i>
                        </div>
                        <h2>No Student Selected</h2>
                        <p style="color: #64748b; margin-top: 10px;">Search and select a student from the left panel to begin fee collection.</p>
                    </div>

                    <!-- Fee Collection Form (hidden by default) -->
                    <div id="collectionForm" style="display: none;">
                        <div class="card mb" style="padding: 24px; border-radius: 16px; margin-bottom: 24px;">
                            <h3 style="font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px;">Outstanding Items</h3>
                            <div id="outstandingList">
                                <!-- Items injected here -->
                            </div>
                        </div>

                        <div class="card" style="padding: 24px; border-radius: 16px;">
                            <h3 style="font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 20px;">Payment Details</h3>
                            <form id="paymentForm" onsubmit="recordPayment(event)">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                                    <div class="form-group">
                                        <label class="fl">Amount to Pay (NPR) <span style="color:var(--red);">*</span></label>
                                        <input type="number" name="amount" id="payAmount" class="fi" style="font-size: 20px; font-weight: 700; color: #10B981;" required step="0.01">
                                    </div>
                                    <div class="form-group">
                                        <label class="fl">Payment Method <span style="color:var(--red);">*</span></label>
                                        <select name="payment_method" class="fi" required>
                                            <option value="cash">Cash</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="check">Check / Cheque</option>
                                            <option value="esewa">eSewa</option>
                                            <option value="khalti">Khalti</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="fl">Payment Date</label>
                                        <input type="date" name="payment_date" class="fi" value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="fl">Discount (if any)</label>
                                        <input type="number" name="discount" class="fi" placeholder="0.00" step="0.01">
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom: 24px;">
                                    <label class="fl">Remarks / Notes</label>
                                    <textarea name="remarks" class="fi" style="height: 80px; resize: none;" placeholder="Optional payment notes..."></textarea>
                                </div>
                                
                                <div style="padding: 20px; background: #f8fafc; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-size: 12px; color: #64748b;">Total Amount Due</div>
                                        <div style="font-size: 24px; font-weight: 800; color: #1a1a2e;" id="totalDueLabel">NPR 0.00</div>
                                    </div>
                                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #F59E0B, #D97706); color: #fff; padding: 14px 30px; font-size: 16px;" id="payBtn">
                                        <i class="fa-solid fa-receipt"></i> Process Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Success Modal / Receipt Overlay -->
<div id="receiptOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:9000; display:none; align-items:center; justify-content:center; padding:20px;">
    <div class="card" style="width:100%; max-width:500px; padding:30px; border-radius:20px; text-align:center;">
        <div style="width:70px; height:70px; background:#DCFCE7; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
            <i class="fa-solid fa-check" style="font-size:35px; color:#166534;"></i>
        </div>
        <h2 style="color:#166534;">Payment Recorded!</h2>
        <p style="margin:10px 0 24px; color:#64748b;">Receipt successfully generated and emailed to student.</p>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <button class="btn bt" onclick="window.location.reload()"><i class="fa-solid fa-plus"></i> New Payment</button>
            <button class="btn" style="background:#1a1a2e; color:#fff;" id="printReceiptBtn"><i class="fa-solid fa-print"></i> Print Receipt</button>
        </div>
    </div>
</div>

<style>
.fl { display: block; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
.fi { width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#F59E0B; box-shadow:0 0 0 3px rgba(245, 158, 11, 0.1); }
.search-dd { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; max-height: 300px; overflow-y: auto; z-index: 100; display: none; }
.search-item { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s; display: flex; align-items: center; gap: 10px; }
.search-item:hover { background: #f8fafc; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; }
.fee-row { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #f1f5f9; }
.fee-row:last-child { border-bottom: none; }
</style>

<script>
let currentStudent = null;
let outstandingItems = [];

async function searchStudents() {
    const query = document.getElementById('studentSearch').value;
    const results = document.getElementById('searchResults');
    
    if (query.length < 2) {
        results.style.display = 'none';
        return;
    }
    
    try {
        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/students?q=${encodeURIComponent(query)}`);
        const result = await res.json();
        
        if (result.success && result.data.length > 0) {
            results.innerHTML = result.data.map(s => `
                <div class="search-item" onclick="selectStudent(${JSON.stringify(s).replace(/"/g, '&quot;')})">
                    <div style="width:32px; height:32px; border-radius:8px; background:#6C5CE7; color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px;">${s.full_name[0]}</div>
                    <div>
                        <div style="font-weight:600; font-size:13px;">${s.full_name}</div>
                        <div style="font-size:11px; color:#64748b;">${s.roll_no} • ${s.batch_name || 'N/A'}</div>
                    </div>
                </div>
            `).join('');
            results.style.display = 'block';
        } else {
            results.innerHTML = '<div style="padding:15px; text-align:center; color:#94a3b8; font-size:13px;">No students found</div>';
            results.style.display = 'block';
        }
    } catch (e) {}
}

async function selectStudent(student) {
    currentStudent = student;
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('studentSearch').value = '';
    
    document.getElementById('stuAvatar').textContent = student.full_name[0].toUpperCase();
    document.getElementById('stuName').textContent = student.full_name;
    document.getElementById('stuDetails').textContent = `${student.roll_no} • ${student.batch_name || 'N/A'}`;
    
    document.getElementById('selectedStudentCard').style.display = 'block';
    document.getElementById('noStudentState').style.display = 'none';
    document.getElementById('collectionForm').style.display = 'block';
    
    loadOutstandingFees(student.id);
}

function clearStudent() {
    currentStudent = null;
    document.getElementById('selectedStudentCard').style.display = 'none';
    document.getElementById('noStudentState').style.display = 'block';
    document.getElementById('collectionForm').style.display = 'none';
}

async function loadOutstandingFees(studentId) {
    const list = document.getElementById('outstandingList');
    list.innerHTML = '<div style="padding:20px; text-align:center; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i> Loading fees...</div>';
    
    try {
            const res = await fetch(`<?= APP_URL ?>/api/frontdesk/fees?action=get_outstanding&student_id=${studentId}`);
            const result = await res.json();
            
            if (result.success) {
                outstandingItems = result.data || [];
                if (outstandingItems.length === 0) {
                    list.innerHTML = '<div style="padding:20px; text-align:center; color:#10B981; font-weight:600;"><i class="fa-solid fa-check-circle"></i> No outstanding fees!</div>';
                    document.getElementById('totalDueLabel').textContent = 'NPR 0.00';
                    document.getElementById('payAmount').value = '';
                    return;
                }
                
                let total = 0;
                list.innerHTML = outstandingItems.map(i => {
                    const balance = parseFloat(i.amount_due) - parseFloat(i.amount_paid);
                    total += balance;
                    return `
                        <div class="fee-row">
                            <div>
                                <div style="font-weight:600; font-size:14px;">${i.fee_name}</div>
                                <div style="font-size:11px; color:#64748b;">Due Date: ${i.due_date}</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700; color:#ef4444;">NPR ${balance.toLocaleString()}</div>
                                <div style="font-size:11px; color:#94a3b8;">Total: ${i.amount_due}</div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                document.getElementById('totalDueLabel').textContent = 'NPR ' + total.toLocaleString();
                document.getElementById('payAmount').value = total;
            }
        } catch (e) {
            list.innerHTML = '<div style="padding:20px; text-align:center; color:#ef4444;">Error loading fees</div>';
        }
    }
    
    async function recordPayment(e) {
        e.preventDefault();
        if (!currentStudent) return;
        
        const btn = document.getElementById('payBtn');
        const form = e.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.action = 'record_payment';
        data.student_id = currentStudent.id;
        
        try {
            const res = await fetch('<?= APP_URL ?>/api/frontdesk/fees', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await res.json();
            if (result.success) {
                const overlay = document.getElementById('receiptOverlay');
                const statusP = overlay.querySelector('p');
                const emailStatus = result.data.email_status;
                const receiptNo = result.data.receipt_no;
                const transactionId = result.data.transaction_id;
                
                if (emailStatus === 'sent') {
                    statusP.innerHTML = `Receipt <strong>#${receiptNo}</strong> successfully generated and emailed to student.`;
                    statusP.style.color = '#166534';
                } else if (emailStatus === 'no_email') {
                    statusP.innerHTML = `Receipt <strong>#${receiptNo}</strong> generated. Email not sent (Student has no email address).`;
                    statusP.style.color = '#92400E';
                } else {
                    statusP.innerHTML = `Receipt <strong>#${receiptNo}</strong> generated. <span style="color:#ef4444;">Email delivery failed.</span>`;
                }
                
                overlay.style.display = 'flex';
                document.getElementById('printReceiptBtn').onclick = () => {
                    window.open(`<?= APP_URL ?>/api/frontdesk/fees?action=generate_receipt_html&receipt_no=${receiptNo}`, '_blank');
                };
            }
 else {
                alert('Error: ' + result.message);
            }
        } catch (e) {
            alert('Internal server error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-receipt"></i> Process Payment';
        }
    }

// Handle URL param student_id
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const sid = urlParams.get('student_id');
    if (sid) {
        // Fetch student details first
        fetch(`<?= APP_URL ?>/api/frontdesk/students?id=${sid}`)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) selectStudent(res.data);
            });
    }
});
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
