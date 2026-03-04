/**
 * test-nexus-admission.js
 * Automated verification script for Nexus Admission Flow.
 */

window.testNexusAdmission = async () => {
    console.log("🚀 Starting Nexus Admission Automated Test...");
    
    try {
        // 1. Ensure we are on the right page
        if (typeof window.renderAddStudentFormV2 !== 'function') {
            throw new Error("Nexus Admission module not loaded!");
        }
        
        await window.renderAddStudentFormV2();
        const form = document.getElementById('nexusAddStudentForm');
        if (!form) throw new Error("Form not found!");

        console.log("✅ Page Rendered. Filling data...");

        // 2. Fill Identity
        form.querySelector('[name="full_name"]').value = "Test Student " + Math.floor(Math.random() * 1000);
        form.querySelector('[name="email"]').value = `test.student.${Date.now()}@example.com`;
        form.querySelector('[name="contact_number"]').value = "9841" + Math.floor(100000 + Math.random() * 900000);
        form.querySelector('[name="gender"]').value = "male";
        form.querySelector('[name="dob_ad"]').value = "2005-05-15";
        form.querySelector('[name="dob_ad"]').dispatchEvent(new Event('change')); // Trigger BS sync

        // 3. Select Academic (Simulate selection)
        // Note: SearchSelects need data-id and internal state, but for basic test we can set hidden inputs
        form.querySelector('[name="course_id"]').value = "1"; // Assuming id 1 exists
        form.querySelector('[name="batch_id"]').value = "1"; // Assuming id 1 exists
        
        // 4. Guardian
        form.querySelector('[name="father_name"]').value = "Test Guardian Name";
        
        // 5. Address
        form.querySelector('[name="permanent_province"]').value = "Bagmati Province";
        form.querySelector('[name="permanent_district"]').value = "Kathmandu";
        form.querySelector('[name="permanent_municipality"]').value = "Kathmandu Metro";
        document.getElementById('same_as_permanent').checked = true;
        document.getElementById('same_as_permanent').dispatchEvent(new Event('change'));

        console.log("✅ Data Filled. Submitting...");

        // 6. Submit
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.click();

        // 7. Check result (Wait for Swal)
        setTimeout(() => {
            const swalTitle = document.querySelector('.swal2-title');
            if (swalTitle && (swalTitle.innerText.includes('Success') || swalTitle.innerText.includes('Complete'))) {
                console.log("🎊 TEST PASSED: Student Enrolled Successfully!");
                Swal.fire("Test Passed!", "Nexus Admission flow is verified.", "success");
            } else {
                console.error("❌ TEST FAILED: Result not detected.");
            }
        }, 3000);

    } catch (err) {
        console.error("❌ TEST FAILED:", err.message);
    }
};

console.log("💡 Nexus Test Script Loaded. Run 'testNexusAdmission()' in console to start.");
