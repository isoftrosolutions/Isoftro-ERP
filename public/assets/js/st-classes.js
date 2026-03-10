/**
 * Hamro ERP — Student Portal · st-classes.js
 * Timetable & Online Classes Module
 */

window.renderSTTimetable = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading timetable...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/classes?action=weekly`);
        const result = await res.json();

        if (!result.success) {
            mc.innerHTML = `<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><h3>Error</h3><p>${result.message || 'Unable to load timetable.'}</p></div></div></div>`;
            return;
        }

        const weekly = result.data;
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const today = new Date().toLocaleDateString('en-US', { weekday: 'long' });

        let html = `
            <div style="padding:24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                    <div>
                        <h2 style="margin:0;font-size:1.5rem;color:var(--td);">Weekly Timetable</h2>
                        <p style="margin:5px 0 0;color:var(--tl);">Your scheduled classes for the week</p>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button class="btn bs" onclick="renderSTClasses()"><i class="fa-solid fa-video"></i> Online Classes</button>
                    </div>
                </div>

                <div class="timetable-container" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:20px;">
        `;

        days.forEach(day => {
            const sessions = weekly[day] || [];
            const isToday = day === today;

            html += `
                <div class="card" style="${isToday ? 'border:2px solid var(--sa-primary);' : ''}">
                    <div class="card-hdr" style="display:flex;justify-content:space-between;align-items:center;background:${isToday ? 'var(--sa-primary-lt)' : 'transparent'}">
                        <div class="ct" style="color:${isToday ? 'var(--sa-primary)' : 'var(--td)'}">${day}</div>
                        ${isToday ? '<span class="badge badge-green">Today</span>' : ''}
                    </div>
                    <div class="card-body" style="padding:15px;">
                        ${sessions.length > 0 ? sessions.map(s => `
                            <div style="display:flex;gap:12px;padding:12px;border:1px solid var(--cb);border-radius:12px;margin-bottom:10px;background:var(--bg);">
                                <div style="text-align:center;min-width:60px;padding-right:12px;border-right:1px solid var(--cb);">
                                    <div style="font-weight:700;color:var(--td);font-size:13px;">${_stFmtTime(s.start_time)}</div>
                                    <div style="font-size:11px;color:var(--tl);">${_stFmtTime(s.end_time)}</div>
                                </div>
                                <div style="flex:1;">
                                    <div style="font-weight:600;color:var(--td);">${s.subject_name || 'Subject'}</div>
                                    <div style="font-size:12px;color:var(--tl);display:flex;align-items:center;gap:5px;margin-top:4px;">
                                        <i class="fa-solid fa-user-tie" style="font-size:10px;"></i> ${s.teacher_name || 'Staff'}
                                    </div>
                                    <div style="font-size:11px;margin-top:8px;display:flex;justify-content:space-between;align-items:center;">
                                        <span class="badge ${s.class_type === 'online' ? 'badge-purple' : 'badge-blue'}" style="font-size:10px;">
                                            <i class="fa-solid ${s.class_type === 'online' ? 'fa-video' : 'fa-building'}"></i> ${s.class_type === 'online' ? 'Online' : (s.room_name || 'Physical')}
                                        </span>
                                        ${s.online_link ? `<a href="${s.online_link}" target="_blank" style="color:var(--sa-primary);text-decoration:none;font-weight:600;"><i class="fa-solid fa-link"></i> Join</a>` : ''}
                                    </div>
                                </div>
                            </div>
                        `).join('') : '<p style="text-align:center;color:var(--tl);padding:20px;font-size:13px;">No classes scheduled</p>'}
                    </div>
                </div>
            `;
        });

        html += `</div></div>`;
        mc.innerHTML = html;

    } catch (error) {
        console.error('Timetable load error:', error);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><p>Error loading timetable.</p></div></div></div>';
    }
};

window.renderSTClasses = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading online classes...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/classes?action=weekly`);
        const result = await res.json();

        if (!result.success) {
            mc.innerHTML = `<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><h3>Error</h3><p>${result.message || 'Unable to load classes.'}</p></div></div></div>`;
            return;
        }

        const weekly = result.data;
        const onlineClasses = [];
        
        Object.keys(weekly).forEach(day => {
            weekly[day].forEach(s => {
                if (s.class_type === 'online' || s.online_link) {
                    onlineClasses.push({ ...s, day });
                }
            });
        });

        let html = `
            <div style="padding:24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                    <div>
                        <h2 style="margin:0;font-size:1.5rem;color:var(--td);">Online Classes</h2>
                        <p style="margin:5px 0 0;color:var(--tl);">Join your live virtual classrooms</p>
                    </div>
                    <button class="btn bs" onclick="renderSTTimetable()"><i class="fa-solid fa-calendar-alt"></i> Back to Timetable</button>
                </div>

                <div class="card">
                    <div class="card-body" style="padding:0;">
                        <div class="table-responsive">
                            <table class="table" style="margin:0;">
                                <thead style="background:var(--bg-lt);">
                                    <tr>
                                        <th style="padding:15px;">Subject</th>
                                        <th style="padding:15px;">Day</th>
                                        <th style="padding:15px;">Time</th>
                                        <th style="padding:15px;">Teacher</th>
                                        <th style="padding:15px;text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${onlineClasses.length > 0 ? onlineClasses.map(c => `
                                        <tr>
                                            <td style="padding:15px;">
                                                <div style="font-weight:600;color:var(--td);">${c.subject_name || 'Subject'}</div>
                                                <div style="font-size:11px;color:var(--tl);">${c.subject_code || ''}</div>
                                            </td>
                                            <td style="padding:15px;color:var(--td);">${c.day}</td>
                                            <td style="padding:15px;color:var(--sa-primary);font-weight:600;">${_stFmtTime(c.start_time)} - ${_stFmtTime(c.end_time)}</td>
                                            <td style="padding:15px;color:var(--tl);">${c.teacher_name || '-'}</td>
                                            <td style="padding:15px;text-align:right;">
                                                ${c.online_link ? `
                                                    <a href="${c.online_link}" target="_blank" class="btn bt btn-sm">
                                                        <i class="fa-solid fa-video"></i> Join Now
                                                    </a>
                                                ` : '<span style="color:var(--tl);font-style:italic;font-size:12px;">Link not available</span>'}
                                            </td>
                                        </tr>
                                    `).join('') : `
                                        <tr>
                                            <td colspan="5" style="padding:50px;text-align:center;color:var(--tl);">
                                                <i class="fa-solid fa-video-slash" style="font-size:2rem;margin-bottom:15px;display:block;"></i>
                                                No online classes scheduled for this batch.
                                            </td>
                                        </tr>
                                    `}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
        mc.innerHTML = html;

    } catch (error) {
        console.error('Online classes load error:', error);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><p>Error loading online classes.</p></div></div></div>';
    }
};

function _stFmtTime(t) {
    if (!t) return '--:--';
    const p = t.split(':');
    let h = parseInt(p[0]);
    const m = p[1];
    const ap = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${m} ${ap}`;
}
