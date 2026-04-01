/**
 * iSoftro ERP — Accounting Module (IA)
 * Handles Chart of Accounts, Vouchers, and Financial Reports
 */

const AccountingModule = {
    accounts: null,

    renderAction: function(subId) {
        const mainContent = document.getElementById('mainContent');
        if (!subId || subId === 'dashboard') {
            this.renderDashboard(mainContent);
        } else if (subId === 'coa') {
            this.renderCOA(mainContent);
        } else if (subId === 'voucher') {
            this.renderVoucherEntry(mainContent);
        } else if (subId === 'ledger') {
            this.renderLedger(mainContent);
        } else if (subId === 'trial-balance') {
            this.renderTrialBalance(mainContent);
        } else if (subId === 'reports') {
            this.renderReports(mainContent);
        }
    },

    renderDashboard: async function(container) {
        container.innerHTML = `
            <div class="dash-v2-container">
                <!-- HEADER STRIP -->
                <div class="dash-v2-header-strip">
                    <div class="header-strip-left">
                        <div class="header-strip-institute"><i class="fa-solid fa-scale-balanced" style="color:var(--text-light); margin-right:8px;"></i> Accounting Dashboard</div>
                        <div class="header-strip-meta">Financial status at a glance</div>
                    </div>
                    <div class="header-strip-right">
                        <div class="header-actions">
                            <button class="btn-v2 primary" style="background:#ef4444; border-color:#ef4444" onclick="AccountingModule.showAddExpenseModal()">
                                <i class="fa-solid fa-minus-circle"></i> Record Expense
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI ROW -->
                <div class="kpi-row-v2" style="margin-top: 20px;">
                    <div class="kpi-card-v2">
                        <div class="kpi-v2-header">
                            <div class="kpi-v2-label">Total Cash & Bank</div>
                            <div class="kpi-v2-icon blue"><i class="fa-solid fa-vault"></i></div>
                        </div>
                        <div class="kpi-v2-value" id="dash_cash">Loading...</div>
                        <div class="kpi-v2-progress"><div class="kpi-v2-progress-fill" style="width: 100%; background: #3b82f6; opacity:0.3;"></div></div>
                    </div>
                    <div class="kpi-card-v2">
                        <div class="kpi-v2-header">
                            <div class="kpi-v2-label">Accounts Receivable</div>
                            <div class="kpi-v2-icon orange"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                        </div>
                        <div class="kpi-v2-value" id="dash_ar">Loading...</div>
                        <div class="kpi-v2-progress"><div class="kpi-v2-progress-fill" style="width: 100%; background: #f59e0b; opacity:0.3;"></div></div>
                    </div>
                    <div class="kpi-card-v2">
                        <div class="kpi-v2-header">
                            <div class="kpi-v2-label">Accounts Payable</div>
                            <div class="kpi-v2-icon purple"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        </div>
                        <div class="kpi-v2-value" id="dash_ap">Loading...</div>
                        <div class="kpi-v2-progress"><div class="kpi-v2-progress-fill" style="width: 100%; background: #8b5cf6; opacity:0.3;"></div></div>
                    </div>
                    <div class="kpi-card-v2">
                        <div class="kpi-v2-header">
                            <div class="kpi-v2-label">Total Income</div>
                            <div class="kpi-v2-icon green"><i class="fa-solid fa-arrow-trend-up"></i></div>
                        </div>
                        <div class="kpi-v2-value" id="dash_income">Loading...</div>
                        <div class="kpi-v2-progress"><div class="kpi-v2-progress-fill" style="width: 100%; background: #10b981; opacity:0.3;"></div></div>
                    </div>
                    <div class="kpi-card-v2">
                        <div class="kpi-v2-header">
                            <div class="kpi-v2-label">Total Expense</div>
                            <div class="kpi-v2-icon" style="color:#ef4444; background:rgba(239,68,68,0.1)"><i class="fa-solid fa-arrow-trend-down"></i></div>
                        </div>
                        <div class="kpi-v2-value" id="dash_expense" style="color:#ef4444">Loading...</div>
                        <div class="kpi-v2-progress"><div class="kpi-v2-progress-fill" style="width: 100%; background: #ef4444; opacity:0.3;"></div></div>
                    </div>
                </div>

                <!-- BOTTOM GRID -->
                <div class="bottom-grid" style="margin-top:20px;">
                    <div class="panel-v2" style="grid-column: 1 / -1;">
                        <div class="panel-v2-header">
                            <h3><i class="fa-solid fa-list-ol"></i> Recent Transactions</h3>
                        </div>
                        <div class="panel-v2-body">
                            <div class="table-responsive" id="recentTransactions">
                                <div style="padding: 30px; text-align: center; color: var(--text-light);"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        try {
            const res = await fetch(APP_URL + '/api/admin/accounting?action=dashboard-stats');
            const result = await res.json();
            if (result.success) {
                const d = result.data;
                const fmt = num => new Intl.NumberFormat('en-IN').format(parseFloat(num || 0).toFixed(2));
                
                document.getElementById('dash_cash').textContent = window.getCurrencySymbol() + ' ' + fmt(d.cash_bank);
                document.getElementById('dash_ar').textContent = window.getCurrencySymbol() + ' ' + fmt(d.accounts_receivable);
                document.getElementById('dash_ap').textContent = window.getCurrencySymbol() + ' ' + fmt(d.accounts_payable);
                document.getElementById('dash_income').textContent = window.getCurrencySymbol() + ' ' + fmt(d.total_income);
                document.getElementById('dash_expense').textContent = window.getCurrencySymbol() + ' ' + fmt(d.total_expense);
                
                let txHtml = '<table class="v2-table">';
                if (d.recent_transactions && d.recent_transactions.length > 0) {
                    d.recent_transactions.forEach(tx => {
                        txHtml += `<tr>
                            <td>
                                <div style="font-size:13px; font-weight:700;">${tx.narration || 'Journal Entry'}</div>
                                <div style="font-size:11px; color:var(--text-light);">${tx.voucher_no} • ${tx.type.toUpperCase()}</div>
                            </td>
                            <td align="right">
                                <div style="font-size:13px; font-weight:700;">${window.getCurrencySymbol()} ${fmt(tx.amount)}</div>
                                <div style="font-size:11px; color:var(--text-light);">${new Date(tx.date).toLocaleDateString()}</div>
                            </td>
                        </tr>`;
                    });
                } else {
                    txHtml += '<tr><td colspan="2" style="text-align:center; padding: 20px; color: var(--text-light)">No recent transactions found.</td></tr>';
                }
                txHtml += '</table>';
                document.getElementById('recentTransactions').innerHTML = txHtml;
            }
        } catch (e) {
            console.error('Error loading dashboard stats:', e);
            document.getElementById('recentTransactions').innerHTML = '<div style="padding: 20px; color: #ef4444; text-align: center;">Failed to load data.</div>';
        }
    },

    renderCOA: async function(container) {
        container.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <span class="bc-cur">Chart of Accounts</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-tree"></i></div>
                        <div>
                            <div class="pg-title">Chart of Accounts</div>
                            <div class="pg-sub">Manage your ledger accounts</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" onclick="AccountingModule.showAddAccountModal()">
                            <i class="fa-solid fa-plus"></i> New Account
                        </button>
                    </div>
                </div>
                <div class="card" id="coaList">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Loading accounts...</span>
                    </div>
                </div>
            </div>
        `;
        this.loadCOA();
    },

    loadCOA: async function() {
        const container = document.getElementById('coaList');
        try {
            const res = await fetch(APP_URL + '/api/admin/accounting?action=accounts');
            const result = await res.json();
            if (result.success) {
                this.displayCOA(result.data, container);
            }
        } catch (e) {
            container.innerHTML = 'Error loading accounts';
        }
    },

    displayCOA: function(accounts, container) {
        let html = '<table class="data-table"><thead><tr><th>Code</th><th>Account Name</th><th>Type</th><th>Balance</th></tr></thead><tbody>';
        accounts.forEach(acc => {
            html += `<tr>
                <td>${acc.code || '-'}</td>
                <td style="font-weight:600;">${acc.name}</td>
                <td><span class="tag bg-b">${acc.type.toUpperCase()}</span></td>
                <td>${acc.balance || '0.00'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    },

    renderVoucherEntry: function(container) {
        container.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <span class="bc-cur">Voucher Entry</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-file-invoice"></i></div>
                        <div>
                            <div class="pg-title">New Voucher</div>
                            <div class="pg-sub">Record a financial transaction</div>
                        </div>
                    </div>
                </div>
                <div class="card" style="max-width:1000px; margin: 0 auto; padding: 25px;">
                    <form id="voucherForm" onsubmit="AccountingModule.saveVoucher(event)">
                        <input type="hidden" name="fiscal_year_id" value="1"> <!-- Default to active FY -->
                        <div class="sg mb">
                            <div class="form-group">
                                <label class="form-label">Voucher No</label>
                                <input type="text" name="voucher_no" class="form-control" placeholder="JV-001" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" value="${new Date().toISOString().split('T')[0]}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-control">
                                    <option value="journal">Journal</option>
                                    <option value="receipt">Receipt</option>
                                    <option value="payment">Payment</option>
                                    <option value="contra">Contra</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb">
                            <label class="form-label">Narration</label>
                            <textarea name="narration" class="form-control" rows="2" placeholder="Describe the transaction..."></textarea>
                        </div>
                        <table class="data-table mb">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th style="width:150px;">Debit</th>
                                    <th style="width:150px;">Credit</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="voucherRows">
                                <!-- Dynamic rows here -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td style="text-align:right; font-weight:700;">Total</td>
                                    <td id="totalDebit" style="font-weight:700;">0.00</td>
                                    <td id="totalCredit" style="font-weight:700;">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <div style="display:flex; justify-content:space-between;">
                            <button type="button" class="btn bs" onclick="AccountingModule.addVoucherRow()">
                                <i class="fa-solid fa-plus"></i> Add Row
                            </button>
                            <button type="submit" class="btn bt">Save Voucher</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        this.addVoucherRow();
        this.addVoucherRow();
    },

    addVoucherRow: function() {
        const tbody = document.getElementById('voucherRows');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select class="form-control account-select" required>
                    <option value="">Select Account</option>
                </select>
            </td>
            <td><input type="number" step="0.01" class="form-control debit-input" value="0.00" onchange="AccountingModule.updateVoucherTotals()"></td>
            <td><input type="number" step="0.01" class="form-control credit-input" value="0.00" onchange="AccountingModule.updateVoucherTotals()"></td>
            <td><button type="button" class="btn-icon text-danger" onclick="this.closest('tr').remove(); AccountingModule.updateVoucherTotals();"><i class="fa-solid fa-trash"></i></button></td>
        `;
        tbody.appendChild(row);
        this.populateAccountSelects();
    },

    populateAccountSelects: async function() {
        if (!this.accounts) {
            try {
                const res = await fetch(APP_URL + '/api/admin/accounting?action=accounts');
                const result = await res.json();
                if (result.success) {
                    this.accounts = result.data;
                }
            } catch (e) {
                console.error('Error fetching accounts', e);
            }
        }
        
        if (this.accounts) {
            const selects = document.querySelectorAll('.account-select');
            selects.forEach(select => {
                if (select.options.length > 1) return;
                this.accounts.forEach(acc => {
                    const opt = document.createElement('option');
                    opt.value = acc.id;
                    opt.textContent = `${acc.code || ''} - ${acc.name}`;
                    select.appendChild(opt);
                });
            });
        }
    },

    updateVoucherTotals: function() {
        const debits = document.querySelectorAll('.debit-input');
        const credits = document.querySelectorAll('.credit-input');
        let totalD = 0, totalC = 0;
        debits.forEach(i => totalD += parseFloat(i.value || 0));
        credits.forEach(i => totalC += parseFloat(i.value || 0));
        document.getElementById('totalDebit').textContent = totalD.toFixed(2);
        document.getElementById('totalCredit').textContent = totalC.toFixed(2);
    },

    saveVoucher: async function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        const postings = [];
        const rows = document.querySelectorAll('#voucherRows tr');
        rows.forEach(row => {
            const accountId = row.querySelector('.account-select').value;
            const debit = row.querySelector('.debit-input').value;
            const credit = row.querySelector('.credit-input').value;
            if (accountId && (parseFloat(debit) > 0 || parseFloat(credit) > 0)) {
                postings.push({ account_id: accountId, debit, credit });
            }
        });

        data.postings = postings;

        try {
            const res = await fetch(APP_URL + '/api/admin/accounting?action=vouchers', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert('Voucher saved successfully!');
                this.renderAction('coa'); // For now, go back to COA or clear form
            } else {
                alert('Error: ' + result.message);
            }
        } catch (e) {
            alert('Fata error saving voucher');
        }
    },

    renderTrialBalance: async function(container) {
        container.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <span class="bc-cur">Trial Balance</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-scale-balanced"></i></div>
                        <div>
                            <div class="pg-title">Trial Balance</div>
                            <div class="pg-sub">Debit and Credit verification</div>
                        </div>
                    </div>
                    <div class="pg-acts" style="display:flex; gap:10px; align-items:center;">
                        <input type="date" id="tb_date_from" class="form-control" title="From Date">
                        <input type="date" id="tb_date_to" class="form-control" title="To Date">
                        <button class="btn bt" onclick="AccountingModule.loadTrialBalance()">Filter</button>
                    </div>
                </div>
                <div class="card" id="trialBalanceList">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Calculating trial balance...</span>
                    </div>
                </div>
            </div>
        `;
        this.loadTrialBalance();
    },

    loadTrialBalance: async function() {
        const container = document.getElementById('trialBalanceList');
        const fromDate = document.getElementById('tb_date_from') ? document.getElementById('tb_date_from').value : '';
        const toDate = document.getElementById('tb_date_to') ? document.getElementById('tb_date_to').value : '';
        
        container.innerHTML = '<div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading...</span></div>';
        
        try {
            const url = APP_URL + '/api/admin/accounting?action=trial-balance' 
                        + (fromDate ? '&date_from=' + fromDate : '') 
                        + (toDate ? '&date_to=' + toDate : '');
            const res = await fetch(url);
            const result = await res.json();
            if (result.success) {
                let html = '<table class="data-table"><thead><tr><th>Account Name</th><th>Type</th><th style="text-align:right;">Debit</th><th style="text-align:right;">Credit</th></tr></thead><tbody>';
                let totalD = 0, totalC = 0;
                result.data.forEach(acc => {
                    const d = parseFloat(acc.total_debit || 0);
                    const c = parseFloat(acc.total_credit || 0);
                    totalD += d;
                    totalC += c;
                    html += `<tr>
                        <td style="font-weight:600;">${acc.name}</td>
                        <td>${acc.type.toUpperCase()}</td>
                        <td style="text-align:right;">${d.toFixed(2)}</td>
                        <td style="text-align:right;">${c.toFixed(2)}</td>
                    </tr>`;
                });
                html += `</tbody><tfoot><tr style="font-weight:800; background:#f8fafc;">
                    <td colspan="2">TOTAL</td>
                    <td style="text-align:right;">${totalD.toFixed(2)}</td>
                    <td style="text-align:right;">${totalC.toFixed(2)}</td>
                </tr></tfoot></table>`;
                container.innerHTML = html;
            }
        } catch (e) {
            container.innerHTML = 'Error loading trial balance';
        }
    },

    renderReports: function(container) {
        container.innerHTML = '<h3>Financial Reports Coming Soon</h3>';
    },

    renderLedger: function(container) {
        container.innerHTML = '<h3>General Ledger Coming Soon</h3>';
    },

    showAddAccountModal: function() {
        let parentOptions = '<option value="">(None)</option>';
        if (this.accounts) {
            this.accounts.filter(a => a.is_group).forEach(a => {
                parentOptions += `<option value="${a.id}">${a.code || ''} - ${a.name}</option>`;
            });
        }

        Swal.fire({
            title: 'Create New Account',
            html: `
                <div style="text-align: left; padding: 10px;">
                    <div class="form-group mb">
                        <label class="form-label">Account Name *</label>
                        <input type="text" id="acc_name" class="form-control" placeholder="e.g. Petty Cash">
                    </div>
                    <div class="form-group mb">
                        <label class="form-label">Account Code</label>
                        <input type="text" id="acc_code" class="form-control" placeholder="e.g. 1001">
                    </div>
                    <div class="form-group mb">
                        <label class="form-label">Account Type *</label>
                        <select id="acc_type" class="form-control">
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="form-group mb">
                        <label class="form-label">Parent Account</label>
                        <select id="acc_parent" class="form-control">
                            ${parentOptions}
                        </select>
                    </div>
                    <div class="form-group mb">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" id="acc_is_group"> 
                            <span>This is a Group Account (category)</span>
                        </label>
                    </div>
                    <div class="form-group mb">
                        <label class="form-label">Opening Balance</label>
                        <input type="number" step="0.01" id="acc_opening" class="form-control" value="0.00">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Create Account',
            confirmButtonColor: '#6366f1',
            preConfirm: () => {
                const name = document.getElementById('acc_name').value;
                const type = document.getElementById('acc_type').value;
                if (!name) {
                    Swal.showValidationMessage('Account Name is required');
                    return false;
                }
                return {
                    name,
                    code: document.getElementById('acc_code').value,
                    type,
                    parent_id: document.getElementById('acc_parent').value,
                    is_group: document.getElementById('acc_is_group').checked ? 1 : 0,
                    opening_balance: document.getElementById('acc_opening').value || 0
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.saveAccount(result.value);
            }
        });
    },

    saveAccount: async function(data) {
        try {
            const res = await fetch(APP_URL + '/api/admin/accounting?action=accounts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire('Success', 'Account created successfully!', 'success').then(() => {
                    this.accounts = null; // Clear cache
                    this.loadCOA(); // Refresh list
                });
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to create account', 'error');
        }
    },

    showAddExpenseModal: async function() {
        if (!this.accounts) {
            const res = await fetch(APP_URL + '/api/admin/accounting?action=accounts');
            const result = await res.json();
            if (result.success) this.accounts = result.data;
        }
        
        // Filter account options
        let expenseOptions = '';
        let assetOptions = '';
        
        if (this.accounts) {
            this.accounts.forEach(a => {
                if (a.type === 'expense') {
                    expenseOptions += `<option value="${a.id}">${a.code || ''} - ${a.name}</option>`;
                } else if (a.type === 'asset') {
                    assetOptions += `<option value="${a.id}">${a.code || ''} - ${a.name}</option>`;
                }
            });
        }

        Swal.fire({
            title: 'Record Expense',
            html: `
                <div style="text-align: left; padding: 10px;">
                    <div class="form-group mb">
                        <label class="form-label">Date *</label>
                        <input type="date" id="exp_date" class="form-control" value="${new Date().toISOString().split('T')[0]}" required>
                    </div>
                    <div class="form-group mb">
                        <label class="form-label">Expense Account *</label>
                        <select id="exp_account_id" class="form-control">
                            <option value="">Select Expense Type</option>
                            ${expenseOptions}
                        </select>
                    </div>
                    <div class="form-group mb">
                        <label class="form-label">Pay From (Asset/Cash) *</label>
                        <select id="exp_payment_account_id" class="form-control">
                            <option value="">Select Payment Method</option>
                            ${assetOptions}
                        </select>
                    </div>
                    <div class="form-group mb">
                        <label class="form-label">Amount *</label>
                        <input type="number" step="0.01" id="exp_amount" class="form-control" value="" placeholder="0.00" required>
                    </div>
                    <div class="form-group mb">
                        <label class="form-label">Description / Narration</label>
                        <input type="text" id="exp_narration" class="form-control" placeholder="e.g. Office supplies">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Record Expense',
            confirmButtonColor: '#d32f2f',
            preConfirm: () => {
                const actDebit = document.getElementById('exp_account_id').value;
                const actCredit = document.getElementById('exp_payment_account_id').value;
                const amount = document.getElementById('exp_amount').value;
                const date = document.getElementById('exp_date').value;
                
                if (!actDebit || !actCredit || !amount || parseFloat(amount) <= 0) {
                    Swal.showValidationMessage('Please fill all required fields correctly');
                    return false;
                }
                
                return {
                    date: date,
                    fiscal_year_id: 1, // Fallback, could dynamically query active FY instead
                    voucher_no: 'EXP-' + new Date().getTime(),
                    type: 'payment',
                    narration: document.getElementById('exp_narration').value || 'Expense Recording',
                    postings: [
                        { account_id: actDebit, debit: amount, credit: 0, description: document.getElementById('exp_narration').value },
                        { account_id: actCredit, debit: 0, credit: amount, description: document.getElementById('exp_narration').value }
                    ]
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.saveExpenseVoucher(result.value);
            }
        });
    },

    saveExpenseVoucher: async function(data) {
        try {
            Swal.fire({
                title: 'Saving Expense...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            const res = await fetch(APP_URL + '/api/admin/accounting?action=vouchers', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire('Success', 'Expense recorded successfully!', 'success').then(() => {
                    this.renderAction('dashboard');
                });
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to record expense. ' + e.message, 'error');
        }
    }
};

window.AccountingModule = AccountingModule;
