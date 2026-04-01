<?php
/**
 * Feedback Module - Functional Form
 * File: resources/views/admin/feedback.php
 */
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$isSPA = isset($_GET['spa']) && $_GET['spa'] === 'true';

if (!$isSPA) {
    $pageTitle = "Submit Feedback";
    include VIEWS_PATH . '/layouts/header.php';
    include __DIR__ . '/layouts/sidebar.php';
    ?>
    <div class="main">
        <?php include __DIR__ . '/layouts/header.php'; ?>
        <div class="content" id="mainContent">
<?php } ?>

    <div class="pg fu">
        <div class="support-wrapper">
            <div class="support-hero" style="background: linear-gradient(135deg, #00B894 0%, #009E7E 100%);">
                <h1><i class="fa-regular fa-paper-plane"></i> Help Us Improve</h1>
                <p>Found a bug? Have a suggestion? We value your input to make iSoftro ERP perfect.</p>
            </div>

            <div class="feedback-container" style="max-width: 800px; margin: 0 auto;">
                <form id="feedbackForm" class="card" style="padding: 30px; border-radius: 16px; border: 1px solid var(--card-border); background: #fff; box-shadow: var(--shadow-md);">
                    <div class="g2" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label>Module / Category</label>
                            <select name="module" class="form-control" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <option value="">Select Module</option>
                                <option value="dashboard">Dashboard</option>
                                <option value="students">Students</option>
                                <option value="staff">Staff / HR</option>
                                <option value="fees">Fees & Payments</option>
                                <option value="exams">Exams & Results</option>
                                <option value="attendance">Attendance</option>
                                <option value="lms">LMS</option>
                                <option value="settings">Settings</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Context / Page</label>
                            <input type="text" name="page" id="feedbackPage" placeholder="e.g. Student List" class="form-control" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Describe the Problem / Suggestion</label>
                        <textarea name="problem" class="form-control" required placeholder="Please provide as much detail as possible..." style="width: 100%; min-height: 150px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; resize: vertical;"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label>Attach Screenshot (Optional)</label>
                        <div class="screenshot-upload" style="border: 2px dashed #cbd5e1; padding: 30px; border-radius: 12px; text-align: center; cursor: pointer; transition: 0.2s;" onclick="document.getElementById('feedbackScreenshot').click()">
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size: 24px; color: var(--text-light); margin-bottom: 8px;"></i>
                            <p id="screenshotLabel" style="color: var(--text-body); font-size: 0.9rem;">Click to upload or drag and drop</p>
                            <input type="file" id="feedbackScreenshot" name="screenshot" accept="image/*" style="display: none;" onchange="handleScreenshotSelect(this)">
                        </div>
                        <div id="screenshotPreview" style="display: none; margin-top: 15px; position: relative;">
                            <img id="previewImg" src="" style="width: 100%; max-height: 200px; object-fit: contain; border-radius: 8px;">
                            <button type="button" onclick="removeScreenshot()" style="position: absolute; top: -10px; right: -10px; background: #ef4444; color: #fff; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer;">&times;</button>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" onclick="goNav('dashboard')" style="background: #f1f5f9; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; color: #334155; cursor: pointer;">Cancel</button>
                        <button type="submit" id="submitFeedbackBtn" style="background: var(--green); border: none; padding: 12px 32px; border-radius: 10px; font-weight: 700; color: #fff; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <span>Submit Feedback</span>
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function handleScreenshotSelect(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('screenshotPreview').style.display = 'block';
                    document.getElementById('screenshotLabel').textContent = file.name;
                }
                reader.readAsDataURL(file);
            }
        }

        function removeScreenshot() {
            document.getElementById('feedbackScreenshot').value = '';
            document.getElementById('screenshotPreview').style.display = 'none';
            document.getElementById('screenshotLabel').textContent = 'Click to upload or drag and drop';
        }

        // Set current page context if available in URL
        const urlParams = new URLSearchParams(window.location.search);
        const refPage = urlParams.get('ref_page');
        if (refPage) {
            document.getElementById('feedbackPage').value = refPage;
        }

        document.getElementById('feedbackForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitFeedbackBtn');
            const originalContent = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Submitting...';

            const formData = new FormData(this);
            
            try {
                const response = await fetch(window.APP_URL + '/api/admin/feedback/submit', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        title: 'Thank You!',
                        text: 'Your feedback has been submitted successfully. We will look into it.',
                        icon: 'success',
                        confirmButtonColor: '#00B894'
                    }).then(() => {
                        goNav('dashboard');
                    });
                } else {
                    throw new Error(data.message || 'Submission failed');
                }
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        });
    </script>

<?php if (!$isSPA) { ?>
        </div>
    </div>
    <script src="<?php echo APP_URL; ?>/assets/js/ia-core.js"></script>
    </body>
    </html>
<?php } ?>
