/**
 * Hamro ERP — ia-timetable.js
 * Timetable Builder: Weekly grid, add/edit slots
 */
let _ttCurrentData = [];

window.renderTimetablePage = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Timetable Builder</span></div>
        <div class="pg-head">
            <div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-calendar-plus"></i></div><div><div class="pg-title">Timetable Builder</div><div class="pg-sub">Manage class schedules and teacher assignments</div></div></div>
            <div class="pg-acts"><button class="btn bt" onclick="openTimetableAddModal()"><i class="fa-solid fa-plus"></i> Add Slot</button></div>
        </div>
        
        <!-- Quick Stats -->
        <!-- Quick Stats -->
        <div class="sg student-sg">
            <div class="sc">
                <div class="sc-top">
                    <div class="sc-ico ic-b"><i class="fa-solid fa-layer-group"></i></div>
                    <span class="tag bg-b">Active</span>
                </div>
                <div class="sc-val" id="ttStatBatches">-</div>
                <div class="sc-lbl">Active Batches</div>
                <div class="sc-delta positive"><i class="fa-solid fa-check-circle"></i> Running</div>
            </div>
            <div class="sc">
                <div class="sc-top">
                    <div class="sc-ico ic-t"><i class="fa-solid fa-book"></i></div>
                    <span class="tag bg-g">Total</span>
                </div>
                <div class="sc-val" id="ttStatSubjects">-</div>
                <div class="sc-lbl">Total Slots</div>
                <div class="sc-delta positive"><i class="fa-solid fa-calendar"></i> This Week</div>
            </div>
            <div class="sc">
                <div class="sc-top">
                    <div class="sc-ico ic-a"><i class="fa-solid fa-user-tie"></i></div>
                    <span class="tag bg-y">Staff</span>
                </div>
                <div class="sc-val" id="ttStatTeachers">-</div>
                <div class="sc-lbl">Teachers</div>
                <div class="sc-delta"><i class="fa-solid fa-users"></i> Assigned</div>
            </div>
            <div class="sc">
                <div class="sc-top">
                    <div class="sc-ico ic-r"><i class="fa-solid fa-user-graduate"></i></div>
                    <span class="tag bg-r">Students</span>
                </div>
                <div class="sc-val" id="ttStatStudents">-</div>
                <div class="sc-lbl">Enrolled Students</div>
                <div class="sc-delta negative"><i class="fa-solid fa-users"></i> Total</div>
            </div>
        </div>
        
        <div class="student-toolbar">
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <div class="tt-filter-group">
                    <label class="tt-filter-label">Course</label>
                    <select id="ttCourseFilter" class="tt-select" onchange="_ttOnCourseFilterChange()"><option value="">All Courses</option></select>
                </div>
                <div class="tt-filter-group">
                    <label class="tt-filter-label">Batch</label>
                    <select id="ttBatchFilter" class="tt-select" onchange="loadTimetableData()"><option value="">All Batches</option></select>
                </div>
                <div class="tt-filter-group">
                    <label class="tt-filter-label">Day</label>
                    <select id="ttDayFilter" class="tt-select" onchange="filterTimetableByDay()">
                        <option value="">All Days</option>
                        <option value="1">Sunday</option>
                        <option value="2">Monday</option>
                        <option value="3">Tuesday</option>
                        <option value="4">Wednesday</option>
                        <option value="5">Thursday</option>
                        <option value="6">Friday</option>
                        <option value="7">Saturday</option>
                    </select>
                </div>
                <button class="btn bs btn-sm" onclick="loadTimetableData()"><i class="fa-solid fa-rotate"></i> Refresh</button>
                <button class="btn bt btn-sm" onclick="openTimetableAddModal()"><i class="fa-solid fa-plus"></i> Add Slot</button>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="tt-legend">
            <span class="tt-legend-item"><span class="tt-legend-dot" style="background:#0d9488"></span> In-Person</span>
            <span class="tt-legend-item"><span class="tt-legend-dot" style="background:#8b5cf6"></span> Online Class</span>
            <span class="tt-legend-item"><span class="tt-legend-dot" style="background:#f59e0b"></span> Lab/Practical</span>
        </div>
        
        <div id="timetableGrid" class="tt-grid"><div class="tt-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading timetable...</span></div></div>
    </div>
    <!-- Modal -->
    <div id="ttModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header"><div class="modal-title" id="ttModalTitle">Add Timetable Slot</div><button class="modal-close" onclick="closeTimetableModal()">&times;</button></div>
            <div class="modal-body">
                <form id="ttForm">
                    <input type="hidden" id="ttSlotId">
                    <div class="form-group"><label class="form-label">Course *</label><select id="ttSlotCourse" class="form-select" required onchange="_ttOnModalCourseChange(this.value)"></select></div>
                    <div class="form-group"><label class="form-label">Batch *</label><select id="ttSlotBatch" class="form-select" required onchange="_ttLoadSubjectsForBatch(this.value)"></select></div>
                    <div class="form-group"><label class="form-label">Subject *</label><select id="ttSlotSubject" class="form-select" required onchange="_ttLoadTeachersForSubject(this.value)"></select></div>
                    <div class="form-group"><label class="form-label">Teacher *</label><select id="ttSlotTeacher" class="form-select" required></select></div>
                    <div class="form-group"><label class="form-label">Day of Week *</label><select id="ttSlotDay" class="form-select" required><option value="1">Sunday</option><option value="2">Monday</option><option value="3">Tuesday</option><option value="4">Wednesday</option><option value="5">Thursday</option><option value="6">Friday</option><option value="7">Saturday</option></select></div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Start Time *</label><input type="time" id="ttSlotStart" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">End Time *</label><input type="time" id="ttSlotEnd" class="form-input" required></div>
                    </div>
                    <div class="form-group"><label class="form-label">Room / Hall</label><input type="text" id="ttSlotRoom" class="form-input" placeholder="e.g. Room 101"></div>
                    <div class="form-group"><label class="form-label">Class Type</label>
                        <div class="tt-class-type-options">
                            <label class="tt-type-option"><input type="radio" name="classType" value="offline" checked> <span class="tt-type-label"><i class="fa-solid fa-building"></i> In-Person</span></label>
                            <label class="tt-type-option"><input type="radio" name="classType" value="online"> <span class="tt-type-label"><i class="fa-solid fa-video"></i> Online</span></label>
                            <label class="tt-type-option"><input type="radio" name="classType" value="lab"> <span class="tt-type-label"><i class="fa-solid fa-flask"></i> Lab</span></label>
                        </div>
                    </div>
                    <div class="form-group" id="onlineLinkGroup" style="display:none;"><label class="form-label">Online Link (Zoom/Google Meet)</label><input type="url" id="ttSlotLink" class="form-input" placeholder="https://zoom.us/j/..."></div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="ttDeleteBtn" class="btn tt-btn-danger" style="display:none;margin-right:auto;" onclick="deleteTimetableSlot()"><i class="fa-solid fa-trash"></i> Delete</button>
                <button class="btn tt-btn-secondary" onclick="closeTimetableModal()">Cancel</button>
                <button class="btn tt-btn-primary" onclick="saveTimetableSlot()">Save Slot</button>
            </div>
        </div>
    </div>`;
    await Promise.all([_ttLoadStats(), _ttLoadBatches(), _ttLoadTeachers(), loadTimetableData()]);
};

async function _ttLoadStats() {
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/timetable?stats=1');
        const data = await res.json();
        if (data.success && data.data) {
            const batchesEl = document.getElementById('ttStatBatches');
            const slotsEl = document.getElementById('ttStatSubjects');
            const teachersEl = document.getElementById('ttStatTeachers');
            const studentsEl = document.getElementById('ttStatStudents');
            if (batchesEl) batchesEl.textContent = data.data.batches || '0';
            if (slotsEl) slotsEl.textContent = data.data.slots || '0';
            if (teachersEl) teachersEl.textContent = data.data.teachers || '0';
            if (studentsEl) studentsEl.textContent = data.data.students || '0';
        }
    } catch(e) { console.error('TT stats error',e); }
}

async function _ttLoadBatches() {
    const cFilt = document.getElementById('ttCourseFilter');
    const bFilt = document.getElementById('ttBatchFilter');
    const cModal = document.getElementById('ttSlotCourse');
    const bModal = document.getElementById('ttSlotBatch');
    if (!cFilt || !bFilt) return;

    try {
        // Load Courses
        const cRes = await fetch(APP_URL + '/api/frontdesk/courses');
        const cData = await cRes.json();
        if (cData.success) {
            window._ttAllCourses = cData.data;
            const opts = '<option value="">All Courses</option>' + cData.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            cFilt.innerHTML = opts;
            if (cModal) cModal.innerHTML = '<option value="">Select Course</option>' + cData.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }

        // Load All Batches once to cache them
        const bRes = await fetch(APP_URL + '/api/frontdesk/batches');
        const bData = await bRes.json();
        if (bData.success) {
            window._ttAllBatches = bData.data;
            _ttPopulateBatchFilter();
        }
    } catch(e) { console.error('TT load batches/courses error', e); }
}

window._ttOnCourseFilterChange = function() {
    _ttPopulateBatchFilter();
    loadTimetableData();
};

function _ttPopulateBatchFilter() {
    const cid = document.getElementById('ttCourseFilter').value;
    const bFilt = document.getElementById('ttBatchFilter');
    if (!bFilt || !window._ttAllBatches) return;

    const filtered = cid ? window._ttAllBatches.filter(b => b.course_id == cid) : window._ttAllBatches;
    bFilt.innerHTML = '<option value="">All Batches</option>' + filtered.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
}

window._ttOnModalCourseChange = function(courseId, prefilledBatchId = null) {
    const bModal = document.getElementById('ttSlotBatch');
    if (!bModal || !window._ttAllBatches) return;

    const filtered = courseId ? window._ttAllBatches.filter(b => b.course_id == courseId) : [];
    bModal.innerHTML = '<option value="">Select Batch</option>' + filtered.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
    
    if (prefilledBatchId) {
        bModal.value = prefilledBatchId;
        _ttLoadSubjectsForBatch(prefilledBatchId);
    } else {
        document.getElementById('ttSlotSubject').innerHTML = '<option value="">Select Batch First</option>';
    }
};

async function _ttLoadTeachers() {
    // We'll optionally keep this for fallback or generic list, 
    // but _ttLoadTeachersForSubject is better
    const sel = document.getElementById('ttSlotTeacher'); if (!sel) return;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/staff?role=teacher');
        const data = await res.json();
        if (data.success) {
            sel.dataset.allTeachers = JSON.stringify(data.data);
            sel.innerHTML = '<option value="">Select Teacher</option>' +
                data.data.map(t=>`<option value="${t.id}">${t.full_name||t.name}</option>`).join('');
        }
    } catch(e) { console.error('TT teachers error',e); }
}

window._ttLoadSubjectsForBatch = async function(batchId, prefilledSubjectId = null) {
    const sel = document.getElementById('ttSlotSubject'); if (!sel) return;
    sel.innerHTML = '<option value="">Loading subjects...</option>';
    try {
        const res = await fetch(`${APP_URL}/api/frontdesk/subject_allocation?batch_id=${batchId}`);
        const data = await res.json();
        if (data.success && data.data.length) {
            sel.innerHTML = '<option value="">Select Subject</option>';
            data.data.forEach(s => {
                const o = document.createElement('option');
                o.value = s.subject_id;
                o.textContent = s.subject_name;
                o.dataset.teacherId = s.teacher_id;
                if (s.subject_id == prefilledSubjectId) o.selected = true;
                sel.appendChild(o);
            });
            if (prefilledSubjectId) _ttLoadTeachersForSubject(prefilledSubjectId);
        } else {
            sel.innerHTML = '<option value="">No subjects allocated to this batch</option>';
        }
    } catch(e) { sel.innerHTML = '<option value="">Error loading subjects</option>'; }
}

window._ttLoadTeachersForSubject = function(subjectId) {
    const subSel = document.getElementById('ttSlotSubject');
    const teaSel = document.getElementById('ttSlotTeacher');
    if (!subSel || !teaSel) return;
    
    const opt = subSel.options[subSel.selectedIndex];
    if (opt && opt.dataset.teacherId) {
        teaSel.value = opt.dataset.teacherId;
    }
}

window.loadTimetableData = async function() {
    const grid = document.getElementById('timetableGrid');
    const batchId = document.getElementById('ttBatchFilter')?.value;
    if (!grid) return;
    grid.innerHTML = '<div class="tt-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div>';
    try {
        const url = batchId ? `${APP_URL}/api/frontdesk/timetable?batch_id=${batchId}` : `${APP_URL}/api/frontdesk/timetable`;
        const res = await fetch(url);
        const data = await res.json();
        if (data.success) { _ttCurrentData = data.data||[]; _renderTimetableGrid(data.grouped||[]); }
        else grid.innerHTML = `<div class="tt-empty">${data.message||'Error loading data'}</div>`;
    } catch(e) { grid.innerHTML = '<div class="tt-empty">Failed to load timetable</div>'; }
};

function _renderTimetableGrid(grouped) {
    const grid = document.getElementById('timetableGrid'); if (!grid) return;
    const dayFilter = document.getElementById('ttDayFilter')?.value;
    
    // Build table-based layout
    let html = `
    <div class="premium-tw" style="margin-top: 20px;">
    <table>
        <thead>
            <tr>
                <th style="width:120px;" class="text-center">Time</th>
                <th class="text-center">Sunday</th>
                <th class="text-center">Monday</th>
                <th class="text-center">Tuesday</th>
                <th class="text-center">Wednesday</th>
                <th class="text-center">Thursday</th>
                <th class="text-center">Friday</th>
                <th class="text-center">Saturday</th>
            </tr>
        </thead>
        <tbody>`;
    
    // Get unique time slots sorted by start time
    const timeSlots = [];
    grouped.forEach(g => {
        g.slots.forEach(s => {
            const timeKey = s.start_time + '-' + s.end_time;
            if (!timeSlots.find(t => t.key === timeKey)) {
                timeSlots.push({ 
                    key: timeKey, 
                    start: s.start_time, 
                    end: s.end_time,
                    startFormatted: _fmtTime(s.start_time),
                    endFormatted: _fmtTime(s.end_time)
                });
            }
        });
    });
    timeSlots.sort((a, b) => a.start.localeCompare(b.start));
    
    // Group slots by day and time
    const slotsByDayTime = {};
    grouped.forEach(g => {
        g.slots.forEach(s => {
            const key = g.day_of_week + '|' + s.start_time + '|' + s.end_time;
            if (!slotsByDayTime[key]) slotsByDayTime[key] = [];
            slotsByDayTime[key].push(s);
        });
    });
    
    // If no data
    if (timeSlots.length === 0) {
        html += `<tr><td colspan="8" class="text-center text-muted py-5">
            <i class="fa-solid fa-calendar-xmark fa-3x mb-3 d-block"></i>
            No timetable slots created yet.<br>
            <button class="btn btn-primary mt-3" onclick="openTimetableAddModal()">
                <i class="fa-solid fa-plus"></i> Add First Slot
            </button>
        </td></tr>`;
    } else {
        // Render each time slot row
        timeSlots.forEach(slot => {
            html += `<tr>
                <td class="text-center align-middle bg-light" style="vertical-align:middle;">
                    <div class="fw-bold">${slot.startFormatted}</div>
                    <div class="text-muted small">to</div>
                    <div class="fw-bold">${slot.endFormatted}</div>
                </td>`;
            
            // Each day column (1-7)
            for (let d = 1; d <= 7; d++) {
                const key = d + '|' + slot.start + '|' + slot.end;
                const daySlots = slotsByDayTime[key] || [];
                
                if (daySlots.length === 0) {
                    html += `<td class="text-center" style="vertical-align:middle;" onclick="openTimetableAddModalWithTime(${d}, '${slot.start}', '${slot.end}')">
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary" title="Add class">
                            <i class="fa-solid fa-plus"></i> Add
                        </a>
                    </td>`;
                } else {
                    html += `<td class="p-1">`;
                    daySlots.forEach(s => {
                        const isOnline = s.online_link || s.class_type === 'online';
                        const isLab = s.class_type === 'lab';
                        const themeColor = isOnline ? '#6366f1' : (isLab ? '#f59e0b' : '#10b981');
                        
                        html += `
                        <div class="tt-table-slot p-2 mb-2 rounded shadow-sm border-0" 
                             onclick="openTimetableEditModal(${s.id})" style="cursor:pointer; border-left: 4px solid ${themeColor} !important; background: #fff; transition: all 0.2s ease;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge badge-subtle-primary" style="background: ${themeColor}15; color: ${themeColor}; border: 1px solid ${themeColor}30; font-size: 10px; font-weight: 700;">
                                    ${isOnline ? '<i class="fa-solid fa-video"></i> Online' : (isLab ? '<i class="fa-solid fa-flask"></i> Lab' : '<i class="fa-solid fa-chalkboard"></i> ' + (s.batch_name || 'Class'))}
                                </span>
                            </div>
                            <div class="fw-bold" style="font-size: 13px; color: var(--text-dark); margin-top: 4px;">${s.subject_name || s.subject || 'Unknown Subject'}</div>
                            <div class="small mt-1" style="font-size: 11px; color: var(--text-light);"><i class="fa-solid fa-user-circle" style="opacity: 0.7;"></i> ${s.teacher_name || '-'}</div>
                            ${s.room ? `<div class="small mt-1" style="font-size: 10px; color: var(--text-light);"><i class="fa-solid fa-location-dot" style="opacity: 0.7;"></i> ${s.room}</div>` : ''}
                        </div>`;
                    });
                    html += `</td>`;
                }
            }
            html += `</tr>`;
        });
    }
    
    html += `</tbody></table></div>`;
    
    grid.innerHTML = html;
}

function filterTimetableByDay() {
    loadTimetableData();
}

function _fmtTime(t) {
    if (!t) return '';
    const p = t.split(':'); let h=parseInt(p[0]); const m=p[1]; const ap=h>=12?'PM':'AM'; h=h%12||12; return `${h}:${m} ${ap}`;
}

window.openTimetableAddModal = async function() {
    document.getElementById('ttModalTitle').textContent='Add Timetable Slot';
    document.getElementById('ttSlotId').value='';
    document.getElementById('ttForm').reset();
    document.getElementById('ttDeleteBtn').style.display='none';
    document.getElementById('ttModal').classList.add('active');
    
    // Pre-fill from filter if possible
    const currentCourse = document.getElementById('ttCourseFilter').value;
    const currentBatch = document.getElementById('ttBatchFilter').value;

    if (currentCourse) {
        document.getElementById('ttSlotCourse').value = currentCourse;
        _ttOnModalCourseChange(currentCourse, currentBatch);
    } else {
        document.getElementById('ttSlotBatch').innerHTML = '<option value="">Select Course First</option>';
        document.getElementById('ttSlotSubject').innerHTML = '<option value="">Select Batch First</option>';
    }

    // Reset class type radio
    document.querySelector('input[name="classType"][value="offline"]').checked = true;
    document.getElementById('onlineLinkGroup').style.display='none';
    
    // Add event listeners for class type
    document.querySelectorAll('input[name="classType"]').forEach(radio => {
        radio.onchange = function() {
            const linkGroup = document.getElementById('onlineLinkGroup');
            if (this.value === 'online') {
                linkGroup.style.display = 'block';
            } else {
                linkGroup.style.display = 'none';
            }
        };
    });
};

// Quick add slot with pre-filled day and time
window.openTimetableAddModalWithTime = async function(day, startTime, endTime) {
    document.getElementById('ttModalTitle').textContent='Add Timetable Slot';
    document.getElementById('ttSlotId').value='';
    document.getElementById('ttForm').reset();
    document.getElementById('ttDeleteBtn').style.display='none';
    document.getElementById('ttModal').classList.add('active');
    
    // Pre-fill day and time
    document.getElementById('ttSlotDay').value = day;
    document.getElementById('ttSlotStart').value = startTime;
    document.getElementById('ttSlotEnd').value = endTime;

    // Pre-fill from filter if possible
    const currentCourse = document.getElementById('ttCourseFilter').value;
    const currentBatch = document.getElementById('ttBatchFilter').value;

    if (currentCourse) {
        document.getElementById('ttSlotCourse').value = currentCourse;
        _ttOnModalCourseChange(currentCourse, currentBatch);
    } else {
        document.getElementById('ttSlotBatch').innerHTML = '<option value="">Select Course First</option>';
        document.getElementById('ttSlotSubject').innerHTML = '<option value="">Select Batch First</option>';
    }
    
    // Reset class type radio
    document.querySelector('input[name="classType"][value="offline"]').checked = true;
    document.getElementById('onlineLinkGroup').style.display='none';
    
    // Add event listeners for class type
    document.querySelectorAll('input[name="classType"]').forEach(radio => {
        radio.onchange = function() {
            const linkGroup = document.getElementById('onlineLinkGroup');
            if (this.value === 'online') {
                linkGroup.style.display = 'block';
            } else {
                linkGroup.style.display = 'none';
            }
        };
    });
};
window.openTimetableEditModal = async function(id) {
    const slot = _ttCurrentData.find(s=>s.id==id); if (!slot) return;
    document.getElementById('ttModalTitle').textContent='Edit Timetable Slot';
    document.getElementById('ttSlotId').value=slot.id;
    
    // Get batch info to find course
    const batch = window._ttAllBatches?.find(b => b.id == slot.batch_id);
    if (batch) {
        document.getElementById('ttSlotCourse').value = batch.course_id;
        _ttOnModalCourseChange(batch.course_id, slot.batch_id);
    }
    
    // Load subjects for this batch and pre-select
    await _ttLoadSubjectsForBatch(slot.batch_id, slot.subject_id);
    
    document.getElementById('ttSlotTeacher').value=slot.teacher_id;
    document.getElementById('ttSlotDay').value=slot.day_of_week;
    document.getElementById('ttSlotStart').value=slot.start_time;
    document.getElementById('ttSlotEnd').value=slot.end_time;
    document.getElementById('ttSlotRoom').value=slot.room||'';
    document.getElementById('ttSlotLink').value=slot.online_link||'';
    document.getElementById('ttDeleteBtn').style.display='inline-flex';
    document.getElementById('ttModal').classList.add('active');
    
    // Handle class type
    const classType = slot.class_type || 'offline';
    const linkGroup = document.getElementById('onlineLinkGroup');
    
    // Set radio button
    const radio = document.querySelector(`input[name="classType"][value="${classType}"]`);
    if (radio) radio.checked = true;
    
    if (classType === 'online') {
        linkGroup.style.display = 'block';
    } else {
        linkGroup.style.display = 'none';
    }
    
    // Add event listeners for class type
    document.querySelectorAll('input[name="classType"]').forEach(r => {
        r.onchange = function() {
            const lg = document.getElementById('onlineLinkGroup');
            if (this.value === 'online') {
                lg.style.display = 'block';
            } else {
                lg.style.display = 'none';
            }
        };
    });
};
window.closeTimetableModal = function() { document.getElementById('ttModal').classList.remove('active'); };

window.saveTimetableSlot = async function() {
    const id = document.getElementById('ttSlotId').value;
    if (!document.getElementById('ttForm').reportValidity()) return;
    
    // Get class type
    const classTypeEl = document.querySelector('input[name="classType"]:checked');
    const classType = classTypeEl ? classTypeEl.value : 'offline';
    
    const payload = {
        action: id?'update':'create', id,
        batch_id:   document.getElementById('ttSlotBatch').value,
        teacher_id: document.getElementById('ttSlotTeacher').value,
        subject_id:  document.getElementById('ttSlotSubject').value,
        day_of_week:document.getElementById('ttSlotDay').value,
        start_time: document.getElementById('ttSlotStart').value,
        end_time:   document.getElementById('ttSlotEnd').value,
        room:       document.getElementById('ttSlotRoom').value,
        online_link:document.getElementById('ttSlotLink').value,
        class_type: classType
    };
    try {
        const res = await fetch(APP_URL+'/api/frontdesk/timetable',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const data = await res.json();
        if (data.success) { closeTimetableModal(); _ttLoadStats(); loadTimetableData(); Swal.fire('Success',data.message,'success'); }
        else Swal.fire('Conflict / Error',data.message,'error');
    } catch(e) { Swal.fire('Error','Failed to save timetable slot','error'); }
};

window.deleteTimetableSlot = async function() {
    const id = document.getElementById('ttSlotId').value; if (!id) return;
    const r = await Swal.fire({title:'Are you sure?',text:'This will permanently remove this slot.',icon:'warning',showCancelButton:true,confirmButtonColor:'#e11d48',confirmButtonText:'Yes, delete it!'});
    if (!r.isConfirmed) return;
    try {
        const res = await fetch(APP_URL+'/api/frontdesk/timetable',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id})});
        const data = await res.json();
        if (data.success) { closeTimetableModal(); _ttLoadStats(); loadTimetableData(); Swal.fire('Deleted','Slot has been removed.','success'); }
        else Swal.fire('Error',data.message,'error');
    } catch(e) { Swal.fire('Error','Failed to delete slot','error'); }
};
