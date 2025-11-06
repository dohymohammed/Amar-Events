const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');
const mobileOverlay = document.querySelector('.mobile-overlay');

function toggleMobileMenu() {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
    mobileOverlay.classList.toggle('active');
    document.body.classList.toggle('menu-open');
}

function closeMobileMenu() {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    mobileOverlay.classList.remove('active');
    document.body.classList.remove('menu-open');
}

hamburger.addEventListener('click', toggleMobileMenu);

mobileOverlay.addEventListener('click', closeMobileMenu);

document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', closeMobileMenu));

document.addEventListener('DOMContentLoaded', () => {
    const style = document.createElement('style');
    style.textContent = `
        body.menu-open {
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);
});

let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const totalSlides = slides.length;

function showSlide(index) {
    slides.forEach(slide => slide.classList.remove('active'));
    slides[index].classList.add('active');
}

function changeSlide(direction) {
    currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
    showSlide(currentSlide);
}

setInterval(() => {
    changeSlide(1);
}, 5000);

let currentEventSlide = 0;
const eventsTrack = document.querySelector('.events-track');
const eventCards = document.querySelectorAll('.events-track .event-card');

function slideEvents(direction) {
    const cardWidth = eventCards[0].offsetWidth + 32; 
    const maxSlide = eventCards.length - Math.floor(eventsTrack.parentElement.offsetWidth / cardWidth);

    currentEventSlide = Math.max(0, Math.min(maxSlide, currentEventSlide + direction));

    if (eventsTrack) {
        eventsTrack.style.transform = `translateX(-${currentEventSlide * cardWidth}px)`;
    }
}

function animateCounter(element, target, suffix = '') {
    let current = 0;
    const increment = target / 100;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }

        if (suffix === 'Million') {
            element.textContent = Math.floor(current) + 'Million';
        } else if (suffix.includes('K+')) {
            element.textContent = Math.floor(current) + 'K+';
        } else {
            element.textContent = Math.floor(current) + suffix;
        }
    }, 30);
}

const statsSection = document.querySelector('.statistics');
const statNumbers = document.querySelectorAll('.stat-number');
let hasAnimated = false;

if (statsSection && statNumbers.length > 0) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !hasAnimated) {
                hasAnimated = true;

                statNumbers.forEach(statNumber => {
                    const target = parseInt(statNumber.getAttribute('data-target'));
                    const suffix = statNumber.getAttribute('data-suffix');
                    animateCounter(statNumber, target, suffix);
                });
            }
        });
    }, { threshold: 0.5 });

    observer.observe(statsSection);
}

const faqItems = document.querySelectorAll('.faq-item');

faqItems.forEach(item => {
    const question = item.querySelector('.faq-question');

    question.addEventListener('click', () => {
        const isActive = item.classList.contains('active');

        faqItems.forEach(faqItem => {
            faqItem.classList.remove('active');
            const icon = faqItem.querySelector('.faq-icon');
            icon.classList.remove('fa-minus');
            icon.classList.add('fa-plus');
        });

        if (!isActive) {
            item.classList.add('active');
            const icon = item.querySelector('.faq-icon');
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');
        }
    });
});

window.addEventListener('scroll', () => {
    const backToTopBtn = document.querySelector('.back-to-top');
    if (backToTopBtn) {
        if (window.pageYOffset > 100) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    }
});

const backToTopBtn = document.querySelector('.back-to-top');
if (backToTopBtn) {
    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

console.log('Events website initialized with animated counters');

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('eventSearch')) {
        initializeEventSearch();
    }
});

function initializeEventSearch() {
    const searchInput = document.getElementById('eventSearch');
    const eventCards = document.querySelectorAll('.event-card');
    const categoryCards = document.querySelectorAll('.category-card');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        eventCards.forEach(card => {
            const eventTitle = card.querySelector('.event-title');
            const eventDetails = card.querySelector('.event-details');

            if (eventTitle && eventDetails) {
                const titleText = eventTitle.textContent.toLowerCase();
                const detailsText = eventDetails.textContent.toLowerCase();

                if (titleText.includes(searchTerm) || detailsText.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                    card.style.transition = 'opacity 0.3s ease';
                    card.style.opacity = '1';
                } else {
                    card.style.display = 'none';
                    card.style.opacity = '0';
                }
            }
        });

        const visibleEvents = Array.from(eventCards).filter(card => card.style.display !== 'none');
        const eventsContainer = document.querySelector('.events-grid');
        let noResultsMsg = document.querySelector('.no-results-message');

        if (visibleEvents.length === 0 && searchTerm !== '') {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-results-message';
                noResultsMsg.innerHTML = '<p>No events found matching your search.</p>';
                noResultsMsg.style.textAlign = 'center';
                noResultsMsg.style.color = '#888';
                noResultsMsg.style.padding = '2rem';
                noResultsMsg.style.fontSize = '1.1rem';
                eventsContainer.appendChild(noResultsMsg);
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }
    });

}