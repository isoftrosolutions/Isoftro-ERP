/**
 * iSoftro ERP — Institute Admin · ia-reports.js
 * Consolidated Analytics and Reporting Modules
 */

window.renderExamAnalytics = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    _iaRenderBreadcrumb([
        { label: 'Reports', link: "javascript:goNav('reports','fee-rep')" },
        { label: 'Exam Performance' }
    ]);

    mc.innerHTML = `
        <div class="pg fu">
            <div class="pg-head">
                <div class="pg-left">
                    <div class="pg-ico" style="background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;">
                        <i class="fa-solid fa-square-poll-vertical"></i>
                    </div>
                    <div>
                        <div class="pg-title">Examination Analytics</div>
                        <div class="pg-sub">Track student performance trends and subject-wise metrics</div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 24px; text-align: center; padding: 100px 40px;">
                <div style="margin-bottom: 24px;">
                    <i class="fa-solid fa-chart-line" style="font-size: 4rem; color: var(--brand); opacity: 0.2;"></i>
                </div>
                <h2 style="color: var(--text-dark); font-weight: 800;">Academic Intelligence Dashboard</h2>
                <p style="color: var(--text-light); max-width: 500px; margin: 10px auto 30px;">
                    We are currently aggregating examination data for this session. 
                    Performance curves and ranking distributions will be available once the finals are recorded.
                </p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button class="btn bt" onclick="goNav('exams','schedule')">Manage Exams</button>
                    <button class="btn bs" onclick="goNav('exams','results')">View Results</button>
                </div>
            </div>
        </div>
    `;
};

window.renderCustomReports = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    _iaRenderBreadcrumb([
        { label: 'Reports', link: "javascript:goNav('reports','fee-rep')" },
        { label: 'Custom Builder' }
    ]);

    mc.innerHTML = `
        <div class="pg fu">
             <div class="pg-head">
                <div class="pg-left">
                    <div class="pg-ico" style="background: linear-gradient(135deg, #00b894, #00cec9); color: #fff;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <div class="pg-title">Custom Report Builder</div>
                        <div class="pg-sub">Design tailored reports by selecting dimensions and metrics</div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 24px; border: 2px dashed var(--card-border); background: #f8fafc; border-radius: 24px;">
                <div style="padding: 120px 40px; text-align: center;">
                    <div style="position: relative; display: inline-block; margin-bottom: 30px;">
                        <i class="fa-solid fa-gears" style="font-size: 5rem; color: var(--brand); opacity: 0.1;"></i>
                        <i class="fa-solid fa-tools" style="position: absolute; bottom: 0; right: -10px; font-size: 2rem; color: var(--brand);"></i>
                    </div>
                    <h2 style="color: var(--text-dark); font-weight: 900; letter-spacing: -0.02em;">Report Engine Under Maintenance</h2>
                    <p style="color: var(--text-light); font-size: 15px; max-width: 450px; margin: 15px auto;">
                        Our "Dynamic Query Engine" is being updated to support 1-click Excel exports and PDF data visualizations.
                    </p>
                    <div class="badge-pill" style="display: inline-flex; background: rgba(0, 184, 148, 0.1); color: var(--brand); font-weight: 800; padding: 8px 20px; border-radius: 99px; margin-top: 20px;">
                        Coming in Release v3.4 (Next Month)
                    </div>
                </div>
            </div>
        </div>
    `;
};
