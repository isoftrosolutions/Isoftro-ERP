/**
 * Timetable Builder JavaScript
 * Handles grid rendering, conflict detection UI, and CRUD operations
 */

let currentBatches = [];
let currentTeachers = [];
let currentSubjects = [];
let currentRooms = [];

document.addEventListener('DOMContentLoaded', function() {
    loadBatches();
    loadTeachers();
    loadSubjects();
    loadRooms();
    loadTimetable();
    
    // Event listeners
    const batchFilter = document.getElementById('batchFilter');
    if (batchFilter) {
        batchFilter.addEventListener('change', loadTimetable);
    }
});

async function loadRooms() {
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

async function loadSubjects() {
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

async function loadBatches() {
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

async function loadTeachers() {
    try {
        const response = await fetch(`${window.baseUrl}/api/admin/staff?role=teacher&tenant_id=${window.currentTenantId}`);
        const result = await response.json();
        
        if (result.success) {
            currentTeachers = result.data || [];
            const select = document.getElementById('slotTeacher');
            if (select) {
                select.innerHTML = '<option value="">Select Teacher</option>' + 
                    currentTeachers.map(t => {
                        const name = t.name || t.teacher_name || 'Unknown';
                        return `<option value="${t.teacher_id || t.id}">${name}</option>`;
                    }).join('');
            }
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
    }
}

async function loadTimetable() {
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
            renderTimetable(result.grouped || []);
        } else {
            if (grid) grid.innerHTML = '<div class="tt-empty">Error loading timetable</div>';
        }
    } catch (error) {
        console.error('Error loading timetable:', error);
        if (grid) grid.innerHTML = '<div class="tt-empty">Error loading timetable</div>';
    }
}

function renderTimetable(groupedData) {
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
                <div class="tt-slot" draggable="true" ondragstart="handleDragStart(event, ${slot.id})" onclick="editSlot(${slot.id})">
                    <div class="tt-slot-time">${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</div>
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
    attachDropListeners();
}

let draggedSlotId = null;

function handleDragStart(e, id) {
    draggedSlotId = id;
    e.dataTransfer.setData('text/plain', id);
    e.target.classList.add('dragging');
}

function attachDropListeners() {
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
                await updateSlotDay(draggedSlotId, dayOfWeek);
                draggedSlotId = null;
            }
        });
    });
}

async function updateSlotDay(slotId, newDay) {
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
            loadTimetable();
        } else {
            alert(result.message || 'Error moving slot');
        }
    } catch (error) {
        console.error('Error updating slot day:', error);
        alert('Error moving slot');
    }
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

function closeModal() {
    const modal = document.getElementById('slotModal');
    if (modal) modal.classList.remove('active');
}

async function saveSlot() {
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
        const response = await fetch(`${window.baseUrl}/api/admin/timetable?tenant_id=${window.currentTenantId}`, {
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
