// Sidebar toggle  
const sideNav = document.getElementById('sideNav');
const menuBtn = document.getElementById('menuBtn');

menuBtn.addEventListener('click', () => {
    sideNav.style.width = '250px';
});

// Close sidebar when clicking outside of it
document.addEventListener('click', (event) => {
    // Check if the click is outside the sidebar and not on the menu button
    if (sideNav.style.width === '250px' && !sideNav.contains(event.target) && !menuBtn.contains(event.target)) {
        sideNav.style.width = '0';
    }
});

// Services Slideshow
const featuresContainer = document.querySelector('.features');
const featureBoxes = document.querySelectorAll('.features .feature-box');
const dots = document.querySelectorAll('.carousel-dot');
const totalSlides = dots.length; // Use the number of dots for the true slide count
let currentIndex = 0;

function updateSlideshow() {
    // Determine the transition duration based on whether we're resetting the loop
    const transitionDuration = featuresContainer.classList.contains('no-transition') ? '0s' : '0.6s';
    featuresContainer.style.transition = `transform ${transitionDuration} ease-in-out`;

    const boxWidth = featureBoxes[0].offsetWidth + 40;
    const offset = -currentIndex * boxWidth;
    featuresContainer.style.transform = `translateX(${offset}px)`;

    // If we've reached a duplicated slide, instantly reset the position back to the start
    if (currentIndex >= totalSlides) {
        setTimeout(() => {
            featuresContainer.style.transition = 'none';
            featuresContainer.style.transform = `translateX(0px)`;
            currentIndex = 0;
            updateDots();
        }, 600); // Match the CSS transition duration
    } else {
        updateDots();
    }
}

function updateDots() {
    dots.forEach((dot, i) => {
        dot.classList.remove('active');
        if (i === currentIndex) {
            dot.classList.add('active');
        }
    });
}

// Click events for dots
dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        currentIndex = index;
        updateSlideshow();
    });
});

// Auto-rotate slideshow
setInterval(() => {
    currentIndex++;
    updateSlideshow();
}, 3000);

// Initial call to set up the first slide
updateSlideshow();

/* --- Header Highlight Fix --- */

const sections = document.querySelectorAll('section');
const navLinks = document.querySelectorAll('header .landing-nav a');

// Intersection Observer to handle active link on scroll
const options = {
    root: null,
    rootMargin: '0px',
    threshold: 0.5 // Adjust this value for when the highlight should change
};

const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const currentId = entry.target.id;
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').substring(1) === currentId) {
                    link.classList.add('active');
                }
            });
        }
    });
}, options);

sections.forEach(section => {
    observer.observe(section);
});

// Add a click event listener for smooth scrolling and immediate highlighting
navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = link.getAttribute('href').substring(1);
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
            window.scrollTo({
                top: targetSection.offsetTop - 80, // Adjust for the fixed header height
                behavior: 'smooth'
            });
            navLinks.forEach(navLink => navLink.classList.remove('active'));
            link.classList.add('active');
        }
    });
});

// Handle contact form submission with AJAX
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const messageDiv = document.getElementById('contactMessage');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Show loading state
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;
            messageDiv.innerHTML = '<div class="form-message info-msg">📨 Sending your message...</div>';
            
            fetch('contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const isSuccess = data.status === 'success';
                const msgClass = isSuccess ? 'success-msg' : 'error-msg';
                
                messageDiv.innerHTML = `<div class="form-message ${msgClass}">${data.message}</div>`;
                
                // Reset form on success
                if (isSuccess) {
                    contactForm.reset();
                }
                
                // Reset button
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                
                // Auto-hide message after 8 seconds (only for success)
                if (isSuccess) {
                    setTimeout(() => {
                        messageDiv.innerHTML = '';
                    }, 8000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.innerHTML = '<div class="form-message error-msg">❌ Network error. Please try again.</div>';
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});