<?php
/**
 * Premium Payment Processing Modal Component
 * 
 * This component provides a high-end, 3-step payment processing animation
 * for ERP systems.
 */
?>
<style>
    :root {
        --p: #00b894;
        --p-d: #009e7e;
        --p-lt: rgba(0, 184, 148, 0.1);
        --bg-overlay: rgba(15, 23, 42, 0.4);
        --text-main: #1e293b;
        --text-sub: #475569;
        --text-muted: #94a3b8;
        --glass: rgba(255, 255, 255, 0.9);
        --sh-p: 0 40px 100px -20px rgba(0, 0, 0, 0.15);
        --trans: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        --rad: 24px;
    }

    /* Modal System */
    .pm-overlay {
        position: fixed;
        inset: 0;
        background: var(--bg-overlay);
        backdrop-filter: blur(12px);
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .pm-overlay.active {
        display: flex;
        opacity: 1;
    }

    .pm-card {
        background: #fff;
        width: 100%;
        max-width: 500px;
        border-radius: var(--rad);
        position: relative;
        overflow: hidden;
        box-shadow: var(--sh-p);
        transform: scale(0.9) translateY(40px);
        opacity: 0;
        transition: var(--trans);
        display: flex;
        flex-direction: column;
    }

    .pm-overlay.active .pm-card {
        transform: scale(1) translateY(0);
        opacity: 1;
    }

    /* Modal Header */
    .pm-header {
        padding: 32px 32px 20px;
        text-align: center;
    }

    .pm-title {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 8px;
    }

    .pm-subtitle {
        font-size: 14px;
        color: var(--text-sub);
        font-weight: 500;
    }

    /* Steps Indicator */
    .pm-steps-wrapper {
        padding: 0 40px 32px;
        display: flex;
        justify-content: space-between;
        position: relative;
    }

    .pm-steps-wrapper::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 55px;
        right: 55px;
        height: 3px;
        background: #e2e8f0;
        z-index: 0;
    }

    .pm-step-item {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        flex: 1;
    }

    .pm-step-icon {
        width: 44px;
        height: 44px;
        background: #fff;
        border: 3px solid #e2e8f0;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: var(--text-muted);
        transition: var(--trans);
    }

    .pm-step-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: var(--trans);
    }

    /* Active Step */
    .pm-step-item.active .pm-step-icon {
        border-color: var(--p);
        color: var(--p);
        box-shadow: 0 0 0 6px var(--p-lt);
        animation: pm-pulse 2s infinite;
    }

    .pm-step-item.active .pm-step-label {
        color: var(--p);
    }

    /* Completed Step */
    .pm-step-item.completed .pm-step-icon {
        background: var(--p);
        border-color: var(--p);
        color: #fff;
    }

    .pm-step-item.completed .pm-step-label {
        color: var(--text-main);
    }

    @keyframes pm-pulse {
        0% { box-shadow: 0 0 0 0px var(--p-lt); }
        70% { box-shadow: 0 0 0 10px rgba(0, 184, 148, 0); }
        100% { box-shadow: 0 0 0 0px rgba(0, 184, 148, 0); }
    }

    /* Content Area */
    .pm-body {
        padding: 0 40px 40px;
        text-align: center;
        min-height: 220px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .pm-step-content {
        display: none;
        width: 100%;
        animation: pm-fadeIn 0.5s ease;
    }

    .pm-step-content.active {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    @keyframes pm-fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Animation Elements */
    .pm-dot-bricks {
        position: relative;
        width: 10px;
        height: 10px;
        border-radius: 5px;
        background-color: var(--p);
        color: var(--p);
        box-shadow: -15px 0 0 0 var(--p);
        animation: pm-dot-bricks 2s infinite ease;
        margin-bottom: 30px;
    }

    @keyframes pm-dot-bricks {
        0% { box-shadow: -15px 0 0 0 var(--p); }
        8.33% { box-shadow: 0 -15px 0 0 var(--p); }
        16.66% { box-shadow: 15px 0 0 0 var(--p); }
        25% { box-shadow: 0 15px 0 0 var(--p); }
        33.33% { box-shadow: -15px 0 0 0 var(--p); }
        41.66% { box-shadow: 0 -15px 0 0 var(--p); }
        50% { box-shadow: 15px 0 0 0 var(--p); }
        58.33% { box-shadow: 0 15px 0 0 var(--p); }
        66.66% { box-shadow: -15px 0 0 0 var(--p); }
        75% { box-shadow: 0 -15px 0 0 var(--p); }
        83.33% { box-shadow: 15px 0 0 0 var(--p); }
        91.66% { box-shadow: 0 15px 0 0 var(--p); }
        100% { box-shadow: -15px 0 0 0 var(--p); }
    }

    .pm-progress-container {
        width: 100%;
        height: 8px;
        background: #f1f5f9;
        border-radius: 10px;
        margin: 20px 0;
        overflow: hidden;
    }

    .pm-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--p-d), var(--p));
        width: 0%;
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .pm-success-icon {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, var(--p), var(--p-d));
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 40px;
        margin-bottom: 24px;
        box-shadow: 0 20px 40px -10px rgba(0, 184, 148, 0.4);
        animation: pm-successScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes pm-successScale {
        from { transform: scale(0); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .pm-summary-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        width: 100%;
        padding: 24px;
        margin-top: 24px;
        text-align: left;
    }

    .pm-summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .pm-summary-row:last-child {
        margin-bottom: 0;
        padding-top: 12px;
        border-top: 1px dashed #cbd5e1;
    }

    .pm-summary-label {
        font-size: 13px;
        color: var(--text-sub);
        font-weight: 500;
    }

    .pm-summary-value {
        font-size: 13px;
        color: var(--text-main);
        font-weight: 700;
    }

    .pm-summary-value.amount {
        font-size: 16px;
        color: var(--p-d);
    }

    /* Actions */
    .pm-actions {
        padding: 0 40px 40px;
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .pm-btn {
        padding: 14px 24px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--trans);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border: none;
        text-decoration: none;
    }

    .pm-btn-p {
        background: linear-gradient(135deg, var(--p-d), var(--p));
        color: #fff;
        box-shadow: 0 10px 20px -5px rgba(0, 184, 148, 0.3);
    }

    .pm-btn-outline {
        background: transparent;
        color: var(--text-sub);
        border: 2px solid #e2e8f0;
    }

    .pm-btn:hover { transform: translateY(-2px); }
    .pm-btn:active { transform: translateY(0); }

    /* Icons Animation */
    .pm-floating { animation: pm-floating 3s ease-in-out infinite; }
    @keyframes pm-floating {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .pm-plane-anim {
        position: absolute;
        top: 0;
        right: 0;
        font-size: 20px;
        color: var(--p);
        animation: pm-planeFly 2s infinite linear;
    }

    @keyframes pm-planeFly {
        0% { transform: translate(-20px, 20px) scale(0); opacity: 0; }
        50% { transform: translate(10px, -10px) scale(1.2); opacity: 1; }
        100% { transform: translate(40px, -40px) scale(0); opacity: 0; }
    }

    .pm-close-x {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: #f1f5f9;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--text-sub);
    }

    /* Error State */
    .pm-error-icon {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 40px;
        margin-bottom: 24px;
        box-shadow: 0 20px 40px -10px rgba(239, 68, 68, 0.4);
        animation: pm-successScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .pm-error-message {
        font-size: 13px;
        color: var(--text-sub);
        margin-top: 10px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        padding: 12px 16px;
        border-radius: 12px;
        max-width: 100%;
        word-break: break-word;
    }

    /* Step item error state */
    .pm-step-item.error .pm-step-icon {
        border-color: #ef4444;
        color: #ef4444;
        box-shadow: 0 0 0 6px rgba(239, 68, 68, 0.1);
    }

    .pm-step-item.error .pm-step-label {
        color: #ef4444;
    }
</style>

<!-- Modal Overlay -->
<div class="pm-overlay" id="paymentProcessingOverlay">
    <div class="pm-card" id="paymentProcessingCard">
        <div class="pm-close-x" id="pmCloseBtn" onclick="PaymentProcessor.close()">
            <i class="fas fa-times"></i>
        </div>

        <!-- Header -->
        <div class="pm-header" id="pmHeader">
            <h2 class="pm-title" id="pmMainTitle">Processing Payment</h2>
            <p class="pm-subtitle" id="pmMainSubtitle">Please wait while we finalize the transaction</p>
        </div>

        <!-- Progress Steps -->
        <div class="pm-steps-wrapper" id="pmStepsIndicator">
            <div class="pm-step-item active" id="pmStep1">
                <div class="pm-step-icon"><i class="fas fa-wallet"></i></div>
                <span class="pm-step-label">Record</span>
            </div>
            <div class="pm-step-item" id="pmStep2">
                <div class="pm-step-icon"><i class="fas fa-file-invoice"></i></div>
                <span class="pm-step-label">Generate</span>
            </div>
            <div class="pm-step-item" id="pmStep3">
                <div class="pm-step-icon"><i class="fas fa-paper-plane"></i></div>
                <span class="pm-step-label">Send</span>
            </div>
        </div>

        <!-- Main Body -->
        <div class="pm-body">
            <!-- Step 1: Recording -->
            <div class="pm-step-content active" id="pmContent1">
                <div class="pm-dot-bricks"></div>
                <p style="font-weight: 600; color: var(--text-main);">Recording payment details securely...</p>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 8px;">Encrypting transaction payload</p>
            </div>

            <!-- Step 2: PDF Generation -->
            <div class="pm-step-content" id="pmContent2">
                <div class="fas fa-file-pdf pm-floating" style="font-size: 48px; color: var(--p); margin-bottom: 24px;"></div>
                <p style="font-weight: 600; color: var(--text-main);">Generating receipt PDF...</p>
                <div class="pm-progress-container">
                    <div class="pm-progress-fill" id="pmPdfProgress"></div>
                </div>
                <p style="font-size: 13px; color: var(--text-muted);">Finalizing document assets</p>
            </div>

            <!-- Step 3: Sending -->
            <div class="pm-step-content" id="pmContent3">
                <div style="position: relative; font-size: 48px; color: var(--p); margin-bottom: 24px;">
                    <i class="fas fa-envelope-open-text"></i>
                    <i class="fas fa-paper-plane pm-plane-anim"></i>
                </div>
                <p style="font-weight: 600; color: var(--text-main);">Sending receipt to student email...</p>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 8px;">Verifying recipient inbox</p>
            </div>

            <!-- Error State -->
            <div class="pm-step-content" id="pmErrorContent">
                <div class="pm-error-icon">
                    <i class="fas fa-times"></i>
                </div>
                <h2 class="pm-title" style="font-size: 22px; color: #dc2626;">Payment Failed</h2>
                <p class="pm-subtitle">Something went wrong during processing.</p>
                <div class="pm-error-message" id="pmErrorMessage">An unexpected error occurred.</div>
            </div>

            <!-- Success State -->
            <div class="pm-step-content" id="pmSuccessContent">
                <div class="pm-success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="pm-title" style="font-size: 26px;">Payment Successful!</h2>
                <p class="pm-subtitle">Payment recorded and receipt sent successfully.</p>

                <div class="pm-summary-card">
                    <div class="pm-summary-row">
                        <span class="pm-summary-label">Student Name</span>
                        <span class="pm-summary-value" id="pmResStudent">---</span>
                    </div>
                    <div class="pm-summary-row">
                        <span class="pm-summary-label">Payment Method</span>
                        <span class="pm-summary-value" id="pmResMethod">---</span>
                    </div>
                    <div class="pm-summary-row">
                        <span class="pm-summary-label">Transaction ID</span>
                        <span class="pm-summary-value" id="pmResTxn">---</span>
                    </div>
                    <div class="pm-summary-row">
                        <span class="pm-summary-label">Amount Paid</span>
                        <span class="pm-summary-value amount" id="pmResAmount">---</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Actions -->
        <div class="pm-actions" id="pmActions" style="display: none;">
            <button class="pm-btn pm-btn-p" id="pmBtnDownload">
                <i class="fas fa-download"></i> Download Receipt
            </button>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <button class="pm-btn pm-btn-outline" onclick="PaymentProcessor.onViewRecords()">
                    <i class="fas fa-list"></i> Records
                </button>
                <button class="pm-btn" style="background: var(--text-main); color: #fff;" onclick="PaymentProcessor.close()">
                    Done
                </button>
            </div>
        </div>

        <!-- Error Actions -->
        <div class="pm-actions" id="pmErrorActions" style="display: none;">
            <button class="pm-btn pm-btn-p" id="pmBtnRetry" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <i class="fas fa-redo"></i> Retry Payment
            </button>
            <button class="pm-btn pm-btn-outline" onclick="PaymentProcessor.close()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<script>
    /**
     * PaymentProcessor — API-driven Payment Modal
     * 
     * Usage (called from ia-fees.js / fd-fees.js):
     *   PaymentProcessor.open(data)    → opens modal at Step 1
     *   PaymentProcessor.goToStep(2)   → advance to Step 2 (with brief animation delay)
     *   PaymentProcessor.goToStep(3)   → advance to Step 3
     *   PaymentProcessor.showSuccess(data) → show success screen
     *   PaymentProcessor.showError(msg, onRetry) → show error screen with retry
     *   PaymentProcessor.close()       → close modal
     */
    window.PaymentProcessor = {
        _isRunning: false,
        _onRetry: null,

        /**
         * open(data) — Open the modal and show Step 1 (Recording)
         * data: { studentName, amount, method }
         * Returns false if already running (double-click guard)
         */
        open: function(data) {
            if (this._isRunning) {
                console.warn('[PaymentProcessor] Already running — ignoring duplicate call');
                return false;
            }
            this._isRunning = true;
            this._onRetry = null;
            this._reset();
            this._populatePreview(data);

            // Show modal at Step 1
            document.getElementById('paymentProcessingOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
            return true;
        },

        /**
         * goToStep(num) — Transition from current step to the given step
         * Completes all previous steps and activates the target step.
         * Includes a brief cosmetic delay for smooth UX.
         */
        goToStep: async function(num) {
            // Complete all steps before the target
            for (let i = 1; i < num; i++) {
                const stepEl = document.getElementById('pmStep' + i);
                if (stepEl) {
                    stepEl.classList.remove('active', 'error');
                    stepEl.classList.add('completed');
                }
            }

            // Brief UX delay so the user can see the transition
            await this._wait(400);

            // Activate the target step content
            document.querySelectorAll('.pm-step-content').forEach(c => c.classList.remove('active'));
            const content = document.getElementById('pmContent' + num);
            if (content) content.classList.add('active');

            // Activate the step indicator
            document.querySelectorAll('.pm-step-item').forEach(s => {
                if (!s.classList.contains('completed')) s.classList.remove('active');
            });
            const stepEl = document.getElementById('pmStep' + num);
            if (stepEl) {
                stepEl.classList.remove('error');
                stepEl.classList.add('active');
            }

            // If step 2, run the progress bar animation
            if (num === 2) {
                this._animateProgress('pmPdfProgress', 800);
            }
        },

        /**
         * showSuccess(data) — Display the success state
         * data: { studentName, amount, method, txnId, downloadUrl }
         */
        showSuccess: async function(data) {
            // Complete all steps
            for (let i = 1; i <= 3; i++) {
                const stepEl = document.getElementById('pmStep' + i);
                if (stepEl) {
                    stepEl.classList.remove('active', 'error');
                    stepEl.classList.add('completed');
                }
            }

            await this._wait(400);

            // Populate success data
            document.getElementById('pmResStudent').textContent = data.studentName || 'N/A';
            document.getElementById('pmResMethod').textContent = data.method || 'Manual Payment';
            document.getElementById('pmResTxn').textContent = data.txnId || 'N/A';
            document.getElementById('pmResAmount').textContent = data.amount || '0.00';

            const dlBtn = document.getElementById('pmBtnDownload');
            if (data.downloadUrl && data.downloadUrl !== '#') {
                dlBtn.style.display = '';
                dlBtn.onclick = () => window.open(data.downloadUrl, '_blank');
            } else {
                dlBtn.style.display = 'none';
            }

            // Switch to success view
            document.querySelectorAll('.pm-step-content').forEach(c => c.classList.remove('active'));
            document.getElementById('pmSuccessContent').classList.add('active');
            document.getElementById('pmStepsIndicator').style.display = 'none';
            document.getElementById('pmHeader').style.display = 'none';
            document.getElementById('pmActions').style.display = 'grid';
            document.getElementById('pmErrorActions').style.display = 'none';
            document.getElementById('pmCloseBtn').style.display = 'flex';

            this._isRunning = false;
        },

        /**
         * showError(message, onRetry) — Display the error state
         * message: string error message to show
         * onRetry: optional callback function for the retry button
         */
        showError: function(message, onRetry) {
            this._onRetry = onRetry || null;

            // Mark current active step as error
            document.querySelectorAll('.pm-step-item.active').forEach(s => {
                s.classList.remove('active');
                s.classList.add('error');
            });

            // Switch to error content
            document.querySelectorAll('.pm-step-content').forEach(c => c.classList.remove('active'));
            document.getElementById('pmErrorContent').classList.add('active');
            document.getElementById('pmErrorMessage').textContent = message || 'An unexpected error occurred.';

            // Show error actions
            document.getElementById('pmActions').style.display = 'none';
            document.getElementById('pmErrorActions').style.display = 'grid';
            document.getElementById('pmCloseBtn').style.display = 'flex';

            // Wire retry button
            const retryBtn = document.getElementById('pmBtnRetry');
            if (onRetry) {
                retryBtn.style.display = '';
                retryBtn.onclick = () => {
                    this._isRunning = false;
                    this.close();
                    if (this._onRetry) this._onRetry();
                };
            } else {
                retryBtn.style.display = 'none';
            }

            this._isRunning = false;
        },

        /**
         * close() — Close the modal and reset state
         */
        close: function() {
            document.getElementById('paymentProcessingOverlay').classList.remove('active');
            document.body.style.overflow = 'auto';
            this._isRunning = false;
            this._onRetry = null;
        },

        /**
         * onViewRecords() — Close and navigate to records
         */
        onViewRecords: function() {
            this.close();
            if (window.onPaymentRecordsView) window.onPaymentRecordsView();
        },

        /* ── Legacy compatibility: start() maps to new API ── */
        start: function(data) {
            console.warn('[PaymentProcessor] start() is deprecated. Use open() + goToStep() + showSuccess() instead.');
            return this.open(data);
        },

        /* ── Legacy compatibility: populate() ── */
        populate: function(data) {
            document.getElementById('pmResStudent').textContent = data.studentName || 'N/A';
            document.getElementById('pmResMethod').textContent = data.method || 'Manual Payment';
            document.getElementById('pmResTxn').textContent = data.txnId || 'N/A';
            document.getElementById('pmResAmount').textContent = data.amount || '0.00';
            const dlBtn = document.getElementById('pmBtnDownload');
            if (data.downloadUrl && data.downloadUrl !== '#') {
                dlBtn.onclick = () => window.open(data.downloadUrl, '_blank');
            }
        },

        /* ── Private helpers ── */
        _reset: function() {
            document.querySelectorAll('.pm-step-content').forEach(c => c.classList.remove('active'));
            document.getElementById('pmContent1').classList.add('active');
            document.querySelectorAll('.pm-step-item').forEach(s => s.classList.remove('active', 'completed', 'error'));
            document.getElementById('pmStep1').classList.add('active');
            document.getElementById('pmActions').style.display = 'none';
            document.getElementById('pmErrorActions').style.display = 'none';
            document.getElementById('pmCloseBtn').style.display = 'none';
            document.getElementById('pmStepsIndicator').style.display = 'flex';
            document.getElementById('pmHeader').style.display = 'block';
            document.getElementById('pmMainTitle').textContent = 'Processing Payment';
            document.getElementById('pmMainSubtitle').textContent = 'Please wait while we finalize the transaction';
            document.getElementById('pmPdfProgress').style.width = '0%';
        },

        _populatePreview: function(data) {
            document.getElementById('pmResStudent').textContent = data.studentName || 'N/A';
            document.getElementById('pmResMethod').textContent = data.method || 'Manual Payment';
            document.getElementById('pmResAmount').textContent = data.amount || '0.00';
        },

        _wait: (ms) => new Promise(resolve => setTimeout(resolve, ms)),

        _animateProgress: function(id, duration) {
            const el = document.getElementById(id);
            if (!el) return;
            el.style.width = '0%';
            let progress = 0;
            const interval = 20;
            const step = 100 / (duration / interval);
            const timer = setInterval(() => {
                progress += step;
                if (progress >= 100) {
                    el.style.width = '100%';
                    clearInterval(timer);
                } else {
                    el.style.width = progress + '%';
                }
            }, interval);
        }
    };
</script>

