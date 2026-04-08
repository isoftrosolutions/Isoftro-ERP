<?php
/**
 * ISOFTRO - Error Log Viewer
 * Variable: $lines (array of log lines, newest first)
 */
$lines = $lines ?? [];
$errorCount = count(array_filter($lines, fn($l) => stripos($l, '.ERROR') !== false || stripos($l, '.CRITICAL') !== false));
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Logs</span> <span style="color:#94a3b8;"> / Error Logs</span></div>
        <h1>Error Logs</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="goNav('logs')"><i class="fas fa-shield-check"></i> Audit Logs</button>
        <button class="btn bs" onclick="goNav('logs-api')"><i class="fas fa-network-wired"></i> API Logs</button>
        <button class="btn bs" onclick="refreshErrors()"><i class="fas fa-sync"></i> Refresh</button>
    </div>
</div>

<div class="g3 mt-20">
    <div class="card p-20" style="border-left:4px solid #ef4444;">
        <div style="font-size:11px;font-weight:700;color:#ef4444;text-transform:uppercase;">Error / Critical Lines</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= $errorCount ?></div>
    </div>
    <div class="card p-20" style="border-left:4px solid #6366f1;">
        <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;">Total Lines (Recent)</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count($lines) ?></div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct" style="justify-content:space-between;">
        <span><i class="fas fa-bug"></i> Laravel Error Log</span>
        <div style="display:flex;gap:8px;">
            <input type="text" class="search-inp" placeholder="Filter lines..." onkeyup="filterErrorLines(this.value)" style="border:1px solid #e2e8f0;border-radius:8px;padding:6px 12px;font-size:13px;">
            <select class="filter-sel" onchange="filterByLevel(this.value)">
                <option value="">All Levels</option>
                <option value="ERROR">ERROR</option>
                <option value="CRITICAL">CRITICAL</option>
                <option value="WARNING">WARNING</option>
                <option value="INFO">INFO</option>
            </select>
        </div>
    </div>
    <?php if (empty($lines)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-light);">
        <i class="fas fa-check-circle" style="font-size:40px;opacity:.3;display:block;margin-bottom:15px;color:#22c55e;"></i>
        <p>No error log file found or log is empty.</p>
    </div>
    <?php else: ?>
    <div id="errorLogWrap" style="margin-top:15px;background:#0f172a;border-radius:12px;padding:16px;max-height:580px;overflow-y:auto;">
        <?php foreach ($lines as $line): ?>
        <?php
            $cls = '#94a3b8'; $weight = '400';
            if (stripos($line, '.CRITICAL') !== false || stripos($line, '.EMERGENCY') !== false) { $cls = '#ff6b6b'; $weight = '700'; }
            elseif (stripos($line, '.ERROR') !== false) { $cls = '#fca5a5'; $weight = '600'; }
            elseif (stripos($line, '.WARNING') !== false) { $cls = '#fde68a'; }
            elseif (stripos($line, '.INFO') !== false || stripos($line, '.DEBUG') !== false) { $cls = '#6ee7b7'; }
        ?>
        <div class="log-line" style="font-family:monospace;font-size:12px;color:<?= $cls ?>;font-weight:<?= $weight ?>;padding:2px 0;border-bottom:1px solid rgba(255,255,255,.04);white-space:pre-wrap;word-break:break-all;">
            <?= htmlspecialchars($line) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function filterErrorLines(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.log-line').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
function filterByLevel(level) {
    document.querySelectorAll('.log-line').forEach(el => {
        el.style.display = (!level || el.textContent.toUpperCase().includes('.' + level)) ? '' : 'none';
    });
}
function refreshErrors() { goNav('logs-errors'); }
</script>
