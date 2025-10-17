// trajet-detail.js - Gestion de la page de détail d'un trajet

// Variable globale pour stocker les détails du trajet
let trajetDetails = null;

// Fonction appelée au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier que trajetId est défini
    if (typeof trajetId === 'undefined' || !trajetId) {
        console.error('trajetId non défini');
        alert('Erreur : ID du trajet non trouvé');
        return;
    }

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
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur HTTP: ' + response.status);
        }
        return response.json();
    })
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
            const errorSection = document.getElementById('errorSection');
            if (errorSection) {
                errorSection.style.display = 'block';
            } else {
                alert('Erreur : ' + data.message);
            }
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        document.getElementById('loadingSection').style.display = 'none';
        const errorSection = document.getElementById('errorSection');
        if (errorSection) {
            errorSection.style.display = 'block';
        } else {
            alert('Erreur de chargement du trajet');
        }
    });
}

// ✅ Fonction pour afficher les détails du trajet - VERSION CORRIGÉE
function displayTrajetDetails(trajet) {
    // Indicateur écologique
    const isEco = trajet.type_carburant === 'electrique' || 
                  trajet.type_carburant === 'hybride' || 
                  trajet.type_carburant === 'hydrogene';
    
    if (isEco) {
        document.getElementById('ecoIndicator').textContent = '🌱 Trajet écologique';
        document.getElementById('ecoIndicator').classList.remove('hidden');
    }
    
    // ✅ Informations principales avec formatage corrigé
    document.getElementById('villeDepart').textContent = trajet.ville_depart;
    document.getElementById('villeArrivee').textContent = trajet.ville_arrivee;
    
    // ✅ Date formatée en français complet (ex: "mercredi 22 octobre 2025")
    const dateFrancais = formatDateFrancais(trajet.date_depart);
    document.getElementById('dateDepart').textContent = dateFrancais;
    
    // ✅ Heure extraite du DATETIME (ex: "14:30")
    const heureDepart = extractTimeFromDateTime(trajet.date_depart);
    document.getElementById('heureDepart').textContent = heureDepart;
    
    // ✅ Itinéraire détaillé avec adresses ou villes
    document.getElementById('adresseDepart').textContent = trajet.adresse_depart || trajet.ville_depart;
    document.getElementById('adresseArrivee').textContent = trajet.adresse_arrivee || trajet.ville_arrivee;
    
    // ✅ Heures d'arrivée et départ dans l'itinéraire
    const heureArrivee = extractTimeFromDateTime(trajet.date_arrivee);
    document.getElementById('heureDepartDetail').textContent = 'Départ à ' + heureDepart;
    document.getElementById('heureArriveeDetail').textContent = 'Arrivée prévue à ' + heureArrivee;
    
    // ✅ Calcul de la durée entre les deux DATETIME
    const duree = calculateDurationFromDates(trajet.date_depart, trajet.date_arrivee);
    document.getElementById('dureeTrajet').textContent = duree;
    
    // ✅ Informations sur le véhicule
    document.getElementById('vehiculeMarque').textContent = trajet.marque || 'Marque inconnue';
    document.getElementById('vehiculeModele').textContent = trajet.modele || 'Modèle inconnu';
    document.getElementById('vehiculeCouleur').textContent = capitalizeFirst(trajet.couleur) || 'Non spécifiée';
    document.getElementById('vehiculeEnergie').textContent = capitalizeFirst(trajet.type_carburant) || 'Non spécifié';
    document.getElementById('vehiculePlaces').textContent = trajet.nombre_places_vehicule || '4';
    
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
    const ratingElement = document.getElementById('driverRating');
    ratingElement.textContent = ''; // Vider d'abord
    ratingElement.textContent = stars + ' ';
    const spanElement = document.createElement('span');
    spanElement.textContent = `${trajet.note_moyenne || 0}/5 (${trajet.nb_avis || 0} avis)`;
    ratingElement.appendChild(spanElement);
    
    // Statistiques du conducteur
    document.getElementById('totalTrajets').textContent = trajet.total_trajets || 0;
    document.getElementById('memberSince').textContent = formatMemberSince(trajet.membre_depuis);
    
    // Informations de réservation
    document.getElementById('prixTrajet').textContent = trajet.prix;
    document.getElementById('placesDisponibles').textContent = trajet.places_disponibles;
    
    // Coût total (prix + commission de 2 crédits)
    const prixNumeric = parseFloat(trajet.prix.toString().replace(/\s/g, ''));
    const coutTotal = prixNumeric + 2;

    // Mettre à jour le coût total seulement si l'élément existe (utilisateur connecté)
    const coutTotalElement = document.getElementById('coutTotal');
    if (coutTotalElement) {
        coutTotalElement.textContent = coutTotal;
    }
    
    // Vérifier si l'utilisateur peut réserver
    if (typeof isLoggedIn !== 'undefined' && isLoggedIn) {
        checkBookingAvailability(trajet, coutTotal);
    }
}

// Fonction pour afficher les préférences
function displayPreferences(preferences) {
    const container = document.getElementById('preferencesContainer');
    if (!container) return;
    
    container.textContent = ''; // Vider avec textContent pour éviter XSS

    if (!preferences) {
        const p = document.createElement('p');
        p.className = 'no-avis';
        p.textContent = 'Aucune préférence spécifiée';
        container.appendChild(p);
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
            div.textContent = `${pref.label} ${value == 1 ? '✅' : '❌'} ${value == 1 ? pref.yes : pref.no}`;
            container.appendChild(div);
        }
    });
    
    // Préférences personnalisées
    if (preferences.preferences_autres) {
        const div = document.createElement('div');
        div.className = 'preference-item';
        div.style.gridColumn = '1 / -1';
        div.textContent = `📝 ${preferences.preferences_autres}`;
        container.appendChild(div);
    }
}

// Fonction pour afficher les avis
function displayAvis(avis) {
    const container = document.getElementById('avisContainer');
    if (!container) return;

    container.textContent = ''; // Vider avec textContent

    if (!avis || avis.length === 0) {
        const p = document.createElement('p');
        p.className = 'no-avis';
        p.textContent = 'Aucun avis pour le moment';
        container.appendChild(p);
        return;
    }

    avis.forEach(item => {
        const div = document.createElement('div');
        div.className = 'avis-item';

        const stars = '⭐'.repeat(item.note);

        // Créer les éléments de manière sécurisée
        const header = document.createElement('div');
        header.className = 'avis-header';

        const authorSpan = document.createElement('span');
        authorSpan.className = 'avis-author';
        authorSpan.textContent = item.auteur;

        const ratingSpan = document.createElement('span');
        ratingSpan.className = 'avis-rating';
        ratingSpan.textContent = stars;

        header.appendChild(authorSpan);
        header.appendChild(ratingSpan);

        const commentP = document.createElement('p');
        commentP.className = 'avis-comment';
        commentP.textContent = item.commentaire || 'Aucun commentaire';

        div.appendChild(header);
        div.appendChild(commentP);
        container.appendChild(div);
    });
}

// Fonction pour vérifier la disponibilité de réservation
function checkBookingAvailability(trajet, coutTotal) {
    const btnParticiper = document.getElementById('btnParticiper');
    if (!btnParticiper) return;
    
    // Vérifier si l'utilisateur est le conducteur
    if (typeof userId !== 'undefined' && userId && parseInt(trajet.id_conducteur) === parseInt(userId)) {
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
    if (typeof userCredits !== 'undefined' && userCredits !== null && parseFloat(userCredits) < coutTotal) {
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
    const prixNumeric = parseFloat(trajetDetails.prix.toString().replace(/\s/g, ''));
    const coutTotal = prixNumeric + 2;
    
    // Afficher le modal de confirmation
    document.getElementById('modalCout').textContent = coutTotal;
    document.getElementById('modalTrajet').textContent = 
        trajetDetails.ville_depart + ' → ' + trajetDetails.ville_arrivee;
    document.getElementById('modalDate').textContent = 
        formatDateFrancais(trajetDetails.date_depart) + ' à ' + extractTimeFromDateTime(trajetDetails.date_depart);
    
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
if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
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
    if (btnParticiper) {
        btnParticiper.disabled = true;
        btnParticiper.textContent = 'Réservation en cours...';
    }
    
    // Vérifier que trajetId existe
    if (typeof trajetId === 'undefined' || !trajetId) {
        alert('Erreur : ID du trajet non trouvé');
        return;
    }

    // Créer les données à envoyer
    const formData = new FormData();
    formData.append('trajet_id', trajetId);
    formData.append('nombre_places', 1); // Pour l'instant, on réserve toujours 1 place
    
    // Appeler l'API
    fetch('api/participer-trajet.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur HTTP: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Réponse de réservation:', data);
        
        if (data.success) {
            // Réservation réussie
            alert('✅ ' + data.message);
            
            if (btnParticiper) {
                // Mettre à jour l'interface
                btnParticiper.textContent = 'Réservé !';
                btnParticiper.disabled = true;
            }
            
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
            if (btnParticiper) {
                btnParticiper.disabled = false;
                btnParticiper.textContent = 'Réserver ce trajet';
            }
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Une erreur est survenue. Veuillez réessayer.');
        if (btnParticiper) {
            btnParticiper.disabled = false;
            btnParticiper.textContent = 'Réserver ce trajet';
        }
    });
}

// ========== FONCTIONS UTILITAIRES ==========

// ✅ Formater une date en français complet
function formatDateFrancais(dateString) {
    if (!dateString) return 'Date non définie';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Date invalide';
    
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
    };
    
    return date.toLocaleDateString('fr-FR', options);
}

// ✅ Extraire l'heure depuis un DATETIME complet
function extractTimeFromDateTime(datetimeString) {
    if (!datetimeString) return 'Non défini';
    
    const date = new Date(datetimeString);
    if (isNaN(date.getTime())) return 'Non défini';
    
    return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ✅ Calculer la durée entre deux dates complètes
function calculateDurationFromDates(dateDepart, dateArrivee) {
    if (!dateDepart || !dateArrivee) return 'Non calculable';
    
    const depart = new Date(dateDepart);
    const arrivee = new Date(dateArrivee);
    
    if (isNaN(depart.getTime()) || isNaN(arrivee.getTime())) {
        return 'Non calculable';
    }
    
    const diffMs = arrivee - depart;
    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    
    if (diffMinutes < 0) return 'Non calculable';
    
    const hours = Math.floor(diffMinutes / 60);
    const minutes = diffMinutes % 60;
    
    if (hours > 0) {
        return `${hours}h${minutes > 0 ? minutes.toString().padStart(2, '0') : ''}`;
    }
    return `${minutes}min`;
}

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

// Formater la date d'inscription (membre depuis)
function formatMemberSince(dateString) {
    if (!dateString) return 'Nouveau';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Nouveau';
    
    const month = date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
    return month.charAt(0).toUpperCase() + month.slice(1);
}

// Mettre la première lettre en majuscule
function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
