# Post Reviewer Brand Guidelines

> **For AI assistants:** Use these values when generating UI components, styles, or any visual elements for Post Reviewer.

## Colors

### Primary Palette
| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| Primary (Approval Green) | `#10B981` | `16, 185, 129` | CTAs, success states, brand accent |
| Primary Dark | `#059669` | `5, 150, 105` | Hover/pressed states |
| Primary Light | `#D1FAE5` | `209, 250, 229` | Backgrounds, subtle highlights |
| Secondary (Slate) | `#1E293B` | `30, 41, 59` | Headlines, body text, dark backgrounds |
| Accent (Amber) | `#F59E0B` | `245, 158, 11` | Pending states, warnings, highlights |
| Accent Light | `#FEF3C7` | `254, 243, 199` | Pending backgrounds |

### Neutrals
| Name | Hex | Usage |
|------|-----|-------|
| Neutral 50 | `#F8FAFC` | Page backgrounds |
| Neutral 100 | `#F1F5F9` | Card backgrounds |
| Neutral 200 | `#E2E8F0` | Borders, dividers |
| Neutral 300 | `#CBD5E1` | Disabled states |
| Neutral 400 | `#94A3B8` | Placeholder text |
| Neutral 500 | `#64748B` | Secondary text |
| Neutral 600 | `#475569` | Body text |
| Neutral 900 | `#0F172A` | Darkest text |

### Semantic Colors
| State | Hex | Background |
|-------|-----|------------|
| Success | `#10B981` | `#D1FAE5` |
| Warning | `#F59E0B` | `#FEF3C7` |
| Error | `#EF4444` | `#FEE2E2` |
| Info | `#3B82F6` | `#DBEAFE` |

## Typography

### Font Stack
```css
--font-logo: "Plus Jakarta Sans", system-ui, sans-serif;
--font-heading: "Plus Jakarta Sans", system-ui, sans-serif;
--font-body: "Inter", system-ui, sans-serif;
--font-mono: "JetBrains Mono", monospace;
```

### Logo Wordmark
- **Font:** Plus Jakarta Sans
- **Weight:** 700 (Bold)
- **Letter Spacing:** -0.04em
- **Text:** "Post Reviewer"

### Type Scale
| Element | Font | Size | Weight | Letter Spacing |
|---------|------|------|--------|----------------|
| H1 | Plus Jakarta Sans | 48px | 700 | -0.03em |
| H2 | Plus Jakarta Sans | 36px | 700 | -0.03em |
| H3 | Plus Jakarta Sans | 24px | 600 | -0.02em |
| H4 | Plus Jakarta Sans | 18px | 600 | -0.01em |
| Body | Inter | 16px | 400 | normal |
| Small | Inter | 14px | 400 | normal |
| Caption | Inter | 12px | 500 | normal |

## Spacing & Radius

### Border Radius
| Element | Radius |
|---------|--------|
| Buttons | `10px` |
| Cards | `16px` |
| Inputs | `8px` |
| Badges | `20px` (pill) |
| Modals | `24px` |

### Standard Spacing
Use 4px base unit: `4, 8, 12, 16, 20, 24, 32, 40, 48, 64`

## Components

### Buttons
```css
/* Primary */
background: #10B981;
color: #FFFFFF;
padding: 12px 24px;
border-radius: 10px;
font-weight: 600;

/* Secondary */
background: #FFFFFF;
color: #1E293B;
border: 2px solid #E2E8F0;
padding: 12px 24px;
border-radius: 10px;
font-weight: 600;
```

### Status Badges
```css
/* Approved */
background: #D1FAE5;
color: #059669;

/* Pending */
background: #FEF3C7;
color: #B45309;

/* Changes Requested */
background: #FEE2E2;
color: #DC2626;

/* Draft */
background: #F1F5F9;
color: #475569;
```

### Cards
```css
background: #FFFFFF;
border: 1px solid #E2E8F0;
border-radius: 16px;
padding: 20px;
```

## Logo Files

Located in `/brand/` directory:
- `post-reviewer-icon.svg` — Icon only, light mode
- `post-reviewer-icon-dark.svg` — Icon only, dark mode
- `post-reviewer-logo-full.svg` — Full logo, light mode
- `post-reviewer-logo-full-dark.svg` — Full logo, dark mode
- `post-reviewer-favicon.ico` — Multi-size favicon
- `post-reviewer-icon-{size}.png` — PNG icons (16-512px)
- `post-reviewer-logo-{width}x{height}.png` — PNG logos

## CSS Variables (Copy-Paste Ready)

```css
:root {
  /* Colors */
  --color-primary: #10B981;
  --color-primary-dark: #059669;
  --color-primary-light: #D1FAE5;
  --color-secondary: #1E293B;
  --color-accent: #F59E0B;
  --color-accent-light: #FEF3C7;
  
  --color-success: #10B981;
  --color-warning: #F59E0B;
  --color-error: #EF4444;
  --color-info: #3B82F6;
  
  --color-neutral-50: #F8FAFC;
  --color-neutral-100: #F1F5F9;
  --color-neutral-200: #E2E8F0;
  --color-neutral-300: #CBD5E1;
  --color-neutral-400: #94A3B8;
  --color-neutral-500: #64748B;
  --color-neutral-600: #475569;
  --color-neutral-900: #0F172A;
  
  /* Typography */
  --font-logo: "Plus Jakarta Sans", system-ui, sans-serif;
  --font-heading: "Plus Jakarta Sans", system-ui, sans-serif;
  --font-body: "Inter", system-ui, sans-serif;
  --font-mono: "JetBrains Mono", monospace;
  
  /* Radius */
  --radius-sm: 8px;
  --radius-md: 10px;
  --radius-lg: 16px;
  --radius-xl: 24px;
  --radius-full: 9999px;
}
```

## Tailwind Config (If Using Tailwind)

```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#10B981',
          dark: '#059669',
          light: '#D1FAE5',
        },
        secondary: '#1E293B',
        accent: {
          DEFAULT: '#F59E0B',
          light: '#FEF3C7',
        },
      },
      fontFamily: {
        logo: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        heading: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        body: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace'],
      },
      letterSpacing: {
        logo: '-0.04em',
      },
      borderRadius: {
        'card': '16px',
        'button': '10px',
      },
    },
  },
}
```
