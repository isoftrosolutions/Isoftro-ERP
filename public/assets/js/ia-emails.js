/**
 * iSoftro ERP — ia-emails.js (The EMAIL Module)
 * Premium Mass-Emailing & Campaigns
 */
window.renderEmailModule = async function() {
    const mc = document.getElementById('mainContent');
    
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Email Module</span></div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);"><i class="fa-solid fa-paper-plane"></i></div>
                <div><div class="pg-title">Email Module</div><div class="pg-sub">Broadcast messages and announcements to students and staff</div></div>
            </div>
            <div class="pg-right">
                <button class="btn bt" onclick="_renderNewCampaign()"><i class="fa-solid fa-plus"></i> New Campaign</button>
            </div>
        </div>

        <div style="max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1fr 350px;gap:24px;align-items:start;">
            
            <!-- Left: Campaign History -->
            <div class="card" style="padding:0;overflow:hidden;border:none;box-shadow:0 4px 20px rgba(0,0,0,0.05);">
                <div style="padding:20px 24px;background:#f8fafc;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                    <h3 style="margin:0;font-size:16px;color:#1e293b;font-weight:700;">Recent Campaigns</h3>
                    <div class="bdg bg-t" style="font-size:11px;">Track status & delivery</div>
                </div>
                <div id="campaignHistory" style="min-height:400px;padding:10px;">
                    <div style="text-align:center;padding:80px 0;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:#cbd5e1;"></i></div>
                </div>
            </div>

            <!-- Right: Stats / Info -->
            <div style="display:flex;flex-direction:column;gap:24px;">
                <div class="card" style="padding:25px;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;">
                    <div style="font-size:13px;opacity:.8;margin-bottom:5px;">Total Emails Sent (Lifetime)</div>
                    <div style="font-size:32px;font-weight:800;" id="totalSentCount">0</div>
                    <div style="margin-top:20px;height:4px;background:rgba(255,255,255,0.2);border-radius:2px;overflow:hidden;"><div style="width:75%;height:100%;background:#fff;"></div></div>
                    <div style="margin-top:10px;font-size:12px;opacity:.7;">You have reached 75% of your quota for this month.</div>
                </div>

                <div class="card" style="padding:25px;">
                    <h4 style="margin:0 0 15px;font-size:14px;font-weight:700;color:#334155;">Sending Power</h4>
                    <p style="font-size:13px;color:#64748b;line-height:1.6;">Our email module uses a distributed queue system to ensure high delivery rates and prevents your messages from being flagged as SPAM.</p>
                    <div style="margin-top:20px;display:flex;flex-direction:column;gap:12px;">
                        <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#1e293b;"><i class="fa-solid fa-check-circle" style="color:#10b981;"></i> Scheduled Delivery</div>
                        <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#1e293b;"><i class="fa-solid fa-check-circle" style="color:#10b981;"></i> Dynamic Placeholders</div>
                        <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#1e293b;"><i class="fa-solid fa-check-circle" style="color:#10b981;"></i> Attachment Support</div>
                    </div>
                </div>
            </div>

        </div>
    </div>`;

    await _loadCampaignHistory();
};

async function _loadCampaignHistory() {
    const listDiv = document.getElementById('campaignHistory');
    try {
        const res = await fetch(APP_URL + '/api/admin/communications?action=list_campaigns');
        const r = await res.json();
        
        if (r.success && r.data) {
            document.getElementById('totalSentCount').textContent = r.meta?.total_sent || 0;
            if (r.data.length === 0) {
                listDiv.innerHTML = `<div style="text-align:center;padding:100px 0;color:#94a3b8;"><i class="fa-solid fa-inbox" style="font-size:3rem;display:block;margin-bottom:15px;opacity:.3"></i><p>No campaigns sent yet.</p></div>`;
                return;
            }

            listDiv.innerHTML = r.data.map(c => `
                <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;color:#1e293b;font-size:15px;margin-bottom:3px;">${c.campaign_name}</div>
                        <div style="font-size:12px;color:#64748b;">Sent to: <span style="font-weight:600;color:#6366f1;">${c.target_label}</span> &bull; ${new Date(c.created_at).toLocaleString()}</div>
                    </div>
                    <div style="text-align:right;">
                        <span class="bdg bg-${c.status === 'completed' ? 't' : 'w'}" style="font-size:10px;">${c.status.toUpperCase()}</span>
                        <div style="font-size:11px;color:#94a3b8;margin-top:4px;">${c.sent_count}/${c.total_recipients} Delivered</div>
                    </div>
                </div>
            `).join('');
        }
    } catch (e) { listDiv.innerHTML = `<div style="padding:40px;text-align:center;color:#ef4444;">Failed to load campaigns</div>`; }
}

window._renderNewCampaign = async function() {
    const { value: formValues } = await Swal.fire({
        title: 'Launch New Campaign',
        html: `
        <div style="text-align:left;padding:10px 0;">
            <div class="form-group mb" style="margin-bottom:15px;">
                <label class="form-label" style="font-weight:600;font-size:12px;color:#64748b;">Campaign Name</label>
                <input id="cpName" class="form-control" placeholder="e.g. Winter Break Announcement" style="padding:10px;font-size:14px;">
            </div>
            <div class="form-group mb" style="margin-bottom:15px;">
                <label class="form-label" style="font-weight:600;font-size:12px;color:#64748b;">Recipient Group</label>
                <select id="cpTarget" class="form-control" style="padding:10px;font-size:14px;height:auto;">
                    <option value="all_students">All Active Students</option>
                    <option value="all_teachers">All Teachers</option>
                    <option value="by_course">Select by Course</option>
                    <option value="by_batch">Select by Batch</option>
                </select>
            </div>
            <div id="cpExtraTarget" style="display:none;margin-bottom:15px;">
                <!-- Dynamically filled -->
            </div>
            <div class="form-group mb" style="margin-bottom:20px;">
                <label class="form-label" style="font-weight:600;font-size:12px;color:#64748b;">Email Subject</label>
                <input id="cpSubject" class="form-control" placeholder="Email Subject" style="padding:10px;font-size:14px;">
            </div>
            <div class="form-group mb">
                <label class="form-label" style="font-weight:600;font-size:12px;color:#64748b;">Message Content</label>
                <div id="cpMsgEditor" style="height:200px;border:1px solid #e2e8f0;border-radius:8px;"></div>
            </div>
        </div>`,
        width: '700px',
        showCancelButton: true,
        confirmButtonText: 'Launch Campaign <i class="fa-solid fa-paper-plane" style="margin-left:5px;"></i>',
        confirmButtonColor: '#4f46e5',
        didOpen: () => {
            // Initialize Quill in the modal
            if (!window.Quill) return;
            window._quillCampaign = new Quill('#cpMsgEditor', {
                theme: 'snow',
                modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], ['clean']] }
            });

            // Target Toggle Logic
            const target = document.getElementById('cpTarget');
            const extra = document.getElementById('cpExtraTarget');
            target.addEventListener('change', async () => {
                if (target.value === 'by_course') {
                    extra.style.display = 'block';
                    extra.innerHTML = '<select id="cpCourseId" class="form-control" style="padding:10px;"><option>Loading courses...</option></select>';
                    const res = await fetch(APP_URL + '/api/admin/courses');
                    const r = await res.json();
                    if (r.data) {
                        const sel = document.getElementById('cpCourseId');
                        sel.innerHTML = r.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
                    }
                } else if (target.value === 'by_batch') {
                    extra.style.display = 'block';
                    extra.innerHTML = '<select id="cpBatchId" class="form-control" style="padding:10px;"><option>Loading batches...</option></select>';
                    const res = await fetch(APP_URL + '/api/admin/batches');
                    const r = await res.json();
                    if (r.data) {
                        const sel = document.getElementById('cpBatchId');
                        sel.innerHTML = r.data.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
                    }
                } else {
                    extra.style.display = 'none';
                }
            });
        },
        preConfirm: () => {
            const name = document.getElementById('cpName').value;
            const target = document.getElementById('cpTarget').value;
            const subject = document.getElementById('cpSubject').value;
            const message = window._quillCampaign.root.innerHTML;
            
            let targetId = null;
            if (target === 'by_course') targetId = document.getElementById('cpCourseId').value;
            if (target === 'by_batch') targetId = document.getElementById('cpBatchId').value;

            if (!name || !subject || message === '<p><br></p>') {
                Swal.showValidationMessage('Please fill in all required fields.');
                return false;
            }

            return { name, target, target_id: targetId, subject, message };
        }
    });

    if (formValues) {
        _launchCampaign(formValues);
    }
};

async function _launchCampaign(data) {
    Swal.fire({
        title: 'Launching...',
        html: 'We are preparing the recipients and dispatching messages to the delivery queue.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const formData = new FormData();
        formData.append('action', 'launch_campaign');
        for (const key in data) formData.append(key, data[key]);

        const res = await fetch(APP_URL + '/api/admin/communications', {
            method: 'POST',
            body: formData
        });
        const r = await res.json();

        if (r.success) {
            Swal.fire({
                icon: 'success',
                title: 'Campaign Launched!',
                text: `${r.count} emails have been scheduled for delivery.`,
                confirmButtonColor: '#4f46e5'
            });
            _loadCampaignHistory();
        } else throw new Error(r.message);
    } catch (e) {
        Swal.fire('Launch Failed', e.message, 'error');
    }
}
