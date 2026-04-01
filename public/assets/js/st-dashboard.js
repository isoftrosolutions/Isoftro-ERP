/**
 * iSoftro ERP — Student Portal · st-dashboard.js
 * Student Dashboard Module
 */

window.renderSTDashboard = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/dashboard`);
        const result = await res.json();

        if (!result.success) {
            mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><h3>Error Loading Dashboard</h3><p>' + (result.message || 'Unable to load your dashboard.') + '</p></div></div></div>';
            return;
        }

        const d = result.data;
        const si = d.student_info || {};
        const currency = window.getCurrencySymbol();

        mc.innerHTML = `
            <!-- Welcome Banner -->
            <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,var(--sa-primary),var(--sa-primary-h));color:#fff;">
                <div class="card-body" style="padding:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                        <div style="display:flex;align-items:center;gap:20px;">
                            <div style="width:70px;height:70px;background:#fff;border-radius:15px;display:flex;align-items:center;justify-content:center;color:var(--sa-primary);font-size:1.8rem;font-weight:800;box-shadow:0 10px 20px rgba(0,0,0,0.1);">
                                ${si.full_name ? si.full_name.charAt(0) : 'S'}
                            </div>
                            <div>
                                <h2 style="margin:0;font-size:1.5rem;">Welcome back, ${si.full_name || 'Student'}! 👋</h2>
                                <p style="margin:8px 0 0;opacity:0.9;">${si.course_name || ''} • ${si.batch_name || ''}</p>
                                <p style="margin:4px 0 0;font-size:13px;opacity:0.8;">Roll No: ${si.roll_no || 'N/A'}</p>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:1.5rem;font-weight:800;">${new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}</div>
                            <div style="opacity:0.8;font-size:13px;margin-top:5px;"><i class="fa-solid fa-location-dot"></i> ${si.institute_name || 'iSoftro ERP'}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
                <div class="card" onclick="goST('attendance')" style="cursor:pointer;transition:0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="padding:20px;display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon ic-green"><i class="fa-solid fa-calendar-check"></i></div>
                        <div><div class="stat-val">${d.attendance_summary?.percentage || 0}%</div><div class="stat-lbl">Attendance</div></div>
                    </div>
                </div>
                <div class="card" onclick="goST('assignments')" style="cursor:pointer;transition:0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="padding:20px;display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon ic-amber"><i class="fa-solid fa-tasks"></i></div>
                        <div><div class="stat-val">${d.pending_assignments || 0}</div><div class="stat-lbl">Pending Assignments</div></div>
                    </div>
                </div>
                <div class="card" onclick="goST('exams')" style="cursor:pointer;transition:0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="padding:20px;display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon ic-red"><i class="fa-solid fa-file-alt"></i></div>
                        <div><div class="stat-val">${d.upcoming_exams?.length || 0}</div><div class="stat-lbl">Upcoming Exams</div></div>
                    </div>
                </div>
                <div class="card" onclick="goST('fees')" style="cursor:pointer;transition:0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="padding:20px;display:flex;align-items:center;gap:16px;">
                        <div class="stat-icon ic-purple"><i class="fa-solid fa-money-bill-wave"></i></div>
                        <div><div class="stat-val">${currency}${parseInt(d.fee_summary?.outstanding || 0).toLocaleString()}</div><div class="stat-lbl">Fee Balance</div></div>
                    </div>
                </div>
            </div>

            <div class="g65">
                <!-- Today's Classes -->
                <div class="card">
                    <div class="card-hdr" style="display:flex;justify-content:space-between;align-items:center;">
                        <div class="ct"><i class="fa-solid fa-calendar-day" style="margin-right:8px;color:var(--sa-primary);"></i> Today's Timetable</div>
                        <button class="btn bs btn-sm" onclick="goST('timetable')">View All</button>
                    </div>
                    <div class="card-body">
                        ${d.today_classes && d.today_classes.length > 0 ? d.today_classes.map(c => `
                            <div style="display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--cb);border-radius:12px;margin-bottom:12px;background:var(--bg-lt);">
                                <div style="text-align:center;min-width:65px;padding-right:12px;border-right:1px solid var(--cb);">
                                    <div style="font-weight:700;color:var(--td);font-size:14px;">${c.start_time ? c.start_time.substring(0, 5) : '--:--'}</div>
                                    <div style="font-size:11px;color:var(--tl);">${c.end_time ? c.end_time.substring(0, 5) : '--:--'}</div>
                                </div>
                                <div style="flex:1;">
                                    <div style="font-weight:600;color:var(--td);">${c.subject_name || 'Subject'}</div>
                                    <div style="font-size:12px;color:var(--tl);">${c.teacher_name || 'Teacher'} • ${c.room || 'Room'}</div>
                                </div>
                                <div>
                                    ${c.class_type === 'online' ? 
                                        `<span class="badge badge-purple"><i class="fa-solid fa-video"></i> Online</span>` : 
                                        `<span class="badge badge-blue"><i class="fa-solid fa-building"></i> Physical</span>`
                                    }
                                </div>
                            </div>
                        `).join('') : `
                            <div style="text-align:center;padding:40px;color:var(--tl);">
                                <i class="fa-solid fa- mug-hot" style="font-size:2.5rem;margin-bottom:15px;opacity:0.5;"></i>
                                <p>No classes scheduled for today. Enjoy your day!</p>
                            </div>
                        `}
                    </div>
                </div>

                <!-- Recent Notices -->
                <div class="card">
                    <div class="card-hdr" style="display:flex;justify-content:space-between;align-items:center;">
                        <div class="ct"><i class="fa-solid fa-bullhorn" style="margin-right:8px;color:var(--amber);"></i> Recent Notices</div>
                        <button class="btn bs btn-sm" onclick="goST('notices')">View All</button>
                    </div>
                    <div class="card-body">
                        ${d.recent_notices && d.recent_notices.length > 0 ? d.recent_notices.map(n => `
                            <div style="padding:12px;border-bottom:1px solid var(--cb);display:flex;gap:15px;">
                                <div style="width:10px;height:10px;border-radius:50%;background:${n.priority === 'high' ? 'var(--red)' : 'var(--blue)'};margin-top:5px;"></div>
                                <div style="flex:1;">
                                    <div style="font-weight:600;color:var(--td);">${n.title}</div>
                                    <div style="font-size:11px;color:var(--tl);margin-top:4px;">${new Date(n.created_at).toLocaleDateString()} • ${n.posted_by || 'Admin'}</div>
                                </div>
                            </div>
                        `).join('') : '<p style="text-align:center;color:var(--tl);padding:20px;">No recent notices</p>'}
                    </div>
                </div>
            </div>
        `;

    } catch (error) {
        console.error('Dashboard load error:', error);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><p>Error loading dashboard.</p></div></div></div>';
    }
};

window.renderSTDashboard = window.renderSTDashboard;
