// Gamification JavaScript Module
// ===========================

// Import CSS
import '../css/gamification.css';

class GamificationManager {
    constructor() {
        this.init();
    }

    init() {
        this.initializeEventListeners();
        this.initializeTooltips();
        this.initializeAnimations();
    }

    // Initialisation des écouteurs d'événements
    initializeEventListeners() {
        // Écouter les événements de gamification
        const self = this;
        
        document.addEventListener('gamification:pointsEarned', function(event) {
            self.handlePointsEarned(event);
        });
        
        document.addEventListener('gamification:badgeUnlocked', function(event) {
            self.handleBadgeUnlocked(event);
        });
        
        document.addEventListener('gamification:levelUp', function(event) {
            self.handleLevelUp(event);
        });
        
        // Boutons d'ajout de points (pour les tests)
        document.querySelectorAll('[data-action="add-points"]').forEach(function(button) {
            button.addEventListener('click', function(event) {
                self.handleAddPoints(event);
            });
        });
    }

    // Initialisation des tooltips
    initializeTooltips() {
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    // Initialisation des animations
    initializeAnimations() {
        // Animation des badges au chargement
        this.animateBadgesOnLoad();
        
        // Animation de la barre de progression
        this.animateProgressBars();
    }

    // Gestion de l'ajout de points
    async handleAddPoints(event) {
        const button = event.currentTarget;
        const points = parseInt(button.dataset.points) || 10;
        const reason = button.dataset.reason || 'Action completed';
        
        try {
            const response = await fetch('/gamification/api/add-points', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    points: points,
                    reason: reason
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showPointsAnimation(points, button);
                this.updateGamificationStats(data.new_stats);
                
                // Déclencher l'événement personnalisé
                document.dispatchEvent(new CustomEvent('gamification:pointsEarned', {
                    detail: { points: points, reason: reason, newStats: data.new_stats }
                }));
            } else {
                this.showError(data.error || 'Erreur lors de l\'ajout de points');
            }
        } catch (error) {
            this.showError('Erreur réseau lors de l\'ajout de points');
        }
    }

    // Animation des points gagnés
    showPointsAnimation(points, element) {
        const pointsElement = document.createElement('div');
        pointsElement.className = 'points-earned';
        pointsElement.textContent = '+' + points + ' XP';
        
        const rect = element.getBoundingClientRect();
        pointsElement.style.left = rect.left + rect.width / 2 - 50 + 'px';
        pointsElement.style.top = rect.top + 'px';
        
        document.body.appendChild(pointsElement);
        
        // Supprimer l'élément après l'animation
        setTimeout(function() {
            pointsElement.remove();
        }, 2000);
    }

    // Notification de badge débloqué
    handleBadgeUnlocked(event) {
        const badge = event.detail.badge;
        const reason = event.detail.reason;
        this.showBadgeNotification(badge, reason);
    }

    // Notification de niveau supérieur
    handleLevelUp(event) {
        const newLevel = event.detail.newLevel;
        const previousLevel = event.detail.previousLevel;
        this.showLevelUpNotification(newLevel, previousLevel);
    }

    // Afficher une notification de badge
    showBadgeNotification(badge, reason) {
        const notification = document.createElement('div');
        notification.className = 'badge-notification';
        notification.innerHTML = 
            '<div class="d-flex align-items-center">' +
                '<div class="badge-icon-wrapper me-3">' +
                    '<div class="badge-icon badge-unlock" style="color: ' + (badge.color || '#6366f1') + ';">' +
                        '<i class="' + (badge.icon || 'fas fa-award') + ' fa-2x"></i>' +
                    '</div>' +
                '</div>' +
                '<div class="flex-grow-1">' +
                    '<h6 class="mb-1">Nouveau Badge Débloqué !</h6>' +
                    '<div class="fw-bold">' + (badge.name || 'Badge') + '</div>' +
                    '<small class="text-muted">' + (reason || 'Félicitations !') + '</small>' +
                '</div>' +
                '<button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>' +
            '</div>';
        
        document.body.appendChild(notification);
        
        // Supprimer après 3 secondes
        setTimeout(function() {
            notification.remove();
        }, 3000);
    }

    // Afficher une notification de niveau supérieur
    showLevelUpNotification(newLevel, previousLevel) {
        const notification = document.createElement('div');
        notification.className = 'badge-notification';
        notification.innerHTML = 
            '<div class="d-flex align-items-center">' +
                '<div class="me-3">' +
                    '<i class="fas fa-level-up-alt fa-2x text-success"></i>' +
                '</div>' +
                '<div class="flex-grow-1">' +
                    '<h6 class="mb-1">Niveau Supérieur !</h6>' +
                    '<div class="fw-bold">' + previousLevel + ' → ' + newLevel + '</div>' +
                    '<small class="text-muted">Continuez comme ça !</small>' +
                '</div>' +
                '<button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>' +
            '</div>';
        
        document.body.appendChild(notification);
        
        // Supprimer après 3 secondes
        setTimeout(function() {
            notification.remove();
        }, 3000);
    }

    // Mettre à jour les statistiques de gamification
    updateGamificationStats(stats) {
        // Mettre à jour les points
        const pointsElements = document.querySelectorAll('[data-gamification="points"]');
        pointsElements.forEach(function(element) {
            element.textContent = stats.total_points;
        });
        
        // Mettre à jour le niveau
        const levelElements = document.querySelectorAll('[data-gamification="level"]');
        levelElements.forEach(function(element) {
            element.textContent = stats.current_level.name;
        });
        
        // Mettre à jour le rang
        const rankElements = document.querySelectorAll('[data-gamification="rank"]');
        rankElements.forEach(function(element) {
            element.textContent = '#' + stats.rank;
        });
        
        // Mettre à jour la barre de progression
        this.updateProgressBar(stats.progress);
    }

    // Mettre à jour la barre de progression
    updateProgressBar(progress) {
        const progressBars = document.querySelectorAll('[data-gamification="progress"]');
        progressBars.forEach(function(bar) {
            const progressBar = bar.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = progress.progress + '%';
                progressBar.setAttribute('aria-valuenow', progress.progress);
            }
        });
    }

    // Animation des badges au chargement
    animateBadgesOnLoad() {
        const badges = document.querySelectorAll('.badge-display');
        badges.forEach(function(badge, index) {
            setTimeout(function() {
                badge.style.animation = 'fadeInUp 0.5s ease-out';
            }, index * 100);
        });
    }

    // Animation des barres de progression
    animateProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(function(bar) {
            const targetWidth = bar.style.width;
            bar.style.width = '0%';
            
            setTimeout(function() {
                bar.style.transition = 'width 1s ease-out';
                bar.style.width = targetWidth;
            }, 100);
        });
    }

    // Afficher une erreur
    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
        alert.innerHTML = 
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        
        document.body.appendChild(alert);
        
        setTimeout(function() {
            alert.remove();
        }, 5000);
    }

    // Rafraîchir le leaderboard
    async refreshLeaderboard(period) {
        period = period || 'all';
        
        try {
            const response = await fetch('/gamification/api/leaderboard?period=' + period);
            const data = await response.json();
            
            this.updateLeaderboardTable(data);
        } catch (error) {
            this.showError('Erreur lors du chargement du leaderboard');
        }
    }

    // Mettre à jour le tableau du leaderboard
    updateLeaderboardTable(data) {
        const tbody = document.querySelector('#leaderboardTable tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        const self = this;
        data.forEach(function(entry, index) {
            const row = document.createElement('tr');
            if (entry.isCurrentUser) {
                row.className = 'table-primary';
            }
            
            row.innerHTML = 
                '<td>' +
                    '<div class="text-center">' +
                        self.getRankDisplay(index + 1, entry.rank) +
                    '</div>' +
                '</td>' +
                '<td>' +
                    '<div class="d-flex align-items-center">' +
                        self.getUserDisplay(entry.user) +
                        '<div>' +
                            '<div class="fw-semibold">' + entry.user.name + '</div>' +
                            (entry.isCurrentUser ? '<span class="badge bg-primary ms-2">Vous</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '<td>' +
                    self.getLevelDisplay(entry.level) +
                '</td>' +
                '<td>' +
                    '<div class="fw-bold text-primary">' + entry.total_points + '</div>' +
                    '<small class="text-muted">XP</small>' +
                '</td>' +
                '<td>' +
                    '<div class="progress" style="height: 8px; width: 100px;">' +
                        '<div class="progress-bar" style="width: ' + entry.progress + '%; background-color: ' + (entry.level?.color || '#6366f1') + ';"></div>' +
                    '</div>' +
                    '<small class="text-muted">' + Math.round(entry.progress) + '%</small>' +
                '</td>' +
                '<td>' +
                    '<span class="badge bg-warning text-dark">' +
                        '<i class="fas fa-trophy me-1"></i>' +
                        entry.badges_count +
                    '</span>' +
                '</td>';
            
            tbody.appendChild(row);
        });
    }

    // Affichage du rang
    getRankDisplay(position, rank) {
        if (position === 1) {
            return '<i class="fas fa-trophy text-warning"></i> #' + rank;
        } else if (position === 2) {
            return '<i class="fas fa-medal text-secondary"></i> #' + rank;
        } else if (position === 3) {
            return '<i class="fas fa-medal" style="color: #cd7f32;"></i> #' + rank;
        } else {
            return '#' + rank;
        }
    }

    // Affichage de l'utilisateur
    getUserDisplay(user) {
        if (user.profile_image) {
            return '<img src="' + user.profile_image + '" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;" alt="' + user.name + '">';
        } else {
            return '<div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">' +
                '<span class="text-white fw-bold">' + user.name.charAt(0).toUpperCase() + '</span>' +
            '</div>';
        }
    }

    // Affichage du niveau
    getLevelDisplay(level) {
        if (level) {
            return '<span class="badge rounded-pill" style="background-color: ' + level.color + '; color: white;">' +
                (level.icon ? '<i class="' + level.icon + ' me-1"></i>' : '') +
                level.name +
            '</span>';
        } else {
            return '<span class="badge bg-secondary">Beginner</span>';
        }
    }
}

// Initialisation du module
document.addEventListener('DOMContentLoaded', function() {
    window.gamificationManager = new GamificationManager();
});

// Export pour utilisation globale
window.GamificationManager = GamificationManager;
