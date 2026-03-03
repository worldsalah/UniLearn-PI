# Dynamic Progress Bars Usage Guide

## Available Progress Bar Types

### 1. Linear Progress Bar
```twig
{% from 'templates/components/progress_bars.html.twig' import dynamicProgressBar %}

{{ dynamicProgressBar(
    current,           // Current value
    total,             // Maximum value
    'primary',          // Bootstrap color (primary, success, warning, danger, info, etc.)
    true,               // Animated
    true,               // Show percentage
    true,               // Show labels
    '25px'              // Height
) }}
```

### 2. Circular Progress Bar
```twig
{% from 'templates/components/progress_bars.html.twig' import circularProgressBar %}

{{ circularProgressBar(
    percentage,         // Percentage (0-100)
    'success',          // Bootstrap color
    'md',               // Size (sm, md, lg, xl)
    true,               // Animated
    true                // Show text
) }}
```

### 3. Step Progress Bar
```twig
{% from 'templates/components/progress_bars.html.twig' import stepProgressBar %}

{% set steps = [
    {'title': 'Step 1', 'completed': true},
    {'title': 'Step 2', 'completed': false, 'active': true},
    {'title': 'Step 3', 'completed': false}
] %}

{{ stepProgressBar(steps, 2, 'primary', false) }}
```

### 4. Skill Bar
```twig
{% from 'templates/components/progress_bars.html.twig' import skillBar %}

{{ skillBar(
    'JavaScript',     // Skill name
    4,               // Current level
    5,                // Maximum level
    'primary'           // Color
) }}
```

### 5. Loading Bar
```twig
{% from 'templates/components/progress_bars.html.twig' import loadingBar %}

{{ loadingBar('primary', '4px') }}
```

## Integration Examples

### In Course Dashboard
```twig
<div class="course-progress">
    <h6>Course Completion</h6>
    {% from 'templates/components/progress_bars.html.twig' import dynamicProgressBar %}
    
    {{ dynamicProgressBar(
        completed_lessons, 
        total_lessons, 
        'success', 
        true, 
        true, 
        true, 
        '20px'
    )}}
</div>
```

### In User Profile
```twig
<div class="xp-progress">
    <h6>XP Progress to Next Level</h6>
    {% from 'templates/components/progress_bars.html.twig' import circularProgressBar %}
    
    {{ circularProgressBar(
        progress.progress, 
        'primary', 
        'lg', 
        true, 
        true
    )}}
</div>
```

### In Quiz Results
```twig
<div class="quiz-score">
    <h6>Quiz Score</h6>
    {% from 'templates/components/progress_bars.html.twig' import skillBar %}
    
    {{ skillBar(
        'Quiz Average', 
        quiz_score, 
        100, 
        'info'
    )}}
</div>
```

## Real-time Updates

### JavaScript Integration
```javascript
// Update progress bar dynamically
function updateProgressBar(elementId, current, total) {
    const progressBar = document.getElementById(elementId);
    const percentage = (current / total) * 100;
    
    progressBar.style.width = percentage + '%';
    progressBar.setAttribute('aria-valuenow', Math.round(percentage));
    
    // Update text if present
    const textElement = progressBar.querySelector('.progress-bar-text');
    if (textElement) {
        textElement.textContent = Math.round(percentage) + '%';
    }
}

// Animate value changes
function animateValue(start, end, element) {
    const duration = 1000;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(start + (end - start) * progress);
        
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(updateTime);
        }
    }
    
    requestAnimationFrame(updateTime);
}
```

### API Integration
```javascript
// Fetch real-time progress data
function fetchProgressData() {
    fetch('/progress/api/real-time')
        .then(response => response.json())
        .then(data => {
            updateProgressBar('main-progress', data.current_points, data.current_level.getMaxXp());
        })
        .catch(error => {
            console.error('Error fetching progress:', error);
        });
}

// Auto-refresh every 5 seconds
setInterval(fetchProgressData, 5000);
```

## Customization Options

### Colors
- `primary` (blue)
- `success` (green)
- `warning` (yellow)
- `danger` (red)
- `info` (cyan)
- `secondary` (gray)
- `dark` (dark)
- `light` (light)

### Sizes
- **Linear:** Height in pixels (e.g., '4px', '8px', '25px', '30px')
- **Circular:** sm (60px), md (80px), lg (120px), xl (160px)

### Animation Options
- `animated: true` - Smooth transitions
- `showPercentage: true` - Show percentage text
- `showLabels: true` - Show current/total labels
- `striped: true` - Striped animation
- `clickable: true` - Make steps clickable

## CSS Classes Available

### Animation Classes
- `.animate-pulse` - Pulsing effect
- `.animate__animated` - Animation enabled
- `.progress-bar-animated` - Striped animation
- `.progress-bar-striped` - Striped pattern

### Hover Effects
- Progress bars scale slightly on hover
- Buttons have lift effect on hover
- Smooth transitions for all changes

## Best Practices

1. **Use semantic HTML** - Always include proper ARIA attributes
2. **Provide context** - Always show labels and percentages
3. **Consider accessibility** - Use high contrast colors
4. **Mobile responsive** - Test on different screen sizes
5. **Performance** - Don't over-animate on slow connections

## Demo Page

Visit `/progress/demo` to see all progress bar types in action with:
- Real-time XP updates
- Interactive controls
- Auto-updating demonstrations
- Smooth animations
- Mobile-responsive design
