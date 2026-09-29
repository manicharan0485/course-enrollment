/* ===================================
   Authentication Pages JavaScript
   Form Validation & Interactions
   =================================== */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===================================
    // FORM VALIDATION
    // ===================================
    const forms = document.querySelectorAll('.auth-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-submit');
            
            // Add loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Remove loading state if validation fails
            const inputs = this.querySelectorAll('input[required]');
            let hasError = false;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    hasError = true;
                }
            });
            
            if (hasError) {
                e.preventDefault();
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    });
    
    // ===================================
    // INPUT FOCUS EFFECTS
    // ===================================
    const inputs = document.querySelectorAll('.form-group input');
    
    inputs.forEach(input => {
        // Add filled class if input has value
        if (input.value) {
            input.parentElement.classList.add('filled');
        }
        
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            
            if (this.value) {
                this.parentElement.classList.add('filled');
            } else {
                this.parentElement.classList.remove('filled');
            }
        });
        
        // Real-time validation
        input.addEventListener('input', function() {
            validateInput(this);
        });
    });
    
    // ===================================
    // INPUT VALIDATION
    // ===================================
    function validateInput(input) {
        const type = input.type;
        const value = input.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Remove existing error
        removeError(input);
        
        if (type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (value && !emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Invalid email format';
            }
        }
        
        if (type === 'password') {
            if (input.name === 'password' && value && value.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters';
            }
            
            // Check password strength
            if (input.name === 'password' && value) {
                updatePasswordStrength(input, value);
            }
            
            // Check password match
            if (input.name === 'confirm_password') {
                const password = document.querySelector('input[name="password"]');
                if (password && value && value !== password.value) {
                    isValid = false;
                    errorMessage = 'Passwords do not match';
                }
            }
        }
        
        if (!isValid) {
            showError(input, errorMessage);
        }
        
        return isValid;
    }
    
    // ===================================
    // PASSWORD STRENGTH INDICATOR
    // ===================================
    function updatePasswordStrength(input, password) {
        // Find or create strength indicator
        let strengthDiv = input.parentElement.querySelector('.password-strength');
        
        if (!strengthDiv) {
            strengthDiv = document.createElement('div');
            strengthDiv.className = 'password-strength';
            strengthDiv.innerHTML = '<div class="password-strength-bar"></div>';
            input.parentElement.appendChild(strengthDiv);
        }
        
        const strengthBar = strengthDiv.querySelector('.password-strength-bar');
        const strength = calculatePasswordStrength(password);
        
        strengthBar.className = 'password-strength-bar';
        
        if (strength < 40) {
            strengthBar.classList.add('weak');
        } else if (strength < 70) {
            strengthBar.classList.add('medium');
        } else {
            strengthBar.classList.add('strong');
        }
    }
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 15;
        if (/[a-z]/.test(password)) strength += 15;
        if (/[A-Z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
        
        return strength;
    }
    
    // ===================================
    // ERROR HANDLING
    // ===================================
    function showError(input, message) {
        const formGroup = input.parentElement;
        
        let errorDiv = formGroup.querySelector('.form-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'form-error';
            formGroup.appendChild(errorDiv);
        }
        
        errorDiv.textContent = message;
        errorDiv.style.cssText = `
            color: #FCA5A5;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            animation: slideIn 0.3s ease-out;
        `;
        
        input.style.borderColor = 'rgba(239, 68, 68, 0.5)';
    }
    
    function removeError(input) {
        const formGroup = input.parentElement;
        const errorDiv = formGroup.querySelector('.form-error');
        
        if (errorDiv) {
            errorDiv.remove();
        }
        
        input.style.borderColor = '';
    }
    
    // ===================================
    // PASSWORD VISIBILITY TOGGLE
    // ===================================
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle';
        toggleBtn.innerHTML = '👁️';
        toggleBtn.setAttribute('aria-label', 'Toggle password visibility');
        
        toggleBtn.style.cssText = `
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            opacity: 0.5;
            transition: opacity 0.3s;
        `;
        
        toggleBtn.addEventListener('mouseenter', function() {
            this.style.opacity = '1';
        });
        
        toggleBtn.addEventListener('mouseleave', function() {
            this.style.opacity = '0.5';
        });
        
        toggleBtn.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '🙈';
            } else {
                input.type = 'password';
                this.innerHTML = '👁️';
            }
        });
        
        // Make parent relative for absolute positioning
        input.parentElement.style.position = 'relative';
        input.style.paddingRight = '3rem';
        
        input.parentElement.appendChild(toggleBtn);
    });
    
    // ===================================
    // MOBILE MENU TOGGLE
    // ===================================
    const menuToggle = document.querySelector('.menu-toggle');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            
            // Animate hamburger to X
            const spans = this.querySelectorAll('span');
            if (this.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translateY(9px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translateY(-9px)';
            } else {
                spans[0].style.transform = '';
                spans[1].style.opacity = '';
                spans[2].style.transform = '';
            }
        });
    }
    
    // ===================================
    // AUTO DISMISS ALERTS
    // ===================================
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Add slideOut animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
    `;
    document.head.appendChild(style);
    
    // ===================================
    // KEYBOARD SHORTCUTS
    // ===================================
    document.addEventListener('keydown', function(e) {
        // Press Escape to clear form
        if (e.key === 'Escape') {
            const form = document.querySelector('.auth-form');
            if (form && confirm('Clear form?')) {
                form.reset();
                inputs.forEach(input => {
                    input.parentElement.classList.remove('filled', 'focused');
                    removeError(input);
                });
            }
        }
    });
    
    // ===================================
    // PREVENT MULTIPLE SUBMISSIONS
    // ===================================
    let isSubmitting = false;
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
            
            // Reset after 3 seconds
            setTimeout(() => {
                isSubmitting = false;
            }, 3000);
        });
    });
    
    // ===================================
    // SMOOTH ANIMATIONS
    // ===================================
    const authContent = document.querySelector('.auth-content');
    if (authContent) {
        authContent.style.animation = 'fadeInUp 0.6s ease-out';
    }
    
    const authStyle = document.createElement('style');
    authStyle.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(authStyle);
    
    // ===================================
    // BROWSER AUTOFILL DETECTION
    // ===================================
    setTimeout(() => {
        inputs.forEach(input => {
            if (input.matches(':-webkit-autofill')) {
                input.parentElement.classList.add('filled');
            }
        });
    }, 100);
    
    // ===================================
    // FORM ANALYTICS (Optional)
    // ===================================
    inputs.forEach(input => {
        let focusTime;
        
        input.addEventListener('focus', function() {
            focusTime = Date.now();
        });
        
        input.addEventListener('blur', function() {
            const timeSpent = Date.now() - focusTime;
            console.log(`Time spent on ${this.name}: ${timeSpent}ms`);
        });
    });
    
    // ===================================
    // PERFORMANCE MONITORING
    // ===================================
    window.addEventListener('load', function() {
        const loadTime = performance.now();
        console.log(`Auth page loaded in ${(loadTime / 1000).toFixed(2)} seconds`);
    });
});

// ===================================
// UTILITY FUNCTIONS
// ===================================

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Toast notification (for success messages)
window.showToast = function(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    toast.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${type === 'success' ? '#10B981' : '#EF4444'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        z-index: 9999;
        animation: slideInRight 0.3s ease-out;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// Add toast animations
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(toastStyle);

console.log('✨ Auth pages initialized successfully!');