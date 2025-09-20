// auth.js - Scripts authentification avec PHP

document.addEventListener('DOMContentLoaded', function() {
    // ========== CONNEXION ==========
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember')?.checked || false;
            
            // Validation côté client
            if (!validateEmail(email)) {
                showMessage('error', 'Format d\'email invalide', 'errorMessage');
                return;
            }
            
            // Désactiver le bouton pendant l'envoi
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Connexion en cours...';
            
            // Créer les données du formulaire
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            formData.append('remember', remember);
            
            // Envoyer la requête AJAX - Utiliser login-simple.php qui fonctionne
            fetch('api/login-simple.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // D'abord récupérer le texte pour debug
                return response.text();
            })
            .then(text => {
                console.log('Réponse brute:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showMessage('success', data.message, 'successMessage');
                        // Redirection après 1 seconde
                        setTimeout(() => {
                            window.location.href = data.data.redirect;
                        }, 1000);
                    } else {
                        showMessage('error', data.message, 'errorMessage');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Se connecter';
                    }
                } catch (e) {
                    console.error('Erreur parsing JSON:', e);
                    showMessage('error', 'Erreur de communication avec le serveur', 'errorMessage');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Se connecter';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('error', 'Une erreur est survenue. Veuillez réessayer.', 'errorMessage');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Se connecter';
            });
        });
    }
    
    // ========== INSCRIPTION ==========
    const signupForm = document.getElementById('signupForm');
    
    if (signupForm) {
        // Vérification de la force du mot de passe
        const passwordInput = document.getElementById('password');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.getElementById('passwordStrength');
                
                if (strengthBar) {
                    const strength = calculatePasswordStrength(password);
                    updatePasswordStrengthBar(strengthBar, strength);
                }
            });
        }
        
        // Vérification de la correspondance des mots de passe
        const passwordConfirmInput = document.getElementById('password_confirm');
        
        if (passwordConfirmInput) {
            passwordConfirmInput.addEventListener('input', function() {
                const password = document.getElementById('password').value;
                const passwordConfirm = this.value;
                
                if (passwordConfirm.length > 0) {
                    if (password !== passwordConfirm) {
                        this.style.borderColor = 'var(--danger)';
                    } else {
                        this.style.borderColor = 'var(--succes)';
                    }
                } else {
                    this.style.borderColor = '';
                }
            });
        }
        
        // Soumission du formulaire d'inscription
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const pseudo = document.getElementById('pseudo').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            const terms = document.getElementById('terms').checked;
            
            // Validations côté client
            if (pseudo.length < 3 || pseudo.length > 20) {
                showMessage('error', 'Le pseudo doit contenir entre 3 et 20 caractères', 'errorMessage');
                return;
            }
            
            if (!validateEmail(email)) {
                showMessage('error', 'Format d\'email invalide', 'errorMessage');
                return;
            }
            
            if (password.length < 8) {
                showMessage('error', 'Le mot de passe doit contenir au moins 8 caractères', 'errorMessage');
                return;
            }
            
            if (password !== passwordConfirm) {
                showMessage('error', 'Les mots de passe ne correspondent pas', 'errorMessage');
                return;
            }
            
            if (!terms) {
                showMessage('error', 'Vous devez accepter les conditions d\'utilisation', 'errorMessage');
                return;
            }
            
            // Désactiver le bouton pendant l'envoi
            const submitBtn = signupForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Création en cours...';
            
            // Créer les données du formulaire
            const formData = new FormData();
            formData.append('pseudo', pseudo);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('password_confirm', passwordConfirm);
            formData.append('terms', terms);
            
            // Envoyer la requête AJAX - Utiliser register-simple.php qui fonctionne
            fetch('api/register-simple.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // D'abord récupérer le texte pour debug
                return response.text();
            })
            .then(text => {
                console.log('Réponse inscription brute:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showMessage('success', data.message, 'successMessage');
                        // Redirection après 2 secondes
                        setTimeout(() => {
                            window.location.href = data.data.redirect;
                        }, 2000);
                    } else {
                        showMessage('error', data.message, 'errorMessage');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Créer mon compte';
                    }
                } catch (e) {
                    console.error('Erreur parsing JSON:', e);
                    console.log('Réponse reçue:', text);
                    showMessage('error', 'Erreur de communication avec le serveur', 'errorMessage');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Créer mon compte';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('error', 'Une erreur est survenue. Veuillez réessayer.', 'errorMessage');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Créer mon compte';
            });
        });
    }
    
    // ========== FONCTIONS UTILITAIRES ==========
    
    // Validation email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Calcul de la force du mot de passe
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        return strength;
    }
    
    // Mise à jour de la barre de force
    function updatePasswordStrengthBar(bar, strength) {
        bar.className = 'password-strength-bar';
        
        if (strength <= 2) {
            bar.classList.add('weak');
        } else if (strength <= 3) {
            bar.classList.add('medium');
        } else {
            bar.classList.add('strong');
        }
    }
    
    // Masquer les messages d'erreur quand on tape
    const inputs = document.querySelectorAll('.form-input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const errorMsg = document.getElementById('errorMessage');
            const successMsg = document.getElementById('successMessage');
            
            if (errorMsg) errorMsg.classList.remove('show');
            if (successMsg) successMsg.classList.remove('show');
        });
    });
});

// Fonction globale pour afficher les messages (utilisée aussi dans main.js)
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