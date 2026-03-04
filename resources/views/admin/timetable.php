<?php
/**
 * Timetable Builder View
 * Allows admin to create and manage class schedules
 */
?>

<style>
.timetable-container {
    padding: 20px;
}

.tt-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.tt-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.tt-filters {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.tt-select {
    padding: 10px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    min-width: 200px;
    background: white;
    cursor: pointer;
}

.tt-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.tt-btn-primary {
    background: #0d9488;
    color: white;
}

.tt-btn-primary:hover {
    background: #0f766e;
}

.tt-btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.tt-btn-secondary:hover {
    background: #e2e8f0;
}

.tt-btn-danger {
    background: #fee2e2;
    color: #dc2626;
}

.tt-btn-danger:hover {
    background: #fecaca;
}

/* Timetable Grid */
.tt-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 12px;
    margin-top: 20px;
}

.tt-day-column {
    background: #f8fafc;
    border-radius: 12px;
    padding: 12px;
    min-height: 400px;
}

.tt-day-header {
    text-align: center;
    padding: 12px;
    background: #0d9488;
    color: white;
    border-radius: 8px;
    font-weight: 600;
    margin-bottom: 12px;
}

.tt-day-header.sunday {
    background: #dc2626;
}

.tt-slot {
    background: white;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    border-left: 4px solid #0d9488;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.tt-slot:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.tt-slot-time {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 6px;
}

.tt-slot-subject {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.tt-slot-teacher {
    font-size: 12px;
    color: #64748b;
}

.tt-slot-room {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 6px;
}

.tt-empty {
    text-align: center;
    padding: 20px;
    color: #94a3b8;
    font-size: 13px;
}

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #64748b;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.modal-close:hover {
    background: #f1f5f9;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.form-input, .form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* Responsive */
@media (max-width: 1024px) {
    .tt-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 768px) {
    .tt-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .tt-filters {
        flex-direction: column;
    }
    
    .tt-select {
        width: 100%;
    }
}

.tt-loading {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

.tt-loading i {
    font-size: 32px;
    margin-bottom: 12px;
    display: block;
}
</style>

<div class="timetable-container">
    <div class="tt-header">
        <h1 class="tt-title">Timetable Builder</h1>
    </div>
    
    <div class="tt-filters">
        <select class="tt-select" id="batchFilter">
            <option value="">Select Batch</option>
        </select>
        <button class="tt-btn tt-btn-primary" onclick="openAddModal()">
            <i class="fa-solid fa-plus"></i> Add Slot
        </button>
        <button class="tt-btn tt-btn-secondary" onclick="loadTimetable()">
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
<div class="modal-overlay" id="slotModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add Timetable Slot</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
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
                    <input type="text" class="form-input" id="slotSubject" placeholder="Enter subject name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Day of Week *</label>
                    <select class="form-select" id="slotDay" required>
                        <option value=""> Select Day</option>
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
                        <input type="text" class="form-input" id="slotRoom" placeholder="e.g., Room 101">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Online Link</label>
                        <input type="url" class="form-input" id="slotOnlineLink" placeholder="https://...">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="tt-btn tt-btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="tt-btn tt-btn-danger" id="deleteBtn" style="display:none;" onclick="deleteSlot()">Delete</button>
            <button class="tt-btn tt-btn-primary" onclick="saveSlot()">Save</button>
        </div>
    </div>
</div>

<script>
let currentBatches = [];
let currentTeachers = [];

document.addEventListener('DOMContentLoaded', function() {
    loadBatches();
    loadTeachers();
    loadTimetable();
});

async function loadBatches() {
    try {
        const response = await fetch('?api=batches&tenant_id=' + window.currentTenantId);
        const result = await response.json();
        
        if (result.success) {
            currentBatches = result.data || [];
            const select = document.getElementById('batchFilter');
            const modalSelect = document.getElementById('slotBatch');
            
            // Keep first option
            const filterOptions = '<option value="">All Batches</option>';
            const modalOptions = '<option value="">Select Batch</option>';
            
            select.innerHTML = filterOptions + currentBatches.map(b => 
                `<option value="${b.id}">${b.name}</option>`
            ).join('');
            
            modalSelect.innerHTML = modalOptions + currentBatches.map(b => 
                `<option value="${b.id}">${b.name}</option>`
            ).join('');
        }
    } catch (error) {
        console.error('Error loading batches:', error);
    }
}

async function loadTeachers() {
    try {
        const response = await fetch('?api=staff&role=teacher&tenant_id=' + window.currentTenantId);
        const result = await response.json();
        
        if (result.success) {
            currentTeachers = result.data || [];
            const select = document.getElementById('slotTeacher');
            
            select.innerHTML = '<option value="">Select Teacher</option>' + 
                currentTeachers.map(t => {
                    const name = t.name || t.teacher_name || 'Unknown';
                    return `<option value="${t.teacher_id || t.id}">${name}</option>`;
                }).join('');
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
    }
}

async function loadTimetable() {
    const batchId = document.getElementById('batchFilter').value;
    const grid = document.getElementById('timetableGrid');
    
    grid.innerHTML = '<div class="tt-loading"><i class="fa-solid fa-spinner fa-spin"></i>Loading timetable...</div>';
    
    try {
        const url = batchId 
            ? `?api=timetable&batch_id=${batchId}&tenant_id=${window.currentTenantId}`
            : `?api=timetable&tenant_id=${window.currentTenantId}`;
            
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            renderTimetable(result.grouped || []);
        } else {
            grid.innerHTML = '<div class="tt-empty">Error loading timetable</div>';
        }
    } catch (error) {
        console.error('Error loading timetable:', error);
        grid.innerHTML = '<div class="tt-empty">Error loading timetable</div>';
    }
}

function renderTimetable(groupedData) {
    const grid = document.getElementById('timetableGrid');
    const days = ['', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    // Create all 7 day columns
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
                <div class="tt-slot" onclick="editSlot(${slot.id})">
                    <div class="tt-slot-time">${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</div>
                    <div class="tt-slot-subject">${slot.subject}</div>
                    <div class="tt-slot-teacher">${slot.teacher_name || 'No teacher'}</div>
                    ${slot.room ? `<div class="tt-slot-room"><i class="fa-solid fa-door"></i> ${slot.room}</div>` : ''}
                </div>`;
            });
        }
        
        html += '</div>';
    }
    
    grid.innerHTML = html;
}

function formatTime(time) {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Timetable Slot';
    document.getElementById('slotId').value = '';
    document.getElementById('slotForm').reset();
    document.getElementById('deleteBtn').style.display = 'none';
    document.getElementById('slotModal').classList.add('active');
}

async function editSlot(slotId) {
    // Get the slot data from the current timetable
    const batchId = document.getElementById('batchFilter').value;
    const url = batchId 
        ? `?api=timetable&batch_id=${batchId}&tenant_id=${window.currentTenantId}`
        : `?api=timetable&tenant_id=${window.currentTenantId}`;
    
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
                document.getElementById('slotSubject').value = slot.subject;
                document.getElementById('slotDay').value = slot.day_of_week;
                document.getElementById('slotStartTime').value = slot.start_time;
                document.getElementById('slotEndTime').value = slot.end_time;
                document.getElementById('slotRoom').value = slot.room || '';
                document.getElementById('slotOnlineLink').value = slot.online_link || '';
                document.getElementById('deleteBtn').style.display = 'inline-flex';
                document.getElementById('slotModal').classList.add('active');
            }
        }
    } catch (error) {
        console.error('Error loading slot:', error);
    }
}

function closeModal() {
    document.getElementById('slotModal').classList.remove('active');
}

async function saveSlot() {
    const slotId = document.getElementById('slotId').value;
    const data = {
        action: slotId ? 'update' : 'create',
        batch_id: document.getElementById('slotBatch').value,
        teacher_id: document.getElementById('slotTeacher').value,
        subject: document.getElementById('slotSubject').value,
        day_of_week: document.getElementById('slotDay').value,
        start_time: document.getElementById('slotStartTime').value,
        end_time: document.getElementById('slotEndTime').value,
        room: document.getElementById('slotRoom').value,
        online_link: document.getElementById('slotOnlineLink').value
    };
    
    if (slotId) {
        data.id = slotId;
    }
    
    // Validation
    if (!data.batch_id || !data.teacher_id || !data.subject || !data.day_of_week || !data.start_time || !data.end_time) {
        alert('Please fill in all required fields');
        return;
    }
    
    try {
        const response = await fetch('?api=timetable&tenant_id=' + window.currentTenantId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            loadTimetable();
        } else {
            alert(result.message || 'Error saving timetable slot');
        }
    } catch (error) {
        console.error('Error saving slot:', error);
        alert('Error saving timetable slot');
    }
}

async function deleteSlot() {
    const slotId = document.getElementById('slotId').value;
    
    if (!confirm('Are you sure you want to delete this timetable slot?')) {
        return;
    }
    
    try {
        const response = await fetch('?api=timetable&tenant_id=' + window.currentTenantId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: slotId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            loadTimetable();
        } else {
            alert(result.message || 'Error deleting timetable slot');
        }
    } catch (error) {
        console.error('Error deleting slot:', error);
        alert('Error deleting timetable slot');
    }
}

// Event listeners
document.getElementById('batchFilter').addEventListener('change', loadTimetable);
</script>
