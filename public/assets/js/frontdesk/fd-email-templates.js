/**
 * iSoftro ERP — ia-email-templates.js
 * Email Templates Module for Institute Admins
 */
window.renderEmailTemplates = async function() {
    const mc = document.getElementById('mainContent');
    
    // Dynamically inject Quill CSS
    if (!document.getElementById('quillCss')) {
        const lnk = document.createElement('link');
        lnk.id = 'quillCss';
        lnk.rel = 'stylesheet';
        lnk.href = 'https://cdn.quilljs.com/1.3.6/quill.snow.css';
        document.head.appendChild(lnk);
    }

    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Email Templates</span></div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg,#0ea5e9,#3b82f6);"><i class="fa-solid fa-envelope-open-text"></i></div>
                <div><div class="pg-title">Email Templates</div><div class="pg-sub">Customize automated emails sent to students</div></div>
            </div>
            <div class="pg-right">
                <button class="btn bs" onclick="goNav('settings','email')"><i class="fa-solid fa-cog"></i> Sender Settings</button>
            </div>
        </div>

        <div style="max-width:1100px;margin:0 auto;display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start;">
            
            <!-- Sidebar: List of Templates -->
            <div class="card" style="padding:0;overflow:hidden;border:1px solid #e2e8f0;background:#fff;">
                <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;background:#f8fafc;font-weight:700;font-size:13px;color:#475569;display:flex;justify-content:space-between;align-items:center;">
                    System Templates <span id="tplCount" class="bdg bg-t" style="font-size:10px;">0</span>
                </div>
                <div id="templatesList" style="max-height:600px;overflow-y:auto;padding:10px;">
                    <div style="text-align:center;padding:40px 0;"><i class="fa-solid fa-spinner fa-spin" style="color:#cbd5e1;font-size:24px;"></i></div>
                </div>
            </div>

            <!-- Main Content: Editor Editor -->
            <div class="card" style="padding:32px;border:1px solid #e2e8f0;min-height:400px;display:flex;flex-direction:column;">
                
                <div id="emptyEditorState" style="text-align:center;padding:80px 0;flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center;">
                    <i class="fa-regular fa-envelope" style="font-size:48px;color:#cbd5e1;margin-bottom:16px;"></i>
                    <h3 style="margin:0 0 8px;color:#334155;font-weight:600;">Select a template to edit</h3>
                    <p style="margin:0;color:#64748b;font-size:14px;max-width:300px;">Choose a template from the list on the left to customize its subject and content.</p>
                </div>
                
                <div id="templateEditor" style="display:none;">
                    <form id="tplForm">
                        <input type="hidden" id="tplId" name="id">
                        
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;">
                            <div>
                                <h2 id="tplTitle" style="margin:0 0 4px;color:#0f172a;font-size:18px;">Edit Template</h2>
                                <div id="tplKey" style="font-size:11px;color:#64748b;font-family:monospace;background:#f1f5f9;padding:2px 6px;border-radius:4px;display:inline-block;">template_key</div>
                            </div>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:#475569;">
                                <input type="checkbox" id="tplActive" name="is_active" value="1" style="width:16px;height:16px;accent-color:#0ea5e9;">
                                Active
                            </label>
                        </div>
                        
                        <div class="form-group" style="margin-bottom:20px;">
                            <label class="form-label">Subject Line *</label>
                            <input type="text" id="tplSubject" name="subject" class="form-control" placeholder="Email Subject" required style="font-size:14px;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom:20px;">
                            <label class="form-label" style="display:block;margin-bottom:8px;">Email Body *</label>
                            
                            <!-- Interactive Placeholders Toolbar -->
                            <div style="background:#f8fafc;padding:10px;border:1px solid #e2e8f0;border-bottom:none;border-radius:4px 4px 0 0;display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                <span style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-right:8px;">Insert Tag:</span>
                                <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:#0ea5e9;font-size:12px;padding:4px 8px;border-radius:4px;" onclick="_insertQuillPlaceholder('{{student_name}}')"><i class="fa-solid fa-user"></i> Student Name</button>
                                <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:#0ea5e9;font-size:12px;padding:4px 8px;border-radius:4px;" onclick="_insertQuillPlaceholder('{{institute_name}}')"><i class="fa-solid fa-school"></i> Institute Name</button>
                                <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:#0ea5e9;font-size:12px;padding:4px 8px;border-radius:4px;" onclick="_insertQuillPlaceholder('{{amount}}')"><i class="fa-solid fa-money-bill"></i> Amount</button>
                                <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:#0ea5e9;font-size:12px;padding:4px 8px;border-radius:4px;" onclick="_insertQuillPlaceholder('{{course_name}}')"><i class="fa-solid fa-book"></i> Course</button>
                                <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:#0ea5e9;font-size:12px;padding:4px 8px;border-radius:4px;" onclick="_insertQuillPlaceholder('{{due_date}}')"><i class="fa-solid fa-calendar"></i> Due Date</button>
                                <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:#0ea5e9;font-size:12px;padding:4px 8px;border-radius:4px;" onclick="_insertQuillPlaceholder('{{email}}')"><i class="fa-solid fa-at"></i> Login Email</button>
                                <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #cbd5e1;color:#0ea5e9;font-size:12px;padding:4px 8px;border-radius:4px;" onclick="_insertQuillPlaceholder('{{plain_password}}')"><i class="fa-solid fa-key"></i> Password</button>
                            </div>
                            
                            <!-- Quill Editor Container -->
                            <div id="quillEditorContainer" style="height:350px;background:#fff;font-family:inherit;"></div>
                            
                            <!-- Hidden textarea to store real HTML value for form submission -->
                            <textarea id="tplBody" name="body_content" style="display:none;" required></textarea>
                        </div>
                        
                        <div style="text-align:right;margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9;">
                            <button type="submit" class="btn bt" style="background:#0ea5e9;border-color:#0284c7;"><i class="fa-solid fa-save"></i> Save Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>`;

    await _loadTemplatesList();
    
    // Inject Quill JS script if not loaded
    if (!window.Quill) {
        try {
            await new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.quilljs.com/1.3.6/quill.min.js';
                s.onload = resolve;
                s.onerror = reject;
                document.body.appendChild(s);
            });
        } catch (e) {
            console.error('Failed to load Quill editor:', e);
            Swal.fire('Warning', 'Could not load rich text editor. Please refresh.', 'warning');
        }
    }
    
    // Initialize Quill instance
    if (window.Quill) {
        window._quillTplEditor = new Quill('#quillEditorContainer', {
            theme: 'snow',
            placeholder: 'Type your email message here...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });
        
        // Listen to changes and sync into hidden textarea
        window._quillTplEditor.on('text-change', function() {
            document.getElementById('tplBody').value = window._quillTplEditor.root.innerHTML;
        });
    }

    document.getElementById('tplForm').onsubmit = _saveTemplate;
};

// Global Store
window._emailTemplatesData = [];

async function _loadTemplatesList() {
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/email_templates');
        const r = await res.json();
        
        const listDiv = document.getElementById('templatesList');
        
        if (r.success && r.data) {
            window._emailTemplatesData = r.data;
            document.getElementById('tplCount').textContent = r.data.length;
            
            if (r.data.length === 0) {
                listDiv.innerHTML = '<div style="padding:20px;text-align:center;font-size:13px;color:#94a3b8;">No templates found.</div>';
                return;
            }
            
            let html = '';
            r.data.forEach(t => {
                const statusColor = t.is_active == 1 ? '#10b981' : '#94a3b8';
                html += `
                <div class="tpl-item" onclick="_openTemplate(${t.id})" style="padding:12px 16px;border-radius:8px;margin-bottom:4px;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;gap:12px;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background=''">
                    <div style="width:8px;height:8px;border-radius:50%;background:${statusColor};flex-shrink:0;"></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:14px;font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${t.template_name}</div>
                        <div style="font-size:11px;color:#64748b;margin-top:2px;">${t.template_key}</div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="color:#cbd5e1;font-size:12px;"></i>
                </div>
                `;
            });
            listDiv.innerHTML = html;
        } else {
            Swal.fire('Error', r.message || 'Failed to load templates', 'error');
        }
    } catch (e) {
        Swal.fire('Network Error', e.message, 'error');
    }
}

window._openTemplate = function(id) {
    const tpl = window._emailTemplatesData.find(t => t.id == id);
    if (!tpl) return;
    
    document.getElementById('emptyEditorState').style.display = 'none';
    document.getElementById('templateEditor').style.display = 'block';
    
    // Highlight list item
    document.querySelectorAll('.tpl-item').forEach(el => {
        el.style.background = '';
        el.style.borderLeft = 'none';
        el.onmouseout = () => { el.style.background = ''; };
    });
    const clickedItem = event.currentTarget;
    if(clickedItem) {
        clickedItem.style.background = '#e0f2fe';
        clickedItem.onmouseout = null; // Disable hover revert for active
    }

    // Fill form
    document.getElementById('tplId').value = tpl.id;
    document.getElementById('tplTitle').textContent = tpl.template_name;
    document.getElementById('tplKey').textContent = tpl.template_key;
    document.getElementById('tplSubject').value = tpl.subject;
    
    // Set hidden HTML value
    document.getElementById('tplBody').value = tpl.body_content;
    
    // Set Quill Editor HTML content safely
    if (window._quillTplEditor) {
        // Prevent trigger loop by clearing first
        
        // Decode HTML entities that may be returned by the server safely
        let decodedHtml = tpl.body_content || '';
        const txtArea = document.createElement('textarea');
        txtArea.innerHTML = decodedHtml;
        decodedHtml = txtArea.value;

        window._quillTplEditor.root.innerHTML = decodedHtml;
    }
    
    document.getElementById('tplActive').checked = tpl.is_active == 1;
};

// Insert tags into Quill at cursor
window._insertQuillPlaceholder = function(tag) {
    if (!window._quillTplEditor) return;
    const range = window._quillTplEditor.getSelection(true);
    if (range) {
        window._quillTplEditor.insertText(range.index, tag, 'user');
        window._quillTplEditor.setSelection(range.index + tag.length, Quill.sources.SILENT);
    }
};

async function _saveTemplate(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    const formData = new FormData(e.target);
    if (!formData.has('is_active')) formData.append('is_active', '0');
    
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/email_templates', {
            method: 'POST',
            body: formData
        });
        const r = await res.json();
        
        if (r.success) {
            Swal.fire({
                title: 'Saved!',
                text: 'Template changes saved successfully.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            // Reload list silently to update status badge if changed
            _loadTemplatesList();
        } else {
            Swal.fire('Error', r.message, 'error');
        }
    } catch (err) {
        Swal.fire('Network Error', err.message, 'error');
    } finally {
        btn.innerHTML = orig;
        btn.disabled = false;
    }
}

window.showPlaceholders = function() {
    Swal.fire({
        title: 'Available Placeholders',
        html: `
        <div style="text-align:left;font-size:13px;line-height:1.6;color:#334155;">
            <p>You can use these tags anywhere in the subject or body. They will be automatically replaced when the email is sent:</p>
            <div style="background:#f1f5f9;padding:12px;border-radius:8px;border:1px solid #e2e8f0;">
                <code style="color:#0ea5e9;font-weight:bold;">{{student_name}}</code> - Full name of the student<br>
                <code style="color:#0ea5e9;font-weight:bold;">{{institute_name}}</code> - Your institute name<br>
                <code style="color:#0ea5e9;font-weight:bold;">{{amount}}</code> - Payment/Due Amount<br>
                <code style="color:#0ea5e9;font-weight:bold;">{{course_name}}</code> - Name of the course/class<br>
                <code style="color:#0ea5e9;font-weight:bold;">{{due_date}}</code> - Deadline or Due date<br>
                <code style="color:#0ea5e9;font-weight:bold;">{{email}}</code> - Student Login Email<br>
                <code style="color:#0ea5e9;font-weight:bold;">{{plain_password}}</code> - Unencrypted password (Welcome email only)<br>
            </div>
            <p style="margin-top:12px;font-size:12px;color:#64748b;">Make sure to include the curly braces exactly as shown.</p>
        </div>
        `,
        confirmButtonText: 'Got it',
        confirmButtonColor: '#0ea5e9'
    });
};
