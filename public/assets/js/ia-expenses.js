/**
 * Hamro ERP — Institute Admin · ia-expenses.js
 * Module: Expenses Management (Functional Specification v1.0)
 */

// Inject module-specific styles
(function injectExpenseStyles() {
    if (document.getElementById('ia-expenses-styles')) return;
    const style = document.createElement('style');
    style.id = 'ia-expenses-styles';
    style.innerHTML = `
        .v2-form-section-header { margin-bottom: 20px; }
        .v2-form-section-header h4 { margin: 0; color: var(--primary-color, #4e73df); font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .v2-form-section-header p { margin: 4px 0 0; font-size: 13px; color: #858796; }
        
        .v2-conditional-area {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .v2-input:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
        }
        
        .v2-checkbox-label:hover span {
            color: #4e73df;
        }

        .receipt-preview-zone {
            border: 2px dashed #e3e6f0;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            background: #f8f9fc;
            transition: all 0.3s;
        }
        
        .receipt-preview-zone:hover {
            border-color: #4e73df;
            background: #fff;
        }
    `;
    document.head.appendChild(style);
})();

/* ── 1. EXPENSE DASHBOARD ── */
window.renderExpenseDashboard = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
        <div class="pg-header">
            <div class="pg-header-left">
                <h2>Expenses Dashboard</h2>
                <div class="pg-breadcrumb">Finance &nbsp;•&nbsp; Expenses &nbsp;•&nbsp; Dashboard</div>
            </div>
            <div class="pg-header-right">
                <button class="btn-v2 secondary" onclick="window.renderExpenseCategoryList()"><i class="fa-solid fa-folder-tree"></i> Categories</button>
                <button class="btn-v2 primary" onclick="goNav('expenses', 'add')"><i class="fa-solid fa-plus"></i> Add Expense</button>
            </div>
        </div>

        <div id="expensesStatsContainer">
            <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading summary...</div>
        </div>

        <div class="main-grid" style="margin-top:24px;">
            <div class="panel-v2" style="grid-column: span 2;">
                <div class="panel-v2-header">
                    <h3><i class="fa-solid fa-chart-line"></i> Expense Trend (Last 6 Months)</h3>
                </div>
                <div class="panel-v2-body">
                    <canvas id="expenseTrendChart" style="height:300px;"></canvas>
                </div>
            </div>
            <div class="panel-v2">
                <div class="panel-v2-header">
                    <h3><i class="fa-solid fa-chart-pie"></i> Category Breakdown</h3>
                </div>
                <div class="panel-v2-body">
                    <canvas id="expenseCategoryChart" style="height:300px;"></canvas>
                </div>
            </div>
        </div>

        <div class="panel-v2" style="margin-top:24px;">
            <div class="panel-v2-header">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Expenses</h3>
                <button class="btn-v2 secondary sm" onclick="goNav('expenses', 'list')">View All</button>
            </div>
            <div class="panel-v2-body">
                <div class="table-responsive" id="recentExpensesTable">
                    <div class="pg-loading">Loading recent activities...</div>
                </div>
            </div>
        </div>
    `;

    try {
        const res = await fetch(`${window.APP_URL}/api/admin/expenses/stats`);
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        
        const s = result.summary;
        const formatMoney = num => new Intl.NumberFormat('en-NP', { style: 'currency', currency: 'NPR', maximumFractionDigits: 0 }).format(num);

        // Update Stats Row
        document.getElementById('expensesStatsContainer').innerHTML = `
            <div class="kpi-row-v2">
                <div class="kpi-card-v2">
                    <div class="kpi-v2-header">
                        <div class="kpi-v2-label">Total Expenses (Month)</div>
                        <div class="kpi-v2-icon red"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                    </div>
                    <div class="kpi-v2-value">${formatMoney(s.total_monthly)}</div>
                    <div class="kpi-v2-meta">Current Month Spending</div>
                </div>
                <div class="kpi-card-v2">
                    <div class="kpi-v2-header">
                        <div class="kpi-v2-label">Last 90 Days</div>
                        <div class="kpi-v2-icon orange"><i class="fa-solid fa-calendar-week"></i></div>
                    </div>
                    <div class="kpi-v2-value">${formatMoney(s.total_quarterly)}</div>
                    <div class="kpi-v2-meta">Quarterly Spending</div>
                </div>
                <div class="kpi-card-v2">
                    <div class="kpi-v2-header">
                        <div class="kpi-v2-label">Top Category</div>
                        <div class="kpi-v2-icon purple"><i class="fa-solid fa-crown"></i></div>
                    </div>
                    <div class="kpi-v2-value" style="font-size:18px;">${s.category_breakdown[0]?.category || 'N/A'}</div>
                    <div class="kpi-v2-meta">Most spent this month</div>
                </div>
                <div class="kpi-card-v2">
                    <div class="kpi-v2-header">
                        <div class="kpi-v2-label">Total Transactions</div>
                        <div class="kpi-v2-icon blue"><i class="fa-solid fa-receipt"></i></div>
                    </div>
                    <div class="kpi-v2-value">${s.recent_expenses.length}</div>
                    <div class="kpi-v2-meta">Entries this month</div>
                </div>
            </div>
        `;

        // Render Recent Table
        document.getElementById('recentExpensesTable').innerHTML = `
            <table class="v2-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th align="right">Amount</th>
                        <th align="center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${s.recent_expenses.map(e => `
                        <tr>
                            <td class="ws-nowrap">
                                <div style="font-weight:700;">${e.date_bs}</div>
                                <div style="font-size:11px; opacity:0.6;">${e.date_ad}</div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:10px; height:10px; border-radius:50%; background:${e.category_color || '#ccc'}"></div>
                                    ${e.category_name}
                                </div>
                            </td>
                            <td>${e.description || '-'}</td>
                            <td class="text-capitalize">${e.payment_method}</td>
                            <td align="right" style="font-weight:800;">NPR ${new Intl.NumberFormat('en-NP').format(e.amount)}</td>
                            <td align="center">
                                <span class="v2-status-badge ${e.status}">${e.status}</span>
                            </td>
                        </tr>
                    `).join('')}
                    ${s.recent_expenses.length === 0 ? '<tr><td colspan="6" align="center" style="padding:40px;">No expenses found.</td></tr>' : ''}
                </tbody>
            </table>
        `;

        // Initialize Charts
        initExpenseCharts(s.monthly_trend, s.category_breakdown);

    } catch (err) {
        console.error(err);
        document.getElementById('expensesStatsContainer').innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
    }
};

function initExpenseCharts(trend, breakdown) {
    // Trend Chart
    const trendCtx = document.getElementById('expenseTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trend.map(t => t.month),
            datasets: [{
                label: 'Total Expenses',
                data: trend.map(t => t.total),
                borderColor: '#e74a3b',
                backgroundColor: 'rgba(231, 74, 59, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Category Chart
    const catCtx = document.getElementById('expenseCategoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: breakdown.map(b => b.category),
            datasets: [{
                data: breakdown.map(b => b.total),
                backgroundColor: breakdown.map(b => b.color || '#4e73df'),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

/* ── 2. MANAGE CATEGORIES ── */
window.renderExpenseCategoryList = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
        <div class="pg-header">
            <div class="pg-header-left">
                <h2>Manage Expense Categories</h2>
                <div class="pg-breadcrumb">Finance &nbsp;•&nbsp; Expenses &nbsp;•&nbsp; Categories</div>
            </div>
            <div class="pg-header-right">
                <button class="btn-v2 secondary" onclick="window.renderExpenseDashboard()"><i class="fa-solid fa-arrow-left"></i> Back</button>
                <button class="btn-v2 primary" onclick="showExpenseCategoryModal()"><i class="fa-solid fa-plus"></i> New Category</button>
            </div>
        </div>

        <div class="card" style="margin-top:24px;">
            <div class="table-responsive" id="catTableContainer">
                <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading categories...</div>
            </div>
        </div>
    `;

    try {
        const res = await fetch(`${window.APP_URL}/api/admin/expense-categories?action=list`);
        const result = await res.json();
        if (!result.success) throw new Error(result.message);

        document.getElementById('catTableContainer').innerHTML = `
            <table class="v2-table">
                <thead>
                    <tr>
                        <th width="50">Icon</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th align="center">Status</th>
                        <th align="right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${result.categories.map(c => `
                        <tr>
                            <td>
                                <div style="width:36px; height:36px; border-radius:8px; background:${c.color || '#f1f1f1'}; color:#fff; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-${c.icon || 'folder'}"></i>
                                </div>
                            </td>
                            <td><strong>${c.name}</strong></td>
                            <td>${c.description || '-'}</td>
                            <td align="center">
                                <span class="v2-status-badge ${c.is_active ? 'active' : 'pending'}">${c.is_active ? 'Active' : 'Inactive'}</span>
                            </td>
                            <td align="right">
                                <button class="btn-icon" onclick='showExpenseCategoryModal(${JSON.stringify(c)})'><i class="fa-solid fa-edit"></i></button>
                                <button class="btn-icon red" onclick="deleteExpenseCategory(${c.id})"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

    } catch (err) {
        document.getElementById('catTableContainer').innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
    }
};

window.showExpenseCategoryModal = function(data = null) {
    const modalHtml = `
        <div class="v2-modal-overlay" id="catModal">
            <div class="v2-modal-card">
                <div class="v2-modal-header">
                    <h3>${data ? 'Edit' : 'New'} Expense Category</h3>
                    <button class="btn-close" onclick="closeCatModal()">&times;</button>
                </div>
                <form id="catForm" onsubmit="saveExpenseCategory(event)">
                    <div class="v2-modal-body">
                        <input type="hidden" name="id" value="${data ? data.id : 0}">
                        <div class="v2-form-group">
                            <label>Category Name *</label>
                            <input type="text" name="name" class="v2-input" required value="${data ? data.name : ''}">
                        </div>
                        <div class="v2-form-row">
                            <div class="v2-form-group">
                                <label>Icon (FontAwesome name)</label>
                                <input type="text" name="icon" class="v2-input" placeholder="e.g. building" value="${data ? data.icon : 'folder'}">
                            </div>
                            <div class="v2-form-group">
                                <label>Color Key</label>
                                <input type="color" name="color" class="v2-input" style="height:44px; padding:4px;" value="${data ? data.color : '#4e73df'}">
                            </div>
                        </div>
                        <div class="v2-form-group">
                            <label>Description (Optional)</label>
                            <textarea name="description" class="v2-input" rows="3">${data ? data.description : ''}</textarea>
                        </div>
                        <div class="v2-form-group">
                            <label class="v2-checkbox-label">
                                <input type="checkbox" name="is_active" value="1" ${!data || data.is_active ? 'checked' : ''}> Active
                            </label>
                        </div>
                    </div>
                    <div class="v2-modal-footer">
                        <button type="button" class="btn-v2 secondary" onclick="closeCatModal()">Cancel</button>
                        <button type="submit" class="btn-v2 primary">Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    setTimeout(() => document.getElementById('catModal').classList.add('active'), 10);
};

window.closeCatModal = function() {
    const m = document.getElementById('catModal');
    m.classList.remove('active');
    setTimeout(() => m.remove(), 300);
};

window.saveExpenseCategory = async function(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    try {
        const res = await fetch(`${window.APP_URL}/api/admin/expense-categories?action=save`, {
            method: 'POST',
            body: fd
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        
        Swal.fire({ icon: 'success', title: 'Success', text: result.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        closeCatModal();
        window.renderExpenseCategoryList();
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Save Category';
    }
};

window.deleteExpenseCategory = async function(id) {
    if (!confirm('Are you sure you want to delete this category?')) return;
    try {
        const res = await fetch(`${window.APP_URL}/api/admin/expense-categories?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        Swal.fire({ icon: 'success', title: 'Deleted', text: result.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        window.renderExpenseCategoryList();
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    }
};

/* ── 3. ADD/EDIT EXPENSE MODAL ── */
window.renderAddExpenseForm = async function(id = 0) {
    const todayAD = new Date().toISOString().split('T')[0];
    const todayBS = window.nepaliDateHelper ? window.nepaliDateHelper.adToBs(todayAD) : '';

    const modalHtml = `
        <div class="v2-modal-overlay active" id="expenseModal">
            <div class="v2-modal-card" style="max-width:850px; width:95%;">
                <div class="v2-modal-header">
                    <h3><i class="fa-solid fa-receipt"></i> ${id > 0 ? 'Edit' : 'Record'} Expense</h3>
                    <button class="btn-close" onclick="closeExpenseModal()">&times;</button>
                </div>
                <form id="expenseForm" onsubmit="saveExpense(event)" enctype="multipart/form-data">
                    <div class="v2-modal-body" style="max-height:80vh; overflow-y:auto;">
                        <input type="hidden" name="id" value="${id}">
                        <input type="hidden" name="date_ad" id="date_ad" value="${todayAD}">
                        <div id="expenseFormContent">
                            <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading form...</div>
                        </div>
                    </div>
                    <div class="v2-modal-footer">
                        <div style="flex:1;">
                             <label class="v2-checkbox-label" style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0;">
                                <input type="checkbox" name="is_recurring" value="1" style="width:18px; height:18px;"> 
                                <span><i class="fa-solid fa-repeat" style="font-size:12px;"></i> Recurring Expense</span>
                            </label>
                        </div>
                        <button type="button" class="btn-v2 secondary" onclick="closeExpenseModal()">Cancel</button>
                        <button type="submit" id="saveExpenseBtn" class="btn-v2 primary" style="min-width:160px;">
                            <i class="fa-solid fa-check-circle"></i> Save & Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    try {
        const catRes = await fetch(`${window.APP_URL}/api/admin/expense-categories?action=list`);
        const catResult = await catRes.json();
        if (!catResult.success) throw new Error("Could not load categories");

        let edata = null;
        if (id > 0) {
            const editRes = await fetch(`${window.APP_URL}/api/admin/expenses?action=list`);
            const editResult = await editRes.json();
            edata = editResult.expenses.find(e => e.id == id);
            if (edata) {
                document.getElementById('date_ad').value = edata.date_ad;
            }
        }

        document.getElementById('expenseFormContent').innerHTML = `
            <div class="main-grid" style="gap:24px;">
                <div style="grid-column: span 1;">
                    <div class="v2-form-section-header">
                        <h4><i class="fa-solid fa-circle-info"></i> Basic Info</h4>
                    </div>
                    <div class="v2-form-group">
                        <label><i class="fa-solid fa-calendar-day"></i> Expense Date (BS) *</label>
                        <input type="text" name="date_bs" id="date_bs" class="v2-input" 
                               value="${edata ? edata.date_bs : todayBS}" 
                               placeholder="YYYY-MM-DD" 
                               onchange="updateADDate(this.value)" required>
                        <small style="font-size:11px; color:#858796;">Nepali Date (e.g. 2080-12-05)</small>
                    </div>
                    <div class="v2-form-group">
                        <label><i class="fa-solid fa-coins"></i> Amount (NPR) *</label>
                        <input type="number" step="0.01" name="amount" class="v2-input" required value="${edata ? edata.amount : ''}" placeholder="0.00" style="font-weight:700; font-size:18px;">
                    </div>
                    <div class="v2-form-group">
                        <label><i class="fa-solid fa-tags"></i> Category *</label>
                        <select name="expense_category_id" class="v2-input" required>
                            <option value="">Select Category</option>
                            ${catResult.categories.map(c => `<option value="${c.id}" ${edata && edata.expense_category_id == c.id ? 'selected' : ''}>${c.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="v2-form-group">
                        <label><i class="fa-solid fa-comment-dots"></i> Description / Note</label>
                        <textarea name="description" class="v2-input" rows="3" placeholder="Describe the purpose...">${edata ? edata.description || '' : ''}</textarea>
                    </div>
                </div>

                <div style="grid-column: span 1;">
                    <div class="v2-form-section-header">
                        <h4><i class="fa-solid fa-credit-card"></i> Payment Info</h4>
                    </div>
                    <div class="v2-form-group">
                        <label><i class="fa-solid fa-wallet"></i> Payment Method *</label>
                        <select name="payment_method" class="v2-input" onchange="togglePaymentFields(this.value)" required>
                            <option value="cash" ${edata && edata.payment_method == 'cash' ? 'selected' : ''}>💵 Cash Payment</option>
                            <option value="bank_transfer" ${edata && edata.payment_method == 'bank_transfer' ? 'selected' : ''}>🏦 Bank Transfer</option>
                            <option value="esewa" ${edata && edata.payment_method == 'esewa' ? 'selected' : ''}>📱 eSewa Wallet</option>
                            <option value="khalti" ${edata && edata.payment_method == 'khalti' ? 'selected' : ''}>💜 Khalti Wallet</option>
                            <option value="cheque" ${edata && edata.payment_method == 'cheque' ? 'selected' : ''}>✍️ Bank Cheque</option>
                        </select>
                    </div>

                    <div id="paymentDetailsArea" class="v2-conditional-area" style="display:${edata && edata.payment_method != 'cash' ? 'block' : 'none'}; margin-bottom:16px;">
                        <div style="background:rgba(78, 115, 223, 0.05); padding:16px; border-radius:12px; border:1px dashed #4e73df;">
                            <div id="bankDetails" class="pay-detail" style="display:${edata && edata.payment_method == 'bank_transfer' ? 'block' : 'none'};">
                                <div class="v2-form-group">
                                    <label><i class="fa-solid fa-building-columns"></i> Bank Name</label>
                                    <input type="text" name="bank_name" class="v2-input" value="${edata ? edata.bank_name || '' : ''}" placeholder="e.g. Nabil Bank">
                                </div>
                                <div class="v2-form-group">
                                    <label><i class="fa-solid fa-hashtag"></i> Reference #</label>
                                    <input type="text" name="reference_number" class="v2-input" value="${edata ? edata.reference_number || '' : ''}" placeholder="Bank ref number">
                                </div>
                            </div>
                            <div id="digitalWalletDetails" class="pay-detail" style="display:${edata && (edata.payment_method == 'esewa' || edata.payment_method == 'khalti') ? 'block' : 'none'};">
                                <div class="v2-form-group">
                                    <label><i class="fa-solid fa-fingerprint"></i> Transaction ID</label>
                                    <input type="text" name="transaction_id" class="v2-input" value="${edata ? edata.transaction_id || '' : ''}" placeholder="Wallet txn reference">
                                </div>
                            </div>
                            <div id="chequeDetails" class="pay-detail" style="display:${edata && edata.payment_method == 'cheque' ? 'block' : 'none'};">
                                <div class="v2-form-group">
                                    <label><i class="fa-solid fa-money-check"></i> Cheque Number</label>
                                    <input type="text" name="cheque_number" class="v2-input" value="${edata ? edata.cheque_number || '' : ''}" placeholder="00000000">
                                </div>
                                <div class="v2-form-group">
                                    <label><i class="fa-solid fa-calendar-check"></i> Cheque Date</label>
                                    <input type="date" name="cheque_date" class="v2-input" value="${edata ? edata.cheque_date : ''}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="v2-form-group">
                        <label><i class="fa-solid fa-file-invoice"></i> Receipt / Proof (Optional)</label>
                        <input type="file" name="receipt" class="v2-input" accept="image/*,.pdf" style="padding-top:8px;" onchange="previewReceipt(this)">
                        ${edata && edata.receipt_path ? `<div style="font-size:11px; margin-top:4px;"><a href="${window.APP_URL}/${edata.receipt_path}" target="_blank">View Current Receipt</a></div>` : ''}
                        <div id="receiptPreview" class="receipt-preview-zone" style="margin-top:10px; display:none;">
                             <i class="fa-solid fa-paperclip" style="color:#4e73df;"></i>
                             <span id="receiptFileName" style="font-size:12px; color:#4e73df; margin-left:8px;"></span>
                             <button type="button" class="btn-icon sm red" onclick="clearReceipt()" style="margin-left:10px;"><i class="fa-solid fa-times"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (edata && edata.is_recurring) {
            document.querySelector('input[name="is_recurring"]').checked = true;
        }

    } catch (err) {
        document.getElementById('expenseFormContent').innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    }
};

window.closeExpenseModal = function() {
    const m = document.getElementById('expenseModal');
    if (m) {
        m.classList.remove('active');
        setTimeout(() => m.remove(), 300);
    }
};

window.updateADDate = async function(bsDate) {
    if (!bsDate) return;
    try {
        const res = await fetch(`${window.APP_URL}/api/admin/date-convert?type=bs&date=${bsDate}`);
        const result = await res.json();
        if (result.success) {
            document.getElementById('date_ad').value = result.converted;
        }
    } catch (e) { console.error(e); }
};

window.togglePaymentFields = function(method) {
    const area = document.getElementById('paymentDetailsArea');
    const details = area.querySelectorAll('.pay-detail');
    details.forEach(d => d.style.display = 'none');
    
    if (method === 'cash') {
        area.style.display = 'none';
        return;
    }

    area.style.display = 'block';
    if (method === 'bank_transfer') document.getElementById('bankDetails').style.display = 'block';
    if (method === 'esewa' || method === 'khalti') document.getElementById('digitalWalletDetails').style.display = 'block';
    if (method === 'cheque') document.getElementById('chequeDetails').style.display = 'block';
};

window.previewReceipt = function(input) {
    const preview = document.getElementById('receiptPreview');
    const fileName = document.getElementById('receiptFileName');
    if (input.files && input.files[0]) {
        fileName.innerText = input.files[0].name;
        preview.style.display = 'flex';
        preview.style.alignItems = 'center';
        preview.style.justifyContent = 'center';
    } else {
        preview.style.display = 'none';
    }
};

window.clearReceipt = function() {
    const input = document.querySelector('input[name="receipt"]');
    input.value = "";
    document.getElementById('receiptPreview').style.display = 'none';
};

window.saveExpense = async function(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

    try {
        const res = await fetch(`${window.APP_URL}/api/admin/expenses?action=save`, {
            method: 'POST',
            body: fd
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);

        Swal.fire({ icon: 'success', title: 'Recorded', text: result.message });
        closeExpenseModal();
        window.renderExpenseDashboard();
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Save & Approve';
    }
};

/* ── 4. EXPENSE LIST VIEW ── */
window.renderExpenseList = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
        <div class="pg-header">
            <div class="pg-header-left">
                <h2>All Expenses</h2>
                <div class="pg-breadcrumb">Finance &nbsp;•&nbsp; Expenses &nbsp;•&nbsp; List</div>
            </div>
            <div class="pg-header-right">
                <button class="btn-v2 secondary" onclick="window.renderExpenseDashboard()"><i class="fa-solid fa-arrow-left"></i> Dashboard</button>
                <button class="btn-v2 primary" onclick="goNav('expenses', 'add')"><i class="fa-solid fa-plus"></i> New Expense</button>
            </div>
        </div>

        <div class="panel-v2" style="margin-top:24px;">
            <div class="panel-v2-body">
                <form id="expenseFilterForm" onsubmit="applyExpenseFilters(event)" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
                    <div class="v2-form-group">
                        <label>Category</label>
                        <select name="category_id" class="v2-input sm" id="filterCategory"></select>
                    </div>
                    <div class="v2-form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="v2-input sm">
                    </div>
                    <div class="v2-form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="v2-input sm">
                    </div>
                    <div class="v2-form-group" style="display:flex; align-items:flex-end;">
                        <button type="submit" class="btn-v2 primary sm" style="width:100%;">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top:16px;">
            <div id="expenseListTable" class="table-responsive">
                <div class="pg-loading">Loading expenses...</div>
            </div>
        </div>
    `;

    // Load Categories for filter
    try {
        const catRes = await fetch(`${window.APP_URL}/api/admin/expense-categories?action=list`);
        const catResult = await catRes.json();
        if (catResult.success) {
            const select = document.getElementById('filterCategory');
            select.innerHTML = '<option value="0">All Categories</option>' + catResult.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }
    } catch(e) {}

    applyExpenseFilters();
};

window.applyExpenseFilters = async function(e = null) {
    if(e) e.preventDefault();
    const fd = e ? new FormData(e.target) : new FormData();
    const params = new URLSearchParams(fd).toString();
    
    const tableContainer = document.getElementById('expenseListTable');
    tableContainer.innerHTML = '<div class="pg-loading">Refreshing...</div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/admin/expenses?action=list&${params}`);
        const result = await res.json();
        if (!result.success) throw new Error(result.message);

        tableContainer.innerHTML = `
            <table class="v2-table">
                <thead>
                    <tr>
                        <th>Date (BS)</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th align="right">Amount</th>
                        <th align="center">Status</th>
                        <th align="right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${result.expenses.map(e => `
                        <tr>
                            <td><strong>${e.date_bs}</strong><br><small style="opacity:0.6">${e.date_ad}</small></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:10px; height:10px; border-radius:50%; background:${e.category_color}"></div>
                                    ${e.category_name}
                                </div>
                            </td>
                            <td>${e.description || '-'}</td>
                            <td class="text-capitalize">${e.payment_method.replace('_', ' ')}</td>
                            <td align="right" style="font-weight:800;">NPR ${new Intl.NumberFormat('en-NP').format(e.amount)}</td>
                            <td align="center"><span class="v2-status-badge ${e.status}">${e.status}</span></td>
                            <td align="right">
                                <button class="btn-icon" onclick="goNav('expenses','add',{id:${e.id}})" title="Edit"><i class="fa-solid fa-edit"></i></button>
                                <button class="btn-icon red" onclick="deleteExpense(${e.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `).join('')}
                    ${result.expenses.length === 0 ? '<tr><td colspan="7" align="center" style="padding:40px;">No matching expenses found.</td></tr>' : ''}
                </tbody>
            </table>
        `;
    } catch (err) {
        tableContainer.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    }
};

window.deleteExpense = async function(id) {
    if(!confirm("Are you sure you want to delete this expense record?")) return;
    try {
        const res = await fetch(`${window.APP_URL}/api/admin/expenses?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        Swal.fire({ icon: 'success', title: 'Deleted', text: result.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        window.applyExpenseFilters();
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    }
};
