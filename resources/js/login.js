/**
 * Hamro ERP — Login Page Script
 * Handles form validation and authentication
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember');
    
    // Check for saved credentials
    const savedCredentials = getSavedCredentials();
    if (savedCredentials) {
        usernameInput.value = savedCredentials.username;
        passwordInput.value = savedCredentials.password;
        rememberCheckbox.checked = true;
    }
    
    // Form submission handler
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        const submitButton = loginForm.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        submitButton.disabled = true;
        
        try {
            // Simulate authentication (replace with actual API call)
            const credentials = {
                username: usernameInput.value.trim(),
                password: passwordInput.value
            };
            
            const response = await authenticateUser(credentials);
            
            if (response.success) {
                // Save credentials if remember me is checked
                if (rememberCheckbox.checked) {
                    saveCredentials(credentials);
                } else {
                    clearCredentials();
                }
                
                // Store user data in session storage
                sessionStorage.setItem('userData', JSON.stringify(response.user));
                
                // Redirect to Gateway (index.php)
                window.location.href = 'index.php';
            } else {
                // Show error message
                showError(response.message || 'Invalid username or password');
            }
        } catch (error) {
            console.error('Login error:', error);
            showError('An error occurred. Please try again.');
        } finally {
            // Restore button state
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }
    });
    
    // Form validation
    function validateForm() {
        let isValid = true;
        const username = usernameInput.value.trim();
        const password = passwordInput.value;
        
        // Clear previous errors
        clearErrors();
        
        // Validate username
        if (!username) {
            showError('Username is required', usernameInput);
            isValid = false;
        } else if (username.length < 3) {
            showError('Username must be at least 3 characters', usernameInput);
            isValid = false;
        }
        
        // Validate password
        if (!password) {
            showError('Password is required', passwordInput);
            isValid = false;
        } else if (password.length < 6) {
            showError('Password must be at least 6 characters', passwordInput);
            isValid = false;
        }
        
        return isValid;
    }
    
    // Show error message
    function showError(message, inputElement = null) {
        if (inputElement) {
            inputElement.classList.add('error');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            inputElement.parentNode.appendChild(errorDiv);
        } else {
            // Show global error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message global-error';
            errorDiv.textContent = message;
            loginForm.insertBefore(errorDiv, loginForm.firstChild);
        }
    }
    
    // Clear errors
    function clearErrors() {
        const errorMessages = loginForm.querySelectorAll('.error-message');
        errorMessages.forEach(msg => msg.remove());
        
        const inputs = loginForm.querySelectorAll('.form-input');
        inputs.forEach(input => input.classList.remove('error'));
    }
    
    // Save credentials to localStorage
    function saveCredentials(credentials) {
        localStorage.setItem('erpCredentials', JSON.stringify({
            username: credentials.username,
            password: credentials.password
        }));
    }
    
    // Get saved credentials
    function getSavedCredentials() {
        const saved = localStorage.getItem('erpCredentials');
        return saved ? JSON.parse(saved) : null;
    }
    
    // Clear saved credentials
    function clearCredentials() {
        localStorage.removeItem('erpCredentials');
    }
    
    // Authenticate user via API
    async function authenticateUser(credentials) {
        const formData = new FormData();
        formData.append('username', credentials.username);
        formData.append('password', credentials.password);
        if (rememberCheckbox.checked) {
            formData.append('remember', 'on');
        }

        try {
            const response = await fetch('api/login', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return {
                success: false,
                message: 'Connection error. Please try again later.'
            };
        }
    }
    
    // Get dashboard URL based on role
    function getDashboardUrl(role) {
        const dashboardMap = {
            'superadmin': 'super_admin.php',
            'instituteadmin': 'instituteadmin.php',
            'teacher': 'teacher.php',
            'student': 'student.php',
            'guardian': 'guardian.php',
            'frontdesk': 'frontdesk.php'
        };
        
        return dashboardMap[role] || 'index.php';
    }
    
    // Get role color for avatar
    function getRoleColor(role) {
        const roleColors = {
            'superadmin': '8141A5',
            'instituteadmin': '00B894',
            'teacher': '3B82F6',
            'student': 'F59E0B',
            'guardian': '009E7E',
            'frontdesk': 'E11D48'
        };
        
        return roleColors[role] || '94A3B8';
    }
    
    // Password Visibility Toggle
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            const icon = togglePassword.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                togglePassword.style.color = 'var(--green)';
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                togglePassword.style.color = 'var(--text-light)';
            }
        });
    }

    // Input field focus/blur handlers
    const inputs = loginForm.querySelectorAll('.form-input, .lp-input');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', () => {
            input.parentElement.classList.remove('focused');
        });
    });
    
    // Forgot password link
    const forgotPasswordLink = document.querySelector('.forgot-password');
    forgotPasswordLink.addEventListener('click', (e) => {
        e.preventDefault();
        alert('Password reset functionality would be implemented here.');
    });
    
    // Sign up link
    const signUpLink = document.querySelector('.footer-link');
    signUpLink.addEventListener('click', (e) => {
        e.preventDefault();
        alert('User registration functionality would be implemented here.');
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // Ctrl/Cmd + Enter to submit form
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            loginForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // Auto-focus username field
    usernameInput.focus();
});
