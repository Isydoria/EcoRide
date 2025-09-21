// trajet-detail.js - Gestion de la page de détail d'un trajet

// Variable globale pour stocker les détails du trajet
let trajetDetails = null;

// Fonction appelée au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page de détail chargée pour le trajet ID:', trajetId);
    
    // Charger les détails du trajet
    loadTrajetDetails();
});

// Fonction pour charger les détails du trajet
function loadTrajetDetails() {
    console.log('Chargement des détails du trajet...');
    
    // Créer les données à envoyer
    const formData = new FormData();
    formData.append('trajet_id', trajetId);
    
    // Appeler l'API
    fetch('api/get-trajet-detail.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Données reçues:', data);
        
        if (data.success) {
            trajetDetails = data.trajet;
            displayTrajetDetails(data.trajet);
            
            // Masquer le chargement et afficher le contenu
            document.getElementById('loadingSection').style.display = 'none';
            document.getElementById('trajetContent').style.display = 'block';
        } else {
            // Afficher l'erreur
            document.getElementById('loadingSection').style.display = 'none';
            document.getElementById('errorSection').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        document.getElementById('loadingSection').style.display = 'none';
        document.getElementById('errorSection').style.display = 'block';
    });
}

// Fonction pour afficher les détails du trajet
function displayTrajetDetails(trajet) {
    // Indicateur écologique
    const isEco = trajet.type_carburant === 'electrique' || 
                  trajet.type_carburant === 'hybride' || 
                  trajet.type_carburant === 'hydrogene';
    
    if (isEco) {
        document.getElementById('ecoIndicator').innerHTML = '🌱 Trajet écologique';
        document.getElementById('ecoIndicator').classList.remove('hidden');
    }
    
    // Informations principales
    document.getElementById('villeDepart').textContent = trajet.ville_depart;
    document.getElementById('villeArrivee').textContent = trajet.ville_arrivee;
    document.getElementById('dateDepart').textContent = formatDate(trajet.date_depart);
    document.getElementById('heureDepart').textContent = formatTime(trajet.heure_depart);
    
    // Itinéraire détaillé
    document.getElementById('adresseDepart').textContent = trajet.adresse_depart || trajet.ville_depart;
    document.getElementById('adresseArrivee').textContent = trajet.adresse_arrivee || trajet.ville_arrivee;
    document.getElementById('heureDepartDetail').textContent = 'Départ à ' + formatTime(trajet.heure_depart);
    document.getElementById('heureArriveeDetail').textContent = 'Arrivée prévue à ' + formatTime(trajet.heure_arrivee);
    document.getElementById('dureeTrajet').textContent = calculateDuration(trajet.heure_depart, trajet.heure_arrivee);
    
    // Informations sur le véhicule
    document.getElementById('vehiculeMarque').textContent = trajet.marque;
    document.getElementById('vehiculeModele').textContent = trajet.modele;
    document.getElementById('vehiculeCouleur').textContent = trajet.couleur;
    document.getElementById('vehiculeEnergie').textContent = capitalizeFirst(trajet.type_carburant);
    document.getElementById('vehiculePlaces').textContent = trajet.nombre_places_vehicule;
    
    // Classe spéciale pour véhicule écologique
    if (isEco) {
        document.getElementById('vehiculeEnergie').classList.add('eco');
    }
    
    // Préférences du conducteur
    displayPreferences(trajet.preferences);
    
    // Avis
    displayAvis(trajet.avis);
    
    // Informations du conducteur
    const initial = trajet.conducteur_pseudo ? trajet.conducteur_pseudo[0].toUpperCase() : '?';
    document.getElementById('driverAvatar').textContent = initial;
    document.getElementById('driverName').textContent = trajet.conducteur_pseudo;
    
    // Note et étoiles
    const stars = createStarRating(trajet.note_moyenne || 0);
    document.getElementById('driverRating').innerHTML = `
        ${stars} 
        <span>(${trajet.note_moyenne || 0}/5 - ${trajet.nb_avis || 0} avis)</span>
    `;
    
    // Statistiques du conducteur
    document.getElementById('totalTrajets').textContent = trajet.total_trajets || 0;
    document.getElementById('memberSince').textContent = formatMemberSince(trajet.membre_depuis);
    
    // Informations de réservation
    document.getElementById('prixTrajet').textContent = trajet.prix;
    document.getElementById('placesDisponibles').textContent = trajet.places_disponibles;
    
    // Coût total (prix + commission de 2 crédits)
    const prixNumeric = parseFloat(trajet.prix);
    const coutTotal = prixNumeric + 2;
    document.getElementById('coutTotal').textContent = coutTotal;
    
    // Vérifier si l'utilisateur peut réserver
    if (isLoggedIn) {
        checkBookingAvailability(trajet, coutTotal);
    }
}

// Fonction pour afficher les préférences
function displayPreferences(preferences) {
    const container = document.getElementById('preferencesContainer');
    container.innerHTML = '';
    
    if (!preferences) {
        container.innerHTML = '<p class="no-avis">Aucune préférence spécifiée</p>';
        return;
    }
    
    // Préférences standards
    const prefList = [
        { key: 'accepte_fumeur', label: '🚬 Fumeur', yes: 'Accepté', no: 'Non accepté' },
        { key: 'accepte_animaux', label: '🐕 Animaux', yes: 'Acceptés', no: 'Non acceptés' },
        { key: 'accepte_musique', label: '🎵 Musique', yes: 'Appréciée', no: 'Silence préféré' },
        { key: 'accepte_discussion', label: '💬 Discussion', yes: 'Appréciée', no: 'Calme préféré' }
    ];
    
    prefList.forEach(pref => {
        const value = preferences[pref.key];
        if (value !== undefined) {
            const div = document.createElement('div');
            div.className = `preference-item ${value == 1 ? 'yes' : 'no'}`;
            div.innerHTML = `
                ${pref.label} 
                ${value == 1 ? '✅' : '❌'} 
                ${value == 1 ? pref.yes : pref.no}
            `;
            container.appendChild(div);
        }
    });
    
    // Préférences personnalisées
    if (preferences.preferences_autres) {
        const div = document.createElement('div');
        div.className = 'preference-item';
        div.style.gridColumn = '1 / -1';
        div.innerHTML = `📝 ${preferences.preferences_autres}`;
        container.appendChild(div);
    }
}

// Fonction pour afficher les avis
function displayAvis(avis) {
    const container = document.getElementById('avisContainer');
    container.innerHTML = '';
    
    if (!avis || avis.length === 0) {
        container.innerHTML = '<p class="no-avis">Aucun avis pour le moment</p>';
        return;
    }
    
    avis.forEach(item => {
        const div = document.createElement('div');
        div.className = 'avis-item';
        
        const stars = '⭐'.repeat(item.note);
        
        div.innerHTML = `
            <div class="avis-header">
                <span class="avis-author">${item.auteur}</span>
                <span class="avis-rating">${stars}</span>
            </div>
            <p class="avis-comment">${item.commentaire}</p>
        `;
        
        container.appendChild(div);
    });
}

// Fonction pour vérifier la disponibilité de réservation
function checkBookingAvailability(trajet, coutTotal) {
    const btnParticiper = document.getElementById('btnParticiper');
    
    // Vérifier si l'utilisateur est le conducteur
    if (parseInt(trajet.id_conducteur) === userId) {
        btnParticiper.textContent = 'Vous êtes le conducteur';
        btnParticiper.disabled = true;
        return;
    }
    
    // Vérifier si déjà réservé
    if (trajet.deja_reserve) {
        btnParticiper.textContent = 'Déjà réservé';
        btnParticiper.disabled = true;
        return;
    }
    
    // Vérifier les crédits
    if (userCredits < coutTotal) {
        btnParticiper.textContent = 'Crédits insuffisants';
        btnParticiper.disabled = true;
        return;
    }
    
    // Vérifier les places disponibles
    if (trajet.places_disponibles <= 0) {
        btnParticiper.textContent = 'Complet';
        btnParticiper.disabled = true;
        return;
    }
}

// Fonction pour participer au trajet
function participerTrajet() {
    console.log('Demande de participation au trajet');
    
    if (!trajetDetails) return;
    
    // Calculer le coût total
    const prixNumeric = parseFloat(trajetDetails.prix);
    const coutTotal = prixNumeric + 2;
    
    // Afficher le modal de confirmation
    document.getElementById('modalCout').textContent = coutTotal;
    document.getElementById('modalTrajet').textContent = 
        trajetDetails.ville_depart + ' → ' + trajetDetails.ville_arrivee;
    document.getElementById('modalDate').textContent = 
        formatDate(trajetDetails.date_depart) + ' à ' + formatTime(trajetDetails.heure_depart);
    
    document.getElementById('confirmModal').style.display = 'flex';
}

// Fonction pour fermer le modal
function closeModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

// Fonction pour fermer la bannière flottante
function closeFloatingBanner() {
    const banner = document.getElementById('floatingBanner');
    if (banner) {
        banner.classList.add('closing');
        setTimeout(() => {
            banner.style.display = 'none';
        }, 300);
    }
}

// Fermer automatiquement la bannière après 10 secondes
if (!isLoggedIn) {
    setTimeout(() => {
        closeFloatingBanner();
    }, 10000);
}

// Fonction pour confirmer la réservation
function confirmerReservation() {
    console.log('Confirmation de la réservation');
    
    // Fermer le modal
    closeModal();
    
    // Désactiver le bouton
    const btnParticiper = document.getElementById('btnParticiper');
    btnParticiper.disabled = true;
    btnParticiper.textContent = 'Réservation en cours...';
    
    // Créer les données à envoyer
    const formData = new FormData();
    formData.append('trajet_id', trajetId);
    formData.append('nombre_places', 1); // Pour l'instant, on réserve toujours 1 place
    
    // Appeler l'API
    fetch('api/participer-trajet.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Réponse de réservation:', data);
        
        if (data.success) {
            // Réservation réussie
            alert('✅ ' + data.message);
            
            // Mettre à jour l'interface
            btnParticiper.textContent = 'Réservé !';
            btnParticiper.disabled = true;
            
            // Mettre à jour les crédits affichés
            if (data.nouveaux_credits !== undefined) {
                const creditsElement = document.querySelector('.user-credits strong');
                if (creditsElement) {
                    creditsElement.textContent = data.nouveaux_credits;
                }
            }
            
            // Recharger la page après 2 secondes
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            // Erreur
            alert('❌ ' + data.message);
            btnParticiper.disabled = false;
            btnParticiper.textContent = 'Réserver ce trajet';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Une erreur est survenue. Veuillez réessayer.');
        btnParticiper.disabled = false;
        btnParticiper.textContent = 'Réserver ce trajet';
    });
}

// ========== FONCTIONS UTILITAIRES ==========

// Créer les étoiles pour la notation
function createStarRating(rating) {
    const fullStars = Math.floor(rating);
    let stars = '';
    
    for (let i = 0; i < 5; i++) {
        if (i < fullStars) {
            stars += '⭐';
        } else {
            stars += '☆';
        }
    }
    
    return stars;
}

// Formater une date
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    return date.toLocaleDateString('fr-FR', options);
}

// Formater une heure
function formatTime(timeString) {
    if (!timeString) return '';
    // Si c'est déjà au format HH:MM, on le retourne tel quel
    if (timeString.includes(':')) {
        return timeString.substring(0, 5);
    }
    // Sinon on essaie de le formater
    return timeString;
}

// Calculer la durée entre deux heures
function calculateDuration(heureDepart, heureArrivee) {
    if (!heureDepart || !heureArrivee) return 'Non défini';
    
    const [h1, m1] = heureDepart.split(':').map(Number);
    const [h2, m2] = heureArrivee.split(':').map(Number);
    
    const minutes1 = h1 * 60 + m1;
    const minutes2 = h2 * 60 + m2;
    
    const diff = minutes2 - minutes1;
    const hours = Math.floor(diff / 60);
    const mins = diff % 60;
    
    if (hours > 0 && mins > 0) {
        return `${hours}h${mins}min`;
    } else if (hours > 0) {
        return `${hours}h`;
    } else {
        return `${mins}min`;
    }
}

// Formater la date d'inscription
function formatMemberSince(dateString) {
    if (!dateString) return 'Nouveau';
    
    const date = new Date(dateString);
    const month = date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
    return month;
}

// Mettre la première lettre en majuscule
function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
