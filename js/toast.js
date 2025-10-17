/**
 * Toast Notification System - EcoRide
 * Système moderne de notifications pour remplacer alert()
 */

const Toast = {
    container: null,

    /**
     * Initialiser le conteneur de toasts
     */
    init() {
        if (this.container) return;

        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    },

    /**
     * Afficher un toast
     * @param {string} message - Message à afficher
     * @param {string} type - Type: success, error, warning, info
     * @param {number} duration - Durée en ms (0 = permanent)
     * @param {string} title - Titre optionnel
     */
    show(message, type = 'info', duration = 5000, title = null) {
        this.init();

        // Créer l'élément toast
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        // Icône selon le type
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        // Titre par défaut selon le type
        const defaultTitles = {
            success: 'Succès',
            error: 'Erreur',
            warning: 'Attention',
            info: 'Information'
        };

        const finalTitle = title || defaultTitles[type];
        const icon = icons[type] || icons.info;

        // Construire le contenu
        const iconEl = document.createElement('div');
        iconEl.className = 'toast-icon';
        iconEl.textContent = icon;

        const contentEl = document.createElement('div');
        contentEl.className = 'toast-content';

        const titleEl = document.createElement('div');
        titleEl.className = 'toast-title';
        titleEl.textContent = finalTitle;

        const messageEl = document.createElement('div');
        messageEl.className = 'toast-message';
        messageEl.textContent = message;

        contentEl.appendChild(titleEl);
        contentEl.appendChild(messageEl);

        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.innerHTML = '×';
        closeBtn.setAttribute('aria-label', 'Fermer');
        closeBtn.addEventListener('click', () => {
            this.hide(toast);
        });

        toast.appendChild(iconEl);
        toast.appendChild(contentEl);
        toast.appendChild(closeBtn);

        // Barre de progression si durée définie
        if (duration > 0) {
            const progressBar = document.createElement('div');
            progressBar.className = 'toast-progress';
            progressBar.style.width = '100%';
            toast.appendChild(progressBar);

            // Animer la barre
            setTimeout(() => {
                progressBar.style.width = '0%';
                progressBar.style.transition = `width ${duration}ms linear`;
            }, 10);
        }

        // Ajouter au conteneur
        this.container.appendChild(toast);

        // Animation d'entrée
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        // Auto-fermeture
        if (duration > 0) {
            setTimeout(() => {
                this.hide(toast);
            }, duration);
        }

        return toast;
    },

    /**
     * Masquer un toast
     */
    hide(toast) {
        if (!toast || !toast.parentElement) return;

        toast.classList.remove('show');
        toast.classList.add('hide');

        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    },

    /**
     * Méthodes raccourcies
     */
    success(message, duration = 5000, title = null) {
        return this.show(message, 'success', duration, title);
    },

    error(message, duration = 7000, title = null) {
        return this.show(message, 'error', duration, title);
    },

    warning(message, duration = 6000, title = null) {
        return this.show(message, 'warning', duration, title);
    },

    info(message, duration = 5000, title = null) {
        return this.show(message, 'info', duration, title);
    },

    /**
     * Toast de confirmation avec boutons
     * Remplace confirm()
     */
    confirm(message, title = 'Confirmation') {
        return new Promise((resolve) => {
            this.init();

            const toast = document.createElement('div');
            toast.className = 'toast toast-warning';

            const iconEl = document.createElement('div');
            iconEl.className = 'toast-icon';
            iconEl.textContent = '?';

            const contentEl = document.createElement('div');
            contentEl.className = 'toast-content';

            const titleEl = document.createElement('div');
            titleEl.className = 'toast-title';
            titleEl.textContent = title;

            const messageEl = document.createElement('div');
            messageEl.className = 'toast-message';
            messageEl.textContent = message;

            const buttonsEl = document.createElement('div');
            buttonsEl.style.cssText = 'display: flex; gap: 8px; margin-top: 12px;';

            const confirmBtn = document.createElement('button');
            confirmBtn.textContent = 'Confirmer';
            confirmBtn.className = 'btn btn-primary btn-sm';
            confirmBtn.style.cssText = 'padding: 6px 16px; font-size: 13px; border-radius: 4px; border: none; cursor: pointer; background: #10b981; color: white;';

            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Annuler';
            cancelBtn.className = 'btn btn-secondary btn-sm';
            cancelBtn.style.cssText = 'padding: 6px 16px; font-size: 13px; border-radius: 4px; border: none; cursor: pointer; background: #6b7280; color: white;';

            confirmBtn.addEventListener('click', () => {
                this.hide(toast);
                resolve(true);
            });

            cancelBtn.addEventListener('click', () => {
                this.hide(toast);
                resolve(false);
            });

            buttonsEl.appendChild(confirmBtn);
            buttonsEl.appendChild(cancelBtn);

            contentEl.appendChild(titleEl);
            contentEl.appendChild(messageEl);
            contentEl.appendChild(buttonsEl);

            toast.appendChild(iconEl);
            toast.appendChild(contentEl);

            this.container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
        });
    }
};

// Rendre Toast disponible globalement
window.Toast = Toast;
