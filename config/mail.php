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
    // ── System SMTP (Using environment variables) ────────────────
    'smtp_host'       => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'smtp_port'       => getenv('MAIL_PORT') ?: 587,
    'smtp_encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',  // 'tls' | 'ssl' | 'none'
    'smtp_user'       => getenv('MAIL_USERNAME') ?: 'isoftrosolutions@gmail.com',
    'smtp_pass'       => getenv('MAIL_PASSWORD') ?: 'tpkm awve kkzl ifdm',

    // ── System "from" identity (used when no institute name is set) ──
    'system_from_email' => getenv('MAIL_FROM_ADDRESS') ?: 'isoftrosolutions@gmail.com',
    'system_from_name'  => 'iSoftro Support',

    // ── Defaults ─────────────────────────────────────────────
    'timeout'   => 15,    // seconds
    'debug'     => 0,     // 0=off (production), 2=verbose (for dev)
];
