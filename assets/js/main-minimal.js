/* ===================================
   Minimal Landing Page JavaScript
   Smooth Interactions & Animations
   =================================== */

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    
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
    // NAVBAR SCROLL EFFECT
    // ===================================
    const navbar = document.querySelector('.navbar-minimal');
    let lastScroll = 0;
    
    window.addEventListener('scroll', throttle(function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 50) {
            navbar.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.1)';
        } else {
            navbar.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.06)';
        }
        
        lastScroll = currentScroll;
    }, 100));
    
    // ===================================
    // INTERSECTION OBSERVER FOR ANIMATIONS
    // ===================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe feature items
    document.querySelectorAll('.feature-item, .stat-item').forEach(el => {
        observer.observe(el);
    });
    
    // ===================================
    // BUTTON HOVER EFFECT
    // ===================================
    const ctaButton = document.querySelector('.btn-cta');
    if (ctaButton) {
        ctaButton.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        ctaButton.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    }
    
    // ===================================
    // PARALLAX EFFECT (Subtle)
    // ===================================
    const heroContent = document.querySelector('.hero-landing-content');
    if (heroContent) {
        window.addEventListener('scroll', throttle(function() {
            const scrolled = window.pageYOffset;
            const parallax = scrolled * 0.3;
            heroContent.style.transform = `translateY(${parallax}px)`;
        }, 100));
    }
    
    // ===================================
    // FLOATING ELEMENTS ANIMATION
    // ===================================
    const floatElements = document.querySelectorAll('.float-element');
    floatElements.forEach((element, index) => {
        element.style.animationDelay = `${index * 2}s`;
    });
    
    // ===================================
    // HEXAGON INTERACTIVE ROTATION
    // ===================================
    const hexagon = document.querySelector('.hexagon');
    if (hexagon) {
        let rotation = 0;
        
        hexagon.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1) rotate(5deg)';
        });
        
        hexagon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
        });
        
        // Subtle continuous rotation
        setInterval(() => {
            rotation += 0.5;
            hexagon.style.transform = `rotate(${rotation}deg)`;
        }, 100);
    }
    
    // ===================================
    // STATS COUNTER ANIMATION
    // ===================================
    function animateCounter(element, target, duration = 2000) {
        let start = 0;
        const increment = target / (duration / 16);
        const suffix = element.textContent.replace(/[0-9]/g, '');
        
        const timer = setInterval(() => {
            start += increment;
            if (start >= target) {
                element.textContent = target + suffix;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(start) + suffix;
            }
        }, 16);
    }
    
    // Animate stats when visible
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const numberElement = entry.target.querySelector('.stat-number');
                if (numberElement && !numberElement.classList.contains('animated')) {
                    const value = parseInt(numberElement.textContent);
                    numberElement.classList.add('animated');
                    animateCounter(numberElement, value);
                }
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('.stat-item').forEach(stat => {
        statsObserver.observe(stat);
    });
    
    // ===================================
    // KEYBOARD NAVIGATION
    // ===================================
    document.addEventListener('keydown', function(e) {
        // Press 'G' to scroll to Get Started button
        if (e.key === 'g' || e.key === 'G') {
            const ctaBtn = document.querySelector('.btn-cta');
            if (ctaBtn) {
                ctaBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                ctaBtn.focus();
            }
        }
    });
    
    // ===================================
    // MOBILE MENU (if needed)
    // ===================================
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            const navActions = document.querySelector('.nav-actions');
            navActions.classList.toggle('active');
        });
    }
    
    // ===================================
    // LOADING ANIMATION
    // ===================================
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
        
        // Log load time
        const loadTime = performance.now();
        console.log(`Page loaded in ${(loadTime / 1000).toFixed(2)} seconds`);
    });
    
    // ===================================
    // FEATURE CARDS STAGGER
    // ===================================
    const featureItems = document.querySelectorAll('.feature-item');
    featureItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
    });
    
    // ===================================
    // SCROLL TO TOP ON LOGO CLICK
    // ===================================
    const logo = document.querySelector('.logo-minimal');
    if (logo) {
        logo.addEventListener('click', function(e) {
            if (window.pageYOffset > 0) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // ===================================
    // TOAST NOTIFICATION SYSTEM
    // ===================================
    window.showToast = function(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span>
                <span class="toast-message">${message}</span>
            </div>
        `;
        
        toast.style.cssText = `
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInUp 0.3s ease-out;
            font-family: var(--font-main);
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutDown 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
    
    // ===================================
    // PAGE VISIBILITY API
    // ===================================
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden
            document.title = '👋 Come back! - Student Enrolment';
        } else {
            // Page is visible
            document.title = 'Student Enrolment Journey';
        }
    });
    
    // ===================================
    // PERFORMANCE MONITORING
    // ===================================
    if ('PerformanceObserver' in window) {
        const perfObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.duration > 100) {
                    console.warn(`Slow operation: ${entry.name} took ${entry.duration}ms`);
                }
            }
        });
        
        perfObserver.observe({ entryTypes: ['measure'] });
    }
});

// ===================================
// UTILITY FUNCTIONS
// ===================================

// Throttle function for performance
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

// Check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Add animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutDown {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(100%);
            opacity: 0;
        }
    }
    
    .animate-in {
        animation: fadeInUp 0.6s ease-out both;
    }
    
    .toast-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .toast-icon {
        font-size: 1.2rem;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .toast {
            bottom: 1rem !important;
            right: 1rem !important;
            left: 1rem !important;
            max-width: calc(100% - 2rem);
        }
    }
`;
document.head.appendChild(style);

// Log initialization
console.log('✨ Minimal Landing Page initialized successfully!');