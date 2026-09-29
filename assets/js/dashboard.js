/* ===================================
   Dashboard JavaScript
   Interactions & Animations
   =================================== */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===================================
    // MOBILE SIDEBAR TOGGLE
    // ===================================
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            
            // Close sidebar when clicking outside
            if (sidebar.classList.contains('active')) {
                setTimeout(() => {
                    document.addEventListener('click', closeSidebarOnClickOutside);
                }, 100);
            }
        });
    }
    
    function closeSidebarOnClickOutside(e) {
        if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
            sidebar.classList.remove('active');
            document.removeEventListener('click', closeSidebarOnClickOutside);
        }
    }
    
    // ===================================
    // SEARCH BAR
    // ===================================
    const searchInput = document.querySelector('.search-bar input');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            const query = e.target.value.trim();
            if (query.length > 2) {
                performSearch(query);
            }
        }, 300));
    }
    
    function performSearch(query) {
        console.log('Searching for:', query);
        // Implement search functionality
        // You can add AJAX call here to search courses, users, etc.
    }
    
    // ===================================
    // USER MENU DROPDOWN
    // ===================================
    const userMenu = document.querySelector('.user-menu');
    
    if (userMenu) {
        userMenu.addEventListener('click', function() {
            // Toggle dropdown menu
            // You can add dropdown implementation here
            console.log('User menu clicked');
        });
    }
    
    // ===================================
    // NOTIFICATION & MESSAGE BUTTONS
    // ===================================
    const iconBtns = document.querySelectorAll('.icon-btn');
    
    iconBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            
            if (icon.classList.contains('fa-bell')) {
                openNotifications();
            } else if (icon.classList.contains('fa-envelope')) {
                openMessages();
            }
        });
    });
    
    function openNotifications() {
        console.log('Opening notifications');
        // Implement notifications panel
    }
    
    function openMessages() {
        console.log('Opening messages');
        // Implement messages panel
    }
    
    // ===================================
    // ANIMATE STATS ON SCROLL
    // ===================================
    const statCards = document.querySelectorAll('.stat-card');
    
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                
                // Animate numbers
                const statContent = entry.target.querySelector('.stat-content h3');
                if (statContent) {
                    animateNumber(statContent);
                }
            }
        });
    }, observerOptions);
    
    statCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        observer.observe(card);
    });
    
    // ===================================
    // ANIMATE NUMBERS
    // ===================================
    function animateNumber(element) {
        const target = element.textContent.trim();
        const isPercentage = target.includes('%');
        const isCurrency = target.includes('$') || target.includes('£') || target.includes('€');
        
        // Extract numeric value
        let numericValue = parseFloat(target.replace(/[^0-9.]/g, ''));
        
        if (isNaN(numericValue)) return;
        
        let current = 0;
        const increment = numericValue / 30;
        const duration = 1000;
        const stepTime = duration / 30;
        
        const timer = setInterval(() => {
            current += increment;
            
            if (current >= numericValue) {
                current = numericValue;
                clearInterval(timer);
            }
            
            let displayValue = Math.floor(current);
            
            if (isCurrency) {
                displayValue = '$' + displayValue.toLocaleString();
            } else if (isPercentage) {
                displayValue = displayValue + '%';
            } else {
                displayValue = displayValue.toLocaleString();
            }
            
            element.textContent = displayValue;
        }, stepTime);
    }
    
    // ===================================
    // SMOOTH SCROLL
    // ===================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // ===================================
    // TABLE ROW ACTIONS
    // ===================================
    const tableActionBtns = document.querySelectorAll('.data-table .btn-icon');
    
    tableActionBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const action = this.title;
            const row = this.closest('tr');
            
            console.log(`Action: ${action} on row`, row);
            
            // Implement edit, view, delete actions
            if (action === 'View') {
                viewRecord(row);
            } else if (action === 'Edit') {
                editRecord(row);
            } else if (action === 'Delete') {
                deleteRecord(row);
            }
        });
    });
    
    function viewRecord(row) {
        console.log('Viewing record', row);
        // Implement view functionality
    }
    
    function editRecord(row) {
        console.log('Editing record', row);
        // Implement edit functionality
    }
    
    function deleteRecord(row) {
        if (confirm('Are you sure you want to delete this record?')) {
            row.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                row.remove();
                showToast('Record deleted successfully', 'success');
            }, 300);
        }
    }
    
    // ===================================
    // PROGRESS BAR ANIMATION
    // ===================================
    const progressBars = document.querySelectorAll('.progress-fill');
    
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
    
    // ===================================
    // TIME FILTER
    // ===================================
    const timeFilter = document.querySelector('.time-filter');
    
    if (timeFilter) {
        timeFilter.addEventListener('change', function() {
            const value = this.value;
            console.log('Time filter changed to:', value);
            // Update charts or data based on filter
        });
    }
    
    // ===================================
    // TOAST NOTIFICATIONS
    // ===================================
    window.showToast = function(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
        
        toast.innerHTML = `
            <div class="toast-icon">${icon}</div>
            <div class="toast-content">
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close">×</button>
        `;
        
        toast.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInRight 0.3s ease-out;
            max-width: 400px;
        `;
        
        document.body.appendChild(toast);
        
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.style.cssText = `
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            margin-left: auto;
        `;
        
        closeBtn.addEventListener('click', () => {
            removeToast(toast);
        });
        
        setTimeout(() => {
            removeToast(toast);
        }, 5000);
    };
    
    function removeToast(toast) {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }
    
    // ===================================
    // EMPTY STATE ACTIONS
    // ===================================
    const emptyStateButtons = document.querySelectorAll('.empty-state .btn');
    
    emptyStateButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            console.log('Empty state action clicked');
        });
    });
    
    // ===================================
    // QUICK ACTION CARDS
    // ===================================
    const actionCards = document.querySelectorAll('.action-card');
    
    actionCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 12px 32px rgba(255, 107, 61, 0.2)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '';
        });
    });
    
    // ===================================
    // COURSE CARDS HOVER
    // ===================================
    const courseItems = document.querySelectorAll('.course-item');
    
    courseItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.course-icon');
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg)';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.course-icon');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
        });
    });
    
    // ===================================
    // KEYBOARD SHORTCUTS
    // ===================================
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape to close sidebar on mobile
        if (e.key === 'Escape') {
            sidebar.classList.remove('active');
        }
    });
    
    // ===================================
    // AUTO-REFRESH DATA (Optional)
    // ===================================
    // Uncomment to enable auto-refresh every 30 seconds
    /*
    setInterval(() => {
        refreshDashboardData();
    }, 30000);
    
    function refreshDashboardData() {
        console.log('Refreshing dashboard data...');
        // Implement AJAX call to refresh data
    }
    */
    
    // ===================================
    // PERFORMANCE MONITORING
    // ===================================
    window.addEventListener('load', function() {
        const loadTime = performance.now();
        console.log(`Dashboard loaded in ${(loadTime / 1000).toFixed(2)} seconds`);
    });
    
    // ===================================
    // PAGE VISIBILITY API
    // ===================================
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            console.log('Dashboard is visible, refreshing data...');
            // Optionally refresh data when user returns to tab
        }
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

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Format currency
function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Format date
function formatDate(date, locale = 'en-US') {
    return new Intl.DateTimeFormat(locale, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(new Date(date));
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
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
    
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(-20px);
        }
    }
    
    .toast-icon {
        font-size: 1.2rem;
        font-weight: bold;
    }
    
    .toast-message {
        font-weight: 500;
    }
`;
document.head.appendChild(style);

console.log('✨ Dashboard initialized successfully!');