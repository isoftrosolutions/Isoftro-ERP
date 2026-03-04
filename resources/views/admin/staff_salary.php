<?php
/**
 * Staff Salary Management - Admin View
 * Handles UI for tracking and managing staff salaries (Teachers, Front Desk, etc.)
 */
?>
<div class="page-header">
    <div class="page-title">
        <i class="fa-solid fa-wallet pulse-icon"></i>
        <div>
            <h1>Staff Salary Management</h1>
            <p>Manage and track salary payments for all staff members</p>
        </div>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="goNav('staff-salary', 'add')">
            <i class="fa-solid fa-plus"></i> Add Payment
        </button>
    </div>
</div>

<div class="filter-card card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select id="filterRole" class="form-select" onchange="loadSalaries()">
                    <option value="">All Staff</option>
                    <option value="teacher">Teachers</option>
                    <option value="frontdesk">Front Desk</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <select id="filterMonth" class="form-select" onchange="loadSalaries()">
                    <?php
                    $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                    $currentMonth = (int)date('m');
                    foreach ($months as $i => $m) {
                        $selected = ($i + 1 == $currentMonth) ? 'selected' : '';
                        echo "<option value='" . ($i + 1) . "' $selected>$m</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Year</label>
                <select id="filterYear" class="form-select" onchange="loadSalaries()">
                    <?php
                    $currentYear = (int)date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                        echo "<option value='$y'>$y</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 overflow-hidden">
    <div class="table-container">
        <table class="table table-hover align-middle mb-0" id="salaryTable">
            <thead class="table-light">
                <tr>
                    <th>Staff Name</th>
                    <th>Role</th>
                    <th>Period</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="salaryList">
                <tr>
                    <td colspan="7" class="text-center py-5 gray-txt">
                        <i class="fa-solid fa-spinner fa-spin fa-2x mb-3"></i><br>
                        Loading salary records...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


<script>
// Initialize directly for SPA compatibility
(function() {
    loadStaffList();
    loadSalaries();
})();

async function loadStaffList() {
    const staffSelect = document.getElementById('staffSelect');
    if (!staffSelect) return;
    staffSelect.innerHTML = '<option value="">Choose staff...</option>';
    
    try {
        // Load teachers
        const resT = await fetch(window.APP_URL + '/api/admin/staff?role=teacher');
        const dataT = await resT.json();
        if (dataT.success) {
            dataT.data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.user_id;
                opt.setAttribute('data-role', 'Teacher');
                opt.textContent = `${s.full_name} (Teacher)`;
                staffSelect.appendChild(opt);
            });
        }

        // Load front desk
        const resF = await fetch(window.APP_URL + '/api/admin/staff?role=frontdesk');
        const dataF = await resF.json();
        if (dataF.success) {
            dataF.data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.user_id;
                opt.setAttribute('data-role', 'Front Desk');
                opt.textContent = `${s.name || s.full_name} (Front Desk)`;
                staffSelect.appendChild(opt);
            });
        }
    } catch (e) {
        console.error('Error loading staff list:', e);
    }
}

async function loadSalaries() {
    const filters = {
        role: document.getElementById('filterRole').value,
        month: document.getElementById('filterMonth').value,
        year: document.getElementById('filterYear').value
    };

    const qs = new URLSearchParams(filters).toString();
    try {
        const res = await fetch(window.APP_URL + '/api/admin/salary?' + qs);
        const data = await res.json();
        if (data.success) {
            renderSalaries(data.data);
        } else if (typeof showToast === 'function') {
            showToast('error', data.message);
        }
    } catch (e) {
        console.error('Error loading salaries:', e);
    }
}

function renderSalaries(data) {
    const list = document.getElementById('salaryList');
    if (!list) return;
    list.innerHTML = '';

    if (data.length === 0) {
        list.innerHTML = '<tr><td colspan="7" class="text-center py-4">No records found</td></tr>';
        return;
    }

    const months = ["", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    data.forEach(s => {
        const roleBadge = s.staff_role === 'teacher' ? 'bg-primary' : 'bg-info';
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div class="initial-box sm me-2">${s.staff_name.charAt(0)}</div>
                    <div class="fw-bold">${s.staff_name}</div>
                </div>
            </td>
            <td><span class="badge ${roleBadge} text-white">${s.staff_role.toUpperCase()}</span></td>
            <td>${months[s.month]} ${s.year}</td>
            <td class="fw-bold">NPR ${parseFloat(s.amount).toLocaleString()}</td>
            <td>${s.payment_date}</td>
            <td><span class="badge bg-success text-white">${s.status.toUpperCase()}</span></td>
            <td>
                <button class="btn btn-sm btn-light me-1 btn-edit-salary">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button class="btn btn-sm btn-light text-danger btn-delete-salary">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;

        row.querySelector('.btn-edit-salary').onclick = () => goNav('staff-salary', 'edit', {id: s.id});
        row.querySelector('.btn-delete-salary').onclick = () => deleteSalary(s.id);
        list.appendChild(row);
    });
}


async function deleteSalary(id) {
    if (!confirm('Are you sure you want to delete this salary record?')) return;

    try {
        const res = await fetch(window.APP_URL + '/api/admin/salary', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({id: id})
        });
        const result = await res.json();
        if (result.success) {
            if (typeof showToast === 'function') showToast('success', result.message);
            loadSalaries();
        } else {
            if (typeof showToast === 'function') showToast('error', result.message);
        }
    } catch (e) {
        console.error('Error deleting salary:', e);
    }
}

function resetFilters() {
    document.getElementById('filterRole').value = '';
    document.getElementById('filterMonth').value = '<?php echo (int)date('m'); ?>';
    document.getElementById('filterYear').value = '<?php echo (int)date('Y'); ?>';
    loadSalaries();
}
</script>

<style>
.initial-box.sm { width: 30px; height: 30px; border-radius: 6px; background: #f0f3f7; color: #5a6d90; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; }
.required:after { content: " *"; color: red; }
.pulse-icon { color: var(--brand); font-size: 2rem; margin-right: 15px; }
.gray-txt { color: #94a3b8; }
</style>
