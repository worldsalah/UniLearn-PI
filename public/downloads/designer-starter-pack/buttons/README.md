# MARKETPLACE BUTTON COMPONENTS
## Designer Starter Pack - Button Library

### OVERVIEW
Comprehensive button component library for the UniLearn marketplace. Ensures consistent interaction patterns and visual hierarchy across all marketplace interfaces.

---

## BUTTON TYPES

### 1. PRIMARY BUTTONS
**Use**: Main actions, conversions, CTAs

#### Specifications
- **Height**: 40px (medium), 48px (large)
- **Padding**: 16px 24px (medium), 20px 32px (large)
- **Border Radius**: 8px
- **Font Weight**: 600 (SemiBold)
- **Font Size**: 14px (medium), 16px (large)

#### Colors
- **Background**: #3B82F6
- **Hover**: #2563EB
- **Active**: #1D4ED8
- **Text**: #FFFFFF

#### States
```
Default: Solid blue background
Hover: Darker blue, 0.1s ease transition
Active: Slightly darker, scale(0.98)
Disabled: #9CA3AF background, cursor: not-allowed
```

### 2. SECONDARY BUTTONS
**Use**: Alternative actions, less important CTAs

#### Specifications
- **Height**: 40px (medium), 48px (large)
- **Padding**: 16px 24px (medium), 20px 32px (large)
- **Border**: 2px solid #3B82F6
- **Background**: Transparent
- **Text**: #3B82F6

#### States
```
Default: Transparent with blue border
Hover: Blue background with white text
Active: Darker blue background
Disabled: #D1D5DB border, #9CA3AF text
```

### 3. OUTLINE BUTTONS
**Use**: Subtle actions, tertiary options

#### Specifications
- **Height**: 36px
- **Padding**: 12px 20px
- **Border**: 1px solid #D1D5DB
- **Background**: Transparent
- **Text**: #374151

#### States
```
Default: Light gray border
Hover: #3B82F6 border and text
Active: #3B82F6 background with white text
```

### 4. GHOST BUTTONS
**Use**: Minimal actions, icon buttons

#### Specifications
- **Height**: 32px
- **Padding**: 8px 16px
- **Background**: Transparent
- **Text**: #6B7280

#### States
```
Default: Transparent with gray text
Hover: #F3F4F6 background
Active: #E5E7EB background
```

### 5. ICON BUTTONS
**Use**: Actions with icons only

#### Specifications
- **Size**: 32px × 32px, 40px × 40px, 48px × 48px
- **Border Radius**: 8px
- **Icon Size**: 16px, 20px, 24px

#### Variants
- **Primary**: Blue background, white icon
- **Secondary**: Gray background, dark icon
- **Ghost**: Transparent, gray icon

---

## BUTTON SIZES

### SIZE SYSTEM
```
XS: 28px height, 12px padding, 12px font
SM: 32px height, 12px padding, 12px font
MD: 40px height, 16px padding, 14px font
LG: 48px height, 20px padding, 16px font
XL: 56px height, 24px padding, 18px font
```

### USAGE GUIDELINES
- **XS**: Table actions, compact interfaces
- **SM**: Form actions, secondary CTAs
- **MD**: Primary actions, most common
- **LG**: Hero sections, important CTAs
- **XL**: Mobile CTAs, accessibility focus

---

## SPECIALIZED BUTTONS

### 1. CTA BUTTONS
**Use**: Marketing, conversions, hero sections

#### Features
- **Gradient Background**: #3B82F6 to #8B5CF6
- **Larger Size**: 48px minimum height
- **Enhanced Shadow**: 0 8px 25px rgba(59, 130, 246, 0.3)
- **Glow Effect**: Subtle blue glow on hover

### 2. DANGER BUTTONS
**Use**: Destructive actions, deletions

#### Colors
- **Background**: #EF4444
- **Hover**: #DC2626
- **Active**: #B91C1C
- **Text**: #FFFFFF

### 3. SUCCESS BUTTONS
**Use**: Positive actions, confirmations

#### Colors
- **Background**: #10B981
- **Hover**: #059669
- **Active**: #047857
- **Text**: #FFFFFF

### 4. WARNING BUTTONS
**Use**: Cautionary actions, warnings

#### Colors
- **Background**: #F59E0B
- **Hover**: #D97706
- **Active**: #B45309
- **Text**: #FFFFFF

---

## BUTTON GROUPS

### HORIZONTAL GROUPS
```
[Button] [Button] [Button]
Spacing: 8px between buttons
Alignment: Left or center
```

### VERTICAL GROUPS
```
[Button]
[Button]
[Button]
Spacing: 4px between buttons
Full width buttons
```

### TOOLBAR GROUPS
```
[Icon] [Icon] [Icon]
Compact: 32px height
Unified: Connected borders
```

---

## LOADING STATES

### SPINNER BUTTONS
- **Spinner**: 16px × 16px, white
- **Text**: Replace with "Loading..."
- **Disabled**: No interaction during loading
- **Animation**: Rotate animation, 1s duration

### SKELETON BUTTONS
- **Background**: #E5E7EB
- **Animation**: Pulse effect
- **Height**: Same as normal button
- **Border Radius**: Same as normal button

---

## ICON INTEGRATION

### ICON + TEXT
```
[Icon] Text Content
Icon Position: Left (default)
Spacing: 8px between icon and text
Icon Size: 16px (medium), 20px (large)
```

### TEXT + ICON
```
Text Content [Icon]
Icon Position: Right
Spacing: 8px between text and icon
Use for: "Next →", "Download ↓"
```

### ICON ONLY
```
[Icon]
Square aspect ratio
Center alignment
Tooltip on hover
```

---

## ACCESSIBILITY

### FOCUS STATES
- **Outline**: 2px solid #3B82F6
- **Offset**: 2px
- **Border Radius**: Same as button
- **High Contrast**: Always visible

### KEYBOARD NAVIGATION
- **Tab Order**: Logical flow
- **Enter/Space**: Activate button
- **Focus Management**: Visible focus indicator

### SCREEN READERS
- **ARIA Labels**: Descriptive text
- **Role**: button="button"
- **States**: aria-pressed, aria-disabled

---

## RESPONSIVE BEHAVIOR

### MOBILE OPTIMIZATION
- **Minimum Touch Target**: 44px × 44px
- **Spacing**: Increased on mobile
- **Text Size**: Minimum 16px for readability
- **Full Width**: Buttons on small screens

### TABLET ADAPTATION
- **Medium Size**: Default on tablets
- **Touch Optimized**: Adequate spacing
- **Hover States**: Disabled on touch devices

### DESKTOP ENHANCEMENTS
- **Hover Effects**: Full hover states
- **Tooltips**: On icon buttons
- **Keyboard Shortcuts**: Where appropriate

---

## ANIMATION SPECIFICATIONS

### TRANSITIONS
```css
.button {
  transition: all 0.2s ease-out;
}

.button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.button:active {
  transform: translateY(0);
  transition-duration: 0.1s;
}
```

### MICRO-INTERACTIONS
- **Hover**: 0.2s ease-out
- **Active**: 0.1s ease-in
- **Focus**: 0.1s ease-out
- **Loading**: 1s infinite

---

## FIGMA COMPONENTS

### COMPONENT STRUCTURE
```
📁 Button Components
  📄 Primary Button
  📄 Secondary Button
  📄 Outline Button
  📄 Ghost Button
  📄 Icon Button
  📄 Button Group
  📄 Loading States
```

### VARIANT PROPERTIES
- **Size**: XS, SM, MD, LG, XL
- **State**: Default, Hover, Active, Disabled
- **Type**: Primary, Secondary, Outline, Ghost
- **Icon**: Left, Right, Only

### AUTO LAYOUT
- **Horizontal**: Button content
- **Padding**: Responsive to size
- **Constraints**: Respects content
- **Spacing**: Consistent across variants

---

## IMPLEMENTATION CODE

### CSS CUSTOM PROPERTIES
```css
:root {
  --button-height-md: 40px;
  --button-padding-md: 16px 24px;
  --button-border-radius: 8px;
  --button-font-weight: 600;
  --button-transition: all 0.2s ease-out;
  
  --button-primary-bg: #3B82F6;
  --button-primary-hover: #2563EB;
  --button-primary-active: #1D4ED8;
  
  --button-secondary-border: #3B82F6;
  --button-secondary-text: #3B82F6;
  --button-secondary-hover-bg: #3B82F6;
}
```

### BASE CLASSES
```css
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: var(--button-border-radius);
  font-weight: var(--button-font-weight);
  text-decoration: none;
  cursor: pointer;
  transition: var(--button-transition);
  font-family: inherit;
  font-size: inherit;
  line-height: 1;
}

.btn:focus {
  outline: 2px solid var(--button-primary-bg);
  outline-offset: 2px;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
```

### MODIFIER CLASSES
```css
.btn--primary {
  background-color: var(--button-primary-bg);
  color: white;
}

.btn--primary:hover:not(:disabled) {
  background-color: var(--button-primary-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn--secondary {
  background-color: transparent;
  color: var(--button-secondary-text);
  border: 2px solid var(--button-secondary-border);
}

.btn--secondary:hover:not(:disabled) {
  background-color: var(--button-secondary-hover-bg);
  color: white;
}

.btn--md {
  height: var(--button-height-md);
  padding: var(--button-padding-md);
  font-size: 14px;
}
```

---

## USAGE EXAMPLES

### BASIC USAGE
```html
<button class="btn btn--primary btn--md">
  Get Started
</button>

<button class="btn btn--secondary btn--md">
  Learn More
</button>
```

### WITH ICONS
```html
<button class="btn btn--primary btn--md">
  <svg class="btn__icon" width="16" height="16">
    <!-- Icon SVG -->
  </svg>
  Download Now
</button>
```

### BUTTON GROUP
```html
<div class="btn-group">
  <button class="btn btn--primary">Save</button>
  <button class="btn btn--secondary">Cancel</button>
</div>
```

---

## TESTING CHECKLIST

### VISUAL TESTING
- [ ] All sizes render correctly
- [ ] Colors match specifications
- [ ] Borders and shadows consistent
- [ ] Icons properly aligned

### INTERACTION TESTING
- [ ] Hover states work on all buttons
- [ ] Active states feel responsive
- [ ] Focus states are visible
- [ ] Disabled states are clear

### ACCESSIBILITY TESTING
- [ ] Keyboard navigation works
- [ ] Screen reader announcements
- [ ] Color contrast meets WCAG
- [ ] Touch targets adequate

### RESPONSIVE TESTING
- [ ] Mobile touch targets work
- [ ] Tablet layouts adapt
- [ ] Desktop hover states function
- [ ] Text scales appropriately

---

## BEST PRACTICES

### DO's
✅ Use consistent button hierarchy
✅ Maintain adequate touch targets
✅ Provide clear visual feedback
✅ Use descriptive button text
✅ Consider loading states
✅ Test across devices

### DON'Ts
❌ Use multiple primary buttons
❌ Make buttons too small
❌ Use vague button text
❌ Ignore accessibility
❌ Overcomplicate button styles
❌ Forget disabled states

---

## NEXT STEPS

1. **Create Figma components** with all variants
2. **Build React/Vue components** for development
3. **Implement design tokens** for consistency
4. **Create usage documentation** for team
5. **Test across browsers** and devices
6. **Establish version control** for updates

Remember: **Buttons are the primary interaction point** - get them right! 🎯
