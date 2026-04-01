/**
 * Feedback Module - SPA Handler
 * File: public/assets/js/ia-feedback.js
 */

(function() {
    'use strict';

    window.renderFeedbackPage = async function() {
        const mc = document.getElementById('mainContent');
        if (!mc) return;

        // Show loading state (reusing support styles)
        mc.innerHTML = `
            <div class="pg-loading">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <p>Loading Feedback Module...</p>
            </div>
        `;

        try {
            const url = window.APP_URL + '/resources/views/admin/feedback.php?spa=true';
            const response = await fetch(url);
            
            if (!response.ok) throw new Error('Failed to load feedback page');
            
            const html = await response.text();
            mc.innerHTML = html;

            // Update URL and Title
            window.history.pushState({ nav: 'feedback' }, 'Feedback', '?page=feedback');
            document.title = 'Feedback | iSoftro ERP';

        } catch (error) {
            console.error('Feedback SPA Error:', error);
            mc.innerHTML = `
                <div class="pg fu">
                    <div class="card" style="text-align:center; padding:100px 20px;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size:3rem; color: #ef4444; margin-bottom:20px;"></i>
                        <h2>Oops! Connection Error</h2>
                        <p style="margin:10px 0 24px; color:var(--text-body);">We couldn't load the feedback page. Please try again.</p>
                        <button onclick="window.renderFeedbackPage()" class="btn-primary" style="background:var(--green); color:#fff; border:none; padding:12px 24px; border-radius:10px; cursor:pointer;">
                            Retry Connection
                        </button>
                    </div>
                </div>
            `;
        }
    };

})();
