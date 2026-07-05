# XD Chat — CSS Folder Documentation

## Folder Path

```text
assets/css/
```

## Purpose

Is folder me XD Chat project ki saari CSS files rakhi jayengi. Har CSS file ka ek fixed purpose hoga.

## CSS File Structure

```text
01-reset.css
02-variables.css
03-base.css
04-layout.css
05-components.css
06-utilities.css
07-responsive.css
08-animations.css
```

## Completed Files

### 01-reset.css

Purpose:

Browser ke default margin, padding aur styling ko reset karna.

Contains:

* Global margin reset
* Global padding reset
* Box sizing
* Image responsive reset
* Link reset
* List reset
* Button reset
* Table reset

Status:

```text
Completed
Production Ready
```

---

### 02-variables.css

Purpose:

XD Chat ka complete design token system define karna.

Contains:

* Brand colors
* Background colors
* Text colors
* Border colors
* Typography
* Font weights
* Spacing
* Border radius
* Shadows
* Transitions
* Z-index
* Component sizes

Status:

```text
Completed
Production Ready
```

---

### 03-base.css

Purpose:

Project ke default base styles define karna.

Contains:

* HTML base
* Body font
* Body background
* Text color
* Heading styles
* Paragraph styles
* Selection color
* Container class
* Page wrapper
* Hidden utility

Status:

```text
Completed
Production Ready
```

---

## Pending Files

### 04-layout.css

Purpose:

Layout related styling.

Will contain:

* Header layout
* Sidebar layout
* Main content layout
* Grid system
* Section layout

Status:

```text
Pending
```

---

### 05-components.css

Purpose:

Reusable UI components.

Will contain:

* Buttons
* Inputs
* Cards
* Badges
* Alerts
* Chat widget
* Chat bubbles

Status:

```text
Pending
```

---

### 06-utilities.css

Purpose:

Reusable helper classes.

Will contain:

* Text alignment
* Flex helpers
* Margin helpers
* Padding helpers
* Display helpers

Status:

```text
Pending
```

---

### 07-responsive.css

Purpose:

Responsive design rules.

Will contain:

* Mobile layout
* Tablet layout
* Desktop adjustments

Status:

```text
Pending
```

---

### 08-animations.css

Purpose:

Animation and transition classes.

Will contain:

* Fade animation
* Slide animation
* Widget open animation
* Message animation

Status:

```text
Pending
```

---

## CSS Rules

1. Har CSS class `xd-` se start hogi.

Example:

```css
.xd-btn
.xd-card
.xd-input
.xd-widget
```

2. Direct colors use nahi karne.

Wrong:

```css
color: #2563eb;
```

Correct:

```css
color: var(--xd-color-primary);
```

3. Direct spacing, radius ya size use nahi karna.

Wrong:

```css
padding: 16px;
border-radius: 14px;
```

Correct:

```css
padding: var(--xd-space-4);
border-radius: var(--xd-radius-button);
```

4. Har file ka ek hi responsibility hoga.

5. Files ka order maintain rahega.

6. Hardcoded values allowed nahi hain.

7. Project CSS namespace hamesha `xd-` rahega.

## Locked Design Decisions

```text
Product Name  : XD Chat
Tagline       : Live Chat Made Simple
Primary Color : #2563EB
Font Family   : Inter
Icon Library  : Heroicons
Design Style  : Modern Rounded
Button Radius : 14px
Input Radius  : 14px
Card Radius   : 18px
Bubble Radius : 18px
```

## Last Updated

```text
04 July 2026
```
