// Smooth scroll untuk navigasi
document.querySelectorAll('nav a').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        document.querySelector(targetId).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// CTA button click event
document.querySelector('.cta-button').addEventListener('click', function() {
    alert('Terima kasih telah mengunjungi website kami!');
});

// Form validation example
function validateForm() {
    const email = document.getElementById('email').value;
    if (!email.includes('@')) {
        alert('Email tidak valid!');
        return false;
    }
    return true;
}