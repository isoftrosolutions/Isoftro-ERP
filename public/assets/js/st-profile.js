/**
 * Hamro ERP — Student Portal · st-profile.js
 * Student Profile Module: view profile with tabs (Personal, Course, Payment, Exam, Attendance)
 */

window._ST_Profile = { data: null, activeTab: 'personal', feesData: null, attendanceData: null, examData: null, courseData: null };

window.renderStudentProfile = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading profile...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/profile?action=view`);
        const result = await res.json();

        if (!result.success) {
            mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><h3>Error Loading Profile</h3><p>' + (result.message || 'Unable to load your profile data.') + '</p></div></div></div>';
            return;
        }

        window._ST_Profile.data = result.data;
        
        // Fetch additional data for tabs
        await Promise.all([
            loadProfileFees(),
            loadProfileAttendance(),
            loadProfileExams(),
            loadProfileCourse()
        ]);

        renderProfileWithTabs(mc);
    } catch (error) {
        console.error('Profile load error:', error);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><h3>Error</h3><p>Failed to load profile.</p></div></div></div>';
    }
};

async function loadProfileFees() {
    try {
        const res = await fetch(`${window.APP_URL}/api/student/profile?action=fees`);
        const result = await res.json();
        window._ST_Profile.feesData = result.success ? result.data : null;
    } catch (e) {
        window._ST_Profile.feesData = null;
    }
}

async function loadProfileAttendance() {
    try {
        const res = await fetch(`${window.APP_URL}/api/student/profile?action=attendance`);
        const result = await res.json();
        window._ST_Profile.attendanceData = result.success ? result.data : null;
    } catch (e) {
        window._ST_Profile.attendanceData = null;
    }
}

async function loadProfileExams() {
    try {
        const res = await fetch(`${window.APP_URL}/api/student/profile?action=exam_results`);
        const result = await res.json();
        window._ST_Profile.examData = result.success ? result.data : null;
    } catch (e) {
        window._ST_Profile.examData = null;
    }
}

async function loadProfileCourse() {
    try {
        const res = await fetch(`${window.APP_URL}/api/student/profile?action=course`);
        const result = await res.json();
        window._ST_Profile.courseData = result.success ? result.data : null;
    } catch (e) {
        window._ST_Profile.courseData = null;
    }
}

function renderProfileWithTabs(mc) {
    const s = window._ST_Profile.data;
    const tabs = [
        { id: 'personal', icon: 'fa-user', label: 'Personal' },
        { id: 'course', icon: 'fa-book', label: 'Course' },
        { id: 'payment', icon: 'fa-credit-card', label: 'Payment' },
        { id: 'exam', icon: 'fa-graduation-cap', label: 'Exam' },
        { id: 'attendance', icon: 'fa-calendar-check', label: 'Attendance' }
    ];
    const activeTab = window._ST_Profile.activeTab;
    const initials = (s.full_name || 'S').split(' ').filter(n => n).map(n => n[0] || '').join('').toUpperCase().substring(0, 2) || 'ST';
    const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';

    mc.innerHTML = `
        <div class="container-fluid p-4">
            <!-- Profile Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center g-4">
                        <div class="col-auto">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
                                 style="width:100px;height:100px;background:linear-gradient(135deg,var(--sa-primary),var(--sa-primary-h));font-size:2.5rem;font-weight:700;">
                                ${initials}
                            </div>
                        </div>
                        <div class="col">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <h2 class="mb-0 fs-4 fw-bold text-dark">${s.full_name || 'N/A'}</h2>
                                <span class="badge rounded-pill bg-success-soft text-success px-3 py-2 border border-success" style="font-size:11px;">
                                    <i class="fa-solid fa-check me-1"></i> ${(s.registration_status || 'Active').toUpperCase()}
                                </span>
                            </div>
                            <div class="d-flex flex-wrap gap-3 text-muted mb-2 small">
                                <span><i class="fa-solid fa-id-badge me-1 text-primary"></i> ${s.roll_no || 'No ID'}</span>
                                <span><i class="fa-solid fa-envelope me-1 text-primary"></i> ${s.email || s.login_email || 'N/A'}</span>
                                <span><i class="fa-solid fa-phone me-1 text-primary"></i> ${s.phone || s.login_phone || 'N/A'}</span>
                            </div>
                            <div class="text-primary fw-semibold small">
                                <i class="fa-solid fa-graduation-cap me-1"></i> ${s.course_name || 'No Course'} ${s.batch_name ? ' • ' + s.batch_name : ''}
                            </div>
                        </div>
                        <div class="col-12 col-lg-auto d-flex gap-2">
                            <button class="btn btn-primary d-inline-flex align-items-center gap-2 py-2 px-3 rounded-3 shadow-sm border-0" onclick="window.editStudentProfile()">
                                <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                            </button>
                            <button class="btn btn-light d-inline-flex align-items-center gap-2 py-2 px-3 rounded-3 shadow-sm border border-light" onclick="window.renderChangePassword()">
                                <i class="fa-solid fa-key"></i> Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="d-flex gap-1 mb-4 border-bottom overflow-auto hide-scrollbar">
                ${tabs.map(t => `
                    <button onclick="window.switchStudentProfileTab(this, '${t.id}')" 
                        class="btn rounded-0 px-4 py-3 border-0 border-bottom border-3 d-flex align-items-center gap-2 transition-all"
                        style="
                        border-bottom-color: ${activeTab === t.id ? 'var(--sa-primary)' : 'transparent'} !important;
                        color: ${activeTab === t.id ? 'var(--sa-primary)' : 'var(--tl)'};
                        font-weight: ${activeTab === t.id ? '600' : '500'};
                        background: none;
                        white-space:nowrap;
                    ">
                        <i class="fa-solid ${t.icon}"></i> ${t.label}
                    </button>
                `).join('')}
            </div>

            <!-- Tab Content -->
            <div id="profileTabContent" class="fade-in">
                ${renderProfileTabContent(activeTab)}
            </div>
        </div>
    `;
}

window.switchStudentProfileTab = function(el, tabId) {
    window._ST_Profile.activeTab = tabId;
    
    // Update tab styling
    document.querySelectorAll('[onclick^="switchStudentProfileTab"]').forEach(btn => {
        const isActive = btn.getAttribute('onclick').includes(tabId);
        btn.style.borderBottomColor = isActive ? 'var(--sa-primary)' : 'transparent';
        btn.style.color = isActive ? 'var(--sa-primary)' : 'var(--tl)';
        btn.style.fontWeight = isActive ? '600' : '500';
    });
    
    // Update content
    document.getElementById('profileTabContent').innerHTML = renderProfileTabContent(tabId);
};

function renderProfileTabContent(tabId) {
    const s = window._ST_Profile.data;
    const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';
    
    switch(tabId) {
        case 'personal':
            return renderPersonalTab(s, formatDate);
        case 'course':
            return renderCourseTab(s, formatDate);
        case 'payment':
            return renderPaymentTab(s);
        case 'exam':
            return renderExamTab(s);
        case 'attendance':
            return renderAttendanceTab(s);
        default:
            return '';
    }
}

function renderPersonalTab(s, formatDate) {
    const parse = (str) => { if (!str) return {}; try { return typeof str === 'string' ? JSON.parse(str) : str; } catch(e) { return {}; } };
    const pAddr = parse(s.permanent_address);
    const tAddr = parse(s.temporary_address);
    const quals = Array.isArray(parse(s.academic_qualifications)) ? parse(s.academic_qualifications) : [];
    const fmtAddr = (a) => {
        const parts = [a.ward ? `Ward-${a.ward}` : null, a.municipality, a.district, a.province].filter(Boolean);
        return parts.join(', ') || '—';
    };

    return `
        <div class="row g-4">
            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-user me-2 text-primary"></i> Personal Information</div></div>
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Full Name</span>
                            <span class="text-dark fw-medium">${s.full_name || '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Date of Birth</span>
                            <span class="text-dark fw-medium">${s.dob_ad || s.date_of_birth ? formatDate(s.dob_ad || s.date_of_birth) : '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Gender</span>
                            <span class="text-dark fw-medium">${s.gender ? s.gender.charAt(0).toUpperCase() + s.gender.slice(1) : '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Blood Group</span>
                            <span class="text-dark fw-medium">${s.blood_group || '-'}</span>
                        </div>
                        <div class="mb-0 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Nationality</span>
                            <span class="text-dark fw-medium">${s.nationality || '-'}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-address-book me-2 text-primary"></i> Contact Information</div></div>
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Email</span>
                            <span class="text-dark fw-medium overflow-hidden text-truncate ms-2" title="${s.email || s.login_email || '-'}">${s.email || s.login_email || '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Phone</span>
                            <span class="text-dark fw-medium">${s.phone || s.login_phone || '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Guardian</span>
                            <span class="text-dark fw-medium">${s.guardian_name || '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">G. Phone</span>
                            <span class="text-dark fw-medium">${s.guardian_phone || '-'}</span>
                        </div>
                        <div class="mb-0 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">G. Relation</span>
                            <span class="text-dark fw-medium">${s.guardian_relation || '-'}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-home me-2 text-primary"></i> Address Information</div></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted small fw-semibold mb-1">Permanent Address</div>
                            <div class="text-dark fw-medium">${fmtAddr(pAddr)}</div>
                        </div>
                        <div class="mb-0">
                            <div class="text-muted small fw-semibold mb-1">Temporary Address</div>
                            <div class="text-dark fw-medium">${fmtAddr(tAddr)}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-users me-2 text-primary"></i> Family Information</div></div>
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Father's Name</span>
                            <span class="text-dark fw-medium">${s.father_name || '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Mother's Name</span>
                            <span class="text-dark fw-medium">${s.mother_name || '-'}</span>
                        </div>
                        <div class="mb-0 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Husband Name</span>
                            <span class="text-dark fw-medium">${s.husband_name || '-'}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderCourseTab(s, formatDate) {
    const courseData = window._ST_Profile.courseData || s;
    
    return `
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-book me-2 text-primary"></i> Course Details</div></div>
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Course</span>
                            <span class="text-dark fw-medium">${s.course_name || '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Duration</span>
                            <span class="text-dark fw-medium">${courseData.duration || '-'}</span>
                        </div>
                        <div class="mb-0 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Course Fee</span>
                            <span class="text-dark fw-medium">${courseData.course_fee ? 'Rs. ' + parseFloat(courseData.course_fee).toLocaleString() : '-'}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-users me-2 text-primary"></i> Batch Details</div></div>
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Batch Name</span>
                            <span class="text-dark fw-medium">${s.batch_name || '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Start Date</span>
                            <span class="text-dark fw-medium">${courseData.batch_start_date ? formatDate(courseData.batch_start_date) : '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">End Date</span>
                            <span class="text-dark fw-medium">${courseData.batch_end_date ? formatDate(courseData.batch_end_date) : '-'}</span>
                        </div>
                        <div class="mb-0 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Status</span>
                            <span class="badge bg-success-soft text-success border border-success px-2 py-1" style="font-size:10px;">${(courseData.batch_status || 'Active').toUpperCase()}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-id-card me-2 text-primary"></i> Academic Info</div></div>
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Roll Number</span>
                            <span class="text-dark fw-medium">${s.roll_no || '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Registration Date</span>
                            <span class="text-dark fw-medium">${s.created_at ? formatDate(s.created_at) : '-'}</span>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Admission Date</span>
                            <span class="text-dark fw-medium">${s.admission_date ? formatDate(s.admission_date) : '-'}</span>
                        </div>
                        <div class="mb-0 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Status</span>
                            <span class="badge bg-primary-soft text-primary border border-primary px-2 py-1" style="font-size:10px;">${(s.registration_status || 'fully_registered').toUpperCase()}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderPaymentTab(s) {
    const feesData = window._ST_Profile.feesData;
    const summary = feesData?.summary || { total_due: 0, total_paid: 0, outstanding: 0 };
    const records = feesData?.records || [];
    const payments = feesData?.payments || [];

    return `
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">Rs. ${parseFloat(summary.total_paid || 0).toLocaleString()}</div>
                        <div class="small opacity-75">Total Paid</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">Rs. ${parseFloat(summary.outstanding || 0).toLocaleString()}</div>
                        <div class="small opacity-75">Pending Amount</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">Rs. ${parseFloat(summary.total_due || 0).toLocaleString()}</div>
                        <div class="small opacity-75">Total Fee</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-file-invoice me-2 text-primary"></i> Fee Records</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-muted small fw-semibold">Fee Item</th>
                                <th class="px-3 py-3 text-end text-muted small fw-semibold">Amount Due</th>
                                <th class="px-3 py-3 text-end text-muted small fw-semibold">Amount Paid</th>
                                <th class="px-3 py-3 text-end text-muted small fw-semibold">Balance</th>
                                <th class="px-3 py-3 text-center text-muted small fw-semibold">Due Date</th>
                                <th class="px-4 py-3 text-center text-muted small fw-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${records.length > 0 ? records.map(r => {
                                const balance = parseFloat(r.amount_due || 0) - parseFloat(r.amount_paid || 0);
                                const isPaid = balance <= 0;
                                return `
                                    <tr>
                                        <td class="px-4 py-3 fw-medium text-dark">${r.fee_item_name || 'Fee'}</td>
                                        <td class="px-3 py-3 text-end text-muted">Rs. ${parseFloat(r.amount_due || 0).toLocaleString()}</td>
                                        <td class="px-3 py-3 text-end text-muted">Rs. ${parseFloat(r.amount_paid || 0).toLocaleString()}</td>
                                        <td class="px-3 py-3 text-end fw-bold ${isPaid ? 'text-success' : 'text-danger'}">Rs. ${balance.toLocaleString()}</td>
                                        <td class="px-3 py-3 text-center text-muted small">${r.due_date ? new Date(r.due_date).toLocaleDateString() : '-'}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="badge rounded-pill ${isPaid ? 'bg-success-soft text-success border border-success' : 'bg-danger-soft text-danger border border-danger'}" style="font-size:10px;">
                                                ${isPaid ? 'PAID' : 'PENDING'}
                                            </span>
                                        </td>
                                    </tr>
                                `;
                            }).join('') : '<tr><td colspan="6" class="p-5 text-center text-muted">No fee records found.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        ${payments.length > 0 ? `
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-credit-card me-2 text-primary"></i> Payment History</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-muted small fw-semibold">Receipt No.</th>
                                <th class="px-3 py-3 text-end text-muted small fw-semibold">Amount</th>
                                <th class="px-3 py-3 text-center text-muted small fw-semibold">Payment Date</th>
                                <th class="px-3 py-3 text-center text-muted small fw-semibold">Mode</th>
                                <th class="px-3 py-3 text-center text-muted small fw-semibold">Status</th>
                                <th class="px-4 py-3 text-center text-muted small fw-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${payments.map(p => `
                                <tr>
                                    <td class="px-4 py-3 fw-bold text-primary">${p.receipt_number || ('-')}</td>
                                    <td class="px-3 py-3 text-end fw-medium">Rs. ${parseFloat(p.amount || 0).toLocaleString()}</td>
                                    <td class="px-3 py-3 text-center text-muted small">${p.payment_date ? new Date(p.payment_date).toLocaleDateString('en-US', {year:'numeric',month:'short',day:'numeric'}) : '-'}</td>
                                    <td class="px-3 py-3 text-center text-uppercase small fw-medium">${p.payment_mode || '-'}</td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="badge rounded-pill bg-success-soft text-success border border-success" style="font-size:10px;">COMPLETED</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        ${p.receipt_number ? `
                                            <a href="${window.APP_URL}/api/student/profile?action=receipt&receipt_no=${encodeURIComponent(p.receipt_number)}&is_pdf=1"
                                               target="_blank"
                                               class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2 rounded-3 px-3 py-1 shadow-sm"
                                               title="Download Receipt ${p.receipt_number}">
                                                <i class="fa-solid fa-download" style="font-size:11px;"></i>
                                                <span style="font-size:11px;">Download</span>
                                            </a>
                                        ` : '<span class="text-muted small">—</span>'}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        ` : ''}
    `;
}

function renderExamTab(s) {
    const examData = window._ST_Profile.examData;
    const results = examData?.results || [];
    const summary = examData?.summary || { total_exams: 0, passed: 0, failed: 0, average_percentage: 0 };

    return `
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">${summary.total_exams}</div>
                        <div class="small opacity-75">Total Exams</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">${summary.average_percentage}%</div>
                        <div class="small opacity-75">Avg. Score</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">${summary.passed}</div>
                        <div class="small opacity-75">Passed</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">${summary.failed}</div>
                        <div class="small opacity-75">Failed</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-clipboard-list me-2 text-primary"></i> Exam Results</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-muted small fw-semibold">Exam</th>
                                <th class="px-3 py-3 text-center text-muted small fw-semibold">Date</th>
                                <th class="px-3 py-3 text-center text-muted small fw-semibold">Subject</th>
                                <th class="px-3 py-3 text-end text-muted small fw-semibold">Total</th>
                                <th class="px-3 py-3 text-end text-muted small fw-semibold">Obtained</th>
                                <th class="px-4 py-3 text-center text-muted small fw-semibold">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${results.length > 0 ? results.map(r => {
                                const percentage = r.max_marks > 0 ? ((r.marks_obtained / r.max_marks) * 100).toFixed(1) : 0;
                                const isPass = parseFloat(percentage) >= (r.pass_marks || 40);
                                return `
                                    <tr>
                                        <td class="px-4 py-3 fw-medium text-dark">${r.exam_name || 'Exam'}</td>
                                        <td class="px-3 py-3 text-center text-muted small">${r.exam_date ? new Date(r.exam_date).toLocaleDateString() : '-'}</td>
                                        <td class="px-3 py-3 text-center text-muted small">${r.subject_name || '-'}</td>
                                        <td class="px-3 py-3 text-end text-muted small">${r.max_marks || 0}</td>
                                        <td class="px-3 py-3 text-end fw-bold text-success">${r.marks_obtained || 0}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="badge rounded-pill ${isPass ? 'bg-success-soft text-success border border-success' : 'bg-danger-soft text-danger border border-danger'}" style="font-size:10px;">
                                                ${percentage}%
                                            </span>
                                        </td>
                                    </tr>
                                `;
                            }).join('') : '<tr><td colspan="6" class="p-5 text-center text-muted">No exam results found.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

function renderAttendanceTab(s) {
    const attData = window._ST_Profile.attendanceData;
    const records = attData?.records || [];
    const summary = attData?.summary || { total: 0, present: 0, absent: 0, late: 0, percentage: 0 };

    return `
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">${summary.percentage}%</div>
                        <div class="small opacity-75">Overall Attendance</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">${summary.present}</div>
                        <div class="small opacity-75">Present Days</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">${summary.absent}</div>
                        <div class="small opacity-75">Absent Days</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;">
                    <div class="card-body text-center py-4">
                        <div class="fs-3 fw-bold mb-1">${summary.late}</div>
                        <div class="small opacity-75">Late Days</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bars -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success small fw-bold">Present</span>
                            <span class="text-muted small">${summary.total > 0 ? ((summary.present / summary.total) * 100).toFixed(1) : 0}%</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: ${summary.total > 0 ? (summary.present / summary.total * 100) : 0}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-danger small fw-bold">Absent</span>
                            <span class="text-muted small">${summary.total > 0 ? ((summary.absent / summary.total) * 100).toFixed(1) : 0}%</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: ${summary.total > 0 ? (summary.absent / summary.total * 100) : 0}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-warning small fw-bold">Late</span>
                            <span class="text-muted small">${summary.total > 0 ? ((summary.late / summary.total) * 100).toFixed(1) : 0}%</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: ${summary.total > 0 ? (summary.late / summary.total * 100) : 0}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><div class="fw-bold text-dark fs-6"><i class="fa-solid fa-calendar-check me-2 text-primary"></i> Attendance Records</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-muted small fw-semibold">Date</th>
                                <th class="px-3 py-3 text-muted small fw-semibold">Subject</th>
                                <th class="px-3 py-3 text-muted small fw-semibold">Status</th>
                                <th class="px-4 py-3 text-start text-muted small fw-semibold">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${records.length > 0 ? records.slice(0, 50).map(r => {
                                const status = (r.status || '').toLowerCase();
                                const statusStyles = {
                                    'present': { bg: 'bg-success-soft text-success border-success', label: 'PRESENT' },
                                    'absent': { bg: 'bg-danger-soft text-danger border-danger', label: 'ABSENT' },
                                    'late': { bg: 'bg-warning-soft text-warning border-warning', label: 'LATE' },
                                    'excused': { bg: 'bg-info-soft text-info border-info', label: 'EXCUSED' }
                                };
                                const st = statusStyles[status] || statusStyles.present;
                                return `
                                    <tr>
                                        <td class="px-4 py-3 text-muted small">${r.attendance_date ? new Date(r.attendance_date).toLocaleDateString() : '-'}</td>
                                        <td class="px-3 py-3 fw-medium text-dark">${r.subject_name || '-'}</td>
                                        <td class="px-3 py-3">
                                            <span class="badge rounded-pill border ${st.bg}" style="font-size:10px;">${st.label}</span>
                                        </td>
                                        <td class="px-4 py-3 text-start text-muted small">${r.notes || '—'}</td>
                                    </tr>
                                `;
                            }).join('') : '<tr><td colspan="4" class="p-5 text-center text-muted">No attendance records found.</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

window.editStudentProfile = async function() {
    const mc = document.getElementById('mainContent');
    const s = window._ST_Profile.data;
    if (!s) return;

    mc.innerHTML = `
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="fw-bold text-dark fs-5 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-pen-to-square text-primary"></i> Edit Profile
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <form id="profileEditForm">
                                <input type="hidden" name="id" value="${s.id}">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Full Name</label>
                                        <input type="text" name="full_name" class="form-control rounded-3" value="${s.full_name || ''}" required />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Email Address</label>
                                        <input type="email" name="email" class="form-control rounded-3" value="${s.email || s.login_email || ''}" required />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="form-control rounded-3" value="${s.dob_ad || s.date_of_birth || ''}" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Gender</label>
                                        <select name="gender" class="form-select rounded-3">
                                            <option value="">Select Gender</option>
                                            <option value="male" ${s.gender === 'male' ? 'selected' : ''}>Male</option>
                                            <option value="female" ${s.gender === 'female' ? 'selected' : ''}>Female</option>
                                            <option value="other" ${s.gender === 'other' ? 'selected' : ''}>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Blood Group</label>
                                        <select name="blood_group" class="form-select rounded-3">
                                            <option value="">Select</option>
                                            <option value="A+" ${s.blood_group === 'A+' ? 'selected' : ''}>A+</option>
                                            <option value="A-" ${s.blood_group === 'A-' ? 'selected' : ''}>A-</option>
                                            <option value="B+" ${s.blood_group === 'B+' ? 'selected' : ''}>B+</option>
                                            <option value="B-" ${s.blood_group === 'B-' ? 'selected' : ''}>B-</option>
                                            <option value="O+" ${s.blood_group === 'O+' ? 'selected' : ''}>O+</option>
                                            <option value="O-" ${s.blood_group === 'O-' ? 'selected' : ''}>O-</option>
                                            <option value="AB+" ${s.blood_group === 'AB+' ? 'selected' : ''}>AB+</option>
                                            <option value="AB-" ${s.blood_group === 'AB-' ? 'selected' : ''}>AB-</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control rounded-3" value="${s.phone || ''}" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Nationality</label>
                                        <input type="text" name="nationality" class="form-control rounded-3" value="${s.nationality || ''}" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Guardian Name</label>
                                        <input type="text" name="guardian_name" class="form-control rounded-3" value="${s.guardian_name || ''}" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Guardian Phone</label>
                                        <input type="tel" name="guardian_phone" class="form-control rounded-3" value="${s.guardian_phone || ''}" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold">Guardian Relation</label>
                                        <input type="text" name="guardian_relation" class="form-control rounded-3" value="${s.guardian_relation || ''}" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">General Address</label>
                                        <textarea name="address" class="form-control rounded-3" rows="3">${s.address || ''}</textarea>
                                    </div>
                                </div>
                                <div class="mt-4 d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-light px-4 py-2 rounded-3 border" onclick="window.renderStudentProfile()">Cancel</button>
                                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('profileEditForm').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        try {
            const res = await fetch(`${window.APP_URL}/api/student/profile?action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();

            if (result.success) {
                alert('Profile updated successfully!');
                window.renderStudentProfile();
            } else {
                alert(result.message || 'Failed to update profile');
            }
        } catch (err) {
            alert('Error updating profile: ' + err.message);
        }
    };
};

window.renderChangePassword = function() {
    const mc = document.getElementById('mainContent');

    mc.innerHTML = `
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="fw-bold text-dark fs-5 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-key text-primary"></i> Change Password
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <form id="passwordChangeForm">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Current Password</label>
                                    <input type="password" name="current_password" class="form-control rounded-3" placeholder="Enter current password" required />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">New Password</label>
                                    <input type="password" name="new_password" class="form-control rounded-3" placeholder="Minimum 6 characters" required minlength="6" />
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control rounded-3" placeholder="Repeat new password" required />
                                </div>
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-light px-4 py-2 rounded-3 border" onclick="window.renderStudentProfile()">Cancel</button>
                                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('passwordChangeForm').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        if (data.new_password !== data.confirm_password) {
            alert('New passwords do not match!');
            return;
        }

        if (data.new_password.length < 6) {
            alert('Password must be at least 6 characters!');
            return;
        }

        try {
            const res = await fetch(`${window.APP_URL}/api/student/profile?action=change_password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: data.current_password, new_password: data.new_password })
            });
            const result = await res.json();

            if (result.success) {
                alert('Password changed successfully!');
                window.renderStudentProfile();
            } else {
                alert(result.message || 'Failed to change password');
            }
        } catch (err) {
            alert('Error changing password: ' + err.message);
        }
    };
};

window.renderStudentProfile = window.renderStudentProfile;
window.editStudentProfile = window.editStudentProfile;
window.renderChangePassword = window.renderChangePassword;
window.switchStudentProfileTab = window.switchStudentProfileTab;
