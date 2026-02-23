# PRODUCT CARD TEMPLATES FOR FIGMA
## Marketplace Designer Starter Pack

### OVERVIEW
This document contains specifications for creating product card templates in Figma for the UniLearn marketplace. These templates ensure consistency across all marketplace listings.

### ACCESSING TEMPLATES
To use these templates:
1. Copy the specifications below
2. Create new frames in Figma with these dimensions
3. Follow the design guidelines
4. Export as PNG/SVG for development

---

## TEMPLATE 1: STANDARD PRODUCT CARD

### Dimensions
- **Width**: 320px
- **Height**: 400px
- **Border Radius**: 12px
- **Padding**: 16px

### Layout Structure
```
┌─────────────────────────────┐
│ Image Area (200px height)   │
├─────────────────────────────┤
│ Category Badge              │
│ Title (2 lines max)         │
│ Description (3 lines max)   │
│ Rating & Reviews           │
│ Price                      │
│ Freelancer Info            │
│ Action Buttons             │
└─────────────────────────────┘
```

### Components Specifications

#### Image Area
- **Size**: 288px × 200px
- **Border Radius**: 8px (top corners only)
- **Background**: #F8F9FA
- **Image Fit**: Cover
- **Overlay**: Gradient from bottom (0.6 opacity black)

#### Category Badge
- **Position**: Top left, 12px from edges
- **Background**: White with 90% opacity
- **Text**: #6B7280, 12px, Medium
- **Padding**: 6px 12px
- **Border Radius**: 20px

#### Title
- **Font**: Inter, 16px, SemiBold
- **Color**: #111827
- **Max Lines**: 2
- **Line Height**: 1.4

#### Description
- **Font**: Inter, 14px, Regular
- **Color**: #6B7280
- **Max Lines**: 3
- **Line Height**: 1.5

#### Rating Component
- **Stars**: 14px, #F59E0B
- **Rating Text**: 12px, #6B7280
- **Layout**: Stars + "(4.5)"

#### Price
- **Font**: Inter, 20px, Bold
- **Color**: #059669
- **Layout**: "$" + price

#### Freelancer Info
- **Avatar**: 32px × 32px, Circle
- **Name**: 14px, Medium, #374151
- **Title**: 12px, Regular, #6B7280

#### Action Buttons
- **Primary**: 100% width, 40px height
- **Secondary**: Icon-only, 32px × 32px

---

## TEMPLATE 2: TRENDING PRODUCT CARD

### Dimensions
- **Width**: 340px
- **Height**: 420px
- **Border Radius**: 16px
- **Special**: Trending bar indicator

### Additional Components

#### Trending Bar
- **Position**: Top, full width
- **Height**: 4px
- **Background**: Linear gradient (#EF4444 to #F59E0B)
- **Animation**: Pulse effect

#### Trend Badge
- **Position**: Top right
- **Background**: #EF4444
- **Text**: "🔥 Trending", White, 12px, Bold
- **Padding**: 4px 8px
- **Border Radius**: 12px

#### Trend Score
- **Position**: Bottom right
- **Background**: Black with 80% opacity
- **Text**: "Score: 85", White, 12px
- **Padding**: 4px 8px
- **Border Radius**: 8px

---

## TEMPLATE 3: FEATURED PRODUCT CARD

### Dimensions
- **Width**: 360px
- **Height**: 440px
- **Border Radius**: 20px
- **Special**: Premium styling

### Premium Elements

#### Featured Banner
- **Position**: Top left
- **Background**: Linear gradient (45deg, #8B5CF6 to #EC4899)
- **Text**: "⭐ Featured", White, 14px, Bold
- **Padding**: 8px 16px
- **Border Radius**: 0 0 12px 0

#### Glow Effect
- **Shadow**: 0 20px 40px rgba(139, 92, 246, 0.2)
- **Border**: 2px solid rgba(139, 92, 246, 0.2)

#### Enhanced Typography
- **Title**: 18px, Bold
- **Price**: 24px, Bold with gradient text

---

## TEMPLATE 4: MINIMAL PRODUCT CARD

### Dimensions
- **Width**: 280px
- **Height**: 360px
- **Border Radius**: 8px
- **Style**: Clean, minimal

### Minimal Features

#### Simplified Layout
- **No badges** except category
- **Reduced padding**: 12px
- **Clean typography**: More whitespace
- **Subtle shadows**: 0 4px 12px rgba(0,0,0,0.1)

#### Focus Elements
- **Large image**: 256px × 180px
- **Essential info only**: Title, price, freelancer
- **Single CTA**: "View Details"

---

## TEMPLATE 5: LIST VIEW PRODUCT CARD

### Dimensions
- **Width**: 100% (responsive)
- **Height**: 120px
- **Border Radius**: 8px
- **Layout**: Horizontal

### Horizontal Layout Structure
```
┌─────────────────────────────────────────────────────────┐
│ [Image] [Content Area]              [Price] [Actions] │
└─────────────────────────────────────────────────────────┘
```

### Component Breakdown

#### Image Area
- **Size**: 120px × 120px
- **Position**: Left side
- **Border Radius**: 8px

#### Content Area
- **Width**: Flexible
- **Padding**: 16px
- **Contains**: Title, description, rating, freelancer

#### Price Area
- **Width**: 100px
- **Text-align**: Right
- **Contains**: Price, badge

#### Actions Area
- **Width**: 80px
- **Contains**: Primary button

---

## COLOR SYSTEM

### Primary Colors
- **Primary Blue**: #3B82F6
- **Primary Purple**: #8B5CF6
- **Primary Pink**: #EC4899

### Semantic Colors
- **Success**: #10B981
- **Warning**: #F59E0B
- **Error**: #EF4444
- **Info**: #06B6D4

### Neutral Colors
- **White**: #FFFFFF
- **Gray 50**: #F9FAFB
- **Gray 100**: #F3F4F6
- **Gray 200**: #E5E7EB
- **Gray 300**: #D1D5DB
- **Gray 400**: #9CA3AF
- **Gray 500**: #6B7280
- **Gray 600**: #4B5563
- **Gray 700**: #374151
- **Gray 800**: #1F2937
- **Gray 900**: #111827

---

## TYPOGRAPHY SYSTEM

### Font Family
- **Primary**: Inter (available on Google Fonts)
- **Fallback**: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto

### Font Sizes
- **xs**: 12px
- **sm**: 14px
- **base**: 16px
- **lg**: 18px
- **xl**: 20px
- **2xl**: 24px
- **3xl**: 30px

### Font Weights
- **Regular**: 400
- **Medium**: 500
- **SemiBold**: 600
- **Bold**: 700

---

## INTERACTIVE STATES

### Hover States
- **Cards**: Transform translateY(-4px), shadow-lg
- **Buttons**: Background darken by 10%
- **Links**: Underline appear

### Focus States
- **Outline**: 2px solid #3B82F6
- **Offset**: 2px

### Active States
- **Buttons**: Transform scale(0.98)
- **Cards**: Shadow reduce

---

## RESPONSIVE BREAKPOINTS

### Mobile (320px - 768px)
- **Card Width**: 100% of container
- **Font Sizes**: Reduce by 10%
- **Spacing**: Reduce by 20%

### Tablet (768px - 1024px)
- **Card Width**: 280px - 320px
- **Grid**: 2-3 columns
- **Font Sizes**: Base sizes

### Desktop (1024px+)
- **Card Width**: 320px - 360px
- **Grid**: 3-4 columns
- **Font Sizes**: Base sizes

---

## EXPORT SPECIFICATIONS

### Image Exports
- **Format**: PNG (for photos), SVG (for icons)
- **Resolution**: 2x (Retina)
- **Compression**: 80% quality

### Component Exports
- **Format**: SVG
- **Optimization**: Remove unnecessary metadata
- **Naming**: kebab-case (e.g., product-card-standard)

---

## FIGMA ORGANIZATION

### File Structure
```
📁 Marketplace Product Cards
  📁 Templates
    📄 Standard Card
    📄 Trending Card
    📄 Featured Card
    📄 Minimal Card
    📄 List View Card
  📁 Components
    📄 Badges
    📄 Buttons
    📄 Ratings
    📄 Avatars
  📁 Assets
    📁 Icons
    📁 Images
    📁 Patterns
```

### Naming Convention
- **Frames**: kebab-case
- **Components**: PascalCase
- **Layers**: camelCase
- **Assets**: descriptive names

---

## ANIMATION SPECIFICATIONS

### Micro-interactions
- **Card Hover**: 0.3s ease-out
- **Button Press**: 0.1s ease-in
- **Badge Appear**: 0.2s ease-out

### Loading States
- **Skeleton**: 1s pulse animation
- **Image Load**: 0.5s fade-in
- **Content**: Staggered appearance

---

## ACCESSIBILITY GUIDELINES

### Color Contrast
- **Normal Text**: 4.5:1 minimum
- **Large Text**: 3:1 minimum
- **Interactive Elements**: 3:1 minimum

### Focus Management
- **Tab Order**: Logical flow
- **Focus Indicators**: Visible 2px outline
- **Keyboard Access**: All interactive elements

### Screen Reader Support
- **Alt Text**: Descriptive for images
- **ARIA Labels**: For interactive elements
- **Semantic Structure**: Proper heading hierarchy

---

## TESTING CHECKLIST

### Visual Testing
- [ ] All text readable at all sizes
- [ ] Colors consistent across templates
- [ ] Images display correctly
- [ ] Borders and shadows render properly

### Interactive Testing
- [ ] Hover states work correctly
- [ ] Focus states visible
- [ ] Click targets adequate (44px minimum)
- [ ] Animations smooth and performant

### Responsive Testing
- [ ] Mobile layout works
- [ ] Tablet layout works
- [ ] Desktop layout works
- [ ] Text scales appropriately

---

## IMPLEMENTATION NOTES

### CSS Variables
```css
:root {
  --card-border-radius: 12px;
  --card-padding: 16px;
  --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  --primary-color: #3B82F6;
  --text-primary: #111827;
  --text-secondary: #6B7280;
}
```

### Component Classes
```css
.product-card {
  border-radius: var(--card-border-radius);
  padding: var(--card-padding);
  box-shadow: var(--card-shadow);
  transition: transform 0.3s ease-out;
}

.product-card:hover {
  transform: translateY(-4px);
}
```

---

## NEXT STEPS

1. **Create Figma file** with these specifications
2. **Build component library** with all elements
3. **Create variants** for each template
4. **Test responsiveness** across breakpoints
5. **Export assets** for development
6. **Document usage** guidelines for team

Remember: **Consistency is key** to a professional marketplace experience! 🎨
