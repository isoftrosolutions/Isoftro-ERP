<?php
/**
 * Front Desk — Add New Inquiry
 * Beautiful form for capturing potential student leads
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Add New Inquiry';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';

}
// Fetch courses for the dropdown
try {
    $db = \App\Support\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, name FROM courses WHERE tenant_id = ? AND status = 'active' ORDER BY name ASC");
    $stmt->execute([$_SESSION['userData']['tenant_id'] ?? 0]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $courses = [];
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('inquiries');
}
?>
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #8B5CF6, #7C3AED);">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <div>
                    <h1 class="pg-title">New Inquiry</h1>
                    <p class="pg-sub">Capture lead details for potential enrollment</p>
                </div>
            </div>
            <div class="pg-acts">
                <a href="<?= APP_URL ?>/dash/front-desk/inquiries" class="btn bt">
                    <i class="fa-solid fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <div style="max-width: 800px; margin: 0 auto;">
            <div class="card" style="padding: 30px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
                <form id="inquiryForm" onsubmit="saveInquiry(event)">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                        <!-- Full Name -->
                        <div class="form-group">
                            <label class="fl">Full Name <span style="color:var(--red);">*</span></label>
                            <input type="text" name="full_name" class="fi" placeholder="e.g. Rajesh Hamal" required>
                        </div>

                        <!-- Phone Number -->
                        <div class="form-group">
                            <label class="fl">Phone Number <span style="color:var(--red);">*</span></label>
                            <input type="tel" name="phone" class="fi" placeholder="e.g. 98XXXXXXXX" required pattern="[0-9]{10}">
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label class="fl">Email Address</label>
                            <input type="email" name="email" class="fi" placeholder="e.g. name@example.com">
                        </div>

                        <!-- Source -->
                        <div class="form-group">
                            <label class="fl">Source <span style="color:var(--red);">*</span></label>
                            <select name="source" class="fi" required>
                                <option value="walk_in">Walk-in</option>
                                <option value="phone">Phone Call</option>
                                <option value="facebook">Facebook</option>
                                <option value="website">Website</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Interested Course -->
                        <div class="form-group">
                            <label class="fl">Interested Course <span style="color:var(--red);">*</span></label>
                            <select name="course_id" class="fi" required>
                                <option value="">Select a course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Preferred Time -->
                        <div class="form-group">
                            <label class="fl">Preferred Time</label>
                            <select name="preferred_time" class="fi">
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                                <option value="evening">Evening</option>
                            </select>
                        </div>
                    </div>

                    <!-- Notes / Inquiry Details -->
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label class="fl">Inquiry Details / Notes</label>
                        <textarea name="notes" class="fi" style="height: 100px; resize: none;" placeholder="Enter any specific requirements or notes here..."></textarea>
                    </div>

                    <div style="display:flex; justify-content: flex-end; gap: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                        <button type="reset" class="btn bt">Clear Form</button>
                        <button type="submit" class="btn" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED); color:#fff;" id="submitBtn">
                            <i class="fa-solid fa-paper-plane"></i> Save Inquiry
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="responseMsg" style="margin-top: 20px; display: none;"></div>
        </div>
    </div>
<style>
.fl { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px; }
.fi { width:100%; padding:12px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; font-family: inherit; }
.fi:focus { border-color:#8B5CF6; box-shadow:0 0 0 3px rgba(139, 92, 246, 0.1); }
.btn { padding:12px 24px; border-radius:12px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:10px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; border-color:#cbd5e1; }
</style>

<script>
async function saveInquiry(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('submitBtn');
    const msg = document.getElementById('responseMsg');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    data.action = 'create';

    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/inquiries', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await res.json();
        
        if (result.success) {
            msg.innerHTML = `<div style="padding:16px; background:#DCFCE7; color:#166534; border-radius:12px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-check-circle" style="font-size:20px;"></i>
                <div>
                    <div style="font-weight:700;">Inquiry Saved Successfully!</div>
                    <div style="font-size:13px; opacity:0.9;">The inquiry has been recorded and is now in the pipeline.</div>
                </div>
            </div>`;
            msg.style.display = 'block';
            form.reset();
            
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = '<?= APP_URL ?>/dash/front-desk/inquiries';
            }, 2000);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        msg.innerHTML = `<div style="padding:16px; background:#FEE2E2; color:#991B1B; border-radius:12px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-circle-xmark" style="font-size:20px;"></i>
            <div>
                <div style="font-weight:700;">Failed to Save Inquiry</div>
                <div style="font-size:13px; opacity:0.9;">${error.message}</div>
            </div>
        </div>`;
        msg.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Save Inquiry';
    }
}
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
