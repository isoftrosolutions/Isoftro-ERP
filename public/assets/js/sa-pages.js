/**
 * ISOFTRO - Super Admin Page Handlers
 * Specific logic for each dashboard view
 */

window.SuperAdmin = window.SuperAdmin || {};

// PAGE RENDERERS
SuperAdmin.renderDashboard = function() {
    SuperAdmin.fetchAndRender('pages/super_admin/overview');
};

SuperAdmin.renderTenants = function() {
    SuperAdmin.fetchAndRender('pages/super_admin/tenants');
};

SuperAdmin.renderUsers = function() {
    SuperAdmin.fetchAndRender('pages/super_admin/users');
};

SuperAdmin.renderSettings = function() {
    SuperAdmin.fetchAndRender('pages/super_admin/settings');
};

// ACTIONS
function viewTenant(id) {
    console.log("Viewing tenant:", id);
    // Modal logic here
}

function editTenant(id) {
    console.log("Editing tenant:", id);
    // Modal logic here
}

function impersonateTenant(id) {
    SuperAdmin.confirmAction("Impersonate Institute?", "You will be logged in as an administrator for this institute.", "Yes, Impersonate")
    .then((result) => {
        if (result.isConfirmed) {
            window.location.href = window.APP_URL + "/dash/super-admin/impersonate/" + id;
        }
    });
}

function suspendTenant(id) {
    SuperAdmin.confirmAction("Suspend Institute?", "The institute and all its users will lose access immediately.", "Yes, Suspend")
    .then((result) => {
        if (result.isConfirmed) {
            SuperAdmin.showNotification("Institute suspended successfully", "success");
        }
    });
}

function testEmail() {
    SuperAdmin.showNotification("Testing email connection...", "info");
}

function testSMS() {
    SuperAdmin.showNotification("Checking SMS balance...", "info");
}
