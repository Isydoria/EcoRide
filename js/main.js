function toggleMenu() {
    const navMenu = document.querySelector('.nav-menu');
    const menuBtn = document.querySelector('.mobile-menu-btn');
    
    if (navMenu) {
        navMenu.classList.toggle('active');
    }
    
    // Animation du burger
    if (menuBtn) {
        menuBtn.classList.toggle('active');
    }
}

// Fermer le menu mobile quand on clique ailleurs
document.addEventListener('click', function(event) {
    const navMenu = document.querySelector('.nav-menu');
    const menuBtn = document.querySelector('.mobile-menu-btn');
    
    if (navMenu && navMenu.classList.contains('active')) {
        if (!event.target.closest('.nav-menu') && !event.target.closest('.mobile-menu-btn')) {
            navMenu.classList.remove('active');
            if (menuBtn) menuBtn.classList.remove('active');
        }
    }
});

// Animation navbar au scroll
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        if (window.scrollY > 50) {
            navbar.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
        } else {
            navbar.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        }
    }
});

// Fonction utilitaire pour afficher les messages
function showMessage(type, message, elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = message;
        element.classList.add('show');
        
        // Masquer automatiquement après 5 secondes
        setTimeout(() => {
            element.classList.remove('show');
        }, 5000);
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('EcoRide - Application prête');
    
    // Ajouter l'événement au bouton menu si présent
    const menuBtn = document.querySelector('.mobile-menu-btn');
    if (menuBtn) {
        menuBtn.addEventListener('click', toggleMenu);
    }
});