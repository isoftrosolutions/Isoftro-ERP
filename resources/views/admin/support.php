<?php
/**
 * Support & Help Center - Institute Admin
 * Contains: YouTube tutorials, contact details, FAQ with 50 articles
 * 
 * SPA-enabled page with external CSS and JS support
 */
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$pageTitle = "Support & Help Center";
$roleCSS = "ia-dashboard-new.css";
$wrapperClass = "app-layout";
$user = getCurrentUser();
$tenantName = $_SESSION['tenant_name'] ?? 'Institute';
$activePage = 'support';

$isSPA = isset($_GET['spa']) && $_GET['spa'] === 'true';

// Generate version for cache busting
$assetVersion = defined('ASSET_VERSION') ? ASSET_VERSION : time();

if (!$isSPA) {
    include VIEWS_PATH . '/layouts/header.php';
    include __DIR__ . '/layouts/sidebar.php';
    ?>

<div class="main">
    <?php include __DIR__ . '/layouts/header.php'; ?>

    <div class="content" id="mainContent">
<?php } else { ?>
        <!-- SPA mode: content only -->
<?php } ?>

        <!-- Support content (CSS loaded in header) -->

        <div class="support-wrapper" id="supportContent">
            <!-- Hero Header -->
            <div class="support-hero">
                <h1><i class="fa-solid fa-headset"></i> Support & Help Center</h1>
                <p>Find answers, watch tutorials, or get in touch with our support team. We're here to help you make the most of Hamro ERP.</p>
            </div>

            <!-- Quick Help Cards -->
            <div class="quick-help-grid">
                <a href="#tutorials" class="quick-help-card youtube">
                    <i class="fa-brands fa-youtube"></i>
                    <h3>Video Tutorials</h3>
                </a>
                <a href="#faq" class="quick-help-card">
                    <i class="fa-solid fa-circle-question"></i>
                    <h3>FAQs</h3>
                </a>
                <a href="#contact" class="quick-help-card">
                    <i class="fa-solid fa-phone"></i>
                    <h3>Contact Us</h3>
                </a>
                <a href="https://wa.me/9779800000000" target="_blank" class="quick-help-card whatsapp">
                    <i class="fa-brands fa-whatsapp"></i>
                    <h3>WhatsApp Chat</h3>
                </a>
            </div>

            <!-- Support Cards Grid -->
            <div class="support-cards-grid">
                <!-- YouTube Tutorials -->
                <div class="support-card" id="tutorials">
                    <h2><i class="fa-brands fa-youtube"></i> Video Tutorials</h2>
                    <div class="youtube-list">
                        <a href="https://youtube.com/@hamrolabs" target="_blank" class="youtube-item">
                            <i class="fa-brands fa-youtube"></i>
                            <div>
                                <strong>HamroLabs Official Channel</strong>
                                <span>Subscribe for latest updates</span>
                            </div>
                        </a>
                        <a href="https://youtube.com/watch?v=intro-erp" target="_blank" class="youtube-item">
                            <i class="fa-brands fa-youtube"></i>
                            <div>
                                <strong>ERP Introduction</strong>
                                <span>Getting started guide</span>
                            </div>
                        </a>
                        <a href="https://youtube.com/watch?v=student-management" target="_blank" class="youtube-item">
                            <i class="fa-brands fa-youtube"></i>
                            <div>
                                <strong>Student Management</strong>
                                <span>Complete walkthrough</span>
                            </div>
                        </a>
                        <a href="https://youtube.com/watch?v=fee-management" target="_blank" class="youtube-item">
                            <i class="fa-brands fa-youtube"></i>
                            <div>
                                <strong>Fee Management</strong>
                                <span>Collection & reports</span>
                            </div>
                        </a>
                        <a href="https://youtube.com/watch?v=attendance-system" target="_blank" class="youtube-item">
                            <i class="fa-brands fa-youtube"></i>
                            <div>
                                <strong>Attendance System</strong>
                                <span>Setup & daily operations</span>
                            </div>
                        </a>
                        <a href="https://youtube.com/watch?v=exam-results" target="_blank" class="youtube-item">
                            <i class="fa-brands fa-youtube"></i>
                            <div>
                                <strong>Exams & Results</strong>
                                <span>Management guide</span>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="support-card" id="contact">
                    <h2><i class="fa-solid fa-address-card"></i> Contact Us</h2>
                    <div class="contact-list">
                        <div class="contact-item">
                            <i class="fa-solid fa-phone"></i>
                            <div>
                                <div class="label">Support Hotline</div>
                                <a href="tel:+9779800000000">+977 980-000-0000</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-phone"></i>
                            <div>
                                <div class="label">Sales Inquiry</div>
                                <a href="tel:+9779800000001">+977 980-000-0001</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-envelope"></i>
                            <div>
                                <div class="label">Email Support</div>
                                <a href="mailto:support@hamrolabs.com">support@hamrolabs.com</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fa-brands fa-whatsapp"></i>
                            <div>
                                <div class="label">WhatsApp</div>
                                <a href="https://wa.me/9779800000000" target="_blank">+977 980-000-0000</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <div>
                                <div class="label">Office Address</div>
                                <strong>Kathmandu, Nepal</strong>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-clock"></i>
                            <div>
                                <div class="label">Working Hours</div>
                                <strong>Sun-Fri: 9:00 AM - 6:00 PM</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-wrapper" id="faq">
                <h2><i class="fa-solid fa-circle-question"></i> Frequently Asked Questions (50 Articles)</h2>
                
                <div class="faq-search-box">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="faqSearch" placeholder="Search for answers..." oninput="debouncedSearchFAQ()">
                </div>

                <div class="faq-container" id="faqContainer">
                    <!-- Getting Started -->
                    <div class="faq-item" data-category="getting-started">
                        <span class="faq-category">Getting Started</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            1. How do I log in to the ERP system for the first time?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Open your web browser and go to your institute's ERP URL</li>
                                <li>Enter your email address provided by the administrator</li>
                                <li>Enter your temporary password</li>
                                <li>Click the "Login" button</li>
                                <li>On first login, you'll be prompted to change your password</li>
                                <li>Create a strong password with at least 8 characters</li>
                                <li>Confirm the new password and save</li>
                            </ol>
                            <p><strong>Tip:</strong> If you forget your password, click "Forgot Password" on the login page.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="getting-started">
                        <span class="faq-category">Getting Started</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            2. How do I reset my password?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to the login page</li>
                                <li>Click on "Forgot Password" link below the login button</li>
                                <li>Enter your registered email address</li>
                                <li>Click "Send Reset Link"</li>
                                <li>Check your email inbox for the password reset link</li>
                                <li>Click the link in the email (valid for 1 hour)</li>
                                <li>Enter your new password twice</li>
                                <li>Click "Reset Password" to complete</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="getting-started">
                        <span class="faq-category">Getting Started</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            3. How do I update my profile information?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Click on your profile icon in the top right corner</li>
                                <li>Select "My Account" from the dropdown menu</li>
                                <li>Click on the "Edit Profile" button</li>
                                <li>Update your name, phone number, and other details</li>
                                <li>Click "Choose File" to upload a profile photo</li>
                                <li>Click "Save Changes" to update your information</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Student Management -->
                    <div class="faq-item" data-category="students">
                        <span class="faq-category">Student Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            4. How do I add a new student to the system?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Students" from the sidebar menu</li>
                                <li>Click the "+ Add Student" button</li>
                                <li>Fill in the student's personal information (name, DOB, gender)</li>
                                <li>Enter contact details (phone, email, address)</li>
                                <li>Select the batch/course the student is enrolling in</li>
                                <li>Upload student's photo (optional)</li>
                                <li>Enter parent's/guardian's information</li>
                                <li>Click "Save & Continue" to add the student</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="students">
                        <span class="faq-category">Student Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            5. How do I edit student information?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Students" in the sidebar</li>
                                <li>Use the search box to find the student</li>
                                <li>Click on the student's name to open their profile</li>
                                <li>Click the "Edit" button in the top right</li>
                                <li>Update the required information</li>
                                <li>Click "Save Changes" to update the record</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="students">
                        <span class="faq-category">Student Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            6. How do I promote students to the next grade?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Students" → "Bulk Actions"</li>
                                <li>Select "Promote Students" option</li>
                                <li>Choose the source batch/grade</li>
                                <li>Select students to promote (use checkboxes)</li>
                                <li>Choose the destination batch/grade</li>
                                <li>Set the promotion date</li>
                                <li>Click "Promote Selected Students"</li>
                                <li>Review and confirm the promotion</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="students">
                        <span class="faq-category">Student Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            7. How do I issue a student ID card?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Students" and find the student</li>
                                <li>Click on the student's profile</li>
                                <li>Click the "ID Card" button</li>
                                <li>Verify the information displayed</li>
                                <li>Select ID card template (if multiple available)</li>
                                <li>Click "Generate ID Card"</li>
                                <li>Preview the ID card</li>
                                <li>Click "Print" or "Download PDF"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Fee Management -->
                    <div class="faq-item" data-category="fees">
                        <span class="faq-category">Fee Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            8. How do I collect fees from a student?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Fees" → "Collect Fee" from the sidebar</li>
                                <li>Search for the student by name or ID</li>
                                <li>Select the student from the results</li>
                                <li>View the fee structure and pending amount</li>
                                <li>Enter the amount being paid</li>
                                <li>Select payment mode (Cash, Card, Bank Transfer, etc.)</li>
                                <li>Enter transaction reference (if applicable)</li>
                                <li>Click "Collect Fee" to complete the transaction</li>
                                <li>Print or email the receipt</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="fees">
                        <span class="faq-category">Fee Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            9. How do I set up fee structure for a batch?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Fees" → "Fee Structure"</li>
                                <li>Click "+ Create Fee Structure"</li>
                                <li>Select the batch/course</li>
                                <li>Enter the academic year</li>
                                <li>Add fee components (Tuition, Admission, Exam, etc.)</li>
                                <li>Enter amount for each component</li>
                                <li>Set due dates for each fee type</li>
                                <li>Add any applicable discounts</li>
                                <li>Click "Save Fee Structure"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="fees">
                        <span class="faq-category">Fee Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            10. How do I generate fee reports?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Fees" → "Reports"</li>
                                <li>Select report type (Collection, Pending, Summary)</li>
                                <li>Choose date range for the report</li>
                                <li>Select batch/course (optional)</li>
                                <li>Click "Generate Report"</li>
                                <li>View the report on screen</li>
                                <li>Click "Export" to download as Excel/PDF</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="fees">
                        <span class="faq-category">Fee Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            11. How do I apply a discount to a student's fee?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Students" and find the student</li>
                                <li>Open the student's profile</li>
                                <li>Click on "Fee Details" tab</li>
                                <li>Click "Apply Discount" button</li>
                                <li>Select discount type (Percentage or Fixed Amount)</li>
                                <li>Enter discount value</li>
                                <li>Select which fee components the discount applies to</li>
                                <li>Add remarks/reason for discount</li>
                                <li>Click "Apply Discount"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Attendance -->
                    <div class="faq-item" data-category="attendance">
                        <span class="faq-category">Attendance</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            12. How do I mark daily attendance?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Attendance" → "Daily Attendance"</li>
                                <li>Select the batch/class</li>
                                <li>Select the date (defaults to today)</li>
                                <li>View the list of all students in the batch</li>
                                <li>Mark Present (P), Absent (A), or Late (L) for each student</li>
                                <li>Use "Mark All Present" for quick marking</li>
                                <li>Add remarks for absent students (optional)</li>
                                <li>Click "Save Attendance" to record</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="attendance">
                        <span class="faq-category">Attendance</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            13. How do I view attendance reports?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Attendance" → "Reports"</li>
                                <li>Select report type (Daily, Monthly, Student-wise)</li>
                                <li>Choose date range</li>
                                <li>Select batch/student</li>
                                <li>Click "Generate Report"</li>
                                <li>View attendance statistics and percentages</li>
                                <li>Export as PDF or Excel if needed</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Exams -->
                    <div class="faq-item" data-category="exams">
                        <span class="faq-category">Exams & Results</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            14. How do I create a new exam?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Exams" → "Create Exam"</li>
                                <li>Enter exam name (e.g., "First Terminal Exam 2024")</li>
                                <li>Select exam type (Terminal, Final, Unit Test, etc.)</li>
                                <li>Choose the batch/classes</li>
                                <li>Set exam start and end dates</li>
                                <li>Add subjects and their exam dates/times</li>
                                <li>Enter maximum marks for each subject</li>
                                <li>Click "Create Exam" to save</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="exams">
                        <span class="faq-category">Exams & Results</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            15. How do I enter marks for students?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Exams" → "Enter Marks"</li>
                                <li>Select the exam from the dropdown</li>
                                <li>Choose the subject</li>
                                <li>Select the batch/class</li>
                                <li>Enter marks for each student</li>
                                <li>Enter practical marks if applicable</li>
                                <li>Add remarks for individual students if needed</li>
                                <li>Click "Save Marks"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="exams">
                        <span class="faq-category">Exams & Results</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            16. How do I generate report cards?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Exams" → "Report Cards"</li>
                                <li>Select the exam</li>
                                <li>Choose the batch/class</li>
                                <li>Click "Generate Report Cards"</li>
                                <li>Preview the report cards</li>
                                <li>Select students (or all) to generate</li>
                                <li>Choose report card template</li>
                                <li>Click "Print" or "Download PDF"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Batches & Courses -->
                    <div class="faq-item" data-category="batches">
                        <span class="faq-category">Batches & Courses</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            17. How do I create a new batch?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Batches" → "All Batches"</li>
                                <li>Click "+ Create Batch" button</li>
                                <li>Enter batch name (e.g., "Grade 10 - A")</li>
                                <li>Select the course/class</li>
                                <li>Set academic year</li>
                                <li>Set start and end dates</li>
                                <li>Select class teacher</li>
                                <li>Set maximum student capacity</li>
                                <li>Click "Create Batch"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="batches">
                        <span class="faq-category">Batches & Courses</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            18. How do I assign subjects to a batch?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Batches" → "Subject Allocation"</li>
                                <li>Select the batch</li>
                                <li>Click "Add Subject"</li>
                                <li>Select subject from the list</li>
                                <li>Assign teacher for the subject</li>
                                <li>Set number of periods per week</li>
                                <li>Add more subjects as needed</li>
                                <li>Click "Save Allocation"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Staff Management -->
                    <div class="faq-item" data-category="staff">
                        <span class="faq-category">Staff Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            19. How do I add a new teacher/staff member?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Staff" → "All Staff"</li>
                                <li>Click "+ Add Staff" button</li>
                                <li>Enter personal details (name, DOB, gender)</li>
                                <li>Add contact information</li>
                                <li>Select role (Teacher, Admin, Accountant, etc.)</li>
                                <li>Enter qualification and experience</li>
                                <li>Set salary details</li>
                                <li>Upload photo and documents</li>
                                <li>Click "Save Staff"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="staff">
                        <span class="faq-category">Staff Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            20. How do I assign a class teacher to a batch?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Batches" → "All Batches"</li>
                                <li>Click on the batch you want to assign a class teacher to</li>
                                <li>Click "Edit" or "Settings"</li>
                                <li>Find "Class Teacher" field</li>
                                <li>Select a teacher from the dropdown</li>
                                <li>Click "Save Changes"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Library -->
                    <div class="faq-item" data-category="library">
                        <span class="faq-category">Library</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            21. How do I add books to the library?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Library" → "Add Book"</li>
                                <li>Enter book details (title, author, ISBN, publisher)</li>
                                <li>Set quantity available</li>
                                <li>Add rack/shelf location</li>
                                <li>Click "Add Book" to save</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="library">
                        <span class="faq-category">Library</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            22. How do I issue a book to a student?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Library" → "Issue Book"</li>
                                <li>Search for the student</li>
                                <li>Select the book to issue</li>
                                <li>Set return due date</li>
                                <li>Click "Issue Book"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Timetable -->
                    <div class="faq-item" data-category="timetable">
                        <span class="faq-category">Timetable</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            23. How do I create a class timetable?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Timetable" → "Create Timetable"</li>
                                <li>Select the batch/class</li>
                                <li>Choose the day of the week</li>
                                <li>Add periods with subject and teacher</li>
                                <li>Set start and end time for each period</li>
                                <li>Click "Save Timetable"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="timetable">
                        <span class="faq-category">Timetable</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            24. How do I view teacher's timetable?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Timetable" → "Teacher Timetable"</li>
                                <li>Select the teacher from dropdown</li>
                                <li>View their weekly schedule</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Study Materials -->
                    <div class="faq-item" data-category="lms">
                        <span class="faq-category">Study Materials</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            25. How do I upload study materials for students?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Study Materials" → "Upload"</li>
                                <li>Select the batch/course</li>
                                <li>Choose subject</li>
                                <li>Upload file (PDF, Video, etc.)</li>
                                <li>Add title and description</li>
                                <li>Set visibility (students can access)</li>
                                <li>Click "Upload"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="lms">
                        <span class="faq-category">Study Materials</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            26. How do students access study materials?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>For Students:</strong>
                            <ol>
                                <li>Log in to student portal</li>
                                <li>Go to "Study Materials" or "LMS"</li>
                                <li>Select batch/course</li>
                                <li>Browse and download available materials</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Front Desk -->
                    <div class="faq-item" data-category="frontdesk">
                        <span class="faq-category">Front Desk</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            27. How do I manage visitor logs?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Front Desk" → "Visitor Log"</li>
                                <li>Click "New Visitor" button</li>
                                <li>Enter visitor details (name, phone, purpose)</li>
                                <li>Select person to meet</li>
                                <li>Click "Check In"</li>
                                <li>When visitor leaves, click "Check Out"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="frontdesk">
                        <span class="faq-category">Front Desk</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            28. How do I manage phone call logs?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Front Desk" → "Call Logs"</li>
                                <li>Click "Log Call" button</li>
                                <li>Enter caller information</li>
                                <li>Note the purpose of call</li>
                                <li>Select staff member the call is for</li>
                                <li>Mark as "Pending" or "Resolved"</li>
                                <li>Click "Save"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Settings -->
                    <div class="faq-item" data-category="settings">
                        <span class="faq-category">Settings</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            29. How do I configure institute profile?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Settings" → "Institute Profile"</li>
                                <li>Edit institute name, logo, address</li>
                                <li>Update contact information</li>
                                <li>Configure academic details</li>
                                <li>Click "Save Changes"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="settings">
                        <span class="faq-category">Settings</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            30. How do I set up email notifications?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Settings" → "Email Settings"</li>
                                <li>Configure SMTP server details</li>
                                <li>Set sender email and name</li>
                                <li>Test email configuration</li>
                                <li>Enable/disable notification types</li>
                                <li>Save settings</li>
                            </ol>
                        </div>
                    </div>

                    <!-- User Roles & Permissions -->
                    <div class="faq-item" data-category="security">
                        <span class="faq-category">Security</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            31. How do I manage user roles and permissions?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Settings" → "User Roles"</li>
                                <li>Create new role or edit existing</li>
                                <li>Assign permissions to role</li>
                                <li>Save role configuration</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="security">
                        <span class="faq-category">Security</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            32. How do I enable two-factor authentication?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Settings" → "Security"</li>
                                <li>Find "Two-Factor Authentication" option</li>
                                <li>Click "Enable"</li>
                                <li>Scan QR code with authenticator app</li>
                                <li>Enter verification code</li>
                                <li>Save backup codes securely</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Reports & Analytics -->
                    <div class="faq-item" data-category="reports">
                        <span class="faq-category">Reports</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            33. How do I generate student performance reports?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Reports" → "Student Performance"</li>
                                <li>Select batch/class</li>
                                <li>Choose exam or date range</li>
                                <li>Select metrics to include</li>
                                <li>Click "Generate Report"</li>
                                <li>Export as PDF or Excel</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="reports">
                        <span class="faq-category">Reports</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            34. How do I view financial summaries?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Fees" → "Reports"</li>
                                <li>Select "Financial Summary"</li>
                                <li>Choose date range</li>
                                <li>View income, expenses, and balance</li>
                                <li>Export report if needed</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Backup & Data -->
                    <div class="faq-item" data-category="backup">
                        <span class="faq-category">Backup & Data</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            35. How do I backup my data?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Settings" → "Backup"</li>
                                <li>Click "Create Backup"</li>
                                <li>Select what to include (full or partial)</li>
                                <li>Choose backup destination</li>
                                <li>Wait for backup to complete</li>
                                <li>Download backup file</li>
                            </ol>
                            <p><strong>Note:</strong> Regular automated backups are also performed by the system.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="backup">
                        <span class="faq-category">Backup & Data</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            36. How do I import student data from Excel?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Students" → "Import"</li>
                                <li>Download the template CSV/Excel file</li>
                                <li>Fill in student data following the template format</li>
                                <li>Upload the completed file</li>
                                <li>Map columns if needed</li>
                                <li>Click "Import"</li>
                                <li>Review and confirm import</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Mobile App -->
                    <div class="faq-item" data-category="mobile">
                        <span class="faq-category">Mobile App</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            37. Is there a mobile app for parents?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes! Hamro ERP offers mobile apps for:</p>
                            <ul>
                                <li><strong>Parents/Guardians:</strong> Available on Google Play Store and Apple App Store</li>
                                <li><strong>Students:</strong> Available on Google Play Store and Apple App Store</li>
                            </ul>
                            <p>Download "Hamro ERP" app and log in with your credentials to access student information, fees, attendance, and more.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="mobile">
                        <span class="faq-category">Mobile App</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            38. How do parents view their child's attendance on the app?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Open Hamro ERP app</li>
                                <li>Log in with parent credentials</li>
                                <li>Select child (if multiple)</li>
                                <li>Go to "Attendance" tab</li>
                                <li>View daily/monthly attendance</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="faq-item" data-category="notifications">
                        <span class="faq-category">Notifications</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            39. How do I send SMS notifications to parents?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Communications" → "SMS"</li>
                                <li>Click "Compose SMS"</li>
                                <li>Select recipients (all parents, specific batch, etc.)</li>
                                <li>Write your message</li>
                                <li>Click "Send"</li>
                            </ol>
                            <p><strong>Note:</strong> SMS credits are required. Contact admin to purchase credits.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="notifications">
                        <span class="faq-category">Notifications</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            40. How do I send email notifications?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Communications" → "Email"</li>
                                <li>Click "Compose Email"</li>
                                <li>Select recipients or import list</li>
                                <li>Choose email template (optional)</li>
                                <li>Edit subject and body</li>
                                <li>Click "Send"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Leave Management -->
                    <div class="faq-item" data-category="leave">
                        <span class="faq-category">Leave Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            41. How do I apply for leave?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Attendance" → "Leave Request"</li>
                                <li>Click "Apply for Leave"</li>
                                <li>Select leave type</li>
                                <li>Choose start and end dates</li>
                                <li>Enter reason for leave</li>
                                <li>Upload supporting document (if required)</li>
                                <li>Click "Submit Application"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="leave">
                        <span class="faq-category">Leave Management</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            42. How do I approve leave requests?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Attendance" → "Leave Requests"</li>
                                <li>View pending leave applications</li>
                                <li>Click on a request to view details</li>
                                <li>Review the reason and documents</li>
                                <li>Click "Approve" or "Reject"</li>
                                <li>Add remarks if needed</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Assignments -->
                    <div class="faq-item" data-category="assignments">
                        <span class="faq-category">Assignments</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            43. How do I create an assignment for students?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "Assignments" → "Create Assignment"</li>
                                <li>Select batch and subject</li>
                                <li>Enter assignment title and description</li>
                                <li>Set due date and marks</li>
                                <li>Upload files if needed</li>
                                <li>Click "Publish"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="assignments">
                        <span class="faq-category">Assignments</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            44. How do I check submitted assignments?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Assignments"</li>
                                <li>Click on the assignment</li>
                                <li>Go to "Submissions" tab</li>
                                <li>View list of submissions</li>
                                <li>Click on each to review</li>
                                <li>Add marks and feedback</li>
                                <li>Click "Submit Grades"</li>
                            </ol>
                        </div>
                    </div>

                    <!-- ID Cards -->
                    <div class="faq-item" data-category="idcards">
                        <span class="faq-category">ID Cards</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            45. How do I generate bulk ID cards?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Go to "ID Cards" → "Bulk Generate"</li>
                                <li>Select batch/course</li>
                                <li>Choose ID card template</li>
                                <li>Preview all cards</li>
                                <li>Click "Generate All"</li>
                                <li>Download as PDF or print directly</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Online Payments -->
                    <div class="faq-item" data-category="payments">
                        <span class="faq-category">Online Payments</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            46. How do I enable online fee payment?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Contact Hamro Labs to enable payment gateway</li>
                                <li>Configure payment settings in "Fees" → "Settings"</li>
                                <li>Connect bank account details</li>
                                <li>Enable payment methods (eSewa, Khalti, Bank Transfer)</li>
                                <li>Test with a small transaction</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item" data-category="payments">
                        <span class="faq-category">Online Payments</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            47. How do parents pay fees online?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>For Parents:</strong>
                            <ol>
                                <li>Log in to parent portal or app</li>
                                <li>Go to "Fees" → "Pay Online"</li>
                                <li>View pending fees</li>
                                <li>Click "Pay Now"</li>
                                <li>Choose payment method</li>
                                <li>Complete payment</li>
                                <li>Receive confirmation and receipt</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Academic Calendar -->
                    <div class="faq-item" data-category="calendar">
                        <span class="faq-category">Academic Calendar</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            48. How do I manage the academic calendar?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Academic Calendar"</li>
                                <li>Click "Add Event"</li>
                                <li>Enter event title, date, description</li>
                                <li>Select event type (holiday, exam, activity)</li>
                                <li>Set visibility (all, staff only, students)</li>
                                <li>Save event</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Troubleshooting -->
                    <div class="faq-item" data-category="troubleshooting">
                        <span class="faq-category">Troubleshooting</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            49. Why am I unable to log in?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p><strong>Possible solutions:</strong></p>
                            <ol>
                                <li>Check your internet connection</li>
                                <li>Verify your email and password are correct</li>
                                <li>Clear browser cache and cookies</li>
                                <li>Try a different browser</li>
                                <li>Use "Forgot Password" to reset</li>
                                <li>Contact administrator if issue persists</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Inquiries -->
                    <div class="faq-item" data-category="inquiries">
                        <span class="faq-category">Inquiries</span>
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            50. How do I convert an inquiry to admission?
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <strong>Step-by-step guide:</strong>
                            <ol>
                                <li>Navigate to "Inquiries" → "All Inquiries"</li>
                                <li>Find and click on the inquiry</li>
                                <li>Click "Convert to Admission"</li>
                                <li>System will redirect to student admission form</li>
                                <li>Pre-filled data from inquiry will appear</li>
                                <li>Complete remaining fields</li>
                                <li>Collect admission fee</li>
                                <li>Click "Complete Admission"</li>
                            </ol>
                        </div>
                    </div>

                </div>
            </div>
        <?php if (!$isSPA): ?>
    </div>
</div>

<?php $v = time(); ?>
<script src="<?php echo APP_URL; ?>/public/assets/js/pwa-handler.js?v=<?php echo $v; ?>"></script>
</body>
</html>
<?php endif; ?>
        <script src="<?php echo APP_URL; ?>/public/assets/js/ia-support.js?v=<?php echo $assetVersion; ?>"></script>

        <script>
            // Initialize support page when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                // Legacy function aliases for backward compatibility
                window.toggleFAQ = window.toggleFAQ || function(element) {
                    const answer = element.nextElementSibling;
                    const isActive = element.classList.contains('active');
                    
                    // Close all others
                    document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));
                    document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('active'));
                    
                    // Toggle current
                    if (!isActive) {
                        element.classList.add('active');
                        answer.classList.add('active');
                    }
                };

                window.searchFAQ = window.searchFAQ || function() {
                    const searchTerm = document.getElementById('faqSearch').value.toLowerCase();
                    const faqItems = document.querySelectorAll('.faq-item');
                    
                    faqItems.forEach(item => {
                        const question = item.querySelector('.faq-question').textContent.toLowerCase();
                        const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                        
                        if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                };

                window.debouncedSearchFAQ = window.debouncedSearchFAQ || function() {
                    var timeout;
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        searchFAQ();
                    }, 300);
                };

                window.scrollToSection = window.scrollToSection || function(id) {
                    document.getElementById(id).scrollIntoView({ behavior: 'smooth' });
                };

                // Initialize the SPA functionality
                if (typeof window.initSupportPage === 'function') {
                    window.initSupportPage();
                }
            });
        </script>
<?php if (!$isSPA): ?>
    </div>
</div>

<?php $v = time(); ?>
<script src="<?php echo APP_URL; ?>/public/assets/js/pwa-handler.js?v=<?php echo $v; ?>"></script>
</body>
</html>
<?php endif; ?>
