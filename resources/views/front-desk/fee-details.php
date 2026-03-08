<?php
/**
 * Front Desk — Fee Payment Details
 * Shows summary after payment and provides actions (Print/Email)
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}
?>

<div class="pg">
    <div class="bc">
        <a href="javascript:goNav('dashboard')">Dashboard</a>
        <span class="bc-sep">/</span>
        <a href="javascript:goNav('fee','fee-coll')">Fee Collection</a>
        <span class="bc-sep">/</span>
        <span class="bc-cur">Payment Details</span>
    </div>

    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #059669);">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <h1 class="pg-title">Payment Successful</h1>
                <p class="pg-sub">Transaction completed and logged to ledger</p>
            </div>
        </div>
    </div>

    <div class="g65">
        <div class="card" style="padding:0; overflow:hidden;">
            <div style="background:#f8fafc; padding:20px; border-bottom:1px solid #f1f5f9;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Receipt Summary</div>
                    <div id="receiptNoChip" style="background:#DBEAFE; color:#1E40AF; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700;">...</div>
                </div>
            </div>
            
            <div style="padding:24px;" id="receiptDetailsContent">
                <div class="skeleton" style="height:20px; width:60%; margin-bottom:15px;"></div>
                <div class="skeleton" style="height:20px; width:40%; margin-bottom:15px;"></div>
                <div class="skeleton" style="height:20px; width:70%; margin-bottom:15px;"></div>
            </div>

            <div style="padding:24px; border-top:1px solid #f1f5f9; display:grid; grid-template-columns:1fr 1fr; gap:12px;" id="actionButtons" style="display:none;">
                <button class="btn bt" onclick="printReceipt()" style="justify-content:center;">
                    <i class="fa-solid fa-print"></i> Print Receipt
                </button>
                <button class="btn bt" onclick="downloadReceipt()" style="justify-content:center;">
                    <i class="fa-solid fa-file-pdf"></i> Download PDF
                </button>
                <button class="btn bt" onclick="sendReceiptEmail()" id="emailBtn" style="justify-content:center; grid-column: span 2;">
                    <i class="fa-solid fa-envelope"></i> Send Email to Student
                </button>
            </div>
        </div>

        <div class="card" style="height:fit-content;">
            <div class="card-header pb">
                <div class="ct"><i class="fa-solid fa-lightbulb"></i> Next Actions</div>
            </div>
            <div class="tw" style="padding:15px; display:flex; flex-direction:column; gap:10px;">
                <button class="btn bs" onclick="goNav('fee', 'fee-coll')" style="width:100%; border:1.5px solid #e2e8f0;">
                    <i class="fa-solid fa-plus"></i> Collect Another Payment
                </button>
                <button class="btn bs" onclick="goNav('fee', 'fee-sum')" style="width:100%; border:1.5px solid #e2e8f0;">
                    <i class="fa-solid fa-list-ul"></i> View All Receipts
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const receiptNo = urlParams.get('receipt_no');
    
    if (!receiptNo) {
        document.getElementById('receiptDetailsContent').innerHTML = '<div class="alert alert-danger">No receipt number provided.</div>';
        return;
    }

    document.getElementById('receiptNoChip').textContent = '#' + receiptNo;

    async function loadDetails() {
        try {
            const res = await fetch(`<?= APP_URL ?>/api/frontdesk/fees?action=get_receipt_details&receipt_no=${receiptNo}`, getHeaders());
            const result = await res.json();
            
            if (result.success) {
                const data = result.data;
                const date = new Date(data.payment_date).toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'});
                
                document.getElementById('receiptDetailsContent').innerHTML = `
                    <div style="margin-bottom:20px;">
                        <div style="font-size:13px; color:#64748b; margin-bottom:4px;">Student Name</div>
                        <div style="font-size:18px; font-weight:700; color:#1e293b;">${data.student_name}</div>
                        <div style="font-size:12px; color:#94a3b8;">Roll No: ${data.roll_no || 'N/A'}</div>
                    </div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div>
                            <div style="font-size:13px; color:#64748b; margin-bottom:4px;">Amount Paid</div>
                            <div style="font-size:24px; font-weight:800; color:#10B981;">Rs. ${parseFloat(data.amount).toLocaleString('en-NP', {minimumFractionDigits: 2})}</div>
                        </div>
                        <div>
                            <div style="font-size:13px; color:#64748b; margin-bottom:4px;">Payment Date</div>
                            <div style="font-size:15px; font-weight:600; color:#1e293b;">${date}</div>
                            <div style="font-size:12px; color:#94a3b8;">via ${data.payment_method.toUpperCase()}</div>
                        </div>
                    </div>
                `;
                document.getElementById('actionButtons').style.display = 'grid';
                window.currentTransactionId = data.id;
                window.receiptPdfPath = data.pdf_path;
            } else {
                throw new Error(result.message);
            }
        } catch (e) {
            document.getElementById('receiptDetailsContent').innerHTML = `<div class="alert alert-danger">${e.message}</div>`;
        }
    }

    window.printReceipt = function() {
        window.open(`<?= APP_URL ?>/api/frontdesk/fees?action=generate_receipt_html&receipt_no=${receiptNo}`, '_blank');
    };

    window.downloadReceipt = function() {
        window.open(`<?= APP_URL ?>/api/frontdesk/fees?action=generate_receipt_html&is_pdf=1&receipt_no=${receiptNo}`, '_blank');
    };

    window.sendReceiptEmail = async function() {
        const btn = document.getElementById('emailBtn');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Sending...';

        try {
            const res = await fetch(`<?= APP_URL ?>/api/frontdesk/fees`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({ action: 'trigger_email', receipt_no: receiptNo })
            });
            const result = await res.json();
            
            if (result.success) {
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Email Queued!';
                btn.classList.replace('bt', 'bs');
                btn.style.background = '#DCFCE7';
                btn.style.color = '#166534';
                btn.style.borderColor = '#166534';
            } else {
                throw new Error(result.message);
            }
        } catch (e) {
            btn.innerHTML = '<i class="fa-solid fa-xmark"></i> Failed';
            alert(e.message);
        } finally {
            setTimeout(() => {
                btn.disabled = false;
                if (!btn.innerHTML.includes('Queued')) btn.innerHTML = originalHtml;
            }, 3000);
        }
    };

    loadDetails();
})();
</script>
