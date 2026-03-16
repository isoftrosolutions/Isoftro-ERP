/**
 * Hamro ERP — ia-rooms.js
 * Room Management: List, Add, Edit, Delete
 */

window.renderRoomsPage = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">Room Management</span>
        </div>
        
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: linear-gradient(135deg, #0d9488, #14b8a6); color: #fff;">
                    <i class="fa-solid fa-door-open"></i>
                </div>
                <div>
                    <div class="pg-title">Room Management</div>
                    <div class="pg-sub">Manage classrooms, labs, and other physical spaces</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="renderAddRoomForm()" style="border-radius: 12px; font-weight: 700;">
                    <i class="fa-solid fa-plus"></i> <span>Add New Room</span>
                </button>
            </div>
        </div>

        <div class="card fu" style="padding: 0; overflow: hidden; border-radius: 16px;">
            <div class="table-responsive">
                <table class="premium-student-table" id="roomsTable">
                    <thead>
                        <tr>
                            <th>Room Name</th>
                            <th>Code</th>
                            <th>Capacity</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="roomsList">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 50px;">
                                <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 24px; color: var(--brand);"></i>
                                <div style="margin-top: 10px;">Loading rooms...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;

    await loadRooms();
};

async function loadRooms() {
    const tbody = document.getElementById('roomsList');
    if (!tbody) return;

    try {
        const res = await fetch(`${APP_URL}/api/admin/rooms`, { credentials: 'include' });
        const result = await res.json();
        
        if (!result.success) throw new Error(result.message);
        
        const rooms = result.data || [];
        if (rooms.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 80px 20px;">
                    <div style="font-size: 40px; color: #e2e8f0; margin-bottom: 20px;">
                        <i class="fa-solid fa-door-closed"></i>
                    </div>
                    <h4 style="color: #64748b;">No rooms found</h4>
                    <p style="color: #94a3b8; font-size: 14px;">Start by adding your first classroom or lab.</p>
                    <button class="btn bt" onclick="renderAddRoomForm()" style="margin-top: 20px; font-size: 13px;">Add New Room</button>
                </td>
            </tr>`;
            return;
        }

        tbody.innerHTML = rooms.map(room => `
            <tr>
                <td>
                    <div style="font-weight: 700; color: #1e293b;">${room.name}</div>
                </td>
                <td><span class="badge" style="background: #f1f5f9; color: #475569;">${room.code || 'N/A'}</span></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-users" style="color: #94a3b8; font-size: 12px;"></i>
                        <span>${room.capacity || 'N/A'}</span>
                    </div>
                </td>
                <td>
                    <div style="font-size: 13px; color: #64748b; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${room.description || '-'}
                    </div>
                </td>
                <td>
                    <span class="badge" style="background: ${room.is_active ? '#ecfdf5' : '#fff1f2'}; color: ${room.is_active ? '#10b981' : '#e11d48'}; font-weight: 700;">
                        ${room.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td style="text-align: right;">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn-icon-p" onclick="renderEditRoomForm(${JSON.stringify(room).replace(/"/g, '&quot;')})" title="Edit Room">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn-icon-p" style="color: #e11d48; border-color: #fee2e2;" onclick="deleteRoom(${room.id}, '${room.name}')" title="Delete Room">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--red); padding: 40px;">${e.message}</td></tr>`;
    }
}

window.renderAddRoomForm = function() {
    _renderRoomForm('Add New Room', 'Record details of the new physical space', {
        name: '',
        code: '',
        capacity: '',
        description: '',
        is_active: 1
    }, 'POST');
};

window.renderEditRoomForm = function(room) {
    _renderRoomForm('Edit Room', 'Update details for ' + room.name, room, 'PUT');
};

function _renderRoomForm(title, sub, data, method) {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `
    <div class="pg fu">
        <div class="bc">
            <a href="#" onclick="goNav('overview')"><i class="fa-solid fa-home"></i></a> 
            <span class="bc-sep">/</span> 
            <a href="#" onclick="renderRoomsPage()">Room Management</a> 
            <span class="bc-sep">/</span> 
            <span class="bc-cur">${method === 'POST' ? 'Add' : 'Edit'} Room</span>
        </div>

        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background: linear-gradient(135deg, #0d9488, #14b8a6); color: #fff;">
                    <i class="fa-solid ${method === 'POST' ? 'fa-plus' : 'fa-pen-to-square'}"></i>
                </div>
                <div>
                    <div class="pg-title">${title}</div>
                    <div class="pg-sub">${sub}</div>
                </div>
            </div>
        </div>

        <div class="card fu" style="max-width: 600px; margin: 0 auto; padding: 40px; border-radius: 20px;">
            <form id="roomForm">
                ${data.id ? `<input type="hidden" name="id" value="${data.id}">` : ''}
                
                <div class="form-group">
                    <label class="form-label">Room Name *</label>
                    <input type="text" name="name" class="form-control" required value="${data.name}" placeholder="e.g. Room 101, Science Lab">
                </div>

                <div class="form-group">
                    <label class="form-label">Room Code / No.</label>
                    <input type="text" name="code" class="form-control" value="${data.code || ''}" placeholder="e.g. R-101">
                </div>

                <div class="form-group">
                    <label class="form-label">Capacity (Max Students)</label>
                    <input type="number" name="capacity" class="form-control" value="${data.capacity || ''}" placeholder="e.g. 40">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" style="height: 100px;" placeholder="Details about this room...">${data.description || ''}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" ${data.is_active ? 'selected' : ''}>Active</option>
                        <option value="0" ${!data.is_active ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn bs" onclick="renderRoomsPage()">Cancel</button>
                    <button type="submit" class="btn bt" id="saveRoomBtn">
                        <i class="fa-solid fa-check"></i> ${method === 'POST' ? 'Create Room' : 'Update Room'}
                    </button>
                </div>
            </form>
        </div>
    </div>`;

    document.getElementById('roomForm').onsubmit = async (e) => {
        e.preventDefault();
        const btn = document.getElementById('saveRoomBtn');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';

        const formData = new FormData(e.target);
        const jsonData = Object.fromEntries(formData.entries());

        try {
            const res = await fetch(`${APP_URL}/api/admin/rooms`, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(jsonData),
                credentials: 'include'
            });
            const result = await res.json();
            
            if (result.success) {
                Swal.fire('Success', result.message, 'success').then(() => renderRoomsPage());
            } else {
                throw new Error(result.message);
            }
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    };
}

window.deleteRoom = async function(id, name) {
    const confirm = await Swal.fire({
        title: 'Delete Room?',
        text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Yes, Delete'
    });

    if (confirm.isConfirmed) {
        try {
            const res = await fetch(`${APP_URL}/api/admin/rooms?id=${id}`, {
                method: 'DELETE',
                credentials: 'include'
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire('Deleted!', result.message, 'success').then(() => renderRoomsPage());
            } else {
                throw new Error(result.message);
            }
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
        }
    }
};
