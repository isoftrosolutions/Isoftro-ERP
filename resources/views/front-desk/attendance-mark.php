<?php
/**
 * Front Desk — Attendance Marking
 * Mobile-first, premium UI for daily attendance tracking
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Mark Attendance';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}

// Fetch courses & batches
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];

$stmtCourses = $db->prepare("SELECT id, name FROM courses WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL ORDER BY name");
$stmtCourses->execute(['tid' => $tenantId]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

$stmtBatches = $db->prepare("SELECT id, course_id, name, shift FROM batches WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL ORDER BY name");
$stmtBatches->execute(['tid' => $tenantId]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('academic');
}
?>

<div class="pg att-page">
    <!-- Page Header -->
    <div class="att-header">
        <div class="att-header-left">
            <div class="att-header-icon">
                <i class="fa-solid fa-clipboard-user"></i>
            </div>
            <div>
                <h1 class="att-header-title">Mark Attendance</h1>
                <p class="att-header-sub">Track daily student presence</p>
            </div>
        </div>
        <div class="att-today-badge">
            <i class="fa-regular fa-calendar"></i>
            <span id="todayDisplay"><?= date('D, M d') ?></span>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="att-filters">
        <div class="att-filter-row">
            <div class="att-filter-group">
                <label class="att-label"><i class="fa-solid fa-graduation-cap"></i> Course</label>
                <div class="att-select-wrap">
                    <select id="courseSelect" class="att-select" onchange="filterBatches(this.value)">
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down att-select-icon"></i>
                </div>
            </div>
            <div class="att-filter-group">
                <label class="att-label"><i class="fa-solid fa-users-rectangle"></i> Batch <span class="att-req">*</span></label>
                <div class="att-select-wrap">
                    <select id="batchSelect" class="att-select">
                        <option value="">Select Course First</option>
                    </select>
                    <i class="fa-solid fa-chevron-down att-select-icon"></i>
                </div>
            </div>
        </div>
        <div class="att-filter-row att-filter-bottom">
            <div class="att-filter-group att-date-group">
                <label class="att-label"><i class="fa-regular fa-calendar-days"></i> Date</label>
                <input type="date" id="attendanceDate" class="att-input" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="att-load-btn" onclick="loadStudentList()">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span>Load Students</span>
            </button>
        </div>
    </div>

    <!-- Attendance Sheet (Hidden by default) -->
    <div id="attendanceSheet" class="att-sheet" style="display:none;">
        <!-- Sheet Header with Stats -->
        <div class="att-sheet-header">
            <div class="att-sheet-info">
                <h3 id="batchNameLabel" class="att-batch-name">-</h3>
                <p id="dateLabel" class="att-date-text">-</p>
            </div>
            <div class="att-counters" id="attCounters">
                <div class="att-counter att-counter-p" id="counterPresent">
                    <span class="att-counter-val" id="countP">0</span>
                    <span class="att-counter-lbl">P</span>
                </div>
                <div class="att-counter att-counter-a" id="counterAbsent">
                    <span class="att-counter-val" id="countA">0</span>
                    <span class="att-counter-lbl">A</span>
                </div>
                <div class="att-counter att-counter-l" id="counterLate">
                    <span class="att-counter-val" id="countL">0</span>
                    <span class="att-counter-lbl">L</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions Bar -->
        <div class="att-quick-bar">
            <button class="att-quick-btn att-qb-present" onclick="markAll('present')">
                <i class="fa-solid fa-check-double"></i> All Present
            </button>
            <button class="att-quick-btn att-qb-absent" onclick="markAll('absent')">
                <i class="fa-solid fa-xmark"></i> All Absent
            </button>
            <div class="att-search-inline">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="attSearch" placeholder="Search student..." oninput="filterStudents(this.value)">
            </div>
        </div>

        <!-- Student List Container -->
        <div id="studentContainer" class="att-student-list">
            <!-- Dynamic rows rendered here -->
        </div>

        <!-- Sticky Save Footer -->
        <div class="att-save-footer">
            <div class="att-save-summary">
                <span id="totalStudentsLabel">0 students</span>
            </div>
            <button class="att-save-btn" id="saveBtn" onclick="saveAttendance()">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <span>Save Attendance</span>
            </button>
        </div>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="att-empty">
        <div class="att-empty-inner">
            <div class="att-empty-icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <h3>Ready to Take Attendance</h3>
            <p>Select a course, batch and date above, then tap <strong>"Load Students"</strong> to begin.</p>
            <div class="att-empty-steps">
                <div class="att-step">
                    <div class="att-step-num">1</div>
                    <span>Choose Course</span>
                </div>
                <div class="att-step-arrow"><i class="fa-solid fa-arrow-right"></i></div>
                <div class="att-step">
                    <div class="att-step-num">2</div>
                    <span>Select Batch</span>
                </div>
                <div class="att-step-arrow"><i class="fa-solid fa-arrow-right"></i></div>
                <div class="att-step">
                    <div class="att-step-num">3</div>
                    <span>Mark & Save</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification Container -->
<div id="attToast" class="att-toast"></div>

<style>
/* ═══════════════════════════════════════════════════════════
   ATTENDANCE MARK — MOBILE-FIRST PREMIUM UI
   ═══════════════════════════════════════════════════════════ */

/* ── Page Header ── */
.att-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}
.att-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.att-header-icon {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, #10B981, #059669);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 20px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    flex-shrink: 0;
}
.att-header-title {
    font-size: clamp(1.1rem, 2.5vw, 1.4rem); font-weight: 800;
    color: var(--text-dark); letter-spacing: -0.5px;
    margin: 0;
}
.att-header-sub {
    font-size: clamp(11px, 1.1vw, 12px); color: var(--text-light); margin: 2px 0 0;
}
.att-today-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #f0fdf4; color: #059669;
    font-size: clamp(11px, 1.1vw, 12px); font-weight: 700;
    padding: 6px 14px; border-radius: 20px;
    border: 1px solid #bbf7d0;
}

/* ── Filter Section ── */
.att-filters {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--card-border);
    padding: 16px;
    margin-bottom: 20px;
    box-shadow: var(--shadow);
}
.att-filter-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}
.att-filter-bottom {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.att-filter-group { display: flex; flex-direction: column; }
.att-label {
    font-size: clamp(10px, 1vw, 11px); font-weight: 700; color: var(--text-light);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: flex; align-items: center; gap: 5px;
}
.att-label i { font-size: 10px; }
.att-req { color: var(--red); }
.att-select-wrap {
    position: relative;
}
.att-select {
    width: 100%; padding: 13.5px 36px 13.5px 14px;
    border: 1.5px solid var(--card-border); border-radius: 10px;
    font-size: clamp(13px, 1.2vw, 14px); font-family: var(--font);
    background: #fff; color: var(--text-dark);
    outline: none; appearance: none; cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
    min-height: 44px; /* Ensure 44px touch target */
}
.att-select:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.1);
}
.att-select-icon {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    font-size: 11px; color: var(--text-light); pointer-events: none;
}
.att-input {
    width: 100%; padding: 13.5px 14px;
    border: 1.5px solid var(--card-border); border-radius: 10px;
    font-size: clamp(13px, 1.2vw, 14px); font-family: var(--font);
    background: #fff; color: var(--text-dark);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    min-height: 44px;
}
.att-input:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.1);
}
.att-load-btn {
    width: 100%; padding: 14px 20px;
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    color: #fff; border: none; border-radius: 10px;
    font-size: clamp(13px, 1.2vw, 14px); font-weight: 700; font-family: var(--font);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(26, 26, 46, 0.2);
    min-height: 44px;
}
.att-load-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(26, 26, 46, 0.25); }
.att-load-btn:active { transform: translateY(0); }

/* ── Attendance Sheet ── */
.att-sheet {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--card-border);
    overflow: hidden;
    box-shadow: var(--shadow);
    margin-bottom: 100px; /* space for sticky footer */
}

/* Sheet Header */
.att-sheet-header {
    padding: 16px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-bottom: 1px solid var(--card-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.att-batch-name {
    font-size: clamp(14px, 1.4vw, 16px); font-weight: 800; color: var(--text-dark); margin: 0;
}
.att-date-text {
    font-size: clamp(10px, 1vw, 11px); color: var(--text-light); margin: 2px 0 0;
}
.att-counters {
    display: flex; gap: 6px;
}
.att-counter {
    display: flex; flex-direction: column; align-items: center;
    min-width: 42px; padding: 6px 8px;
    border-radius: 10px; text-align: center;
    transition: transform 0.2s;
}
.att-counter:hover { transform: scale(1.05); }
.att-counter-val { font-size: clamp(16px, 1.6vw, 20px); font-weight: 800; line-height: 1; }
.att-counter-lbl { font-size: clamp(8px, 0.8vw, 10px); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.att-counter-p { background: #dcfce7; color: #166534; }
.att-counter-a { background: #fee2e2; color: #b91c1c; }
.att-counter-l { background: #fef3c7; color: #92400e; }

/* Quick Actions Bar */
.att-quick-bar {
    padding: 10px 16px;
    display: flex;
    gap: 8px;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.att-quick-btn {
    flex-shrink: 0;
    padding: 10px 16px; border-radius: 8px;
    font-size: clamp(11px, 1.1vw, 12px); font-weight: 700; font-family: var(--font);
    cursor: pointer; border: 1.5px solid var(--card-border);
    background: #fff; color: var(--text-body);
    display: flex; align-items: center; gap: 5px;
    transition: all 0.2s;
    white-space: nowrap;
    min-height: 44px;
}
.att-quick-btn:hover { border-color: var(--green); color: var(--green); }
.att-qb-present:active { background: #dcfce7; border-color: #10b981; color: #166534; }
.att-qb-absent:active { background: #fee2e2; border-color: #ef4444; color: #b91c1c; }
.att-search-inline {
    flex: 1; min-width: 140px;
    position: relative;
    margin-left: auto;
}
.att-search-inline i {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    font-size: 12px; color: var(--text-light);
}
.att-search-inline input {
    width: 100%; padding: 10px 10px 10px 30px;
    border: 1.5px solid var(--card-border); border-radius: 8px;
    font-size: clamp(11px, 1.1vw, 12px); font-family: var(--font);
    outline: none; background: #f8fafc;
    transition: all 0.2s;
    min-height: 44px;
}
.att-search-inline input:focus {
    background: #fff;
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.08);
}

/* ── Student List ── */
.att-student-list {
    max-height: 60vh;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

/* Student Row */
.att-stu {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f8fafc;
    gap: 10px;
    transition: background 0.15s;
    animation: fadeInRow 0.2s ease-out;
}
.att-stu:last-child { border-bottom: none; }
.att-stu:active { background: #f8fafc; }
@keyframes fadeInRow {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

.att-stu-num {
    width: 24px; font-size: clamp(10px, 1vw, 11px); font-weight: 700;
    color: var(--text-light); text-align: center; flex-shrink: 0;
}
.att-stu-avatar {
    width: 36px; height: 36px;
    border-radius: 50%; object-fit: cover;
    border: 2px solid #f1f5f9; flex-shrink: 0;
    background: #f1f5f9;
}
.att-stu-info { flex: 1; min-width: 0; }
.att-stu-name {
    font-size: clamp(13px, 1.2vw, 14px); font-weight: 700; color: var(--text-dark);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.att-stu-roll {
    font-size: clamp(9px, 0.9vw, 11px); color: var(--text-light); font-weight: 600;
    letter-spacing: 0.3px;
}
.att-stu-leave {
    display: inline-flex; align-items: center; gap: 3px;
    background: #dbeafe; color: #1d4ed8;
    font-size: clamp(8px, 0.8vw, 9px); font-weight: 800;
    padding: 1px 5px; border-radius: 4px;
    text-transform: uppercase; letter-spacing: 0.3px;
    margin-left: 6px;
}

/* Status Toggle Buttons */
.att-status-group {
    display: flex; gap: 8px; flex-shrink: 0;
}
.att-pill {
    width: 44px; height: 44px;
    border-radius: 12px; border: 2px solid #e2e8f0;
    background: #fff; color: var(--text-light);
    font-size: clamp(13px, 1.2vw, 15px); font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-tap-highlight-color: transparent;
    user-select: none;
}
.att-pill:active { transform: scale(0.92); }

.att-pill.p-active {
    background: #10b981; color: #fff;
    border-color: #10b981;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);
}
.att-pill.a-active {
    background: #ef4444; color: #fff;
    border-color: #ef4444;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.35);
}
.att-pill.l-active {
    background: #f59e0b; color: #fff;
    border-color: #f59e0b;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);
}

/* ── Sticky Save Footer ── */
.att-save-footer {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-top: 1px solid var(--card-border);
    padding: 12px 16px;
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    z-index: 100;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
}
.att-save-footer.visible { display: flex; }
.att-save-summary {
    font-size: clamp(11px, 1.1vw, 12px); color: var(--text-light); font-weight: 600;
}
.att-save-btn {
    padding: 12px 28px;
    background: linear-gradient(135deg, #10B981, #059669);
    color: #fff; border: none; border-radius: 12px;
    font-size: clamp(13px, 1.3vw, 15px); font-weight: 700; font-family: var(--font);
    cursor: pointer;
    display: flex; align-items: center; gap: 8px;
    transition: all 0.2s;
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
    white-space: nowrap;
}
.att-save-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35); }
.att-save-btn:active { transform: translateY(0); }
.att-save-btn:disabled {
    opacity: 0.6; cursor: not-allowed; transform: none !important;
    box-shadow: none !important;
}

/* ── Empty State ── */
.att-empty { text-align: center; padding: 40px 20px; }
.att-empty-inner {
    max-width: 400px; margin: 0 auto;
}
.att-empty-icon {
    width: 72px; height: 72px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-radius: 20px; margin: 0 auto 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; color: #10b981;
}
.att-empty h3 {
    font-size: clamp(15px, 1.5vw, 18px); font-weight: 800; color: var(--text-dark);
    margin-bottom: 8px;
}
.att-empty p {
    font-size: clamp(13px, 1.2vw, 14px); color: var(--text-light); line-height: 1.6;
    margin-bottom: 24px;
}
.att-empty-steps {
    display: flex; align-items: center; justify-content: center;
    gap: 8px; flex-wrap: wrap;
}
.att-step {
    display: flex; align-items: center; gap: 6px;
    font-size: clamp(11px, 1.1vw, 12px); font-weight: 600; color: var(--text-body);
}
.att-step-num {
    width: 22px; height: 22px; border-radius: 50%;
    background: var(--green); color: #fff;
    font-size: clamp(10px, 1vw, 11px); font-weight: 800;
    display: flex; align-items: center; justify-content: center;
}
.att-step-arrow { color: var(--text-light); font-size: 10px; }

/* ── Toast Notification ── */
.att-toast {
    position: fixed; bottom: 90px; left: 50%; transform: translateX(-50%) translateY(80px);
    background: #1e293b; color: #fff;
    padding: 12px 24px; border-radius: 12px;
    font-size: clamp(12px, 1.2vw, 14px); font-weight: 600; font-family: var(--font);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    opacity: 0; visibility: hidden;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 200;
    display: flex; align-items: center; gap: 8px;
    white-space: nowrap;
    max-width: 90vw;
}
.att-toast.show {
    opacity: 1; visibility: visible;
    transform: translateX(-50%) translateY(0);
}
.att-toast.success { background: #059669; }
.att-toast.error { background: #dc2626; }

/* ── Loading Skeleton ── */
.att-skeleton {
    padding: 16px;
}
.att-skeleton-row {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 0; border-bottom: 1px solid #f8fafc;
}
.att-skel {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: skel-pulse 1.5s infinite linear;
    border-radius: 6px;
}
@keyframes skel-pulse {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.att-skel-circle { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; }
.att-skel-text { height: 12px; width: 120px; }
.att-skel-text-sm { height: 9px; width: 70px; margin-top: 6px; }
.att-skel-pills { display: flex; gap: 4px; margin-left: auto; }
.att-skel-pill { width: 38px; height: 38px; border-radius: 10px; }

/* ═══════════════════════════════════
   RESPONSIVE BREAKPOINTS
   ═══════════════════════════════════ */

/* Tablet & up */
@media (min-width: 640px) {
    .att-filter-row {
        grid-template-columns: 1fr 1fr;
    }
    .att-filter-bottom {
        flex-direction: row;
        align-items: flex-end;
    }
    .att-date-group { flex: 1; }
    .att-load-btn { width: auto; padding: 11px 24px; }
    .att-pill { width: 42px; height: 42px; }
    .att-stu { padding: 14px 20px; }
    .att-stu-name { font-size: 14px; }
}

/* Desktop */
@media (min-width: 1024px) {
    .att-save-footer {
        left: var(--sb-w);
    }
    .att-filters {
        padding: 20px;
    }
    .att-filter-row {
        grid-template-columns: 1fr 1fr;
    }
    .att-filter-bottom {
        margin-top: 16px;
    }
    .att-student-list {
        max-height: 55vh;
    }
    .att-stu { padding: 14px 24px; gap: 12px; }
    .att-stu-avatar { width: 40px; height: 40px; }
    .att-pill { width: 44px; height: 44px; font-size: 14px; border-radius: 12px; }
}

/* Large Desktop */
@media (min-width: 1280px) {
    .att-student-list {
        max-height: 60vh;
    }
}

/* ── Percentage bar in student row (desktop only) ── */
.att-pct-bar {
    display: none;
}
@media (min-width: 768px) {
    .att-pct-bar {
        display: flex; align-items: center; gap: 6px;
        flex-shrink: 0; width: 80px;
    }
    .att-pct-track {
        flex: 1; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden;
    }
    .att-pct-fill { height: 100%; border-radius: 2px; transition: width 0.3s; }
    .att-pct-text {
        font-size: 10px; font-weight: 700; min-width: 30px; text-align: right;
    }
}
</style>

<script>
const BATCHES = <?= json_encode($batches) ?>;
const DEFAULT_AVATAR = '<?= APP_URL ?>/public/assets/images/default-avatar.png';
const API_URL = '<?= APP_URL ?>';
let studentList = [];

function filterBatches(courseId) {
    const sel = document.getElementById('batchSelect');
    sel.innerHTML = '<option value="">Select Batch</option>';
    if (!courseId) return;
    const filtered = BATCHES.filter(b => b.course_id == courseId);
    filtered.forEach(b => {
        sel.innerHTML += `<option value="${b.id}">${b.name} (${b.shift})</option>`;
    });
}

async function loadStudentList() {
    const batchId = document.getElementById('batchSelect').value;
    const date = document.getElementById('attendanceDate').value;
    
    if (!batchId) {
        showToast('Please select a batch first', 'error');
        return;
    }
    
    document.getElementById('emptyState').style.display = 'none';
    const container = document.getElementById('studentContainer');
    const sheet = document.getElementById('attendanceSheet');
    sheet.style.display = 'block';
    
    // Show skeleton loading
    let skelHtml = '<div class="att-skeleton">';
    for (let i = 0; i < 6; i++) {
        skelHtml += `
            <div class="att-skeleton-row">
                <div class="att-skel att-skel-circle"></div>
                <div style="flex:1;">
                    <div class="att-skel att-skel-text"></div>
                    <div class="att-skel att-skel-text-sm"></div>
                </div>
                <div class="att-skel-pills">
                    <div class="att-skel att-skel-pill"></div>
                    <div class="att-skel att-skel-pill"></div>
                    <div class="att-skel att-skel-pill"></div>
                </div>
            </div>`;
    }
    skelHtml += '</div>';
    container.innerHTML = skelHtml;
    
    try {
        const res = await fetch(`${API_URL}/api/frontdesk/attendance?action=get_sheet&batch_id=${batchId}&date=${date}`);
        const result = await res.json();
        
        if (result.success) {
            studentList = (result.data || []).map(s => ({
                id: s.student_id,
                full_name: s.full_name,
                roll_no: s.roll_no,
                photo_url: s.photo_url || '',
                status: s.attendance?.status || (s.on_leave ? 'leave' : 'present'),
                percentage: s.percentage || 0,
                on_leave: s.on_leave || false,
                visible: true
            }));
            
            const batchSel = document.getElementById('batchSelect');
            document.getElementById('batchNameLabel').textContent = batchSel.options[batchSel.selectedIndex].text;
            document.getElementById('dateLabel').textContent = new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('totalStudentsLabel').textContent = studentList.length + ' student' + (studentList.length !== 1 ? 's' : '');
            
            renderSheet();
            updateCounters();
            
            // Show sticky footer
            document.querySelector('.att-save-footer').classList.add('visible');
        } else {
            container.innerHTML = `<div style="padding:50px; text-align:center; color:var(--red);"><i class="fa-solid fa-triangle-exclamation" style="font-size:20px; margin-bottom:8px; display:block;"></i>${result.message || 'Failed to load'}</div>`;
        }
    } catch (e) {
        container.innerHTML = '<div style="padding:50px; text-align:center; color:var(--red);"><i class="fa-solid fa-wifi" style="font-size:20px; margin-bottom:8px; display:block;"></i>Network error. Please try again.</div>';
    }
}

function renderSheet() {
    const container = document.getElementById('studentContainer');
    if (studentList.length === 0) {
        container.innerHTML = '<div style="padding:50px; text-align:center; color:var(--text-light);"><i class="fa-solid fa-user-slash" style="font-size:24px; margin-bottom:10px; display:block; opacity:0.4;"></i>No students found in this batch.</div>';
        return;
    }
    
    container.innerHTML = studentList.map((s, idx) => {
        if (!s.visible) return '';
        const pActive = s.status === 'present' ? 'p-active' : '';
        const aActive = s.status === 'absent' ? 'a-active' : '';
        const lActive = s.status === 'late' ? 'l-active' : '';
        const pctColor = s.percentage < 75 ? 'var(--red)' : 'var(--green)';
        
        return `
        <div class="att-stu" data-id="${s.id}" style="animation-delay:${idx * 0.02}s">
            <div class="att-stu-num">${idx + 1}</div>
            <img class="att-stu-avatar" src="${s.photo_url || DEFAULT_AVATAR}" onerror="this.src='${DEFAULT_AVATAR}'" alt="">
            <div class="att-stu-info">
                <div class="att-stu-name">
                    ${s.full_name}
                    ${s.on_leave ? '<span class="att-stu-leave"><i class="fa-solid fa-umbrella-beach"></i> Leave</span>' : ''}
                </div>
                <div class="att-stu-roll">#${s.roll_no}</div>
            </div>
            <div class="att-pct-bar">
                <div class="att-pct-track"><div class="att-pct-fill" style="width:${s.percentage}%; background:${pctColor};"></div></div>
                <span class="att-pct-text" style="color:${pctColor};">${s.percentage}%</span>
            </div>
            <div class="att-status-group">
                <div class="att-pill ${pActive}" onclick="toggleStatus(${s.id}, 'present')">P</div>
                <div class="att-pill ${aActive}" onclick="toggleStatus(${s.id}, 'absent')">A</div>
                <div class="att-pill ${lActive}" onclick="toggleStatus(${s.id}, 'late')">L</div>
            </div>
        </div>`;
    }).join('');
}

function toggleStatus(studentId, status) {
    const s = studentList.find(i => i.id == studentId);
    if (!s) return;
    s.status = status;

    // Optimistic UI update without full re-render
    const row = document.querySelector(`.att-stu[data-id="${studentId}"]`);
    if (row) {
        const pills = row.querySelectorAll('.att-pill');
        pills.forEach(p => p.className = 'att-pill');
        const map = { present: 'p-active', absent: 'a-active', late: 'l-active' };
        const idx = { present: 0, absent: 1, late: 2 }[status];
        if (pills[idx]) {
            pills[idx].classList.add(map[status]);
            // Haptic-like micro animation
            pills[idx].style.transform = 'scale(1.15)';
            setTimeout(() => pills[idx].style.transform = '', 150);
        }
    }
    updateCounters();
}

function markAll(status) {
    studentList.forEach(s => { if (s.visible) s.status = status; });
    renderSheet();
    updateCounters();
    showToast(`All students marked as ${status}`, 'success');
}

function updateCounters() {
    let p = 0, a = 0, l = 0;
    studentList.forEach(s => {
        if (s.status === 'present') p++;
        else if (s.status === 'absent') a++;
        else if (s.status === 'late') l++;
    });
    const cP = document.getElementById('countP');
    const cA = document.getElementById('countA');
    const cL = document.getElementById('countL');
    if (cP) cP.textContent = p;
    if (cA) cA.textContent = a;
    if (cL) cL.textContent = l;
}

function filterStudents(query) {
    const q = query.toLowerCase();
    studentList.forEach(s => {
        s.visible = s.full_name.toLowerCase().includes(q) || (s.roll_no + '').toLowerCase().includes(q);
    });
    renderSheet();
}

async function saveAttendance() {
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Saving...</span>';
    
    const batchId = document.getElementById('batchSelect').value;
    const courseId = document.getElementById('courseSelect').value;
    const date = document.getElementById('attendanceDate').value;
    
    try {
        const res = await fetch(`${API_URL}/api/frontdesk/attendance`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'take',
                batch_id: batchId,
                course_id: courseId,
                attendance_date: date,
                attendance: studentList.map(s => ({ student_id: s.id, status: s.status || 'present' }))
            })
        });
        
        const result = await res.json();
        if (result.success) {
            showToast('Attendance saved successfully!', 'success');
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> <span>Save Attendance</span>';
    }
}

function showToast(message, type = '') {
    const toast = document.getElementById('attToast');
    toast.className = 'att-toast ' + type;
    toast.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-circle-check' : type === 'error' ? 'fa-circle-xmark' : 'fa-info-circle'}"></i> ${message}`;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
