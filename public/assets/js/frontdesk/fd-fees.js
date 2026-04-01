/**
 * iSoftro ERP — ia-fees.js
 * Fee Setup: List, Add, Edit, Delete fee items
 * Production-Ready Mobile-First Responsive UI
 */

/* ═══════════════════════════════════════════════════════════════════
   TOAST NOTIFICATION SYSTEM
   ═══════════════════════════════════════════════════════════════════ */
window._showToast = function(message, type = 'success') {
    let container = document.getElementById('fsToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'fsToastContainer';
        container.className = 'fs-toast-container';
        document.body.appendChild(container);
    }
    
    const icons = {
        success: '<svg class="fs-toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg class="fs-toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg class="fs-toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg class="fs-toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };
    
    const toast = document.createElement('div');
    toast.className = `fs-toast ${type} scale-in`;
    toast.innerHTML = `
        ${icons[type]}
        <div class="fs-toast-content">
            <div class="fs-toast-message">${message}</div>
        </div>
        <button class="fs-toast-close" aria-label="Close notification">&times;</button>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto dismiss
    const dismissTimer = setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
    
    // Manual dismiss
    toast.querySelector('.fs-toast-close').addEventListener('click', () => {
        clearTimeout(dismissTimer);
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    });
};

/* ═══════════════════════════════════════════════════════════════════
   CONFIRMATION DIALOG
   ═══════════════════════════════════════════════════════════════════ */
window._showConfirmDialog = function(options) {
    const { title, message, confirmText = 'Confirm', cancelText = 'Cancel', type = 'warning', onConfirm } = options;
    
    const icons = {
        danger: '<i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem;"></i>',
        warning: '<i class="fa-solid fa-circle-question" style="font-size: 2rem;"></i>'
    };
    
    const modalId = 'fsConfirmModal';
    const existing = document.getElementById(modalId);
    if (existing) existing.remove();
    
    const backdrop = document.createElement('div');
    backdrop.id = 'fsConfirmBackdrop';
    backdrop.className = 'fs-modal-backdrop active';
    
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'fs-modal fs-modal-confirm active';
    modal.innerHTML = `
        <div class="fs-modal-body">
            <div class="fs-confirm-icon ${type}">${icons[type]}</div>
            <h3 class="fs-confirm-title">${title}</h3>
            <p class="fs-confirm-message">${message}</p>
        </div>
        <div class="fs-modal-footer">
            <button class="fs-btn fs-btn-secondary" id="fsConfirmCancel">${cancelText}</button>
            <button class="fs-btn fs-btn-danger" id="fsConfirmOk">${confirmText}</button>
        </div>
    `;
    
    document.body.appendChild(backdrop);
    document.body.appendChild(modal);
    
    const closeDialog = () => {
        backdrop.classList.remove('active');
        modal.classList.remove('active');
        setTimeout(() => {
            backdrop.remove();
            modal.remove();
        }, 300);
    };
    
    document.getElementById('fsConfirmCancel').addEventListener('click', closeDialog);
    backdrop.addEventListener('click', closeDialog);
    
    document.getElementById('fsConfirmOk').addEventListener('click', () => {
        if (onConfirm) onConfirm();
        closeDialog();
    });
    
    // Focus trap for accessibility
    modal.querySelector('.fs-btn-primary, .fs-btn-danger').focus();
};

/* ═══════════════════════════════════════════════════════════════════
   FORM VALIDATION UTILITIES
   ═══════════════════════════════════════════════════════════════════ */
function _validateFormInput(input) {
    const formGroup = input.closest('.fs-form-group') || input.parentElement;
    if (!formGroup) return true;
    
    const isValid = input.checkValidity();
    
    if (isValid) {
        formGroup.classList.remove('has-error');
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    } else {
        formGroup.classList.add('has-error');
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
    }
    
    return isValid;
}

function _setupInlineValidation(form) {
    const inputs = form.querySelectorAll('.fs-form-input, .form-control');
    inputs.forEach(input => {
        input.addEventListener('blur', () => _validateFormInput(input));
        input.addEventListener('input', () => {
            if (input.classList.contains('is-invalid')) {
                _validateFormInput(input);
            }
        });
    });
}

/* ══════════════ HELPER FUNCTIONS ═══════════════════════════════ */
window.formatMoney = function(amount) {
    return parseFloat(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

window.formatDate = function(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' });
}

async function _safeFetch(url, options = {}) {
    const res = await fetch(url, options);
    const contentType = res.headers.get("content-type");
    
    // First, try to get the response text
    const text = await res.text();
    
    // Check if content-type indicates JSON, or if the text starts with JSON-like characters
    const isJsonContent = contentType && contentType.indexOf("application/json") !== -1;
    const looksLikeJson = text.trim().startsWith('{') || text.trim().startsWith('[');
    
    if (isJsonContent || looksLikeJson) {
        try {
            return JSON.parse(text);
        } catch (e) {
            // If it looks like JSON but parse fails, treat as non-JSON
            console.error('[ia-fees] Failed to parse JSON response:', text.substring(0, 200));
            throw new Error("Server returned invalid JSON. Check browser console for details.");
        }
    } else {
        console.error('[ia-fees] Server returned non-JSON response:', text.substring(0, 200));
        throw new Error("Server returned non-JSON response. Check browser console for details.");
    }
}

/* ═══════════════════════════════════════════════════════════════════
   FEE SETUP LIST - MOBILE FIRST UI
   ═══════════════════════════════════════════════════════════════════ */
window.renderFeeSetup = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
        <div class="fee-setup-module">
            <!-- Page Header -->
            <div class="fs-page-header">
                <div class="fs-page-header-content">
                    <h1 class="fs-page-title">
                        <span class="fs-page-title-icon">
                            <i class="fa-solid fa-sliders"></i>
                        </span>
                        Fee Items Setup
                    </h1>
                    <p class="fs-page-subtitle">Configure and manage fee structure for your courses</p>
                </div>
            </div>
            
            <!-- Action Bar -->
            <div class="fs-action-bar">
                <div class="fs-search-container">
                    <i class="fa-solid fa-search fs-search-icon"></i>
                    <input 
                        type="text" 
                        id="feeSearchInput" 
                        class="fs-form-input fs-search-input" 
                        placeholder="Search fee items..."
                        aria-label="Search fee items"
                        oninput="_filterFeeItems()"
                    >
                </div>
                <div class="fs-filter-group">
                    <select id="feeTypeFilter" class="fs-select" onchange="_filterFeeItems()" aria-label="Filter by fee type">
                        <option value="">All Types</option>
                        <option value="admission">Admission</option>
                        <option value="monthly">Monthly</option>
                        <option value="exam">Exam</option>
                        <option value="material">Material</option>
                        <option value="fine">Fine</option>
                        <option value="other">Other</option>
                    </select>
                    <select id="feeCourseFilter" class="fs-select" onchange="_filterFeeItems()" aria-label="Filter by course">
                        <option value="">All Courses</option>
                    </select>
                </div>
                <button class="fs-btn fs-btn-primary" onclick="openAddFeeModal()">
                    <i class="fa-solid fa-plus"></i>
                    <span class="fs-hide-mobile">Add Fee Item</span>
                    <span class="fs-hide-desktop">Add</span>
                </button>
            </div>
            
            <!-- Fee List Container -->
            <div class="fs-card" id="feeListContainer">
                <div class="fs-loading">
                    <div class="fs-loading-spinner"></div>
                    <span class="fs-loading-text">Loading fee items...</span>
                </div>
            </div>
        </div>
    `;
    await _loadFeeItems();
};

let feeItemsData = [];
let coursesData = [];

async function _loadFeeItems() {
    const c = document.getElementById('feeListContainer'); if (!c) return;
    const courseFilter = document.getElementById('feeCourseFilter');
    try {
        const result = await _safeFetch(APP_URL + '/api/frontdesk/fees');
        if (!result.success) throw new Error(result.message);
        
        feeItemsData = result.data || [];
        coursesData = result.courses || [];
        
        // Populate course filter
        if (courseFilter) {
            let options = '<option value="">All Courses</option>';
            coursesData.forEach(crs => {
                options += `<option value="${crs.id}">${crs.name}</option>`;
            });
            courseFilter.innerHTML = options;
        }
        
        _renderFeeItems(feeItemsData);
    } catch(e) { 
        c.innerHTML=`<div style="padding:20px;color:var(--red);text-align:center">${e.message}</div>`; 
    }
}

/* ── QUICK PAYMENT / CONSOLIDATED BILL OVERVIEW ──────────────────── */
window.renderQuickPayment = async (studentId) => {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Preparing bill overview...</span></div></div>`;

    try {
        const result = await _safeFetch(`${window.APP_URL}/api/frontdesk/fees?action=get_payment_init_data&student_id=${studentId}`);
        if (!result.success) throw new Error(result.message);

        const { student, institute, summary, records } = result.data;
        const photoSrc = student.photo_url ? (student.photo_url.startsWith('http') ? student.photo_url : window.APP_URL + student.photo_url) : null;
        const initials = (student.name || 'S').split(' ').filter(n => n).map(n => n[0] || '').join('').toUpperCase().substring(0, 2) || 'ST';

        let recordsHtml = '';
        if (records.length === 0) {
            recordsHtml = `<tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">No outstanding fees found.</td></tr>`;
        } else {
            records.forEach(r => {
                recordsHtml += `
                    <tr>
                        <td style="font-weight:600; color:#1e293b;">${r.fee_item_name}</td>
                        <td><span class="badge ${r.fee_type === 'monthly' ? 'bg-info' : 'bg-primary'}">${r.fee_type.replace('_',' ')}</span></td>
                        <td>${new Date(r.due_date).toLocaleDateString()}</td>
                        <td style="text-align:right; font-weight:700;">${getCurrencySymbol()}${parseFloat(r.amount_due).toLocaleString()}</td>
                        <td style="text-align:right; color:#ef4444; font-weight:700;">${getCurrencySymbol()}${parseFloat(r.amount_due - r.amount_paid).toLocaleString()}</td>
                    </tr>
                `;
            });
        }

        const totalDue = records.reduce((sum, r) => sum + (parseFloat(r.amount_due) - parseFloat(r.amount_paid)), 0);

        mc.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <a href="#" onclick="goNav('students')">Students</a> <span class="bc-sep">›</span>
                    <span class="bc-cur">Quick Payment</span>
                </div>

                <div class="card" style="max-width:1000px; margin:20px auto; overflow:hidden; border-radius:16px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.1);">
                    <!-- Institute Header -->
                    <div style="background:linear-gradient(135deg, #009E7E 0%, #007d63 100%); padding:30px; color:white; display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap:20px;">
                            ${institute.logo_path ? `<img src="${window.APP_URL}/public/${institute.logo_path}" style="height:60px; filter:brightness(0) invert(1);">` : `<div style="width:60px; height:60px; background:rgba(255,255,255,0.2); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:bold;">${institute.name[0]}</div>`}
                            <div>
                                <h1 style="margin:0; font-size:1.5rem; letter-spacing:-0.5px;">${institute.name}</h1>
                                <p style="margin:5px 0 0; opacity:0.8; font-size:0.9rem;"><i class="fa-solid fa-location-dot"></i> ${institute.address || ''}</p>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.8rem; opacity:0.7; text-transform:uppercase; font-weight:700; letter-spacing:1px;">Bill Overview</div>
                            <div style="font-size:1.2rem; font-weight:600;">${new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</div>
                        </div>
                    </div>

                    <div style="padding:30px; display:grid; grid-template-columns:1fr 1fr; gap:30px; background:#f8fafc;">
                        <!-- Student Section -->
                        <div class="card" style="padding:20px; border:none; box-shadow:0 1px 3px rgba(0,0,0,0.05); background:white;">
                            <h3 style="font-size:0.9rem; color:#64748b; margin-top:0; border-bottom:1px solid #f1f5f9; padding-bottom:10px; margin-bottom:15px; text-transform:uppercase; letter-spacing:0.5px;">Student Information</h3>
                            <div style="display:flex; gap:15px; align-items:center;">
                                <div style="width:70px; height:70px; border-radius:50%; overflow:hidden; background:#f1f5f9; border:3px solid #e2e8f0;">
                                    ${photoSrc ? `<img src="${photoSrc}" style="width:100%; height:100%; object-fit:cover;">` : `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#64748b; font-size:1.2rem;">${initials}</div>`}
                                </div>
                                <div style="flex:1;">
                                    <div style="font-size:1.1rem; font-weight:700; color:#1e293b;">${student.name}</div>
                                    <div style="font-size:0.85rem; color:#64748b; margin-top:2px;"><i class="fa-solid fa-id-badge"></i> ${student.roll_no || 'No Roll No'}</div>
                                    <div style="font-size:0.85rem; font-weight:600; color:#009E7E; margin-top:4px;">${student.course_name} • ${student.batch_name}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Section -->
                        <div class="card" style="padding:20px; border:none; box-shadow:0 1px 3px rgba(0,0,0,0.05); background:white;">
                            <h3 style="font-size:0.9rem; color:#64748b; margin-top:0; border-bottom:1px solid #f1f5f9; padding-bottom:10px; margin-bottom:15px; text-transform:uppercase; letter-spacing:0.5px;">Financial Summary</h3>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <div style="padding:10px; background:#f0fdf4; border-radius:10px;">
                                    <div style="font-size:0.75rem; color:#166534; font-weight:600;">Total Paid</div>
                                    <div style="font-size:1.1rem; font-weight:700; color:#166534;">${getCurrencySymbol()}${parseFloat(summary?.total_paid || 0).toLocaleString()}</div>
                                </div>
                                <div style="padding:10px; background:#fef2f2; border-radius:10px;">
                                    <div style="font-size:0.75rem; color:#991b1b; font-weight:600;">Due Amount</div>
                                    <div style="font-size:1.1rem; font-weight:700; color:#991b1b;">${getCurrencySymbol()}${parseFloat(summary?.due_amount || 0).toLocaleString()}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Outstanding Fees Table -->
                    <div style="padding:0 30px 30px;">
                        <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-list-check" style="color:#009E7E;"></i> Outstanding Fees
                        </h3>
                        <div class="table-responsive">
                            <table class="table" style="margin:0;">
                                <thead style="background:#f1f5f9;">
                                    <tr>
                                        <th style="padding:12px 15px; font-size:0.85rem; color:#475569;">Fee Item</th>
                                        <th style="padding:12px 15px; font-size:0.85rem; color:#475569;">Type</th>
                                        <th style="padding:12px 15px; font-size:0.85rem; color:#475569;">Due Date</th>
                                        <th style="padding:12px 15px; font-size:0.85rem; color:#475569; text-align:right;">Amount</th>
                                        <th style="padding:12px 15px; font-size:0.85rem; color:#475569; text-align:right;">Net Due</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${recordsHtml}
                                </tbody>
                                <tfoot style="background:#f8fafc; font-weight:700; border-top:2px solid #e2e8f0;">
                                    <tr>
                                        <td colspan="4" style="text-align:right; padding:15px; font-size:1rem; color:#1e293b;">Total Current Outstanding:</td>
                                        <td style="text-align:right; padding:15px; font-size:1.1rem; color:#ef4444;">${getCurrencySymbol()}${totalDue.toLocaleString()}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Payment Form Section -->
                    <div style="padding:30px; background:#f1f5f9; border-top:1px solid #e2e8f0;">
                        <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-money-bill-transfer" style="color:#009E7E;"></i> Record New Payment
                        </h3>
                        <form id="quickPaymentForm" class="row">
                            <input type="hidden" name="student_id" value="${studentId}">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Amount to Pay (${getCurrencySymbol()})</label>
                                    <input type="number" name="amount" class="form-control" value="${totalDue}" min="1" max="${totalDue}" required style="font-weight:bold; font-size:1.1rem;">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_mode" class="form-control" required>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="esewa">eSewa</option>
                                        <option value="khalti">Khalti</option>
                                        <option value="card">Card</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control" value="${new Date().toISOString().split('T')[0]}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Notes / Reference</label>
                                    <input type="text" name="notes" class="form-control" placeholder="e.g., Transaction ID, Cheque No.">
                                </div>
                            </div>
                            
                            <div class="col-12" style="margin-top:20px; display:flex; justify-content:flex-end; gap:12px; border-top:1px solid #cbd5e1; padding-top:25px;">
                                <button type="button" class="btn bs" onclick="goNav('students','profile',{id:${studentId}})" style="padding:10px 25px;">Cancel</button>
                                <button type="submit" class="btn bt" style="padding:10px 35px; background:#009e7e; font-weight:600; border-radius:10px;">
                                    <i class="fa-solid fa-check-circle"></i> Proceed to Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Helper to perform the payment submission
        const _submitPayment = async (form) => {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            const btn = form.querySelector('button[type="submit"]');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Initializing...';

            const modalData = {
                studentName: student.name,
                amount: getCurrencySymbol() + parseFloat(data.amount).toLocaleString(),
                method: data.payment_mode.charAt(0).toUpperCase() + data.payment_mode.slice(1).replace('_', ' ')
            };

            const restoreBtn = () => { btn.disabled = false; btn.innerHTML = orig; };

            // Step 1: Open the modal (with double-click guard)
            if (window.PaymentProcessor) {
                if (!window.PaymentProcessor.open(modalData)) {
                    restoreBtn();
                    return; // Already running, ignore
                }
            }

            try {
                // Step 1 is visible — make the actual API call
                const result = await _safeFetch(`${window.APP_URL}/api/frontdesk/fees`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'record_bulk_payment',
                        student_id: studentId,
                        amount: data.amount,
                        payment_mode: data.payment_mode,
                        payment_date: data.payment_date,
                        notes: data.notes
                    })
                });

                if (!result.success) {
                    throw new Error(result.message || 'Payment failed');
                }

                const d = result.data;

                if (window.PaymentProcessor) {
                    // Step 2: Generating receipt PDF (cosmetic)
                    await window.PaymentProcessor.goToStep(2);
                    await new Promise(r => setTimeout(r, 800));

                    // Step 3: Sending to student (cosmetic)
                    await window.PaymentProcessor.goToStep(3);
                    await new Promise(r => setTimeout(r, 600));

                    // Success screen with real data
                    await window.PaymentProcessor.showSuccess({
                        studentName: student.name,
                        amount: getCurrencySymbol() + parseFloat(data.amount).toLocaleString(),
                        method: data.payment_mode.charAt(0).toUpperCase() + data.payment_mode.slice(1).replace('_', ' '),
                        txnId: (d.transaction_ids && d.transaction_ids[0]) || 'TXN-' + Math.random().toString(36).substr(2, 9).toUpperCase(),
                        downloadUrl: `${window.APP_URL}/api/frontdesk/fees?action=generate_receipt_html&is_pdf=1&receipt_no=${d.receipt_no}`
                    });
                    
                    // After the user closes the modal or clicks 'Done', redirect them to the history page
                    window.onPaymentRecordsView = () => goNav('fee', 'record');
                } else {
                    const d2 = result.data;
                    goNav('fee', 'details', { receipt_no: d2.receipt_no });
                }

                restoreBtn();

            } catch (err) {
                console.error('[fd-fees] Payment error:', err);

                if (window.PaymentProcessor) {
                    window.PaymentProcessor.showError(
                        err.message || 'An unexpected error occurred. Please try again.',
                        () => _submitPayment(form) // Retry callback
                    );
                } else {
                    Swal.fire('Error', err.message || 'Something went wrong while processing the payment', 'error');
                }

                restoreBtn();
            }
        };

        document.getElementById('quickPaymentForm').onsubmit = (e) => {
            e.preventDefault();
            _submitPayment(e.target);
        };

    } catch (error) {
        console.error(error);
        mc.innerHTML = `<div class="card" style="padding:60px; text-align:center; color:var(--red);">
            <i class="fa-solid fa-circle-exclamation" style="font-size:3rem; margin-bottom:10px;"></i>
            <p>${error.message}</p>
            <button class="btn bt" onclick="goNav('students')">Back to Directory</button>
        </div>`;
    }
}

/* ── POST-PAYMENT SUCCESS SCREEN ────────────────────────────────── */
window._showEmailSendingScreen = (receiptNo, studentName, studentId, emailStatus = null) => {
    // Redirection is now handled in the success handlers
    goNav('fee', 'details', { receipt_no: receiptNo });
};

window.openReceipt = (receiptNo, transactionId = null) => {
    let url = `${window.APP_URL}/api/frontdesk/fees?action=generate_receipt_html`;
    if (transactionId) {
        url += `&transaction_id=${transactionId}`;
    } else {
        url += `&receipt_no=${receiptNo}`;
    }
    window.open(url, '_blank');
};

function _renderFeeItems(items) {
    const c = document.getElementById('feeListContainer'); if (!c) return;
    
    if (!items.length) {
        c.innerHTML = `
            <div class="fs-empty">
                <div class="fs-empty-icon">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <h3 class="fs-empty-title">No Fee Items Found</h3>
                <p class="fs-empty-description">
                    Click "Add Fee Item" to create your first fee structure for courses.
                </p>
                <button class="fs-btn fs-btn-primary" onclick="openAddFeeModal()">
                    <i class="fa-solid fa-plus"></i> Add First Fee Item
                </button>
            </div>
        `;
        return;
    }
    
    // Mobile-first responsive table
    let html = `
        <div class="fs-table-container">
            <table class="fs-table">
                <thead>
                    <tr>
                        <th>Fee Name</th>
                        <th>Course</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Installments</th>
                        <th>Late Fine</th>
                        <th>Status</th>
                        <th aria-label="Actions"></th>
                    </tr>
                </thead>
                <tbody class="fs-stagger">
    `;
    
    const typeLabels = {
        'admission': 'Admission',
        'monthly': 'Monthly',
        'exam': 'Exam',
        'material': 'Material',
        'fine': 'Fine',
        'other': 'Other'
    };
    
    items.forEach(fi => {
        html += `
            <tr>
                <td data-label="Fee Name">
                    <div style="font-weight: 600; color: var(--fs-text);">${fi.name}</div>
                </td>
                <td data-label="Course">${fi.course_name || '<span class="fs-text-muted">-</span>'}</td>
                <td data-label="Type">
                    <span class="fs-badge fs-badge-${fi.type}">${typeLabels[fi.type] || fi.type}</span>
                </td>
                <td data-label="Amount" style="font-weight: 600;">
                    NPR ${parseFloat(fi.amount).toLocaleString()}
                </td>
                <td data-label="Installments">${fi.installments || 1}</td>
                <td data-label="Late Fine">${parseFloat(fi.late_fine_per_day || 0).toLocaleString()}</td>
                <td data-label="Status">
                    ${fi.is_active 
                        ? '<span class="fs-badge fs-badge-success"><i class="fa-solid fa-check"></i> Active</span>' 
                        : '<span class="fs-badge fs-badge-neutral"><i class="fa-solid fa-pause"></i> Inactive</span>'}
                </td>
                <td data-label="Actions">
                    <div class="fs-table-actions">
                        <button class="fs-btn fs-btn-icon fs-btn-ghost" 
                                title="Edit" 
                                aria-label="Edit ${fi.name}"
                                onclick="openEditFeeModal(${fi.id})">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="fs-btn fs-btn-icon fs-btn-ghost" 
                                title="${fi.is_active ? 'Deactivate' : 'Activate'}"
                                aria-label="${fi.is_active ? 'Deactivate' : 'Activate'} ${fi.name}"
                                onclick="toggleFeeItem(${fi.id}, '${fi.name.replace(/'/g, "\\'")}', ${fi.is_active})">
                            <i class="fa-solid fa-toggle-${fi.is_active ? 'on' : 'off'}"></i>
                        </button>
                        <button class="fs-btn fs-btn-icon fs-btn-ghost" 
                                title="Delete"
                                aria-label="Delete ${fi.name}"
                                style="color: var(--fs-error);"
                                onclick="deleteFeeItem(${fi.id}, '${fi.name.replace(/'/g, "\\'")}')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `</tbody></table></div>`;
    
    // Add summary footer
    html += `
        <div style="padding: var(--fs-space-4); background: var(--fs-background); border-top: 1px solid var(--fs-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--fs-space-3);">
            <span style="color: var(--fs-text-secondary); font-size: var(--fs-font-size-sm);">
                Showing <strong>${items.length}</strong> fee item${items.length !== 1 ? 's' : ''}
            </span>
        </div>
    `;
    
    c.innerHTML = html;
}

function _filterFeeItems() {
    const search = document.getElementById('feeSearchInput')?.value?.toLowerCase() || '';
    const typeFilter = document.getElementById('feeTypeFilter')?.value || '';
    const courseFilter = document.getElementById('feeCourseFilter')?.value || '';
    
    const filtered = feeItemsData.filter(fi => {
        const matchSearch = !search || fi.name.toLowerCase().includes(search);
        const matchType = !typeFilter || fi.type === typeFilter;
        const matchCourse = !courseFilter || String(fi.course_id) === courseFilter;
        return matchSearch && matchType && matchCourse;
    });
    
    _renderFeeItems(filtered);
}

/* ═══════════════════════════════════════════════════════════════════
   ADD/EDIT MODAL - NEW DESIGN SYSTEM
   ═══════════════════════════════════════════════════════════════════ */
function openAddFeeModal() {
    const coursesOptions = coursesData.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    
    const modalHtml = `
        <div class="fs-modal-backdrop active" onclick="if(event.target===this)closeModal('feeModal')"></div>
        <div class="fs-modal active fs-modal-lg" role="dialog" aria-modal="true" aria-labelledby="feeModalTitle">
            <div class="fs-modal-header">
                <h2 class="fs-modal-title" id="feeModalTitle">
                    <i class="fa-solid fa-plus-circle" style="color: var(--fs-primary);"></i>
                    Add New Fee Item
                </h2>
                <button class="fs-modal-close" onclick="closeModal('feeModal')" aria-label="Close modal">&times;</button>
            </div>
            <form id="feeItemForm" novalidate>
                <div class="fs-modal-body">
                    <!-- Basic Information Section -->
                    <div class="fs-collapse expanded">
                        <div class="fs-collapse-header" onclick="this.parentElement.classList.toggle('expanded')">
                            <span class="fs-collapse-title">
                                <i class="fa-solid fa-info-circle"></i>
                                Basic Information
                            </span>
                            <i class="fa-solid fa-chevron-down fs-collapse-icon"></i>
                        </div>
                        <div class="fs-collapse-body">
                            <div class="fs-collapse-content">
                                <div class="fs-form-group">
                                    <label class="fs-form-label fs-form-label-required" for="feeName">Fee Item Name</label>
                                    <input type="text" id="feeName" name="name" class="fs-form-input" 
                                           required placeholder="e.g. Monthly Tuition Fee" 
                                           minlength="2" maxlength="100">
                                    <span class="fs-form-error">Please enter a valid fee item name</span>
                                </div>
                                
                                <div class="fs-form-row fs-form-row-2">
                                    <div class="fs-form-group">
                                        <label class="fs-form-label fs-form-label-required" for="feeCourse">Course</label>
                                        <select id="feeCourse" name="course_id" class="fs-select" required>
                                            <option value="">Select Course</option>
                                            ${coursesOptions}
                                        </select>
                                        <span class="fs-form-error">Please select a course</span>
                                    </div>
                                    
                                    <div class="fs-form-group">
                                        <label class="fs-form-label fs-form-label-required" for="feeType">Fee Type</label>
                                        <select id="feeType" name="type" class="fs-select" required>
                                            <option value="monthly">Monthly</option>
                                            <option value="admission">Admission</option>
                                            <option value="exam">Exam</option>
                                            <option value="material">Material</option>
                                            <option value="fine">Fine</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Amount & Payments Section -->
                    <div class="fs-collapse expanded">
                        <div class="fs-collapse-header" onclick="this.parentElement.classList.toggle('expanded')">
                            <span class="fs-collapse-title">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                Amount & Payments
                            </span>
                            <i class="fa-solid fa-chevron-down fs-collapse-icon"></i>
                        </div>
                        <div class="fs-collapse-body">
                            <div class="fs-collapse-content">
                                <div class="fs-form-row fs-form-row-2">
                                    <div class="fs-form-group">
                                        <label class="fs-form-label fs-form-label-required" for="feeAmount">Amount (NPR)</label>
                                        <input type="number" id="feeAmount" name="amount" class="fs-form-input" 
                                               required min="1" step="0.01" placeholder="0.00">
                                        <span class="fs-form-error">Please enter a valid amount</span>
                                    </div>
                                    
                                    <div class="fs-form-group">
                                        <label class="fs-form-label" for="feeInstallments">Number of Installments</label>
                                        <input type="number" id="feeInstallments" name="installments" class="fs-form-input" 
                                               value="1" min="1" max="12" placeholder="1">
                                        <span class="fs-form-help">Maximum 12 installments allowed</span>
                                    </div>
                                </div>
                                
                                <div class="fs-form-group">
                                    <label class="fs-form-label" for="feeLateFine">Late Fine per Day (NPR)</label>
                                    <input type="number" id="feeLateFine" name="late_fine_per_day" class="fs-form-input" 
                                           value="0" min="0" step="0.01" placeholder="0.00">
                                    <span class="fs-form-help">Enter 0 to disable late fines</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Section -->
                    <div class="fs-collapse">
                        <div class="fs-collapse-header" onclick="this.parentElement.classList.toggle('expanded')">
                            <span class="fs-collapse-title">
                                <i class="fa-solid fa-toggle-on"></i>
                                Status Settings
                            </span>
                            <i class="fa-solid fa-chevron-down fs-collapse-icon"></i>
                        </div>
                        <div class="fs-collapse-body">
                            <div class="fs-collapse-content">
                                <div class="fs-form-group">
                                    <label class="fs-toggle">
                                        <input type="checkbox" name="is_active" checked>
                                        <span class="fs-toggle-switch"></span>
                                        <span class="fs-toggle-label">Active</span>
                                    </label>
                                    <span class="fs-form-help">Inactive fee items won't appear for students</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fs-modal-footer">
                    <button type="button" class="fs-btn fs-btn-secondary" onclick="closeModal('feeModal')">
                        Cancel
                    </button>
                    <button type="submit" class="fs-btn fs-btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Save Fee Item
                    </button>
                </div>
            </form>
        </div>
    `;
    
    _showModal('feeModal', modalHtml);
    
    // Setup form validation
    const form = document.getElementById('feeItemForm');
    _setupInlineValidation(form);
    form.addEventListener('submit', e => _submitFeeForm(e, 'create'));
    
    // Focus first input
    setTimeout(() => document.getElementById('feeName')?.focus(), 100);
}

function openEditFeeModal(id) {
    const feeItem = feeItemsData.find(fi => fi.id === id);
    if (!feeItem) return;
    
    const coursesOptions = coursesData.map(c => 
        `<option value="${c.id}" ${c.id === feeItem.course_id ? 'selected' : ''}>${c.name}</option>`
    ).join('');
    
    const modalHtml = `
        <div class="fs-modal-backdrop active" onclick="if(event.target===this)closeModal('feeModal')"></div>
        <div class="fs-modal active fs-modal-lg" role="dialog" aria-modal="true" aria-labelledby="feeModalTitle">
            <div class="fs-modal-header">
                <h2 class="fs-modal-title" id="feeModalTitle">
                    <i class="fa-solid fa-pen" style="color: var(--fs-info);"></i>
                    Edit Fee Item
                </h2>
                <button class="fs-modal-close" onclick="closeModal('feeModal')" aria-label="Close modal">&times;</button>
            </div>
            <form id="feeItemForm" novalidate>
                <div class="fs-modal-body">
                    <input type="hidden" name="id" value="${id}">
                    
                    <!-- Basic Information Section -->
                    <div class="fs-collapse expanded">
                        <div class="fs-collapse-header" onclick="this.parentElement.classList.toggle('expanded')">
                            <span class="fs-collapse-title">
                                <i class="fa-solid fa-info-circle"></i>
                                Basic Information
                            </span>
                            <i class="fa-solid fa-chevron-down fs-collapse-icon"></i>
                        </div>
                        <div class="fs-collapse-body">
                            <div class="fs-collapse-content">
                                <div class="fs-form-group">
                                    <label class="fs-form-label fs-form-label-required" for="feeName">Fee Item Name</label>
                                    <input type="text" id="feeName" name="name" class="fs-form-input" 
                                           required placeholder="e.g. Monthly Tuition Fee" 
                                           value="${feeItem.name}"
                                           minlength="2" maxlength="100">
                                    <span class="fs-form-error">Please enter a valid fee item name</span>
                                </div>
                                
                                <div class="fs-form-row fs-form-row-2">
                                    <div class="fs-form-group">
                                        <label class="fs-form-label fs-form-label-required" for="feeCourse">Course</label>
                                        <select id="feeCourse" name="course_id" class="fs-select" required>
                                            <option value="">Select Course</option>
                                            ${coursesOptions}
                                        </select>
                                        <span class="fs-form-error">Please select a course</span>
                                    </div>
                                    
                                    <div class="fs-form-group">
                                        <label class="fs-form-label fs-form-label-required" for="feeType">Fee Type</label>
                                        <select id="feeType" name="type" class="fs-select" required>
                                            <option value="monthly" ${feeItem.type === 'monthly' ? 'selected' : ''}>Monthly</option>
                                            <option value="admission" ${feeItem.type === 'admission' ? 'selected' : ''}>Admission</option>
                                            <option value="exam" ${feeItem.type === 'exam' ? 'selected' : ''}>Exam</option>
                                            <option value="material" ${feeItem.type === 'material' ? 'selected' : ''}>Material</option>
                                            <option value="fine" ${feeItem.type === 'fine' ? 'selected' : ''}>Fine</option>
                                            <option value="other" ${feeItem.type === 'other' ? 'selected' : ''}>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Amount & Payments Section -->
                    <div class="fs-collapse expanded">
                        <div class="fs-collapse-header" onclick="this.parentElement.classList.toggle('expanded')">
                            <span class="fs-collapse-title">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                Amount & Payments
                            </span>
                            <i class="fa-solid fa-chevron-down fs-collapse-icon"></i>
                        </div>
                        <div class="fs-collapse-body">
                            <div class="fs-collapse-content">
                                <div class="fs-form-row fs-form-row-2">
                                    <div class="fs-form-group">
                                        <label class="fs-form-label fs-form-label-required" for="feeAmount">Amount (NPR)</label>
                                        <input type="number" id="feeAmount" name="amount" class="fs-form-input" 
                                               required min="1" step="0.01" placeholder="0.00"
                                               value="${feeItem.amount}">
                                        <span class="fs-form-error">Please enter a valid amount</span>
                                    </div>
                                    
                                    <div class="fs-form-group">
                                        <label class="fs-form-label" for="feeInstallments">Number of Installments</label>
                                        <input type="number" id="feeInstallments" name="installments" class="fs-form-input" 
                                               value="${feeItem.installments || 1}" min="1" max="12" placeholder="1">
                                        <span class="fs-form-help">Maximum 12 installments allowed</span>
                                    </div>
                                </div>
                                
                                <div class="fs-form-group">
                                    <label class="fs-form-label" for="feeLateFine">Late Fine per Day (NPR)</label>
                                    <input type="number" id="feeLateFine" name="late_fine_per_day" class="fs-form-input" 
                                           value="${feeItem.late_fine_per_day || 0}" min="0" step="0.01" placeholder="0.00">
                                    <span class="fs-form-help">Enter 0 to disable late fines</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Section -->
                    <div class="fs-collapse">
                        <div class="fs-collapse-header" onclick="this.parentElement.classList.toggle('expanded')">
                            <span class="fs-collapse-title">
                                <i class="fa-solid fa-toggle-on"></i>
                                Status Settings
                            </span>
                            <i class="fa-solid fa-chevron-down fs-collapse-icon"></i>
                        </div>
                        <div class="fs-collapse-body">
                            <div class="fs-collapse-content">
                                <div class="fs-form-group">
                                    <label class="fs-toggle">
                                        <input type="checkbox" name="is_active" ${feeItem.is_active ? 'checked' : ''}>
                                        <span class="fs-toggle-switch"></span>
                                        <span class="fs-toggle-label">Active</span>
                                    </label>
                                    <span class="fs-form-help">Inactive fee items won't appear for students</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fs-modal-footer">
                    <button type="button" class="fs-btn fs-btn-secondary" onclick="closeModal('feeModal')">
                        Cancel
                    </button>
                    <button type="submit" class="fs-btn fs-btn-primary">
                        <i class="fa-solid fa-save"></i>
                        Update Fee Item
                    </button>
                </div>
            </form>
        </div>
    `;
    
    _showModal('feeModal', modalHtml);
    
    // Setup form validation
    const form = document.getElementById('feeItemForm');
    _setupInlineValidation(form);
    form.addEventListener('submit', e => _submitFeeForm(e, 'update'));
}

function _showModal(id, html) {
    // Remove existing modal if any
    const existing = document.getElementById(id);
    if (existing) {
        existing.remove();
        const backdrop = document.querySelector('.fs-modal-backdrop.active');
        if (backdrop) backdrop.remove();
    }
    
    const div = document.createElement('div');
    div.id = id;
    div.innerHTML = html;
    document.body.appendChild(div);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Handle escape key
    const escapeHandler = (e) => {
        if (e.key === 'Escape') {
            closeModal(id);
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    setTimeout(() => document.addEventListener('keydown', escapeHandler), 100);
}

window.closeModal = function(id) {
    const modal = document.getElementById(id);
    const backdrop = document.querySelector('.fs-modal-backdrop.active');
    
    if (modal) {
        modal.classList.remove('active');
    }
    if (backdrop) {
        backdrop.classList.remove('active');
    }
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    setTimeout(() => {
        if (modal) modal.remove();
        if (backdrop) backdrop.remove();
    }, 300);
};

async function _submitFeeForm(e, action) {
    e.preventDefault();
    const form = e.target;
    
    // Validate form
    const inputs = form.querySelectorAll('.fs-form-input[required]');
    let isValid = true;
    inputs.forEach(input => {
        if (!_validateFormInput(input)) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        _showToast('Please fill in all required fields correctly', 'error');
        return;
    }
    
    const formData = new FormData(form);
    const data = {
        action: action,
        name: formData.get('name'),
        course_id: formData.get('course_id'),
        type: formData.get('type'),
        amount: formData.get('amount'),
        installments: formData.get('installments'),
        late_fine_per_day: formData.get('late_fine_per_day'),
        is_active: form.querySelector('input[name="is_active"]')?.checked || false
    };
    
    if (action === 'update') {
        data.id = formData.get('id');
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnContent = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.classList.add('fs-btn-loading');
    submitBtn.innerHTML = '';
    
    try {
        const result = await _safeFetch(APP_URL + '/api/frontdesk/fees', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (!result.success) throw new Error(result.message);
        
        closeModal('feeModal');
        await _loadFeeItems();
        
        // Show success toast
        _showToast(result.message || (action === 'create' ? 'Fee item created successfully' : 'Fee item updated successfully'), 'success');
    } catch(err) {
        _showToast(err.message || 'An error occurred while saving', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.classList.remove('fs-btn-loading');
        submitBtn.innerHTML = originalBtnContent;
    }
}

async function toggleFeeItem(id, name, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    
    _showConfirmDialog({
        title: `${currentStatus ? 'Deactivate' : 'Activate'} Fee Item`,
        message: `Are you sure you want to ${action} "${name}"? ${currentStatus ? 'This will prevent students from seeing this fee item.' : 'This will allow students to see and pay this fee item.'}`,
        confirmText: currentStatus ? 'Deactivate' : 'Activate',
        type: 'warning',
        onConfirm: async () => {
            try {
                const result = await _safeFetch(APP_URL + '/api/frontdesk/fees', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', id: id })
                });
                
                if (!result.success) throw new Error(result.message);
                
                await _loadFeeItems();
                _showToast(result.message || `Fee item ${action}d successfully`, 'success');
            } catch(err) {
                _showToast(err.message || 'An error occurred', 'error');
            }
        }
    });
}

async function deleteFeeItem(id, name) {
    _showConfirmDialog({
        title: 'Delete Fee Item',
        message: `Are you sure you want to delete "${name}"? This action cannot be undone and may affect existing fee records.`,
        confirmText: 'Delete',
        type: 'danger',
        onConfirm: async () => {
            try {
                const result = await _safeFetch(APP_URL + '/api/frontdesk/fees', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: id })
                });
                
                if (!result.success) throw new Error(result.message);
                
                await _loadFeeItems();
                _showToast(result.message || 'Fee item deleted successfully', 'success');
            } catch(err) {
                _showToast(err.message || 'An error occurred while deleting', 'error');
            }
        }
    });
}

function _showToast(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position:fixed;bottom:20px;right:20px;background:#10b981;color:#fff;padding:12px 20px;
        border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:10000;
        animation:slideIn 0.3s ease;
    `;
    toast.innerHTML = `<i class="fa-solid fa-check-circle"></i> ${message}`;
    document.body.appendChild(toast);
    
    // Add animation keyframes if not exists
    if (!document.getElementById('toast-anim')) {
        const style = document.createElement('style');
        style.id = 'toast-anim';
        style.textContent = `
            @keyframes slideIn { from { transform:translateX(100%);opacity:0; } to { transform:translateX(0);opacity:1; } }
        `;
        document.head.appendChild(style);
    }
    
    setTimeout(() => toast.remove(), 3000);
}
// Combined with window._showEmailSendingScreen above

/* ══════════════ FEE COLLECTION (ENTRY POINT) ═══════════════════════════════ */
window.renderFeeCollect = function() {
    // We direct "Fee Collection" menu to the search-based collect interface
    // or to the "Outstanding Dues" list which is a better starting point.
    // However, as per user's specific request for "Fee Collection", we'll provide a dedicated student search view.
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Fee Collection</span>
        </div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div>
                    <div class="pg-title">Fee Collection</div>
                    <div class="pg-sub">Search students and record payments</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="renderFeeOutstanding()"><i class="fa-solid fa-clock"></i> Outstanding Dues</button>
            </div>
        </div>

        <div class="card" style="padding: 40px; text-align: center; border-radius: 16px; margin-top: 20px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #e2e8f0;">
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="width: 80px; height: 80px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                    <i class="fa-solid fa-user-graduate" style="font-size: 32px; color: #0284c7;"></i>
                </div>
                <h2 style="font-size: 1.5rem; color: #1e293b; margin-bottom: 12px; font-weight: 800;">Collect Fees</h2>
                <p style="color: #64748b; margin-bottom: 30px;">Enter student name, ID, or phone number to find their account and record a payment.</p>
                
                <div style="position: relative; max-width: 500px; margin: 0 auto;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem;"></i>
                    <input type="text" id="collectStudentSearch" placeholder="Student name, Roll No, or Phone..." 
                           style="width: 100%; padding: 16px 16px 16px 48px; border-radius: 12px; border: 2px solid #e2e8f0; font-size: 1.1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: all 0.2s;"
                           oninput="_debounceSearchCollect(this.value)">
                    <div id="collectSearchResults" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; margin-top: 8px; z-index: 1000; overflow: hidden; display: none;"></div>
                </div>
            </div>
        </div>
    </div>`;
};

window._debounceSearchCollect = function(query) {
    if (window._collectSearchTimer) clearTimeout(window._collectSearchTimer);
    window._collectSearchTimer = setTimeout(async () => {
        const results = document.getElementById('collectSearchResults');
        if (!query || query.length < 2) {
            results.style.display = 'none';
            return;
        }

        try {
            const res = await fetch(`${APP_URL}/api/frontdesk/students?search=${encodeURIComponent(query)}`);
            const data = await res.json();
            
            if (data.success && data.data && data.data.length > 0) {
                results.innerHTML = data.data.map(s => `
                    <div style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s;" 
                         onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'"
                         onclick="renderQuickPayment(${s.id})">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">${(s.name || 'S')[0]}</div>
                            <div style="text-align: left;">
                                <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem;">${s.name}</div>
                                <div style="font-size: 0.8rem; color: #64748b;">${s.course_name} • ${s.batch_name || 'N/A'} • ${s.student_id || 'No ID'}</div>
                            </div>
                            <div style="margin-left: auto;">
                                <i class="fa-solid fa-chevron-right" style="color: #cbd5e1;"></i>
                            </div>
                        </div>
                    </div>
                `).join('');
                results.style.display = 'block';
            } else {
                results.innerHTML = '<div style="padding: 20px; color: #94a3b8;">No students found</div>';
                results.style.display = 'block';
            }
        } catch(e) {
            console.error(e);
        }
    }, 300);
}

/* ══════════════ FEE RECORD / PAYMENT HISTORY ═══════════════════════════════ */
window.renderFeeRecord = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Payment History</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-history"></i></div><div><div class="pg-title">Payment History</div><div class="pg-sub">List of all fee payments and receipts</div></div></div>
            <div class="pg-acts">
                <button class="btn bt" onclick="renderFeeOutstanding()"><i class="fa-solid fa-plus-circle"></i> Record New Payment</button>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="card mb" style="padding:20px;">
            <div style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:250px;">
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:#64748b; margin-bottom:5px;">Search</label>
                    <input type="text" id="historySearchInput" class="form-control" placeholder="Search by student name or receipt #..." onkeyup="_debounce(_loadPaymentHistory, 300)()">
                </div>
                <div style="width:160px;">
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:#64748b; margin-bottom:5px;">From Date</label>
                    <input type="date" id="historyDateFrom" class="form-control" onchange="_loadPaymentHistory()">
                </div>
                <div style="width:160px;">
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:#64748b; margin-bottom:5px;">To Date</label>
                    <input type="date" id="historyDateTo" class="form-control" onchange="_loadPaymentHistory()">
                </div>
                <button class="btn bs" onclick="_resetHistoryFilters()"><i class="fa-solid fa-rotate-right"></i> Reset</button>
            </div>
        </div>
        
        <!-- History List -->
        <div class="card" style="padding:25px;">
            <div id="paymentHistoryList"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading payment records...</span></div></div>
        </div>
    </div>`;
    
    await _loadPaymentHistory();
};

function _resetHistoryFilters() {
    document.getElementById('historySearchInput').value = '';
    document.getElementById('historyDateFrom').value = '';
    document.getElementById('historyDateTo').value = '';
    _loadPaymentHistory();
}

function _debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

async function _autoSelectStudent(id) {
    try {
        // Fetch student details from API
        const res = await fetch(APP_URL + '/api/frontdesk/students?id=' + id);
        const result = await res.json();
        if (result.success && result.data) {
            // Some APIs return an array even for single ID, or a single object
            const s = Array.isArray(result.data) ? result.data[0] : result.data;
            if (s) {
                // Mapping field names if they differ
                const name = u.name || s.name;
                const course = s.course_name || '';
                const batch = s.batch_name || '';
                _selectStudent(s.id, name, course, batch);
            }
        }
    } catch(e) { console.error('Auto-select error:', e); }
}

let studentsData = [];
let selectedStudent = null;
let outstandingFeesData = [];

async function _loadCoursesForFilter() {
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/fees');
        const result = await res.json();
        if (result.success && result.courses) {
            const select = document.getElementById('studentCourseFilter');
            result.courses.forEach(c => {
                select.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });
        }
    } catch(e) { console.error(e); }
}

async function _searchStudents(query) {
    const container = document.getElementById('studentSearchResults');
    if (!query || query.length < 2) {
        container.innerHTML = '';
        return;
    }
    
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/students?search=' + encodeURIComponent(query));
        const result = await res.json();
        
        if (!result.success || !result.data || result.data.length === 0) {
            container.innerHTML = '<div style="padding:20px;text-align:center;color:#94a3b8;">No students found</div>';
            return;
        }
        
        studentsData = result.data;
        let html = '<table class="table" style="margin:0;"><thead><tr><th>Name</th><th>Course</th><th>Batch</th><th>Action</th></tr></thead><tbody>';
        
        result.data.forEach(s => {
            html += `<tr>
                <td><strong>${s.name}</strong><br><small>${s.student_id || 'N/A'}</small></td>
                <td>${s.course_name || '-'}</td>
                <td>${s.batch_name || '-'}</td>
                <td><button class="btn bt" style="padding:6px 12px;font-size:12px;" onclick="_selectStudent(${s.id}, '${s.name.replace(/'/g,"\\'")}', '${(s.course_name || '').replace(/'/g,"\\'")}', '${(s.batch_name || '').replace(/'/g,"\\'")}')"><i class="fa-solid fa-check"></i> Select</button></td>
            </tr>`;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch(e) {
        container.innerHTML = '<div style="padding:20px;text-align:center;color:#ef4444;">Error loading students</div>';
    }
}

function _filterStudentsByCourse() {
    const courseFilter = document.getElementById('studentCourseFilter')?.value;
    const searchInput = document.getElementById('studentSearchInput');
    if (courseFilter && studentsData.length > 0) {
        const filtered = studentsData.filter(s => String(s.course_id) === courseFilter);
        // Re-render results
        if (filtered.length > 0) {
            let html = '<table class="table" style="margin:0;"><thead><tr><th>Name</th><th>Course</th><th>Batch</th><th>Action</th></tr></thead><tbody>';
            filtered.forEach(s => {
                html += `<tr>
                    <td><strong>${s.name}</strong><br><small>${s.student_id || 'N/A'}</small></td>
                    <td>${s.course_name || '-'}</td>
                    <td>${s.batch_name || '-'}</td>
                    <td><button class="btn bt" style="padding:6px 12px;font-size:12px;" onclick="_selectStudent(${s.id}, '${s.name.replace(/'/g,"\\'")}', '${(s.course_name || '').replace(/'/g,"\\'")}', '${(s.batch_name || '').replace(/'/g,"\\'")}')"><i class="fa-solid fa-check"></i> Select</button></td>
                </tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('studentSearchResults').innerHTML = html;
        } else {
            document.getElementById('studentSearchResults').innerHTML = '<div style="padding:20px;text-align:center;color:#94a3b8;">No students found in selected course</div>';
        }
    } else if (searchInput.value) {
        _searchStudents(searchInput.value);
    }
}

function _selectStudent(id, name, course, batch) {
    selectedStudent = { id, name, course, batch };
    
    document.getElementById('selectedStudentSection').style.display = 'block';
    document.getElementById('selectedStudentName').textContent = name;
    document.getElementById('selectedStudentInfo').textContent = `${course} ${batch ? ' • ' + batch : ''}`;
    
    // Reset summary
    document.getElementById('sumTotalAssigned').textContent = 'Loading...';
    document.getElementById('sumTotalPaid').textContent = 'Loading...';
    document.getElementById('sumTotalBalance').textContent = 'Loading...';
    document.getElementById('paymentStudentId').value = id;
    document.getElementById('studentSearchResults').innerHTML = '';
    document.getElementById('studentSearchInput').value = '';
    
    // Load outstanding fees for this student
    _loadOutstandingFees(id);
}

function _clearSelectedStudent() {
    selectedStudent = null;
    outstandingFeesData = [];
    document.getElementById('selectedStudentSection').style.display = 'none';
    document.getElementById('paymentStudentId').value = '';
}

async function _loadOutstandingFees(studentId) {
    const container = document.getElementById('outstandingFeesList');
    container.innerHTML = '<div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading...</span></div>';
    
    try {
        const result = await _safeFetch(APP_URL + '/api/frontdesk/fees?action=get_outstanding&student_id=' + studentId);
        
        if (!result.success) {
            container.innerHTML = '<div style="padding:20px;text-align:center;color:#ef4444;">' + result.message + '</div>';
            return;
        }
        
        outstandingFeesData = result.data || [];
        const accountSummary = result.summary || {};
        
        if (outstandingFeesData.length === 0) {
            container.innerHTML = `<div style="padding:60px; text-align:center; color:#64748b;">
                <i class="fa-solid fa-circle-check" style="font-size:3rem; color:#10b981; margin-bottom:15px; diplay:block;"></i>
                <h3 style="margin:0; color:#1e293b;">Clear Account</h3>
                <p style="margin-top:5px;">This student has no outstanding fee records.</p>
            </div>`;
            return;
        }
        
        // Populate fee item dropdown
        const feeSelect = document.getElementById('paymentFeeItem');
        feeSelect.innerHTML = '<option value="">Select Fee</option>';
        
        const uniqueFees = {};
        outstandingFeesData.forEach(of => {
            if (!uniqueFees[of.fee_item_id]) {
                uniqueFees[of.fee_item_id] = of;
                feeSelect.innerHTML += `<option value="${of.fee_item_id}">${of.fee_item_name} (${of.fee_type})</option>`;
            }
        });
        
        // Render outstanding fees table
        let html = '<table class="table"><thead><tr><th>Fee Type</th><th>Inst.</th><th>Due Date</th><th>Fee Amt</th><th>Paid</th><th>Fine</th><th>Balance</th><th>Action</th></tr></thead><tbody>';
        
        outstandingFeesData.forEach(of => {
            const assigned = parseFloat(of.amount_due);
            const paid = parseFloat(of.amount_paid);
            const balance = assigned - paid;
            
            const isPaid = balance <= 0;
            const bsDueDate = window.getBSDate ? window.getBSDate(of.due_date) : null;
            const bsStr = bsDueDate ? `${bsDueDate.y}-${String(bsDueDate.m+1).padStart(2,'0')}-${String(bsDueDate.d).padStart(2,'0')}` : '';

            html += `<tr>
                <td>${of.fee_item_name}</td>
                <td>${of.installment_no}</td>
                <td>
                    <span class="${isOverdue ? 'text-danger fw-bold' : ''}">${of.due_date}</span>
                    ${bsStr ? `<br><small style="color:#64748b;">${bsStr}</small>` : ''}
                </td>
                <td>${assigned.toLocaleString()}</td>
                <td style="color:#10b981;">${paid.toLocaleString()}</td>
                <td id="fine_cell_${of.id}">-</td>
                <td><strong style="color:${balance > 0 ? '#ef4444' : 'inherit'}">${balance.toLocaleString()}</strong></td>
                <td><button class="btn bt" onclick="_quickSelectFee(${of.id})">Pay Now</button></td>
            </tr>`;
            
            if (isOverdue) _updateCalculatedFine(of.id);
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;

        // Update Summary with ACCURATE data from backend summary table
        const totalAssigned = parseFloat(accountSummary.total_fee || 0);
        const totalPaid = parseFloat(accountSummary.paid_amount || 0);
        const totalBalance = parseFloat(accountSummary.due_amount || 0);

        const paidPercent = totalAssigned > 0 ? Math.round((totalPaid / totalAssigned) * 100) : 0;
        const circle = document.getElementById('circlePaid');
        if (circle) {
            circle.style.setProperty('--p-val', (paidPercent * 3.6) + 'deg');
            circle.querySelector('span').textContent = paidPercent + '%';
        }

        document.getElementById('sumTotalAssigned').textContent = 'Assigned: NPR ' + totalAssigned.toLocaleString();
        document.getElementById('sumTotalPaid').textContent = 'NPR ' + totalPaid.toLocaleString();
        document.getElementById('sumTotalBalance').textContent = 'NPR ' + totalBalance.toLocaleString();
        
        // Next Due Logic
        const nextDue = outstandingFeesData[0]; // Already sorted by date in backend
        if (nextDue) {
            document.getElementById('nextDueAmount').textContent = 'NPR ' + (parseFloat(nextDue.amount_due) - parseFloat(nextDue.amount_paid)).toLocaleString();
            document.getElementById('nextDueDate').textContent = 'Date: ' + nextDue.due_date;
            
            // SMART PAY: Auto-select oldest
            _quickSelectFee(nextDue.id);
        }

        
    } catch(e) {
        container.innerHTML = '<div style="padding:20px;text-align:center;color:#ef4444;">Error loading fees</div>';
    }
}

async function _updateLiveFineFeedback(recordId) {
    const display = document.getElementById('liveFineDisplay');
    const inputDate = document.getElementById('paidDate').value;
    
    try {
        // We might need to pass the date to the API if it's not today
        const res = await fetch(`${APP_URL}/api/frontdesk/fees?action=get_calculated_fine&fee_record_id=${recordId}&payment_date=${inputDate}`);
        const result = await res.json();
        if (result.success && result.data.fine > 0) {
            display.innerHTML = `<div class="live-fine-badge"><i class="fa-solid fa-triangle-exclamation"></i> Late Fine: NPR ${result.data.fine.toLocaleString()}</div>`;
        } else {
            display.innerHTML = '';
        }
    } catch(e) { display.innerHTML = ''; }
}

async function _updateCalculatedFine(recordId) {
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/fees?action=get_calculated_fine&fee_record_id=' + recordId);
        const result = await res.json();
        if (result.success && result.data.fine > 0) {
            const cell = document.getElementById('fine_cell_' + recordId);
            if (cell) cell.innerHTML = `<span style="color:red;font-weight:600">${result.data.fine.toLocaleString()}</span>`;
        }
    } catch(e) { console.error(e); }
}

function _quickSelectFee(recordId) {
    const fr = outstandingFeesData.find(of => of.id === recordId);
    if (!fr) return;
    
    const feeSelect = document.getElementById('paymentFeeItem');
    const instSelect = document.getElementById('paymentInstallment');
    
    feeSelect.value = fr.fee_item_id;
    // Trigger change manually
    feeSelect.dispatchEvent(new Event('change'));
    
    // Wait for installment select to populate
    setTimeout(() => {
        instSelect.value = fr.installment_no;
        instSelect.dispatchEvent(new Event('change'));
        // Scroll to form
        document.getElementById('paymentFormSection').scrollIntoView({ behavior: 'smooth' });
    }, 100);
}

async function _loadPaymentHistory() {
    const container = document.getElementById('paymentHistoryList');
    if (!container) return;

    const search = document.getElementById('historySearchInput')?.value || '';
    const dateFrom = document.getElementById('historyDateFrom')?.value || '';
    const dateTo = document.getElementById('historyDateTo')?.value || '';

    try {
        const url = `${APP_URL}/api/frontdesk/fees?action=get_payment_history&search=${encodeURIComponent(search)}&date_from=${dateFrom}&date_to=${dateTo}`;
        const result = await _safeFetch(url);

        if (!result.success || !result.data || result.data.length === 0) {
            container.innerHTML = '<div style="padding:60px; text-align:center; color:#94a3b8;"><i class="fa-solid fa-receipt" style="font-size:3rem; margin-bottom:15px; opacity:0.3;"></i><p>No payment records found matching your filters.</p></div>';
            return;
        }

        let html = `<table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student Details</th>
                    <th>Fee Item</th>
                    <th>Receipt No.</th>
                    <th>Amount Paid</th>
                    <th>Mode</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>`;
        
        result.data.forEach(t => {
            html += `<tr>
                <td><div style="font-weight:600;">${t.payment_date}</div><div style="font-size:0.75rem; color:#64748b;">${t.paid_date}</div></td>
                <td>
                    <div style="font-weight:700; color:var(--primary);">${t.student_name}</div>
                    <div style="font-size:0.8rem; color:#64748b;">${t.receipt_no}</div>
                </td>
                <td><span class="tag bg-l">${t.fee_item_name}</span></td>
                <td><strong style="color:var(--text-dark);">${t.receipt_no}</strong></td>
                <td><strong style="color:var(--green); font-size:1.1rem;">NPR ${parseFloat(t.amount_paid).toLocaleString()}</strong></td>
                <td><span class="tag bg-s">${(t.payment_mode || 'CASH').toUpperCase()}</span></td>
                <td style="text-align:right;">
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <button class="btn bs" style="padding:6px 10px;" onclick="window.open('${APP_URL}/api/frontdesk/fees?action=generate_receipt_html&transaction_id=${t.id}&receipt_no=${t.receipt_no}&is_pdf=1', '_blank')" title="Download PDF Receipt"><i class="fa-solid fa-file-pdf"></i></button>
                        <button class="btn bs" style="padding:6px 10px;" onclick="viewPayment(${t.id})" title="Details"><i class="fa-solid fa-eye"></i></button>
                        <button class="btn bs" style="padding:6px 10px;" onclick="editPayment(${t.id})" title="Edit"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn bs" style="padding:6px 10px; color:#ef4444;" onclick="deletePayment(${t.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </td>
            </tr>`;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
        
    } catch(e) {
        console.error(e);
        container.innerHTML = '<div style="padding:40px; text-align:center; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Error loading payment history</div>';
    }
}


// Student Ledger View
window.renderStudentLedger = async function(studentId) {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Student Ledger</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-book"></i></div><div><div class="pg-title" id="ledgerTitle">Student Ledger</div><div class="pg-sub">Fee history and payment transactions</div></div></div>
            <div class="pg-acts">
                <div class="btn-group" style="margin-right:15px; background:#f1f5f9; padding:4px; border-radius:8px;">
                    <button class="btn btn-sm" id="btnLedgerTable" onclick="_switchLedgerView('table')" style="background:var(--blue); color:#fff; border-radius:6px;"><i class="fa-solid fa-table-list"></i> Table</button>
                    <button class="btn btn-sm" id="btnLedgerTimeline" onclick="_switchLedgerView('timeline')" style="background:transparent; color:#64748b; border-radius:6px;"><i class="fa-solid fa-timeline"></i> Timeline</button>
                </div>
                <button class="btn bs" onclick="_printLedger()"><i class="fa-solid fa-print"></i> Print</button>
            </div>
        </div>
        <div id="ledgerContent"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading ledger...</span></div></div>
    </div>`;
    
    try {
        const result = await _safeFetch(`${APP_URL}/api/frontdesk/fees?action=get_student_ledger&student_id=${studentId}`);
        if (!result.success) throw new Error(result.message);
        
        _renderLedgerUI(result.data);
    } catch(e) {
        document.getElementById('ledgerContent').innerHTML = `<div style="padding:20px;color:red">${e.message}</div>`;
    }
};

function _renderLedgerUI(data) {
    const c = document.getElementById('ledgerContent');
    const { ledger, transactions, balance } = data;
    
    let summaryHtml = `
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:25px;">
        <div class="card glass-card" style="padding:20px;border-left:4px solid var(--blue);">
            <div style="color:#64748b;font-size:0.9rem;font-weight:600;">Total Due</div>
            <div style="font-size:1.5rem;font-weight:800;">NPR ${parseFloat(balance.total_due).toLocaleString()}</div>
        </div>
        <div class="card glass-card" style="padding:20px;border-left:4px solid var(--green);">
            <div style="color:#64748b;font-size:0.9rem;font-weight:600;">Total Paid</div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--green);">NPR ${parseFloat(balance.total_paid).toLocaleString()}</div>
        </div>
        <div class="card glass-card" style="padding:20px;border-left:4px solid var(--red);">
            <div style="color:#64748b;font-size:0.9rem;font-weight:600;">Balance Payable</div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--red);">NPR ${parseFloat(balance.balance).toLocaleString()}</div>
        </div>
    </div>`;

    c.innerHTML = summaryHtml + `
        <div id="ledgerTableView">
            <div class="card mb" style="padding:25px;">
                <h4 class="mb"><i class="fa-solid fa-list"></i> Fee Records</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>Date</th><th>Fee Item</th><th>Inst.</th><th>Due</th><th>Paid</th><th>Fine</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                             ${ledger.map(l => {
                                 const bs = window.getBSDate ? window.getBSDate(l.due_date) : null;
                                 const bsStr = bs ? `${bs.y}-${String(bs.m+1).padStart(2,'0')}-${String(bs.d).padStart(2,'0')}` : '';
                                 return `
                                 <tr>
                                     <td>
                                         ${l.due_date}
                                         ${bsStr ? `<br><small style="color:#64748b;">${bsStr}</small>` : ''}
                                     </td>
                                     <td>${l.fee_item_name}</td>
                                     <td>${l.installment_no}</td>
                                     <td>${parseFloat(l.amount_due).toLocaleString()}</td>
                                     <td>${parseFloat(l.amount_paid).toLocaleString()}</td>
                                     <td style="color:red">${l.fine_applied > 0 ? l.fine_applied : '-'}</td>
                                     <td><span class="tag bg-${l.status === 'paid' ? 't' : (l.status === 'overdue' ? 'r' : 'y')}">${(l.status || 'PENDING').toUpperCase()}</span></td>
                                 </tr>
                             `; }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card" style="padding:25px;">
                <h4 class="mb"><i class="fa-solid fa-receipt"></i> Payment History</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>Date</th><th>Receipt No.</th><th>Method</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            ${transactions.map(t => {
                                const bs = window.getBSDate ? window.getBSDate(t.payment_date) : null;
                                const bsStr = bs ? `${bs.y}-${String(bs.m+1).padStart(2,'0')}-${String(bs.d).padStart(2,'0')}` : '';
                                return `
                                <tr>
                                    <td>
                                        ${t.payment_date}
                                        ${bsStr ? `<br><small style="color:#64748b;">${bsStr}</small>` : ''}
                                    </td>
                                    <td><strong>${t.receipt_number}</strong></td>
                                    <td><span class="tag bg-s">${(t.payment_method || 'CASH').toUpperCase()}</span></td>
                                    <td><strong>${parseFloat(t.amount).toLocaleString()}</strong></td>
                                    <td><span class="tag bg-t">COMPLETED</span></td>
                                    <td>
                                        <button class="btn bs" style="padding:4px 8px;font-size:12px;" onclick="window.handleDownloadPdf('${t.receipt_number}')" title="Download PDF"><i class="fa-solid fa-file-pdf"></i></button>
                                        <button class="btn bs" style="padding:4px 8px;font-size:12px;" onclick="viewPayment(${t.id})" title="View"><i class="fa-solid fa-eye"></i></button>
                                        <button class="btn bs" style="padding:4px 8px;font-size:12px;" onclick="editPayment(${t.id})" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                        <button class="btn bs" style="padding:4px 8px;font-size:12px;color:var(--red);" onclick="deletePayment(${t.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            `; }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>`;

        const combinedTimeline = ledger.concat(transactions.map(t => ({...t, is_payment: true})))
            .sort((a,b) => new Date(b.payment_date || b.due_date) - new Date(a.payment_date || a.due_date));

        const timelineHtml = combinedTimeline.map(item => {
            if (item.is_payment) {
                const bs = window.getBSDate ? window.getBSDate(item.payment_date) : null;
                return `
                    <div class="timeline-item paid">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="timeline-date">${item.payment_date} ${bs ? `• ${bs.y}-${bs.m+1}-${bs.d} BS` : ''}</div>
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div>
                                    <h4 style="margin:0; color:#16a34a;">Payment Received</h4>
                                    <p style="margin:4px 0 0 0; font-size:0.9rem; color:#64748b;">Receipt: ${item.receipt_number} • Via ${(item.payment_method || 'CASH').toUpperCase()}</p>
                                </div>
                                <div style="text-align:right">
                                    <div style="font-size:1.1rem; font-weight:800; color:#16a34a;">+ NPR ${parseFloat(item.amount).toLocaleString()}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                const bs = window.getBSDate ? window.getBSDate(item.due_date) : null;
                return `
                    <div class="timeline-item ${item.status}">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="timeline-date">${item.due_date} ${bs ? `• ${bs.y}-${bs.m+1}-${bs.d} BS` : ''}</div>
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div>
                                    <h4 style="margin:0; color:var(--text-dark);">${item.fee_item_name}</h4>
                                    <p style="margin:4px 0 0 0; font-size:0.9rem; color:#64748b;">Installment ${item.installment_no}</p>
                                </div>
                                <div style="text-align:right">
                                    <div style="font-size:1.1rem; font-weight:800; color:var(--text-dark);">NPR ${parseFloat(item.amount_due).toLocaleString()}</div>
                                    <span class="tag bg-${item.status === 'paid' ? 't' : (item.status === 'overdue' ? 'r' : 'y')}" style="margin-top:5px; display:inline-block;">${(item.status || 'PENDING').toUpperCase()}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }).join('');

        mc.innerHTML += `
        <div id="ledgerTimelineView" style="display:none">
            <div class="payment-timeline">
                ${timelineHtml}
            </div>
        </div>
    `;
}

window._switchLedgerView = function(view) {
    const table = document.getElementById('ledgerTableView');
    const timeline = document.getElementById('ledgerTimelineView');
    const btnTable = document.getElementById('btnLedgerTable');
    const btnTimeline = document.getElementById('btnLedgerTimeline');

    if (view === 'timeline') {
        table.style.display = 'none';
        timeline.style.display = 'block';
        btnTimeline.style.background = 'var(--blue)';
        btnTimeline.style.color = '#fff';
        btnTable.style.background = 'transparent';
        btnTable.style.color = '#64748b';
    } else {
        table.style.display = 'block';
        timeline.style.display = 'none';
        btnTable.style.background = 'var(--blue)';
        btnTable.style.color = '#fff';
        btnTimeline.style.background = 'transparent';
        btnTimeline.style.color = '#64748b';
    }
};



/* ══════════════ VIEW / EDIT / DELETE PAYMENTS ═══════════════════════════════ */
window.viewPayment = async function(transactionId) {
    try {
        const result = await _safeFetch(APP_URL + '/api/frontdesk/fees?action=get_payment_details&transaction_id=' + transactionId);
        
        if (!result.success) throw new Error(result.message);
        
        const txn = result.data.transaction;
        
        const modalHtml = `
        <div class="modal-overlay" onclick="if(event.target===this)closeModal('viewPaymentModal')">
            <div class="modal" style="max-width:650px">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-eye"></i> View Payment Details</h3>
                    <button class="modal-close" onclick="closeModal('viewPaymentModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                        <div>
                            <p style="margin:0 0 5px 0;color:#64748b;">Receipt Number</p>
                            <h4 style="margin:0;">${txn.receipt_number}</h4>
                        </div>
                        <div>
                            <p style="margin:0 0 5px 0;color:#64748b;">Payment Date</p>
                            <h4 style="margin:0;">
                                ${txn.payment_date} 
                                ${window.getBSDate ? `<br><small style="color:var(--primary); font-size:0.85em;">${window.formatNepaliDate(txn.payment_date)}</small>` : ''}
                            </h4>
                        </div>
                    </div>
                    
                    <table class="table" style="margin-bottom:20px;">
                        <tbody>
                            <tr>
                                <td><strong>Student Name</strong></td>
                                <td>${txn.student_name}</td>
                            </tr>
                            <tr>
                                <td><strong>Class / Batch</strong></td>
                                <td>${txn.course_name} / ${txn.batch_name}</td>
                            </tr>
                            <tr>
                                <td><strong>Fee Type</strong></td>
                                <td>${txn.fee_item_name}</td>
                            </tr>
                            <tr>
                                <td><strong>Amount Paid</strong></td>
                                <td><strong>NPR ${parseFloat(txn.amount).toLocaleString()}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Payment Method</strong></td>
                                <td><span class="tag bg-s">${(txn.payment_method || 'CASH').toUpperCase()}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Notes</strong></td>
                                <td>${txn.notes || 'N/A'}</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div style="display:flex;gap:15px;justify-content:center;margin-top:20px;">
                        <a href="${APP_URL}/api/frontdesk/fees?action=generate_receipt_html&transaction_id=${transactionId}" target="_blank" class="btn bt"><i class="fa-solid fa-file-pdf"></i> View / Download Receipt</a>
                        ${result.data.image_url ? `<a href="${result.data.image_url}" target="_blank" class="btn bs"><i class="fa-solid fa-image"></i> View Uploaded Bill</a>` : ''}
                    </div>
                </div>
            </div>
        </div>`;
        
        _showModal('viewPaymentModal', modalHtml);
        
    } catch(err) {
        alert(err.message);
    }
};

window.editPayment = async function(transactionId) {
    try {
        const result = await _safeFetch(APP_URL + '/api/frontdesk/fees?action=get_payment_details&transaction_id=' + transactionId);
        
        if (!result.success) throw new Error(result.message);
        const txn = result.data.transaction;
        
        const modalHtml = `
        <div class="modal-overlay" onclick="if(event.target===this)closeModal('editPaymentModal')">
            <div class="modal" style="max-width:550px">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-pen"></i> Edit Payment Record</h3>
                    <button class="modal-close" onclick="closeModal('editPaymentModal')">&times;</button>
                </div>
                <form id="editPaymentForm">
                    <input type="hidden" name="transaction_id" value="${txn.id}">
                    <div class="modal-body">
                        <div class="mb" style="background:#f8fafc;padding:15px;border-radius:8px;">
                            <p style="margin:0 0 5px 0;"><strong>Student:</strong> ${txn.student_name} (${txn.course_name})</p>
                            <p style="margin:0;"><strong>Fee Area:</strong> ${txn.fee_item_name}</p>
                            <p style="margin:4px 0 0 0;font-size:0.85rem;color:#b91c1c;"><i class="fa-solid fa-circle-info"></i> Modifying amounts will automatically recalculate student ledger balances and rewrite the PDF receipt.</p>
                        </div>
                    
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
                            <div class="form-group">
                                <label class="form-label">Payment Date</label>
                                <input type="date" name="paid_date" class="form-control" value="${txn.payment_date}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Amount Paid</label>
                                <input type="number" name="amount_paid" class="form-control" value="${txn.amount}" required min="1" step="0.01">
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
                            <div class="form-group">
                                <label class="form-label">Payment Mode</label>
                                <select name="payment_mode" class="form-control" required>
                                    <option value="cash" ${txn.payment_method==='cash'?'selected':''}>Cash</option>
                                    <option value="bank_transfer" ${txn.payment_method==='bank_transfer'?'selected':''}>Bank Transfer</option>
                                    <option value="cheque" ${txn.payment_method==='cheque'?'selected':''}>Cheque</option>
                                    <option value="esewa" ${txn.payment_method==='esewa'?'selected':''}>eSewa</option>
                                    <option value="khalti" ${txn.payment_method==='khalti'?'selected':''}>Khalti</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Replace Bill/Receipt (Optional)</label>
                                <input type="file" name="receipt_image" accept="image/*,.pdf" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">${txn.notes || ''}</textarea>
                        </div>

                        <div class="form-group" style="padding:10px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;">
                            <label class="form-check" style="margin:0;">
                                <input type="checkbox" name="resend_email" value="1">
                                <span class="form-check-label">Resend Updated Email Receipt to Student</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bs" onclick="closeModal('editPaymentModal')">Cancel</button>
                        <button type="submit" class="btn bt">Save Updates</button>
                    </div>
                </form>
            </div>
        </div>`;
        
        _showModal('editPaymentModal', modalHtml);
        
        document.getElementById('editPaymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            const formData = new FormData(this);
            formData.append('action', 'edit_payment');

            try {
                const saveResult = await _safeFetch(APP_URL + '/api/frontdesk/fees', { method: 'POST', body: formData });
                
                if (saveResult.success) {
                    _showToast(saveResult.message);
                    closeModal('editPaymentModal');
                    if(document.getElementById('paymentStudentId') && document.getElementById('paymentStudentId').value) {
                        await renderStudentLedger(document.getElementById('paymentStudentId').value);
                    }
                    if(document.getElementById('recentPaymentsList')) {
                        await _loadRecentPayments();
                    }
                } else {
                    alert(saveResult.message || 'Update failed');
                }
            } catch(err) {
                alert('Update Error');
            } finally {
                btn.innerHTML = 'Save Updates';
                btn.disabled = false;
            }
        });

    } catch(err) {
        alert(err.message);
    }
};

window.deletePayment = async function(transactionId) {
    if(!confirm("Are you sure you want to completely delete this payment? The ledger balances will actively deduct this amount.")) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete_payment');
        formData.append('transaction_id', transactionId);

        const result = await _safeFetch(APP_URL + '/api/frontdesk/fees', {
            method: 'POST',
            body: formData
        });
        
        if(result.success) {
            _showToast(result.message);
            if(document.getElementById('paymentStudentId') && document.getElementById('paymentStudentId').value) {
                await renderStudentLedger(document.getElementById('paymentStudentId').value);
            }
            if(document.getElementById('recentPaymentsList')) {
                await _loadRecentPayments();
            }
        } else {
            alert(result.message);
        }
    } catch(err) {
        alert("Failed to delete payment.");
    }
};

/* ══════════════ FEE OUTSTANDING ═══════════════════════════════ */
window.renderFeeOutstanding = async function() {
    const mc = document.getElementById('mainContent');
    
    // Inject Premium CSS
    if (!document.getElementById('ia-fees-premium-css')) {
        const link = document.createElement('link');
        link.id = 'ia-fees-premium-css';
        link.rel = 'stylesheet';
        link.href = window.APP_URL + '/assets/css/ia-fees-premium.css';
        document.head.appendChild(link);
    }

    mc.innerHTML = `<div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-house"></i></a> 
            <span class="bc-sep">/</span> 
            <a href="#" onclick="goNav('fee')">Fee Management</a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">Outstanding Dues</span>
        </div>
        
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico"><i class="fa-solid fa-clock"></i></div>
                <div>
                    <div class="pg-title">Outstanding Dues</div>
                    <div class="pg-sub">Real-time tracking of pending student fees</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" onclick="_loadOutstandingData()"><i class="fa-solid fa-arrows-rotate"></i> Sync Data</button>
            </div>
        </div>
        
        <!-- Premium Glassmorphism Stats -->
        <div class="due-stats-grid" id="outstandingSummaryCards">
            <div class="due-stat-card blue">
                <div class="due-stat-header">
                    <div class="due-stat-label">Students with Dues</div>
                    <div class="due-stat-icon blue"><i class="fa-solid fa-users"></i></div>
                </div>
                <div class="due-stat-value" id="iaTotalStudents">0</div>
            </div>
            <div class="due-stat-card orange">
                <div class="due-stat-header">
                    <div class="due-stat-label">Total Outstanding</div>
                    <div class="due-stat-icon orange"><i class="fa-solid fa-money-bill-wave"></i></div>
                </div>
                <div class="due-stat-value" id="iaTotalOutstanding">NPR 0</div>
            </div>
            <div class="due-stat-card red">
                <div class="due-stat-header">
                    <div class="due-stat-label">Pending Records</div>
                    <div class="due-stat-icon red"><i class="fa-solid fa-list-check"></i></div>
                </div>
                <div class="due-stat-value" id="iaPendingItems">0</div>
            </div>
            <div class="due-stat-card teal">
                <div class="due-stat-header">
                    <div class="due-stat-label">Collection Rate</div>
                    <div class="due-stat-icon teal"><i class="fa-solid fa-chart-pie"></i></div>
                </div>
                <div class="due-stat-value" id="iaCollectionRate">0%</div>
            </div>
        </div>
        
        <div class="premium-filter-bar" style="margin-bottom:20px;">
            <div class="premium-search-input">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="outstandingSearchInput" placeholder="Search by student name or record..." oninput="_filterOutstanding()">
            </div>
            <div class="filter-group" style="display:flex; gap:10px;">
                <select id="outstandingCourseFilter" class="form-control" style="width:180px; border-radius:12px;" onchange="_filterOutstanding()">
                    <option value="">All Courses</option>
                </select>
                <select id="outstandingStatusFilter" class="form-control" style="width:150px; border-radius:12px;" onchange="_filterOutstanding()">
                    <option value="">All Status</option>
                    <option value="overdue">Overdue</option>
                    <option value="pending">Upcoming</option>
                </select>
            </div>
        </div>

        <div class="premium-due-table-container" id="outstandingListContainer">
            <div class="pg-loading" style="padding:60px; text-align:center;">
                <i class="fa-solid fa-circle-notch fa-spin"></i><span> Loading outstanding data...</span>
            </div>
        </div>
    </div>`;
    
    await _loadOutstandingData();
};

let outstandingAllData = [];

async function _loadOutstandingData() {
    const c = document.getElementById('outstandingListContainer');
    const courseFilter = document.getElementById('outstandingCourseFilter');
    
    try {
        const result = await _safeFetch(APP_URL + '/api/frontdesk/fees?action=get_outstanding');
        
        if (!result.success) {
            c.innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;">' + result.message + '</div>';
            return;
        }
        
        outstandingAllData = result.data || [];
        
        // Accurate Summary Statistics
        const totalStudents = outstandingAllData.length;
        const totalDue = outstandingAllData.reduce((sum, d) => sum + parseFloat(d.total_due || 0), 0);
        const totalPaid = outstandingAllData.reduce((sum, d) => sum + parseFloat(d.total_paid || 0), 0);
        const totalOutstanding = totalDue - totalPaid;
        const totalPendingItems = outstandingAllData.reduce((sum, d) => sum + parseInt(d.outstanding_count || 0), 0);
        const collectionRate = totalDue > 0 ? Math.round((totalPaid / totalDue) * 100) : 0;
        
        document.getElementById('iaTotalStudents').textContent = totalStudents;
        document.getElementById('iaTotalOutstanding').textContent = 'NPR ' + totalOutstanding.toLocaleString();
        document.getElementById('iaPendingItems').textContent = totalPendingItems;
        document.getElementById('iaCollectionRate').textContent = collectionRate + '%';
        
        // Populate course filter
        const currentCourseVal = courseFilter.value;
        courseFilter.innerHTML = '<option value="">All Courses</option>';
        const courses = [...new Set(outstandingAllData.map(d => d.course_id).filter(Boolean))];
        courses.forEach(cid => {
            const cname = outstandingAllData.find(d => d.course_id === cid)?.course_name;
            if (cname) courseFilter.innerHTML += `<option value="${cid}">${cname}</option>`;
        });
        courseFilter.value = currentCourseVal;
        
        _renderOutstanding(outstandingAllData);
    } catch(e) {
        console.error('Error loading outstanding data:', e);
        c.innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Error loading data</div>';
    }
}

function _filterOutstanding() {
    const search = document.getElementById('outstandingSearchInput')?.value?.toLowerCase() || '';
    const courseFilter = document.getElementById('outstandingCourseFilter')?.value || '';
    const statusFilter = document.getElementById('outstandingStatusFilter')?.value || '';
    const today = new Date().toISOString().split('T')[0];
    
    const filtered = outstandingAllData.filter(d => {
        const matchSearch = !search || (d.student_name && d.student_name.toLowerCase().includes(search));
        const matchCourse = !courseFilter || String(d.course_id) === courseFilter;
        
        let matchStatus = true;
        if (statusFilter === 'overdue') {
            matchStatus = d.next_due_date && d.next_due_date < today;
        } else if (statusFilter === 'pending') {
            matchStatus = !d.next_due_date || d.next_due_date >= today;
        }
        
        return matchSearch && matchCourse && matchStatus;
    });
    
    _renderOutstanding(filtered);
}

function _renderOutstanding(data) {
    const c = document.getElementById('outstandingListContainer');
    const today = new Date().toISOString().split('T')[0];
    
    if (!data.length) {
        c.innerHTML = `<div style="padding:80px; text-align:center; color:#64748b;">
            <i class="fa-solid fa-magnifying-glass" style="font-size:3rem; margin-bottom:15px; opacity:0.3;"></i>
            <p>No students match your outstanding dues filter.</p>
        </div>`;
        return;
    }
    
    const getInitials = (n) => {
        if (!n) return 'S';
        const parts = n.split(' ').slice(0,2).filter(w => w).map(w => w[0] || '').join('');
        return parts ? parts.toUpperCase() : 'S';
    };
    const getAvatarColor = (id) => ['av-teal','av-blue','av-purple','av-amber','av-red'][(id || 0) % 5];

    let html = `<table class="premium-due-table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th>STUDENT</th>
                <th>PENDING ITEMS</th>
                <th>FINANCIAL PROGRESS</th>
                <th>BALANCE</th>
                <th>NEXT DUEDATE</th>
                <th style="text-align:right;">ACTION</th>
            </tr>
        </thead>
        <tbody>`;
    
    data.forEach(d => {
        const balance = parseFloat(d.total_due || 0) - parseFloat(d.total_paid || 0);
        const progress = d.total_due > 0 ? Math.round((parseFloat(d.total_paid) / parseFloat(d.total_due)) * 100) : 0;
        const isOverdue = d.next_due_date && d.next_due_date < today;

        html += `<tr>
            <td>
                <div class="due-s-card">
                    <div class="due-s-av ${getAvatarColor(d.student_id)}">${getInitials(d.student_name)}</div>
                    <div>
                        <div class="due-s-name">${d.student_name}</div>
                        <div class="due-s-course">${d.course_name || 'N/A'}</div>
                    </div>
                </div>
            </td>
            <td>
                <span class="due-count-badge">${d.outstanding_count} Items</span>
            </td>
            <td style="width:200px;">
                <div style="display:flex; justify-content:space-between; font-size:0.75rem; font-weight:700; color:#64748b; margin-bottom:4px;">
                    <span>Paid: ${progress}%</span>
                    <span>NPR ${parseFloat(d.total_paid).toLocaleString()}</span>
                </div>
                <div class="due-fin-progress">
                    <div class="due-fin-bar" style="width:${progress}%"></div>
                </div>
            </td>
            <td>
                <strong style="color:#ef4444; font-size:1rem;">NPR ${balance.toLocaleString()}</strong>
            </td>
            <td>
                <div class="due-date-badge ${isOverdue ? 'overdue' : ''}">
                    <i class="fa-solid ${isOverdue ? 'fa-triangle-exclamation' : 'fa-calendar-day'}"></i>
                    ${d.next_due_date || 'N/A'}
                </div>
                ${d.next_due_date && window.getBSDate ? `<div style="font-size:0.7rem; color:#64748b; margin-top:2px;">${window.formatNepaliDate(d.next_due_date)}</div>` : ''}
            </td>
            <td style="text-align:right;">
                <button class="btn bt" style="border-radius:10px; padding:10px 18px;" onclick="goNav('fee','record',{student_id:${d.student_id}})">
                    <i class="fa-solid fa-hand-holding-dollar"></i> Collect
                </button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    c.innerHTML = html;
}

async function _selectStudentForPayment(studentId) {
    if (!studentId) return;
    // The renderFeeRecord handles sid from URL, but for internal nav we can call this
    await _autoSelectStudent(studentId);
}

window.renderFeeReports = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = '<div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading Reports Dashboard...</span></div>';
    
    try {
        const res = await fetch(APP_URL + '/dash/admin/report-fees?spa=true');
        if (!res.ok) throw new Error('Failed to load dashboard');
        const html = await res.text();
        mc.innerHTML = html;
        
        // Browsers do not execute script tags injected via innerHTML. 
        // We must extract them and append them to the document.
        const scripts = mc.querySelectorAll('script');
        for (let i = 0; i < scripts.length; i++) {
            const oldScript = scripts[i];
            const newScript = document.createElement('script');
            // copy all attributes
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            // copy the script text
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            // replace original with new (forces execution)
            oldScript.parentNode.replaceChild(newScript, oldScript);
        }
        
        // Setup initial scripts and data bindings expected by the report dashboard
        if (typeof filterBatches === 'function') filterBatches();
        if (typeof toggleReportFilters === 'function') toggleReportFilters();
        if (typeof loadFeeReportData === 'function') loadFeeReportData();
        
    } catch(err) {
        mc.innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Error loading Fee Reports module.</div>';
    }
};

/* ── PRODUCTION-GRADE PAYMENT DETAILS VIEW ────────────────── */
window.renderFeeDetails = async function(receiptNo) {
    const mc = document.getElementById('mainContent');
    if (!receiptNo) {
        mc.innerHTML = `<div class="pg fu"><div class="card" style="padding:40px;text-align:center;"><h3>Missing Receipt Number</h3></div></div>`;
        return;
    }

    const dateStr = window.formatNepaliDate ? window.formatNepaliDate(new Date()) : '';

    mc.innerHTML = `
        <div class="pg fu">
            <div class="bc no-print">
                <a href="#" onclick="goNav('overview')">Dashboard</a> 
                <span class="bc-sep">&rsaquo;</span> 
                <a href="#" onclick="goNav('fee','record')">Fee Records</a>
                <span class="bc-sep">&rsaquo;</span> 
                <span class="bc-cur">Payment Details</span>
            </div>
            <div id="paymentDetailsContent">
                <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading payment summary...</span></div>
            </div>
        </div>
    `;

    try {
        const res = await fetch(`${APP_URL}/api/frontdesk/fees?action=get_payment_details&receipt_no=${receiptNo}`);
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        
        const txn = result.data.transaction;
        const c = document.getElementById('paymentDetailsContent');
        
        const nepaliDate = window.formatNepaliDate ? window.formatNepaliDate(txn.payment_date) : '';
        const adDate = new Date(txn.payment_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

        c.innerHTML = `
            <div class="fee-details-card" id="receipt-print-zone" style="max-width: 600px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; animation: slideUp 0.4s ease; border: 1px solid #edf2f7;">
                <div class="fd-header" style="padding: 20px 30px; background: #f8fafc; border-bottom: 1px solid #edf2f7; display: flex; justify-content: space-between; align-items: center;">
                    <div class="fd-title" style="display: flex; align-items: center; gap: 12px; font-weight: 700; color: #1a202c; font-size: 1.1rem;">
                        <i class="fa-solid fa-receipt" style="color: var(--primary);"></i>
                        <span>Payment Receipt Summary</span>
                    </div>
                    <div class="fd-status-badge" style="background: #c6f6d5; color: #22543d; padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 800;">SUCCESS</div>
                </div>

                <div class="fd-body" style="padding: 30px;">
                    <div class="fd-main-info" style="display: flex; justify-content: space-between; margin-bottom: 30px; padding-bottom: 25px; border-bottom: 1px dashed #e2e8f0;">
                        <div class="fd-amount-box">
                            <span class="label" style="display: block; font-size: 0.8rem; color: #718096; margin-bottom: 5px; text-transform: uppercase;">Amount Paid</span>
                            <span class="value" style="font-size: 2.2rem; font-weight: 800; color: #2d3748;">${getCurrencySymbol()}${parseFloat(txn.amount).toLocaleString()}</span>
                        </div>
                        <div class="fd-receipt-box" style="text-align: right;">
                            <span class="label" style="display: block; font-size: 0.8rem; color: #718096; margin-bottom: 5px; text-transform: uppercase;">Receipt No.</span>
                            <span class="value" style="font-size: 1.2rem; font-weight: 700; color: #4a5568;">${txn.receipt_number}</span>
                        </div>
                    </div>

                    <div class="fd-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div class="fd-item">
                            <span class="label" style="display: block; font-size: 0.75rem; color: #a0aec0; margin-bottom: 3px; font-weight: 600; text-transform: uppercase;">Student Name</span>
                            <span class="value" style="font-weight: 700; color: #1a202c; font-size: 1rem;">${txn.student_name}</span>
                        </div>
                        <div class="fd-item">
                            <span class="label" style="display: block; font-size: 0.75rem; color: #a0aec0; margin-bottom: 3px; font-weight: 600; text-transform: uppercase;">Course / Batch</span>
                            <span class="value" style="font-weight: 600; color: #2d3748;">${txn.course_name} • ${txn.batch_name}</span>
                        </div>
                        <div class="fd-item">
                            <span class="label" style="display: block; font-size: 0.75rem; color: #a0aec0; margin-bottom: 3px; font-weight: 600; text-transform: uppercase;">Payment Date</span>
                            <span class="value" style="font-weight: 600; color: #2d3748;">
                                ${adDate}<br>
                                <small style="color:#718096; font-size: 0.85em;">${nepaliDate}</small>
                            </span>
                        </div>
                        <div class="fd-item">
                            <span class="label" style="display: block; font-size: 0.75rem; color: #a0aec0; margin-bottom: 3px; font-weight: 600; text-transform: uppercase;">Method</span>
                            <span class="value" style="font-weight: 600; color: #2d3748; text-transform:capitalize;">
                                <i class="fa-solid fa-credit-card" style="font-size: 0.8rem; margin-right: 4px; opacity: 0.7;"></i>
                                ${txn.payment_method.replace('_', ' ')}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="fd-actions no-print" style="padding: 25px 30px; background: #f8fafc; display: flex; gap: 12px;">
                    <button class="btn bt" style="flex: 1; justify-content:center;" onclick="window.printReceipt()">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                    <button class="btn bs" style="flex: 1; justify-content:center;" onclick="window.handleDownloadPdf('${txn.receipt_number}')">
                        <i class="fa-solid fa-download"></i> PDF
                    </button>
                    <button class="btn bs" id="btnResendEmail" style="flex: 1; justify-content:center;" onclick="window.handleResendEmail('${txn.receipt_number}')">
                        <i class="fa-solid fa-paper-plane"></i> Email
                    </button>
                </div>
                
                <div class="fd-footer no-print" style="padding: 15px 30px; text-align: center; border-top: 1px solid #edf2f7;">
                    <button class="btn-text" style="background: none; border: none; color: var(--primary); font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin: 0 auto;" onclick="goNav('fee','record')">
                        <i class="fa-solid fa-arrow-left"></i> Record Another Payment
                    </button>
                </div>
            </div>
        `;
    } catch(e) {
        document.getElementById('paymentDetailsContent').innerHTML = `
            <div class="card" style="padding:40px; text-align:center; color:var(--red);">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:3rem; margin-bottom:15px;"></i>
                <h3>Failed to Load Payment Details</h3>
                <p>${e.message}</p>
                <button class="btn bt" onclick="renderFeeDetails('${receiptNo}')" style="margin-top:20px;">Retry</button>
            </div>
        `;
    }
};

/* ══════════════ FEE SUMMARY REPORT ═══════════════════════════════ */
window.renderFeeSummary = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Fee Summary</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-chart-pie"></i></div><div><div class="pg-title">Fee Summary Report</div><div class="pg-sub">Overview of fee collections and outstanding dues</div></div></div></div>
        <div class="sg mb">
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-g"><i class="fa-solid fa-calendar-day"></i></div></div><div class="sc-val" id="todayCollection">-</div><div class="sc-lbl">Today's Collection</div></div>
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-b"><i class="fa-solid fa-calendar-check"></i></div></div><div class="sc-val" id="monthCollection">-</div><div class="sc-lbl">This Month</div></div>
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-r"><i class="fa-solid fa-exclamation-triangle"></i></div></div><div class="sc-val" id="totalOutstanding">-</div><div class="sc-lbl">Total Outstanding</div></div>
            <div class="sc card"><div class="sc-top"><div class="sc-ico ic-y"><i class="fa-solid fa-users"></i></div></div><div class="sc-val" id="defaulterCount">-</div><div class="sc-lbl">Defaulters</div></div>
        </div>
        <div class="card">
            <div class="ct"><i class="fa-solid fa-filter"></i> Filter by Date Range</div>
            <div style="display:flex;gap:15px;flex-wrap:wrap;margin-top:15px;">
                <div><label class="form-label">From</label><input type="date" id="summaryStartDate" class="form-control" value="${new Date().toISOString().split('T')[0]}"></div>
                <div><label class="form-label">To</label><input type="date" id="summaryEndDate" class="form-control" value="${new Date().toISOString().split('T')[0]}"></div>
                <div style="display:flex;align-items:flex-end;"><button class="btn bt" onclick="loadCollectionSummary()">Load Summary</button></div>
            </div>
            <div id="collectionSummaryTable" style="margin-top:20px;"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div></div>
        </div>
    </div>`;
    
    // Load summary stats
    await _loadFeeSummaryStats();
    // Load initial collection summary
    await loadCollectionSummary();
};

async function _loadFeeSummaryStats() {
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/fee-reports?action=summary', getHeaders());
        const result = await res.json();
        if (result.success) {
            const s = result.data;
            const currency = window.getCurrencySymbol?.() || 'Rs';
            document.getElementById('todayCollection').textContent = currency + ' ' + parseFloat(s.today_collection || 0).toLocaleString();
            document.getElementById('monthCollection').textContent = currency + ' ' + parseFloat(s.month_collection || 0).toLocaleString();
            document.getElementById('totalOutstanding').textContent = currency + ' ' + parseFloat(s.total_outstanding || 0).toLocaleString();
            document.getElementById('defaulterCount').textContent = s.defaulter_count || 0;
        }
    } catch(e) {
        console.error('Failed to load fee summary stats', e);
    }
}

window.loadCollectionSummary = async function() {
    const c = document.getElementById('collectionSummaryTable');
    if (!c) return;
    
    const start = document.getElementById('summaryStartDate')?.value || new Date().toISOString().split('T')[0];
    const end = document.getElementById('summaryEndDate')?.value || new Date().toISOString().split('T')[0];
    
    c.innerHTML = '<div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>';
    
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/fee-reports?action=collection_summary&start=' + start + '&end=' + end, getHeaders());
        const result = await res.json();
        if (result.success) {
            const data = result.data;
            const currency = window.getCurrencySymbol?.() || 'Rs';
            const total = data.reduce((sum, item) => sum + parseFloat(item.total || 0), 0);
            
            if (!data.length) {
                c.innerHTML = '<div style="padding:40px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-inbox" style="font-size:2rem;"></i><p>No collections in this period</p></div>';
                return;
            }
            
            let html = `<table class="table"><thead><tr><th>Payment Method</th><th>Total Amount</th><th>Transactions</th></tr></thead><tbody>`;
            data.forEach(item => {
                const method = (item.payment_method || 'unknown').replace(/_/g, ' ').toUpperCase();
                html += `<tr>
                    <td><span class="tag bg-b">${method}</span></td>
                    <td style="font-weight:600;">${currency} ${parseFloat(item.total || 0).toLocaleString()}</td>
                    <td>${item.count || 0}</td>
                </tr>`;
            });
            html += `<tr style="background:#f8fafc;font-weight:700;"><td>TOTAL</td><td>${currency} ${total.toLocaleString()}</td><td>${data.reduce((s,i) => s + (i.count||0), 0)}</td></tr>`;
            html += '</tbody></table>';
            c.innerHTML = html;
        } else {
            throw new Error(result.message);
        }
    } catch(e) {
        c.innerHTML = '<div style="padding:20px;color:var(--red);">' + e.message + '</div>';
    }
};

window.printReceipt = function() {
    window.print();
};

window.handleDownloadPdf = function(receiptNo) {
    window.location.href = `${APP_URL}/api/frontdesk/fees?action=generate_receipt_html&is_pdf=1&receipt_no=${receiptNo}`;
};

window.handleResendEmail = async function(receiptNo) {
    const btn = document.getElementById('btnResendEmail');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Queuing...`;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    Swal.fire({
        title: '<div style="margin-bottom:15px;"><i class="fa-solid fa-envelope-circle-check" style="color:var(--primary, #3b82f6); font-size:3.5rem; filter: drop-shadow(0 4px 10px rgba(59, 130, 246, 0.3));"></i></div><span style="color:var(--primary); font-family:\'Inter\',sans-serif; letter-spacing:-0.5px;">Dispatching Email Receipt</span>',
        html: `
            <div id="emailProgressText" style="margin-top: 5px; color: #475569; font-size: 1.1rem; font-weight: 600; min-height: 1.5em; transition: all 0.3s ease;">Initializing...</div>
            
            <div style="width: 100%; max-width: 380px; height: 14px; background: #f1f5f9; border-radius: 20px; margin: 30px auto 15px; overflow: hidden; position: relative; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <div id="emailProgressBar" style="width: 0%; height: 100%; background: linear-gradient(90deg, var(--primary, #3b82f6), #60a5fa, var(--primary, #3b82f6)); background-size: 200% 100%; border-radius: 20px; transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: flex; align-items: center; justify-content: center; animation: progressGradient 2s infinite linear;">
                    <div style="position: absolute; top: 0; left: 0; bottom: 0; right: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); animation: progressShine 1.5s infinite linear;"></div>
                </div>
            </div>
            
            <div style="font-size: 2.5rem; font-weight: 900; color: var(--primary, #1e293b); font-variant-numeric: tabular-nums; letter-spacing: -1.5px; font-family:\'Outfit\',\'Inter\',sans-serif;" id="emailProgressPercent">0%</div>

            <style>
                @keyframes progressShine { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
                @keyframes progressGradient { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }
                .swal2-popup { border-radius: 28px !important; padding: 3rem 1.5rem !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important; }
                #emailProgressText { font-family: 'Inter', sans-serif; }
            </style>
        `,
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            const bar = document.getElementById('emailProgressBar');
            const percent = document.getElementById('emailProgressPercent');
            const text = document.getElementById('emailProgressText');
            const freshCsrfToken = document.querySelector('meta[name="csrf-token"]')?.content || csrfToken;

            let progress = 0;
            const progressInterval = setInterval(() => {
                // Determine current stage and target increment
                // 1–20% → Preparing email request
                // 20–50% → Generating PDF receipt
                // 50–80% → Attaching PDF to email
                // 80–100% → Sending email via API
                
                if (progress < 98) {
                    let increment = 0;
                    if (progress < 20) {
                        increment = 2;
                        text.innerText = "Preparing email request...";
                    } else if (progress < 50) {
                        increment = 1.5;
                        text.innerText = "Generating PDF receipt...";
                    } else if (progress < 80) {
                        increment = 1;
                        text.innerText = "Attaching PDF to email...";
                    } else {
                        increment = 0.5;
                        text.innerText = "Sending email...";
                    }
                    
                    progress = Math.min(98, progress + increment);
                    bar.style.width = `${progress}%`;
                    percent.innerText = `${Math.floor(progress)}%`;
                }
            }, 100);

            _safeFetch(`${APP_URL}/api/frontdesk/fees`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': freshCsrfToken 
                },
                body: JSON.stringify({ 
                    action: 'send_email_receipt', 
                    receipt_no: receiptNo,
                    csrf_token: freshCsrfToken
                })
            })
            .then(result => {
                clearInterval(progressInterval);
                if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                
                if (result.success) {
                    bar.style.width = '100%';
                    bar.style.background = 'linear-gradient(90deg, #10b981, #059669)';
                    bar.style.animation = 'none';
                    percent.innerText = '100%';
                    percent.style.color = '#10b981';
                    text.innerText = 'Email sent to ' + (result.email || 'student') + '!';
                    text.style.color = '#10b981';
                    
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sent Successfully!',
                            text: 'Receipt has been sent to ' + (result.email || 'the student.') + '.',
                            confirmButtonColor: '#3b82f6',
                            timer: 4000,
                            timerProgressBar: true
                        });
                    }, 500);
                } else {
                    throw new Error(result.message);
                }
            })
            .catch(e => {
                clearInterval(progressInterval);
                if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                Swal.fire({
                    icon: 'error',
                    title: 'Sending Failed',
                    html: `<div style="color:#ef4444; font-weight:600; margin-bottom:10px;">${e.message || 'Error occurred while sending email.'}</div>
                           <div style="font-size:0.85rem; color:#64748b;">The server might be experiencing temporary issues. Please try again.</div>`,
                    confirmButtonText: '<i class="fa-solid fa-rotate-right"></i> Try Again',
                    confirmButtonColor: '#3b82f6',
                    showCancelButton: true,
                    cancelButtonText: 'Dismiss'
                }).then((r) => {
                    if (r.isConfirmed) handleResendEmail(receiptNo);
                });
            });
        }
    });
};
