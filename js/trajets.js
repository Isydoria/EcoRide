// trajets.js - Gestion de la recherche et affichage des trajets

// Variables globales pour stocker les résultats et les filtres
let currentResults = [];
let currentFilters = {
    ecologique: false,
    prix_max: null,
    duree_max: null,
    note_min: null
};

// Fonction appelée quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page trajets chargée');
    
    // Gérer la soumission du formulaire de recherche
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', handleSearch);
    }
    
    // Définir la date minimum à aujourd'hui
    const dateInput = document.getElementById('date_depart');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        dateInput.value = today; // Mettre aujourd'hui par défaut
    }
});

// Fonction pour gérer la recherche
function handleSearch(e) {
    e.preventDefault();
    console.log('Recherche lancée');
    
    // Récupérer les valeurs du formulaire
    const villeDepart = document.getElementById('ville_depart').value;
    const villeArrivee = document.getElementById('ville_arrivee').value;
    const dateDepart = document.getElementById('date_depart').value;
    
    // Vérifier que les villes sont différentes
    if (villeDepart.toLowerCase() === villeArrivee.toLowerCase()) {
        showMessage('error', '⚠️ Les villes de départ et d\'arrivée doivent être différentes');
        return;
    }
    
    // Afficher le message de chargement
    showMessage('loading', '🔄 Recherche en cours...');
    
    // Afficher la section des filtres
    document.getElementById('filtersSection').style.display = 'block';
    
    // Créer les données à envoyer
    const formData = new FormData();
    formData.append('ville_depart', villeDepart);
    formData.append('ville_arrivee', villeArrivee);
    formData.append('date_depart', dateDepart);
    
    // Envoyer la requête au serveur
    fetch('api/search-trajets.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Résultats reçus:', data);
        
        if (data.success) {
            currentResults = data.trajets;
            displayResults(data.trajets);
            
            // Message selon le nombre de résultats
            if (data.trajets.length > 0) {
                showMessage('success', `✅ ${data.trajets.length} trajet(s) trouvé(s)`);
            } else {
                showNoResults(data.alternatives);
            }
        } else {
            showMessage('error', '❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showMessage('error', '❌ Une erreur est survenue. Veuillez réessayer.');
    });
}

// Fonction pour afficher les résultats
function displayResults(trajets) {
    const grid = document.getElementById('trajetsGrid');
    grid.innerHTML = ''; // Vider la grille
    
    // Masquer le message "pas de résultats"
    document.getElementById('noResults').style.display = 'none';
    
    // Si pas de trajets, afficher un message
    if (trajets.length === 0) {
        grid.innerHTML = '<p class="no-results">Aucun trajet ne correspond à vos critères.</p>';
        return;
    }
    
    // Créer une carte pour chaque trajet
    trajets.forEach(trajet => {
        const card = createTrajetCard(trajet);
        grid.innerHTML += card;
    });
}

// Fonction pour créer une carte de trajet
function createTrajetCard(trajet) {
    // Vérifier si c'est écologique (véhicule électrique ou hybride)
    const isEco = trajet.type_carburant === 'electrique' || 
                  trajet.type_carburant === 'hybride' || 
                  trajet.type_carburant === 'hydrogene';
    
    // Créer les étoiles pour la note
    const stars = createStarRating(trajet.note_moyenne || 0);
    
    // Calculer la durée en heures et minutes
    // Calculer durée à partir des dates complètes au lieu des heures séparées
    const duree = trajet.date_arrivee && trajet.date_depart
        ? calculateDurationFromDates(trajet.date_depart, trajet.date_arrivee)
        : 'À définir';
    
    // Créer l'initiale du conducteur
    const initial = trajet.conducteur_pseudo ? trajet.conducteur_pseudo[0].toUpperCase() : '?';
    
    return `
        <div class="trajet-card ${isEco ? 'ecologique' : ''}">
            <div class="trajet-header">
                ${isEco ? '<span class="eco-badge">🌱 Électrique</span>' : ''}
            </div>
            
            <div class="driver-info">
                <div class="driver-avatar">${initial}</div>
                <div class="driver-details">
                    <div class="driver-name">${trajet.conducteur_pseudo || 'Conducteur'}</div>
                    <div class="driver-rating">
                        ${stars} 
                        <span>(${trajet.note_moyenne || 0}/5 - ${trajet.nb_avis || 0} avis)</span>
                    </div>
                </div>
            </div>
            
            <div class="trajet-details">
                <div class="trajet-route">
                    <div class="route-point">
                        <div class="route-city">📍 ${trajet.ville_depart}</div>
                    </div>
                    <span class="route-arrow">→</span>
                    <div class="route-point">
                        <div class="route-city">📍 ${trajet.ville_arrivee}</div>
                    </div>
                </div>
                
                <div class="trajet-info">
                    <div class="info-item">
                        📅 ${formatDate(trajet.date_depart)}
                    </div>
                    <div class="info-item">
                        🕐 ${formatTime(trajet.heure_depart)}
                    </div>
                    <div class="info-item">
                        ⏱️ Durée: ${duree}
                    </div>
                    <div class="info-item">
                        🚗 ${trajet.marque} ${trajet.modele}
                    </div>
                </div>
            </div>
            
            <div class="trajet-footer">
                <div>
                    <div class="trajet-price">
                        ${trajet.prix} crédits
                        <small>/ place</small>
                    </div>
                    <div class="trajet-places">
                        ${trajet.places_disponibles} place(s) restante(s)
                    </div>
                </div>
                <a href="trajet-detail.php?id=${trajet.id_trajet}" class="btn-details">
                    Voir détails
                </a>
            </div>
        </div>
    `;
}

// Fonction pour appliquer les filtres
function applyFilters() {
    console.log('Application des filtres');

    // Récupérer les valeurs des filtres
    currentFilters.ecologique = document.getElementById('filter-eco').checked;
    currentFilters.prix_max = document.getElementById('filter-prix').value || null;
    currentFilters.duree_max = document.getElementById('filter-duree').value || null;
    currentFilters.note_min = document.getElementById('filter-note').value || null;

    // Refaire la recherche avec les filtres (US4 - côté serveur)
    const villeDepart = document.getElementById('ville_depart').value;
    const villeArrivee = document.getElementById('ville_arrivee').value;
    const dateDepart = document.getElementById('date_depart').value;

    if (villeDepart && villeArrivee && dateDepart) {
        // Afficher le message de filtrage
        showMessage('loading', '🔄 Application des filtres...');

        // Créer les données à envoyer avec les filtres
        const formData = new FormData();
        formData.append('ville_depart', villeDepart);
        formData.append('ville_arrivee', villeArrivee);
        formData.append('date_depart', dateDepart);

        // Ajouter les filtres
        if (currentFilters.ecologique) {
            formData.append('ecologique', 'true');
        }
        if (currentFilters.prix_max) {
            formData.append('prix_max', currentFilters.prix_max);
        }
        if (currentFilters.duree_max) {
            formData.append('duree_max', currentFilters.duree_max);
        }
        if (currentFilters.note_min) {
            formData.append('note_min', currentFilters.note_min);
        }

        // Envoyer la requête au serveur avec filtres
        fetch('api/search-trajets.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Résultats filtrés reçus:', data);

            if (data.success) {
                currentResults = data.trajets;
                displayResults(data.trajets);

                // Message selon le nombre de résultats
                if (data.trajets.length > 0) {
                    showMessage('success', `✅ ${data.trajets.length} trajet(s) trouvé(s) après filtrage`);
                } else {
                    showMessage('info', '🔍 Aucun trajet ne correspond aux filtres sélectionnés. Essayez de les modifier.');
                }
            } else {
                showMessage('error', '❌ ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showMessage('error', '❌ Une erreur est survenue lors du filtrage.');
        });
    }
}

// Fonction pour afficher un message
function showMessage(type, message) {
    const statusDiv = document.getElementById('statusMessage');
    statusDiv.className = 'status-message ' + type;
    statusDiv.innerHTML = `<p>${message}</p>`;
}

// Fonction pour afficher "pas de résultats"
function showNoResults(alternatives) {
    document.getElementById('trajetsGrid').innerHTML = '';
    document.getElementById('noResults').style.display = 'block';
    
    // Si des dates alternatives sont proposées
    if (alternatives && alternatives.length > 0) {
        const altDiv = document.getElementById('alternativeDates');
        altDiv.innerHTML = `
            <h4>📅 Dates alternatives avec des trajets disponibles :</h4>
            <div class="alternative-dates-list">
                ${alternatives.map(date => `
                    <button class="alternative-date-btn" onclick="searchAlternativeDate('${date}')">
                        ${formatDate(date)}
                    </button>
                `).join('')}
            </div>
        `;
    }
}

// Fonction pour rechercher une date alternative
function searchAlternativeDate(date) {
    document.getElementById('date_depart').value = date;
    document.getElementById('searchForm').dispatchEvent(new Event('submit'));
}

// ========== FONCTIONS UTILITAIRES ==========

// Créer les étoiles pour la notation
function createStarRating(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
    let stars = '';
    
    for (let i = 0; i < fullStars; i++) {
        stars += '⭐';
    }
    if (hasHalfStar) {
        stars += '✨';
    }
    for (let i = stars.length; i < 5; i++) {
        stars += '☆';
    }
    
    return stars;
}

// Formater une date
function formatDate(dateString) {
    if (!dateString) return 'Date inconnue';

    const date = new Date(dateString);

    // Vérifier que la date est valide
    if (isNaN(date.getTime())) return 'Date invalide';

    const options = {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    };

    return date.toLocaleDateString('fr-FR', options);
}

// Formater une heure
function formatTime(timeString) {
    return timeString.substring(0, 5);
}

// Calculer la durée entre deux heures
function calculateDuration(heureDepart, heureArrivee) {
    const minutes = calculateDurationInMinutes(heureDepart, heureArrivee);
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours > 0) {
        return `${hours}h${mins > 0 ? mins : ''}`;
    }
    return `${mins}min`;
}

// Calculer la durée en minutes
function calculateDurationInMinutes(heureDepart, heureArrivee) {
    const [h1, m1] = heureDepart.split(':').map(Number);
    const [h2, m2] = heureArrivee.split(':').map(Number);

    const minutes1 = h1 * 60 + m1;
    const minutes2 = h2 * 60 + m2;

    return minutes2 - minutes1;
}

// Calculer durée entre deux dates complètes
function calculateDurationFromDates(dateDepart, dateArrivee) {
    const depart = new Date(dateDepart);
    const arrivee = new Date(dateArrivee);

    const diffMs = arrivee - depart;
    const diffMinutes = Math.floor(diffMs / (1000 * 60));

    if (diffMinutes < 0) return 'À définir';

    const hours = Math.floor(diffMinutes / 60);
    const minutes = diffMinutes % 60;

    if (hours > 0) {
        return `${hours}h${minutes > 0 ? minutes.toString().padStart(2, '0') : ''}`;
    }
    return `${minutes}min`;
}