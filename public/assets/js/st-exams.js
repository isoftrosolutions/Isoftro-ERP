/**
 * Hamro ERP — Student Portal · st-exams.js
 * Student Exams, Notices, Classes, etc. Module
 */

window.renderSTExams = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/exams`);
        const result = await res.json();
        
        mc.innerHTML = `
            <div style="padding:24px;">
                <div class="card-hdr"><div class="ct"><i class="fa-solid fa-file-alt" style="margin-right:8px;color:#dc2626;"></i> Mock Exams</div></div>
                <div class="card">
                    <div class="card-body">
                        ${result.success && result.data && result.data.length > 0 ? `
                            <table class="table">
                                <thead><tr><th>Exam Name</th><th>Date</th><th>Time</th><th>Duration</th><th>Total Marks</th></tr></thead>
                                <tbody>
                                    ${result.data.map(e => `<tr><td><strong>${e.exam_name || '-'}</strong></td><td>${e.exam_date || '-'}</td><td>${e.exam_time || '-'}</td><td>${e.duration || '-'} min</td><td>${e.total_marks || '-'}</td></tr>`).join('')}
                                </tbody>
                            </table>
                        ` : `
                            <div style="text-align:center;padding:60px;">
                                <i class="fa-solid fa-file-alt" style="font-size:4rem;color:#dc2626;opacity:0.3;margin-bottom:20px;"></i>
                                <h3>No Exams Scheduled</h3>
                                <p style="color:var(--tl);">No exams are scheduled at the moment.</p>
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    } catch (e) {
        console.error('Exams load error:', e);
    }
};

window.renderSTResults = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading results...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/exams?action=results`);
        const result = await res.json();
        
        if (result.success && result.data) {
            const d = result.data;
            mc.innerHTML = `
                <div style="padding:24px;">
                    <div class="card-hdr"><div class="ct"><i class="fa-solid fa-trophy" style="margin-right:8px;color:#d97706;"></i> My Exam Results</div></div>
                    
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px;">
                        <div class="card"><div style="padding:20px;text-align:center;"><div style="font-size:2rem;font-weight:800;color:#d97706;">${d.stats.average_percentage}%</div><div style="font-size:13px;color:var(--tl);">Average Score</div></div></div>
                        <div class="card"><div style="padding:20px;text-align:center;"><div style="font-size:2rem;font-weight:800;color:#16a34a;">${d.stats.passed}</div><div style="font-size:13px;color:var(--tl);">Exams Passed</div></div></div>
                        <div class="card"><div style="padding:20px;text-align:center;"><div style="font-size:2rem;font-weight:800;color:#dc2626;">${d.stats.failed}</div><div style="font-size:13px;color:var(--tl);">Exams Failed</div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <table class="table">
                                <thead><tr><th>Exam</th><th>Subject</th><th>Date</th><th>Marks</th><th>Percentage</th><th>Status</th></tr></thead>
                                <tbody>
                                    ${d.results && d.results.length > 0 ? d.results.map(r => {
                                        const pct = ((r.marks_obtained / r.total_marks) * 100).toFixed(1);
                                        const passed = r.marks_obtained >= r.passing_marks;
                                        return `<tr><td><strong>${r.exam_title || '-'}</strong></td><td>${r.subject_name || '-'}</td><td>${r.exam_date || '-'}</td><td>${r.marks_obtained}/${r.total_marks}</td><td>${pct}%</td><td><span class="badge ${passed ? 'badge-green' : 'badge-red'}">${passed ? 'Pass' : 'Fail'}</span></td></tr>`;
                                    }).join('') : '<tr><td colspan="6" style="text-align:center;padding:30px;">No results found</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } else {
            mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:60px;"><i class="fa-solid fa-trophy" style="font-size:4rem;color:#d97706;opacity:0.3;margin-bottom:20px;"></i><h3>No Results</h3><p style="color:var(--tl);">' + (result.message || 'Your exam results will be displayed here once published.') + '</p></div></div></div>';
        }
    } catch (e) {
        console.error('Results load error:', e);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><p>Error loading results.</p></div></div></div>';
    }
};

window.renderSTLeaderboard = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading leaderboard...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/exams?action=leaderboard`);
        const result = await res.json();
        
        if (result.success && result.data) {
            mc.innerHTML = `
                <div style="padding:24px;">
                    <div class="card-hdr"><div class="ct"><i class="fa-solid fa-medal" style="margin-right:8px;color:#8141A5;"></i> Batch Leaderboard</div></div>
                    <div class="card">
                        <div class="card-body">
                            <table class="table">
                                <thead><tr><th>Rank</th><th>Student</th><th>Roll No</th><th>Average Percentage</th></tr></thead>
                                <tbody>
                                    ${result.data.length > 0 ? result.data.map((r, idx) => `
                                        <tr style="${result.my_position === (idx+1) ? 'background:var(--sa-primary-lt); font-weight:700;' : ''}">
                                            <td>${idx + 1}</td>
                                            <td><div style="display:flex;align-items:center;gap:10px;"><div style="width:30px;height:30px;border-radius:50%;background:var(--cb);display:flex;align-items:center;justify-content:center;font-size:12px;">${r.student_name.charAt(0)}</div> ${r.student_name} ${result.my_position === (idx+1) ? '<span class="badge badge-primary">You</span>' : ''}</div></td>
                                            <td>${r.roll_no || '-'}</td>
                                            <td>${parseFloat(r.average_percentage).toFixed(2)}%</td>
                                        </tr>
                                    `).join('') : '<tr><td colspan="4" style="text-align:center;padding:30px;">No leaderboard data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } else {
            mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:60px;"><i class="fa-solid fa-medal" style="font-size:4rem;color:#8141A5;opacity:0.3;margin-bottom:20px;"></i><h3>Leaderboard</h3><p style="color:var(--tl);">' + (result.message || 'Leaderboard will be available once results are published.') + '</p></div></div></div>';
        }
    } catch (e) {
        console.error('Leaderboard load error:', e);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><p>Error loading leaderboard.</p></div></div></div>';
    }
};

window.renderSTExams = window.renderSTExams;
window.renderSTResults = window.renderSTResults;
window.renderSTLeaderboard = window.renderSTLeaderboard;

