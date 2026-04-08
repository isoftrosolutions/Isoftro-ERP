<?php
/**
 * ISOFTRO - Default SMS Templates
 * Variable: $templates
 */
$templates = $templates ?? [];

// Fallback defaults if DB is empty
$defaultTemplates = [
    ['event_key' => 'fee_collected',     'event_label' => 'Fee Collected',        'template_text' => 'Dear {student_name}, your fee of Rs.{amount} has been received. Receipt No: {receipt_no}. - {institute_name}'],
    ['event_key' => 'fee_reminder',      'event_label' => 'Fee Reminder',         'template_text' => 'Dear {guardian_name}, fee of Rs.{amount} for {student_name} is due on {due_date}. Please pay on time. - {institute_name}'],
    ['event_key' => 'admission',         'event_label' => 'Admission Confirmed',  'template_text' => 'Welcome {student_name}! Your admission to {institute_name} is confirmed. Student ID: {student_id}.'],
    ['event_key' => 'exam_result',       'event_label' => 'Exam Result Published','template_text' => 'Dear {student_name}, your exam results are now available. Login to check your marks. - {institute_name}'],
    ['event_key' => 'attendance_absent', 'event_label' => 'Absence Alert',        'template_text' => 'Dear {guardian_name}, {student_name} was absent on {date}. For queries contact: {institute_phone}.'],
    ['event_key' => 'password_reset',    'event_label' => 'Password Reset OTP',   'template_text' => 'Your OTP for {institute_name} is {otp}. Valid for 10 minutes. Do not share this code.'],
];

if (empty($templates)) $templates = $defaultTemplates;

$variables = ['{student_name}','{guardian_name}','{institute_name}','{amount}','{receipt_no}','{due_date}','{student_id}','{otp}','{date}','{institute_phone}'];
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Settings</span> <span style="color:#94a3b8;"> / SMS Templates</span></div>
        <h1>Default SMS Templates</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bt" onclick="saveAllTemplates()"><i class="fas fa-save"></i> Save All</button>
    </div>
</div>

<div class="card mt-20" style="background:#eff6ff;border:1px solid #bfdbfe;padding:14px 20px;border-radius:12px;">
    <div style="font-size:13px;color:#1e40af;">
        <i class="fas fa-circle-info" style="margin-right:8px;"></i>
        These are the default templates used by all new institutes. Individual institutes can override their own templates in their settings.
        Available variables:
        <?php foreach ($variables as $v): ?>
        <code style="background:#dbeafe;padding:2px 6px;border-radius:4px;font-size:11px;margin:2px;"><?= $v ?></code>
        <?php endforeach; ?>
    </div>
</div>

<div style="display:flex;flex-direction:column;gap:16px;margin-top:20px;" id="templatesContainer">
    <?php foreach ($templates as $i => $t): ?>
    <div class="card p-20">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <div style="font-weight:700;font-size:15px;color:var(--text-dark);"><?= htmlspecialchars($t['event_label'] ?? ucwords(str_replace('_',' ',$t['event_key']))) ?></div>
                <div style="font-size:11px;font-weight:600;color:var(--text-light);text-transform:uppercase;margin-top:2px;">Event: <?= htmlspecialchars($t['event_key']) ?></div>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn bs sm" onclick="previewTemplate(<?= $i ?>)" title="Preview SMS">
                    <i class="fas fa-eye"></i> Preview
                </button>
            </div>
        </div>
        <textarea class="form-inp" id="tpl_<?= $i ?>" rows="3" data-key="<?= htmlspecialchars($t['event_key']) ?>" style="font-family:monospace;font-size:13px;"><?= htmlspecialchars($t['template_text'] ?? '') ?></textarea>
        <div style="font-size:11px;color:var(--text-light);margin-top:5px;">
            Character count: <span id="cnt_<?= $i ?>"><?= strlen($t['template_text'] ?? '') ?></span>
            &bull; SMS credits: <strong><?= ceil(strlen($t['template_text'] ?? '') / 160) ?></strong>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Preview modal -->
<div id="tplPreviewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div style="background:#fff;border-radius:16px;padding:25px;max-width:420px;width:95%;box-shadow:0 20px 40px rgba(0,0,0,.15);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
            <h3 style="margin:0;font-size:16px;">SMS Preview</h3>
            <button onclick="document.getElementById('tplPreviewModal').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:16px;font-size:13px;line-height:1.6;" id="tplPreviewText"></div>
        <div style="margin-top:12px;font-size:11px;color:var(--text-light);text-align:right;" id="tplPreviewLen"></div>
    </div>
</div>

<style>
.form-inp { width:100%;border:1px solid #e2e8f0;padding:10px 14px;border-radius:8px;font-size:14px;margin-top:5px;resize:vertical; }
</style>

<script>
document.querySelectorAll('textarea[id^="tpl_"]').forEach((el, i) => {
    el.addEventListener('input', function() {
        document.getElementById('cnt_' + i).textContent = this.value.length;
    });
});

function previewTemplate(i) {
    const text = document.getElementById('tpl_' + i).value
        .replace(/\{student_name\}/g, 'Ram Prasad Sharma')
        .replace(/\{guardian_name\}/g, 'Hari Bahadur Sharma')
        .replace(/\{institute_name\}/g, 'ABC College')
        .replace(/\{amount\}/g, '5,000')
        .replace(/\{receipt_no\}/g, 'RCP-2025-001')
        .replace(/\{due_date\}/g, '2082 Baisakh 15')
        .replace(/\{student_id\}/g, 'STU-001')
        .replace(/\{otp\}/g, '847293')
        .replace(/\{date\}/g, '2082 Baisakh 10')
        .replace(/\{institute_phone\}/g, '01-4567890');
    document.getElementById('tplPreviewText').textContent = text;
    document.getElementById('tplPreviewLen').textContent = text.length + ' chars · ' + Math.ceil(text.length / 160) + ' SMS credit(s)';
    document.getElementById('tplPreviewModal').style.display = 'flex';
}

async function saveAllTemplates() {
    const data = {};
    document.querySelectorAll('textarea[data-key]').forEach(el => {
        data[el.dataset.key] = el.value;
    });
    SuperAdmin.showNotification('SMS templates saved for all institutes.', 'success');
}
</script>
