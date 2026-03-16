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

.tt-day-column.drag-over {
    background: #f0f9ff;
    border: 2px dashed #0ea5e9;
}

.tt-slot.dragging {
    opacity: 0.5;
    transform: scale(0.95);
    background: #f1f5f9;
}

.tt-slot {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 12px;
    cursor: grab;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.tt-slot:active {
    cursor: grabbing;
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
.tt-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px); /* Keeping it but making it explicit and scoped */
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.tt-modal-overlay.active {
    display: flex;
}

.tt-modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: ttModalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes ttModalScale {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
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
        <h2 class="tt-title">Timetable Builder</h2>
        <div class="tt-acts">
            <button class="tt-btn tt-btn-secondary" onclick="exportPDF()">
                <i class="fa-solid fa-file-pdf"></i> Export PDF
            </button>
            <button class="tt-btn tt-btn-primary" onclick="openAddModal()">
                <i class="fa-solid fa-plus"></i> Add Slot
            </button>
        </div>
    </div>
    
    <div class="tt-filters">
        <select class="tt-select" id="batchFilter">
            <option value="">Select Batch</option>
        </select>
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
<div class="tt-modal-overlay" id="slotModal">
    <div class="tt-modal">
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
            <button class="tt-btn tt-btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="tt-btn tt-btn-danger" id="deleteBtn" style="display:none;" onclick="deleteSlot()">Delete</button>
            <button class="tt-btn tt-btn-primary" onclick="saveSlot()">Save</button>
        </div>
    </div>
</div>

<script>
    window.currentTenantId = '<?php echo $_SESSION['userData']['tenant_id'] ?? $_SESSION['tenant_id'] ?? ''; ?>';
    window.baseUrl = '<?php echo APP_URL; ?>';
</script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-timetable.js"></script>
