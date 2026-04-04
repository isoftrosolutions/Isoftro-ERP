/**
 * iSoftro ERP — Student Portal · st-idcard.js
 * Digital ID Card: view, print, download student identity card
 */

window.renderSTIdCard = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading ID Card...</div></div>';

    try {
        const res = await fetch(`${window.APP_URL}/api/student/profile?action=view`);
        const result = await res.json();

        if (!result.success || !result.data) {
            mc.innerHTML = `<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:60px;">
                <i class="fa-solid fa-id-card" style="font-size:4rem;color:var(--sa-primary);opacity:0.3;margin-bottom:20px;"></i>
                <h3>ID Card Unavailable</h3>
                <p style="color:var(--tl);">${result.message || 'Unable to load student data.'}</p>
            </div></div></div>`;
            return;
        }

        const d = result.data;
        const photo = d.photo_url || d.photo || null;
        const initials = ((d.first_name || '')[0] || '') + ((d.last_name || '')[0] || '');
        const fullName = [d.first_name, d.middle_name, d.last_name].filter(Boolean).join(' ') || d.name || 'Student';
        const institute = window.CURRENT_INSTITUTE || d.institute_name || 'Institute';
        const rollNo = d.roll_number || d.roll_no || '-';
        const studentId = d.student_id || d.id || '-';
        const batch = d.batch_name || d.batch || '-';
        const course = d.course_name || d.course || '-';
        const dob = d.dob || d.date_of_birth || '-';
        const bloodGroup = d.blood_group || '-';
        const phone = d.phone || d.mobile || '-';
        const address = d.address || d.permanent_address || '-';
        const validUntil = d.valid_until || new Date(new Date().getFullYear() + 1, 2, 31).toISOString().split('T')[0];

        mc.innerHTML = `
            <div style="padding:24px;">
                <div class="card-hdr">
                    <div class="ct"><i class="fa-solid fa-id-card" style="margin-right:8px;color:var(--sa-primary);"></i> Digital ID Card</div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-sm btn-outline" onclick="_stPrintIdCard()"><i class="fa-solid fa-print"></i> Print</button>
                        <button class="btn btn-sm btn-primary" onclick="_stDownloadIdCard()"><i class="fa-solid fa-download"></i> Download</button>
                    </div>
                </div>

                <div style="display:flex;justify-content:center;padding:20px 0;">
                    <div id="stIdCardEl" style="width:380px;font-family:'Inter',sans-serif;">
                        <!-- FRONT -->
                        <div style="background:linear-gradient(135deg,#009E7E 0%,#00c897 100%);border-radius:16px;padding:24px;color:#fff;margin-bottom:16px;position:relative;overflow:hidden;">
                            <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.08);"></div>
                            <div style="position:absolute;bottom:-40px;left:-20px;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,0.05);"></div>

                            <!-- Header -->
                            <div style="text-align:center;margin-bottom:16px;">
                                <div style="font-size:14px;font-weight:800;letter-spacing:1px;text-transform:uppercase;">${_escHtml(institute)}</div>
                                <div style="font-size:10px;opacity:0.8;margin-top:2px;">STUDENT IDENTITY CARD</div>
                            </div>

                            <!-- Photo + Info -->
                            <div style="display:flex;align-items:center;gap:16px;">
                                <div style="width:80px;height:96px;border-radius:10px;overflow:hidden;border:3px solid rgba(255,255,255,0.5);flex-shrink:0;background:#fff;display:flex;align-items:center;justify-content:center;">
                                    ${photo
                                        ? `<img src="${_escHtml(photo)}" alt="Photo" style="width:100%;height:100%;object-fit:cover;">`
                                        : `<div style="font-size:28px;font-weight:800;color:#009E7E;">${_escHtml(initials.toUpperCase())}</div>`
                                    }
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:16px;font-weight:800;line-height:1.2;margin-bottom:6px;">${_escHtml(fullName)}</div>
                                    <div style="display:grid;grid-template-columns:auto 1fr;gap:2px 8px;font-size:11px;opacity:0.9;">
                                        <span style="opacity:0.7;">ID:</span><span style="font-weight:600;">${_escHtml(studentId)}</span>
                                        <span style="opacity:0.7;">Roll:</span><span style="font-weight:600;">${_escHtml(rollNo)}</span>
                                        <span style="opacity:0.7;">Course:</span><span style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${_escHtml(course)}</span>
                                        <span style="opacity:0.7;">Batch:</span><span style="font-weight:600;">${_escHtml(batch)}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer row -->
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:10px;border-top:1px solid rgba(255,255,255,0.2);font-size:10px;">
                                <div><span style="opacity:0.7;">DOB:</span> <span style="font-weight:600;">${_escHtml(dob)}</span></div>
                                <div><span style="opacity:0.7;">Blood:</span> <span style="font-weight:600;">${_escHtml(bloodGroup)}</span></div>
                                <div><span style="opacity:0.7;">Valid:</span> <span style="font-weight:600;">${_escHtml(validUntil)}</span></div>
                            </div>
                        </div>

                        <!-- BACK -->
                        <div style="background:#fff;border-radius:16px;padding:24px;border:1px solid var(--card-border);position:relative;">
                            <div style="text-align:center;margin-bottom:12px;">
                                <div style="font-size:12px;font-weight:700;color:var(--text-dark);text-transform:uppercase;letter-spacing:0.5px;">Contact & Emergency</div>
                            </div>
                            <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 12px;font-size:12px;color:var(--text-dark);">
                                <span style="color:var(--tl);"><i class="fa-solid fa-phone" style="width:14px;"></i></span><span>${_escHtml(phone)}</span>
                                <span style="color:var(--tl);"><i class="fa-solid fa-location-dot" style="width:14px;"></i></span><span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${_escHtml(address)}</span>
                            </div>

                            <!-- QR placeholder -->
                            <div style="text-align:center;margin-top:16px;">
                                <div id="stIdQR" style="display:inline-block;width:80px;height:80px;background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                    <i class="fa-solid fa-qrcode" style="font-size:40px;color:#d1d5db;"></i>
                                </div>
                                <div style="font-size:9px;color:var(--tl);margin-top:4px;">Scan to verify</div>
                            </div>

                            <div style="text-align:center;margin-top:12px;font-size:9px;color:var(--tl);">
                                If found, please return to <strong>${_escHtml(institute)}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

    } catch (e) {
        console.error('ID Card load error:', e);
        mc.innerHTML = `<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:60px;">
            <i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i>
            <h3>Error</h3><p>Failed to load ID Card data.</p>
        </div></div></div>`;
    }
};

/** HTML-escape helper */
function _escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

/** Print ID Card */
window._stPrintIdCard = function() {
    const el = document.getElementById('stIdCardEl');
    if (!el) return;
    const w = window.open('', '_blank', 'width=450,height=700');
    w.document.write(`<!DOCTYPE html><html><head><title>Student ID Card</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Inter',sans-serif;padding:20px;}</style>
    </head><body>${el.innerHTML}<script>setTimeout(()=>{window.print();window.close();},500)<\/script></body></html>`);
    w.document.close();
};

/** Download ID Card as image (canvas-based fallback) */
window._stDownloadIdCard = function() {
    const el = document.getElementById('stIdCardEl');
    if (!el) return;
    // If html2canvas is available, use it
    if (window.html2canvas) {
        html2canvas(el, { scale: 2, useCORS: true }).then(canvas => {
            const a = document.createElement('a');
            a.download = 'student-id-card.png';
            a.href = canvas.toDataURL('image/png');
            a.click();
        });
    } else {
        // Fallback: print
        _stPrintIdCard();
    }
};
