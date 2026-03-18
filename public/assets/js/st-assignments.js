/**
 * Hamro ERP — Student Portal · st-assignments.js
 * Student Assignments Module
 */

window.renderSTAssignments = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading assignments...</div></div>';

    try {
        // Fetch all assignment statuses
        const [pendingRes, submittedRes, gradedRes] = await Promise.all([
            fetch(`${window.APP_URL}/api/student/assignments?action=pending`),
            fetch(`${window.APP_URL}/api/student/assignments?action=submitted`),
            fetch(`${window.APP_URL}/api/student/assignments?action=graded`)
        ]);
        
        const pendingResult = await pendingRes.json();
        const submittedResult = await submittedRes.json();
        const gradedResult = await gradedRes.json();
        
        const pending = pendingResult.success ? (pendingResult.data || []) : [];
        const submitted = submittedResult.success ? (submittedResult.data || []) : [];
        const graded = gradedResult.success ? (gradedResult.data || []) : [];
        
        mc.innerHTML = `
            <div style="padding:24px;">
                <!-- Header -->
                <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,var(--sa-primary),var(--sa-primary-h));color:#fff;">
                    <div class="card-body" style="padding:24px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                            <div style="display:flex;align-items:center;gap:16px;">
                                <div style="width:50px;height:50px;background:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--sa-primary);font-size:1.5rem;">
                                    <i class="fa-solid fa-tasks"></i>
                                </div>
                                <div>
                                    <h2 style="margin:0;font-size:1.3rem;">My Assignments</h2>
                                    <p style="margin:5px 0 0;opacity:0.9;font-size:13px;">View and submit your assignments</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;margin-bottom:24px;">
                    <div class="card" onclick="switchAssignmentTab('pending')" style="cursor:pointer;">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#d97706;">${pending.length}</div>
                            <div style="font-size:12px;color:var(--tl);">Pending</div>
                        </div>
                    </div>
                    <div class="card" onclick="switchAssignmentTab('submitted')" style="cursor:pointer;">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#9333ea;">${submitted.length}</div>
                            <div style="font-size:12px;color:var(--tl);">Submitted</div>
                        </div>
                    </div>
                    <div class="card" onclick="switchAssignmentTab('graded')" style="cursor:pointer;">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#16a34a;">${graded.length}</div>
                            <div style="font-size:12px;color:var(--tl);">Graded</div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div style="display:flex;gap:8px;margin-bottom:16px;border-bottom:1px solid var(--cb);padding-bottom:8px;">
                    <button class="btn bs" id="tab-pending" onclick="switchAssignmentTab('pending')" style="background:var(--sa-primary);color:#fff;">Pending (${pending.length})</button>
                    <button class="btn" id="tab-submitted" onclick="switchAssignmentTab('submitted')" style="background:var(--bg);border:1px solid var(--cb);">Submitted (${submitted.length})</button>
                    <button class="btn" id="tab-graded" onclick="switchAssignmentTab('graded')" style="background:var(--bg);border:1px solid var(--cb);">Graded (${graded.length})</button>
                </div>
                
                <!-- Pending Assignments -->
                <div id="assignments-pending" class="assignment-tab">
                    ${renderAssignmentList(pending, 'pending')}
                </div>
                
                <!-- Submitted Assignments -->
                <div id="assignments-submitted" class="assignment-tab" style="display:none;">
                    ${renderAssignmentList(submitted, 'submitted')}
                </div>
                
                <!-- Graded Assignments -->
                <div id="assignments-graded" class="assignment-tab" style="display:none;">
                    ${renderGradedList(graded)}
                </div>
            </div>
        `;
        
        // Store data globally
        window._pendingAssignments = pending;
        window._submittedAssignments = submitted;
        window._gradedAssignments = graded;
        
    } catch (e) {
        console.error('Assignments load error:', e);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><p>Error loading assignments.</p></div></div></div>';
    }
};

function renderAssignmentList(assignments, type) {
    if (!assignments || assignments.length === 0) {
        return `
            <div class="card">
                <div class="card-body" style="text-align:center;padding:40px;">
                    <i class="fa-solid fa-tasks" style="font-size:3rem;opacity:0.3;margin-bottom:15px;"></i>
                    <p>No ${type} assignments</p>
                </div>
            </div>
        `;
    }
    
    return `
        <div class="card">
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Due Date</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${assignments.map(a => {
                            const daysRemaining = parseInt(a.days_remaining || 0);
                            let urgencyBadge = '';
                            let urgencyColor = '';
                            
                            if (type === 'pending') {
                                if (a.urgency === 'overdue') {
                                    urgencyBadge = '<span class="badge" style="background:#fee2e2;color:#991b1b;">Overdue</span>';
                                } else if (a.urgency === 'high') {
                                    urgencyBadge = '<span class="badge" style="background:#fef3c7;color:#92400e;">Due Soon</span>';
                                } else if (a.urgency === 'medium') {
                                    urgencyBadge = '<span class="badge" style="background:#dbeafe;color:#1e40af;">This Week</span>';
                                } else {
                                    urgencyBadge = '<span class="badge" style="background:#dcfce7;color:#166534;">Upcoming</span>';
                                }
                            }
                            
                            const status = a.submitted_at ? 'Submitted' : 'Pending';
                            const statusColor = a.submitted_at ? '#9333ea' : '#d97706';
                            
                            return `
                                <tr>
                                    <td><strong>${escapeHtml(a.title || '-')}</strong></td>
                                    <td>${escapeHtml(a.subject_name || '-')}</td>
                                    <td>${a.due_date ? formatDate(a.due_date) : '-'}</td>
                                    <td>${a.max_marks || '-'}</td>
                                    <td>${urgencyBadge || `<span class="badge" style="background:${statusColor}20;color:${statusColor};">${status}</span>`}</td>
                                    <td>
                                        <button class="btn btn-sm" onclick="viewAssignment(${a.id})" style="background:var(--sa-primary);color:#fff;">
                                            ${a.submitted_at ? 'View' : 'Submit'}
                                        </button>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderGradedList(assignments) {
    if (!assignments || assignments.length === 0) {
        return `
            <div class="card">
                <div class="card-body" style="text-align:center;padding:40px;">
                    <i class="fa-solid fa-trophy" style="font-size:3rem;opacity:0.3;margin-bottom:15px;"></i>
                    <p>No graded assignments yet</p>
                </div>
            </div>
        `;
    }
    
    return `
        <div class="card">
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Due Date</th>
                            <th>Marks</th>
                            <th>Obtained</th>
                            <th>Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${assignments.map(a => {
                            const percentage = a.max_marks > 0 ? Math.round((a.marks_obtained / a.max_marks) * 100) : 0;
                            let gradeColor = '#dc2626';
                            if (percentage >= 60) gradeColor = '#16a34a';
                            else if (percentage >= 40) gradeColor = '#d97706';
                            
                            return `
                                <tr>
                                    <td><strong>${escapeHtml(a.title || '-')}</strong></td>
                                    <td>${escapeHtml(a.subject_name || '-')}</td>
                                    <td>${a.due_date ? formatDate(a.due_date) : '-'}</td>
                                    <td>${a.max_marks || '-'}</td>
                                    <td><strong style="color:${gradeColor};">${a.marks_obtained || 0}</strong> / ${a.max_marks}</td>
                                    <td style="max-width:200px;">${escapeHtml(a.feedback || '-')}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

window.switchAssignmentTab = function(tab) {
    // Hide all tabs
    document.querySelectorAll('.assignment-tab').forEach(el => el.style.display = 'none');
    // Show selected tab
    document.getElementById(`assignments-${tab}`).style.display = 'block';
    
    // Update button styles
    ['pending', 'submitted', 'graded'].forEach(t => {
        const btn = document.getElementById(`tab-${t}`);
        if (t === tab) {
            btn.style.background = 'var(--sa-primary)';
            btn.style.border = '1px solid var(--sa-primary)';
            btn.style.color = '#fff';
        } else {
            btn.style.background = 'var(--bg)';
            btn.style.border = '1px solid var(--cb)';
            btn.style.color = 'var(--td)';
        }
    });
};

window.viewAssignment = async function(id) {
    try {
        const res = await fetch(`${window.APP_URL}/api/student/assignments?action=detail&assignment_id=${id}`);
        const result = await res.json();
        
        if (result.success && result.data) {
            const a = result.data;
            const mc = document.getElementById('mainContent');
            
            const isSubmitted = a.submitted_at;
            const daysRemaining = parseInt(a.days_remaining || 0);
            const isOverdue = daysRemaining < 0;
            
            mc.innerHTML = `
                <div style="padding:24px;">
                    <button class="btn" onclick="goST('assignments')" style="margin-bottom:16px;background:var(--bg);border:1px solid var(--cb);">
                        <i class="fa-solid fa-arrow-left"></i> Back to Assignments
                    </button>
                    
                    <div class="card">
                        <div class="card-hdr">
                            <div class="ct">${escapeHtml(a.title || 'Assignment')}</div>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom:20px;">
                                <p><strong>Subject:</strong> ${escapeHtml(a.subject_name || '-')}</p>
                                <p><strong>Teacher:</strong> ${escapeHtml(a.teacher_name || '-')}</p>
                                <p><strong>Due Date:</strong> ${a.due_date ? formatDate(a.due_date) : '-'} ${!isSubmitted && !isOverdue ? `(${daysRemaining} days left)` : ''}</p>
                                <p><strong>Total Marks:</strong> ${a.max_marks || '-'}</p>
                            </div>
                            
                            <div style="background:var(--bg);padding:16px;border-radius:8px;margin-bottom:20px;">
                                <h4 style="margin-top:0;">Description</h4>
                                <p style="white-space:pre-wrap;">${escapeHtml(a.description || 'No description provided.')}</p>
                            </div>
                            
                            ${a.attachment_url ? `
                                <div style="margin-bottom:20px;">
                                    <a href="${window.APP_URL}/${a.attachment_url.replace(/^\//, '')}" target="_blank" class="btn" style="background:var(--sa-primary);color:#fff;">
                                        <i class="fa-solid fa-download"></i> Download Homework Attachment
                                    </a>
                                </div>
                            ` : ''}
                            
                            ${isSubmitted ? `
                                <div style="background:#dcfce7;padding:16px;border-radius:8px;margin-bottom:20px;">
                                    <h4 style="margin-top:0;color:#166534;"><i class="fa-solid fa-check-circle"></i> Submitted</h4>
                                    <p><strong>Submitted on:</strong> ${formatDate(a.submitted_at)}</p>
                                    <p><strong>Submission:</strong></p>
                                    <p style="white-space:pre-wrap;">${escapeHtml(a.submission_text || 'No text submitted')}</p>
                                    ${a.submission_attachment ? `<p><a href="${window.APP_URL}/${a.submission_attachment.replace(/^\//, '')}" target="_blank" style="color:var(--sa-primary);font-weight:600;text-decoration:underline;"><i class="fa-solid fa-paperclip"></i> View submission attachment</a></p>` : ''}
                                </div>
                            ` : ''}
                            
                            ${!isSubmitted && !isOverdue ? `
                                <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--cb);">
                                    <h4>Submit Assignment</h4>
                                    <form onsubmit="submitAssignment(event, ${a.id})">
                                        <div style="margin-bottom:16px;">
                                            <label style="display:block;margin-bottom:6px;font-weight:600;">Your Answer</label>
                                            <textarea id="submissionText" rows="6" class="form-control" placeholder="Write your answer here..." style="width:100%;padding:10px;border:1px solid var(--cb);border-radius:8px;background:var(--bg);color:var(--td);resize:vertical;"></textarea>
                                        </div>
                                        <div style="margin-bottom:20px;">
                                            <label style="display:block;margin-bottom:6px;font-weight:600;">Attach File (PDF, Image, Docx)</label>
                                            <input type="file" id="submissionFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" style="width:100%;padding:10px;border:1px solid var(--cb);border-radius:8px;background:var(--bg);color:var(--td);">
                                            <p style="font-size:11px;color:var(--tl);margin-top:5px;">Allowed: PDF, DOC, DOCX, JPG, PNG, ZIP (Max 10MB)</p>
                                        </div>
                                        <button type="submit" class="btn bs" style="background:var(--sa-primary);color:#fff;">
                                            <i class="fa-solid fa-paper-plane"></i> Submit Assignment
                                        </button>
                                    </form>
                                    <div id="submissionMessage" style="margin-top:16px;display:none;"></div>
                                </div>
                            ` : ''}
                            
                            ${isOverdue ? `
                                <div style="background:#fee2e2;padding:16px;border-radius:8px;">
                                    <p style="color:#991b1b;"><i class="fa-solid fa-exclamation-triangle"></i> This assignment is overdue and cannot be submitted.</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (e) {
        console.error('Error loading assignment:', e);
        alert('Error loading assignment details');
    }
};

window.submitAssignment = async function(e, assignmentId) {
    e.preventDefault();
    
    const submissionText = document.getElementById('submissionText').value.trim();
    const fileInput = document.getElementById('submissionFile');
    const msgDiv = document.getElementById('submissionMessage');
    
    if (!submissionText && (!fileInput || fileInput.files.length === 0)) {
        alert('Please enter your answer or attach a file');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('assignment_id', assignmentId);
        formData.append('submission_text', submissionText);
        
        const fileInput = document.getElementById('submissionFile');
        if (fileInput && fileInput.files.length > 0) {
            formData.append('attachment', fileInput.files[0]);
        }
        
        const res = await fetch(`${window.APP_URL}/api/student/assignments?action=submit`, {
            method: 'POST',
            body: formData
        });
        
        const result = await res.json();
        
        if (result.success) {
            msgDiv.style.display = 'block';
            msgDiv.innerHTML = `<div style="padding:12px;background:#dcfce7;color:#166734;border-radius:8px;"><i class="fa-solid fa-check-circle"></i> ${result.message}</div>`;
            setTimeout(() => {
                window.renderSTAssignments();
            }, 1500);
        } else {
            alert(result.message || 'Failed to submit');
        }
    } catch (e) {
        console.error('Submit error:', e);
        alert('Error submitting assignment');
    }
};

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.renderSTAssignments = window.renderSTAssignments;
