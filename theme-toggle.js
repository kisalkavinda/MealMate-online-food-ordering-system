// Theme Toggle System for MealMate
// Simplified and reliable version

(function() {
    'use strict';

    console.log('MealMate theme-toggle.js loaded');

    // Get the current theme from localStorage or default to 'dark'
    const getCurrentTheme = () => {
        return localStorage.getItem('mealmate-theme') || 'dark';
    };

    // Set theme on the document
    const setTheme = (theme) => {
        console.log('Setting theme to:', theme);
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('mealmate-theme', theme);
        
        // Update theme-color meta tag
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) {
            meta.setAttribute('content', theme === 'light' ? '#fafafa' : '#0d0d0d');
        }

        // Update icons
        updateThemeIcon(theme);

        // Update button attributes
        const btn = document.querySelector('.theme-toggle-btn');
        if (btn) {
            btn.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
            btn.setAttribute('aria-label', theme === 'light' ? 'Switch to dark theme' : 'Switch to light theme');
        }

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    };

    const updateThemeIcon = (theme) => {
        const sunIcon = document.querySelector('.sun-icon');
        const moonIcon = document.querySelector('.moon-icon');

        if (sunIcon && moonIcon) {
            if (theme === 'light') {
                sunIcon.style.opacity = '1';
                sunIcon.style.transform = 'rotate(0deg) scale(1)';
                moonIcon.style.opacity = '0';
                moonIcon.style.transform = 'rotate(90deg) scale(0)';
            } else {
                sunIcon.style.opacity = '0';
                sunIcon.style.transform = 'rotate(-90deg) scale(0)';
                moonIcon.style.opacity = '1';
                moonIcon.style.transform = 'rotate(0deg) scale(1)';
            }
        }
    };

    // Toggle theme function
    const toggleTheme = () => {
        console.log('Toggle theme called');
        const currentTheme = getCurrentTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
        
        // Animate button
        const btn = document.querySelector('.theme-toggle-btn');
        if (btn) {
            btn.style.transform = 'scale(1.2) rotate(360deg)';
            setTimeout(() => { btn.style.transform = ''; }, 300);
        }
    };

    // Initialize theme
    const initTheme = () => {
        const theme = getCurrentTheme();
        setTheme(theme);
        console.log('Theme initialized:', theme);
    };

    // Attach click handlers to all theme toggle buttons
    const attachClickHandlers = () => {
        const buttons = document.querySelectorAll('.theme-toggle-btn');
        console.log('Found', buttons.length, 'theme toggle button(s)');
        
        buttons.forEach((btn, index) => {
            // Remove any existing listeners by cloning
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            // Add fresh click listener
            newBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Button clicked - Button', index + 1);
                toggleTheme();
            });
            
            // Add keyboard support
            newBtn.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    console.log('Button activated via keyboard');
                    toggleTheme();
                }
            });
            
            console.log('Click handler attached to button', index + 1);
        });
    };

    // Initialize when DOM is ready
    const init = () => {
        initTheme();
        attachClickHandlers();
        
        // Re-attach handlers after a short delay to catch dynamically added buttons
        setTimeout(() => {
            attachClickHandlers();
        }, 500);
    };

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Handle back/forward cache
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            initTheme();
            attachClickHandlers();
        }
    });

    // Expose public API
    window.MealMateTheme = {
        toggle: toggleTheme,
        setTheme: setTheme,
        getTheme: getCurrentTheme
    };

    console.log('Theme toggle system ready');

})();

// Listen for theme changes
document.addEventListener('themechange', (e) => {
    console.log('Theme changed to:', e.detail.theme);
});