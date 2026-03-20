look this the current student admission form sucess modal <div class="m-overlay" id="successDialog_adm" style="display: flex;">
    <div class="m-card active" id="scCard_adm">
        <div class="m-ico" id="mIcon_adm" style="background: linear-gradient(135deg, rgb(0, 184, 148), rgb(0, 158, 126));"><i class="fas fa-check"></i></div>
        <h3 id="mTitle_adm" style="font-size: 26px; font-weight: 900; color: #0f172a; margin: 0 0 0.5rem;">Admission Complete!</h3>
        <div id="mBody_adm" style="font-size: 15px; color: #475569; line-height: 1.6; margin-bottom: 2.5rem;"><p>Student <strong>Nepal Cyber Firm</strong> has been registered successfully.</p>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:18px;padding:1.5rem;text-align:left;margin-top:1.5rem;">
                        <p style="font-size:11px;font-weight:700;color:var(--p);margin-bottom:8px;letter-spacing:0.05em;">PORTAL CREDENTIALS</p>
                        <div style="margin-bottom:6px;font-size:14px;"><span style="opacity:0.6;width:90px;display:inline-block;">Email:</span> <strong>nepalcyberfirm@gmail.com</strong></div>
                        <div style="font-size:14px;"><span style="opacity:0.6;width:90px;display:inline-block;">Password:</span> <span style="font-family:monospace;background:#fff;padding:2px 8px;border-radius:6px;border:1px solid #ddd;">Nepal@123</span></div>
                    </div></div>
        <div id="mActions_adm" style="display: flex; flex-direction: column; gap: 12px;"><button onclick="window.location.href='http://localhost/erp/dash/admin'" class="btn-p" style="height:52px;font-size:14px;background:linear-gradient(135deg,#00b894,#009e7e);color:#fff;">View Records</button><button onclick="location.reload()" class="btn-p" style="height:52px;font-size:14px;background:#f1f5f9;color:#1e293b;box-shadow:none;">Add Another</button></div>
    </div>
</div>   i want that this modal should be displayed like a  modal and that blurs the rest of the body , like as premiunm modals

Create a premium success modal UI component for a student admission system (ERP dashboard).

Requirements:

- The modal must appear centered on the screen with a clean, modern SaaS design
- Add a full-screen overlay background with a dark transparent layer and strong backdrop blur (glassmorphism effect)
- The rest of the page should be blurred and non-interactive when modal is open
- Modal card should have rounded corners, subtle shadow, and smooth transitions
- Use premium colors (green/blue gradient for success, dark gray for overlay)
- Include smooth animations for opening and closing
- Must be fully responsive for desktop and mobile
- Should work with existing CSS framework (Bootstrap 5)
- Keep the same functionality (show success message, credentials, buttons)
- Make it look premium and modern