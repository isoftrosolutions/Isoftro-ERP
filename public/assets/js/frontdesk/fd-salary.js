/**
 * Hamro ERP — ia-salary.js
 * Staff Salary Management: List, Add, Edit, Delete
 */

window.renderStaffSalary = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Staff Salary</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-wallet"></i></div><div><div class="pg-title">Staff Salary Management</div><div class="pg-sub">Manage and track salary payments for all staff</div></div></div>
            <div class="pg-acts"><button class="btn bt" onclick="goNav('staff-salary', 'add')"><i class="fa-solid fa-plus"></i> Add Payment</button></div>
        </div>
        <div id="salaryContainer"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading salary records...</span></div></div>
    </div>`;

    try {
        const res = await fetch(`${APP_URL}/resources/views/admin/staff_salary.php?partial=true`);
        const html = await res.text();
        const salaryContainer = document.getElementById('salaryContainer');
        salaryContainer.innerHTML = html;
        
        salaryContainer.querySelectorAll('script').forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

    } catch (e) {
        document.getElementById('salaryContainer').innerHTML = `<div style="padding:40px;text-align:center;color:var(--red);">${e.message}</div>`;
    }
};

window.renderStaffSalaryForm = async function(id = null) {
    const mc = document.getElementById('mainContent');
    const isEdit = !!id;
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <a href="#" onclick="goNav('staff-salary')">Staff Salary</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">${isEdit ? 'Edit' : 'Add'} Payment</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-wallet"></i></div><div><div class="pg-title">${isEdit ? 'Edit' : 'Add'} Salary Payment</div><div class="pg-sub">Fill details below to record a staff salary payment</div></div></div>
        </div>
        <div class="card fu" style="max-width:800px; margin:0 auto; padding:30px;">
            <form id="salaryForm">
                <input type="hidden" id="salaryId" value="${id || ''}">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Staff *</label>
                    <select id="staffSelect" class="form-select shadow-none" required>
                        <option value="">Loading staff...</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Amount (NPR) *</label>
                        <input type="number" id="salAmount" class="form-control shadow-none" required placeholder="0.00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Payment Date *</label>
                        <input type="date" id="salDate" class="form-control shadow-none" value="${new Date().toISOString().split('T')[0]}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Month *</label>
                        <select id="salMonth" class="form-select shadow-none">
                            <option value="1">January</option><option value="2">February</option><option value="3">March</option>
                            <option value="4">April</option><option value="5">May</option><option value="6">June</option>
                            <option value="7">July</option><option value="8">August</option><option value="9">September</option>
                            <option value="10">October</option><option value="11">November</option><option value="12">December</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Year *</label>
                        <select id="salYear" class="form-select shadow-none"></select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Payment Method</label>
                    <select id="salMethod" class="form-select shadow-none">
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cheque">Cheque</option>
                        <option value="E-Sewa/Khalti">E-Sewa/Khalti</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Remarks</label>
                    <textarea id="salRemarks" class="form-control shadow-none" rows="3" placeholder="Optional notes..."></textarea>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end; border-top:1px solid #e2e8f0; padding-top:20px;">
                    <button type="button" class="btn bs" onclick="goNav('staff-salary')">Cancel</button>
                    <button type="button" class="btn bt" onclick="window.saveSalaryFromPage()">${isEdit ? 'Update' : 'Save'} Payment</button>
                </div>
            </form>
        </div>
    </div>`;

    // Initialize/Populate
    const currentMonth = new Date().getMonth() + 1;
    document.getElementById('salMonth').value = currentMonth;
    
    const yearSelect = document.getElementById('salYear');
    const curYear = new Date().getFullYear();
    for (let y = curYear - 1; y <= curYear + 1; y++) {
        const opt = document.createElement('option');
        opt.value = y; opt.textContent = y;
        if (y === curYear) opt.selected = true;
        yearSelect.appendChild(opt);
    }

    await loadStaffListForForm();

    const staffSelect = document.getElementById('staffSelect');
    staffSelect.addEventListener('change', (e) => {
        const selectedOpt = e.target.options[e.target.selectedIndex];
        const salary = selectedOpt.getAttribute('data-salary');
        if (salary && salary !== '0') {
            document.getElementById('salAmount').value = salary;
        }
    });

    if (isEdit) {
        await loadSalaryForEdit(id);
    }
};

async function loadStaffListForForm() {
    const staffSelect = document.getElementById('staffSelect');
    staffSelect.innerHTML = '<option value="">Choose staff...</option>';
    try {
        const [resT, resF] = await Promise.all([
            fetch(window.APP_URL + '/api/frontdesk/staff?role=teacher'),
            fetch(window.APP_URL + '/api/frontdesk/staff?role=frontdesk')
        ]);
        const [dataT, dataF] = await Promise.all([resT.json(), resF.json()]);
        
        if (dataT.success) dataT.data.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.user_id; 
            opt.textContent = `${s.name} (Teacher)`;
            opt.setAttribute('data-salary', s.monthly_salary || 0);
            staffSelect.appendChild(opt);
        });
        if (dataF.success) dataF.data.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.user_id; 
            opt.textContent = `${s.name || u.name} (Front Desk)`;
            opt.setAttribute('data-salary', s.monthly_salary || 0);
            staffSelect.appendChild(opt);
        });
    } catch (e) { console.error(e); }
}

async function loadSalaryForEdit(id) {
    try {
        const res = await fetch(window.APP_URL + '/api/frontdesk/salary?id=' + id);
        const result = await res.json();
        if (result.success && result.data.length > 0) {
            const s = result.data[0];
            document.getElementById('staffSelect').value = s.user_id;
            document.getElementById('salAmount').value = s.amount;
            document.getElementById('salMonth').value = s.month;
            document.getElementById('salYear').value = s.year;
            document.getElementById('salDate').value = s.payment_date;
            document.getElementById('salMethod').value = s.payment_method;
            document.getElementById('salRemarks').value = s.remarks;
        }
    } catch (e) { console.error(e); }
}

window.saveSalaryFromPage = async function() {
    const id = document.getElementById('salaryId').value;
    const data = {
        id: id,
        user_id: document.getElementById('staffSelect').value,
        amount: document.getElementById('salAmount').value,
        month: document.getElementById('salMonth').value,
        year: document.getElementById('salYear').value,
        payment_date: document.getElementById('salDate').value,
        payment_method: document.getElementById('salMethod').value,
        remarks: document.getElementById('salRemarks').value
    };

    if (!data.user_id || !data.amount) {
        if (typeof showToast === 'function') showToast('Please fill required fields', 'warn');
        return;
    }

    try {
        const res = await fetch(window.APP_URL + '/api/frontdesk/salary', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            if (typeof showToast === 'function') showToast(result.message, 'success');
            goNav('staff-salary');
        } else {
            if (typeof showToast === 'function') showToast(result.message, 'error');
        }
    } catch (e) {
        console.error(e);
        if (typeof showToast === 'function') showToast('Failed to save payment', 'error');
    }
};
