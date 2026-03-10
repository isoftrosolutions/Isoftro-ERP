/**
 * Hamro ERP — Student Dashboard
 * Production Blueprint V3.0 — Implementation (LIGHT THEME)
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── STATE ──
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page') || 'dashboard';
    
    let activeNav = initialPage.includes('-') ? initialPage.split('-')[0] : initialPage;
    let activeSub = initialPage.includes('-') ? initialPage.split('-')[1] : null;
    let expanded = { classes: true, att: false, assignments: false, exams: false, fee: false, study: false, library: false, notices: false, profile: false };

    // ── ELEMENTS ──
    const mainContent = document.getElementById('mainContent');
    const sbBody = document.getElementById('sbBody');
    const sbToggle = document.getElementById('sbToggle');
    const sbClose = document.getElementById('sbClose');
    const sbOverlay = document.getElementById('sbOverlay');

    // ── SIDEBAR TOGGLE (matches Super Admin pattern) ──
    const toggleSidebar = () => {
        if (window.innerWidth >= 1024) {
            document.body.classList.toggle('sb-collapsed');
        } else {
            document.body.classList.toggle('sb-active');
        }
    };
    const closeSidebar = () => document.body.classList.remove('sb-active');

    if (sbToggle) sbToggle.addEventListener('click', toggleSidebar);
    if (sbClose) sbClose.addEventListener('click', closeSidebar);
    if (sbOverlay) sbOverlay.addEventListener('click', closeSidebar);

    // ── NAVIGATION TREE — PRD v3.0 Blueprint (Section 4.5) ──
    const NAV = [
        { id: "dashboard", icon: "fa-house", label: "Dashboard", sub: null, sec: "MAIN" },

        { id: "classes", icon: "fa-calendar-days", label: "My Classes", sub: [
            { id: "today",   l: "Today's Timetable",   nav: "classes", sub: "today"  },
            { id: "weekly",  l: "Weekly Schedule",      nav: "classes", sub: "weekly" },
            { id: "cal",     l: "Academic Calendar",    nav: "classes", sub: "cal"   }
        ], sec: "ACADEMIC" },

        { id: "att", icon: "fa-circle-check", label: "Attendance", sub: [
            { id: "sum",     l: "My Attendance Summary", nav: "att", sub: "sum"  },
            { id: "hist",    l: "Attendance History",    nav: "att", sub: "hist" },
            { id: "leave",   l: "Apply for Leave",       nav: "att", sub: "leave"}
        ], sec: "ACADEMIC" },

        { id: "assignments", icon: "fa-file-lines", label: "Assignments", sub: [
            { id: "pending", l: "Pending Assignments",  nav: "assignments", sub: "pending" },
            { id: "submit",  l: "Submit Assignment",    nav: "assignments", sub: "submit"  },
            { id: "graded",  l: "Graded Assignments",   nav: "assignments", sub: "graded"  }
        ], sec: "ACADEMIC" },

        { id: "exams", icon: "fa-trophy", label: "Exams & Mock Tests", sub: [
            { id: "avail",     l: "Available Exams",        nav: "exams", sub: "avail"     },
            { id: "results",   l: "My Results",             nav: "exams", sub: "results"   },
            { id: "analytics", l: "Performance Analytics",  nav: "exams", sub: "analytics" },
            { id: "leader",    l: "Leaderboard",            nav: "exams", sub: "leader"    }
        ], sec: "ACADEMIC" },

        { id: "fee", icon: "fa-coins", label: "Fee", sub: [
            { id: "status",   l: "Fee Status",          nav: "fee", sub: "status"   },
            { id: "pay",      l: "Payment History",     nav: "fee", sub: "pay"      },
            { id: "receipts", l: "Download Receipts",   nav: "fee", sub: "receipts" }
        ], sec: "PERSONAL" },

        { id: "study", icon: "fa-book-open", label: "Study Materials", sub: [
            { id: "overview",  l: "Library Overview",   nav: "study", sub: "overview"  },
            { id: "materials", l: "All Resources",      nav: "study", sub: "materials" },
            { id: "favorites", l: "My Favorites",       nav: "study", sub: "favorites" }
        ], sec: "PERSONAL" },

        { id: "library", icon: "fa-book-bookmark", label: "Library", sub: [
            { id: "borrowed", l: "My Borrowed Books", nav: "library", sub: "borrowed" },
            { id: "search",   l: "Book Search",       nav: "library", sub: "search"   }
        ], sec: "PERSONAL" },

        { id: "notices", icon: "fa-bullhorn", label: "Notices", sub: [
            { id: "inst",  l: "Institute Announcements", nav: "notices", sub: "inst"  },
            { id: "batch", l: "Batch Notices",           nav: "notices", sub: "batch" }
        ], sec: "PERSONAL" },

        { id: "profile", icon: "fa-circle-user", label: "My Profile", sub: [
            { id: "personal",  l: "Personal Details",   nav: "profile", sub: "personal"  },
            { id: "academic",  l: "Academic History",   nav: "profile", sub: "academic"  },
            { id: "docs",      l: "Documents",          nav: "profile", sub: "docs"      }
        ], sec: "PERSONAL" },
    ];

    // ── DATA ──
    const TODAY_CLASSES = [
        { subj: "General Knowledge", teacher: "Ramesh Sharma Sir", time: "7:00–8:30 AM", type: "physical", room: "Room 201", status: "done", color: "#3b82f6" },
        { subj: "Lok Sewa Ain", teacher: "Sita Devi Ma'am", time: "10:00–11:30 AM", type: "online", link: "meet.jit.si/hamrolabs-batch-a", status: "ongoing", color: "#8b5cf6" },
        { subj: "Current Affairs", teacher: "Ramesh Sharma Sir", time: "5:00–6:30 PM", type: "physical", room: "Room 104", status: "upcoming", color: "#10b981" },
    ];

    const ASSIGNMENTS = [
        { title: "Write 500 words on Federal Nepal", subject: "General Knowledge", due: "Tomorrow", urgency: "high", icon: "✍️" },
        { title: "Case study: PSC Selection Process", subject: "Lok Sewa Ain", due: "In 3 days", urgency: "med", icon: "📋" },
        { title: "Summarize Current Affairs Week 4", subject: "Current Affairs", due: "In 5 days", urgency: "low", icon: "📰" },
    ];

    const UPCOMING_EXAMS = [
        { name: "Mock Test #4 — GK & Current Affairs", batch: "Kharidar Batch A", dd: "05", mm: "Bhadra", dur: "90 min", qs: 100 },
        { name: "Sectional Test — Lok Sewa Ain", batch: "Kharidar Batch A", dd: "12", mm: "Bhadra", dur: "45 min", qs: 50 },
        { name: "Full Mock Exam #2", batch: "Kharidar Batch A", dd: "20", mm: "Bhadra", dur: "120 min", qs: 150 },
    ];

    const RECENT_RESULTS = [
        { name: "Mock Test #3", score: 78, total: 100, rank: 4, batchAvg: 74.2, color: "#3b82f6" },
        { name: "Sectional — GK", score: 82, total: 100, rank: 2, batchAvg: 71.0, color: "#10b981" },
        { name: "Lok Sewa Ain — Test", score: 65, total: 100, rank: 11, batchAvg: 68.5, color: "#f59e0b" },
    ];

    const LEADERBOARD = [
        { rank: 1, name: "Priya Basnet", score: 91.4, av: "PB", bg: "#8b5cf6", me: false },
        { rank: 2, name: "Anita Thapa", score: 88.2, av: "AT", bg: "#10b981", me: false },
        { rank: 3, name: "Bikash Shrestha", score: 85.6, av: "BS", bg: "#f59e0b", me: false },
        { rank: 4, name: "Suman Karki", score: 78.1, av: "SK", bg: "#3b82f6", me: true },
        { rank: 5, name: "Roshan Tamang", score: 74.9, av: "RT", bg: "#ef4444", me: false },
    ];

    const MATERIALS = [
        { name: "Constitution of Nepal 2072 — Chapter 3 Notes", subj: "Lok Sewa Ain", type: "PDF", icon: "📄", bg: "#eff6ff", uploaded: "2h ago" },
        { name: "Current Affairs August 2081 — Model Set", subj: "GK", type: "PDF", icon: "📄", bg: "#f0fdf4", uploaded: "Yesterday" },
        { name: "GK Question Bank — 500 Questions (2080–2081)", subj: "GK", type: "PDF", icon: "📄", bg: "#fdf4ff", uploaded: "2 days ago" },
    ];

    const NOTICES = [
        { dot: "#3b82f6", title: "Mock Test #4 scheduled — 2081 Bhadra 05 at 7:00 AM", time: "1h ago", scope: "Batch" },
        { dot: "#f59e0b", title: "Holiday Notice: No classes on 2081 Shrawan 30 due to Dashain", time: "3h ago", scope: "Institute" },
        { dot: "#10b981", title: "New study materials uploaded: 3 resources added to your batch", time: "Yesterday", scope: "Batch" },
        { dot: "#ef4444", title: "Fee Reminder: Installment #2 (NPR 3,000) due on 2081 Bhadra 01", time: "Yesterday", scope: "Fee" },
    ];

    const MOCK_QUESTIONS = [
        { q: "Which article of the Constitution of Nepal 2072 guarantees the Right to Education?", opts: ["Article 28", "Article 31", "Article 35", "Article 40"], ans: 1 },
        { q: "Nepal's Federal Parliament consists of how many members in the National Assembly?", opts: ["45", "59", "75", "89"], ans: 1 },
        { q: "The term of office for the President of Nepal is:", opts: ["4 years", "5 years", "6 years", "7 years"], ans: 1 },
    ];

    const WEEKLY_SCHEDULE = [
        { day: "Monday", sessions: ["GK 7:00-8:30 AM", "Lok Sewa Ain 10:00-11:30 AM", "Math 5:00-6:30 PM"] },
        { day: "Tuesday", sessions: ["Current Affairs 7:00-8:30 AM", "English 10:00-11:30 AM", "GK 5:00-6:30 PM"] },
        { day: "Wednesday", sessions: ["Lok Sewa Ain 7:00-8:30 AM", "Math 10:00-11:30 AM", "Current Affairs 5:00-6:30 PM"] },
        { day: "Thursday", sessions: ["GK 7:00-8:30 AM", "Lok Sewa Ain 10:00-11:30 AM", "Current Affairs 5:00-6:30 PM"] },
        { day: "Friday", sessions: ["Math 7:00-8:30 AM", "English 10:00-11:30 AM", "Mock Test 5:00-6:30 PM"] },
        { day: "Saturday", sessions: ["Revision 9:00 AM-12:00 PM", "Doubt Clearing 2:00-4:00 PM"] },
        { day: "Sunday", off: true },
    ];

    const ACADEMIC_CALENDAR = [
        { event: "Mock Test #4", date: "2081 Bhadra 05", status: "upcoming" },
        { event: "Mid-Term Assessment", date: "2081 Bhadra 15", status: "upcoming" },
        { event: "Final Mock Exam", date: "2081 Ashwin 20", status: "upcoming" },
        { event: "Batch Completion Ceremony", date: "2081 Kartik 15", status: "upcoming" },
    ];

    const ATTENDANCE_SUMMARY = {
        totalClasses: 85,
        attended: 70,
        absent: 8,
        onLeave: 7,
        percentage: 82,
        monthlyTrend: [80, 82, 78, 85, 83, 82]
    };

    const ATTENDANCE_HISTORY = [
        { date: "2081 Shrawan 22", subject: "General Knowledge", status: "present", markedBy: "Ramesh Sharma Sir" },
        { date: "2081 Shrawan 21", subject: "Lok Sewa Ain", status: "present", markedBy: "Sita Devi Ma'am" },
        { date: "2081 Shrawan 20", subject: "Current Affairs", status: "absent", markedBy: "System" },
        { date: "2081 Shrawan 19", subject: "General Knowledge", status: "present", markedBy: "Ramesh Sharma Sir" },
        { date: "2081 Shrawan 18", subject: "Mathematics", status: "leave", markedBy: "Admin" },
    ];

    const LEAVE_APPLICATIONS = [
        { id: "LV-001", from: "2081 Shrawan 18", to: "2081 Shrawan 18", reason: "Family function", status: "approved" },
        { id: "LV-002", from: "2081 Shrawan 10", to: "2081 Shrawan 10", reason: "Health issues", status: "approved" },
    ];

    const GRADED_ASSIGNMENTS = [
        { title: "Essay on Federalism", subject: "Lok Sewa Ain", submitted: "2081 Shrawan 15", graded: "2081 Shrawan 16", score: 85, feedback: "Good analysis, improve structure", maxScore: 100 },
        { title: "Current Affairs Summary", subject: "GK", submitted: "2081 Shrawan 10", graded: "2081 Shrawan 11", score: 78, feedback: "Well researched", maxScore: 100 },
        { title: "Math Problem Set 1", subject: "Mathematics", submitted: "2081 Shrawan 05", graded: "2081 Shrawan 06", score: 65, feedback: "Need more practice", maxScore: 100 },
    ];

    const FEE_STATUS = {
        totalDue: 14000,
        paid: 8000,
        outstanding: 6000,
        nextDueDate: "2081 Bhadra 01",
        nextInstallment: 3000,
        history: [
            { type: "Admission Fee", amount: 5000, date: "2081 Baisakh 15", status: "Paid", receipt: "RCP-001" },
            { type: "Installment #1", amount: 3000, date: "2081 Ashad 01", status: "Paid", receipt: "RCP-002" },
            { type: "Installment #2", amount: 3000, date: "2081 Bhadra 01", status: "Pending", receipt: null },
            { type: "Installment #3", amount: 3000, date: "2081 Ashwin 01", status: "Upcoming", receipt: null },
        ]
    };

    const PAYMENT_HISTORY = [
        { date: "2081 Ashad 01", method: "Online Banking", amount: 3000, ref: "TXN789456", status: "Success" },
        { date: "2081 Baisakh 15", method: "E-Sewa", amount: 5000, ref: "TXN123789", status: "Success" },
    ];

    const PREVIOUS_YEAR_PAPERS = [
        { year: "2080", exam: "Kharidar Final", subjects: ["GK", "Lok Sewa Ain", "Math"], downloads: 156 },
        { year: "2079", exam: "Kharidar Final", subjects: ["GK", "Lok Sewa Ain", "English"], downloads: 142 },
        { year: "2078", exam: "Kharidar Final", subjects: ["GK", "Mathematics", "Current Affairs"], downloads: 128 },
    ];

    const BOOKMARKS = [
        { title: "Constitution Article 31-35", type: "Note", added: "2 days ago", subject: "Lok Sewa Ain" },
        { title: "Important Supreme Court Cases", type: "Link", added: "1 week ago", subject: "GK" },
        { title: "Math Formulas Sheet", type: "PDF", added: "3 days ago", subject: "Mathematics" },
    ];

    const DOWNLOADS = [
        { name: "Constitution_Notes_Ch3.pdf", size: "2.4 MB", date: "2081 Shrawan 20", type: "PDF" },
        { name: "Current_Affairs_Aug_2081.docx", size: "1.8 MB", date: "2081 Shrawan 19", type: "DOCX" },
        { name: "Math_Problem_Set_1.pdf", size: "856 KB", date: "2081 Shrawan 18", type: "PDF" },
    ];

    const LIBRARY_BOOKS = [
        { title: "Constitution of Nepal", author: "Dr. B.R. Bhattarai", borrowed: "2081 Shrawan 10", due: "2081 Bhadra 10", status: "active", fine: 0 },
        { title: "Lok Sewa Ain - Commentary", author: "Justice Karki", borrowed: "2081 Shrawan 05", due: "2081 Bhadra 05", status: "active", fine: 0 },
        { title: "Public Administration", author: "Prof. Sharma", borrowed: "2081 Ashad 20", due: "2081 Shrawan 20", status: "returned", fine: 0 },
    ];

    const LIBRARY_SEARCH_RESULTS = [
        { title: "Federalism in Nepal", author: "Dr. P. Thapa", available: true, copies: 3 },
        { title: "Civil Service Guide", author: "M. Shrestha", available: true, copies: 2 },
        { title: "Constitution at a Glance", author: "Legal Aid Council", available: false, copies: 0 },
    ];

    const INSTITUTE_NOTICES = [
        { title: "Batch A Mock Test #4 Schedule", date: "2081 Shrawan 25", category: "Exams", priority: "high" },
        { title: "Library Hours Extended During Exams", date: "2081 Shrawan 24", category: "General", priority: "medium" },
        { title: "New Faculty Joining - Mathematics", date: "2081 Shrawan 22", category: "Announcement", priority: "low" },
    ];

    const BATCH_NOTICES = [
        { title: "Extra class on Saturday - 9:00 AM", date: "2081 Shrawan 26", postedBy: "Class Teacher" },
        { title: "Assignment Submission Reminder", date: "2081 Shrawan 25", postedBy: "Admin" },
        { title: "Weekly Test Postponed to Friday", date: "2081 Shrawan 24", postedBy: "Coordinator" },
    ];

    const PROFILE_PERSONAL = {
        name: "Suman Karki",
        roll: "HL-2081-KH-047",
        batch: "Kharidar Batch A - 2081",
        phone: "9801234567",
        email: "suman.karki@example.com",
        address: "Kathmandu, Nepal",
        dob: "2055-04-15",
        bloodGroup: "B+",
        emergencyContact: "Ramesh Karki (Father) - 9851234567",
    };

    const PROFILE_ACADEMIC = {
        enrollmentDate: "2081 Baisakh 01",
        currentLevel: "Kharidar Preparation",
        previousEducation: "Bachelor's in Education (B.Ed.)",
        university: "Tribhuvan University",
        achievements: ["Batch Rank #4", "Best Performer - GK Section", "100% Attendance - Month 3"],
        skills: ["Quick Learning", "Analytical Thinking", "Current Affairs", "Essay Writing"],
    };

    const PROFILE_DOCUMENTS = [
        { name: "SLC Certificate", status: "Verified", uploaded: "2081 Baisakh 05" },
        { name: "+2 Certificate", status: "Verified", uploaded: "2081 Baisakh 05" },
        { name: "Bachelor's Degree", status: "Verified", uploaded: "2081 Baisakh 06" },
        { name: "Citizenship Certificate", status: "Pending", uploaded: "2081 Baisakh 07" },
        { name: "Passport Size Photo", status: "Verified", uploaded: "2081 Baisakh 05" },
    ];

    // ── MODALS ──
    let currentModal = null;

    function showModal(content) {
        currentModal = document.createElement('div');
        currentModal.className = 'modal-overlay';
        currentModal.innerHTML = `
            <div class="modal-content">
                <button class="modal-close" onclick="closeModal()">✕</button>
                ${content}
            </div>
        `;
        document.body.appendChild(currentModal);
    }

    function closeModal() {
        if (currentModal) {
            currentModal.remove();
            currentModal = null;
        }
    }

    function MockExamModal({ onClose }) {
        const [qIdx, setQIdx] = useState(0);
        const [answers, setAnswers] = useState({});
        const [submitted, setSubmitted] = useState(false);
        const [timeLeft] = useState("28:42");

        const q = MOCK_QUESTIONS[qIdx];
        const total = MOCK_QUESTIONS.length;
        const score = submitted ? MOCK_QUESTIONS.filter((q,i)=>answers[i]===q.ans).length : 0;

        return (
            `<div class="overlay" onclick="if(event.target===this) ${!submitted ? 'closeModal()' : ''}">
                <div class="modal" style="width:520px;">
                    ${!submitted ? `
                    <div class="exam-header">
                        <div>
                            <div style="font-size:10px;color:rgba(255,255,255,.5%);margin-bottom:2px;">Mock Test #4 — GK & Current Affairs</div>
                            <div style="font-size:12px;color:rgba(255,255,255,.7%);">Q${qIdx+1} of ${total}</div>
                        </div>
                        <div class="exam-timer">${timeLeft}</div>
                        <div style="flex:1;">
                            <div class="exam-prog"><div class="exam-prog-fill" style="width:${((qIdx+1)/total)*100}%;"></div></div>
                        </div>
                    </div>
                    <div style="font-size:13.5px;font-weight:600;color:var(--text-dark);line-height:1.6;margin-bottom:18px;">${q.q}</div>
                    ${q.opts.map((opt,i)=>`
                    <button class="mcq-opt ${answers[qIdx]===i?"selected":""}" onclick="setAnswers(a=>({...a,[qIdx]:${i}}))">
                        <div class="mcq-letter">${["A","B","C","D"][i]}</div>
                        ${opt}
                    </button>
                    `).join('')}
                    <div style="display:flex;gap:8px;justify-content:space-between;margin-top:16px;">
                        <button class="btn btn-ghost btn-sm" onclick="setQIdx(q=>Math.max(0,q-1))" ${qIdx===0 ? 'disabled style="opacity:0.4;"' : ''}>← Prev</button>
                        ${qIdx<total-1 ?
                            `<button class="btn btn-primary btn-sm" onclick="setQIdx(q=>q+1)" ${answers[qIdx]===undefined ? 'disabled style="opacity:0.5;"' : ''}>Next →</button>` :
                            `<button class="btn btn-green btn-sm" onclick="setSubmitted(true)" ${Object.keys(answers).length<total ? 'disabled style="opacity:0.5;"' : ''}>✓ Submit</button>`
                        }
                    </div>
                    ` : `
                    <div style="text-align:center;padding:10px 0 20px;">
                        <div style="font-size:40px;margin-bottom:8px;">🎉</div>
                        <div style="font-family:var(--font-d);font-size:22px;font-weight:800;color:var(--text-dark);margin-bottom:4px;">
                            ${score}/${total} correct
                        </div>
                        <div style="font-size:13px;color:var(--text-body);margin-bottom:16px;">Score: ${Math.round((score/total)*100)}% · Batch avg: 74.2%</div>
                        <div style="background:var(--green-lt);border-radius:10px;padding:12px 20px;margin-bottom:16px;display:inline-flex;gap:20px;">
                            ${[{l:"Your Rank",v:"#4"},{l:"Percentile",v:"82nd"},{l:"Time Taken",v:"31:18"}].map((s,i)=>`
                            <div style="text-align:center;">
                                <div style="font-family:var(--font-d);font-size:18px;font-weight:800;color:var(--green);">${s.v}</div>
                                <div style="font-size:10px;color:var(--text-light);">${s.l}</div>
                            </div>
                            `).join('')}
                        </div>
                        <div style="margin-bottom:14px;">
                            ${MOCK_QUESTIONS.map((q,i)=>`
                            <div style="display:flex;gap:10px;padding:9px 0;border-bottom:1px solid var(--card-border);text-align:left;">
                                <div style="font-size:10px;color:var(--text-light);width:20px;flex-shrink:0;">${i+1}.</div>
                                <div style="flex:1;">
                                    <div style="font-size:11.5px;color:var(--text-dark);margin-bottom:4px;">${q.q.slice(0,70)}…</div>
                                    <div style="display:flex;gap:6px;">
                                        <span class="pill ${answers[i]===q.ans?"pg":"pr"}">${answers[i]===q.ans?"✓ Correct":"✗ Wrong"}</span>
                                        <span style="font-size:10px;color:var(--text-light);">Correct: ${["A","B","C","D"][q.ans]}</span>
                                    </div>
                                </div>
                            </div>
                            `).join('')}
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;justify-content:center;">
                        <button class="btn btn-ghost" onclick="closeModal()">Close</button>
                        <button class="btn btn-primary" onclick="closeModal()">View Full Analytics</button>
                    </div>
                    `}
                </div>
            </div>
        `);
    }

    function SubmitAssignmentModal({ onClose }) {
        return `
            <div class="overlay" onclick="if(event.target===this) closeModal()">
                <div class="modal">
                    <div class="modal-h">
                        <div class="modal-t">📝 Submit Assignment</div>
                        <button class="modal-x" onclick="closeModal()">✕</button>
                    </div>
                    <div class="form-row">
                        <label class="form-lbl">Assignment</label>
                        <select class="form-sel">
                            <option>Write 500 words on Federal Nepal (Due: Tomorrow)</option>
                            <option>Case study: PSC Selection Process (Due: In 3 days)</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label class="form-lbl">Your Answer / Submission</label>
                        <textarea class="form-inp" rows="5" placeholder="Write your answer here, or paste text..."></textarea>
                    </div>
                    <div class="form-row">
                        <label class="form-lbl">Attach File (optional)</label>
                        <div style="border:1.5px dashed var(--card-border);border-radius:10px;padding:18px;text-align:center;cursor:pointer;transition:border-color .14s;background:var(--bg);"
                            onmouseenter="this.style.borderColor='var(--blue)'" onmouseleave="this.style.borderColor='var(--card-border)'">
                            <div style="font-size:22px;margin-bottom:4px;">📎</div>
                            <div style="font-size:12px;color:var(--text-body);">Drop PDF or image here</div>
                            <div style="font-size:10.5px;color:var(--text-light);margin-top:2px;">PDF, DOCX, JPG · Max 10MB</div>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                        <button class="btn btn-primary" onclick="closeModal()">Submit Assignment</button>
                    </div>
                </div>
            </div>
        `;
    }

    // ── NAVIGATION LOGIC ──
    window.goNav = (id, subActive = null) => {
        if (typeof subActive === 'string') {
            activeNav = id;
            activeSub = subActive;
        } else if (subActive && subActive.nav) {
            activeNav = subActive.nav;
            activeSub = subActive.sub;
        } else {
            activeNav = id;
            activeSub = null;
        }

        // Update URL via pushState
        const url = new URL(window.location);
        const pageVal = activeSub ? `${activeNav}-${activeSub}` : activeNav;
        url.searchParams.set('page', pageVal);
        window.history.pushState({ pageVal }, '', url);

        if (window.innerWidth < 1024) closeSidebar();
        renderSidebar();
        renderPage();
    };

    // Handle Browser Back/Forward
    window.addEventListener('popstate', (e) => {
        let pageVal;
        if (e.state && e.state.pageVal) {
            pageVal = e.state.pageVal;
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            pageVal = urlParams.get('page') || 'dashboard';
        }
        activeNav = pageVal.includes('-') ? pageVal.split('-')[0] : pageVal;
        activeSub = pageVal.includes('-') ? pageVal.split('-')[1] : null;

        renderSidebar();
        renderPage();
    });

    window.toggleExp = (id) => {
        expanded[id] = !expanded[id];
        renderSidebar();
    };

    function renderSidebar() {
        const sections = [...new Set(NAV.map(n => n.sec))];
        let html = '';

        sections.forEach(sec => {
            html += `<div class="sb-sec"><div class="sb-sec-lbl">${sec}</div>`;

            NAV.filter(n => n.sec === sec).forEach(nav => {
                const isActive = activeNav === nav.id;
                // Check if any sub-item matches current activeNav + activeSub
                const subIsActive = nav.sub && nav.sub.some(s => s.nav === activeNav && s.sub === activeSub);
                
                const isExp = expanded[nav.id];

                html += `<div class="sb-item">
                    <button class="nb-btn ${isActive || subIsActive ? 'active' : ''}" onclick="${nav.sub ? `toggleExp('${nav.id}')` : `goNav('${nav.id}')`}">
                        <i class="fa-solid ${nav.icon}"></i>
                        <span style="flex:1; text-align:left;">${nav.label}</span>
                        ${nav.sub ? `<i class="fa-solid fa-chevron-right" style="font-size:10px; transition:0.2s; ${isExp ? 'transform:rotate(90deg)' : ''}"></i>` : ''}
                    </button>`;

                if (nav.sub && isExp) {
                    html += `<div class="sub-menu">`;
                    nav.sub.forEach(s => {
                        const isThisSubActive = s.nav === activeNav && s.sub === activeSub;
                        html += `<button class="sub-btn ${isThisSubActive ? 'active' : ''}" onclick="goNav('${nav.id}', ${JSON.stringify(s).replace(/"/g, '&quot;')})">
                            ${s.l}
                        </button>`;
                    });
                    html += `</div>`;
                }
                html += `</div>`;
            });
            html += `</div>`;
        });

        // Append Install App Button
        html += `
            <div class="sb-install-box">
                <button class="install-btn-trigger" onclick="openPwaModal()">
                    <i class="fa-solid fa-bolt"></i>
                    <span> Install App</span>
                </button>
            </div>
        `;

        sbBody.innerHTML = html;
        renderBottomNav();
    }

    function renderBottomNav() {
        let bNav = document.getElementById('bottomNav');
        if (!bNav) {
            bNav = document.createElement('nav');
            bNav.id = 'bottomNav';
            bNav.className = 'mobile-bottom-nav';
            document.body.appendChild(bNav);
        }

        const items = [
            { id: 'dashboard', icon: 'fa-house', label: 'Home', action: "goNav('dashboard')" },
            { id: 'classes', icon: 'fa-calendar-days', label: 'Classes', action: "goNav('classes', 'weekly')" },
            { id: 'assignments', icon: 'fa-file-lines', label: 'Tasks', action: "goNav('assignments', 'pending')" },
            { id: 'exams', icon: 'fa-trophy', label: 'Exams', action: "goNav('exams', 'avail')" },
            { id: 'fee', icon: 'fa-coins', label: 'Fee', action: "goNav('fee', 'status')" }
        ];

        let html = '';
        items.forEach(item => {
            const isActive = activeNav === item.id;
            html += `<button class="mb-nav-btn ${isActive ? 'active' : ''}" onclick="${item.action}">
                <i class="fa-solid ${item.icon}"></i>
                <span>${item.label}</span>
            </button>`;
        });
        bNav.innerHTML = html;
    }

    // ── API HELPERS ──
    const API_BASE = window.APP_URL || '';
    
    async function apiGet(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = `${API_BASE}/api/${endpoint}${queryString ? '?' + queryString : ''}`;
        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'Network error' };
        }
    }
    
    async function apiPost(endpoint, data) {
        const url = `${API_BASE}/api/${endpoint}`;
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'Network error' };
        }
    }
    
    // Global state for fetched data
    let dashboardData = null;
    let classesData = null;
    let attendanceData = null;
    
    // ── PAGE RENDERING ──
    function renderPage() {
        mainContent.innerHTML = '<div class="pg fu">Loading...</div>';

        // Dashboard
        if (activeNav === 'dashboard') {
            renderDashboard();
            return;
        }

        // Classes
        if (activeNav === 'classes') {
            if (activeSub === 'today') { renderClassesToday(); return; }
            if (activeSub === 'weekly') { renderClassesWeekly(); return; }
            if (activeSub === 'cal') { renderClassesCalendar(); return; }
        }

        // Attendance
        if (activeNav === 'att') {
            if (activeSub === 'sum') { renderAttendanceSummary(); return; }
            if (activeSub === 'hist') { renderAttendanceHistory(); return; }
            if (activeSub === 'leave') { renderAttendanceLeave(); return; }
        }

        // Assignments
        if (activeNav === 'assignments') {
            if (activeSub === 'pending') { renderAssignmentsPending(); return; }
            if (activeSub === 'submit') { showModal('submit'); renderDashboard(); return; }
            if (activeSub === 'graded') { renderAssignmentsGraded(); return; }
        }

        // Exams
        if (activeNav === 'exams') {
            if (activeSub === 'avail') { renderExamsAvailable(); return; }
            if (activeSub === 'results') { renderExamsResults(); return; }
            if (activeSub === 'analytics') { renderExamsAnalytics(); return; }
            if (activeSub === 'leader') { renderExamsLeaderboard(); return; }
        }

        // Fee
        if (activeNav === 'fee') {
            if (activeSub === 'status') { renderFeeStatus(); return; }
            if (activeSub === 'pay') { renderFeePaymentHistory(); return; }
        }
        // Study Materials
        if (activeNav === 'study') {
            window.renderStudentLMS?.(activeSub || 'overview');
            return;
        }

        // Library
        if (activeNav === 'library') {
            if (activeSub === 'borrowed') { renderLibraryBorrowed(); return; }
            if (activeSub === 'search') { renderLibrarySearch(); return; }
        }

        // Notices
        if (activeNav === 'notices') {
            if (activeSub === 'inst') { renderNoticesInstitute(); return; }
            if (activeSub === 'batch') { renderNoticesBatch(); return; }
        }

        // Profile
        if (activeNav === 'profile') {
            if (activeSub === 'personal') { renderProfilePersonal(); return; }
            if (activeSub === 'academic') { renderProfileAcademic(); return; }
            if (activeSub === 'docs') { renderProfileDocuments(); return; }
        }

        renderGenericPage();
    }

    // ── DASHBOARD ──
    async function renderDashboard() {
        // Show loading state
        mainContent.innerHTML = `
            <div class="pg fu" style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
                <div style="text-align:center;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:var(--green);margin-bottom:16px;"></i>
                    <div style="color:var(--text-body);">Loading dashboard...</div>
                </div>
            </div>
        `;
        
        // Fetch dashboard data
        const response = await apiGet('student/dashboard');
        
        if (!response.success) {
            mainContent.innerHTML = `
                <div class="pg fu" style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
                    <div style="text-align:center;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size:32px;color:var(--red);margin-bottom:16px;"></i>
                        <div style="color:var(--text-body);">${response.message || 'Failed to load dashboard'}</div>
                        <button class="btn btn-primary" style="margin-top:16px;" onclick="renderDashboard()">Retry</button>
                    </div>
                </div>
            `;
            return;
        }
        
        const data = response.data;
        dashboardData = data;
        
        const studentInfo = data.student_info || {};
        const todayClasses = data.today_classes || [];
        const attendanceSummary = data.attendance_summary || {};
        const feeSummary = data.fee_summary || {};
        const notices = data.recent_notices || [];
        const exams = data.upcoming_exams || [];
        const pendingAssignments = data.pending_assignments || 0;
        
        const attPct = attendanceSummary.percentage || 0;
        const attColor = attPct >= 75 ? "var(--green)" : attPct >= 60 ? "var(--amber)" : "var(--red)";
        const circ = 2 * Math.PI * 30;

        mainContent.innerHTML = `
            <div class="pg fu">
                <!-- HERO BAND -->
                <div class="hero">
                    <div class="hero-greeting">Good Morning, Suman Karki! 🌄</div>
                    <div class="hero-sub">Pioneer Loksewa Institute · Kharidar Batch A · 2081 Shrawan 22, Thursday</div>
                    <div class="hero-chips">
                        <div class="hero-chip">📚 <span>Kharidar Prep</span></div>
                        <div class="hero-chip">🎟️ Roll: <span>HL-2081-KH-047</span></div>
                        <div class="hero-chip">📅 Day 84 of 180</div>
                        <div class="hero-chip">🏆 Batch Rank: <span>#4 / 28</span></div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="qa-grid">
                    <button class="qa-btn" onclick="openModal('exam')">
                        <div class="qa-icon" style="color:var(--blue)"><i class="fa-solid fa-laptop-code"></i></div>
                        <div class="qa-lbl">Take Mock Test</div>
                    </button>
                    <button class="qa-btn" onclick="openModal('submit')">
                        <div class="qa-icon" style="color:var(--amber)"><i class="fa-solid fa-file-pen"></i></div>
                        <div class="qa-lbl">Submit Assignment</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('study', 'notes')">
                        <div class="qa-icon" style="color:var(--green)"><i class="fa-solid fa-download"></i></div>
                        <div class="qa-lbl">Download Notes</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('classes', 'weekly')">
                        <div class="qa-icon" style="color:var(--purple)"><i class="fa-solid fa-calendar-week"></i></div>
                        <div class="qa-lbl">View Timetable</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('fee', 'status')">
                        <div class="qa-icon" style="color:var(--navy)"><i class="fa-solid fa-credit-card"></i></div>
                        <div class="qa-lbl">Pay Fee</div>
                    </button>
                </div>

                <!-- TODAY'S CLASSES -->
                <div class="mb">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <div style="font-family:var(--font-d);font-size:10.5px;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.7px;">Today's Classes</div>
                        <span style="font-size:11px;color:var(--text-light);">${TODAY_CLASSES.length} sessions · Thursday</span>
                    </div>
                    <div class="cls-strip">
                        ${TODAY_CLASSES.map((cls,i)=>`
                            <div class="cls-card ${cls.status}">
                                <div class="cls-stripe" style="background:${cls.color}"></div>
                                <div class="cls-time">
                                    ${cls.time}
                                    ${cls.status==="ongoing"?'<span class="live-badge">● LIVE</span>':''}
                                    ${cls.status==="done"?'<span style="font-size:9px;color:var(--green);font-weight:700;">✓ Done</span>':''}
                                </div>
                                <div class="cls-subj" style="color:${cls.color}">${cls.subj}</div>
                                <div class="cls-teacher">${cls.teacher}</div>
                                <div class="cls-meta">
                                    <span class="cls-loc ${cls.type==="online"?"cls-online":"cls-room"}">
                                        ${cls.type==="online"?"🎥 Online":"🏫 "+cls.room}
                                    </span>
                                </div>
                                ${cls.status==="ongoing"?
                                    `<button class="btn btn-primary btn-sm" style="margin-top:10px;width:100%;justify-content:center;">
                                        ${cls.type==="online"?"🔗 Join Class":"✅ Mark Present"}
                                    </button>`:''}
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- MAIN GRID ROW 1 -->
                <div class="g65 mb">
                    <!-- LEFT: Assignments + Exams -->
                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <!-- Pending Assignments -->
                        <div class="card">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                                <div class="ct" style="margin:0;">Pending Assignments</div>
                                <span class="pill pr">${ASSIGNMENTS.length} pending</span>
                            </div>
                            ${ASSIGNMENTS.map((a,i)=>`
                                <div class="assign-item">
                                    <div class="assign-ic urgency-${a.urgency}">${a.icon}</div>
                                    <div class="assign-body">
                                        <div class="assign-title">${a.title}</div>
                                        <div class="assign-sub">${a.subject} · Due ${a.due}</div>
                                    </div>
                                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;">
                                        <span class="pill ${a.urgency==="high"?"pr":a.urgency==="med"?"py":"pb"}">${a.urgency}</span>
                                        <button class="btn btn-ghost btn-sm" onclick="openModal('submit')">Submit</button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>

                        <!-- Upcoming Exams -->
                        <div class="card">
                            <div class="ct">Upcoming Exams</div>
                            ${UPCOMING_EXAMS.map((ex,i)=>`
                                <div class="exam-item">
                                    <div class="exam-date-box">
                                        <div class="exam-dd">${ex.dd}</div>
                                        <div class="exam-mm">${ex.mm}</div>
                                    </div>
                                    <div class="exam-body">
                                        <div class="exam-name">${ex.name}</div>
                                        <div class="exam-sub">${ex.batch} · ${ex.dur} · ${ex.qs} questions</div>
                                    </div>
                                    <button class="btn btn-primary btn-sm" onclick="openModal('exam')">Take</button>
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <!-- Attendance Ring -->
                        <div class="card">
                            <div class="ct">My Attendance — This Month</div>
                            <div class="att-ring-wrap">
                                <svg class="att-ring-svg" width="80" height="80" viewBox="0 0 80 80">
                                    <circle cx="40" cy="40" r="30" fill="none" stroke="#f1f5f9" strokeWidth="7"/>
                                    <circle cx="40" cy="40" r="30" fill="none" stroke="${attColor}" strokeWidth="7"
                                        strokeDasharray="${circ}" strokeDashoffset="${circ*(1-attPct/100)}" strokeLinecap="round"/>
                                    <text x="40" y="40" textAnchor="middle" dominantBaseline="middle"
                                        fontFamily="Syne,sans-serif" fontWeight="800" fontSize="14" fill="${attColor}"
                                        style="transform:rotate(90deg) translate(0px,-80px);transform-origin:40px 40px;">
                                        ${attPct}%
                                    </text>
                                </svg>
                                <div class="att-detail">
                                    ${[
                                        {l:"Classes Attended", v:ATTENDANCE_SUMMARY.attended+"/"+ATTENDANCE_SUMMARY.totalClasses},
                                        {l:"Days Absent", v: ATTENDANCE_SUMMARY.absent},
                                        {l:"Days on Leave", v: ATTENDANCE_SUMMARY.onLeave},
                                        {l:"Status", v: attPct>=75?"Good 🟢":attPct>=60?"Warning 🟡":"Critical 🔴"},
                                    ].map((s,i)=>`
                                        <div class="att-stat-row">
                                            <div class="att-stat-lbl">${s.l}</div>
                                            <div class="att-stat-val" style="${s.l==="Status"?"color:"+attColor:""}">${s.v}</div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>

                        <!-- Fee Status Widget (PRD Spec) -->
                        <div class="card">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                                <div class="ct" style="margin:0;">Fee Summary</div>
                                <span class="pill py">Due Soon</span>
                            </div>
                            <div style="background:var(--bg); border-radius:10px; padding:12px; margin-bottom:12px;">
                                <div style="font-size:11px; color:var(--text-light); text-transform:uppercase; font-weight:700;">Outstanding Dues</div>
                                <div style="font-size:20px; font-weight:800; color:var(--red);">NPR ${FEE_STATUS.outstanding}</div>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--card-border);">
                                <span style="font-size:12px; color:var(--text-body);">Next Installment</span>
                                <span style="font-size:12px; font-weight:700; color:var(--text-dark);">NPR ${FEE_STATUS.nextInstallment}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; margin-bottom:12px;">
                                <span style="font-size:12px; color:var(--text-body);">Due Date</span>
                                <span style="font-size:12px; font-weight:700; color:var(--red);">${FEE_STATUS.nextDueDate}</span>
                            </div>
                            <div style="display:flex;gap:8px;justify-content:stretch;">
                                <button class="btn btn-primary btn-sm" style="flex:1; justify-content:center;" onclick="goNav('fee', 'status')">Pay Now</button>
                                <button class="btn btn-ghost btn-sm" onclick="goNav('fee', 'receipts')"><i class="fa-solid fa-receipt"></i> Receipts</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOTTOM GRID -->
                <div class="g35 mb">
                    <!-- Leaderboard -->
                    <div class="card">
                        <div class="ct">Batch Leaderboard — Mock Test #3</div>
                        ${LEADERBOARD.map((s,i)=>`
                            <div class="lb-item ${s.me?"lb-me-row":""}">
                                <div class="lb-rank ${i===0?"gold":i===1?"silver":i===2?"bronze":s.me?"me":""}">${s.rank}</div>
                                <div class="lb-av" style="background:${s.bg}">${s.av}</div>
                                <div class="lb-name" style="font-weight:${s.me?700:500};color:${s.me?"var(--green)":"var(--text-dark)"}">
                                    ${s.name} ${s.me?'<span style="font-size:10px;color:var(--green);">(You)</span>':''}
                                </div>
                                <div class="lb-score" style="color:${s.me?"var(--green)":"var(--text-dark)"}">${s.score}</div>
                            </div>
                        `).join('')}
                    </div>

                    <!-- Recent Results + Materials -->
                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <!-- Recent Results -->
                        <div class="card">
                            <div class="ct">Recent Exam Results</div>
                            ${RECENT_RESULTS.map((r,i)=>{
                                const c2 = 2*Math.PI*18;
                                return `
                                    <div class="result-item">
                                        <div class="result-score-ring">
                                            <svg width="44" height="44" viewBox="0 0 44 44" style="transform:rotate(-90deg);">
                                                <circle cx="22" cy="22" r="18" fill="none" stroke="#f1f5f9" strokeWidth="4"/>
                                                <circle cx="22" cy="22" r="18" fill="none" stroke="${r.color}" strokeWidth="4"
                                                    strokeDasharray="${c2}" strokeDashoffset="${c2*(1-r.score/100)}" strokeLinecap="round"/>
                                            </svg>
                                            <div class="result-score-pct" style="color:${r.color}">${r.score}</div>
                                        </div>
                                        <div style="flex:1;">
                                            <div class="result-name">${r.name}</div>
                                            <div class="result-meta">Batch avg: ${r.batchAvg}% · ${r.score>=r.batchAvg?"↑ Above avg":"↓ Below avg"}</div>
                                        </div>
                                        <span class="result-rank">#${r.rank}</span>
                                    </div>
                                `;
                            }).join('')}
                            <div class="div"/>
                            <button class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;" onclick="goNav('exams', 'analytics')">View Full Analytics →</button>
                        </div>

                        <!-- New Materials -->
                        <div class="card">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                                <div class="ct" style="margin:0;">Newly Uploaded Materials</div>
                                <span class="pill pb">${MATERIALS.length} new</span>
                            </div>
                            ${MATERIALS.map((m,i)=>`
                                <div class="mat-item">
                                    <div class="mat-ic" style="background:${m.bg}; font-size:16px;">${m.icon}</div>
                                    <div class="mat-body">
                                        <div class="mat-name">${m.name}</div>
                                        <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                                            <span class="pill pb" style="font-size:9px;">${m.subj}</span>
                                            <span style="font-size:10.5px; color:var(--text-light);">${m.uploaded}</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-ghost btn-sm" onclick="goNav('study', 'notes')"><i class="fa-solid fa-download"></i></button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>

                <!-- NOTICES -->
                <div class="card">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                        <div class="ct" style="margin:0;">Notices & Announcements</div>
                        <span class="pill pr">1 unread</span>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 30px;">
                        ${NOTICES.map((n,i)=>`
                            <div class="notice-item">
                                <div class="notice-dot" style="background:${n.dot}"></div>
                                <div>
                                    <div class="notice-t">${n.title}</div>
                                    <div class="notice-m">${n.time} · ${n.scope}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    // ── PAGE COMPONENTS ──
    async function renderClassesToday() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Today's Timetable</div><div class="ps">Loading today's schedule...</div></div>
                <div style="display:flex;justify-content:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:var(--green);"></i></div>
            </div>
        `;
        
        const response = await apiGet('student/classes', { action: 'today' });
        
        if (!response.success) {
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="ph"><div class="pt">Today's Timetable</div></div>
                    <div class="card" style="text-align:center;padding:40px;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size:32px;color:var(--red);margin-bottom:16px;"></i>
                        <div>${response.message || 'Failed to load schedule'}</div>
                        <button class="btn btn-primary" style="margin-top:16px;" onclick="renderClassesToday()">Retry</button>
                    </div>
                </div>
            `;
            return;
        }
        
        const classes = response.data || [];
        const dayName = response.day || new Date().toLocaleDateString('en-US', { weekday: 'long' });
        const dateStr = response.date || new Date().toISOString().split('T')[0];
        
        if (classes.length === 0) {
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="ph"><div class="pt">Today's Timetable</div><div class="ps">${dayName} · ${dateStr}</div></div>
                    <div class="card" style="text-align:center;padding:60px 40px;">
                        <div style="font-size:48px;margin-bottom:16px;">🎉</div>
                        <div style="font-size:16px;font-weight:600;color:var(--text-dark);">No classes today!</div>
                        <div style="color:var(--text-light);margin-top:8px;">Enjoy your day off</div>
                    </div>
                </div>
            `;
            return;
        }
        
        // Color mapping for subjects
        const subjectColors = {
            'General Knowledge': '#3b82f6',
            'GK': '#3b82f6',
            'Lok Sewa Ain': '#8b5cf6',
            'Current Affairs': '#10b981',
            'Mathematics': '#f59e0b',
            'Math': '#f59e0b',
            'English': '#ec4899'
        };
        
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Today's Timetable</div><div class="ps">${dayName} · ${dateStr} · ${classes.length} sessions</div></div>
                <div class="cls-strip">
                    ${classes.map((cls, i) => {
                        const color = subjectColors[cls.subject_name] || '#6b7280';
                        const isOngoing = cls.status === 'ongoing';
                        const isDone = cls.status === 'completed';
                        const type = cls.is_online ? 'online' : 'physical';
                        return `
                        <div class="cls-card ${cls.status}">
                            <div class="cls-stripe" style="background:${color}"></div>
                            <div class="cls-time">
                                ${cls.start_time?.substring(0, 5) || ''} - ${cls.end_time?.substring(0, 5) || ''}
                                ${isOngoing ? '<span class="live-badge">● LIVE</span>' : ''}
                                ${isDone ? '<span style="font-size:9px;color:var(--green);font-weight:700;">✓ Done</span>' : ''}
                            </div>
                            <div class="cls-subj" style="color:${color}">${cls.subject_name}</div>
                            <div class="cls-teacher">${cls.teacher_name || 'TBA'}</div>
                            <div class="cls-meta">
                                <span class="cls-loc ${type === 'online' ? 'cls-online' : 'cls-room'}">
                                    ${type === 'online' ? '🎥 Online' : '🏫 ' + (cls.room_name || 'Room TBA')}
                                </span>
                            </div>
                            ${isOngoing ?
                                `<button class="btn btn-primary btn-sm" style="margin-top:10px;width:100%;justify-content:center;" onclick="${type === 'online' && cls.online_link ? `window.open('${cls.online_link}', '_blank')` : ''}">
                                    ${type === 'online' ? '🔗 Join Class' : '✅ Mark Present'}
                                </button>` : ''}
                        </div>
                    `}).join('')}
                </div>
            </div>
        `;
    }

    async function renderClassesWeekly() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Weekly Schedule</div><div class="ps">Loading your weekly timetable...</div></div>
                <div style="display:flex;justify-content:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:var(--green);"></i></div>
            </div>
        `;
        
        const response = await apiGet('student/classes', { action: 'weekly' });
        
        if (!response.success) {
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="ph"><div class="pt">Weekly Schedule</div></div>
                    <div class="card" style="text-align:center;padding:40px;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size:32px;color:var(--red);margin-bottom:16px;"></i>
                        <div>${response.message || 'Failed to load schedule'}</div>
                        <button class="btn btn-primary" style="margin-top:16px;" onclick="renderClassesWeekly()">Retry</button>
                    </div>
                </div>
            `;
            return;
        }
        
        const weeklyData = response.data || {};
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const today = new Date().toLocaleDateString('en-US', { weekday: 'long' });
        
        // Subject colors
        const subjectColors = {
            'General Knowledge': '#3b82f6', 'GK': '#3b82f6',
            'Lok Sewa Ain': '#8b5cf6',
            'Current Affairs': '#10b981',
            'Mathematics': '#f59e0b', 'Math': '#f59e0b',
            'English': '#ec4899'
        };
        
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Weekly Schedule</div><div class="ps">Your regular class timetable for the week</div></div>
                <div class="g2">
                    ${days.map((day, i) => {
                        const classes = weeklyData[day] || [];
                        const isToday = day === today;
                        return `
                        <div class="card" style="${isToday ? 'border:2px solid var(--green);' : ''}">
                            <div class="ct" style="display:flex;justify-content:space-between;align-items:center;">
                                ${day}
                                ${isToday ? '<span class="pill pg">Today</span>' : ''}
                            </div>
                            ${classes.length === 0 ?
                                '<div style="text-align:center;padding:20px;color:var(--text-light);">📅 No classes</div>' :
                                classes.map((cls, j) => `
                                    <div class="att-stat-row" style="padding:10px 0;border-bottom:1px solid var(--card-border);">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div style="width:8px;height:8px;border-radius:50%;background:${subjectColors[cls.subject_name] || '#6b7280'}"></div>
                                            <span class="att-stat-lbl">${cls.start_time?.substring(0, 5)} - ${cls.subject_name}</span>
                                        </div>
                                        <span style="font-size:11px;color:var(--text-light);">${cls.teacher_name || 'TBA'}</span>
                                    </div>
                                `).join('')
                            }
                        </div>
                    `}).join('')}
                </div>
            </div>
        `;
    }

    function renderClassesCalendar() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Academic Calendar</div><div class="ps">Important dates and events for the current batch</div></div>
                <div class="card">
                    ${ACADEMIC_CALENDAR.map((ev, i) => `
                        <div class="att-stat-row" style="padding:12px 0;">
                            <span class="att-stat-lbl" style="font-size:13px;font-weight:600;">${ev.event}</span>
                            <span class="att-stat-val" style="color:var(--blue);font-weight:700;">${ev.date}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    async function renderAttendanceSummary() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Attendance Summary</div><div class="ps">Loading attendance data...</div></div>
                <div style="display:flex;justify-content:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:var(--green);"></i></div>
            </div>
        `;
        
        const response = await apiGet('student/attendance', { action: 'summary' });
        
        if (!response.success) {
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="ph"><div class="pt">Attendance Summary</div></div>
                    <div class="card" style="text-align:center;padding:40px;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size:32px;color:var(--red);margin-bottom:16px;"></i>
                        <div>${response.message || 'Failed to load attendance'}</div>
                        <button class="btn btn-primary" style="margin-top:16px;" onclick="renderAttendanceSummary()">Retry</button>
                    </div>
                </div>
            `;
            return;
        }
        
        const data = response.data || {};
        const summary = data.summary || {};
        const monthlyData = data.monthly_breakdown || [];
        
        const attPct = summary.attendance_percentage || 0;
        const totalDays = summary.total_days || 0;
        const presentDays = summary.present_days || 0;
        const lateDays = summary.late_days || 0;
        const absentDays = summary.absent_days || 0;
        const leaveDays = summary.leave_days || 0;
        
        const attColor = attPct >= 75 ? 'var(--green)' : attPct >= 60 ? 'var(--amber)' : 'var(--red)';
        
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Attendance Summary</div><div class="ps">Your overall attendance statistics</div></div>
                <div class="g3 mb">
                    <div class="stat-card" style="border-top:3px solid ${attColor};">
                        <div class="stat-icon" style="background:${attColor}20;font-size:18px;">📊</div>
                        <div class="stat-val" style="color:${attColor};">${attPct}%</div>
                        <div class="stat-lbl">Overall Attendance</div>
                        <div class="stat-sub">${presentDays + lateDays}/${totalDays} classes</div>
                    </div>
                    <div class="stat-card" style="border-top:3px solid var(--green);">
                        <div class="stat-icon" style="background:var(--green-lt);font-size:18px;">✓</div>
                        <div class="stat-val" style="color:var(--green);">${presentDays}</div>
                        <div class="stat-lbl">Present Days</div>
                    </div>
                    <div class="stat-card" style="border-top:3px solid var(--amber);">
                        <div class="stat-icon" style="background:var(--amber-lt);font-size:18px;">📋</div>
                        <div class="stat-val" style="color:var(--amber);">${leaveDays}</div>
                        <div class="stat-lbl">Days on Leave</div>
                    </div>
                </div>
                ${monthlyData.length > 0 ? `
                <div class="card">
                    <div class="ct">Monthly Trend</div>
                    <div style="display:flex;align-items:flex-end;gap:8px;height:120px;padding:20px 0;">
                        ${monthlyData.map((month, i) => {
                            const val = Math.round(((month.present || 0) / (month.total || 1)) * 100);
                            return `
                            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;">
                                <div style="width:100%;background:var(--blue);border-radius:4px;height:${Math.max(val * 1.2, 10)}px;position:relative;">
                                    <div style="position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:10px;font-weight:700;color:var(--text-dark);">${val}%</div>
                                </div>
                                <span style="font-size:10px;color:var(--text-light);">${month.month?.substring(5) || 'M' + (i+1)}</span>
                            </div>
                        `}).join('')}
                    </div>
                </div>
                ` : ''}
            </div>
        `;
    }

    async function renderAttendanceHistory() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Attendance History</div><div class="ps">Loading attendance records...</div></div>
                <div style="display:flex;justify-content:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:var(--green);"></i></div>
            </div>
        `;
        
        const month = new Date().getMonth() + 1;
        const year = new Date().getFullYear();
        const response = await apiGet('student/attendance', { action: 'history', month, year });
        
        if (!response.success) {
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="ph"><div class="pt">Attendance History</div></div>
                    <div class="card" style="text-align:center;padding:40px;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size:32px;color:var(--red);margin-bottom:16px;"></i>
                        <div>${response.message || 'Failed to load history'}</div>
                        <button class="btn btn-primary" style="margin-top:16px;" onclick="renderAttendanceHistory()">Retry</button>
                    </div>
                </div>
            `;
            return;
        }
        
        const history = response.data || [];
        
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Attendance History</div><div class="ps">Detailed record of your daily attendance</div></div>
                <div class="card" style="padding:0;">
                    ${history.length === 0 ?
                        '<div style="text-align:center;padding:40px;color:var(--text-light);">No attendance records found for this month</div>' :
                        `<table class="tbl">
                            <thead><tr><th>Date</th><th>Subject</th><th>Status</th><th>Marked By</th></tr></thead>
                            <tbody>
                                ${history.map((rec, i) => `
                                    <tr>
                                        <td><span class="nm">${rec.date}</span></td>
                                        <td>${rec.subject_name || 'N/A'}</td>
                                        <td><span class="pill ${rec.status==="present"?"pg":rec.status==="leave"?"py":rec.status==="late"?"py":"pr"}">${rec.status}</span></td>
                                        <td style="color:var(--text-light);font-size:11px;">${rec.marked_by || 'System'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>`
                    }
                </div>
            </div>
        `;
    }

    async function renderAttendanceLeave() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Apply for Leave</div><div class="ps">Loading...</div></div>
                <div style="display:flex;justify-content:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:var(--green);"></i></div>
            </div>
        `;
        
        const response = await apiGet('student/attendance', { action: 'leave_status' });
        const leaveApplications = response.success ? (response.data || []) : [];
        
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Apply for Leave</div><div class="ps">Submit a new leave application</div></div>
                <div class="card" style="max-width:600px;">
                    <form id="leaveForm" onsubmit="submitLeaveApplication(event)">
                        <div class="form-row">
                            <label class="form-lbl">From Date *</label>
                            <input type="date" id="leaveFromDate" class="form-inp" required>
                        </div>
                        <div class="form-row">
                            <label class="form-lbl">To Date *</label>
                            <input type="date" id="leaveToDate" class="form-inp" required>
                        </div>
                        <div class="form-row">
                            <label class="form-lbl">Reason *</label>
                            <textarea id="leaveReason" class="form-inp" rows="4" placeholder="Please provide a reason for your leave application..." required></textarea>
                        </div>
                        <div style="margin-top:20px;">
                            <button type="submit" class="btn btn-primary">Submit Application</button>
                        </div>
                    </form>
                </div>
                ${leaveApplications.length > 0 ? `
                <div class="card" style="margin-top:16px;">
                    <div class="ct">Previous Leave Applications</div>
                    ${leaveApplications.map((lv, i) => `
                        <div class="att-stat-row" style="padding:12px 0;border-bottom:1px solid var(--card-border);">
                            <div>
                                <div style="font-weight:600;">${lv.start_date} to ${lv.end_date}</div>
                                <div style="font-size:11px;color:var(--text-light);">${lv.reason?.substring(0, 50)}${lv.reason?.length > 50 ? '...' : ''}</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span class="pill ${lv.status==='approved'?'pg':lv.status==='pending'?'py':'pr'}">${lv.status}</span>
                                ${lv.status === 'pending' ? `<button class="btn btn-ghost btn-sm" onclick="cancelLeave(${lv.id})">Cancel</button>` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
                ` : ''}
            </div>
        `;
    }
    
    window.submitLeaveApplication = async function(event) {
        event.preventDefault();
        const fromDate = document.getElementById('leaveFromDate').value;
        const toDate = document.getElementById('leaveToDate').value;
        const reason = document.getElementById('leaveReason').value;
        
        if (!fromDate || !toDate || !reason) {
            alert('Please fill in all required fields');
            return;
        }
        
        const response = await apiPost('student/attendance?action=apply_leave', {
            start_date: fromDate,
            end_date: toDate,
            reason: reason
        });
        
        if (response.success) {
            alert('Leave application submitted successfully!');
            renderAttendanceLeave();
        } else {
            alert(response.message || 'Failed to submit application');
        }
    };
    
    window.cancelLeave = async function(leaveId) {
        if (!confirm('Are you sure you want to cancel this leave application?')) return;
        
        const response = await apiPost('student/attendance?action=cancel_leave', { leave_id: leaveId });
        
        if (response.success) {
            alert('Leave application cancelled');
            renderAttendanceLeave();
        } else {
            alert(response.message || 'Failed to cancel');
        }
    };

    function renderAssignmentsPending() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Pending Assignments</div><div class="ps">Assignments that need your attention</div></div>
                ${ASSIGNMENTS.map((a, i) => `
                    <div class="card" style="margin-bottom:16px;">
                        <div style="display:flex;align-items:flex-start;gap:12px;">
                            <div class="assign-ic urgency-${a.urgency}" style="font-size:18px;">${a.icon}</div>
                            <div style="flex:1;">
                                <div class="assign-title" style="font-size:14px;font-weight:700;">${a.title}</div>
                                <div class="assign-sub" style="margin:4px 0 12px;">${a.subject} · Due ${a.due}</div>
                                <div style="display:flex;gap:8px;">
                                    <button class="btn btn-primary btn-sm" onclick="openModal('submit')">Submit Assignment</button>
                                    <button class="btn btn-ghost btn-sm">View Details</button>
                                </div>
                            </div>
                            <span class="pill ${a.urgency==="high"?"pr":a.urgency==="med"?"py":"pb"}" style="font-size:11px;padding:4px 10px;">${a.urgency.toUpperCase()}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderAssignmentsSubmit() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Submit Assignment</div><div class="ps">Upload your assignment for evaluation</div></div>
                <div class="card" style="max-width:800px;">
                    <div class="form-row">
                        <label class="form-lbl">Select Assignment</label>
                        <select class="form-sel" style="width:100%;">
                            ${ASSIGNMENTS.map(a => `<option>${a.title} (${a.subject}) - Due: ${a.due}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-row">
                        <label class="form-lbl">Your Answer / Submission</label>
                        <textarea class="form-inp" rows="8" placeholder="Write your answer here, or paste your text..."></textarea>
                    </div>
                    <div class="form-row">
                        <label class="form-lbl">Attach File (optional)</label>
                        <div style="border:1.5px dashed var(--card-border);border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:border-color .14s;background:var(--bg);"
                            onmouseenter="this.style.borderColor='var(--blue)'" onmouseleave="this.style.borderColor='var(--card-border)'">
                            <div style="font-size:28px;margin-bottom:6px;">📎</div>
                            <div style="font-size:13px;color:var(--text-body);">Drop files here or click to browse</div>
                            <div style="font-size:11px;color:var(--text-light);margin-top:4px;">PDF, DOCX, JPG, PNG · Max 10MB</div>
                        </div>
                    </div>
                    <div style="margin-top:24px;">
                        <button class="btn btn-primary" onclick="showAlert('Assignment submitted successfully!')">Submit Assignment</button>
                    </div>
                </div>
            </div>
        `;
    }

    function renderAssignmentsGraded() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Graded Assignments</div><div class="ps">View your submitted assignments and feedback</div></div>
                ${GRADED_ASSIGNMENTS.map((a, i) => `
                    <div class="card" style="margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                            <div>
                                <div style="font-size:14px;font-weight:700;color:var(--text-dark);">${a.title}</div>
                                <div style="font-size:12px;color:var(--text-light);margin-top:2px;">${a.subject}</div>
                            </div>
                            <span class="pill ${a.score>=70?"pg":"pr"}" style="font-size:12px;padding:4px 10px;">${a.score}/${a.maxScore}</span>
                        </div>
                        <div style="display:flex;gap:16px;font-size:11px;color:var(--text-light);margin-bottom:12px;">
                            <span>Submitted: ${a.submitted}</span>
                            <span>Graded: ${a.graded}</span>
                        </div>
                        <div style="background:var(--bg);border-radius:8px;padding:12px;margin-top:8px;">
                            <div style="font-size:11px;font-weight:700;color:var(--text-body);margin-bottom:4px;">Instructor Feedback:</div>
                            <div style="font-size:12px;color:var(--text-dark);line-height:1.6;">${a.feedback}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderExamsAvailable() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Available Exams</div><div class="ps">Upcoming mock tests and examinations</div></div>
                ${UPCOMING_EXAMS.map((ex, i) => `
                    <div class="card" style="margin-bottom:16px;">
                        <div style="display:flex;gap:16px;align-items:flex-start;">
                            <div class="exam-date-box">
                                <div class="exam-dd">${ex.dd}</div>
                                <div class="exam-mm">${ex.mm}</div>
                            </div>
                            <div style="flex:1;">
                                <div class="exam-name" style="font-size:14px;font-weight:700;">${ex.name}</div>
                                <div style="font-size:12px;color:var(--text-light);margin:4px 0;">${ex.batch} · ${ex.dur} · ${ex.qs} questions</div>
                                <div style="display:flex;gap:8px;margin-top:12px;">
                                    <button class="btn btn-primary btn-sm" onclick="openModal('exam')">Start Exam</button>
                                    <button class="btn btn-ghost btn-sm">View Syllabus</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderExamsResults() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">My Results</div><div class="ps">Your performance in recent exams</div></div>
                <div class="card" style="padding:0;">
                    <table class="tbl">
                        <thead><tr><th>Exam</th><th>Date</th><th>Score</th><th>Rank</th><th>Batch Avg</th><th>Status</th></tr></thead>
                        <tbody>
                            ${RECENT_RESULTS.map((r,i)=>`
                                <tr>
                                    <td><span class="nm">${r.name}</span></td>
                                    <td style="font-family:monospace;font-size:11px;">2081-07-${18-i*7}</td>
                                    <td><span style="font-family:'Syne',sans-serif;font-weight:700;color:var(--blue);">${r.score}/${r.total}</span></td>
                                    <td><span class="result-rank">#${r.rank}</span></td>
                                    <td style="color:var(--text-light);">${r.batchAvg}%</td>
                                    <td><span class="pill ${r.score>=70?"pg":"pr"}">${r.score>=70?"Pass":"Fail"}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function renderExamsAnalytics() {
        const overallAvg = RECENT_RESULTS.reduce((a,b)=>a+b.score,0)/RECENT_RESULTS.length;
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Performance Analytics</div><div class="ps">Your exam trend, subject breakdown and ranking over time</div></div>
                <div class="g3 mb">
                    <div class="stat-card" style="border-top:3px solid var(--blue);">
                        <div class="stat-icon" style="background:var(--blue-lt);font-size:18px;">📊</div>
                        <div class="stat-val" style="color:var(--blue);">${Math.round(overallAvg)}%</div>
                        <div class="stat-lbl">Overall Average</div>
                        <div class="stat-sub">Across ${RECENT_RESULTS.length} mock tests</div>
                    </div>
                    <div class="stat-card" style="border-top:3px solid var(--purple);">
                        <div class="stat-icon" style="background:var(--purple-lt);font-size:18px;">🏆</div>
                        <div class="stat-val" style="color:var(--purple);">#4</div>
                        <div class="stat-lbl">Current Rank</div>
                        <div class="stat-sub">out of 28 in Batch A</div>
                    </div>
                    <div class="stat-card" style="border-top:3px solid var(--green);">
                        <div class="stat-icon" style="background:var(--green-lt);font-size:18px;">⭐</div>
                        <div class="stat-val" style="color:var(--green);">82%</div>
                        <div class="stat-lbl">Best Score</div>
                        <div class="stat-sub">Sectional GK test</div>
                    </div>
                </div>
                <div class="g2 mb">
                    <div class="card">
                        <div class="ct">Score Trend — Last 3 Exams</div>
                        ${RECENT_RESULTS.map((r,i)=>`
                            <div style="margin-bottom:12px;">
                                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                    <div style="font-size:12px;font-weight:500;color:var(--text-dark);">${r.name}</div>
                                    <div style="font-family:var(--font-d);font-weight:700;font-size:13px;color:${r.score>=r.batchAvg?"var(--green)":"var(--amber)"};">${r.score}%</div>
                                </div>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <div style="flex:1;">
                                        <div class="prog-bar"><div class="prog-fill" style="width:${r.score}%;background:${r.color};"></div></div>
                                        <div style="height:2px;margin-top:2px;">
                                            <div style="width:${r.batchAvg}%;border-right:2px dashed var(--text-light);"></div>
                                        </div>
                                    </div>
                                    <span style="font-size:10px;color:var(--text-light);width:50px;text-align:right;">avg ${r.batchAvg}%</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="card">
                        <div class="ct">Subject Breakdown</div>
                        ${[
                            {subj:"General Knowledge", score:80, color:"var(--blue)"},
                            {subj:"Lok Sewa Ain", score:65, color:"var(--purple)"},
                            {subj:"Current Affairs", score:72, color:"var(--green)"},
                            {subj:"Mathematics & Reasoning", score:58, color:"var(--amber)"},
                        ].map((s,i)=>`
                            <div style="margin-bottom:10px;">
                                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                    <div style="font-size:11.5px;color:var(--text-body);">${s.subj}</div>
                                    <div style="font-size:11.5px;font-weight:700;color:${s.score>=70?"var(--green)":s.score>=60?"var(--amber)":"var(--red)"};">${s.score}%</div>
                                </div>
                                <div class="prog-bar"><div class="prog-fill" style="width:${s.score}%;background:${s.color};"></div></div>
                            </div>
                        `).join('')}
                        <div style="background:var(--amber-lt);border-radius:8px;padding:8px 10px;margin-top:10px;font-size:11px;color:var(--amber);font-weight:500;">
                            ⚠ Weak area: Mathematics & Reasoning (58%) — focus recommended
                        </div>
                    </div>
                </div>
                <div class="card" style="padding:0;">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--card-border);"><div class="ct" style="margin:0;">All Exam Attempts</div></div>
                    <table class="tbl">
                        <thead><tr><th>Exam</th><th>Date</th><th>Score</th><th>Rank</th><th>Batch Avg</th><th>Status</th></tr></thead>
                        <tbody>
                            ${[
                                {name:"Mock Test #3", date:"2081-07-18",score:"78/100",rank:"#4",  avg:"74.2%",pass:true},
                                {name:"Sectional — GK", date:"2081-07-10",score:"82/100",rank:"#2",  avg:"71.0%",pass:true},
                                {name:"Lok Sewa Ain Test", date:"2081-07-03",score:"65/100",rank:"#11", avg:"68.5%",pass:true},
                            ].map((r,i)=>`
                                <tr>
                                    <td><span class="nm">${r.name}</span></td>
                                    <td style="font-family:monospace;font-size:11px;">${r.date}</td>
                                    <td><span style="font-family:'Syne',sans-serif;font-weight:700;color:var(--blue);">${r.score}</span></td>
                                    <td><span class="result-rank">${r.rank}</span></td>
                                    <td style="color:var(--text-light);">${r.avg}</td>
                                    <td><span class="pill ${r.pass?"pg":"pr"}">${r.pass?"Pass":"Fail"}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function renderExamsLeaderboard() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Leaderboard</div><div class="ps">Batch rankings for Mock Test #3</div></div>
                <div class="card">
                    ${LEADERBOARD.map((s,i)=>`
                        <div class="lb-item ${s.me?"lb-me-row":""}">
                            <div class="lb-rank ${i===0?"gold":i===1?"silver":i===2?"bronze":s.me?"me":""}">${s.rank}</div>
                            <div class="lb-av" style="background:${s.bg}">${s.av}</div>
                            <div class="lb-name" style="font-weight:${s.me?700:500};color:${s.me?"var(--blue)":"var(--text-dark)"}">
                                ${s.name} ${s.me?'<span style="font-size:10px;color:var(--blue);">(You)</span>':''}
                            </div>
                            <div class="lb-score" style="color:${s.me?"var(--blue)":"var(--text-dark)"}">${s.score}%</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    async function renderFeeStatus() {
        mainContent.innerHTML = '<div class="pg fu">Loading fee status...</div>';
        try {
            const res = await fetch('api/student/fees?action=get_ledger');
            const result = await res.json();
            if (!result.success) throw new Error(result.message);
            
            const { ledger, summary } = result.data;
            
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="ph"><div class="pt">Fee Status</div><div class="ps">Overview of your fee payments and dues</div></div>
                    <div class="g3 mb">
                        <div class="stat-card" style="border-top:3px solid var(--red);">
                            <div class="stat-icon" style="background:var(--red-lt);font-size:18px;">💰</div>
                            <div class="stat-val" style="color:var(--red);">NPR ${parseFloat(summary.balance).toLocaleString()}</div>
                            <div class="stat-lbl">Outstanding Amount</div>
                        </div>
                        <div class="stat-card" style="border-top:3px solid var(--green);">
                            <div class="stat-icon" style="background:var(--green-lt);font-size:18px;">✓</div>
                            <div class="stat-val" style="color:var(--green);">NPR ${parseFloat(summary.total_paid).toLocaleString()}</div>
                            <div class="stat-lbl">Paid Amount</div>
                        </div>
                        <div class="stat-card" style="border-top:3px solid var(--blue);">
                            <div class="stat-icon" style="background:var(--blue-lt);font-size:18px;">📅</div>
                            <div class="stat-val" style="color:var(--blue);">-</div>
                            <div class="stat-lbl">Next Due Date</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="ct">Fee Details</div>
                        <div class="table-responsive">
                            <table class="table" style="width:100%">
                                <thead><tr><th style="text-align:left">Fee Type</th><th>Inst.</th><th>Due Date</th><th>Amount</th><th>Paid</th><th>Status</th></tr></thead>
                                <tbody>
                                    ${ledger.map(f => {
                                        const bal = f.amount_due - f.amount_paid;
                                        const status = bal <= 0 ? 'Paid' : (new Date(f.due_date) < new Date() ? 'Overdue' : 'Pending');
                                        return `
                                        <tr>
                                            <td style="padding:10px 0;">${f.fee_item_name}</td>
                                            <td style="text-align:center">${f.installment_no}</td>
                                            <td style="text-align:center">${f.due_date}</td>
                                            <td style="text-align:right">NPR ${parseFloat(f.amount_due).toLocaleString()}</td>
                                            <td style="text-align:right">NPR ${parseFloat(f.amount_paid).toLocaleString()}</td>
                                            <td style="text-align:center"><span class="pill ${status==='Paid'?'pg':(status==='Overdue'?'pr':'py')}">${status}</span></td>
                                        </tr>`;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } catch(e) {
            mainContent.innerHTML = `<div class="pg fu" style="color:red">Error: ${e.message}</div>`;
        }
    }

    async function renderFeePaymentHistory() {
        mainContent.innerHTML = '<div class="pg fu">Loading history...</div>';
        try {
            const res = await fetch('api/student/fees?action=get_ledger');
            const result = await res.json();
            if (!result.success) throw new Error(result.message);
            
            const { transactions } = result.data;
            
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="ph"><div class="pt">Payment History</div><div class="ps">Record of all your fee payments</div></div>
                    <div class="card" style="padding:0;">
                        <table class="tbl">
                            <thead><tr><th>Date</th><th>Method</th><th>Amount</th><th>Reference</th><th>Status</th></tr></thead>
                            <tbody>
                                ${transactions.map(p => `
                                    <tr>
                                        <td><span class="nm">${p.payment_date}</span></td>
                                        <td>${p.payment_method}</td>
                                        <td style="font-weight:700;">NPR ${parseFloat(p.amount).toLocaleString()}</td>
                                        <td style="font-family:monospace;font-size:11px;">
                                            ${p.receipt_number}
                                            <a href="/payment_flow/generate_pdf.php?receipt_no=${p.receipt_number}" target="_blank" title="Download PDF" style="margin-left:8px;color:var(--blue);">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </a>
                                        </td>
                                        <td><span class="pill pg">Success</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } catch(e) {
            mainContent.innerHTML = `<div class="pg fu" style="color:red">Error: ${e.message}</div>`;
        }
    }

    async function renderFeeReceipts() {
        mainContent.innerHTML = '<div class="pg fu">Loading receipts...</div>';
        try {
            const res = await fetch('api/student/fees?action=get_ledger');
            const result = await res.json();
            if (!result.success) throw new Error(result.message);
            
            const { transactions } = result.data;
            
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="ph"><div class="pt">Download Receipts</div><div class="ps">Download your fee payment receipts</div></div>
                    <div class="card">
                        ${transactions.map(t => `
                            <div class="att-stat-row">
                                <span class="att-stat-lbl">Payment for ${t.fee_item_name || 'Fees'}</span>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <span style="font-size:12px;color:var(--text-light);">${t.payment_date}</span>
                                    <button class="btn btn-ghost btn-sm" onclick="window.open('/payment_flow/generate_pdf.php?receipt_no=${t.receipt_number}', '_blank')">
                                        📄 Download ${t.receipt_number}
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        } catch(e) {
            mainContent.innerHTML = `<div class="pg fu" style="color:red">Error: ${e.message}</div>`;
        }
    }

    function renderStudyNotes() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Notes & Resources</div><div class="ps">Study materials and notes for your batch</div></div>
                <div class="card">
                    ${MATERIALS.map((m, i) => `
                        <div class="mat-item">
                            <div class="mat-ic" style="background:${m.bg};font-size:16px;">${m.icon}</div>
                            <div class="mat-body">
                                <div class="mat-name">${m.name}</div>
                                <div class="mat-sub">${m.subj} · Uploaded ${m.uploaded}</div>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="showAlert('Downloading ${m.name}')">↓</button>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderStudyPapers() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Previous Year Papers</div><div class="ps">Past exam papers for practice</div></div>
                ${PREVIOUS_YEAR_PAPERS.map((p, i) => `
                    <div class="card" style="margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                            <div>
                                <div style="font-size:14px;font-weight:700;color:var(--text-dark);">${p.year} — ${p.exam}</div>
                                <div style="font-size:12px;color:var(--text-light);margin-top:4px;">
                                    Subjects: ${p.subjects.join(', ')} · ${p.downloads} downloads
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="showAlert('Downloading ${p.year} ${p.exam} papers')">Download All</button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderStudyBookmarks() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Bookmarks</div><div class="ps">Your saved resources and links</div></div>
                ${BOOKMARKS.map((b, i) => `
                    <div class="card" style="margin-bottom:12px;">
                        <div style="display:flex;align-items:flex-start;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:8px;background:var(--blue-lt);display:flex;align-items:center;justify-content:center;font-size:16px;">🔖</div>
                            <div style="flex:1;">
                                <div style="font-size:13px;font-weight:600;color:var(--text-dark);">${b.title}</div>
                                <div style="font-size:11px;color:var(--text-light);margin-top:2px;">${b.type} · ${b.subject} · Added ${b.added}</div>
                            </div>
                            <button class="btn btn-ghost btn-sm" onclick="showAlert('Opening ${b.title}')">Open</button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderStudyDownloads() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Downloads</div><div class="ps">Your downloaded files</div></div>
                <div class="card" style="padding:0;">
                    <table class="tbl">
                        <thead><tr><th>File Name</th><th>Size</th><th>Date</th><th>Type</th><th>Action</th></tr></thead>
                        <tbody>
                            ${DOWNLOADS.map((d, i) => `
                                <tr>
                                    <td><span class="nm">${d.name}</span></td>
                                    <td style="font-size:11px;">${d.size}</td>
                                    <td style="font-family:monospace;font-size:11px;">${d.date}</td>
                                    <td><span class="tag" style="background:var(--blue-lt);color:var(--blue);">${d.type}</span></td>
                                    <td><button class="btn btn-ghost btn-sm" onclick="showAlert('Opening ${d.name}')">Open</button></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function renderLibraryBorrowed() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">My Borrowed Books</div><div class="ps">Books currently issued to you</div></div>
                ${LIBRARY_BOOKS.filter(b=>b.status==="active").map((b, i) => `
                    <div class="card" style="margin-bottom:16px;">
                        <div style="display:flex;gap:16px;align-items:flex-start;">
                            <div style="width:48px;height:64px;background:var(--purple-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--purple);flex-shrink:0;">📕</div>
                            <div style="flex:1;">
                                <div style="font-size:14px;font-weight:700;color:var(--text-dark);">${b.title}</div>
                                <div style="font-size:12px;color:var(--text-light);margin-top:2px;">by ${b.author}</div>
                                <div style="display:flex;gap:16px;font-size:11px;color:var(--text-light);margin-top:8px;">
                                    <span>📅 Borrowed: ${b.borrowed}</span>
                                    <span>⏰ Due: <b style="color:var(--red);">${b.due}</b></span>
                                </div>
                                ${b.fine > 0 ? `<div style="margin-top:8px;font-size:12px;color:var(--red);">⚠ Overdue fine: NPR ${b.fine}</div>` : ''}
                            </div>
                            <button class="btn btn-ghost btn-sm">Renew</button>
                        </div>
                    </div>
                `).join('')}
                <div class="card">
                    <div class="ct">Borrowing History</div>
                    ${LIBRARY_BOOKS.filter(b=>b.status==="returned").map((b, i) => `
                        <div class="att-stat-row">
                            <span class="att-stat-lbl">${b.title}</span>
                            <span class="att-stat-val">Returned ${b.borrowed}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderLibrarySearch() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Book Search</div><div class="ps">Search for books in the library catalog</div></div>
                <div class="card" style="margin-bottom:16px;">
                    <div class="tb">
                        <input type="text" class="inp" placeholder="Search by title, author, or ISBN..." style="flex:1;padding:8px 12px;">
                        <button class="btn btn-primary">Search</button>
                    </div>
                </div>
                <div class="card">
                    <div class="ct">Search Results</div>
                    ${LIBRARY_SEARCH_RESULTS.map((b, i) => `
                        <div class="att-stat-row" style="padding:12px 0;">
                            <div style="flex:1;">
                                <div style="font-size:13px;font-weight:600;color:var(--text-dark);">${b.title}</div>
                                <div style="font-size:11px;color:var(--text-light);margin-top:2px;">by ${b.author}</div>
                            </div>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <span class="pill ${b.available?"pg":"pr"}">${b.available?"Available":"Not Available"}</span>
                                <span style="font-size:11px;color:var(--text-light);">${b.copies} copies</span>
                                ${b.available ? `<button class="btn btn-ghost btn-sm">Request</button>` : `<button class="btn btn-ghost btn-sm">Notify Me</button>`}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderNoticesInstitute() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Institute Announcements</div><div class="ps">Official notices from the institute administration</div></div>
                ${INSTITUTE_NOTICES.map((n, i) => `
                    <div class="card" style="margin-bottom:12px;border-left:4px solid ${n.priority==="high"?"var(--red)":n.priority==="medium"?"var(--amber)":"var(--blue)"};">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                            <div style="font-size:13px;font-weight:700;color:var(--text-dark);">${n.title}</div>
                            <span class="tag" style="background:var(--blue-lt);color:var(--blue);">${n.category}</span>
                        </div>
                        <div style="font-size:11px;color:var(--text-light);">Posted on ${n.date}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderNoticesBatch() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Batch Notices</div><div class="ps">Notifications specific to your batch</div></div>
                ${BATCH_NOTICES.map((n, i) => `
                    <div class="card" style="margin-bottom:12px;">
                        <div style="margin-bottom:6px;">
                            <div style="font-size:13px;font-weight:700;color:var(--text-dark);">${n.title}</div>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-light);">
                            <span>Posted by: ${n.postedBy}</span>
                            <span>${n.date}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderProfilePersonal() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Personal Details</div><div class="ps">Your personal information</div></div>
                <div class="card" style="max-width:700px;">
                    ${Object.entries(PROFILE_PERSONAL).map(([key, val]) => `
                        <div class="att-stat-row">
                            <span class="att-stat-lbl" style="text-transform:capitalize;">${key.replace(/([A-Z])/g, ' $1').trim()}</span>
                            <span class="att-stat-val">${val}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderProfileAcademic() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Academic History</div><div class="ps">Your educational background and achievements</div></div>
                <div class="card" style="max-width:700px;">
                    ${Object.entries(PROFILE_ACADEMIC).map(([key, val]) => `
                        <div class="att-stat-row" style="${Array.isArray(val) ? 'align-items:flex-start;' : ''}">
                            <span class="att-stat-lbl" style="text-transform:capitalize;">${key.replace(/([A-Z])/g, ' $1').trim()}</span>
                            <span class="att-stat-val" style="${Array.isArray(val) ? 'display:block;' : ''}">
                                ${Array.isArray(val) ? val.map(v => `<div style="margin-bottom:4px;">• ${v}</div>`).join('') : val}
                            </span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderProfileDocuments() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="ph"><div class="pt">Documents</div><div class="ps">Your uploaded documents and verification status</div></div>
                <div class="card" style="padding:0;">
                    <table class="tbl">
                        <thead><tr><th>Document</th><th>Uploaded</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            ${PROFILE_DOCUMENTS.map((d, i) => `
                                <tr>
                                    <td><span class="nm">${d.name}</span></td>
                                    <td style="font-size:11px;">${d.uploaded}</td>
                                    <td><span class="pill ${d.status==="Verified"?"pg":"pr"}">${d.status}</span></td>
                                    <td><button class="btn btn-ghost btn-sm" onclick="showAlert('Viewing ${d.name}')">View</button></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    // ── GENERIC PAGE RENDERER ──
    function renderGenericPage() {
        const title = activeNav.split('-').map(s=>s.charAt(0).toUpperCase()+s.slice(1)).join(' ');
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="card" style="text-align:center;padding:80px 40px;">
                    <i class="fa-solid fa-person-digging" style="font-size:3rem;color:var(--text-light);margin-bottom:20px;"></i>
                    <h2>${title} Module</h2>
                    <p style="color:var(--text-body);margin-top:10px;">This module is being configured for the V3.0 production environment.</p>
                </div>
            </div>
        `;
    }

    // ── MODAL HANDLERS ──
    window.openModal = (type) => {
        if (type === 'exam') {
            showModal(MockExamModal({ onClose: closeModal }));
        } else if (type === 'submit') {
            showModal(SubmitAssignmentModal({ onClose: closeModal }));
        } else if (type === 'notes') {
            goNav('study', 'notes');
        } else if (type === 'timetable') {
            goNav('classes', 'today');
        } else if (type === 'fee') {
            goNav('fee', 'status');
        }
    };

    window.showAlert = (msg) => {
        alert(msg);
    };

    // Init
    renderSidebar();
    renderPage();
});
