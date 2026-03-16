/**
 * Timetable Builder JavaScript
 * Handles grid rendering, conflict detection UI, and CRUD operations
 *
 * SPA Entry Point: window.renderTimetablePage()
 * Called by ia-core.js when user navigates to academic > timetable.
 */

let currentBatches = [];
let currentTeachers = [];
let currentSubjects = [];
let currentRooms = [];

/**
 * SPA PAGE RENDERER — called by ia-core.js (line 212):
 *   if (sub==='timetable') { window.renderTimetablePage?.(); return; }
 *
 * This was the PRIMARY root cause of the infinite loading bug.
 * ia-core.js first sets #mainContent to a loading spinner, then calls
 * this function to replace it. Without it, the spinner stays forever.
 */
window.renderTimetablePage = function() {
    // Resolve globals from SPA context if not already set
    if (!window.baseUrl)         window.baseUrl         = window.APP_URL || '';
    if (!window.currentTenantId) window.currentTenantId = window._IA_TENANT_ID || '';

    const mc = document.getElementById('mainContent');
    if (!mc) return;

    // Inject Timetable HTML into the SPA main content area
    mc.innerHTML = `
        <style>
            .timetable-container { padding: 20px; }
            .tt-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .tt-title { font-size: 24px; font-weight: 700; color: #1e293b; }
            .tt-filters { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
            .tt-select { padding: 10px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; min-width: 200px; background: white; cursor: pointer; }
            .tt-btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
            .tt-btn-primary { background: #0d9488; color: white; }
            .tt-btn-primary:hover { background: #0f766e; }
            .tt-btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
            .tt-btn-secondary:hover { background: #e2e8f0; }
            .tt-btn-danger { background: #fee2e2; color: #dc2626; }
            .tt-btn-danger:hover { background: #fecaca; }
            .tt-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px; margin-top: 20px; }
            .tt-day-column { background: #f8fafc; border-radius: 12px; padding: 12px; min-height: 400px; }
            .tt-day-header { text-align: center; padding: 12px; background: #0d9488; color: white; border-radius: 8px; font-weight: 600; margin-bottom: 12px; }
            .tt-day-header.sunday { background: #dc2626; }
            .tt-day-column.drag-over { background: #f0f9ff; border: 2px dashed #0ea5e9; }
            .tt-slot { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px; cursor: grab; transition: transform 0.2s, box-shadow 0.2s; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
            .tt-slot.dragging { opacity: 0.5; transform: scale(0.95); background: #f1f5f9; }
            .tt-slot:active { cursor: grabbing; }
            .tt-slot:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
            .tt-slot-time { font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 6px; }
            .tt-slot-subject { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
            .tt-slot-teacher { font-size: 12px; color: #64748b; }
            .tt-slot-room { font-size: 11px; color: #94a3b8; margin-top: 6px; }
            .tt-empty { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; }
            .tt-loading { text-align: center; padding: 40px; color: #64748b; }
            .tt-loading i { font-size: 32px; margin-bottom: 12px; display: block; }
            .tt-acts { display: flex; gap: 8px; }
            .tt-modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 10000; align-items: center; justify-content: center; padding: 20px; }
            .tt-modal-overlay.active { display: flex; }
            .tt-modal { background: white; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: ttModalScale 0.3s cubic-bezier(0.34,1.56,0.64,1); }
            @keyframes ttModalScale { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
            .modal-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
            .modal-title { font-size: 18px; font-weight: 700; color: #1e293b; }
            .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
            .modal-close:hover { background: #f1f5f9; }
            .modal-body { padding: 24px; }
            .modal-footer { padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; }
            .form-group { margin-bottom: 16px; }
            .form-label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 6px; }
            .form-input, .form-select { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; box-sizing: border-box; }
            .form-input:focus, .form-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
            .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
            @media (max-width: 1024px) { .tt-grid { grid-template-columns: repeat(4,1fr); } }
            @media (max-width: 768px) { .tt-grid { grid-template-columns: repeat(2,1fr); } .tt-filters { flex-direction: column; } .tt-select { width: 100%; } }
        </style>

        <div class="timetable-container">
            <div class="tt-header">
                <h2 class="tt-title">Timetable Builder</h2>
                <div class="tt-acts">
                    <button class="tt-btn tt-btn-secondary" onclick="exportPDF()">
                        <i class="fa-solid fa-file-pdf"></i> Export PDF
                    </button>
                    <button id="ttAddSlotBtn" class="tt-btn tt-btn-primary" onclick="ttOpenAddModal()">
                        <i class="fa-solid fa-plus"></i> Add Slot
                    </button>
                </div>
            </div>

            <div class="tt-filters">
                <select class="tt-select" id="batchFilter">
                    <option value="">All Batches</option>
                </select>
                <button class="tt-btn tt-btn-secondary" onclick="ttLoadTimetable()">
                    <i class="fa-solid fa-refresh"></i> Refresh
                </button>
            </div>

            <div class="tt-grid" id="timetableGrid">
                <div class="tt-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Loading timetable...
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div class="tt-modal-overlay" id="slotModal">
            <div class="tt-modal">
                <div class="modal-header">
                    <h3 class="modal-title" id="modalTitle">Add Timetable Slot</h3>
                    <button class="modal-close" onclick="ttCloseModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="slotForm">
                        <input type="hidden" id="slotId" value="">
                        <div class="form-group">
                            <label class="form-label">Batch *</label>
                            <select class="form-select" id="slotBatch" required>
                                <option value="">Select Batch</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teacher *</label>
                            <select class="form-select" id="slotTeacher" required>
                                <option value="">Select Teacher</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subject *</label>
                            <select class="form-select" id="slotSubject" required>
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Class Type *</label>
                            <select class="form-select" id="slotClassType" required>
                                <option value="offline">Offline (In-Person)</option>
                                <option value="online">Online (Virtual)</option>
                                <option value="lab">Lab / Practical</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Day of Week *</label>
                            <select class="form-select" id="slotDay" required>
                                <option value="">Select Day</option>
                                <option value="1">Sunday</option>
                                <option value="2">Monday</option>
                                <option value="3">Tuesday</option>
                                <option value="4">Wednesday</option>
                                <option value="5">Thursday</option>
                                <option value="6">Friday</option>
                                <option value="7">Saturday</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Start Time *</label>
                                <input type="time" class="form-input" id="slotStartTime" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Time *</label>
                                <input type="time" class="form-input" id="slotEndTime" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Room</label>
                                <select class="form-select" id="slotRoom">
                                    <option value="">Select Room</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Online Link</label>
                                <input type="url" class="form-input" id="slotOnlineLink" placeholder="https://...">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="tt-btn tt-btn-secondary" onclick="ttCloseModal()">Cancel</button>
                    <button class="tt-btn tt-btn-danger" id="deleteBtn" style="display:none;" onclick="ttDeleteSlot()">Delete</button>
                    <button class="tt-btn tt-btn-primary" onclick="ttSaveSlot()">Save</button>
                </div>
            </div>
        </div>
    `;

    // Initialize all data loaders now that DOM elements exist
    ttLoadBatches();
    ttLoadTeachers();
    ttLoadSubjects();
    ttLoadRooms();
    ttLoadTimetable();

    // Attach filter event listener
    const batchFilter = document.getElementById('batchFilter');
    if (batchFilter) {
        batchFilter.addEventListener('change', ttLoadTimetable);
    }
};

async function ttLoadRooms() {
    try {
        const response = await fetch(`${window.baseUrl}/api/admin/rooms?tenant_id=${window.currentTenantId}`);
        const result = await response.json();
        
        if (result.success) {
            currentRooms = result.data || [];
            const select = document.getElementById('slotRoom');
            if (select) {
                select.innerHTML = '<option value="">Select Room</option>' + 
                    currentRooms.map(r => 
                        `<option value="${r.id}">${r.name} ${r.code ? '('+r.code+')' : ''}</option>`
                    ).join('');
            }
        }
    } catch (error) {
        console.error('Error loading rooms:', error);
    }
}

async function ttLoadSubjects() {
    try {
        const response = await fetch(`${window.baseUrl}/api/admin/subjects?tenant_id=${window.currentTenantId}`);
        const result = await response.json();
        
        if (result.success) {
            currentSubjects = result.data || [];
            const select = document.getElementById('slotSubject');
            if (select) {
                select.innerHTML = '<option value="">Select Subject</option>' + 
                    currentSubjects.map(s => 
                        `<option value="${s.id}">${s.name} (${s.code})</option>`
                    ).join('');
            }
        }
    } catch (error) {
        console.error('Error loading subjects:', error);
    }
}

async function ttLoadBatches() {
    try {
        const response = await fetch(`${window.baseUrl}/api/admin/batches?tenant_id=${window.currentTenantId}`);
        const result = await response.json();
        
        if (result.success) {
            currentBatches = result.data || [];
            const select = document.getElementById('batchFilter');
            const modalSelect = document.getElementById('slotBatch');
            
            const filterOptions = '<option value="">All Batches</option>';
            const modalOptions = '<option value="">Select Batch</option>';
            
            if (select) {
                select.innerHTML = filterOptions + currentBatches.map(b => 
                    `<option value="${b.id}">${b.name}</option>`
                ).join('');
            }
            
            if (modalSelect) {
                modalSelect.innerHTML = modalOptions + currentBatches.map(b => 
                    `<option value="${b.id}">${b.name}</option>`
                ).join('');
            }
        }
    } catch (error) {
        console.error('Error loading batches:', error);
    }
}

async function ttLoadTeachers() {
    try {
        const response = await fetch(`${window.baseUrl}/api/admin/staff?role=teacher&tenant_id=${window.currentTenantId}`);
        const result = await response.json();
        
        if (result.success) {
            currentTeachers = result.data || [];
            const select = document.getElementById('slotTeacher');
            if (select) {
                select.innerHTML = '<option value="">Select Teacher</option>' + 
                    currentTeachers.map(t => {
                        const name = t.full_name || t.name || 'Unknown';
                        return `<option value="${t.id}">${name}</option>`;
                    }).join('');
            }
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
    }
}

async function ttLoadTimetable() {
    const filterEl = document.getElementById('batchFilter');
    const batchId = filterEl ? filterEl.value : '';
    const grid = document.getElementById('timetableGrid');
    
    if (grid) {
        grid.innerHTML = '<div class="tt-loading"><i class="fa-solid fa-spinner fa-spin"></i>Loading timetable...</div>';
    }
    
    try {
        const url = batchId 
            ? `${window.baseUrl}/api/admin/timetable?batch_id=${batchId}&tenant_id=${window.currentTenantId}`
            : `${window.baseUrl}/api/admin/timetable?tenant_id=${window.currentTenantId}`;
            
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            ttRenderTimetable(result.grouped || []);
        } else {
            if (grid) grid.innerHTML = '<div class="tt-empty">Error loading timetable</div>';
        }
    } catch (error) {
        console.error('Error loading timetable:', error);
        if (grid) grid.innerHTML = '<div class="tt-empty">Error loading timetable</div>';
    }
}

function ttRenderTimetable(groupedData) {
    const grid = document.getElementById('timetableGrid');
    if (!grid) return;

    const days = ['', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    let html = '';
    for (let i = 1; i <= 7; i++) {
        const dayData = groupedData.find(d => d.day_of_week == i);
        const slots = dayData ? dayData.slots : [];
        const isSunday = i === 1;
        
        html += `<div class="tt-day-column">
            <div class="tt-day-header ${isSunday ? 'sunday' : ''}">${days[i]}</div>`;
        
        if (slots.length === 0) {
            html += '<div class="tt-empty">No classes</div>';
        } else {
            slots.forEach(slot => {
                html += `
                <div class="tt-slot" draggable="true" ondragstart="ttHandleDragStart(event, ${slot.id})" onclick="ttEditSlot(${slot.id})">
                    <div class="tt-slot-time">${ttFormatTime(slot.start_time)} - ${ttFormatTime(slot.end_time)}</div>
                    <div class="tt-slot-subject">${slot.subject_name || slot.subject}</div>
                    <div class="tt-slot-teacher">${slot.teacher_name || 'No teacher'}</div>
                    ${slot.room_name ? `<div class="tt-slot-room"><i class="fa-solid fa-door-open"></i> ${slot.room_name}</div>` : ''}
                </div>`;
            });
        }
        
        html += '</div>';
    }
    
    grid.innerHTML = html;
    
    // Attach drop listeners to new columns
    ttAttachDropListeners();
}

let draggedSlotId = null;

function ttHandleDragStart(e, id) {
    draggedSlotId = id;
    e.dataTransfer.setData('text/plain', id);
    e.target.classList.add('dragging');
}

function ttAttachDropListeners() {
    const columns = document.querySelectorAll('.tt-day-column');
    columns.forEach((col, index) => {
        const dayOfWeek = index + 1; // 1-7 (Sun-Sat)
        
        col.addEventListener('dragover', (e) => {
            e.preventDefault();
            col.classList.add('drag-over');
        });
        
        col.addEventListener('dragleave', () => {
            col.classList.remove('drag-over');
        });
        
        col.addEventListener('drop', async (e) => {
            e.preventDefault();
            col.classList.remove('drag-over');
            
            if (draggedSlotId) {
                await ttUpdateSlotDay(draggedSlotId, dayOfWeek);
                draggedSlotId = null;
            }
        });
    });
}

async function ttUpdateSlotDay(slotId, newDay) {
    try {
        // Fetch current slot data first to maintain times/teacher/etc
        const batchId = document.getElementById('batchFilter').value;
        const fetchUrl = batchId 
            ? `${window.baseUrl}/api/admin/timetable?batch_id=${batchId}&tenant_id=${window.currentTenantId}`
            : `${window.baseUrl}/api/admin/timetable?tenant_id=${window.currentTenantId}`;
            
        const fetchResponse = await fetch(fetchUrl);
        const fetchResult = await fetchResponse.json();
        
        if (!fetchResult.success) throw new Error("Could not fetch slot data");
        
        const slot = (fetchResult.data || []).find(s => s.id == slotId);
        if (!slot) throw new Error("Slot not found");

        // Update day
        const data = {
            action: 'update',
            id: slotId,
            batch_id: slot.batch_id,
            teacher_id: slot.teacher_id,
            subject_id: slot.subject_id,
            class_type: slot.class_type,
            day_of_week: newDay,
            start_time: slot.start_time,
            end_time: slot.end_time,
            room_id: slot.room_id,
            online_link: slot.online_link
        };

        const response = await fetch(`${window.baseUrl}/api/admin/timetable?tenant_id=${window.currentTenantId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            ttLoadTimetable();
        } else {
            alert(result.message || 'Error moving slot');
        }
    } catch (error) {
        console.error('Error updating slot day:', error);
        alert('Error moving slot');
    }
}

function ttFormatTime(time) {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
}

function ttOpenAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Timetable Slot';
    document.getElementById('slotId').value = '';
    document.getElementById('slotForm').reset();
    document.getElementById('deleteBtn').style.display = 'none';
    document.getElementById('slotModal').classList.add('active');
}

async function ttEditSlot(slotId) {
    const filterEl = document.getElementById('batchFilter');
    const batchId = filterEl ? filterEl.value : '';
    const url = batchId 
        ? `${window.baseUrl}/api/admin/timetable?batch_id=${batchId}&tenant_id=${window.currentTenantId}`
        : `${window.baseUrl}/api/admin/timetable?tenant_id=${window.currentTenantId}`;
    
    try {
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            const allSlots = result.data || [];
            const slot = allSlots.find(s => s.id == slotId);
            
            if (slot) {
                document.getElementById('modalTitle').textContent = 'Edit Timetable Slot';
                document.getElementById('slotId').value = slot.id;
                document.getElementById('slotBatch').value = slot.batch_id;
                document.getElementById('slotTeacher').value = slot.teacher_id;
                document.getElementById('slotSubject').value = slot.subject_id;
                document.getElementById('slotClassType').value = slot.class_type || 'offline';
                document.getElementById('slotDay').value = slot.day_of_week;
                document.getElementById('slotStartTime').value = slot.start_time;
                document.getElementById('slotEndTime').value = slot.end_time;
                document.getElementById('slotRoom').value = slot.room_id || '';
                document.getElementById('slotOnlineLink').value = slot.online_link || '';
                document.getElementById('deleteBtn').style.display = 'inline-flex';
                document.getElementById('slotModal').classList.add('active');
            }
        }
    } catch (error) {
        console.error('Error loading slot:', error);
    }
}

function ttCloseModal() {
    const modal = document.getElementById('slotModal');
    if (modal) modal.classList.remove('active');
}

async function ttSaveSlot() {
    const slotId = document.getElementById('slotId').value;
    const data = {
        action: slotId ? 'update' : 'create',
        batch_id: document.getElementById('slotBatch').value,
        teacher_id: document.getElementById('slotTeacher').value,
        subject_id: document.getElementById('slotSubject').value,
        class_type: document.getElementById('slotClassType').value,
        day_of_week: document.getElementById('slotDay').value,
        start_time: document.getElementById('slotStartTime').value,
        end_time: document.getElementById('slotEndTime').value,
        room_id: document.getElementById('slotRoom').value,
        online_link: document.getElementById('slotOnlineLink').value
    };
    
    if (slotId) {
        data.id = slotId;
    }
    
    // Validation
    if (!data.batch_id || !data.teacher_id || !data.subject_id || !data.day_of_week || !data.start_time || !data.end_time) {
        alert('Please fill in all required fields');
        return;
    }

    if (data.start_time >= data.end_time) {
        alert('Start time must be before end time');
        return;
    }
    
    try {
        const response = await fetch(`${window.baseUrl}/api/admin/timetable?tenant_id=${window.currentTenantId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            ttCloseModal();
            ttLoadTimetable();
        } else {
            alert(result.message || 'Error saving timetable slot');
        }
    } catch (error) {
        console.error('Error saving slot:', error);
        alert('Error saving timetable slot');
    }
}

async function ttDeleteSlot() {
    const slotId = document.getElementById('slotId').value;
    
    if (!confirm('Are you sure you want to delete this timetable slot?')) {
        return;
    }
    
    try {
        const response = await fetch(`${window.baseUrl}/api/admin/timetable?tenant_id=${window.currentTenantId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: slotId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            ttCloseModal();
            ttLoadTimetable();
        } else {
            alert(result.message || 'Error deleting timetable slot');
        }
    } catch (error) {
        console.error('Error deleting slot:', error);
        alert('Error deleting timetable slot');
    }
}
