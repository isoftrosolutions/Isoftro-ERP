/**
 * iSoftro ERP — ia-exams.js  (v2 — matches real DB schema)
 * Schema: exams(id, tenant_id, batch_id, course_id, created_by_user_id,
 *   title, duration_minutes, total_marks, negative_mark, question_mode,
 *   start_at, end_at, status[draft|scheduled|active|completed|cancelled])
 */

/* ─────────────────────────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────────────────────────── */
async function _loadExamDropdowns() {
    let batches = [], courses = [];
    try {
        const [br, cr] = await Promise.all([
            fetch(APP_URL + '/api/frontdesk/batches').then(r => r.json()),
            fetch(APP_URL + '/api/frontdesk/courses').then(r => r.json())
        ]);
        batches = br.success ? br.data : [];
        courses = cr.success ? cr.data : [];
    } catch(e) {}
    return { batches, courses };
}
function _batchOpts(batches, sel = '') {
    return `<option value="">— Select Batch * —</option>` +
        batches.map(b => `<option value="${b.id}" ${b.id == sel ? 'selected' : ''}>${b.name}</option>`).join('');
}
function _courseOpts(courses, sel = '') {
    return `<option value="">— Select Course * —</option>` +
        courses.map(c => `<option value="${c.id}" ${c.id == sel ? 'selected' : ''}>${c.name}</option>`).join('');
}

/* ─────────────────────────────────────────────────────────────────
   EXAM LIST
───────────────────────────────────────────────────────────────── */
window.renderExamList = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">Assessments</span>
        </div>

        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: linear-gradient(135deg, #8141a5, #a855f7); color: #fff;">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div>
                    <div class="pg-title" style="font-size: clamp(1.2rem, 3vw, 1.5rem);">Examination Hall</div>
                    <div class="pg-sub">Schedule, manage and track academic performance</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="goNav('exams','create-ex')">
                    <i class="fa-solid fa-calendar-plus"></i> <span class="d-none d-sm-inline">Schedule Exam</span>
                </button>
            </div>
        </div>

        <!-- Glassmorphic Filters -->
        <div class="toolbar" style="padding: clamp(10px, 2vw, 15px); margin-bottom: 20px; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.4); border-radius: 16px;">
            <div style="display: flex; gap: 12px; flex-wrap: wrap; width: 100%;">
                <div style="position: relative; flex: 1; min-width: 200px;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;"></i>
                    <input type="text" id="examSearch" placeholder="Search exams by title or batch..." 
                        style="width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 13px; outline: none; transition: all 0.2s;" 
                        oninput="window._filterExamList()"
                        onfocus="this.style.borderColor='#a855f7'; this.style.boxShadow='0 0 0 3px rgba(168, 85, 247, 0.1)'"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                </div>
                <select id="examStatusFilter" 
                    style="padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 13px; outline: none; background: #fff; min-width: 150px; font-weight: 600;" 
                    onchange="window._filterExamList()">
                    <option value="">All Status</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="active">Active / Ongoing</option>
                    <option value="completed">Completed</option>
                    <option value="draft">Draft</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <div class="premium-tw table-responsive" id="examListContainer">
            <div class="pg-loading">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <span>Retrieving examination records...</span>
            </div>
        </div>
    </div>`;

    window._allExams = [];
    await _loadExams();
};

window._filterExamList = function() {
    const search = (document.getElementById('examSearch')?.value || '').toLowerCase();
    const status = document.getElementById('examStatusFilter')?.value || '';
    const filtered = (window._allExams || []).filter(ex =>
        (!search || (ex.title || '').toLowerCase().includes(search) || (ex.batch_name || '').toLowerCase().includes(search)) &&
        (!status || ex.status === status)
    );
    _renderExamTable(filtered);
};

async function _loadExams() {
    const c = document.getElementById('examListContainer'); if (!c) return;
    try {
        const res    = await fetch(APP_URL + '/api/frontdesk/exams');
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        window._allExams = result.data || [];
        _renderExamTable(window._allExams);
    } catch(e) {
        c.innerHTML = `<div style="padding:40px;text-align:center;color:var(--red)"><i class="fa-solid fa-circle-exclamation" style="font-size:2rem;margin-bottom:12px;display:block"></i>${e.message}</div>`;
    }
}

function _renderExamTable(exams) {
    const c = document.getElementById('examListContainer'); if (!c) return;

    if (!exams.length) {
        c.innerHTML = `<div class="empty-state-premium" style="margin: 40px 0;">
            <div class="empty-ico"><i class="fa-solid fa-file-circle-xmark"></i></div>
            <h4>No Exams Scheduled</h4>
            <p>Your examination registry is currently empty.</p>
            <button class="btn bt" style="margin-top: 15px;" onclick="goNav('exams','create-ex')">
                <i class="fa-solid fa-plus"></i> New Examination
            </button>
        </div>`;
        return;
    }

    const statusBadge = {
        scheduled: { bg:'#ecf2ff', color:'#4338ca', icon:'fa-clock',         label:'Scheduled'  },
        active:    { bg:'#f0fdf4', color:'#166534', icon:'fa-circle-dot',     label:'Live Now'   },
        completed: { bg:'#f8fafc', color:'#475569', icon:'fa-circle-check',   label:'Finished'   },
        draft:     { bg:'#fffbeb', color:'#92400e', icon:'fa-pen-to-square',  label:'Drafting'   },
        cancelled: { bg:'#fef2f2', color:'#991b1b', icon:'fa-ban',            label:'Cancelled'  },
    };

    let html = `<table class="premium-student-table">
        <thead>
            <tr>
                <th style="width: 25%;">Examination Details</th>
                <th style="width: 20%;">Target Cohort</th>
                <th style="width: 20%;">Schedule</th>
                <th style="width: 15%; text-align: center;">Assessment Info</th>
                <th style="width: 10%; text-align: center;">Status</th>
                <th style="width: 10%; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>`;

    exams.forEach(ex => {
        const sb  = statusBadge[ex.status] || statusBadge.draft;
        const startDt = ex.start_at ? new Date(ex.start_at) : null;
        const endDt   = ex.end_at   ? new Date(ex.end_at)   : null;
        const dateTxt = startDt ? startDt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) : '—';
        const timeTxt = startDt ? startDt.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) + (endDt ? ' - ' + endDt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}) : '') : 'TBD';
        
        html += `<tr>
            <td>
                <div class="std-card">
                    <div class="std-img initials" style="background: linear-gradient(135deg, #8141a5, #a855f7);">
                        <i class="fa-solid fa-file-signature" style="font-size: 14px;"></i>
                    </div>
                    <div class="std-info">
                        <div class="name">${ex.title}</div>
                        <div class="id">
                            ${ex.question_mode === 'auto' ? '<i class="fa-solid fa-microchip" style="font-size: 10px; margin-right: 4px;"></i> Dynamic Generation' : '<i class="fa-solid fa-pen-nib" style="font-size: 10px; margin-right: 4px;"></i> Manual Entry'}
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-weight: 700; color: #1e293b; font-size: 13px;">${ex.batch_name || 'Generic Batch'}</div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">${ex.course_name || 'Academic Course'}</div>
            </td>
            <td>
                <div style="font-weight: 700; color: #1e293b; font-size: 13px;">${dateTxt}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;"><i class="fa-regular fa-clock" style="font-size: 10px;"></i> ${timeTxt}</div>
            </td>
            <td style="text-align: center;">
                <div style="font-weight: 800; font-size: 14px; color: #1e293b;">${ex.total_marks}</div>
                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Marks • ${ex.duration_minutes}m</div>
            </td>
            <td style="text-align: center;">
                <span class="badge" style="background: ${sb.bg}; color: ${sb.color}; font-weight: 700; font-size: 10px; gap: 4px; padding: 4px 10px; display: inline-flex; align-items: center;">
                    <i class="fa-solid ${sb.icon}" style="font-size: 9px;"></i> ${sb.label}
                </span>
            </td>
            <td style="text-align: right;">
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn-icon-p" title="Edit Exam" onclick="window.renderEditExamForm(${ex.id})">
                        <i class="fa-solid fa-pen-nib"></i>
                    </button>
                    <button class="btn-icon-p" style="color: #e11d48; border-color: #fecdd3;" title="Delete Exam" onclick="window._deleteExam(${ex.id})">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    });

    html += `</tbody></table>`;
    c.innerHTML = html;
}

/* ─────────────────────────────────────────────────────────────────
   CREATE EXAM FORM
───────────────────────────────────────────────────────────────── */
window.renderCreateExamForm = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Preparing assessment form…</span></div></div>`;

    const { batches, courses } = await _loadExamDropdowns();
    const today = new Date().toISOString().slice(0, 10);
    const isEdit = !!window._examEditId;

    mc.innerHTML = `<div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a> 
            <span class="bc-sep">/</span> 
            <a href="#" onclick="goNav('exams','schedule')">Assessments</a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">${isEdit ? 'Edit Session' : 'New Session'}</span>
        </div>

        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: linear-gradient(135deg, #8141a5, #a855f7); color: #fff;">
                    <i class="fa-solid fa-circle-plus"></i>
                </div>
                <div>
                    <div class="pg-title" id="examFormTitle" style="font-size: clamp(1.2rem, 3vw, 1.5rem);">${isEdit ? 'Refine Examination' : 'Orchestrate Examination'}</div>
                    <div class="pg-sub">${isEdit ? 'Adjust scheduling and assessment parameters' : 'Configure a new assessment session for your candidates'}</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" style="border-radius: 12px;" onclick="goNav('exams','schedule')">
                    <i class="fa-solid fa-chevron-left"></i> Discard
                </button>
            </div>
        </div>

        <form id="createExamForm" onsubmit="window._submitExamForm(event)" novalidate>
            <div id="examFormErrors" style="display:none; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 16px; padding: 16px; margin-bottom: 25px; color: #e11d48; font-size: 13px; font-weight: 600;"></div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(clamp(300px, 100%, 450px), 1fr)); gap: clamp(15px, 3vw, 25px); align-items: start;">

                <!-- ── Primary Configuration ── -->
                <div style="display: grid; gap: 20px;">
                    
                    <div class="card" style="padding: clamp(20px, 4vw, 30px); border-radius: 20px;">
                        <div style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #f5f3ff; color: #8141a5; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-info"></i>
                            </div>
                            Core Information
                        </div>

                        <div style="display: grid; gap: 20px;">
                            <div class="form-group">
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.025em;">Exam Identity <span style="color: #e11d48;">*</span></label>
                                <input type="text" id="exTitle" placeholder="e.g. Annual Assessment 2026" maxlength="255" required
                                    style="border-radius: 12px; padding: 12px 16px; height: 48px;"
                                    oninput="window._updateExamPreview()">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.025em;">Cohort / Batch <span style="color: #e11d48;">*</span></label>
                                    <select id="exBatch" required style="border-radius: 12px; height: 48px; padding: 0 16px;" onchange="window._updateExamPreview()">
                                        ${_batchOpts(batches)}
                                    </select>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.025em;">Subject / Course <span style="color: #e11d48;">*</span></label>
                                    <select id="exCourse" required style="border-radius: 12px; height: 48px; padding: 0 16px;">
                                        ${_courseOpts(courses)}
                                    </select>
                                </div>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.025em;">Question Logistics</label>
                                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                    <label style="flex: 1; min-width: 140px; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 15px; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: all 0.2s; position: relative;" id="qmManualLbl">
                                        <input type="radio" name="exQMode" value="manual" checked onchange="window._toggleQMode('manual')" style="position: absolute; opacity: 0; pointer-events: none;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <i class="fa-solid fa-pen-nib" style="font-size: 14px;"></i>
                                            <div style="font-size: 13px; font-weight: 700;">Manual Entry</div>
                                        </div>
                                    </label>
                                    <label style="flex: 1; min-width: 140px; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 15px; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: all 0.2s; position: relative;" id="qmAutoLbl">
                                        <input type="radio" name="exQMode" value="auto" onchange="window._toggleQMode('auto')" style="position: absolute; opacity: 0; pointer-events: none;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <i class="fa-solid fa-microchip" style="font-size: 14px;"></i>
                                            <div style="font-size: 13px; font-weight: 700;">Dynamic Pool</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="padding: clamp(20px, 4vw, 30px); border-radius: 20px;">
                        <div style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            Scheduling
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px;">
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.025em;">Exam Date <span style="color: #e11d48;">*</span></label>
                                <input type="date" id="exDate" required min="${today}" style="border-radius: 12px; height: 48px; padding: 0 16px;" oninput="window._updateExamPreview()">
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.025em;">Start Time <span style="color: #e11d48;">*</span></label>
                                <input type="time" id="exStartTime" required style="border-radius: 12px; height: 48px; padding: 0 16px;">
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.025em;">End Time <span style="color: #e11d48;">*</span></label>
                                <input type="time" id="exEndTime" required style="border-radius: 12px; height: 48px; padding: 0 16px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Secondary Parameters & Preview ── -->
                <div style="display: grid; gap: 20px;">
                    
                    <div class="card" style="padding: clamp(20px, 4vw, 30px); border-radius: 20px;">
                        <div style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #fffbeb; color: #f59e0b; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-award"></i>
                            </div>
                            Grading & Logic
                        </div>
                        
                        <div style="display: grid; gap: 20px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.025em;">Max Marks <span style="color: #e11d48;">*</span></label>
                                    <input type="number" id="exTotalMarks" placeholder="100" min="1" max="9999" required
                                        style="border-radius: 12px; height: 48px; padding: 0 16px; font-weight: 700;"
                                        oninput="window._updateExamPreview()">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.025em;">Limit (Min) <span style="color: #e11d48;">*</span></label>
                                    <input type="number" id="exDuration" placeholder="180" min="1" max="600" required
                                        style="border-radius: 12px; height: 48px; padding: 0 16px; font-weight: 700;">
                                </div>
                            </div>

                            <div style="padding: 15px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 16px;">
                                <div style="font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; margin-bottom: 10px;">Negative Scoring</div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="number" id="exNegMark" placeholder="0.00" min="0" max="10" step="0.25"
                                        style="width: 80px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 700; text-align: center;">
                                    <div style="font-size: 12px; color: #64748b;">Deducted per wrong response</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Real-time Preview -->
                    <div class="card" style="background: linear-gradient(135deg, #0f172a, #1e1b4b); border: none; overflow: hidden; position: relative; color: #fff; padding: 25px; border-radius: 20px;">
                        <div style="position: absolute; top: -10px; right: -10px; font-size: 80px; opacity: 0.05;"><i class="fa-solid fa-file-invoice"></i></div>
                        <div style="position: relative; z-index: 1;">
                            <div style="font-size: 10px; font-weight: 900; color: #6366f1; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px;">Candidate Portal Preview</div>
                            <div id="prevTitle" style="font-size: 18px; font-weight: 800; margin-bottom: 15px; min-height: 25px;">Exam Session</div>
                            
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;">
                                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 5px 12px; border-radius: 10px; font-size: 11px; font-weight: 700; border: 1px solid rgba(16, 185, 129, 0.2);" id="prevDate">Date</div>
                                <div style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 5px 12px; border-radius: 10px; font-size: 11px; font-weight: 700; border: 1px solid rgba(245, 158, 11, 0.2);" id="prevMarks">— Marks</div>
                            </div>

                            <div id="prevBatch" style="font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-users" style="font-size: 10px;"></i> <span>Select a batch</span>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; gap: 12px; margin-top: 10px;">
                        <button type="submit" id="examSubmitBtn" class="btn bt" style="width: 100%; height: 50px; font-size: 14px; font-weight: 800; border-radius: 14px; background: linear-gradient(135deg, #8141a5, #a855f7); color: #fff; box-shadow: 0 10px 20px rgba(129, 65, 165, 0.2);">
                            <i class="fa-solid fa-calendar-check"></i> ${isEdit ? 'Finalize Changes' : 'Execute Schedule'}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>`;

    window._updateExamPreview();
    window._toggleQMode('manual');
};

window._toggleQMode = function(mode) {
    const ml = document.getElementById('qmManualLbl');
    const al = document.getElementById('qmAutoLbl');
    const accent = '#a855f7';
    const border = '#e2e8f0';
    const tint = '#f5f3ff';

    if (ml) {
        ml.style.borderColor = mode === 'manual' ? accent : border;
        ml.style.background = mode === 'manual' ? tint : '#fff';
        const ico = ml.querySelector('i');
        if (ico) ico.style.color = mode === 'manual' ? accent : '#64748b';
    }
    if (al) {
        al.style.borderColor = mode === 'auto' ? accent : border;
        al.style.background = mode === 'auto' ? tint : '#fff';
        const ico = al.querySelector('i');
        if (ico) ico.style.color = mode === 'auto' ? accent : '#64748b';
    }
};

window._updateExamPreview = function() {
    const title  = document.getElementById('exTitle')?.value   || 'Exam Title';
    const date   = document.getElementById('exDate')?.value    || 'Date';
    const marks  = document.getElementById('exTotalMarks')?.value || '—';
    const bEl    = document.getElementById('exBatch');
    const batch  = bEl?.options[bEl.selectedIndex]?.text || '';

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('prevTitle', title);
    set('prevDate',  date);
    set('prevMarks', marks !== '—' ? marks + ' Marks' : '—');
    
    const pBatch = document.getElementById('prevBatch');
    if (pBatch) {
        pBatch.innerHTML = batch && !batch.includes('Select') 
            ? `<i class="fa-solid fa-graduation-cap" style="font-size: 10px;"></i> <span>${batch}</span>` 
            : `<i class="fa-solid fa-users" style="font-size: 10px;"></i> <span>Select a batch</span>`;
    }
};

/* ─────────────────────────────────────────────────────────────────
   SUBMIT
───────────────────────────────────────────────────────────────── */
window._submitExamForm = async function(e) {
    e.preventDefault();
    const errBox = document.getElementById('examFormErrors');
    const btn    = document.getElementById('examSubmitBtn');
    const isEdit = !!window._examEditId;

    const val = id => document.getElementById(id)?.value?.trim() || '';
    const errors = [];

    if (!val('exTitle'))      errors.push('Exam title is required.');
    if (!val('exBatch'))      errors.push('Please select a batch.');
    if (!val('exCourse'))     errors.push('Please select a course.');
    if (!val('exDate'))       errors.push('Exam date is required.');
    if (!val('exStartTime'))  errors.push('Start time is required.');
    if (!val('exEndTime'))    errors.push('End time is required.');
    if (!val('exTotalMarks')) errors.push('Total marks is required.');
    if (!val('exDuration'))   errors.push('Duration is required.');

    if (errors.length) {
        errBox.style.display = 'block';
        errBox.innerHTML     = errors.map(e => `• ${e}`).join('<br>');
        errBox.scrollIntoView({ behavior:'smooth', block:'nearest' });
        return;
    }

    errBox.style.display = 'none';
    btn.disabled         = true;
    btn.innerHTML        = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving…';

    const qMode = document.querySelector('input[name="exQMode"]:checked')?.value || 'manual';

    const payload = {
        action:           isEdit ? 'update' : 'create',
        id:               window._examEditId || undefined,
        title:            val('exTitle'),
        batch_id:         val('exBatch'),
        course_id:        val('exCourse'),
        exam_date:        val('exDate'),
        start_time:       val('exStartTime'),
        end_time:         val('exEndTime'),
        duration_minutes: val('exDuration'),
        total_marks:      val('exTotalMarks'),
        negative_mark:    document.getElementById('exNegMark')?.value || '0',
        question_mode:    qMode,
    };

    try {
        const res    = await fetch(APP_URL + '/api/frontdesk/exams', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        _examToast(result.message || (isEdit ? 'Exam updated!' : 'Exam scheduled!'), 'success');
        window._examEditId = null;
        setTimeout(() => goNav('exams', 'schedule'), 1200);
    } catch(err) {
        _examToast(err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-calendar-plus"></i> ${isEdit ? 'Update Exam' : 'Schedule Exam'}`;
    }
};

/* ─────────────────────────────────────────────────────────────────
   EDIT
───────────────────────────────────────────────────────────────── */
window.renderEditExamForm = async function(examId) {
    window._examEditId = examId;
    await window.renderCreateExamForm();

    try {
        const res    = await fetch(APP_URL + '/api/frontdesk/exams');
        const result = await res.json();
        if (!result.success) return;
        const ex = (result.data || []).find(e => e.id == examId);
        if (!ex) return;

        const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
        set('exTitle',      ex.title);
        set('exBatch',      ex.batch_id);
        set('exCourse',     ex.course_id);
        set('exDate',       ex.exam_date);
        set('exStartTime',  ex.start_time);
        set('exEndTime',    ex.end_time);
        set('exDuration',   ex.duration_minutes);
        set('exTotalMarks', ex.total_marks);
        set('exNegMark',    ex.negative_mark);

        // Set question mode radio
        const qmRadio = document.querySelector(`input[name="exQMode"][value="${ex.question_mode || 'manual'}"]`);
        if (qmRadio) { qmRadio.checked = true; window._toggleQMode(ex.question_mode || 'manual'); }

        window._updateExamPreview();
    } catch(e) {}
};

/* ─────────────────────────────────────────────────────────────────
   DELETE
───────────────────────────────────────────────────────────────── */
window._deleteExam = async function(examId) {
    if (!confirm('Delete this exam? This cannot be undone.')) return;
    try {
        const res    = await fetch(APP_URL + '/api/frontdesk/exams', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete', id:examId}) });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        _examToast('Exam deleted.', 'success');
        await _loadExams();
    } catch(e) { _examToast(e.message, 'error'); }
};

/* ─────────────────────────────────────────────────────────────────
   TOAST
───────────────────────────────────────────────────────────────── */
function _examToast(msg, type = 'success') {
    let t = document.getElementById('examToast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'examToast';
        Object.assign(t.style, {
            position:'fixed', bottom:'24px', right:'24px', zIndex:'9999',
            padding:'12px 20px', borderRadius:'10px', fontFamily:'var(--font)',
            fontSize:'13px', fontWeight:'600', display:'flex', alignItems:'center', gap:'8px',
            boxShadow:'0 8px 30px rgba(0,0,0,.15)', transition:'all .3s',
            transform:'translateY(20px)', opacity:'0'
        });
        document.body.appendChild(t);
    }
    t.style.background = type === 'error' ? 'var(--red)' : 'var(--green)';
    t.style.color      = '#fff';
    t.innerHTML        = `<i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i> ${msg}`;
    requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(20px)'; }, 3000);
}
