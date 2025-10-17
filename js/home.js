// home.js - Scripts page d'accueil

document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de recherche
    const searchForm = document.querySelector('.search-form');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            // Validation des champs
            const departure = document.getElementById('departure');
            const arrival = document.getElementById('arrival');
            const date = document.getElementById('date');
            
            // Vérifier que les villes sont différentes
            if (departure.value.toLowerCase() === arrival.value.toLowerCase()) {
                e.preventDefault();
                if (typeof Toast !== 'undefined') {
                    Toast.warning('Les villes de départ et d\'arrivée doivent être différentes');
                }
                return false;
            }
            
            // Vérifier que la date est future
            const selectedDate = new Date(date.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                e.preventDefault();
                if (typeof Toast !== 'undefined') {
                    Toast.warning('Veuillez sélectionner une date future');
                }
                return false;
            }
            
            // Si tout est ok, le formulaire sera soumis normalement
            console.log('Recherche de trajet:', {
                departure: departure.value,
                arrival: arrival.value,
                date: date.value
            });
        });
        
        // Date minimum = aujourd'hui
        const dateInput = document.getElementById('date');
        if (dateInput) {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            dateInput.min = `${year}-${month}-${day}`;
        }
    }
    
    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observer les feature cards
    document.querySelectorAll('.feature-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });
});