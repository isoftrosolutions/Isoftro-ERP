/**
 * ia-support.js
 * Support Page SPA Functionality
 * Handles dynamic content loading, form submissions, FAQ accordions, and ticket management
 * 
 * @package HamroERP
 * @version 1.0.0
 */

(function() {
    'use strict';

    // ========================================
    // State Management
    // ========================================
    const SupportState = {
        currentTab: 'tutorials',
        faqSearchTerm: '',
        activeFAQ: null,
        tickets: [],
        isLoading: false,
        ticketFormData: {}
    };

    // ========================================
    // API Configuration
    // ========================================
    const API_BASE = window.APP_URL || '';

    /**
     * Fetch with error handling
     */
    async function apiFetch(endpoint, options = {}) {
        const defaultOptions = {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            defaultOptions.headers['X-CSRF-TOKEN'] = csrfToken.content;
        }

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {})
            }
        };

        try {
            const response = await fetch(`${API_BASE}${endpoint}`, mergedOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
        } catch (error) {
            console.error('[Support API Error]', error);
            throw error;
        }
    }

    // ========================================
    // Tab Navigation
    // ========================================
    window.initSupportTabs = function() {
        const tabs = document.querySelectorAll('.support-tab');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.dataset.tab;
                switchSupportTab(tabId);
            });
        });
    };

    window.switchSupportTab = function(tabId) {
        // Update tab active state
        document.querySelectorAll('.support-tab').forEach(t => {
            t.classList.remove('active');
            if (t.dataset.tab === tabId) {
                t.classList.add('active');
            }
        });

        // Update content visibility
        document.querySelectorAll('.support-tab-content').forEach(content => {
            content.classList.remove('active');
            if (content.id === `tab-${tabId}`) {
                content.classList.add('active');
            }
        });

        SupportState.currentTab = tabId;

        // Update URL for history navigation
        if (window.history && window.history.pushState) {
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('tab', tabId);
            window.history.pushState({ tab: tabId }, '', newUrl);
        }

        // Load tab-specific data
        loadTabData(tabId);
    };

    async function loadTabData(tabId) {
        switch (tabId) {
            case 'tickets':
                await loadTickets();
                break;
            case 'contact':
                // Contact info is static, no loading needed
                break;
            case 'tutorials':
            case 'faq':
            default:
                // FAQ and tutorials are static content
                break;
        }
    }

    // ========================================
    // FAQ Functions
    // ========================================
    window.initFAQAccordion = function() {
        const faqQuestions = document.querySelectorAll('.faq-question');
        
        faqQuestions.forEach(question => {
            // Remove existing onclick and add proper event listener
            question.removeAttribute('onclick');
            question.addEventListener('click', function() {
                toggleFAQ(this);
            });
        });
    };

    window.toggleFAQ = function(element) {
        const answer = element.nextElementSibling;
        const isActive = element.classList.contains('active');
        const allowMultiple = document.body.classList.contains('faq-multiple-open');
        
        if (!allowMultiple) {
            // Close all others
            document.querySelectorAll('.faq-question').forEach(q => {
                q.classList.remove('active');
                const a = q.nextElementSibling;
                if (a && a.classList.contains('faq-answer')) {
                    a.classList.remove('active');
                }
            });
        }
        
        // Toggle current
        if (!isActive) {
            element.classList.add('active');
            if (answer && answer.classList.contains('faq-answer')) {
                answer.classList.add('active');
            }
            SupportState.activeFAQ = element;
        } else {
            element.classList.remove('active');
            if (answer && answer.classList.contains('faq-answer')) {
                answer.classList.remove('active');
            }
            SupportState.activeFAQ = null;
        }
    };

    window.searchFAQ = function() {
        const searchTerm = document.getElementById('faqSearch')?.value.toLowerCase() || '';
        SupportState.faqSearchTerm = searchTerm;
        
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            const answer = item.querySelector('.faq-answer');
            
            if (!question || !answer) return;
            
            const questionText = question.textContent.toLowerCase();
            const answerText = answer.textContent.toLowerCase();
            
            if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                item.style.display = 'block';
                item.classList.add('faq-visible');
                item.classList.remove('faq-hidden');
            } else {
                item.style.display = 'none';
                item.classList.add('faq-hidden');
                item.classList.remove('faq-visible');
            }
        });

        // Update URL with search term
        if (window.history && window.history.pushState) {
            const newUrl = new URL(window.location.href);
            if (searchTerm) {
                newUrl.searchParams.set('q', searchTerm);
            } else {
                newUrl.searchParams.delete('q');
            }
            window.history.pushState({ search: searchTerm }, '', newUrl);
        }
    };

    // Debounced search for better performance
    let searchTimeout;
    window.debouncedSearchFAQ = function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchFAQ();
        }, 300);
    };

    // ========================================
    // Scroll to Section
    // ========================================
    window.scrollToSection = function(id) {
        const element = document.getElementById(id);
        if (element) {
            element.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
            
            // Update URL without reload
            if (window.history && window.history.pushState) {
                const newUrl = new URL(window.location.href);
                newUrl.hash = id;
                window.history.pushState({ section: id }, '', newUrl);
            }
        }
    };

    // ========================================
    // Ticket Management
    // ========================================
    async function loadTickets() {
        const container = document.getElementById('ticketListContainer');
        if (!container) return;

        // Show loading
        container.innerHTML = `
            <div class="support-loading">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
                <p>Loading tickets...</p>
            </div>
        `;

        try {
            const data = await apiFetch('/api/frontdesk/support?action=list');
            
            if (data.success) {
                SupportState.tickets = data.data || [];
                renderTicketList(SupportState.tickets);
            } else {
                throw new Error(data.message || 'Failed to load tickets');
            }
        } catch (error) {
            console.error('[Load Tickets Error]', error);
            container.innerHTML = `
                <div class="support-empty">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <h3>Unable to load tickets</h3>
                    <p>Please try again later.</p>
                </div>
            `;
        }
    }

    function renderTicketList(tickets) {
        const container = document.getElementById('ticketListContainer');
        if (!container) return;

        if (!tickets || tickets.length === 0) {
            container.innerHTML = `
                <div class="support-empty">
                    <i class="fa-solid fa-ticket"></i>
                    <h3>No tickets found</h3>
                    <p>You haven't submitted any support tickets yet.</p>
                </div>
            `;
            return;
        }

        const ticketsHTML = tickets.map(ticket => `
            <div class="ticket-item" onclick="viewTicket(${ticket.id})">
                <div class="ticket-header">
                    <span class="ticket-title">${escapeHtml(ticket.subject)}</span>
                    <span class="ticket-status ${ticket.status}">${ticket.status}</span>
                </div>
                <div class="ticket-meta">
                    <span><i class="fa-solid fa-calendar"></i> ${formatDate(ticket.created_at)}</span>
                    <span><i class="fa-solid fa-tag"></i> ${escapeHtml(ticket.category || 'General')}</span>
                    <span><i class="fa-solid fa-flag"></i> ${escapeHtml(ticket.priority || 'Normal')}</span>
                </div>
            </div>
        `).join('');

        container.innerHTML = `<div class="ticket-list">${ticketsHTML}</div>`;
    }

    window.viewTicket = async function(ticketId) {
        try {
            const data = await apiFetch(`/api/frontdesk/support?action=view&id=${ticketId}`);
            
            if (data.success) {
                showTicketModal(data.data || data.ticket);
            }
        } catch (error) {
            console.error('[View Ticket Error]', error);
            showToast('Unable to load ticket details', 'error');
        }
    };

    function showTicketModal(ticket) {
        const modalHTML = `
            <div class="support-modal-overlay active" id="ticketModal">
                <div class="support-modal">
                    <div class="support-modal-header">
                        <h3>${escapeHtml(ticket.subject)}</h3>
                        <button class="support-modal-close" onclick="closeTicketModal()">&times;</button>
                    </div>
                    <div class="support-modal-body">
                        <div class="ticket-detail">
                            <p><strong>Status:</strong> <span class="ticket-status ${ticket.status}">${ticket.status}</span></p>
                            <p><strong>Priority:</strong> ${escapeHtml(ticket.priority || 'Normal')}</p>
                            <p><strong>Category:</strong> ${escapeHtml(ticket.category || 'General')}</p>
                            <p><strong>Created:</strong> ${formatDate(ticket.created_at)}</p>
                            <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--card-border);">
                            <div class="ticket-description">
                                ${escapeHtml(ticket.description)}
                            </div>
                            ${ticket.response ? `
                                <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--card-border);">
                                <div class="ticket-response">
                                    <strong>Support Response:</strong>
                                    <p>${escapeHtml(ticket.response)}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        const existingModal = document.getElementById('ticketModal');
        if (existingModal) {
            existingModal.remove();
        }

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Add close handler
        document.getElementById('ticketModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTicketModal();
            }
        });
    }

    window.closeTicketModal = function() {
        const modal = document.getElementById('ticketModal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => modal.remove(), 300);
        }
    };

    // ========================================
    // Ticket Form
    // ========================================
    window.initTicketForm = function() {
        const form = document.getElementById('supportTicketForm');
        if (!form) return;

        // Remove existing submit handler
        form.removeEventListener('submit', handleTicketSubmit);
        
        // Add new handler
        form.addEventListener('submit', handleTicketSubmit);

        // Add input change handlers for validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                SupportState.ticketFormData[this.name] = this.value;
                validateField(this);
            });
        });
    };

    async function handleTicketSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('.ticket-submit-btn');
        
        // Validate all fields
        const inputs = form.querySelectorAll('[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        // Show loading state
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Submitting...';
        }

        try {
            const formData = new FormData(form);
            
            const data = await apiFetch('/api/frontdesk/support?action=create', {
                method: 'POST',
                body: formData
            });

            if (data.success) {
                showToast('Ticket submitted successfully!', 'success');
                form.reset();
                SupportState.ticketFormData = {};
                
                // Reload tickets if on tickets tab
                if (SupportState.currentTab === 'tickets') {
                    await loadTickets();
                }
            } else {
                throw new Error(data.message || 'Failed to submit ticket');
            }
        } catch (error) {
            console.error('[Submit Ticket Error]', error);
            showToast(error.message || 'Failed to submit ticket. Please try again.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Ticket';
            }
        }
    }

    function validateField(input) {
        const value = input.value.trim();
        const required = input.hasAttribute('required');
        const type = input.type;
        
        let isValid = true;
        let errorMessage = '';

        // Check required
        if (required && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }

        // Check email format
        if (isValid && type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }

        // Check min length
        if (isValid && input.minLength && value.length < input.minLength) {
            isValid = false;
            errorMessage = `Minimum ${input.minLength} characters required`;
        }

        // Update UI
        const formGroup = input.closest('.form-group') || input.parentElement;
        const existingError = formGroup.querySelector('.field-error');
        
        if (existingError) {
            existingError.remove();
        }

        if (!isValid) {
            input.style.borderColor = '#ef4444';
            if (errorMessage) {
                const errorEl = document.createElement('span');
                errorEl.className = 'field-error';
                errorEl.style.color = '#ef4444';
                errorEl.style.fontSize = '0.8rem';
                errorEl.textContent = errorMessage;
                formGroup.appendChild(errorEl);
            }
        } else {
            input.style.borderColor = '';
        }

        return isValid;
    }

    // ========================================
    // Toast Notifications
    // ========================================
    window.showSupportToast = function(message, type = 'info', duration = 4000) {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.support-toast');
        existingToasts.forEach(t => t.remove());

        const toast = document.createElement('div');
        toast.className = `support-toast ${type}`;
        
        let icon = '';
        switch (type) {
            case 'success':
                icon = '<i class="fa-solid fa-check-circle"></i>';
                break;
            case 'error':
                icon = '<i class="fa-solid fa-circle-xmark"></i>';
                break;
            case 'info':
            default:
                icon = '<i class="fa-solid fa-circle-info"></i>';
        }
        
        toast.innerHTML = `${icon} <span>${escapeHtml(message)}</span>`;
        document.body.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // Alias for compatibility
    window.showToast = window.showSupportToast;

    // ========================================
    // Quick Help Card Clicks
    // ========================================
    window.initQuickHelpCards = function() {
        const cards = document.querySelectorAll('.quick-help-card');
        
        cards.forEach(card => {
            card.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // If it's an anchor link, handle it via JS
                if (href && href.startsWith('#')) {
                    e.preventDefault();
                    scrollToSection(href.substring(1));
                }
                // External links open normally
            });
        });
    };

    // ========================================
    // Utility Functions
    // ========================================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    // ========================================
    // History Navigation
    // ========================================
    function initHistoryNavigation() {
        if (!window.history || !window.history.pushState) return;

        // Handle browser back/forward
        window.addEventListener('popstate', function(e) {
            const params = new URLSearchParams(window.location.search);
            
            // Handle tab
            const tab = params.get('tab');
            if (tab && tab !== SupportState.currentTab) {
                switchSupportTab(tab);
            }

            // Handle search
            const search = params.get('q');
            const searchInput = document.getElementById('faqSearch');
            if (search && searchInput && search !== SupportState.faqSearchTerm) {
                searchInput.value = search;
                searchFAQ();
            }

            // Handle section scroll
            const hash = window.location.hash.substring(1);
            if (hash) {
                const element = document.getElementById(hash);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    }

    // ========================================
    // URL Parameter Initialization
    // ========================================
    function initFromURL() {
        const params = new URLSearchParams(window.location.search);
        
        // Set initial tab
        const tab = params.get('tab');
        if (tab) {
            // Delay to ensure DOM is ready
            setTimeout(() => switchSupportTab(tab), 100);
        }

        // Set initial search
        const search = params.get('q');
        const searchInput = document.getElementById('faqSearch');
        if (search && searchInput) {
            searchInput.value = search;
            searchFAQ();
        }

        // Handle hash
        const hash = window.location.hash.substring(1);
        if (hash) {
            setTimeout(() => {
                const element = document.getElementById(hash);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth' });
                }
            }, 100);
        }
    }

    // ========================================
    // Initialization
    // ========================================
    window.initSupportPage = function() {
        console.log('[Support] Initializing Support Page...');
        
        // Initialize components
        initSupportTabs();
        initFAQAccordion();
        initTicketForm();
        initQuickHelpCards();
        
        // Initialize history navigation
        initHistoryNavigation();
        
        // Initialize from URL params
        initFromURL();
        
        // Add search listener
        const searchInput = document.getElementById('faqSearch');
        if (searchInput) {
            searchInput.addEventListener('input', debouncedSearchFAQ);
        }

        console.log('[Support] Support Page initialized successfully');
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initSupportPage);
    } else {
        // DOM already loaded
        window.initSupportPage();
    }

    // ========================================
    // SPA Integration - Fetch Fragment
    // ========================================
    window.renderSupportPage = async function() {
        const mc = document.getElementById('mainContent');
        if (!mc) return;

        // Dynamically load support CSS if not already loaded
        if (!document.getElementById('support-css')) {
            const link = document.createElement('link');
            link.id = 'support-css';
            link.rel = 'stylesheet';
            link.href = (window.APP_URL || '') + '/assets/css/ia-support.css?v=' + Date.now();
            document.head.appendChild(link);
        }

        // Show loading state
        mc.innerHTML = `
            <div class="pg fu">
                <div class="pg-loading">
                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                    <span>Loading Help & Support...</span>
                </div>
            </div>
        `;

        try {
            const res = await fetch(`${window.APP_URL || ''}/resources/views/admin/support.php?spa=true`);
            if (!res.ok) throw new Error('Fragment not found');
            const html = await res.text();
            
            // Inject and scroll to top
            mc.innerHTML = html;
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Initialize all support listeners
            window.initSupportPage();
            
        } catch (err) {
            console.error('[Support SPA] Failed to load:', err);
            mc.innerHTML = `
                <div class="pg fu">
                    <div class="card" style="text-align:center;padding:100px 40px;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size:3rem;color:#ef4444;margin-bottom:20px;"></i>
                        <h2>Failed to Load Support</h2>
                        <p style="color:#64748b;margin-top:10px;">Please check your connection or try again later.</p>
                        <button class="btn bt" style="margin-top:20px;" onclick="renderSupportPage()">Retry</button>
                    </div>
                </div>
            `;
        }
    };

    // Expose functions globally for inline handlers
    // window.renderSupportPage = window.initSupportPage; (Moved above)
    window.switchTab = window.switchSupportTab;
    window.toggleFAQ = window.toggleFAQ;
    window.searchFAQ = window.searchFAQ;
    window.scrollToSection = window.scrollToSection;

})();
