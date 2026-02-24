# Système de Gamification UniLearn

## 🎮 Vue d'ensemble

Le système de gamification de UniLearn permet d'engager les étudiants à travers des points XP, des badges, des niveaux et un classement compétitif.

## 📋 Fonctionnalités

### ✨ Points XP
- Les étudiants gagnent des points XP en effectuant diverses actions
- Progression automatique des niveaux basée sur les XP accumulés
- Suivi en temps réel de la progression vers le niveau suivant

### 🏆 Badges
- Système de badges thématiques (Achievements, Learning, Participation, Milestones)
- Badges automatiques basés sur les seuils de points
- Badges spéciaux pour les accomplissements uniques
- Interface de collection de badges avec animations

### 📊 Niveaux
- 7 niveaux progressifs : Beginner → Novice → Intermediate → Advanced → Expert → Master → Legend
- Chaque niveau a sa propre couleur et icône
- Progression visuelle avec barres de progression animées

### 🏅 Classement (Leaderboard)
- Classement global en temps réel
- Filtres par période (semaine, mois, tous les temps)
- Filtres par niveau
- Position personnelle mise en évidence

## 🚀 Installation et Configuration

### 1. Base de données
Les tables sont déjà créées via les migrations :
- `user_level` : Niveaux disponibles
- `badge` : Badges disponibles
- `user_badge` : Badges obtenus par les utilisateurs
- `user_points` : Points et progression des utilisateurs

### 2. Données initiales
Exécutez les fixtures pour charger les niveaux et badges par défaut :
```bash
php bin/console doctrine:fixtures:load --append
```

### 3. Intégration dans les templates
Ajoutez les fichiers CSS et JavaScript :
```html
<link rel="stylesheet" href="{{ asset('assets/css/gamification.css') }}">
<script src="{{ asset('assets/js/gamification.js') }}"></script>
```

## 🎯 Utilisation

### Afficher le profil gamifié d'un utilisateur
```twig
<a href="{{ path('app_gamification_profile') }}" class="btn btn-primary">
    <i class="fas fa-trophy me-2"></i>Mon Profil Gamifié
</a>
```

### Widget de gamification dans un template
```twig
{% include 'components/gamification_widget.html.twig' with {
    'user_points': user.userPoints,
    'show_rank': true
} %}
```

### Afficher un badge individuel
```twig
{% include 'components/badge_display.html.twig' with {
    'badge': badge,
    'size': 'large',
    'interactive': true,
    'show_label': true
} %}
```

### Ajouter des points à un utilisateur
```php
// Dans un contrôleur ou un service
$gamificationService->addPoints($user, 50, 'Quiz complété');

// Via l'API JavaScript
fetch('/gamification/api/add-points', {
    method: 'POST',
    body: new URLSearchParams({
        points: 50,
        reason: 'Quiz complété'
    })
})
```

## 🔧 Service GamificationService

### Méthodes principales

```php
// Initialiser la gamification pour un utilisateur
$gamificationService->initializeUserGamification($user);

// Ajouter des points
$gamificationService->addPoints($user, 100, 'Cours terminé');

// Attribuer un badge manuellement
$gamificationService->awardBadge($user, $badge, 'Excellent travail !');

// Obtenir les statistiques complètes
$stats = $gamificationService->getGamificationStats($user);

// Obtenir le leaderboard
$leaderboard = $gamificationService->getLeaderboard(10);

// Obtenir le rang d'un utilisateur
$rank = $gamificationService->getUserRank($user);
```

## 🎨 Personnalisation

### Ajouter de nouveaux niveaux
```php
// Dans GamificationFixtures.php
['name' => 'Custom Level', 'code' => 'CUSTOM', 'minXp' => 5000, 'maxXp' => 7500, 'color' => '#ff6b6b', 'icon' => 'fas fa-star', 'order' => 8]
```

### Créer de nouveaux badges
```php
$badge = new Badge();
$badge->setName('Super Student');
$badge->setCode('SUPER_STUDENT');
$badge->setDescription('Obtenu pour une performance exceptionnelle');
$badge->setIcon('fas fa-star');
$badge->setColor('#ffd700');
$badge->setCategory('achievement');
$badge->setPointsRequired(2000);
```

### Personnaliser les couleurs
Modifiez les variables CSS dans `gamification.css` :
```css
:root {
    --level-beginner: #6366f1;
    --level-novice: #22c55e;
    /* ... autres couleurs */
}
```

## 📱 Routes disponibles

### Pages principales
- `/gamification/profile` - Profil gamifié de l'utilisateur
- `/gamification/leaderboard` - Classement des étudiants
- `/gamification/badges` - Collection de badges

### API endpoints
- `/gamification/api/stats` - Statistiques de l'utilisateur connecté
- `/gamification/api/add-points` - Ajouter des points (POST)
- `/gamification/api/leaderboard` - Données du leaderboard

## 🎯 Intégration suggérée

### Dans les cours
```php
// Quand un étudiant termine un cours
$gamificationService->addPoints($user, 100, 'Cours "' . $course->getTitle() . '" terminé');
```

### Dans les quizzes
```php
// Basé sur le score du quiz
$points = round($quizScore / 100 * 50); // Max 50 points par quiz
$gamificationService->addPoints($user, $points, 'Quiz complété avec ' . $quizScore . '%');
```

### Pour la participation
```php
// Connexion quotidienne
$gamificationService->addPoints($user, 5, 'Connexion quotidienne');

// Premier cours du jour
$gamificationService->addPoints($user, 10, 'Premier cours du jour');
```

## 🔍 Événements JavaScript

Le système déclenche des événements personnalisés :

```javascript
// Écouter les gains de points
document.addEventListener('gamification:pointsEarned', function(event) {
    console.log('Points gagnés:', event.detail.points);
});

// Écouter les déblocages de badges
document.addEventListener('gamification:badgeUnlocked', function(event) {
    console.log('Nouveau badge:', event.detail.badge.name);
});

// Écouter les changements de niveau
document.addEventListener('gamification:levelUp', function(event) {
    console.log('Niveau supérieur:', event.detail.newLevel);
});
```

## 🎨 Animations et effets

Le système inclut plusieurs animations :
- Apparition progressive des badges
- Barres de progression animées
- Notifications de gains de points
- Effets de survol sur les badges
- Animations de déblocage

## 📊 Statistiques disponibles

Pour chaque utilisateur :
- Total des points XP
- Niveau actuel
- Progression vers le niveau suivant
- Rang dans le classement
- Nombre de badges obtenus
- Badges récents

## 🚀 Performance

- Les requêtes sont optimisées avec des jointures
- Le leaderboard est mis en cache
- Les animations utilisent CSS pour de meilleures performances
- Les API retournent uniquement les données nécessaires

## 🔮 Évolutions futures

- Badges personnalisés par les instructeurs
- Défis entre étudiants
- Système de récompenses matérielles
- Intégration avec les réseaux sociaux
- Analytics de gamification

---

## 📞 Support

Pour toute question ou problème concernant le système de gamification, consultez la documentation technique ou contactez l'équipe de développement.
