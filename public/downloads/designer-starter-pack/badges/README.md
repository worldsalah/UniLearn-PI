# MARKETPLACE BADGE COMPONENTS
## Designer Starter Pack - Badge Library

### OVERVIEW
Comprehensive badge system for the UniLearn marketplace. Badges provide visual context, status indicators, and promotional elements for products and services.

---

## BADGE TYPES

### 1. STATUS BADGES
**Use**: Product status, availability, condition

#### AVAILABLE
- **Background**: #10B981
- **Text**: #FFFFFF
- **Text**: "Available"
- **Size**: 24px height, 12px font

#### SOLD OUT
- **Background**: #EF4444
- **Text**: #FFFFFF
- **Text**: "Sold Out"
- **Size**: 24px height, 12px font

#### LIMITED
- **Background**: #F59E0B
- **Text**: #FFFFFF
- **Text**: "Limited"
- **Size**: 24px height, 12px font

#### COMING SOON
- **Background**: #6B7280
- **Text**: #FFFFFF
- **Text**: "Coming Soon"
- **Size**: 24px height, 12px font

### 2. PROMOTIONAL BADGES
**Use**: Special offers, discounts, promotions

#### HOT DEAL
- **Background**: Linear gradient(45deg, #EF4444, #DC2626)
- **Text**: #FFFFFF
- **Icon**: 🔥
- **Text**: "Hot Deal"
- **Animation**: Subtle pulse effect

#### DISCOUNT
- **Background**: #8B5CF6
- **Text**: #FFFFFF
- **Text**: "[X]% OFF"
- **Dynamic**: Percentage changes

#### NEW
- **Background**: #06B6D4
- **Text**: #FFFFFF
- **Text**: "NEW"
- **Animation**: Fade in effect

#### FEATURED
- **Background**: Linear gradient(45deg, #8B5CF6, #EC4899)
- **Text**: #FFFFFF
- **Icon**: ⭐
- **Text**: "Featured"

### 3. QUALITY BADGES
**Use**: Ratings, certifications, achievements

#### TOP RATED
- **Background**: #F59E0B
- **Text**: #FFFFFF
- **Icon**: ⭐
- **Text**: "Top Rated"

#### VERIFIED
- **Background**: #10B981
- **Text**: #FFFFFF
- **Icon**: ✓
- **Text**: "Verified"

#### PREMIUM
- **Background**: Linear gradient(45deg, #1F2937, #374151)
- **Text**: #FFFFFF
- **Text**: "Premium"

#### BESTSELLER
- **Background**: #EF4444
- **Text**: #FFFFFF
- **Icon**: 🏆
- **Text": "Bestseller"

### 4. CATEGORY BADGES
**Use**: Service categories, skill tags

#### WEB DEVELOPMENT
- **Background**: #3B82F6
- **Text**: #FFFFFF
- **Text**: "Web Dev"

#### DESIGN
- **Background**: #8B5CF6
- **Text**: #FFFFFF
- **Text**: "Design"

#### MARKETING
- **Background**: #EC4899
- **Text**: #FFFFFF
- **Text**: "Marketing"

#### WRITING
- **Background**: #10B981
- **Text**: #FFFFFF
- **Text**: "Writing"

#### CONSULTING
- **Background**: #F59E0B
- **Text**: #FFFFFF
- **Text**: "Consulting"

---

## BADGE SIZES

### SIZE SYSTEM
```
XS: 20px height, 10px font, 4px padding
SM: 24px height, 12px font, 6px padding
MD: 32px height, 14px font, 8px padding
LG: 40px height, 16px font, 12px padding
XL: 48px height, 18px font, 16px padding
```

### USAGE GUIDELINES
- **XS**: Table cells, compact lists
- **SM**: Product cards, tags
- **MD**: Section headers, featured items
- **LG**: Hero sections, announcements
- **XL**: Promotional banners, alerts

---

## BADGE STYLES

### SOLID BADGES
- **Background**: Full color
- **Text**: White or contrasting color
- **Border**: None
- **Use**: Primary status indicators

### OUTLINE BADGES
- **Background**: Transparent
- **Border**: 2px solid color
- **Text**: Same as border color
- **Use**: Secondary categories, tags

### GHOST BADGES
- **Background**: 10% opacity of color
- **Text**: Full color
- **Border**: None
- **Use**: Subtle indicators, filters

### PILL BADGES
- **Border Radius**: 20px (fully rounded)
- **Padding**: 8px 16px
- **Use**: Modern, friendly appearance

---

## SPECIAL BADGES

### 1. TRENDING BADGE
**Use**: Popular items, trending content

#### Specifications
- **Background**: Linear gradient(45deg, #EF4444, #F59E0B)
- **Text**: #FFFFFF
- **Icon**: 🔥
- **Text**: "Trending"
- **Animation**: Pulse effect, 2s duration
- **Shadow**: 0 4px 12px rgba(239, 68, 68, 0.3)

#### Animation Code
```css
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.trending-badge {
  animation: pulse 2s infinite;
}
```

### 2. SCORE BADGE
**Use**: Ratings, scores, metrics

#### Specifications
- **Background**: Black with 80% opacity
- **Text**: #FFFFFF
- **Text**: "Score: [X.X]"
- **Position**: Overlay on images
- **Border Radius**: 8px

### 3. COUNT BADGE
**Use**: Notifications, quantities, counts

#### Specifications
- **Background**: #EF4444
- **Text**: #FFFFFF
- **Shape**: Circle
- **Size**: Auto based on content
- **Minimum**: 20px × 20px

#### Variations
- **Notification**: Red background
- **Cart**: Blue background
- **Message**: Green background

---

## BADGE COMBINATIONS

### BADGE GROUPS
#### Horizontal Group
```
[Badge] [Badge] [Badge]
Spacing: 4px between badges
Alignment: Left or center
```

#### Vertical Stack
```
[Badge]
[Badge]
[Badge]
Spacing: 2px between badges
```

#### Badge Cloud
```
[Badge]   [Badge]
[Badge] [Badge] [Badge]
  [Badge]   [Badge]
Irregular layout, organic feel
```

### BADGE HIERARCHY
1. **Primary**: Most important information
2. **Secondary**: Supporting information
3. **Tertiary**: Additional details

#### Visual Hierarchy
- **Size**: Larger for important badges
- **Color**: Brighter for primary badges
- **Position**: Top-left for primary information

---

## COLOR SYSTEM

### PRIMARY COLORS
```css
--badge-blue: #3B82F6;
--badge-purple: #8B5CF6;
--badge-pink: #EC4899;
--badge-green: #10B981;
--badge-yellow: #F59E0B;
--badge-red: #EF4444;
--badge-orange: #F97316;
--badge-cyan: #06B6D4;
```

### NEUTRAL COLORS
```css
--badge-gray-50: #F9FAFB;
--badge-gray-100: #F3F4F6;
--badge-gray-200: #E5E7EB;
--badge-gray-300: #D1D5DB;
--badge-gray-400: #9CA3AF;
--badge-gray-500: #6B7280;
--badge-gray-600: #4B5563;
--badge-gray-700: #374151;
--badge-gray-800: #1F2937;
--badge-gray-900: #111827;
```

### SEMANTIC COLORS
```css
--badge-success: #10B981;
--badge-warning: #F59E0B;
--badge-error: #EF4444;
--badge-info: #06B6D4;
```

---

## TYPOGRAPHY

### FONT SYSTEM
- **Family**: Inter, system-ui
- **Weight**: 600 (SemiBold)
- **Transform**: Uppercase for emphasis
- **Letter Spacing**: 0.5px for readability

### FONT SIZES
```css
--badge-text-xs: 10px;
--badge-text-sm: 12px;
--badge-text-md: 14px;
--badge-text-lg: 16px;
--badge-text-xl: 18px;
```

---

## ICON INTEGRATION

### ICON BADGES
#### Left Icon
```
[Icon] Text
Icon Size: 12px, 14px, 16px
Spacing: 4px between icon and text
```

#### Right Icon
```
Text [Icon]
Icon Size: 12px, 14px, 16px
Spacing: 4px between text and icon
```

#### Icon Only
```
[Icon]
Square badge
Icon centered
```

### COMMON ICONS
- ⭐ Star (Rating, Featured)
- 🔥 Fire (Trending, Hot)
- ✓ Check (Verified, Available)
- 🏆 Trophy (Bestseller, Winner)
- 💎 Diamond (Premium, Luxury)
- ⚡ Lightning (Fast, Quick)
- 🎯 Target (Popular, Recommended)
- 📈 Trend (Growing, Improving)

---

## ANIMATIONS

### ENTRANCE ANIMATIONS
```css
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}

@keyframes slideIn {
  from { transform: translateX(-10px); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

@keyframes bounce {
  0%, 20%, 53%, 80%, 100% { transform: translateY(0); }
  40%, 43% { transform: translateY(-10px); }
  70% { transform: translateY(-5px); }
  90% { transform: translateY(-2px); }
}
```

### HOVER EFFECTS
```css
.badge:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease-out;
}
```

### LOADING STATES
```css
@keyframes shimmer {
  0% { background-position: -200px 0; }
  100% { background-position: calc(200px + 100%) 0; }
}

.badge-loading {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200px 100%;
  animation: shimmer 1.5s infinite;
}
```

---

## RESPONSIVE BEHAVIOR

### MOBILE ADAPTATION
- **Minimum Size**: 20px height for touch
- **Spacing**: Increased by 25% on mobile
- **Font Size**: Minimum 10px for readability
- **Touch Targets**: 44px minimum interactive area

### BREAKPOINTS
```css
/* Mobile */
@media (max-width: 768px) {
  .badge {
    font-size: var(--badge-text-xs);
    padding: 4px 8px;
  }
}

/* Tablet */
@media (min-width: 769px) and (max-width: 1024px) {
  .badge {
    font-size: var(--badge-text-sm);
    padding: 6px 12px;
  }
}

/* Desktop */
@media (min-width: 1025px) {
  .badge {
    font-size: var(--badge-text-md);
    padding: 8px 16px;
  }
}
```

---

## ACCESSIBILITY

### COLOR CONTRAST
- **Normal Text**: 4.5:1 minimum contrast ratio
- **Large Text**: 3:1 minimum contrast ratio
- **Icons**: Same contrast as text

### FOCUS STATES
- **Outline**: 2px solid current color
- **Offset**: 1px
- **Border Radius**: Same as badge

### SCREEN READERS
- **ARIA Labels**: Descriptive text for icons
- **Role**: badge="status"
- **Live Regions**: For dynamic badges

---

## FIGMA COMPONENTS

### COMPONENT STRUCTURE
```
📁 Badge Library
  📁 Status Badges
    📄 Available
    📄 Sold Out
    📄 Limited
    📄 Coming Soon
  📁 Promotional Badges
    📄 Hot Deal
    📄 Discount
    📄 New
    📄 Featured
  📁 Quality Badges
    📄 Top Rated
    📄 Verified
    📄 Premium
    📄 Bestseller
  📁 Category Badges
    📄 Web Development
    📄 Design
    📄 Marketing
    📄 Writing
    📄 Consulting
```

### VARIANT PROPERTIES
- **Type**: Status, Promotional, Quality, Category
- **Size**: XS, SM, MD, LG, XL
- **Style**: Solid, Outline, Ghost, Pill
- **Icon**: Left, Right, None
- **State**: Default, Hover, Active, Disabled

### AUTO LAYOUT
- **Horizontal**: Badge content
- **Padding**: Responsive to size
- **Constraints**: Respect content
- **Spacing**: Consistent across variants

---

## IMPLEMENTATION CODE

### CSS CUSTOM PROPERTIES
```css
:root {
  --badge-height-sm: 24px;
  --badge-padding-sm: 6px 12px;
  --badge-border-radius: 12px;
  --badge-font-weight: 600;
  --badge-letter-spacing: 0.5px;
  
  --badge-success-bg: #10B981;
  --badge-success-text: #FFFFFF;
  --badge-warning-bg: #F59E0B;
  --badge-warning-text: #FFFFFF;
  --badge-error-bg: #EF4444;
  --badge-error-text: #FFFFFF;
}
```

### BASE CLASSES
```css
.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--badge-border-radius);
  font-weight: var(--badge-font-weight);
  letter-spacing: var(--badge-letter-spacing);
  text-transform: uppercase;
  white-space: nowrap;
  font-size: var(--badge-text-sm);
  line-height: 1;
  transition: all 0.2s ease-out;
}

.badge:focus {
  outline: 2px solid currentColor;
  outline-offset: 1px;
}
```

### MODIFIER CLASSES
```css
.badge--success {
  background-color: var(--badge-success-bg);
  color: var(--badge-success-text);
}

.badge--warning {
  background-color: var(--badge-warning-bg);
  color: var(--badge-warning-text);
}

.badge--error {
  background-color: var(--badge-error-bg);
  color: var(--badge-error-text);
}

.badge--sm {
  height: var(--badge-height-sm);
  padding: var(--badge-padding-sm);
  font-size: var(--badge-text-xs);
}

.badge--pill {
  border-radius: 20px;
}
```

---

## USAGE EXAMPLES

### BASIC USAGE
```html
<span class="badge badge--success badge--sm">
  Available
</span>

<span class="badge badge--warning badge--pill">
  Limited
</span>
```

### WITH ICONS
```html
<span class="badge badge--error badge--sm">
  🔥 Hot Deal
</span>

<span class="badge badge--success badge--sm">
  ✓ Verified
</span>
```

### BADGE GROUPS
```html
<div class="badge-group">
  <span class="badge badge--primary">New</span>
  <span class="badge badge--success">Available</span>
  <span class="badge badge--warning">Limited</span>
</div>
```

---

## TESTING CHECKLIST

### VISUAL TESTING
- [ ] All sizes render correctly
- [ ] Colors match specifications
- [ ] Icons properly aligned
- [ ] Text readable at all sizes

### INTERACTION TESTING
- [ ] Hover states work appropriately
- [ ] Focus states are visible
- [ ] Animations are smooth
- [ ] Loading states display correctly

### ACCESSIBILITY TESTING
- [ ] Color contrast meets WCAG
- [ ] Screen reader announcements work
- [ ] Keyboard navigation functions
- [ ] Touch targets adequate

### RESPONSIVE TESTING
- [ ] Mobile badges are touchable
- [ ] Tablet layouts adapt
- [ ] Desktop badges display properly
- [ ] Text scales appropriately

---

## BEST PRACTICES

### DO's
✅ Use clear, concise text
✅ Maintain visual hierarchy
✅ Ensure adequate contrast
✅ Test across devices
✅ Use appropriate colors for meaning
✅ Keep badge count reasonable

### DON'Ts
❌ Overuse badges on one item
❌ Use similar colors for different meanings
❌ Make text too small to read
❌ Ignore accessibility
❌ Use too many animations
❌ Forget mobile touch targets

---

## NEXT STEPS

1. **Create Figma components** for all badge types
2. **Build CSS framework** with utility classes
3. **Implement design tokens** for consistency
4. **Create usage guidelines** for team
5. **Test across browsers** and devices
6. **Establish badge governance** for consistency

Remember: **Badges provide quick context** - keep them simple and clear! 🏷️
