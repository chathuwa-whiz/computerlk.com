// Modern Effects and Animations

document.addEventListener('DOMContentLoaded', function() {
    // Smooth Scroll Progress Indicator
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.appendChild(progressBar);

    window.addEventListener('scroll', () => {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        progressBar.style.width = scrolled + '%';
    });

    // Parallax Effect for Hero Section
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            heroSection.style.transform = `translateY(${scrolled * 0.5}px)`;
        });
    }

    // Smooth Image Loading
    const images = document.querySelectorAll('img[loading="lazy"]');
    images.forEach(img => {
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease';
        
        img.addEventListener('load', () => {
            img.style.opacity = '1';
        });
    });

    // Product Card Hover Effects
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', (e) => {
            const bounds = card.getBoundingClientRect();
            const mouseX = e.clientX - bounds.left;
            const mouseY = e.clientY - bounds.top;

            card.style.transform = 
                `perspective(1000px) 
                rotateX(${(mouseY - bounds.height/2) / 30}deg) 
                rotateY(${-(mouseX - bounds.width/2) / 30}deg) 
                translateZ(10px)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 
                'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
        });
    });

    // Smooth Navigation Menu
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            if (link.hash) {
                e.preventDefault();
                const targetId = link.hash;
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // Add to Cart Animation
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Create floating element
            const floater = document.createElement('div');
            floater.className = 'cart-floater';
            floater.innerHTML = '<i class="bi bi-cart"></i>';
            
            // Position at button
            const rect = button.getBoundingClientRect();
            floater.style.left = `${rect.left + rect.width/2}px`;
            floater.style.top = `${rect.top + rect.height/2}px`;
            
            document.body.appendChild(floater);
            
            // Animate to cart icon
            const cart = document.querySelector('.cart-icon');
            if (cart) {
                const cartRect = cart.getBoundingClientRect();
                
                floater.style.transform = `translate(
                    ${cartRect.left - rect.left}px,
                    ${cartRect.top - rect.top}px
                ) scale(0.5)`;
                floater.style.opacity = '0';
                
                // Remove after animation
                setTimeout(() => {
                    floater.remove();
                    
                    // Update cart counter
                    const counter = cart.querySelector('.badge');
                    if (counter) {
                        const currentCount = parseInt(counter.textContent);
                        counter.textContent = currentCount + 1;
                        
                        // Pop animation
                        counter.style.transform = 'scale(1.5)';
                        setTimeout(() => {
                            counter.style.transform = 'scale(1)';
                        }, 200);
                    }
                }, 500);
            }
        });
    });

    // Category Card Tilt Effect
    const categoryCards = document.querySelectorAll('.category-card');
    categoryCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const angleX = (y - centerY) / 20;
            const angleY = (centerX - x) / 20;
            
            card.style.transform = `perspective(1000px) rotateX(${angleX}deg) rotateY(${angleY}deg)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    });

    // Smooth Section Reveal
    const revealSection = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    };

    const sectionObserver = new IntersectionObserver(revealSection, {
        root: null,
        threshold: 0.15
    });

    document.querySelectorAll('section').forEach(section => {
        section.classList.add('reveal-section');
        sectionObserver.observe(section);
    });
});

// Add necessary styles
const style = document.createElement('style');
style.textContent = `
    .cart-floater {
        position: fixed;
        pointer-events: none;
        z-index: 1000;
        width: 20px;
        height: 20px;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .reveal-section {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s ease-out;
    }

    .reveal-section.revealed {
        opacity: 1;
        transform: translateY(0);
    }

    .scroll-progress {
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(to right, var(--primary), var(--accent));
        z-index: 1000;
        transition: width 0.1s ease;
    }
`;

document.head.appendChild(style);