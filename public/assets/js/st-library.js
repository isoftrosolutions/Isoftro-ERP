/**
 * iSoftro ERP — Student Portal · st-library.js
 * Student Library Module
 */

window.renderSTLibrary = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading library...</span></div></div>`;

    try {
        const res = await fetch(`${window.APP_URL}/api/student/library`);
        const result = await res.json();
        
        mc.innerHTML = `
            <div class="pg fu">
                <div class="pg-hdr">
                    <div class="pg-title"><i class="fa-solid fa-book-reader" style="margin-right:10px; color:var(--primary);"></i> My Library</div>
                    <div class="pg-sub">View your borrowed books</div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        ${result.success && result.data && result.data.length > 0 ? `
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${result.data.map(b => `
                                        <tr>
                                            <td><strong>${b.title || '-'}</strong></td>
                                            <td>${b.author || '-'}</td>
                                            <td>${b.issue_date || '-'}</td>
                                            <td>${b.due_date || '-'}</td>
                                            <td><span class="badge ${b.status === 'returned' ? 'badge-green' : 'badge-amber'}">${b.status === 'returned' ? 'Returned' : 'Issued'}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : `
                            <div style="text-align:center; padding:60px;">
                                <i class="fa-solid fa-book-reader" style="font-size:4rem; color:var(--primary); opacity:0.3; margin-bottom:20px;"></i>
                                <h3>No Books Borrowed</h3>
                                <p style="color:var(--text-light);">You have no books borrowed from the library.</p>
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    } catch (e) {
        console.error('Library load error:', e);
    }
};

window.renderSTLibrary = window.renderSTLibrary;
