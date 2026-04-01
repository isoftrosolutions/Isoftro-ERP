/**
 * iSoftro ERP — Student Portal · st-attendance.js
 * Student Attendance Module
 */

window.renderSTAttendance = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div></div>';

    try {
        // Fetch summary and stats
        const [summaryRes, statsRes, calendarRes] = await Promise.all([
            fetch(`${window.APP_URL}/api/student/attendance?action=summary`),
            fetch(`${window.APP_URL}/api/student/attendance?action=stats`),
            fetch(`${window.APP_URL}/api/student/attendance?action=calendar&month=${new Date().getMonth() + 1}&year=${new Date().getFullYear()}`)
        ]);
        
        const summaryResult = await summaryRes.json();
        const statsResult = await statsRes.json();
        const calendarResult = await calendarRes.json();
        
        const summary = summaryResult.success ? (summaryResult.data?.summary || {}) : {};
        const stats = statsResult.success ? (statsResult.data || {}) : {};
        const calendar = calendarResult.success ? (calendarResult.data || {}) : {};
        
        const percentage = summary.attendance_percentage || 0;
        
        // Determine color based on percentage
        let pctColor = 'var(--sa-primary)';
        if (percentage < 50) pctColor = '#dc2626';
        else if (percentage < 75) pctColor = '#d97706';
        else pctColor = '#16a34a';
        
        const currentMonth = new Date().getMonth() + 1;
        const currentYear = new Date().getFullYear();
        
        mc.innerHTML = `
            <div style="padding:24px;">
                <!-- Header -->
                <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,var(--sa-primary),var(--sa-primary-h));color:#fff;">
                    <div class="card-body" style="padding:24px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                            <div style="display:flex;align-items:center;gap:16px;">
                                <div style="width:50px;height:50px;background:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--sa-primary);font-size:1.5rem;">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h2 style="margin:0;font-size:1.3rem;">My Attendance</h2>
                                    <p style="margin:5px 0 0;opacity:0.9;font-size:13px;">Track your attendance records</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:16px;margin-bottom:24px;">
                    <div class="card" onclick="goST('attendance')" style="cursor:pointer;">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:${pctColor};">${percentage}%</div>
                            <div style="font-size:12px;color:var(--tl);">Attendance</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#16a34a;">${summary.present_days || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Present</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#dc2626;">${summary.absent_days || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Absent</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#d97706;">${summary.late_days || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Late</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#9333ea;">${summary.leave_days || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">On Leave</div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div style="display:flex;gap:8px;margin-bottom:16px;border-bottom:1px solid var(--cb);padding-bottom:8px;">
                    <button class="btn bs" id="tab-summary" onclick="switchAttendanceTab('summary')" style="background:var(--sa-primary);color:#fff;">Summary</button>
                    <button class="btn" id="tab-daily" onclick="switchAttendanceTab('daily')" style="background:var(--bg);border:1px solid var(--cb);">Daily Record</button>
                    <button class="btn" id="tab-calendar" onclick="switchAttendanceTab('calendar')" style="background:var(--bg);border:1px solid var(--cb);">Calendar</button>
                </div>
                
                <!-- Summary Tab -->
                <div id="attendance-summary" class="attendance-tab">
                    <div class="card">
                        <div class="card-hdr"><div class="ct"><i class="fa-solid fa-chart-bar" style="margin-right:8px;color:var(--sa-primary);"></i> Monthly Breakdown</div></div>
                        <div class="card-body">
                            <table class="table">
                                <thead><tr><th>Month</th><th>Present</th><th>Absent</th><th>Late</th><th>Leave</th><th>Total</th><th>Percentage</th></tr></thead>
                                <tbody>
                                    ${summaryResult.success && summaryResult.data?.monthly_breakdown?.length > 0 ? 
                                        summaryResult.data.monthly_breakdown.map(m => {
                                            const total = parseInt(m.present || 0) + parseInt(m.absent || 0) + parseInt(m.late || 0) + parseInt(m.leave || 0);
                                            const pct = total > 0 ? Math.round(((parseInt(m.present || 0) + (parseInt(m.late || 0) * 0.5)) / total) * 100) : 0;
                                            return `<tr>
                                                <td>${m.month_name || m.month}</td>
                                                <td style="color:#16a34a;font-weight:600;">${m.present || 0}</td>
                                                <td style="color:#dc2626;font-weight:600;">${m.absent || 0}</td>
                                                <td style="color:#d97706;font-weight:600;">${m.late || 0}</td>
                                                <td style="color:#9333ea;font-weight:600;">${m.leave || 0}</td>
                                                <td>${total}</td>
                                                <td><strong style="color:${pct >= 75 ? '#16a34a' : pct >= 50 ? '#d97706' : '#dc2626'}">${pct}%</strong></td>
                                            </tr>`;
                                        }).join('') : 
                                        '<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--tl);">No attendance records found</td></tr>'
                                    }
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    ${stats.first_date ? `
                    <div class="card" style="margin-top:16px;">
                        <div class="card-hdr"><div class="ct"><i class="fa-solid fa-info-circle" style="margin-right:8px;color:var(--sa-primary);"></i> Details</div></div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;">
                                <div><div style="font-size:12px;color:var(--tl);">First Record</div><div style="font-weight:600;">${formatDate(stats.first_date)}</div></div>
                                <div><div style="font-size:12px;color:var(--tl);">Last Record</div><div style="font-weight:600;">${formatDate(stats.last_date)}</div></div>
                                <div><div style="font-size:12px;color:var(--tl);">Present Streak</div><div style="font-weight:600;color:#16a34a;">${stats.present_streak || 0} days</div></div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
                
                <!-- Daily Record Tab -->
                <div id="attendance-daily" class="attendance-tab" style="display:none;">
                    <div class="card">
                        <div class="card-hdr" style="display:flex;justify-content:space-between;align-items:center;">
                            <div class="ct"><i class="fa-solid fa-list" style="margin-right:8px;color:var(--sa-primary);"></i> Daily Attendance</div>
                            <select id="attendanceMonth" class="form-control" onchange="loadDailyAttendance()" style="padding:6px 12px;width:auto;">
                                ${generateMonthOptions(currentMonth, currentYear)}
                            </select>
                        </div>
                        <div class="card-body" id="dailyAttendanceList">
                            <div style="text-align:center;padding:30px;color:var(--tl);">
                                <i class="fa-solid fa-spinner fa-spin"></i> Loading...
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Calendar Tab -->
                <div id="attendance-calendar" class="attendance-tab" style="display:none;">
                    <div class="card">
                        <div class="card-hdr" style="display:flex;justify-content:space-between;align-items:center;">
                            <div class="ct"><i class="fa-solid fa-calendar" style="margin-right:8px;color:var(--sa-primary);"></i> Attendance Calendar</div>
                            <select id="calendarMonth" class="form-control" onchange="loadCalendarAttendance()" style="padding:6px 12px;width:auto;">
                                ${generateMonthOptions(currentMonth, currentYear)}
                            </select>
                        </div>
                        <div class="card-body">
                            <div id="calendarView"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Store data globally
        window._attendanceCalendar = calendar;
        
    } catch (e) {
        console.error('Attendance load error:', e);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><p>Error loading attendance.</p></div></div></div>';
    }
};

window.switchAttendanceTab = function(tab) {
    // Hide all tabs
    document.querySelectorAll('.attendance-tab').forEach(el => el.style.display = 'none');
    // Show selected tab
    document.getElementById(`attendance-${tab}`).style.display = 'block';
    
    // Update button styles
    document.querySelectorAll('[id^="tab-"]').forEach(btn => {
        btn.style.background = 'var(--bg)';
        btn.style.border = '1px solid var(--cb)';
        btn.style.color = 'var(--td)';
    });
    const activeBtn = document.getElementById(`tab-${tab}`);
    activeBtn.style.background = 'var(--sa-primary)';
    activeBtn.style.border = '1px solid var(--sa-primary)';
    activeBtn.style.color = '#fff';
    
    if (tab === 'daily') {
        loadDailyAttendance();
    } else if (tab === 'calendar') {
        renderCalendar();
    }
};

window.loadDailyAttendance = async function() {
    const select = document.getElementById('attendanceMonth');
    if (!select) return;
    
    const [month, year] = select.value.split('-');
    const container = document.getElementById('dailyAttendanceList');
    
    if (!container) return;
    
    container.innerHTML = '<div style="text-align:center;padding:30px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';
    
    try {
        const res = await fetch(`${window.APP_URL}/api/student/attendance?action=daily&month=${month}&year=${year}`);
        const result = await res.json();
        
        if (result.success && result.data && result.data.length > 0) {
            container.innerHTML = `
                <table class="table">
                    <thead><tr><th>Date</th><th>Status</th><th>Batch</th></tr></thead>
                    <tbody>
                        ${result.data.map(d => {
                            let statusBadge = '';
                            let statusColor = '';
                            switch(d.status) {
                                case 'present':
                                    statusBadge = '<span class="badge" style="background:#dcfce7;color:#166534;">Present</span>';
                                    break;
                                case 'absent':
                                    statusBadge = '<span class="badge" style="background:#fee2e2;color:#991b1b;">Absent</span>';
                                    break;
                                case 'late':
                                    statusBadge = '<span class="badge" style="background:#fef3c7;color:#92400e;">Late</span>';
                                    break;
                                case 'leave':
                                    statusBadge = '<span class="badge" style="background:#f3e8ff;color:#7c3aed;">On Leave</span>';
                                    break;
                            }
                            return `<tr>
                                <td>${formatDate(d.attendance_date)}</td>
                                <td>${statusBadge}</td>
                                <td>${d.batch_name || '-'}</td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            `;
        } else {
            container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--tl);">No attendance records for this month</div>';
        }
    } catch (e) {
        console.error('Error loading daily:', e);
        container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--red);">Error loading data</div>';
    }
};

window.loadCalendarAttendance = async function() {
    const select = document.getElementById('calendarMonth');
    if (!select) return;
    
    const [month, year] = select.value.split('-');
    
    try {
        const res = await fetch(`${window.APP_URL}/api/student/attendance?action=calendar&month=${month}&year=${year}`);
        const result = await res.json();
        
        if (result.success) {
            window._attendanceCalendar = result.data;
            renderCalendar();
        }
    } catch (e) {
        console.error('Error loading calendar:', e);
    }
};

function renderCalendar() {
    const select = document.getElementById('calendarMonth');
    if (!select) return;
    
    const [month, year] = select.value.split('-');
    const container = document.getElementById('calendarView');
    if (!container) return;
    
    const daysInMonth = new Date(year, month, 0).getDate();
    const firstDay = new Date(year, month - 1, 1).getDay();
    const calendar = window._attendanceCalendar || {};
    
    let html = `
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center;margin-top:16px;">
            <div style="font-weight:600;font-size:12px;padding:8px;color:var(--tl);">Sun</div>
            <div style="font-weight:600;font-size:12px;padding:8px;color:var(--tl);">Mon</div>
            <div style="font-weight:600;font-size:12px;padding:8px;color:var(--tl);">Tue</div>
            <div style="font-weight:600;font-size:12px;padding:8px;color:var(--tl);">Wed</div>
            <div style="font-weight:600;font-size:12px;padding:8px;color:var(--tl);">Thu</div>
            <div style="font-weight:600;font-size:12px;padding:8px;color:var(--tl);">Fri</div>
            <div style="font-weight:600;font-size:12px;padding:8px;color:var(--tl);">Sat</div>
    `;
    
    // Empty cells for days before first of month
    for (let i = 0; i < firstDay; i++) {
        html += '<div style="padding:10px;"></div>';
    }
    
    // Days of month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayData = calendar[day];
        let bg = 'var(--bg)';
        let color = 'var(--td)';
        let icon = '';
        
        if (dayData) {
            switch(dayData.status) {
                case 'present':
                    bg = '#dcfce7';
                    color = '#166534';
                    icon = '<i class="fa-solid fa-check"></i>';
                    break;
                case 'absent':
                    bg = '#fee2e2';
                    color = '#991b1b';
                    icon = '<i class="fa-solid fa-times"></i>';
                    break;
                case 'late':
                    bg = '#fef3c7';
                    color = '#92400e';
                    icon = '<i class="fa-solid fa-clock"></i>';
                    break;
                case 'leave':
                    bg = '#f3e8ff';
                    color = '#7c3aed';
                    icon = '<i class="fa-solid fa-plane"></i>';
                    break;
            }
        }
        
        html += `
            <div style="padding:12px;border-radius:8px;background:${bg};color:${color};text-align:center;">
                <div style="font-weight:600;">${day}</div>
                <div style="font-size:16px;margin-top:4px;">${icon}</div>
            </div>
        `;
    }
    
    html += '</div>';
    
    // Legend
    html += `
        <div style="display:flex;gap:16px;justify-content:center;margin-top:20px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;"><div style="width:16px;height:16px;background:#dcfce7;border-radius:4px;"></div><span style="font-size:12px;">Present</span></div>
            <div style="display:flex;align-items:center;gap:6px;"><div style="width:16px;height:16px;background:#fee2e2;border-radius:4px;"></div><span style="font-size:12px;">Absent</span></div>
            <div style="display:flex;align-items:center;gap:6px;"><div style="width:16px;height:16px;background:#fef3c7;border-radius:4px;"></div><span style="font-size:12px;">Late</span></div>
            <div style="display:flex;align-items:center;gap:6px;"><div style="width:16px;height:16px;background:#f3e8ff;border-radius:4px;"></div><span style="font-size:12px;">Leave</span></div>
        </div>
    `;
    
    container.innerHTML = html;
}

function generateMonthOptions(currentMonth, currentYear) {
    let options = '';
    for (let i = 0; i < 12; i++) {
        const d = new Date();
        d.setMonth(d.getMonth() - i);
        const month = d.getMonth() + 1;
        const year = d.getFullYear();
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const selected = (month === currentMonth && year === currentYear) ? 'selected' : '';
        options += `<option value="${month}-${year}" ${selected}>${monthNames[month - 1]} ${year}</option>`;
    }
    return options;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

window.renderSTAttendance = window.renderSTAttendance;
