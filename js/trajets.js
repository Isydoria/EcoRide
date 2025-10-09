// trajets.js - Gestion de la recherche et affichage des trajets
// Version finale avec filtres fonctionnels et compteur de résultats

// Variables globales
let currentResults = [];
let currentFilters = {
    ecologique: false,
    prix_max: null,
    duree_max: null,
    note_min: null
};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Page trajets chargée');
    
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
        dateInput.value = today;
    }

    // Configurer les filtres
    setupFilters();
});

// Configuration des événements des filtres
function setupFilters() {
    // Bouton "Appliquer les filtres"
    const applyButton = document.getElementById('applyFilters');
    if (applyButton) {
        applyButton.addEventListener('click', applyFilters);
    }

    // Bouton "Réinitialiser"
    const resetButton = document.getElementById('resetFilters');
    if (resetButton) {
        resetButton.addEventListener('click', resetFilters);
    }

    // Mise à jour en temps réel du prix
    const prixSlider = document.getElementById('filter-prix');
    const prixDisplay = document.getElementById('prix-display');
    if (prixSlider && prixDisplay) {
        prixSlider.addEventListener('input', function() {
            prixDisplay.textContent = this.value;
        });
    }

    // Mise à jour en temps réel de la durée
    const dureeSlider = document.getElementById('filter-duree');
    const dureeDisplay = document.getElementById('duree-display');
    if (dureeSlider && dureeDisplay) {
        dureeSlider.addEventListener('input', function() {
            dureeDisplay.textContent = this.value;
        });
    }
}

// Gérer la recherche
function handleSearch(e) {
    e.preventDefault();
    console.log('🔍 Recherche lancée');
    
    // Récupérer les valeurs
    const villeDepart = document.getElementById('ville_depart').value.trim();
    const villeArrivee = document.getElementById('ville_arrivee').value.trim();
    const dateDepart = document.getElementById('date_depart').value;
    
    // Validation
    if (!villeDepart || !villeArrivee || !dateDepart) {
        showStatusMessage('error', '⚠️ Veuillez remplir tous les champs');
        return;
    }
    
    if (villeDepart.toLowerCase() === villeArrivee.toLowerCase()) {
        showStatusMessage('error', '⚠️ Les villes de départ et d\'arrivée doivent être différentes');
        return;
    }
    
    // Afficher le message de chargement
    showStatusMessage('loading', '🔄 Recherche en cours...');
    
    // Afficher la section des filtres
    const filtersSection = document.getElementById('filtersSection');
    if (filtersSection) {
        filtersSection.style.display = 'block';
    }
    
    // Préparer les données
    const formData = new FormData();
    formData.append('ville_depart', villeDepart);
    formData.append('ville_arrivee', villeArrivee);
    formData.append('date_depart', dateDepart);
    
    // Envoyer la requête
    fetch('api/search-trajets.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('📦 Résultats reçus:', data);
        
        if (data.success) {
            currentResults = data.trajets;
            displayResults(data.trajets);
            updateResultsCount(data.trajets.length);
            
            if (data.trajets.length > 0) {
                showStatusMessage('success', `✅ ${data.trajets.length} trajet(s) trouvé(s)`);
            } else {
                showNoResults(data.alternatives);
            }
        } else {
            showStatusMessage('error', '❌ ' + data.message);
            updateResultsCount(0);
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        showStatusMessage('error', '❌ Une erreur est survenue');
        updateResultsCount(0);
    });
}

// Appliquer les filtres
function applyFilters() {
    console.log('🔧 Application des filtres');

    // Récupérer les filtres
    currentFilters.ecologique = document.getElementById('filter-eco')?.checked || false;
    currentFilters.prix_max = document.getElementById('filter-prix')?.value || null;
    currentFilters.duree_max = document.getElementById('filter-duree')?.value || null;
    currentFilters.note_min = document.getElementById('filter-note')?.value || null;

    console.log('Filtres:', currentFilters);

    // Récupérer les paramètres de recherche
    const villeDepart = document.getElementById('ville_depart')?.value;
    const villeArrivee = document.getElementById('ville_arrivee')?.value;
    const dateDepart = document.getElementById('date_depart')?.value;

    if (!villeDepart || !villeArrivee || !dateDepart) {
        showStatusMessage('error', '⚠️ Veuillez d\'abord effectuer une recherche');
        return;
    }

    showStatusMessage('loading', '🔄 Application des filtres...');

    // Préparer les données
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

    // Envoyer la requête
    fetch('api/search-trajets.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('📦 Résultats filtrés:', data);

        if (data.success) {
            currentResults = data.trajets;
            displayResults(data.trajets);
            updateResultsCount(data.trajets.length);

            if (data.trajets.length > 0) {
                showStatusMessage('success', `✅ ${data.trajets.length} trajet(s) après filtrage`);
            } else {
                showStatusMessage('info', '🔍 Aucun trajet ne correspond aux filtres');
            }
        } else {
            showStatusMessage('error', '❌ ' + data.message);
            updateResultsCount(0);
        }
    })
    .catch(error => {
        console.error('❌ Erreur:', error);
        showStatusMessage('error', '❌ Erreur lors du filtrage');
    });
}

// Réinitialiser les filtres
function resetFilters() {
    console.log('🔄 Réinitialisation des filtres');
    
    // Réinitialiser les valeurs
    if (document.getElementById('filter-eco')) {
        document.getElementById('filter-eco').checked = false;
    }
    if (document.getElementById('filter-prix')) {
        document.getElementById('filter-prix').value = 50;
        if (document.getElementById('prix-display')) {
            document.getElementById('prix-display').textContent = '50';
        }
    }
    if (document.getElementById('filter-duree')) {
        document.getElementById('filter-duree').value = 10;
        if (document.getElementById('duree-display')) {
            document.getElementById('duree-display').textContent = '10';
        }
    }
    if (document.getElementById('filter-note')) {
        document.getElementById('filter-note').value = '';
    }
    
    // Réinitialiser l'objet
    currentFilters = {
        ecologique: false,
        prix_max: null,
        duree_max: null,
        note_min: null
    };
    
    // Relancer la recherche sans filtres
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.dispatchEvent(new Event('submit'));
    }
}

// Afficher les résultats
function displayResults(trajets) {
    const grid = document.getElementById('trajetsGrid');
    const noResults = document.getElementById('noResults');
    
    if (!grid) return;
    
    // Cacher le message "aucun résultat"
    if (noResults) {
        noResults.style.display = 'none';
    }
    
    // Vider la grille
    grid.innerHTML = '';
    
    if (trajets.length === 0) {
        if (noResults) {
            noResults.style.display = 'block';
        }
        return;
    }
    
    // Ajouter chaque trajet
    trajets.forEach(trajet => {
        const card = createTrajetCard(trajet);
        grid.appendChild(card);
    });
}

// Créer une carte de trajet
function createTrajetCard(trajet) {
    const card = document.createElement('div');
    card.className = 'trajet-card';
    
    // Badge écologique
    const ecoBadge = trajet.is_ecologique ? 
        '<span class="eco-badge">⚡ Écologique</span>' : '';
    
    // Formater les dates
    const dateDepart = new Date(trajet.date_depart);
    const heureDepart = dateDepart.toLocaleTimeString('fr-FR', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    const dateFormatee = dateDepart.toLocaleDateString('fr-FR', { 
        weekday: 'short', 
        day: 'numeric', 
        month: 'short' 
    });
    
    card.innerHTML = `
        <div class="trajet-header">
            ${ecoBadge}
            <div class="trajet-route">
                <strong>${trajet.ville_depart}</strong>
                <span class="arrow">→</span>
                <strong>${trajet.ville_arrivee}</strong>
            </div>
        </div>
        
        <div class="trajet-info">
            <div class="info-item">
                <span class="icon">📅</span>
                <span>${dateFormatee} à ${heureDepart}</span>
            </div>
            <div class="info-item">
                <span class="icon">⏱️</span>
                <span>${trajet.duree_heures}h</span>
            </div>
            <div class="info-item">
                <span class="icon">👤</span>
                <span>${trajet.conducteur_pseudo}</span>
            </div>
            <div class="info-item">
                <span class="icon">⭐</span>
                <span>${trajet.note_moyenne}/5 (${trajet.nb_avis})</span>
            </div>
            <div class="info-item">
                <span class="icon">🚗</span>
                <span>${trajet.marque} ${trajet.modele}</span>
            </div>
            <div class="info-item">
                <span class="icon">💺</span>
                <span>${trajet.places_disponibles} place(s)</span>
            </div>
        </div>
        
        <div class="trajet-footer">
            <div>
                <div class="trajet-price">
                    ${trajet.prix_formatted} <small>crédits</small>
                </div>
            </div>
            <a href="trajet-detail.php?id=${trajet.id_trajet}" class="btn-details">
                Voir détails
            </a>
        </div>
    `;
    
    return card;
}

// Mettre à jour le compteur de résultats
function updateResultsCount(count) {
    let countElement = document.getElementById('resultsCount');
    
    if (!countElement) {
        // Créer l'élément s'il n'existe pas
        const resultsSection = document.querySelector('.results-section .container');
        if (resultsSection) {
            countElement = document.createElement('div');
            countElement.id = 'resultsCount';
            countElement.className = 'results-count';
            
            // Insérer avant le statusMessage
            const statusMessage = document.getElementById('statusMessage');
            if (statusMessage) {
                resultsSection.insertBefore(countElement, statusMessage);
            } else {
                resultsSection.insertBefore(countElement, resultsSection.firstChild);
            }
        }
    }
    
    if (countElement) {
        if (count === 0) {
            countElement.style.display = 'none';
        } else {
            countElement.style.display = 'block';
            countElement.innerHTML = `
                <strong>${count}</strong> trajet${count > 1 ? 's' : ''} trouvé${count > 1 ? 's' : ''}
            `;
        }
    }
}

// Afficher un message de statut
function showStatusMessage(type, message) {
    const statusElement = document.getElementById('statusMessage');
    
    if (!statusElement) return;
    
    // Définir la classe selon le type
    statusElement.className = 'status-message ' + type;
    statusElement.innerHTML = `<p>${message}</p>`;
    statusElement.style.display = 'block';
    
    // Masquer après 5 secondes (sauf pour loading)
    if (type !== 'loading') {
        setTimeout(() => {
            statusElement.style.display = 'none';
        }, 5000);
    }
}

// Afficher le message "aucun résultat"
function showNoResults(alternatives) {
    const noResults = document.getElementById('noResults');
    const grid = document.getElementById('trajetsGrid');
    
    if (!noResults || !grid) return;
    
    // Vider la grille
    grid.innerHTML = '';
    
    // Afficher le message
    noResults.style.display = 'block';
    
    // Ajouter les dates alternatives si disponibles
    const altDatesDiv = document.getElementById('alternativeDates');
    if (altDatesDiv && alternatives && alternatives.length > 0) {
        let html = `
            <div class="alternatives">
                <h4>📅 Dates disponibles :</h4>
                <div class="dates-alternatives">
        `;
        
        alternatives.forEach(date => {
            const dateObj = new Date(date);
            const formatted = dateObj.toLocaleDateString('fr-FR', { 
                weekday: 'short', 
                day: 'numeric', 
                month: 'short' 
            });
            html += `<button class="date-alt" onclick="selectAlternativeDate('${date}')">${formatted}</button>`;
        });
        
        html += `
                </div>
            </div>
        `;
        
        altDatesDiv.innerHTML = html;
    } else if (altDatesDiv) {
        altDatesDiv.innerHTML = '';
    }
}

// Sélectionner une date alternative
function selectAlternativeDate(date) {
    const dateInput = document.getElementById('date_depart');
    if (dateInput) {
        dateInput.value = date;
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.dispatchEvent(new Event('submit'));
        }
    }
}

console.log('✅ Script trajets.js chargé avec succès');