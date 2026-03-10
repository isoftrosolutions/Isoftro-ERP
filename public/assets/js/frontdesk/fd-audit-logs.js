/**
 * Institute Admin - Audit Logs Module
 * Handles rendering and functionality for the Audit Logs page
 */

(function() {
    'use strict';

    // Audit logs state
    let auditLogsData = [];
    let filteredLogs = [];
    let currentPage = 1;
    let itemsPerPage = 20;
    let filters = {
        search: '',
        action: '',
        tableName: '',
        dateFrom: '',
        dateTo: ''
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        window.renderAuditLogs = renderAuditLogs;
    });

    /**
     * Main render function for Audit Logs page
     */
    function renderAuditLogs() {
        const mc = document.getElementById('mainContent');
        if (!mc) return;

        mc.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> 
                    <span class="bc-sep">&rsaquo;</span> 
                    <span class="bc-cur">Audit Logs</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-shield-halved"></i></div>
                        <div>
                            <div class="pg-title">Audit Logs</div>
                            <div class="pg-sub">Track all system activities and changes</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bs" onclick="exportAuditLogs()">
                            <i class="fa-solid fa-download"></i> Export
                        </button>
                    </div>
                </div>
                
                <!-- Filters Section -->
                <div class="card" style="margin-bottom: 20px; padding: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Search</label>
                            <input type="text" id="audit-search" class="input-v2" placeholder="Search by description, user..." 
                                style="width: 100%; padding: 10px 12px; border: 1px solid var(--card-border); border-radius: 8px; background: var(--bg); color: var(--text-body);"
                                onkeyup="debounceAuditSearch(this.value)">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Action Type</label>
                            <select id="audit-action-filter" class="input-v2" 
                                style="width: 100%; padding: 10px 12px; border: 1px solid var(--card-border); border-radius: 8px; background: var(--bg); color: var(--text-body);"
                                onchange="applyAuditFilters()">
                                <option value="">All Actions</option>
                                <option value="CREATE">CREATE</option>
                                <option value="UPDATE">UPDATE</option>
                                <option value="DELETE">DELETE</option>
                                <option value="LOGIN">LOGIN</option>
                                <option value="LOGOUT">LOGOUT</option>
                                <option value="PAYMENT_RECORDED">PAYMENT</option>
                                <option value="TRANSACTION_CREATED">TRANSACTION</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Table</label>
                            <select id="audit-table-filter" class="input-v2" 
                                style="width: 100%; padding: 10px 12px; border: 1px solid var(--card-border); border-radius: 8px; background: var(--bg); color: var(--text-body);"
                                onchange="applyAuditFilters()">
                                <option value="">All Tables</option>
                                <option value="students">Students</option>
                                <option value="attendance_records">Attendance</option>
                                <option value="fee_records">Fee Records</option>
                                <option value="payment_transactions">Payments</option>
                                <option value="users">Users</option>
                                <option value="inquiries">Inquiries</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Date From</label>
                            <input type="date" id="audit-date-from" class="input-v2" 
                                style="width: 100%; padding: 10px 12px; border: 1px solid var(--card-border); border-radius: 8px; background: var(--bg); color: var(--text-body);"
                                onchange="applyAuditFilters()">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px;">Date To</label>
                            <input type="date" id="audit-date-to" class="input-v2" 
                                style="width: 100%; padding: 10px 12px; border: 1px solid var(--card-border); border-radius: 8px; background: var(--bg); color: var(--text-body);"
                                onchange="applyAuditFilters()">
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 20px;">
                    <div class="card" style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-list" style="color: #3b82f6;"></i>
                            </div>
                            <div>
                                <div style="font-size: 24px; font-weight: 700; color: var(--text-primary);" id="total-logs-count">0</div>
                                <div style="font-size: 12px; color: var(--text-secondary);">Total Logs</div>
                            </div>
                        </div>
                    </div>
                    <div class="card" style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-plus" style="color: #10b981;"></i>
                            </div>
                            <div>
                                <div style="font-size: 24px; font-weight: 700; color: var(--text-primary);" id="create-logs-count">0</div>
                                <div style="font-size: 12px; color: var(--text-secondary);">Creates</div>
                            </div>
                        </div>
                    </div>
                    <div class="card" style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-pen" style="color: #f59e0b;"></i>
                            </div>
                            <div>
                                <div style="font-size: 24px; font-weight: 700; color: var(--text-primary);" id="update-logs-count">0</div>
                                <div style="font-size: 12px; color: var(--text-secondary);">Updates</div>
                            </div>
                        </div>
                    </div>
                    <div class="card" style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-trash" style="color: #ef4444;"></i>
                            </div>
                            <div>
                                <div style="font-size: 24px; font-weight: 700; color: var(--text-primary);" id="delete-logs-count">0</div>
                                <div style="font-size: 12px; color: var(--text-secondary);">Deletes</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="card" style="padding: 0; overflow: hidden;">
                    <div style="overflow-x: auto;">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg);">
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Timestamp</th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">User</th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Action</th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Table</th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Record ID</th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Description</th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">IP Address</th>
                                    <th style="padding: 14px 16px; text-align: center; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Details</th>
                                </tr>
                            </thead>
                            <tbody id="audit-logs-tbody">
                                <tr>
                                    <td colspan="8" style="padding: 40px; text-align: center;">
                                        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 24px; color: var(--text-secondary);"></i>
                                        <p style="margin-top: 10px; color: var(--text-secondary);">Loading audit logs...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div style="padding: 16px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--card-border);">
                        <div style="font-size: 13px; color: var(--text-secondary);">
                            Showing <span id="showing-from">0</span> to <span id="showing-to">0</span> of <span id="total-showing">0</span> entries
                        </div>
                        <div style="display: flex; gap: 8px;" id="audit-pagination">
                            <!-- Pagination buttons will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Load audit logs data
        fetchAuditLogs();
    }

    /**
     * Fetch audit logs from API
     */
    async function fetchAuditLogs() {
        try {
            const response = await fetch(APP_URL + '/api/frontdesk/audit-logs', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to fetch audit logs');
            }

            const result = await response.json();
            auditLogsData = result.data || result || [];
            applyFilters();
        } catch (error) {
            console.error('Error fetching audit logs:', error);
            // For demo purposes, use sample data if API fails
            loadSampleData();
        }
    }

    /**
     * Load sample data for demonstration
     */
    function loadSampleData() {
        const now = new Date();
        auditLogsData = [];
        
        // Generate sample audit logs
        const actions = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'PAYMENT_RECORDED'];
        const tables = ['students', 'attendance_records', 'fee_records', 'payment_transactions', 'users', 'inquiries'];
        const users = ['admin@example.com', 'teacher@example.com', 'frontdesk@example.com', 'system'];
        
        for (let i = 0; i < 50; i++) {
            const date = new Date(now.getTime() - Math.random() * 7 * 24 * 60 * 60 * 1000);
            auditLogsData.push({
                id: i + 1,
                created_at: date.toISOString(),
                user_email: users[Math.floor(Math.random() * users.length)],
                action: actions[Math.floor(Math.random() * actions.length)],
                table_name: tables[Math.floor(Math.random() * tables.length)],
                record_id: Math.floor(Math.random() * 1000) + 1,
                description: `Sample audit log entry ${i + 1}`,
                ip_address: `192.168.1.${Math.floor(Math.random() * 255)}`,
                changes: null
            });
        }
        
        applyFilters();
    }

    /**
     * Apply filters to audit logs
     */
    function applyFilters() {
        filteredLogs = auditLogsData.filter(log => {
            // Search filter
            if (filters.search) {
                const searchLower = filters.search.toLowerCase();
                const matchesSearch = 
                    (log.description && log.description.toLowerCase().includes(searchLower)) ||
                    (log.user_email && log.user_email.toLowerCase().includes(searchLower)) ||
                    (log.action && log.action.toLowerCase().includes(searchLower));
                if (!matchesSearch) return false;
            }
            
            // Action filter
            if (filters.action && log.action !== filters.action) {
                return false;
            }
            
            // Table filter
            if (filters.tableName && log.table_name !== filters.tableName) {
                return false;
            }
            
            // Date filters
            if (filters.dateFrom) {
                const logDate = new Date(log.created_at).toISOString().split('T')[0];
                if (logDate < filters.dateFrom) return false;
            }
            
            if (filters.dateTo) {
                const logDate = new Date(log.created_at).toISOString().split('T')[0];
                if (logDate > filters.dateTo) return false;
            }
            
            return true;
        });

        // Update stats
        updateStats();
        
        // Reset to first page
        currentPage = 1;
        
        // Render table
        renderLogsTable();
    }

    /**
     * Update statistics cards
     */
    function updateStats() {
        const total = filteredLogs.length;
        const creates = filteredLogs.filter(l => l.action === 'CREATE').length;
        const updates = filteredLogs.filter(l => l.action === 'UPDATE').length;
        const deletes = filteredLogs.filter(l => l.action === 'DELETE').length;

        document.getElementById('total-logs-count').textContent = total;
        document.getElementById('create-logs-count').textContent = creates;
        document.getElementById('update-logs-count').textContent = updates;
        document.getElementById('delete-logs-count').textContent = deletes;
    }

    /**
     * Render the logs table
     */
    function renderLogsTable() {
        const tbody = document.getElementById('audit-logs-tbody');
        if (!tbody) return;

        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageLogs = filteredLogs.slice(start, end);

        if (pageLogs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="padding: 40px; text-align: center;">
                        <i class="fa-solid fa-shield-halved" style="font-size: 48px; color: var(--text-secondary); opacity: 0.3;"></i>
                        <p style="margin-top: 15px; color: var(--text-secondary);">No audit logs found</p>
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = pageLogs.map(log => {
                const actionClass = getActionClass(log.action);
                const timestamp = new Date(log.created_at).toLocaleString();
                
                return `
                    <tr style="border-bottom: 1px solid var(--card-border);">
                        <td style="padding: 12px 16px; font-size: 13px; color: var(--text-secondary);">${timestamp}</td>
                        <td style="padding: 12px 16px; font-size: 13px;">${log.user_email || 'System'}</td>
                        <td style="padding: 12px 16px;">
                            <span class="badge" style="background: ${actionClass.bg}; color: ${actionClass.color}; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                                ${log.action}
                            </span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 13px;">${log.table_name || '-'}</td>
                        <td style="padding: 12px 16px; font-size: 13px; color: var(--text-secondary);">${log.record_id || '-'}</td>
                        <td style="padding: 12px 16px; font-size: 13px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${log.description || ''}">
                            ${log.description || '-'}
                        </td>
                        <td style="padding: 12px 16px; font-size: 13px; color: var(--text-secondary);">${log.ip_address || '-'}</td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <button class="btn-icon" title="View Details" onclick="viewAuditLogDetails(${log.id})">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Update pagination info
        document.getElementById('showing-from').textContent = filteredLogs.length > 0 ? start + 1 : 0;
        document.getElementById('showing-to').textContent = Math.min(end, filteredLogs.length);
        document.getElementById('total-showing').textContent = filteredLogs.length;

        // Render pagination
        renderPagination();
    }

    /**
     * Get action badge styles
     */
    function getActionClass(action) {
        const classes = {
            'CREATE': { bg: 'rgba(16, 185, 129, 0.1)', color: '#10b981' },
            'UPDATE': { bg: 'rgba(245, 158, 11, 0.1)', color: '#f59e0b' },
            'DELETE': { bg: 'rgba(239, 68, 68, 0.1)', color: '#ef4444' },
            'LOGIN': { bg: 'rgba(59, 130, 246, 0.1)', color: '#3b82f6' },
            'LOGOUT': { bg: 'rgba(107, 114, 128, 0.1)', color: '#6b7280' },
            'PAYMENT_RECORDED': { bg: 'rgba(139, 92, 246, 0.1)', color: '#8b5cf6' },
            'TRANSACTION_CREATED': { bg: 'rgba(236, 72, 153, 0.1)', color: '#ec4899' }
        };
        return classes[action] || { bg: 'rgba(107, 114, 128, 0.1)', color: '#6b7280' };
    }

    /**
     * Render pagination buttons
     */
    function renderPagination() {
        const pagination = document.getElementById('audit-pagination');
        if (!pagination) return;

        const totalPages = Math.ceil(filteredLogs.length / itemsPerPage);
        
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let html = '';
        
        // Previous button
        html += `<button class="btn bs btn-sm" onclick="changeAuditPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-left"></i>
        </button>`;

        // Page numbers
        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
        
        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        if (startPage > 1) {
            html += `<button class="btn bs btn-sm" onclick="changeAuditPage(1)">1</button>`;
            if (startPage > 2) {
                html += `<span style="padding: 0 8px; color: var(--text-secondary);">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="btn ${i === currentPage ? 'bt' : 'bs'} btn-sm" onclick="changeAuditPage(${i})">${i}</button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<span style="padding: 0 8px; color: var(--text-secondary);">...</span>`;
            }
            html += `<button class="btn bs btn-sm" onclick="changeAuditPage(${totalPages})">${totalPages}</button>`;
        }

        // Next button
        html += `<button class="btn bs btn-sm" onclick="changeAuditPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-right"></i>
        </button>`;

        pagination.innerHTML = html;
    }

    /**
     * Change page
     */
    window.changeAuditPage = function(page) {
        const totalPages = Math.ceil(filteredLogs.length / itemsPerPage);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderLogsTable();
    };

    /**
     * Apply audit filters from UI
     */
    window.applyAuditFilters = function() {
        filters.search = document.getElementById('audit-search')?.value || '';
        filters.action = document.getElementById('audit-action-filter')?.value || '';
        filters.tableName = document.getElementById('audit-table-filter')?.value || '';
        filters.dateFrom = document.getElementById('audit-date-from')?.value || '';
        filters.dateTo = document.getElementById('audit-date-to')?.value || '';
        applyFilters();
    };

    /**
     * Debounced search
     */
    let auditSearchTimeout;
    window.debounceAuditSearch = function(value) {
        clearTimeout(auditSearchTimeout);
        auditSearchTimeout = setTimeout(() => {
            filters.search = value;
            applyFilters();
        }, 300);
    };

    /**
     * Get module info based on table name
     */
    function getModuleInfo(tableName) {
        const moduleMap = {
            'students': { name: 'Students', icon: 'fa-user-graduate', nav: 'students', color: '#3b82f6' },
            'attendance_records': { name: 'Attendance', icon: 'fa-calendar-check', nav: 'attendance', color: '#10b981' },
            'fee_records': { name: 'Fee Management', icon: 'fa-hand-holding-dollar', nav: 'fee', color: '#f59e0b' },
            'payment_transactions': { name: 'Payments', icon: 'fa-credit-card', nav: 'fee', color: '#8b5cf6' },
            'users': { name: 'Users & Staff', icon: 'fa-users', nav: 'teachers', color: '#ec4899' },
            'inquiries': { name: 'Inquiries', icon: 'fa-clipboard-question', nav: 'inq', color: '#06b6d4' },
            'batches': { name: 'Batches', icon: 'fa-layer-group', nav: 'academic', color: '#84cc16' },
            'courses': { name: 'Courses', icon: 'fa-book', nav: 'academic', color: '#f97316' },
            'subjects': { name: 'Subjects', icon: 'fa-bookmark', nav: 'academic', color: '#14b8a6' },
            'study_materials': { name: 'Study Materials', icon: 'fa-folder-open', nav: 'lms', color: '#a855f7' },
            'exams': { name: 'Examinations', icon: 'fa-file-signature', nav: 'exams', color: '#ef4444' },
            'staff_salary': { name: 'Salary', icon: 'fa-wallet', nav: 'staff-salary', color: '#22c55e' },
            'leave_requests': { name: 'Leave Requests', icon: 'fa-calendar-minus', nav: 'attendance', color: '#eab308' },
            'announcements': { name: 'Announcements', icon: 'fa-bullhorn', nav: 'comms', color: '#f43f5e' }
        };
        return moduleMap[tableName] || { name: tableName || 'Unknown', icon: 'fa-database', nav: null, color: '#6b7280' };
    }

    /**
     * Get task type info based on action
     */
    function getTaskInfo(action) {
        const taskMap = {
            'CREATE': { type: 'Record Creation', icon: 'fa-plus-circle', color: '#10b981', desc: 'New record added to system' },
            'UPDATE': { type: 'Record Update', icon: 'fa-pen-to-square', color: '#f59e0b', desc: 'Existing record modified' },
            'DELETE': { type: 'Record Deletion', icon: 'fa-trash', color: '#ef4444', desc: 'Record removed from system' },
            'LOGIN': { type: 'User Login', icon: 'fa-sign-in-alt', color: '#3b82f6', desc: 'User logged into system' },
            'LOGOUT': { type: 'User Logout', icon: 'fa-sign-out-alt', color: '#6b7280', desc: 'User logged out of system' },
            'PAYMENT_RECORDED': { type: 'Payment Received', icon: 'fa-money-bill-wave', color: '#8b5cf6', desc: 'Payment transaction recorded' },
            'TRANSACTION_CREATED': { type: 'Transaction', icon: 'fa-receipt', color: '#ec4899', desc: 'Financial transaction created' },
            'Tenant Created': { type: 'Tenant Setup', icon: 'fa-building', color: '#14b8a6', desc: 'New institute/tenant created' },
            'Tenant Updated': { type: 'Tenant Update', icon: 'fa-building', color: '#f97316', desc: 'Institute settings updated' },
            'Tenant Deleted': { type: 'Tenant Deletion', icon: 'fa-building-slash', color: '#ef4444', desc: 'Institute removed' },
            'Plan Updated': { type: 'Plan Change', icon: 'fa-tag', color: '#a855f7', desc: 'Subscription plan modified' }
        };
        return taskMap[action] || { type: action || 'Unknown', icon: 'fa-circle-notch', color: '#6b7280', desc: 'System operation' };
    }

    /**
     * Generate navigation link for the record
     */
    function getNavLink(tableName, recordId) {
        if (!tableName || !recordId) return null;
        
        const links = {
            'students': `goNav('students', 'view', {id: ${recordId}})`,
            'attendance_records': `goNav('attendance', 'report')`,
            'fee_records': `goNav('fee', 'details', {receipt_no: '${recordId}'})`,
            'payment_transactions': `goNav('fee', 'details', {receipt_no: '${recordId}'})`,
            'users': `goNav('teachers', null)`,
            'inquiries': `goNav('inquiries')`,
            'batches': `goNav('academic', 'batches', {id: ${recordId}})`,
            'courses': `goNav('academic', 'courses', {id: ${recordId}})`,
            'subjects': `goNav('academic', 'subjects', {id: ${recordId}})`,
            'study_materials': `goNav('lms', 'materials')`,
            'exams': `goNav('exams', 'schedule')`,
            'staff_salary': `goNav('staff-salary')`,
            'leave_requests': `goNav('attendance', 'leave')`
        };
        return links[tableName] || null;
    }

    /**
     * View audit log details
     */
    window.viewAuditLogDetails = function(logId) {
        const log = auditLogsData.find(l => l.id === logId);
        if (!log) return;

        const changes = log.changes ? JSON.parse(log.changes) : null;
        const moduleInfo = getModuleInfo(log.table_name);
        const taskInfo = getTaskInfo(log.action);
        const navLink = getNavLink(log.table_name, log.record_id);
        
        // Parse new values to extract useful info
        let recordInfo = null;
        if (changes && changes.new) {
            try {
                const newData = typeof changes.new === 'string' ? JSON.parse(changes.new) : changes.new;
                recordInfo = {
                    name: newData.full_name || newData.name || newData.title || null,
                    email: newData.email || null,
                    phone: newData.phone || newData.contact_number || null,
                    status: newData.status || null
                };
            } catch(e) {}
        }
        
        let changesHtml = '<p style="color: var(--text-secondary);">No changes recorded</p>';
        if (changes) {
            if (changes.old && changes.new) {
                changesHtml = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-arrow-left" style="color: #ef4444;"></i> Previous Values
                            </div>
                            <pre style="background: #fef2f2; padding: 12px; border-radius: 8px; font-size: 12px; overflow-x: auto; border-left: 3px solid #ef4444;">${JSON.stringify(changes.old, null, 2)}</pre>
                        </div>
                        <div>
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-arrow-right" style="color: #10b981;"></i> New Values
                            </div>
                            <pre style="background: #ecfdf5; padding: 12px; border-radius: 8px; font-size: 12px; overflow-x: auto; border-left: 3px solid #10b981;">${JSON.stringify(changes.new, null, 2)}</pre>
                        </div>
                    </div>
                `;
            } else {
                changesHtml = `<pre style="background: var(--bg); padding: 12px; border-radius: 8px; font-size: 12px; overflow-x: auto;">${JSON.stringify(changes, null, 2)}</pre>`;
            }
        }

        const modalHtml = `
            <div id="audit-log-modal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;" onclick="if(event.target === this) this.remove()">
                <div style="background: var(--card-bg); border-radius: 16px; width: 95%; max-width: 900px; max-height: 95vh; overflow: auto;">
                    <div style="padding: 20px 24px; border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, ${moduleInfo.color}15, transparent);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: ${moduleInfo.color}20; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid ${moduleInfo.icon}" style="font-size: 20px; color: ${moduleInfo.color};"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 18px;">${taskInfo.type} - ${moduleInfo.name}</h3>
                                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--text-secondary);">${taskInfo.desc}</p>
                            </div>
                        </div>
                        <button onclick="this.closest('#audit-log-modal').remove()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-secondary); padding: 8px;">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div style="padding: 24px;">
                        <!-- Task & Module Info Banner -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                            <div style="background: linear-gradient(135deg, ${taskInfo.color}15, transparent); padding: 16px; border-radius: 12px; border: 1px solid ${taskInfo.color}30;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <i class="fa-solid ${taskInfo.icon}" style="color: ${taskInfo.color};"></i>
                                    <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">OPERATION TYPE</span>
                                </div>
                                <div style="font-weight: 600; font-size: 14px;">${taskInfo.type}</div>
                            </div>
                            <div style="background: linear-gradient(135deg, ${moduleInfo.color}15, transparent); padding: 16px; border-radius: 12px; border: 1px solid ${moduleInfo.color}30;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <i class="fa-solid ${moduleInfo.icon}" style="color: ${moduleInfo.color};"></i>
                                    <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">RELATED MODULE</span>
                                </div>
                                <div style="font-weight: 600; font-size: 14px;">${moduleInfo.name}</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #3b82f615, transparent); padding: 16px; border-radius: 12px; border: 1px solid #3b82f630;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <i class="fa-solid fa-user" style="color: #3b82f6;"></i>
                                    <span style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">USER</span>
                                </div>
                                <div style="font-weight: 600; font-size: 14px;">${log.user_email || 'System'}</div>
                            </div>
                        </div>

                        <!-- Record Details -->
                        ${recordInfo ? `
                        <div style="background: var(--bg); padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 12px;">
                                <i class="fa-solid fa-info-circle"></i> AFFECTED RECORD
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                                ${recordInfo.name ? `<div><span style="font-size: 11px; color: var(--text-secondary);">Name</span><div style="font-weight: 500;">${recordInfo.name}</div></div>` : ''}
                                ${recordInfo.email ? `<div><span style="font-size: 11px; color: var(--text-secondary);">Email</span><div style="font-weight: 500;">${recordInfo.email}</div></div>` : ''}
                                ${recordInfo.phone ? `<div><span style="font-size: 11px; color: var(--text-secondary);">Phone</span><div style="font-weight: 500;">${recordInfo.phone}</div></div>` : ''}
                                ${recordInfo.status ? `<div><span style="font-size: 11px; color: var(--text-secondary);">Status</span><div style="font-weight: 500;"><span class="badge" style="background: ${recordInfo.status === 'active' ? '#10b98120' : '#6b728020'}; color: ${recordInfo.status === 'active' ? '#10b981' : '#6b7280'};">${recordInfo.status}</span></div></div>` : ''}
                            </div>
                        </div>
                        ` : ''}

                        <!-- Main Details Grid -->
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                            <div style="background: var(--bg); padding: 16px; border-radius: 12px;">
                                <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px;">TIMESTAMP</div>
                                <div style="font-weight: 500; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-clock" style="color: var(--text-secondary);"></i>
                                    ${new Date(log.created_at).toLocaleString()}
                                </div>
                            </div>
                            <div style="background: var(--bg); padding: 16px; border-radius: 12px;">
                                <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px;">IP ADDRESS</div>
                                <div style="font-weight: 500; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-globe" style="color: var(--text-secondary);"></i>
                                    ${log.ip_address || 'System/N/A'}
                                </div>
                            </div>
                            <div style="background: var(--bg); padding: 16px; border-radius: 12px;">
                                <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px;">TABLE NAME</div>
                                <div style="font-weight: 500; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-database" style="color: var(--text-secondary);"></i>
                                    ${log.table_name || 'N/A'}
                                </div>
                            </div>
                            <div style="background: var(--bg); padding: 16px; border-radius: 12px;">
                                <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px;">RECORD ID</div>
                                <div style="font-weight: 500; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-hashtag" style="color: var(--text-secondary);"></i>
                                    ${log.record_id || 'N/A'}
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div style="margin-bottom: 20px;">
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px;">
                                <i class="fa-solid fa-align-left"></i> DESCRIPTION
                            </div>
                            <div style="background: var(--bg); padding: 16px; border-radius: 12px; font-size: 14px; line-height: 1.6;">
                                ${log.description || 'No description available'}
                            </div>
                        </div>

                        <!-- Changes -->
                        <div style="margin-bottom: 24px;">
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px;">
                                <i class="fa-solid fa-code-compare"></i> DATA CHANGES
                            </div>
                            ${changesHtml}
                        </div>

                        <!-- Action Links -->
                        ${navLink ? `
                        <div style="display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--card-border);">
                            <button class="btn bt" onclick="document.getElementById('audit-log-modal').remove(); ${navLink}" style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-eye"></i> View Related Record
                            </button>
                            <button class="btn bs" onclick="navigator.clipboard.writeText('Log ID: ${log.id}\nAction: ${log.action}\nTable: ${log.table_name}\nRecord ID: ${log.record_id}\nTime: ${log.created_at}'); alert('Log reference copied!')" style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-copy"></i> Copy Reference
                            </button>
                        </div>
                        ` : `
                        <div style="display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--card-border);">
                            <button class="btn bs" onclick="navigator.clipboard.writeText('Log ID: ${log.id}\nAction: ${log.action}\nTable: ${log.table_name}\nRecord ID: ${log.record_id}\nTime: ${log.created_at}'); alert('Log reference copied!')" style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-copy"></i> Copy Reference
                            </button>
                        </div>
                        `}
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
    };

    /**
     * Export audit logs
     */
    window.exportAuditLogs = function() {
        if (filteredLogs.length === 0) {
            alert('No logs to export');
            return;
        }

        const csv = [
            ['Timestamp', 'User', 'Action', 'Table', 'Record ID', 'Description', 'IP Address'].join(','),
            ...filteredLogs.map(log => [
                log.created_at,
                log.user_email || '',
                log.action || '',
                log.table_name || '',
                log.record_id || '',
                `"${(log.description || '').replace(/"/g, '""')}"`,
                log.ip_address || ''
            ].join(','))
        ].join('\n');

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `audit-logs-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    };

    // Expose render function globally
    window.renderAuditLogs = renderAuditLogs;

})();
