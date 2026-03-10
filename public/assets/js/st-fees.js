/**
 * Hamro ERP — Student Portal · st-fees.js
 * Student Fees Module
 */

window.renderSTFees = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading fees...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/fees`);
        const result = await res.json();
        const currency = window.getCurrencySymbol();
        
        mc.innerHTML = `
            <div style="padding:24px;">
                <div class="card-hdr"><div class="ct"><i class="fa-solid fa-money-bill-wave" style="margin-right:8px;color:var(--sa-primary);"></i> Fee Status</div></div>
                
                ${result.success && result.data ? `
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
                        <div class="card"><div style="padding:20px;text-align:center;"><div style="font-size:2rem;font-weight:800;color:var(--sa-primary);">${currency}${(result.data.total_fee || 0).toLocaleString()}</div><div style="font-size:13px;color:var(--tl);">Total Fee</div></div></div>
                        <div class="card"><div style="padding:20px;text-align:center;"><div style="font-size:2rem;font-weight:800;color:#16a34a;">${currency}${(result.data.total_paid || 0).toLocaleString()}</div><div style="font-size:13px;color:var(--tl);">Amount Paid</div></div></div>
                        <div class="card"><div style="padding:20px;text-align:center;"><div style="font-size:2rem;font-weight:800;color:#dc2626;">${currency}${(result.data.balance || 0).toLocaleString()}</div><div style="font-size:13px;color:var(--tl);">Balance Due</div></div></div>
                    </div>
                    
                    <div class="card">
                        <div class="card-hdr"><div class="ct"><i class="fa-solid fa-receipt" style="margin-right:8px;"></i> Payment History</div></div>
                        <div class="card-body">
                            <table class="table">
                                <thead><tr><th>Receipt No.</th><th>Date</th><th>Amount</th><th>Payment Mode</th><th>Status</th></tr></thead>
                                <tbody>
                                    ${result.data.payments && result.data.payments.length > 0 ? result.data.payments.map(p => `<tr><td><strong>${p.receipt_no || '-'}</strong></td><td>${p.payment_date || '-'}</td><td><strong>${currency}${(p.amount || 0).toLocaleString()}</strong></td><td>${p.payment_mode || '-'}</td><td><span class="badge badge-green">Paid</span></td></tr>`).join('') : '<tr><td colspan="5" style="text-align:center;padding:30px;">No payment records found</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ` : `
                    <div class="card">
                        <div class="card-body" style="text-align:center;padding:60px;">
                            <i class="fa-solid fa-money-bill-wave" style="font-size:4rem;color:var(--sa-primary);opacity:0.3;margin-bottom:20px;"></i>
                            <h3>Fee Information</h3>
                            <p style="color:var(--tl);">${result.message || 'Unable to load fee data.'}</p>
                        </div>
                    </div>
                `}
            </div>
        `;
    } catch (e) {
        console.error('Fees load error:', e);
    }
};

window.renderSTFees = window.renderSTFees;
