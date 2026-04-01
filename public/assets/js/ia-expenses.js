/**
 * iSoftro ERP — Institute Admin · ia-expenses.js
 * Renders the Expenses module interface.
 */

window.renderExpenses = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `
        <div class="header-strip">
            <h2 class="pg-title"><i class="fa-solid fa-file-invoice-dollar" style="color:var(--orange)"></i> Expenses</h2>
            <div class="pg-actions">
                <button class="btn-v2 primary" onclick="_iaOpenAddExpenseModal()"><i class="fa-solid fa-plus"></i> Log Expense</button>
            </div>
        </div>
        <div class="card" style="padding:0;">
            <div class="table-responsive">
                <table class="v2-table" id="expensesTable">
                    <thead>
                        <tr>
                            <th>Voucher No</th>
                            <th>Date</th>
                            <th>Expense Details / Notes</th>
                            <th style="text-align:right">Total Amount</th>
                            <th style="text-align:right">Status</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTbody">
                        <tr><td colspan="5" align="center" style="padding:40px"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;

    _iaLoadExpenses();
};

async function _iaLoadExpenses() {
    const tbody = document.getElementById('expensesTbody');
    if (!tbody) return;

    try {
        const res = await fetch(APP_URL + '/api/admin/expenses?action=index');
        const json = await res.json();
        
        if (!json.success) throw new Error(json.message || "Failed to fetch expenses");
        
        if (!json.data || json.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" align="center" style="padding:40px;color:var(--text-light)">No expenses found</td></tr>`;
            return;
        }

        let html = '';
        json.data.forEach(e => {
            html += `
                <tr>
                    <td style="font-weight:700; color:var(--text-dark)">${e.voucher_no}</td>
                    <td>${new Date(e.date).toLocaleDateString()}</td>
                    <td style="color:var(--text-light)">${e.notes || '-'}</td>
                    <td align="right" style="font-weight:800; color:var(--red)">₹${new Intl.NumberFormat('en-IN').format(e.total_amount)}</td>
                    <td align="right">
                        <span class="v2-status-badge ${e.status === 'approved' ? 'active' : 'pending'}">${e.status}</span>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" align="center" style="padding:40px;color:var(--red)">${err.message}</td></tr>`;
    }
}

window._iaOpenAddExpenseModal = async function() {
    let mod = document.getElementById('expenseModal');
    if (mod) mod.remove();

    const div = document.createElement('div');
    div.id = 'expenseModal';
    div.className = 'modal-v2 active';
    div.innerHTML = `
        <div class="modal-v2-content" style="max-width:500px">
            <div class="modal-v2-header">
                <h3>Log New Expense</h3>
                <button class="modal-close" onclick="document.getElementById('expenseModal').remove()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-v2-body">
                <form id="expenseForm" onsubmit="_iaSubmitExpense(event)">
                    <div class="form-group-v2">
                        <label>Expense Category <span style="color:red">*</span></label>
                        <select class="form-control-v2" id="expCategoryId" required>
                            <option value="">Loading categories...</option>
                        </select>
                    </div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px">
                        <div class="form-group-v2">
                            <label>Amount (Gross) <span style="color:red">*</span></label>
                            <input type="number" step="0.01" class="form-control-v2" id="expAmount" required min="1">
                        </div>
                        <div class="form-group-v2">
                            <label>Date <span style="color:red">*</span></label>
                            <input type="date" class="form-control-v2" id="expDate" required value="${new Date().toISOString().split('T')[0]}">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px">
                        <div class="form-group-v2">
                            <label>Payment Mode</label>
                            <select class="form-control-v2" id="expPaymentMode">
                                <option value="cash">Cash in Hand</option>
                                <option value="bank">Bank Transfer/Cheque</option>
                            </select>
                        </div>
                        <div class="form-group-v2">
                            <label>TDS Deduction %</label>
                            <select class="form-control-v2" id="expTds">
                                <option value="0">0% (No TDS)</option>
                                <option value="1.5">1.5%</option>
                                <option value="15">15%</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group-v2">
                        <label>Notes / Narration</label>
                        <input type="text" class="form-control-v2" id="expNotes" placeholder="Purchased office supplies">
                    </div>
                    
                    <div class="modal-v2-footer" style="padding-top:10px; text-align:right">
                        <button type="button" class="btn-v2 outline" onclick="document.getElementById('expenseModal').remove()">Cancel</button>
                        <button type="submit" class="btn-v2 primary" id="expSubmitBtn">Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.appendChild(div);

    try {
        const res = await fetch(APP_URL + '/api/admin/expense-categories');
        const json = await res.json();
        if (json.success && json.data) {
            let opts = '<option value="">Select Category</option>';
            json.data.forEach(c => {
                opts += `<option value="${c.id}">${c.name}</option>`;
            });
            document.getElementById('expCategoryId').innerHTML = opts;
        } else {
            document.getElementById('expCategoryId').innerHTML = '<option value="">Failed to load</option>';
        }
    } catch(err) {
        document.getElementById('expCategoryId').innerHTML = '<option value="">Error compiling chart</option>';
    }
};

window._iaSubmitExpense = async function(e) {
    e.preventDefault();
    const btn = document.getElementById('expSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    const payload = {
        action: 'store',
        category_id: document.getElementById('expCategoryId').value,
        amount: parseFloat(document.getElementById('expAmount').value),
        date: document.getElementById('expDate').value,
        payment_mode: document.getElementById('expPaymentMode').value,
        tds_percent: parseFloat(document.getElementById('expTds').value),
        notes: document.getElementById('expNotes').value
    };

    try {
        const res = await fetch(APP_URL + '/api/admin/expenses', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        
        if (!json.success) throw new Error(json.message || "Server Error");
        
        // Success
        document.getElementById('expenseModal').remove();
        _iaLoadExpenses(); // reload list
        
        // Notify
        if (window.toast) window.toast('success', `Expense ${json.voucher_no || ''} logged successfully!`);
        else alert('Expense logged successfully!');

    } catch(err) {
        btn.disabled = false;
        btn.innerHTML = 'Save Expense';
        alert("Error: " + err.message);
    }
};
