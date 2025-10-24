<style>
    /* === CSS Variables for Theme === */
    :root {
        --bg-primary: #0d0d0d;
        --bg-secondary: #1a1a1a;
        --bg-card: #222;
        --bg-header: rgba(0, 0, 0, 0.8);
        --text-primary: #fff;
        --text-secondary: #ddd;
        --text-muted: #ccc;
        --accent-primary: #FF4500;
        --accent-hover: #FF6B35;
        --border-color: #FF4500;
        --shadow-color: rgba(255, 69, 0, 0.3);
        --footer-bg: rgba(0, 0, 0, 0.9);
        --footer-border: #333;
    }

    [data-theme="light"] {
        --bg-primary: #fafafa;
        --bg-secondary: #f0f0f0;
        --bg-card: #fff;
        --bg-header: rgba(255, 255, 255, 0.98);
        --text-primary: #1a1a1a;
        --text-secondary: #333;
        --text-muted: #555;
        --accent-primary: #FF4500;
        --accent-hover: #FF3300;
        --border-color: #FF4500;
        --shadow-color: rgba(255, 69, 0, 0.25);
        --footer-bg: #f8f8f8;
        --footer-border: #ddd;
    }

    /* Footer styles for the copyright text */
    .simple-footer {
        background-color: var(--footer-bg);
        color: var(--text-primary);
        padding: 20px 0;
        text-align: center;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        position: relative;
        width: 100%;
        margin: 0;
        border-top: 2px solid var(--border-color);
        transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    /* Orange line above the footer text */
    .simple-footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: var(--accent-primary);
        transition: background-color 0.3s ease;
    }

    /* === Theme Toggle Button === */
    .theme-toggle-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
    }

    .theme-toggle-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--accent-primary);
        border: 3px solid var(--bg-card);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #fff;
        box-shadow: 0 8px 25px var(--shadow-color);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .theme-toggle-btn:hover {
        transform: scale(1.1) rotate(15deg);
        box-shadow: 0 12px 35px var(--shadow-color);
    }

    .theme-toggle-btn:active {
        transform: scale(0.95);
    }

    .theme-toggle-btn .theme-icon {
        position: absolute;
        transition: all 0.3s ease;
    }

    .theme-toggle-btn .sun-icon {
        opacity: 0;
        transform: rotate(-90deg) scale(0);
    }

    .theme-toggle-btn .moon-icon {
        opacity: 1;
        transform: rotate(0deg) scale(1);
    }

    [data-theme="light"] .theme-toggle-btn .sun-icon {
        opacity: 1;
        transform: rotate(0deg) scale(1);
    }

    [data-theme="light"] .theme-toggle-btn .moon-icon {
        opacity: 0;
        transform: rotate(90deg) scale(0);
    }

    /* Responsive design for theme toggle */
    @media (max-width: 768px) {
        .theme-toggle-container {
            bottom: 20px;
            right: 20px;
        }
        
        .theme-toggle-btn {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }
    }
</style>

<div class="simple-footer">
    &copy; <?php echo date('Y'); ?> MealMate. All rights reserved.
</div>

<!-- Theme Toggle Button -->
<div class="theme-toggle-container">
    <button class="theme-toggle-btn" aria-label="Toggle theme" title="Switch theme">
        <i class="fas fa-sun theme-icon sun-icon"></i>
        <i class="fas fa-moon theme-icon moon-icon"></i>
    </button>
</div>

<script src="/MealMate-online-food-ordering-system/theme-toggle.js"></script>