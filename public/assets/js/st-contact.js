/**
 * iSoftro ERP — Student Portal · st-contact.js
 * Contact Admin / Support Tickets Page
 */

window.renderSTContact = async function () {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `<div class="container-fluid p-4"><div class="d-flex justify-content-center align-items-center" style="min-height:200px;">
        <div class="text-center text-muted"><i class="fa-solid fa-circle-notch fa-spin fs-3 mb-3 d-block"></i>Loading...</div>
    </div></div>`;

    try {
        const res = await fetch(`${window.APP_URL}/api/student/contact?action=list`);
        const result = await res.json();
        const tickets = result.success ? (result.data || []) : [];

        mc.innerHTML = `
        <div class="container-fluid p-4" style="max-width:1100px;">

            <!-- Page Header -->
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div class="card-body p-0">
                    <div style="background:linear-gradient(135deg,#009E7E 0%,#007a62 100%);padding:28px 32px;">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:52px;height:52px;background:rgba(255,255,255,0.18);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;flex-shrink:0;">
                                <i class="fa-solid fa-headset"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold text-white">Contact Admin</h4>
                                <p class="mb-0 text-white opacity-75 small">Send a message or submit a support request. We'll respond as soon as possible.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <!-- LEFT: Submit new ticket -->
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="fw-bold text-dark fs-6">
                                <i class="fa-solid fa-paper-plane me-2 text-success"></i>Send a Message
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <form id="contactForm" onsubmit="submitContactForm(event)" novalidate>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Subject <span class="text-danger">*</span></label>
                                    <input type="text" id="contactSubject" name="subject"
                                        class="form-control rounded-3"
                                        placeholder="e.g. Query about fee payment"
                                        maxlength="255" required />
                                    <div class="form-text small text-muted">Brief summary of your issue</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Priority</label>
                                    <select id="contactPriority" name="priority" class="form-select rounded-3">
                                        <option value="low">🟢 Low — General query</option>
                                        <option value="normal" selected>🔵 Normal — Needs attention</option>
                                        <option value="high">🟠 High — Urgent issue</option>
                                        <option value="critical">🔴 Critical — Blocking my progress</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-semibold text-dark">Message <span class="text-danger">*</span></label>
                                    <textarea id="contactMessage" name="description"
                                        class="form-control rounded-3"
                                        rows="6"
                                        placeholder="Describe your issue or question in detail..."
                                        required></textarea>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="form-text small text-muted">Be as specific as possible</span>
                                        <span id="charCount" class="form-text small text-muted">0 chars</span>
                                    </div>
                                </div>

                                <button type="submit" id="contactSubmitBtn"
                                    class="btn btn-success w-100 rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                                    <i class="fa-solid fa-paper-plane"></i>
                                    Send Message
                                </button>

                            </form>
                        </div>

                        <!-- Contact Info -->
                        <div class="card-footer bg-light border-top-0 px-4 py-3">
                            <div class="small text-muted fw-semibold mb-2 text-uppercase" style="letter-spacing:.5px;">Alternative Contact</div>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-2 small text-muted">
                                    <i class="fa-solid fa-clock text-success" style="width:16px;"></i>
                                    Response within 24 business hours
                                </div>
                                <div class="d-flex align-items-center gap-2 small text-muted">
                                    <i class="fa-solid fa-circle-info text-primary" style="width:16px;"></i>
                                    Urgent issues — speak directly to front desk
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: My tickets history -->
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                            <div class="fw-bold text-dark fs-6">
                                <i class="fa-solid fa-ticket me-2 text-primary"></i>My Tickets
                                <span class="badge bg-primary rounded-pill ms-2" style="font-size:11px;">${tickets.length}</span>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary rounded-3 px-3" onclick="window.renderSTContact()">
                                <i class="fa-solid fa-rotate-right me-1"></i>Refresh
                            </button>
                        </div>
                        <div class="card-body p-0" id="ticketListContainer">
                            ${renderTicketList(tickets)}
                        </div>
                    </div>
                </div>

            </div>
        </div>`;

        // Char counter
        const textarea = document.getElementById('contactMessage');
        const charCount = document.getElementById('charCount');
        if (textarea && charCount) {
            textarea.addEventListener('input', () => {
                const len = textarea.value.length;
                charCount.textContent = `${len} chars`;
                charCount.className = `form-text small ${len > 2000 ? 'text-danger' : 'text-muted'}`;
            });
        }

    } catch (e) {
        console.error('Contact page error:', e);
        mc.innerHTML = `<div class="container-fluid p-4"><div class="alert alert-danger rounded-3">
            <i class="fa-solid fa-exclamation-triangle me-2"></i>Failed to load contact page. Please try again.
        </div></div>`;
    }
};

// ── Render ticket list ──────────────────────────────────────────────────────
function renderTicketList(tickets) {
    if (!tickets || tickets.length === 0) {
        return `
            <div class="text-center py-5 text-muted">
                <i class="fa-regular fa-comment-dots fs-1 mb-3 d-block opacity-25"></i>
                <p class="mb-0 fw-medium">No messages sent yet</p>
                <p class="small">Submit a message using the form on the left.</p>
            </div>`;
    }

    const priorityConfig = {
        low:      { label: 'Low',      cls: 'bg-secondary' },
        normal:   { label: 'Normal',   cls: 'bg-primary'   },
        high:     { label: 'High',     cls: 'bg-warning text-dark'  },
        critical: { label: 'Critical', cls: 'bg-danger'    },
    };

    const statusConfig = {
        open:     { label: 'Open',     cls: 'bg-success-soft text-success border border-success'   },
        pending:  { label: 'Pending',  cls: 'bg-warning-soft text-warning border border-warning'   },
        resolved: { label: 'Resolved', cls: 'bg-primary-soft text-primary border border-primary'   },
        closed:   { label: 'Closed',   cls: 'bg-secondary bg-opacity-10 text-secondary border border-secondary' },
    };

    return tickets.map(t => {
        const pri = priorityConfig[t.priority] || priorityConfig.normal;
        const sta = statusConfig[t.status]     || statusConfig.open;
        const date = t.created_at ? new Date(t.created_at).toLocaleDateString('en-US', { day:'numeric', month:'short', year:'numeric' }) : '—';

        return `
        <div class="d-flex align-items-start gap-3 px-4 py-3 border-bottom contact-ticket-row"
             style="cursor:default;transition:background .15s;"
             onmouseover="this.style.background='#f8f9fa'"
             onmouseout="this.style.background=''">

            <!-- Priority dot -->
            <div class="mt-1 flex-shrink-0">
                <span class="badge ${pri.cls} rounded-circle p-1" style="width:10px;height:10px;display:inline-block;"></span>
            </div>

            <!-- Content -->
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold text-dark small mb-1" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:320px;"
                     title="${escapeHtml(t.subject)}">${escapeHtml(t.subject)}</div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge rounded-pill ${sta.cls}" style="font-size:10px;">${sta.label}</span>
                    <span class="badge rounded-pill ${pri.cls}" style="font-size:10px;">${pri.label}</span>
                    <span class="text-muted" style="font-size:11px;"><i class="fa-regular fa-clock me-1"></i>${date}</span>
                </div>
            </div>

            <!-- Ticket ID -->
            <div class="flex-shrink-0 text-muted" style="font-size:11px;">#${t.id}</div>
        </div>`;
    }).join('');
}

// ── Submit form ─────────────────────────────────────────────────────────────
window.submitContactForm = async function (e) {
    e.preventDefault();

    const subject     = document.getElementById('contactSubject')?.value.trim();
    const description = document.getElementById('contactMessage')?.value.trim();
    const priority    = document.getElementById('contactPriority')?.value || 'normal';
    const btn         = document.getElementById('contactSubmitBtn');

    if (!subject || !description) {
        Swal.fire({ icon: 'warning', title: 'Required Fields', text: 'Please fill in subject and message.', confirmButtonColor: '#009E7E' });
        return;
    }
    if (description.length > 3000) {
        Swal.fire({ icon: 'warning', title: 'Too Long', text: 'Message must be under 3000 characters.', confirmButtonColor: '#009E7E' });
        return;
    }

    // Disable button
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-2"></i>Sending...';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/contact?action=submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subject, description, priority })
        });
        const result = await res.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Message Sent!',
                text: result.message || 'We\'ll get back to you soon.',
                confirmButtonColor: '#009E7E',
                timer: 3000,
                timerProgressBar: true,
            });
            // Reset form & reload tickets
            document.getElementById('contactForm').reset();
            document.getElementById('charCount').textContent = '0 chars';

            // Refresh ticket list in place
            const listRes = await fetch(`${window.APP_URL}/api/student/contact?action=list`);
            const listResult = await listRes.json();
            const tickets = listResult.success ? (listResult.data || []) : [];
            const container = document.getElementById('ticketListContainer');
            if (container) container.innerHTML = renderTicketList(tickets);

            // Update badge count
            const badge = document.querySelector('.card-header .badge.bg-primary');
            if (badge) badge.textContent = tickets.length;

        } else {
            Swal.fire({ icon: 'error', title: 'Failed', text: result.message || 'Something went wrong.', confirmButtonColor: '#009E7E' });
        }
    } catch (err) {
        console.error('Submit error:', err);
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not send message. Check your connection.', confirmButtonColor: '#009E7E' });
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
};

function escapeHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

window.renderSTContact = window.renderSTContact;
