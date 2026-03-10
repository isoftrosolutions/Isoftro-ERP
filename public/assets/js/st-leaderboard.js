/**
 * Hamro ERP — Student Portal · st-leaderboard.js
 * Student Performance Leaderboard
 */

window.renderSTLeaderboard = async function(batchId = null) {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `
        <div class="p-4 d-flex justify-content-center align-items-center" style="min-height:300px;">
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div class="text-muted small fw-medium">Calculating Rankings...</div>
            </div>
        </div>
    `;

    try {
        const url = new URL(`${window.APP_URL}/api/student/leaderboard`);
        if (batchId) url.searchParams.set('batch_id', batchId);
        
        const res = await fetch(url);
        const result = await res.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to load leaderboard');
        }

        const data = result.data || [];
        const myRank = result.my_rank || null;
        const batches = result.batches || [];
        const currentBatchId = result.batch_id;

        mc.innerHTML = `
            <div class="container-fluid p-4" style="max-width: 1100px;">
                <!-- Header Card -->
                <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-radius: 1.25rem;">
                    <div class="card-body p-0">
                        <div class="p-4 p-md-5 text-white position-relative" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                            <div class="position-relative z-1">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="bg-white bg-opacity-20 rounded-4 p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; backdrop-filter: blur(8px);">
                                        <i class="fa-solid fa-trophy fs-3 text-warning"></i>
                                    </div>
                                    <div>
                                        <h2 class="h3 fw-bold mb-1">Academic Leaderboard</h2>
                                        <p class="mb-0 opacity-75 small">Composite ranking: 70% Exam Scores + 30% Attendance</p>
                                    </div>
                                </div>

                                ${myRank ? `
                                    <div class="bg-white bg-opacity-10 rounded-4 p-3 mt-4 d-inline-block border border-white border-opacity-10" style="backdrop-filter: blur(12px);">
                                        <div class="d-flex align-items-center gap-4 px-2">
                                            <div>
                                                <div class="small opacity-75 mb-1">Your Rank</div>
                                                <div class="h4 fw-bold mb-0">#${myRank.rank} <span class="small fw-normal opacity-75">/ ${data.length}</span></div>
                                            </div>
                                            <div style="width: 1px; height: 35px; background: rgba(255,255,255,0.2);"></div>
                                            <div>
                                                <div class="small opacity-75 mb-1">Your Score</div>
                                                <div class="h4 fw-bold mb-0">${myRank.composite_score}</div>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                            <!-- Background Decoration -->
                            <i class="fa-solid fa-medal position-absolute opacity-10" style="right: -20px; bottom: -20px; font-size: 15rem; transform: rotate(-15deg);"></i>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Batch Selector & Filters -->
                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm mb-4" style="border-radius: 1rem;">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                                <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-layer-group text-primary"></i>
                                    Switch Batch
                                </h6>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="d-flex flex-column gap-2 mt-2">
                                    ${batches.map(b => `
                                        <button onclick="renderSTLeaderboard(${b.id})" 
                                            class="btn text-start p-3 rounded-4 d-flex align-items-center justify-content-between transition-all ${currentBatchId == b.id ? 'bg-primary-soft border-primary' : 'bg-light border-transparent'}"
                                            style="border: 1px solid ${currentBatchId == b.id ? 'var(--sa-primary)' : 'transparent'};">
                                            <div class="min-width-0">
                                                <div class="fw-bold small text-dark mb-0 truncate">${b.name}</div>
                                                <div class="text-muted smaller">${b.course_name || 'Regular Batch'}</div>
                                            </div>
                                            ${currentBatchId == b.id ? '<i class="fa-solid fa-circle-check text-primary"></i>' : ''}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                        </div>

                        <!-- Top 3 Preview / Hall of Fame -->
                        ${data.slice(0, 3).length > 0 ? `
                            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 1rem; background: #fafafa;">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold mb-4 text-center">Top Performers</h6>
                                    <div class="d-flex align-items-end justify-content-center gap-2 pt-4">
                                        <!-- Rank 2 -->
                                        ${data[1] ? `
                                            <div class="text-center" style="width: 30%;">
                                                <div class="position-relative d-inline-block mb-2">
                                                    <img src="${data[1].photo_url || `${window.APP_URL}/public/assets/img/avatar.png`}" 
                                                        class="rounded-circle border border-2 border-primary border-opacity-25 p-1 bg-white" 
                                                        style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div class="position-absolute bottom-0 start-50 translate-middle-x bg-secondary text-white rounded-circle shadow-sm" style="width: 22px; height: 22px; font-size: 10px; line-height: 22px;">2</div>
                                                </div>
                                                <div class="small fw-bold truncate mb-1">${data[1].full_name.split(' ')[0]}</div>
                                                <div class="bg-secondary bg-opacity-10 text-secondary rounded-top p-2" style="height: 60px;">
                                                    <div class="fw-bold fs-5">${data[1].composite_score}</div>
                                                </div>
                                            </div>
                                        ` : ''}

                                        <!-- Rank 1 -->
                                        ${data[0] ? `
                                            <div class="text-center" style="width: 40%;">
                                                <div class="position-relative d-inline-block mb-3">
                                                    <i class="fa-solid fa-crown text-warning position-absolute translate-middle" style="top: -10px; left: 50%; font-size: 1.25rem; transform: rotate(15deg);"></i>
                                                    <img src="${data[0].photo_url || `${window.APP_URL}/public/assets/img/avatar.png`}" 
                                                        class="rounded-circle border border-4 border-warning p-1 bg-white shadow" 
                                                        style="width: 85px; height: 85px; object-fit: cover;">
                                                    <div class="position-absolute bottom-0 start-50 translate-middle-x bg-warning text-dark rounded-circle shadow-sm border border-2 border-white" style="width: 28px; height: 28px; font-size: 13px; line-height: 24px; font-weight: 900;">1</div>
                                                </div>
                                                <div class="fw-bold truncate mb-1 text-dark">${data[0].full_name.split(' ')[0]}</div>
                                                <div class="bg-warning text-dark rounded-top p-2 shadow-sm" style="height: 85px; background: linear-gradient(to bottom, #fcd34d, #f59e0b);">
                                                    <div class="fw-bold fs-4">${data[0].composite_score}</div>
                                                    <div class="smaller opacity-75 fw-bold text-uppercase">Score</div>
                                                </div>
                                            </div>
                                        ` : ''}

                                        <!-- Rank 3 -->
                                        ${data[2] ? `
                                            <div class="text-center" style="width: 30%;">
                                                <div class="position-relative d-inline-block mb-2">
                                                    <img src="${data[2].photo_url || `${window.APP_URL}/public/assets/img/avatar.png`}" 
                                                        class="rounded-circle border border-2 border-warning border-opacity-25 p-1 bg-white" 
                                                        style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div class="position-absolute bottom-0 start-50 translate-middle-x bg-danger bg-opacity-75 text-white rounded-circle shadow-sm" style="width: 22px; height: 22px; font-size: 10px; line-height: 22px;">3</div>
                                                </div>
                                                <div class="small fw-bold truncate mb-1">${data[2].full_name.split(' ')[0]}</div>
                                                <div class="bg-danger bg-opacity-10 text-danger rounded-top p-2" style="height: 45px;">
                                                    <div class="fw-bold fs-5">${data[2].composite_score}</div>
                                                </div>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Rankings List -->
                    <div class="col-12 col-lg-8">
                        <div class="card border-0 shadow-sm h-100" style="border-radius: 1rem; overflow: hidden;">
                            <div class="card-header bg-white border-0 pt-4 px-4">
                                <h6 class="fw-bold mb-0">Rankings Overview</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="border-0 px-4 py-3 text-muted small fw-bold">RANK</th>
                                                <th class="border-0 px-4 py-3 text-muted small fw-bold">STUDENT</th>
                                                <th class="border-0 px-4 py-3 text-center text-muted small fw-bold">EXAMS</th>
                                                <th class="border-0 px-4 py-3 text-center text-muted small fw-bold">ATTENDANCE</th>
                                                <th class="border-0 px-4 py-3 text-end text-muted small fw-bold">TOTAL SCORE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.map(r => `
                                                <tr class="${r.is_me ? 'bg-primary-soft' : ''}">
                                                    <td class="px-4 py-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            ${r.rank === 1 ? '<i class="fa-solid fa-trophy text-warning"></i>' : 
                                                              r.rank === 2 ? '<i class="fa-solid fa-medal text-secondary"></i>' :
                                                              r.rank === 3 ? '<i class="fa-solid fa-medal text-danger opacity-75"></i>' : 
                                                              `<span class="text-muted fw-bold small ms-1">${r.rank}</span>`}
                                                            ${r.rank <= 3 ? `<span class="fw-bold ${r.rank === 1 ? 'text-warning' : r.rank === 2 ? 'text-secondary' : 'text-danger'}">#${r.rank}</span>` : ''}
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="position-relative">
                                                                <img src="${r.photo_url || `${window.APP_URL}/public/assets/img/avatar.png`}" 
                                                                    class="rounded-circle bg-light border p-1" 
                                                                    style="width: 42px; height: 42px; object-fit: cover;">
                                                                ${r.is_me ? '<span class="position-absolute bottom-0 end-0 bg-success border border-white border-2 rounded-circle" style="width: 12px; height: 12px;"></span>' : ''}
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                                                    ${r.full_name}
                                                                    ${r.is_me ? '<span class="badge bg-primary rounded-pill smaller" style="font-size: 8px;">YOU</span>' : ''}
                                                                </div>
                                                                <div class="text-muted smaller">Roll No: ${r.roll_no || 'NA'}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <div class="fw-bold text-dark">${r.avg_score}%</div>
                                                        <div class="smaller text-muted">${r.exams_taken} exams</div>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <div class="fw-bold text-dark">${r.attendance_pct}%</div>
                                                        <div class="progress mt-1 mx-auto shadow-none bg-light" style="height: 4px; width: 60px; border-radius: 10px;">
                                                            <div class="progress-bar ${r.attendance_pct >= 75 ? 'bg-success' : r.attendance_pct >= 50 ? 'bg-warning' : 'bg-danger'}" role="progressbar" style="width: ${r.attendance_pct}%"></div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-end">
                                                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 px-3 py-1 d-inline-block fw-bold fs-5">
                                                            ${r.composite_score}
                                                        </div>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                ${data.length === 0 ? `
                                    <div class="text-center py-5">
                                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                            <i class="fa-solid fa-users-slash fs-2 text-muted"></i>
                                        </div>
                                        <p class="text-muted">No rankings available for this batch yet.</p>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .bg-primary-soft { background-color: rgba(79, 70, 229, 0.05); }
                .text-primary { color: #4f46e5 !important; }
                .bg-primary { background-color: #4f46e5 !important; }
                .border-primary { border-color: #4f46e5 !important; }
                .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                .smaller { font-size: 11px; }
                .transition-all { transition: all 0.2s ease-in-out; }
            </style>
        `;

    } catch (e) {
        console.error('Leaderboard error:', e);
        mc.innerHTML = `
            <div class="container p-5 text-center">
                <div class="alert alert-danger shadow-sm py-4 rounded-4 border-0">
                    <i class="fa-solid fa-triangle-exclamation fs-1 mb-3 d-block"></i>
                    <h5 class="fw-bold">Unable to load rankings</h5>
                    <p class="mb-4 opacity-75">${e.message}</p>
                    <button onclick="renderSTLeaderboard()" class="btn btn-dark rounded-pill px-4">Try Again</button>
                </div>
            </div>
        `;
    }
};
