/**
 * nexus-data-loader.js
 * Shared utility for dynamically fetching Courses & Batches
 * Works across both Admin and Front Desk portals.
 *
 * Usage:
 *   NexusDataLoader.loadCourses(selectElement)
 *   NexusDataLoader.loadBatches(courseId, selectElement)
 */

window.NexusDataLoader = {

    /**
     * Detect the correct API base path based on current user role.
     * Admin uses /api/admin/, Front Desk uses /api/frontdesk/
     */
    _getApiBase() {
        const role = (window._userRole || '').toLowerCase();
        if (role === 'frontdesk' || role === 'front-desk') {
            return `${window.APP_URL}/api/frontdesk`;
        }
        return `${window.APP_URL}/api/admin`;
    },

    // ISSUE-B4 FIX: Track in-flight batch request so rapid course switches cancel stale requests
    _batchAbortController: null,

    /**
     * Fetch all active courses for the current tenant.
     * @param {HTMLSelectElement|null} selectEl - Optional <select> to populate
     * @param {Function|null} callback - Optional callback(courses[])
     * @returns {Promise<Array>} Array of course objects
     */
    async loadCourses(selectEl, callback) {
        try {
            const res    = await fetch(`${this._getApiBase()}/courses`);
            const result = await res.json();

            if (!result.success) {
                console.error('[NexusDataLoader] Course API error:', result.message);
                return [];
            }

            const courses = result.data || [];

            if (selectEl) {
                // ISSUE-C4 FIX: Show a clear empty state if no active courses exist
                if (courses.length === 0) {
                    selectEl.innerHTML = '<option value="" disabled selected>\u26a0\ufe0f No active courses available \u2014 please contact admin</option>';
                    selectEl.disabled  = true;
                } else {
                    selectEl.innerHTML = '<option value="">Select Course</option>';
                    courses.forEach(c => {
                        const opt       = document.createElement('option');
                        opt.value       = c.id;
                        opt.textContent = `${c.name}${c.code ? ' (' + c.code + ')' : ''}`;
                        selectEl.appendChild(opt);
                    });
                    selectEl.disabled = false;
                }
            }

            if (typeof callback === 'function') callback(courses);
            return courses;

        } catch (e) {
            console.error('[NexusDataLoader] Failed to fetch courses:', e);
            if (selectEl) {
                selectEl.innerHTML = '<option value="" disabled selected>\u26a0\ufe0f Failed to load courses</option>';
                selectEl.disabled  = true;
            }
            return [];
        }
    },

    /**
     * Fetch batches for a specific course.
     * Uses status=open (virtual) which returns active + upcoming batches.
     *
     * ISSUE-B4 FIX: Uses AbortController to cancel stale in-flight requests
     * when the user changes course selection rapidly.
     *
     * @param {number|string} courseId - The course ID to filter by
     * @param {HTMLSelectElement|null} selectEl - Optional <select> to populate
     * @param {Function|null} callback - Optional callback(batches[])
     * @returns {Promise<Array>} Array of batch objects
     */
    async loadBatches(courseId, selectEl, callback) {
        if (!courseId) {
            if (selectEl) {
                selectEl.innerHTML = '<option value="">Select Course First</option>';
                selectEl.disabled  = true;
            }
            return [];
        }

        // ISSUE-B4 FIX: Abort any previous in-flight batch request
        if (this._batchAbortController) {
            this._batchAbortController.abort();
        }
        this._batchAbortController = new AbortController();
        const signal = this._batchAbortController.signal;

        try {
            if (selectEl) {
                selectEl.innerHTML = '<option value="">\u23f3 Loading batches...</option>';
                selectEl.disabled  = true;
            }

            // ISSUE-B1 FIX: status=open returns both 'active' and 'upcoming' batches (server handles this)
            const url    = `${this._getApiBase()}/batches?course_id=${courseId}&status=open`;
            const res    = await fetch(url, { signal });
            const result = await res.json();

            if (!result.success) {
                console.error('[NexusDataLoader] Batch API error:', result.message);
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">No batches found</option>';
                }
                return [];
            }

            const batches = result.data || [];

            if (selectEl) {
                if (batches.length === 0) {
                    selectEl.innerHTML = '<option value="">No batches available for this course</option>';
                } else {
                    selectEl.innerHTML = '<option value="">Select Batch</option>';
                    batches.forEach(b => {
                        const opt       = document.createElement('option');
                        opt.value       = b.id;
                        const upcomingTag = b.status === 'upcoming' ? ' [Upcoming]' : '';
                        opt.textContent = `${b.name}${b.shift ? ' (' + b.shift + ')' : ''}${upcomingTag}`;
                        selectEl.appendChild(opt);
                    });
                    selectEl.disabled = false;
                }
            }

            if (typeof callback === 'function') callback(batches);
            return batches;

        } catch (e) {
            if (e.name === 'AbortError') {
                // Request was superseded by a newer course selection — silently ignore
                return [];
            }
            console.error('[NexusDataLoader] Failed to fetch batches:', e);
            if (selectEl) {
                selectEl.innerHTML = '<option value="">Error loading batches</option>';
            }
            return [];
        }
    },

    /**
     * Load courses into a NexusSearchSelect component (for Nexus Forms).
     * @param {NexusSearchSelect} component - The search select instance
     */
    async loadCoursesForNexus(component) {
        const courses = await this.loadCourses(null);
        component.setData(courses.map(c => ({
            id:    c.id,
            label: `${c.name}${c.code ? ' (' + c.code + ')' : ''}`
        })));
    },

    /**
     * Load batches into a NexusSearchSelect component (for Nexus Forms).
     * @param {number|string} courseId
     * @param {NexusSearchSelect} component - The search select instance
     */
    async loadBatchesForNexus(courseId, component) {
        if (!courseId) return;
        const batches = await this.loadBatches(courseId, null);
        component.setData(batches.map(b => ({
            id:    b.id,
            label: `${b.name}${b.shift ? ' (' + b.shift + ')' : ''}${b.status === 'upcoming' ? ' [Upcoming]' : ''}`
        })));
    }
};

console.log('\ud83d\udce6 NexusDataLoader ready.');
