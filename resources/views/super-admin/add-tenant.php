<?php
/**
 * Hamro ERP — Add New Tenant (Multi-Step)
 * Refactored to match Super Admin layout and design system.
 */

require_once __DIR__ . '/../../../config/config.php';
require_once VIEWS_PATH . '/layouts/header_1.php';

$pageTitle = 'Add New Institute';
$activePage = 'tenant-management.php'; // Highlighting the management section
?>

<?php renderSuperAdminHeader(); renderSidebar($activePage); ?>

<main class="main" id="mainContent">
    <div class="pg fu">
        <!-- Breadcrumbs -->
        <div class="pg-head" style="border:none; margin-bottom:0; padding-bottom:0;">
            <div class="pg-left">
                <div class="breadcrumb">
                    <a href="index.php">Dashboard</a>
                    <i class="fa fa-chevron-right" style="font-size:9px;"></i>
                    <a href="tenant-management.php">Tenant Management</a>
                    <i class="fa fa-chevron-right" style="font-size:9px;"></i>
                    <span>Add New Institute</span>
                </div>
            </div>
        </div>

        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico ic-t"><i class="fa-solid fa-building-circle-plus"></i></div>
                <div>
                    <div class="pg-title">Register New Institute</div>
                    <div class="pg-sub">Create a new tenant account via our secure multi-step wizard.</div>
                </div>
            </div>
        </div>

        <!-- MULTI-STEP WIZARD -->
        <div class="card" style="max-width: 850px; margin: 0 auto; padding: 0; overflow: hidden;">
            
            <!-- STEP INDICATOR -->
            <div class="stepper-head" style="display: flex; background: #f8fafc; border-bottom: 1px solid var(--cb); padding: 20px 30px;">
                <div class="step-item active" id="stepInd1" style="flex: 1; display: flex; align-items: center; gap: 12px; transition: 0.3s;">
                    <div class="step-num" style="width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 2px solid var(--cb); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: var(--tl);">1</div>
                    <div style="font-size: 13px; font-weight: 700; color: var(--tb);">Basic Identity</div>
                </div>
                <div class="step-line" style="width: 40px; height: 2px; background: var(--cb); align-self: center; margin: 0 15px;"></div>
                <div class="step-item" id="stepInd2" style="flex: 1; display: flex; align-items: center; gap: 12px; opacity: 0.3; transition: 0.3s;">
                    <div class="step-num" style="width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 2px solid var(--cb); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: var(--tl);">2</div>
                    <div style="font-size: 13px; font-weight: 700; color: var(--tb);">Technical Setup</div>
                </div>
                <div class="step-line" style="width: 40px; height: 2px; background: var(--cb); align-self: center; margin: 0 15px;"></div>
                <div class="step-item" id="stepInd3" style="flex: 1; display: flex; align-items: center; gap: 12px; opacity: 0.3; transition: 0.3s;">
                    <div class="step-num" style="width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 2px solid var(--cb); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: var(--tl);">3</div>
                    <div style="font-size: 13px; font-weight: 700; color: var(--tb);">Business & Billing</div>
                </div>
            </div>

            <div style="padding: 40px;">
                
                <!-- STEP 1: BASIC IDENTITY -->
                <div class="form-step" id="formStep1">
                    <div class="ct" style="margin-top: 0; margin-bottom:20px;">Institute Basic Details (English & Nepali)</div>
                    <div class="g2" style="margin-bottom:20px;">
                        <div class="form-row">
                            <label class="form-lbl">Institute Name (English) <span style="color:var(--red);">*</span></label>
                            <input class="form-inp" type="text" placeholder="e.g. Everest Loksewa Classes" id="instName">
                        </div>
                        <div class="form-row">
                            <label class="form-lbl">संसद्को नाम (Nepali) <span style="color:var(--red);">*</span></label>
                            <input class="form-inp" type="text" placeholder="उदा: एभरेष्ट लोकसेवा क्लास" id="instNameNp">
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:20px;">
                        <label class="form-lbl">Institute Logo</label>
                        <div style="display:flex; align-items:center; gap:15px; padding:15px; border:2px dashed var(--cb); border-radius:10px; background:var(--bg);">
                            <div style="width:64px; height:64px; background:var(--cb); border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--tl);"><i class="fa fa-image" style="font-size:28px;"></i></div>
                            <div style="flex:1;">
                                <input type="file" id="instLogo" style="font-size:12px; display:block; margin-bottom:4px;">
                                <div style="font-size:11px; color:var(--tl);">Upload PNG/JPG, Max 2MB. Recommended: 512x512px.</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:20px;">
                        <label class="form-lbl">Full Office Address <span style="color:var(--red);">*</span></label>
                        <input class="form-inp" type="text" placeholder="e.g. New Baneshwor, Kathmandu, Nepal" id="instAddress">
                    </div>

                    <div class="g2">
                        <div class="form-row">
                            <label class="form-lbl">Official Contact Number <span style="color:var(--red);">*</span></label>
                            <input class="form-inp" type="tel" placeholder="+977-01-XXXXXXX" id="instPhone">
                        </div>
                        <div class="form-row">
                            <label class="form-lbl">Official Email Address <span style="color:var(--red);">*</span></label>
                            <input class="form-inp" type="email" placeholder="info@institute.com" id="instEmail">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: TECHNICAL SETUP -->
                <div class="form-step" id="formStep2" style="display: none;">
                    <div class="ct" style="margin-top: 0; margin-bottom:20px;">Platform Requirements & Admin Access</div>
                    
                    <div class="form-row" style="margin-bottom:20px;">
                        <label class="form-lbl">Subdomain Name <span style="color:var(--red);">*</span></label>
                        <div style="position: relative;">
                            <input class="form-inp" type="text" placeholder="everest" id="subdomainInp" oninput="previewSubdomain()" style="padding-right: 140px;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; color: var(--tl); font-weight: 600;">.hamrolabs.com.np</span>
                        </div>
                        <div class="form-hint" id="subdomainPreview" style="margin-top:8px; font-weight:600; color:var(--sa-primary); font-size:12px;">preview: everest.hamrolabs.com.np</div>
                    </div>

                    <div class="g2" style="margin-bottom:20px;">
                        <div class="form-row">
                            <label class="form-lbl">Primary Admin Name <span style="color:var(--red);">*</span></label>
                            <input class="form-inp" type="text" placeholder="e.g. Anil Shrestha" id="adminName">
                        </div>
                        <div class="form-row">
                            <label class="form-lbl">Admin Phone Number <span style="color:var(--red);">*</span></label>
                            <input class="form-inp" type="tel" placeholder="98XXXXXXXX" id="adminPhone">
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:20px;">
                        <label class="form-lbl">Admin Login Email <span style="color:var(--red);">*</span></label>
                        <input class="form-inp" type="email" placeholder="admin@institute.com" id="adminEmail">
                    </div>

                    <div class="g2">
                        <div class="form-row">
                            <label class="form-lbl">Account Password <span style="color:var(--red);">*</span></label>
                            <input class="form-inp" type="password" placeholder="Min 8 characters" id="adminPass">
                        </div>
                        <div class="form-row">
                            <label class="form-lbl">Theme Primary Color (Optional)</label>
                            <div style="display:flex; gap:12px; align-items:center;">
                                <input class="form-inp" type="color" value="#009E7E" style="width:56px; padding:4px; height:42px; cursor:pointer;" id="themeColor">
                                <span style="font-size:12px; color:var(--tl);">Select brand color</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: BUSINESS & BILLING -->
                <div class="form-step" id="formStep3" style="display: none;">
                    <div class="ct" style="margin-top: 0; margin-bottom:20px;">Subscription, PAN & Payment Information</div>
                    
                    <div class="g2" style="margin-bottom:20px;">
                        <div class="form-row">
                            <label class="form-lbl">Selected Subscription Plan <span style="color:var(--red);">*</span></label>
                            <select class="form-inp" id="billingPlan" style="appearance: auto;">
                                <option value="starter">Starter (Up to 150 students)</option>
                                <option value="growth">Growth (Up to 500 students)</option>
                                <option value="professional">Professional (Up to 1,500 students)</option>
                                <option value="enterprise">Enterprise (Unlimited)</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label class="form-lbl">Billing Cycle <span style="color:var(--red);">*</span></label>
                            <select class="form-inp" id="billingCycle" style="appearance: auto;">
                                <option value="monthly">Monthly Billing</option>
                                <option value="yearly">Yearly Billing (Save 20%)</option>
                            </select>
                        </div>
                    </div>

                    <div class="g2" style="margin-bottom:20px;">
                        <div class="form-row">
                            <label class="form-lbl">Government PAN Number (Optional)</label>
                            <input class="form-inp" type="text" placeholder="9-digit PAN" id="panNumber">
                        </div>
                        <div class="form-row">
                            <label class="form-lbl">Official Invoice Name <span style="color:var(--red);">*</span></label>
                            <input class="form-inp" type="text" placeholder="e.g. Everest Education Pvt. Ltd." id="invoiceName">
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom:20px;">
                        <label class="form-lbl">Initial Payment Status <span style="color:var(--red);">*</span></label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                            <label class="type-opt">
                                <input type="radio" name="pay_status" value="paid"> 
                                <span>Payment Collected</span>
                            </label>
                            <label class="type-opt active">
                                <input type="radio" name="pay_status" value="pending" checked> 
                                <span>Pending / In-Trial</span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-top:20px; padding:16px; background:var(--sa-primary-lt); border-radius:10px; border:1px solid var(--sa-primary-h); font-size:12px; color:var(--sa-primary-d); display:flex; gap:10px; align-items:center;">
                        <i class="fa fa-info-circle" style="font-size:16px;"></i> 
                        <span>Once finalized, the system will automatically provision the institute database and send login credentials to the admin email.</span>
                    </div>
                </div>

                <!-- NAV BUTTONS -->
                <div style="margin-top: 40px; padding-top: 25px; border-top: 1px solid var(--cb); display: flex; justify-content: space-between; align-items:center;">
                    <button class="btn bs" id="btnPrev" style="display: none;" onclick="changeStep(-1)">
                        <i class="fa-solid fa-arrow-left"></i> Previous
                    </button>
                    <div style="flex: 1;"></div>
                    <div style="display:flex; gap:12px;">
                        <a href="tenant-management.php" class="btn bs">Discard</a>
                        <button class="btn bt" id="btnNext" onclick="changeStep(1)">
                            Next Step <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button class="btn bt" id="btnSubmit" onclick="saveInstitute()" style="display: none;">
                            <i class="fa-solid fa-check-double"></i> Finalize & Register
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<style>
    /* Wizard specific styles */
    .step-item { opacity: 0.4; }
    .step-item.active { opacity: 1 !important; }
    .step-item.active .step-num { 
        border-color: var(--sa-primary) !important; 
        background: var(--sa-primary) !important; 
        color: #fff !important; 
        box-shadow: 0 0 0 4px var(--sa-primary-lt);
    }
    .step-item.completed .step-num { 
        background: var(--sa-primary) !important; 
        border-color: var(--sa-primary) !important; 
        color: #fff !important; 
    }
    
    .type-opt {
        border: 1px solid var(--cb); 
        padding: 14px; 
        border-radius: 12px; 
        cursor: pointer; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        font-size: 13px; 
        font-weight: 600;
        transition: 0.2s;
    }
    .type-opt:hover {
        background: var(--bg);
    }
    .type-opt.active, .type-opt:has(input:checked) {
        border-color: var(--sa-primary) !important;
        background: var(--sa-primary-lt);
        color: var(--sa-primary) !important;
    }

    .form-inp.error {
        border-color: var(--red) !important;
        background-color: #fff5f5 !important;
    }
    .error-msg {
        color: var(--red);
        font-size: 11px;
        font-weight: 600;
        margin-top: 4px;
        display: block;
    }
</style>

<script>
    let currentStep = 1;

    function changeStep(delta) {
        // Validation check for current step
        if (delta === 1 && !validateStep(currentStep)) return;

        // Hide current step
        document.getElementById(`formStep${currentStep}`).style.display = 'none';
        document.getElementById(`stepInd${currentStep}`).classList.remove('active');
        document.getElementById(`stepInd${currentStep}`).classList.add('completed');

        // Update step index
        currentStep += delta;

        // Show new step
        document.getElementById(`formStep${currentStep}`).style.display = 'block';
        document.getElementById(`stepInd${currentStep}`).classList.add('active');
        document.getElementById(`stepInd${currentStep}`).classList.remove('completed');

        // Update buttons
        document.getElementById('btnPrev').style.display = currentStep === 1 ? 'none' : 'flex';
        document.getElementById('btnNext').style.display = currentStep === 3 ? 'none' : 'flex';
        document.getElementById('btnSubmit').style.display = currentStep === 3 ? 'flex' : 'none';
    }

    function validateStep(step) {
        // Basic validation logic
        const currentStepEl = document.getElementById(`formStep${step}`);
        const requiredFields = currentStepEl.querySelectorAll('[required]');
        let isValid = true;

        // Since we don't have many 'required' attributes yet, we'll just return true for now
        // or add them as needed.
        return true; 
    }

    function previewSubdomain() {
        const inp = document.getElementById('subdomainInp').value.toLowerCase().replace(/[^a-z0-0]/g, '');
        document.getElementById('subdomainInp').value = inp;
        document.getElementById('subdomainPreview').textContent = `preview: ${inp || '... '}.hamrolabs.com.np`;
    }

    function saveInstitute() {
        const formData = new FormData();
        formData.append('name', document.getElementById('instName').value);
        formData.append('nepaliName', document.getElementById('instNameNp').value);
        formData.append('subdomain', document.getElementById('subdomainInp').value);
        formData.append('address', document.getElementById('instAddress').value);
        formData.append('phone', document.getElementById('instPhone').value);
        formData.append('email', document.getElementById('instEmail').value);
        
        formData.append('adminName', document.getElementById('adminName').value);
        formData.append('adminEmail', document.getElementById('adminEmail').value);
        formData.append('adminPhone', document.getElementById('adminPhone').value);
        formData.append('adminPass', document.getElementById('adminPass').value);
        
        formData.append('plan', document.getElementById('billingPlan').value);
        formData.append('brandColor', document.getElementById('themeColor').value);
        formData.append('tagline', 'Education evolved.'); // Static or from an input if available
        
        const payStatus = document.querySelector('input[name="pay_status"]:checked').value;
        formData.append('status', payStatus === 'paid' ? 'active' : 'trial');

        SuperAdmin.confirmAction(
            "Finalize Registration?",
            "This will create the institute account and setup the admin login.",
            "Yes, Register Now"
        ).then((result) => {
            if (result.isConfirmed) {
                SuperAdmin.showNotification("Registering institute...", "info");
                
                fetch(window.APP_URL + '/api/super-admin/tenants/save', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': window.CSRF_TOKEN
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        SuperAdmin.showNotification(data.message, "success");
                        setTimeout(() => {
                            SuperAdmin.goNav('tenants');
                        }, 1500);
                    } else {
                        SuperAdmin.showNotification(data.message, "error");
                    }
                })
                .catch(err => {
                    SuperAdmin.showNotification("Network error or server failed.", "error");
                    console.error(err);
                });
            }
        });
    }

    // Handle radio button visual state
    document.querySelectorAll('input[name="pay_status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.type-opt').forEach(opt => opt.classList.remove('active'));
            if(this.checked) this.closest('.type-opt').classList.add('active');
        });
    });
</script>

<?php include 'footer.php'; ?>
