<?php
/**
 * Dedicated Component: Existing Student Enrollment Form
 * Slim, focused, and reliable.
 */
$componentId        = $componentId ?? 'enroll_existing';
$apiEndpoint        = $apiEndpoint ?? APP_URL . '/api/admin/students';
$successRedirectUrl = $successRedirectUrl ?? 'javascript:goNav(\'students\')';
$pageTitle          = $pageTitle ?? 'Enroll Existing Student';

$formId      = 'formEnroll_' . $componentId;
$btnId       = 'btnSubmit_' . $componentId;
$selStuId    = 'stuSelect_' . $componentId;
$selCourseId = 'selCourse_' . $componentId;
$selBatchId  = 'selBatch_' . $componentId;
$dialogId    = 'dialog_' . $componentId;
?>

<style>
.enroll-box { max-width: 900px; margin: 0 auto; animation: fadeUp 0.5s ease-out; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

.enroll-card {
    background: #fff;
    border-radius: 20px;
    padding: clamp(1.5rem, 5vw, 2.5rem);
    box-shadow: 0 15px 35px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.enroll-section-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 12px;
}
.enroll-section-title i {
    width: 36px;
    height: 36px;
    background: rgba(0, 184, 148, 0.1);
    color: #00b894;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.enroll-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
@media (min-width: 768px) { .enroll-grid.grid-2 { grid-template-columns: 1fr 1fr; } }

.enroll-f-grp { margin-bottom: 0.5rem; }
.enroll-label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; }
.enroll-label.req::after { content: '*'; color: #ff7675; margin-left: 4px; }

.enroll-ipt-box { position: relative; }
.enroll-ipt-box i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; pointer-events: none; }

.enroll-fi {
    width: 100%; padding: 12px 16px 12px 48px; border: 2px solid #e2e8f0; border-radius: 12px;
    font-size: 14px; font-weight: 600; outline: none; transition: all 0.2s; background: #fff;
    color: #1e293b; font-family: inherit; appearance: none;
}
.enroll-fi:focus { border-color: #00b894; box-shadow: 0 0 0 4px rgba(0, 184, 148, 0.1); }
.enroll-fi-sel { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 16px center; background-size: 18px; }

.enroll-stu-card {
    margin-top: 1.5rem; display: none; padding: 1.25rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 15px; align-items: center; gap: 15px;
}
.enroll-stu-img { width: 56px; height: 56px; border-radius: 12px; object-fit: cover; background: #e2e8f0; border: 2px solid #fff; }
.enroll-stu-name { font-weight: 800; color: #1e293b; font-size: 15px; }
.enroll-stu-meta { font-size: 12px; color: #64748b; margin-top: 2px; }

.enroll-btn-confirm {
    width: 100%; padding: 16px; background: linear-gradient(135deg, #009e7e, #00b894); color: #fff; border: none; border-radius: 15px; font-size: 16px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s; box-shadow: 0 8px 20px rgba(0, 184, 148, 0.3);
}
.enroll-btn-confirm:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(0, 184, 148, 0.4); }
.enroll-btn-cancel {
    width: 100%; padding: 16px; background: #fff; color: #64748b; border: 2px solid #e2e8f0; border-radius: 15px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;
}
.enroll-btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }

.enroll-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px; }
.enroll-chip { background: #00b894; color: #fff; padding: 8px 16px; border-radius: 50px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 10px rgba(0, 184, 148, 0.2); }
.enroll-chip i { cursor: pointer; opacity: 0.8; font-size: 14px; }
.enroll-chip i:hover { opacity: 1; }

/* Success Modal Styles */
.enroll-modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); z-index: 10000; display: none; align-items: center; justify-content: center; padding: 20px; }
.enroll-modal-card { background: #fff; border-radius: 24px; padding: 40px; width: 100%; max-width: 480px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); transform: scale(0.9); opacity: 0; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.enroll-modal-card.active { transform: scale(1); opacity: 1; }
.enroll-modal-icon { width: 72px; height: 72px; background: linear-gradient(135deg, #00b894, #009e7e); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; color: #fff; font-size: 32px; box-shadow: 0 10px 20px rgba(0, 184, 148, 0.3); }
</style>

<div class="enroll-box">
    <div class="pg-head" style="margin-bottom: 2rem;">
        <div class="pg-ico" style="background: #00b894; color: #fff;"><i class="fas fa-user-graduate"></i></div>
        <div>
            <h1 class="pg-title"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="pg-sub">Process new course enrollment for an existing student profile</p>
        </div>
    </div>

    <form id="<?= $formId ?>" onsubmit="handleEnrollSubmit_<?= $componentId ?>(event)">
        <!-- Step 1: Student Selection -->
        <div class="enroll-card">
            <h3 class="enroll-section-title"><i class="fas fa-search-user"></i> 1. Select Student</h3>
            <div class="enroll-f-grp">
                <label class="enroll-label req">Search by Name or Roll Number</label>
                <div class="enroll-ipt-box">
                    <i class="fas fa-search"></i>
                    <select id="<?= $selStuId ?>" class="enroll-fi enroll-fi-sel" required>
                        <option value="">-- Loading students... --</option>
                    </select>
                </div>
            </div>
            
            <div id="stuCard_<?= $componentId ?>" class="enroll-stu-card">
                <img id="stuImg_<?= $componentId ?>" class="enroll-stu-img" src="">
                <div style="flex: 1;">
                    <div id="stuName_<?= $componentId ?>" class="enroll-stu-name"></div>
                    <div id="stuMeta_<?= $componentId ?>" class="enroll-stu-meta"></div>
                </div>
                <button type="button" class="btn bs" style="font-size: 12px; padding: 8px 15px;" onclick="resetStuSelect_<?= $componentId ?>()">Change</button>
            </div>
        </div>

        <!-- Step 2: Enrollment Details -->
        <div class="enroll-card" style="border-left: 5px solid #00b894;">
            <h3 class="enroll-section-title"><i class="fas fa-graduation-cap"></i> 2. Academic Placement</h3>
            <div class="enroll-grid grid-2">
                <div class="enroll-f-grp">
                    <label class="enroll-label req">Target Course</label>
                    <div class="enroll-ipt-box">
                        <i class="fas fa-award"></i>
                        <select id="<?= $selCourseId ?>" class="enroll-fi enroll-fi-sel">
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'] . ($c['code'] ? " ({$c['code']})" : "")) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="enroll-f-grp">
                    <label class="enroll-label req">Assigned Batch</label>
                    <div class="enroll-ipt-box">
                        <i class="fas fa-users-class"></i>
                        <select id="<?= $selBatchId ?>" class="enroll-fi enroll-fi-sel" disabled>
                            <option value="">-- Select course first --</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; text-align: center;">
                <button type="button" class="btn bt" style="width: auto; padding: 10px 20px; font-weight: 700;" onclick="addEnrollBatch_<?= $componentId ?>()">
                    <i class="fas fa-plus-circle"></i> ADD TO ENROLLMENT
                </button>
            </div>

            <div id="chips_<?= $componentId ?>" class="enroll-chips"></div>
        </div>

        <!-- Step 3: Action Buttons -->
        <div class="enroll-card" style="background: #f8fafc;">
            <div class="enroll-grid grid-2">
                <button type="submit" id="<?= $btnId ?>" class="enroll-btn-confirm">
                    <i class="fas fa-check-circle"></i> CONFIRM NEW ENROLLMENT
                </button>
                <button type="button" class="enroll-btn-cancel" onclick="window.history.back()">
                    <i class="fas fa-times-circle"></i> CANCEL
                </button>
            </div>
            <p style="text-align: center; font-size: 12px; color: #64748b; margin-top: 1.5rem; font-weight: 600;">
                <i class="fas fa-info-circle"></i> This enrollment will be added to the student's existing profile history.
            </p>
        </div>
    </form>
</div>

<!-- Success Dialog -->
<div id="<?= $dialogId ?>" class="enroll-modal-overlay">
    <div class="enroll-modal-card">
        <div class="enroll-modal-icon"><i class="fas fa-check"></i></div>
        <h2 style="font-size: 24px; font-weight: 900; color: #0f172a; margin-bottom: 10px;">Enrollment Successful!</h2>
        <p style="color: #64748b; line-height: 1.6; margin-bottom: 30px;">
            The student has been successfully enrolled in the new course/batch. All records have been updated.
        </p>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <button onclick="window.location.href='<?= $successRedirectUrl ?>'" class="enroll-btn-confirm">VIEW STUDENT DIRECTORY</button>
            <button onclick="location.reload()" class="enroll-btn-cancel">ENROLL ANOTHER STUDENT</button>
        </div>
    </div>
</div>

<script>
(function() {
    const CID          = '<?= $componentId ?>';
    const API_ENDPOINT = '<?= addslashes($apiEndpoint) ?>';
    const REDIRECT_URL = '<?= addslashes($successRedirectUrl) ?>';
    
    let selectedBatches = [];
    const stuSelect     = document.getElementById('<?= $selStuId ?>');
    const courseSelect  = document.getElementById('<?= $selCourseId ?>');
    const batchSelect   = document.getElementById('<?= $selBatchId ?>');
    const chipsBox      = document.getElementById('chips_<?= $componentId ?>');
    
    // ── Load Students Dropdown ──
    async function initStuDropdown() {
        try {
            const res = await fetch(`${window.APP_URL}/api/admin/students?action=list_all`);
            const r   = await res.json();
            if (r.success && r.data) {
                let html = '<option value="">-- Search & Select Student --</option>';
                r.data.forEach(s => {
                    html += `<option value="${s.id}" data-name="${s.name}" data-roll="${s.roll_no}" data-img="${s.photo_url || ''}">${s.name} [${s.roll_no || 'No Roll'}]</option>`;
                });
                stuSelect.innerHTML = html;
                
                // If student_id is in URL, pre-select it
                const preId = new URLSearchParams(window.location.search).get('student_id');
                if (preId) {
                    stuSelect.value = preId;
                    onStuChange(preId);
                }
            } else {
                stuSelect.innerHTML = '<option value="">Failed to load students</option>';
            }
        } catch (e) {
            stuSelect.innerHTML = '<option value="">Error loading students</option>';
        }
    }

    // ── Handling Student Selection ──
    function onStuChange(val) {
        const card = document.getElementById('stuCard_' + CID);
        if (!val) {
            card.style.display = 'none';
            return;
        }
        const opt = stuSelect.options[stuSelect.selectedIndex];
        document.getElementById('stuName_' + CID).textContent = opt.dataset.name;
        document.getElementById('stuMeta_' + CID).textContent = 'Roll No: ' + (opt.dataset.roll || 'N/A');
        
        const photo = opt.dataset.img;
        document.getElementById('stuImg_' + CID).src = photo ? (photo.startsWith('http') ? photo : window.APP_URL + photo) : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(opt.dataset.name) + '&background=00b894&color=fff';
        
        card.style.display = 'flex';
        stuSelect.closest('.enroll-f-grp').style.display = 'none';
    }
    
    window['resetStuSelect_' + CID] = function() {
        stuSelect.value = "";
        document.getElementById('stuCard_' + CID).style.display = 'none';
        stuSelect.closest('.enroll-f-grp').style.display = 'block';
    };
    
    stuSelect.onchange = (e) => onStuChange(e.target.value);

    // ── Course to Batch Cascading ──
    courseSelect.onchange = async (e) => {
        const cId = e.target.value;
        if (!cId) {
            batchSelect.innerHTML = '<option value="">-- Select course first --</option>';
            batchSelect.disabled  = true;
            return;
        }
        batchSelect.innerHTML = '<option value="">⏳ Loading batches...</option>';
        batchSelect.disabled  = true;
        try {
            const res = await fetch(`${window.APP_URL}/api/admin/batches?course_id=${cId}`);
            const r   = await res.json();
            if (r.success && r.data && r.data.length > 0) {
                let h = '<option value="">-- Select Batch --</option>';
                r.data.forEach(b => {
                    h += `<option value="${b.id}">${b.name} (${b.shift || 'Regular'})</option>`;
                });
                batchSelect.innerHTML = h;
                batchSelect.disabled  = false;
            } else {
                batchSelect.innerHTML = '<option value="">No batches available</option>';
                batchSelect.disabled  = true;
            }
        } catch (e) {
            batchSelect.innerHTML = '<option value="">Error loading batches</option>';
        }
    };

    // ── Batch Chipping ──
    window['addEnrollBatch_' + CID] = function() {
        const bId = batchSelect.value;
        if (!bId) {
            Swal.fire('Selection Required', 'Please select a batch to add.', 'warning');
            return;
        }
        
        const cText = courseSelect.options[courseSelect.selectedIndex].text;
        const bText = batchSelect.options[batchSelect.selectedIndex].text;
        
        if (selectedBatches.some(b => b.id === bId)) {
            Swal.fire('Already Added', 'This batch is already in the list.', 'info');
            return;
        }
        
        selectedBatches.push({ id: bId, label: `${cText} › ${bText}` });
        renderChips();
        batchSelect.value = "";
    };

    function renderChips() {
        chipsBox.innerHTML = selectedBatches.map((b, i) => `
            <div class="enroll-chip">
                <span>${b.label}</span>
                <i class="fas fa-times-circle" onclick="removeEnrollBatch_${CID}(${i})"></i>
            </div>
        `).join('');
    }

    window['removeEnrollBatch_' + CID] = function(idx) {
        selectedBatches.splice(idx, 1);
        renderChips();
    };

    // ── Submission ──
    window['handleEnrollSubmit_' + CID] = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('<?= $btnId ?>');
        if (!stuSelect.value) { Swal.fire('Student Required', 'Please select a student.', 'warning'); return; }
        if (selectedBatches.length === 0) { Swal.fire('Placement Required', 'Please add at least one course/batch.', 'warning'); return; }
        
        btn.disabled = true;
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        const payload = {
            student_id: stuSelect.value,
            batch_ids:  selectedBatches.map(b => b.id),
            batch_id:   selectedBatches[0].id, // fallback for legacy APIs
            registration_status: 'fully_registered'
        };

        try {
            const res = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
                body: JSON.stringify(payload)
            });
            const r = await res.json();
            if (r.success) {
                const overlay = document.getElementById('<?= $dialogId ?>');
                const card    = overlay.querySelector('.enroll-modal-card');
                overlay.style.display = 'flex';
                setTimeout(() => card.classList.add('active'), 10);
            } else {
                Swal.fire('Enrollment Failed', r.message || 'Server error', 'error');
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
        } catch (err) {
            Swal.fire('Network Error', 'Connection lost. Please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    };

    initStuDropdown();
})();
</script>
