/**
 * Hamro ERP — Accounting Module (IA)
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
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <span class="bc-cur">Accounting Dashboard</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-gauge"></i></div>
                        <div>
                            <div class="pg-title">Accounting Overview</div>
                            <div class="pg-sub">Financial status at a glance</div>
                        </div>
                    </div>
                </div>
                <div class="sg mb">
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-t"><i class="fa-solid fa-vault"></i></div></div>
                        <div class="sc-val">NPR 0.00</div>
                        <div class="sc-lbl">Total Cash & Bank</div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-b"><i class="fa-solid fa-hand-holding-dollar"></i></div></div>
                        <div class="sc-val">NPR 0.00</div>
                        <div class="sc-lbl">Accounts Receivable</div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-r"><i class="fa-solid fa-file-invoice-dollar"></i></div></div>
                        <div class="sc-val">NPR 0.00</div>
                        <div class="sc-lbl">Accounts Payable</div>
                    </div>
                </div>
                <div class="card">
                    <div class="ct">Recent Transactions</div>
                    <div id="recentTransactions">Loading...</div>
                </div>
            </div>
        `;
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
        try {
            const res = await fetch(APP_URL + '/api/admin/accounting?action=trial-balance');
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
    }
};

window.AccountingModule = AccountingModule;
