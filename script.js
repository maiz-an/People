// Typed.js initialization after DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    var typed = new Typed(".multiple-text", {
        strings: ["Share Foods,", "Support Homeless,", "Give Donations,","Listen Land Activities."],
        typeSpeed: 60,
        backSpeed: 30,
        backDelay: 2000,
        loop: true
    });
});


// Smooth Scroll for Anchors
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// Sticky Navbar on Scroll
window.addEventListener('scroll', () => {
    const header = document.querySelector('.header');
    header.classList.toggle('sticky', window.scrollY > 50);
});



