/**
 * iSoftro ERP — Student Portal · st-receipts.js
 * Payment Receipts: view and download the same receipt generated during payment
 */

window.renderSTReceipts = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading receipts...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/fees`);
        const result = await res.json();
        const currency = window.getCurrencySymbol();

        if (!result.success || !result.data || !result.data.payments || result.data.payments.length === 0) {
            mc.innerHTML = `
                <div style="padding:24px;">
                    <div class="card-hdr"><div class="ct"><i class="fa-solid fa-receipt" style="margin-right:8px;color:var(--sa-primary);"></i> Payment Receipts</div></div>
                    <div class="card">
                        <div class="card-body" style="text-align:center;padding:60px;">
                            <i class="fa-solid fa-receipt" style="font-size:4rem;color:var(--sa-primary);opacity:0.3;margin-bottom:20px;"></i>
                            <h3>No Receipts Found</h3>
                            <p style="color:var(--tl);">You don't have any payment receipts yet. Receipts will appear here after payments are made.</p>
                        </div>
                    </div>
                </div>`;
            return;
        }

        const payments = result.data.payments;
        const totalPaid = result.data.total_paid || payments.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0);

        mc.innerHTML = `
            <div style="padding:24px;">
                <div class="card-hdr">
                    <div class="ct"><i class="fa-solid fa-receipt" style="margin-right:8px;color:var(--sa-primary);"></i> Payment Receipts</div>
                </div>

                <!-- Summary -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
                    <div class="card"><div style="padding:20px;text-align:center;">
                        <div style="font-size:2rem;font-weight:800;color:var(--sa-primary);">${payments.length}</div>
                        <div style="font-size:13px;color:var(--tl);">Total Receipts</div>
                    </div></div>
                    <div class="card"><div style="padding:20px;text-align:center;">
                        <div style="font-size:2rem;font-weight:800;color:#16a34a;">${currency}${totalPaid.toLocaleString()}</div>
                        <div style="font-size:13px;color:var(--tl);">Total Paid</div>
                    </div></div>
                </div>

                <!-- Receipts Table -->
                <div class="card">
                    <div class="card-body" style="overflow-x:auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Receipt No.</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                    <th style="text-align:center;">Download</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${payments.map(p => {
                                    const receiptNo = _rcpEsc(p.receipt_number || p.receipt_no || '-');
                                    const receiptPath = p.receipt_path || '';
                                    const hasFile = !!receiptPath;
                                    return `
                                    <tr>
                                        <td><strong style="color:var(--sa-primary);">${receiptNo}</strong></td>
                                        <td>${_rcpEsc(p.payment_date || p.date || '-')}</td>
                                        <td>${_rcpEsc(p.fee_item_name || p.description || 'Payment')}</td>
                                        <td><strong>${currency}${(parseFloat(p.amount) || 0).toLocaleString()}</strong></td>
                                        <td><span style="text-transform:capitalize;">${_rcpEsc(p.payment_mode || '-')}</span></td>
                                        <td><span class="badge badge-green">Paid</span></td>
                                        <td style="text-align:center;">
                                            ${hasFile ? `
                                                <a href="${window.APP_URL}/${_rcpEsc(receiptPath)}" target="_blank" class="btn btn-sm btn-primary" title="Download Receipt" style="text-decoration:none;">
                                                    <i class="fa-solid fa-download"></i> PDF
                                                </a>
                                            ` : `
                                                <span style="font-size:11px;color:var(--tl);">Not available</span>
                                            `}
                                        </td>
                                    </tr>`;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

    } catch (e) {
        console.error('Receipts load error:', e);
        mc.innerHTML = `<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:60px;">
            <i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i>
            <h3>Error</h3><p>Failed to load receipt data.</p>
        </div></div></div>`;
    }
};

/** Escape HTML */
function _rcpEsc(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
