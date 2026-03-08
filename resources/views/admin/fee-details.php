<?php
/**
 * Admin Fee Details View
 * Displays receipt summary and actions after payment
 */
?>

<div id="feeDetailsContainer" class="fade-in">
    <div class="pg-loading">
        <i class="fa-solid fa-circle-notch fa-spin"></i>
        <span>Loading Payment Details...</span>
    </div>
</div>

<script>
(function() {
    const urlParams = new URLSearchParams(window.location.hash.split('?')[1]);
    const receiptNo = urlParams.get('receipt_no');

    if (!receiptNo) {
        document.getElementById('feeDetailsContainer').innerHTML = `
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i> Receipt number missing.
            </div>`;
        return;
    }

    async function loadDetails() {
        try {
            const res = await fetch(`${APP_URL}/api/admin/fees?action=get_payment_details&receipt_no=${receiptNo}`);
            const result = await res.json();
            
            if (!result.success) throw new Error(result.message);
            
            renderDetails(result.data.transaction);
        } catch (e) {
            document.getElementById('feeDetailsContainer').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> ${e.message}
                </div>`;
        }
    }

    function renderDetails(txn) {
        const container = document.getElementById('feeDetailsContainer');
        container.innerHTML = `
            <div class="fee-details-card">
                <div class="fd-header">
                    <div class="fd-title">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Payment Receipt Summary</span>
                    </div>
                    <div class="fd-status-badge">SUCCESS</div>
                </div>

                <div class="fd-body">
                    <div class="fd-main-info">
                        <div class="fd-amount-box">
                            <span class="label">Amount Paid</span>
                            <span class="value">${getCurrencySymbol()}${parseFloat(txn.amount).toLocaleString()}</span>
                        </div>
                        <div class="fd-receipt-box">
                            <span class="label">Receipt No.</span>
                            <span class="value">${txn.receipt_number}</span>
                        </div>
                    </div>

                    <div class="fd-grid">
                        <div class="fd-item">
                            <span class="label">Student Name</span>
                            <span class="value">${txn.student_name}</span>
                        </div>
                        <div class="fd-item">
                            <span class="label">Course / Batch</span>
                            <span class="value">${txn.course_name} • ${txn.batch_name}</span>
                        </div>
                        <div class="fd-item">
                            <span class="label">Date</span>
                            <span class="value">${new Date(txn.payment_date).toLocaleDateString()}</span>
                        </div>
                        <div class="fd-item">
                            <span class="label">Method</span>
                            <span class="value" style="text-transform:capitalize;">${txn.payment_method.replace('_', ' ')}</span>
                        </div>
                    </div>
                </div>

                <div class="fd-actions">
                    <button class="btn bt" onclick="window.openReceipt('${txn.receipt_number}')">
                        <i class="fa-solid fa-print"></i> Print Receipt
                    </button>
                    <button class="btn bs" onclick="downloadReceiptPdf('${txn.receipt_number}')">
                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                    </button>
                    <button class="btn bs" id="btnResendEmail" onclick="triggerEmailReceipt('${txn.receipt_number}')">
                        <i class="fa-solid fa-envelope"></i> Send Email
                    </button>
                </div>
                
                <div class="fd-footer">
                    <button class="btn-text" onclick="goNav('fees/record')">
                        <i class="fa-solid fa-arrow-left"></i> Back to Record Payment
                    </button>
                </div>
            </div>

            <style>
                .fee-details-card {
                    max-width: 600px;
                    margin: 40px auto;
                    background: #fff;
                    border-radius: 16px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
                    overflow: hidden;
                    animation: slideUp 0.4s ease;
                }
                .fd-header {
                    padding: 20px 30px;
                    background: #f8fafc;
                    border-bottom: 1px solid #edf2f7;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .fd-title {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    font-weight: 700;
                    color: #1a202c;
                    font-size: 1.1rem;
                }
                .fd-title i { color: var(--primary); }
                .fd-status-badge {
                    background: #c6f6d5;
                    color: #22543d;
                    padding: 4px 12px;
                    border-radius: 999px;
                    font-size: 0.75rem;
                    font-weight: 800;
                }
                .fd-body { padding: 30px; }
                .fd-main-info {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 30px;
                    padding-bottom: 25px;
                    border-bottom: 1px dashed #e2e8f0;
                }
                .fd-main-info .label {
                    display: block;
                    font-size: 0.8rem;
                    color: #718096;
                    margin-bottom: 5px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .fd-amount-box .value {
                    font-size: 2rem;
                    font-weight: 800;
                    color: #2d3748;
                }
                .fd-receipt-box { text-align: right; }
                .fd-receipt-box .value {
                    font-size: 1.2rem;
                    font-weight: 700;
                    color: #4a5568;
                }
                .fd-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                }
                .fd-item .label {
                    display: block;
                    font-size: 0.75rem;
                    color: #a0aec0;
                    margin-bottom: 3px;
                }
                .fd-item .value {
                    font-weight: 600;
                    color: #2d3748;
                }
                .fd-actions {
                    padding: 25px 30px;
                    background: #f8fafc;
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 12px;
                }
                @media (min-width: 480px) {
                    .fd-actions { grid-template-columns: 1fr 1fr; }
                    .fd-actions .btn:first-child { grid-column: span 2; }
                }
                .fd-footer {
                    padding: 15px 30px;
                    text-align: center;
                    border-top: 1px solid #edf2f7;
                }
                .btn-text {
                    background: none;
                    border: none;
                    color: var(--primary);
                    font-weight: 600;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    margin: 0 auto;
                }
                @keyframes slideUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            </style>
        `;
    }

    window.downloadReceiptPdf = function(receiptNo) {
        window.location.href = `${APP_URL}/api/admin/fees?action=generate_receipt_html&is_pdf=1&receipt_no=${receiptNo}`;
    };

    window.triggerEmailReceipt = async function(receiptNo) {
        const btn = document.getElementById('btnResendEmail');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Queuing...`;
        
        try {
            const res = await fetch(`${APP_URL}/api/admin/fees`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'send_email_receipt', receipt_no: receiptNo })
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire('Queued', 'Email has been queued for delivery', 'success');
            } else {
                throw new Error(result.message);
            }
        } catch (e) {
            Swal.fire('Error', e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    }

    loadDetails();
})();
</script>
