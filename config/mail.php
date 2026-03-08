<?php
/**
 * ─────────────────────────────────────────────────────────────
 *  Hamro ERP — System Mail Configuration
 *
 *  This file contains the platform's own SMTP credentials.
 *  Institutes NEVER see or touch these settings.
 *  They only configure:  from_name  and  reply_to_email
 *
 *  To change the system mailer, edit ONLY this file.
 * ─────────────────────────────────────────────────────────────
 */

return [
    // ── System SMTP (Hamro ERP's shared relay) ────────────────
    'smtp_host'       => 'smtp.gmail.com',
    'smtp_port'       => 587,
    'smtp_encryption' => 'tls',          // 'tls' | 'ssl' | 'none'
    'smtp_user'       => 'infohamrolabs@gmail.com',
    'smtp_pass'       => 'tujwophwwayyktdb',

    // ── System "from" identity (used when no institute name is set) ──
    'system_from_email' => 'infohamrolabs@gmail.com',
    'system_from_name'  => 'Hamro ERP',

    // ── Defaults ─────────────────────────────────────────────
    'timeout'   => 10,    // seconds
    'debug'     => 2,     // 0=off, 1=errors, 2=verbose (for dev)
];
